<?php

// Classe para emissão de NFSe PMF Homologação / Produção

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
//
$codigoMunicipio = $_GET['idMunicipio'];

if (!isset($codigoMunicipio)) {

    echo json_encode(array("http_code" => "400", "message" => "Parâmetro idMunicipio não informado"));
}
else {

    $fileClass = './'.$codigoMunicipio.'/create.php';
    if (file_exists($fileClass))
        include $fileClass;
    else 
        echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe"));

}

?>