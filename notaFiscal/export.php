<?php

// Classe para exportar arquivo 
include_once '../config/database.php';
 
$database = new Database();
$db = $database->getConnection();

$dirAPI = basename(dirname(dirname( __FILE__ )));

// get posted data
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data);

$data->dataEmissao;
$data->codigoMunicipio;

// identifica Municipio para emissão NFSe
include_once '../objects/municipio.php';
$municipio = new Municipio($db);
$provedor = $municipio->buscaMunicipioProvedor($data->codigoMunicipio);

$arqPhp = ''; 
if ($provedor > '')
    $arqPhp = 'retry'.$provedor.'.php'; 

if (($arqPhp>'') && (file_exists($arqPhp))) {
    include $arqPhp;
}
else {

    $arrErr = array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe", 
                    "error" => "Município emitente = ".$emitente->codigoMunicipio,
                    "codigo" => "A02" );


                    $logMsg->register('S', 'notaFiscal.retry', $arrMsg['message'], $strData);


    continue;
}

?>