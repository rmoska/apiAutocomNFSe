<?php

// Classe para emissão de NFSe PMF em ambiente de Homologação

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../objects/notaFiscal.php';
include_once '../objects/emitente.php';

// get posted data
$data = json_decode(file_get_contents("php://input"));

$idNotaFiscal = $data->idNotaFiscal;

if (empty($idNotaFiscal)) {

    echo json_encode(array("http_code" => "400", "message" => "Parâmetro idNotaFiscal não informado"));
    exit;
}

$database = new Database();
$db = $database->getConnection();
 
$notaFiscal = new NotaFiscal($db);
$notaFiscal->idNotaFiscal = $idNotaFiscal;

$checkNF = $notaFiscal->check();
if ($checkNF["existe"] == 0) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", 
                            "message" => "Nota Fiscal não encontrada. idNotaFiscal=".$notaFiscal->idNotaFiscal));
    exit;
}

$notaFiscal->readOne();

$emitente = new Emitente($db);
$emitente->idEmitente = $notaFiscal->idEmitente;
$emitente->readOne();

if (is_null($emitente->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado para esta Autorização."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado para esta Autorização. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

$fileClass = './'.$emitente->codigoMunicipio.'/cancel.php';
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