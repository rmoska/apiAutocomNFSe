<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database and object files
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/autorizacao.php';
include_once '../objects/emitente.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
// prepare emitente object
$autorizacao = new Autorizacao($db);
 
// get id of emitente to be edited
$data = json_decode(file_get_contents("php://input"));

//    !empty($data->aedf) // AEDFe é autorização para Produção, então aceita branco para testes de Homologação
// make sure data is not empty
if(
    !empty($data->idEmitente) &&
    !empty($data->crt) &&
    !empty($data->cnae) &&
    !empty($data->cmc) &&
    !empty($data->senhaWeb) &&
    !empty($data->certificado) &&
    !empty($data->senha)
){
    // set autorizacao property values
    $autorizacao->idEmitente = $data->idEmitente;
    $autorizacao->crt = $data->crt;
    $autorizacao->cnae = $data->cnae;
    $autorizacao->aedf = $data->aedf;
    $autorizacao->cmc = $data->cmc;
    $autorizacao->senhaWeb = $data->senhaWeb;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;

    $emitente = new Emitente($db);
    $emitente->idEmitente = $data->idEmitente;
    $emitente->readOne();
    if (is_null($emitente->documento)) {

        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado para esta Autorização."));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado para esta Autorização. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    $documento = $emitente->documento;

    if ($autorizacao->check() == 0)
        $retorno = $autorizacao->create($emitente->documento);
    else 
        $retorno = $autorizacao->update($emitente->documento);
 
    if($retorno[0]){

        if (!$autorizacao->getToken("P")){ 

            http_response_code(401);
            echo json_encode(array("http_code" => 401, "message" => "Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado."));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        else {

            include_once '../comunicacao/signNFSe.php';
            $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
            $certificado = new SignNFSe($arraySign);
            if ($certificado->errStatus){
                http_response_code(401);
                echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $certificado->errMsg));
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$certificado->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
                exit;
            }
            $validade = $certificado->certDaysToExpire;
        }

        http_response_code(201);
        echo json_encode(array("http_code" => 201, "message" => "Autorização atualizada", "token" => $autorizacao->token, "validade" => $validade." dias"));
    }
    else{
 
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1]));
        $strData = json_encode($data);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}
else{
 
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>