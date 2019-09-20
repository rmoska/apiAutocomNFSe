<?php

// Classe para emissão de NFSe PMF Homologação / Produção

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../objects/emitente.php';

$database = new Database();
$db = $database->getConnection();

// get posted data
$data = json_decode(file_get_contents("php://input"));

//
if(empty($data->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível emitir Nota Fiscal. Emitente não identificado."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível emitir Nota Fiscal. Emitente não identificado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

//
$emitente = new Emitente($db);
$emitente->documento = $data->documento;
if (($idEmitente = $emitente->check()) == 0) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser emitida."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado. Nota Fiscal não pode ser emitida. Emitente=".$data->documento."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
$emitente->idEmitente = $idEmitente;
$emitente->readOne();

if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

$fileClass = './'.$emitente->codigoMunicipio.'/create.php';
if (file_exists($fileClass)) {

    include $fileClass;
}
else {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>