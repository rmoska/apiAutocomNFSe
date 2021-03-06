<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../shared/logMsg.php';
include_once '../shared/logReq.php';
include_once '../objects/emitente.php';
 
$database = new Database();
$db = $database->getConnection();
$logMsg = new LogMsg($db);
 
$emitente = new Emitente($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data);
$logReq = new LogReq($db);
$logReq->register('emitente.create', $strData, $data->documento, '');
 
// make sure data is not empty
if(
    !empty($data->documento) &&
    !empty($data->nome) &&
    !empty($data->logradouro) &&
    !empty($data->numero) &&
    !empty($data->bairro) &&
    !empty($data->codigoMunicipio) &&
    !empty($data->uf) &&
    !empty($data->email) 
){

    $emitente->documento = $data->documento;
    
    if (($idEmitente = $emitente->check()) > 0) {

        $emitente->idEmitente = $idEmitente;
        $emitente->readOne();
        $emitente->nome = $data->nome;
        $emitente->nomeFantasia = $data->nomeFantasia;
        $emitente->logradouro = $data->logradouro;
        $emitente->numero = $data->numero;
        $emitente->complemento = $data->complemento;
        $emitente->bairro = $data->bairro;
        $emitente->cep = $data->cep;
        $emitente->codigoMunicipio = $data->codigoMunicipio;
        $emitente->uf = $data->uf;
        $emitente->pais = $data->pais;
        $emitente->fone = $data->fone;
        $emitente->celular = $data->celular;
        $emitente->email = $data->email;
    
        $retorno = $emitente->update();

    }
    else {

        $emitente->nome = $data->nome;
        $emitente->nomeFantasia = $data->nomeFantasia;
        $emitente->logradouro = $data->logradouro;
        $emitente->numero = $data->numero;
        $emitente->complemento = $data->complemento;
        $emitente->bairro = $data->bairro;
        $emitente->cep = $data->cep;
        $emitente->codigoMunicipio = $data->codigoMunicipio;
        $emitente->uf = $data->uf;
        $emitente->pais = $data->pais;
        $emitente->fone = $data->fone;
        $emitente->celular = $data->celular;
        $emitente->email = $data->email;
   
        $retorno = $emitente->create();
    }

    if($retorno[0]){

        http_response_code(201);
        echo json_encode(array("http_code" => "201", "message" => "Emitente atualizado", "idEmitente" => $emitente->idEmitente));
    }
    else{
 
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Emitente. Serviço indisponível.", "erro" => $retorno[1], "codigo" => "A00"));
        $logMsg->register('E', 'emitente.create', 'Não foi possível incluir Emitente. Serviço indisponível.', $retorno[1]);
        exit;
    }
}
else{

    $descErr = '';
    if (empty($data->documento)) $descErr .= 'documento / ';
    if (empty($data->nome)) $descErr .= 'nome / ';
    if (empty($data->logradouro)) $descErr .= 'logradouro / ';
    if (empty($data->numero)) $descErr .= 'numero / ';
    if (empty($data->bairro)) $descErr .= 'bairro / ';
    if (empty($data->codigoMunicipio)) $descErr .= 'codigoMunicipio / ';
    if (empty($data->uf)) $descErr .= 'uf / ';
    if (empty($data->email)) $descErr .= 'email / '; 
    $descErr = substr($descErr, 0, -3);

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Emitente. Dados incompletos. Campo(s): ".$descErr, "codigo" => "A02"));
    $strData = json_encode($data);
    $logMsg->register('E', 'emitente.create', 'Não foi possível incluir Emitente. Dados incompletos.', $strData);
    exit;
}
?>