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
include_once '../shared/logMsg.php';
include_once '../objects/emitente.php';

// 
// quando chamada for na base teste, sempre mandar para homologação
$dirAPI = basename(dirname(dirname( __FILE__ )));
if ($dirAPI == "apiAutocomNFSe")
    $ambiente = "P"; // ===== PRODUÇÃO =====
else // if ( basename(dirname(dirname( __FILE__ ))) == "apiAutocomNFSe-teste")
    $ambiente = "H"; // ===== HOMOLOGAÇÃO =====

$database = new Database();
$db = $database->getConnection();
$logMsg = new LogMsg($db);

// get posted data
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data);

//
// confere e busca Emitente
if(empty($data->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível emitir Nota Fiscal. Emitente não identificado."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível emitir Nota Fiscal. Emitente não identificado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível emitir Nota Fiscal. Emitente não identificado.', $strData);
    exit;
}
//
$emitente = new Emitente($db);
$emitente->documento = $data->documento;
if (($idEmitente = $emitente->check()) == 0) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser emitida."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado. Nota Fiscal não pode ser emitida. Emitente=".$data->documento."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Emitente não cadastrado. Nota Fiscal não pode ser emitida.', 'Emitente='.$data->documento);
    exit;
}
$emitente->idEmitente = $idEmitente;
$emitente->readOne();

//
// confere e busca Municipio
if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Emitente sem Município definido no cadastro.', 'Emitente='.$data->documento);
    exit;
}

//
//identificação do serviço: emissão de NFSe
switch ($emitente->codigoMunicipio) {
    case '4205407': // SC - Florianópolis
        $arqPhp = 'createFLN.php'; break;
    case '4216602': // SC - São José
        $arqPhp = 'createBETHA.php'; break;
    case '4202305': // SC - Biguaçu
    case '4211900': // SC - Palhoça
        $arqPhp = 'createIPM.php'; break;
    case '4204202': // SC - Chapecó
    case '4208203': // SC - Itajaí
        $arqPhp = 'createPUBLICA.php'; break;
    case '4202008': // SC - Balneário Camboriú
        $arqPhp = 'createSIMPLISS.php'; break;
    case '4305108': // RS - Caxias do Sul
        $arqPhp = 'createINFISC.php'; break;
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
    $logMsg->register('E', 'notaFiscal.create', 'Município não disponível para emissão da NFSe.', 'Município='.$emitente->codigoMunicipio);
    exit;
}

?>