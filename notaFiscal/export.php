<?php

// Classe para exportar arquivo 
include_once '../config/database.php';
include_once '../shared/logMsg.php';
 
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

    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$codMunic."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.export', 'Município não disponível para emissão da NFSe.', 'Município='.$codMunic);
    exit;

}

?>