<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/autorizacao.php';
include_once '../objects/emitente.php';
 
$database = new Database();
$db = $database->getConnection();
 
$autorizacao = new Autorizacao($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));
 
// make sure data is not empty
if(
    !empty($data->idEmitente) &&
    !empty($data->crt) &&
    !empty($data->cnae) &&
    !empty($data->aedf) &&
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

        http_response_code(503);
        echo json_encode(array("http_code" => 503, "message" => "Emitente inválido para esta Autorização."));
        exit;
    }
    $documento = $emitente->documento;

    if ($autorizacao->check() > 0) {

        http_response_code(503);
        echo json_encode(array("http_code" => 503, "message" => "Já existe Autorização cadastrada para esta Emitente."));
        exit;
    }

    $retorno = $autorizacao->create($emitente->documento);
    if($retorno[0]){

        if (!$autorizacao->getToken()){

            http_response_code(503);
            echo json_encode(array("http_code" => 503, "message" => "Autorização com dados inválidos. Comunicação rejeitada."));
            exit;
        }
        else {

            include_once '../comunicacao/signNFSe.php';
            $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
            $certificado = new SignNFSe($arraySign);
            if (!is_object($certificado)){
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Certificado.", "erro" => $certificado));
                exit;
            }
            $validade = $certificado->certDaysToExpire;

        }

        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => 201, "message" => "Autorização incluída", "token" => $autorizacao->token, "validade" => $validade." dias"));
        exit;
    }
    else{

        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1]));
        exit;
    }
}
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("http_code" => 503, "message" => "Não foi possível incluir Autorizacao. Dados incompletos."));
}
?>