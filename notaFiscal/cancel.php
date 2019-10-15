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

$dirAPI = basename(dirname(dirname( __FILE__ )));

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

// check emitente
if ($notaFiscal->idEmitente != $data->idEmitente) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não confere com Nota original. Nota Fiscal não pode ser cancelada."));
    exit;
}

$emitente = new Emitente($db);
$emitente->idEmitente = $notaFiscal->idEmitente;
$emitente->readOne();
if (is_null($emitente->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

//
//identificação do serviço: emissão de NFSe
switch ($emitente->codigoMunicipio) {
    case '4205407': // SC - Florianópolis
        $arqPhp = 'cancelFLN.php'; break;
    case '4216602': // SC - São José
        $arqPhp = 'cancelBETHA.php'; break;
    case '4202305': // SC - Biguaçu
    case '4211900': // SC - Palhoça
        $arqPhp = 'cancelIPM.php'; break;
    case '4204202': // SC - Chapecó
    case '4208203': // SC - Itajaí
        $arqPhp = 'cancelPUBLICA.php'; break;
    case '4202008': // SC - Balneário Camboriú
        $arqPhp = 'cancelSIMPLISS.php'; break;
    case '4305108': // RS - Caxias do Sul
        $arqPhp = 'cancelINFISC.php'; break;
    default:
        $arqPhp = ''; break;
}

if (file_exists($arqPhp)) {

    include $arqPhp;
}
else {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
    

?>