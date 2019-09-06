<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database and object file
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/notaFiscal.php';
 

echo basename( __DIR__ )."   ---   ";
exit;

// get database connection
$database = new Database();
$db = $database->getConnection();
 
// prepare emitente object
$notaFiscal = new NotaFiscal($db);
 
// get emitente id
$notaFiscal->idNotaFiscal = isset($_GET['idNotaFiscal']) ? $_GET['idNotaFiscal'] : die();

// delete emitente
if($arqPDF = $notaFiscal->printDanfpse($notaFiscal->idNotaFiscal, $db)){
 
    // set response code - 200 ok
    http_response_code(200);
 
    // tell the user
    echo json_encode(array("message" => "Arquivo PDF criado", "linkPDF" => "http://www.autocominformatica.com.br/apiAutocomNFSe/".$arqPDF));
}
 
// if unable to delete emitente
else{
 
    // set response code - 503 service unavailable
    http_response_code(503);
 
    // tell the user
    echo json_encode(array("message" => "Não foi possível gerar arquivo PDF."));
}
?>