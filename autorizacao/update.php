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
include_once '../objects/emitente.php';

$dirAPI = basename(dirname(dirname( __FILE__ )));

// get database connection
$database = new Database();
$db = $database->getConnection();

$logMsg = new LogMsg($db);
 
// get id of emitente to be edited
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data); // armazena para log

//
if(empty($data->idEmitente)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Emitente não identificado."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Emitente não identificado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização. Emitente não identificado.', $strData);
    exit;
}

$emitente = new Emitente($db);
$emitente->idEmitente = $data->idEmitente;
$emitente->readOne();

if (is_null($emitente->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado para esta Autorização."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado para esta Autorização. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Emitente não cadastrado para esta Autorização.', 'Emitente='.$data->idEmitente);
    exit;
}

if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro."));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Emitente sem Município definido no cadastro.', 'Emitente='.$data->idEmitente);
    exit;
}

//
//identificação do serviço: emissão de NFSe
switch ($emitente->codigoMunicipio) {
    case '4106902': // PR - Curitiba
        $arqPhp = 'update4106902.php'; break;
    case '4205407': // SC - Florianópolis
        $arqPhp = 'updateFLN.php'; break;
    case '4216602': // SC - São José
        $arqPhp = 'updateBETHA.php'; break;
    case '4202305': // SC - Biguaçu
    case '4211900': // SC - Palhoça
        $arqPhp = 'updateIPM.php'; break;
    case '4204202': // SC - Chapecó
    case '4208203': // SC - Itajaí
        $arqPhp = 'updatePUBLICA.php'; break;
    case '4202008': // SC - Balneário Camboriú
    case '3549102': // SC - Balneário Camboriú
        $arqPhp = 'updateSIMPLISS.php'; break;
    case '4305108': // RS - Caxias do Sul
        $arqPhp = 'updateINFISC.php'; break;
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
    $logMsg->register('E', 'autorizacao.update', 'Município não disponível para emissão da NFSe.', 'Município='.$emitente->codigoMunicipio);
    exit;
}

?>