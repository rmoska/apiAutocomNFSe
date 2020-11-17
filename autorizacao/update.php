<?php
/** 
* Classe autorizacao.update
* Cria ou atualiza registro de login para prefeituras e armazena certificado digital
* Quando possível, emite nota fiscal em homologação para validar 
*
* @author Rodrigo Moskorz
* @copyright  Autocom Informática 
* @since 2019-09
* @version 2020-07 : incluída tag 'documento' para evitar sobreposição incorreta do registro
*/ 

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

$database = new Database();
$db = $database->getConnection();

$logMsg = new LogMsg($db);
 
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data); // armazena para log

// confere idEmitente e documento prenchido
if(empty($data->idEmitente) || empty($data->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Emitente não identificado. Id=".$data->idEmitente." Doc=".$data->documento.$strData, "codigo" => "A06"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Emitente não identificado.  Id=".$data->idEmitente." Doc=".$data->documento."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização. Emitente não identificado.',  "Id=".$data->idEmitente." Doc=".$data->documento);
    exit;
}

// confere se existe Emitente para IdEmitente
$emitente = new Emitente($db);
$emitente->idEmitente = $data->idEmitente;
$emitente->readOne();

if (is_null($emitente->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado para esta Autorização.", "codigo" => "A06"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado para esta Autorização. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Emitente não cadastrado para esta Autorização.', 'Emitente='.$data->idEmitente);
    exit;
}

// confere se existe Emitente para documento e igual para IdEmitente (alt 03/07/2020)
$emitenteDoc = new Emitente($db);
$emitenteDoc->documento = $data->documento;
$emitenteDoc->idEmitente = $emitenteDoc->check();

if (($emitente->idEmitente != $emitenteDoc->idEmitente)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente inconsistente com esta Autorização. Id=".$data->idEmitente." Doc=".$data->documento, "codigo" => "A06"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente inconsistente com esta Autorização. Id=".$data->idEmitente." Doc=".$data->documento."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Emitente inconsistente com esta Autorização.', "Id=".$data->idEmitente." Doc=".$data->documento);
    exit;
}

if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro.", "codigo" => "A02"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Emitente sem Município definido no cadastro.', 'Emitente='.$data->idEmitente);
    exit;
}

//
// especificação do provedor
switch ($emitente->codigoMunicipio) {
    case '2927408': // BA - Salvador
        $arqPhp = 'updateABRASF1_0.php'; break;
    case '3135456': // MG - Jenipapo de Minas
        $arqPhp = 'updateSINTESE.php'; break;
    case '4106902': // PR - Curitiba
        $arqPhp = 'updateCuritiba.php'; break;
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
        $arqPhp = 'updateSIMPLISS.php'; break;
    case '4218707': // SC - Tubarão
        $arqPhp = 'updateMODERNA.php'; break;
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
    echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe.", "codigo" => "A02"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Município não disponível para emissão da NFSe.', 'Município='.$emitente->codigoMunicipio);
    exit;
}

?>