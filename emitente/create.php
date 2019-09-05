<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/emitente.php';
 
$database = new Database();
$db = $database->getConnection();
 
$emitente = new Emitente($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));
 
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
    // set emitente property values
    $emitente->documento = $data->documento;
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
    
    if ($idEmitente = $emitente->check() > 0) {

        // set response code - 400 bad request
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Emitente já existe para este Documento:".$emitente->documento, "idEmitente" => $idEmitente));

        exit;
    }
    // create emitente
    $retorno = $emitente->create();
    if($retorno[0]){

        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => "201", "message" => "Emitente incluído", "idEmitente" => $emitente->idEmitente));
    }
     // if unable to create emitente, tell the user
    else{
 
        // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Emitente. Serviço indisponível.", "erro" => $retorno[1]));
    }
}
 
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Emitente. Dados incompletos."));
}
?>