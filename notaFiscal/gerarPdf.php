<?php
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

    $fileClass = './'.$codigoMunicipio.'/gerarPdf.php';
    if (file_exists($fileClass)) {
        include $fileClass;

        // include database and object file
        include_once '../objects/notaFiscal.php';
        
        // get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        $notaFiscal = new NotaFiscal($db);
        
        $notaFiscal->idNotaFiscal = isset($_GET['idNotaFiscal']) ? $_GET['idNotaFiscal'] : die();

        $checkNF = $notaFiscal->check();
        if ($checkNF["existe"] == 0) {

            http_response_code(400);
            echo json_encode(array("http_code" => "400", 
                                    "message" => "Nota Fiscal não encontrada. idNotaFiscal=".$notaFiscal->idNotaFiscal));
        }
        else{

            $gerarPdf = new gerarPdf();

            if($arqPDF = $gerarPdf->printDanfpse($notaFiscal->idNotaFiscal, $db)){
    
                http_response_code(200);
                echo json_encode(array("http_code" => "200", 
                                        "message" => "Arquivo PDF criado", "linkPDF" => "http://www.autocominformatica.com.br/apiAutocomNFSe/".$arqPDF));
            }
            
            // if unable to delete emitente
            else{
            
                http_response_code(500);
                echo json_encode(array("http_code" => "500", 
                                        "message" => "Não foi possível gerar arquivo PDF. idNotaFiscal=".$notaFiscal->idNotaFiscal));
            }
        

        }


    }
    else 
        echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe"));

}

?>