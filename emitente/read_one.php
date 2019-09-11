<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
 
// include database and object files
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../objects/emitente.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
// prepare emitente object
$emitente = new Emitente($db);
 
// set ID property of record to read
$emitente->idEmitente = isset($_GET['idEmitente']) ? $_GET['idEmitente'] : die();
 
// read the details of emitente to be edited
$emitente->readOne();
 
if($emitente->documento!=null){
    // create array
    $emitente_item=array(
        "idEmitente" => $emitente->idEmitente,
        "documento" => $emitente->documento,
        "nome" => $emitente->nome,
        "nomeFantasia" => $emitente->nomeFantasia,
        "logradouro" => $emitente->logradouro,
        "numero" => $emitente->numero,
        "complemento" => $emitente->complemento,
        "bairro" => $emitente->bairro,
        "cep" => $emitente->cep,
        "codigoMunicipio" => $emitente->codigoMunicipio,
        "uf" => $emitente->uf,
        "pais" => $emitente->pais,
        "fone" => $emitente->fone,
        "celular" => $emitente->celular,
        "email" => $emitente->email
    );

    // set response code - 200 OK
    http_response_code(200);
 
    // make it json format
    echo json_encode($emitente_item);
}
 
else{
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user product does not exist
    echo json_encode(array("message" => "Emitente não encontrado."));
}
?>