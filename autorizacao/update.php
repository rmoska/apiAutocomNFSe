<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../shared/http_response_code.php';
include_once '../objects/emitente.php';

// get id of emitente to be edited
$data = json_decode(file_get_contents("php://input"));

//
if(empty($data->idEmitente)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Emitente não identificado."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Emitente não identificado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

echo '1';
$emitente = new Emitente($db);
$emitente->idEmitente = $data->idEmitente;
$emitente->readOne();
echo '2';

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
else {

    $fileClass = './'.$emitente->$codigoMunicipio.'/update.php';
    if (file_exists($fileClass)) {

        include $fileClass;
    }
    else {

        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe."));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}

?>