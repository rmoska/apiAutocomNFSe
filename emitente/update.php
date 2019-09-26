<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database and object files
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../objects/emitente.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
// prepare emitente object
$emitente = new Emitente($db);
 
// get id of emitente to be edited
$data = json_decode(file_get_contents("php://input"));
 
if(
    !empty($data->idEmitente) &&
    !empty($data->nome) &&
    !empty($data->logradouro) &&
    !empty($data->numero) &&
    !empty($data->bairro) &&
    !empty($data->cep) &&
    !empty($data->uf) &&
    !empty($data->codigoMunicipio) &&
    !empty($data->email)
){

    // set ID property of emitente to be edited
    $emitente->idEmitente = $data->idEmitente;
    
    // set emitente property values
    $emitente->nome = $data->nome;
    $emitente->nomeFantasia = $data->nomeFantasia;
    $emitente->logradouro = $data->logradouro;
    $emitente->numero = $data->numero;
    $emitente->complemento = $data->complemento;
    $emitente->bairro = $data->bairro;
    $emitente->cep = $data->cep;
    $emitente->uf = $data->uf;
    $emitente->codigoMunicipio = $data->codigoMunicipio;
    $emitente->pais = $data->pais;
    $emitente->fone = $data->fone;
    $emitente->celular = $data->celular;
    $emitente->email = $data->email;

    // update emitente
    $retorno = $emitente->update();
    if($retorno[0]){
    
        http_response_code(201);
        echo json_encode(array("http_code" => "200", "message" => "Emitente atualizado", "idEmitente" => $emitente->idEmitente));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente atualizado. idEmitente=".$emitente->idEmitente."\n"), 3, "../arquivosNFSe/apiOK.log");
        exit;
    }
    
    // if unable to update emitente, tell the user
    else{
    
        // set response code - 503 service unavailable
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Emitente. Serviço indisponível.", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Emitente. Serviço indisponível. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}
else{
 
    $descErr = '';
    if (empty($data->idEmitente)) $descErr .= 'idEmitente / ';
    if (empty($data->documento)) $descErr .= 'documento / ';
    if (empty($data->nome)) $descErr .= 'nome / ';
    if (empty($data->logradouro)) $descErr .= 'logradouro / ';
    if (empty($data->numero)) $descErr .= 'numero / ';
    if (empty($data->bairro)) $descErr .= 'bairro / ';
    if (empty($data->codigoMunicipio)) $descErr .= 'codigoMunicipio / ';
    if (empty($data->uf)) $descErr .= 'uf / ';
    if (empty($data->email)) $descErr .= 'email / '; 
    $descErr = substr($descErr, 0, -3);

    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível atualizar Emitente. Dados incompletos. Campo(s): ".$descErr));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Emitente. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>