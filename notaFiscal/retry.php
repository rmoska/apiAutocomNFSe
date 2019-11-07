<?php

// Classe para repetir tentativa de emissão de NFSe PMF pendentes por Servidor Indisponível / Timeout

//
// statusErr 
// 0 = situação mantida (timeout)
// 1 = situação mantida, erro autorização
// 2 = erro no processamento, nf excluída 
// 3 = emitida com sucesso
function logErro($statusErr, $arrMsg, $objNF){

    // retorna msg erro / sucesso / situação mantida
    if ($statusErr == 1) {

        $strData = json_encode($arrMsg);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$strData."\n"), 3, "../arquivosNFSe/apiRetry.log");
    }
    else if ($statusErr == 2) {

        $objNF->deleteCompletoTransaction();
        $strData = json_encode($arrMsg);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$strData."\n"), 3, "../arquivosNFSe/apiRetry.log");
    }
    else if ($statusErr == 3) {

        $strData = json_encode($arrMsg);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$strData."\n"), 3, "../arquivosNFSe/apiRetry.log");
    }   
}

include_once '../config/database.php';
include_once '../objects/notaFiscal.php';
 
$database = new Database();
$db = $database->getConnection();

$dirAPI = basename(dirname(dirname( __FILE__ )));

$notaFiscal = new NotaFiscal($db);
 
$stmt = $notaFiscal->readPendente();

//
// se não encontrou registros, encerra processamento
if($stmt->rowCount() == 0)
    exit;
 
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/emitente.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../objects/municipio.php';
include_once '../shared/utilities.php';
$utilities = new Utilities();

while ($rNF = $stmt->fetch(PDO::FETCH_ASSOC)){

    $notaFiscal = new NotaFiscal($db);
    $notaFiscal->idNotaFiscal = $rNF["idNotaFiscal"];
    $notaFiscal->readOne();

    $tomador = new Tomador($db);
    $tomador->idTomador = $notaFiscal->idTomador;
    $tomador->readOne();

    $emitente = new Emitente($db);
    $emitente->idEmitente = $notaFiscal->idEmitente;
    $emitente->readOne();

    $notaFiscalItem = new NotaFiscalItem($db);
    $arrayNotaFiscalItem = $notaFiscalItem->read($notaFiscal->idNotaFiscal);

    $totalItens = 0;
    $vlTotBC = 0; 
    $vlTotISS = 0; 
    $vlTotServ = 0; 
    foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {

        $totalItens += floatval($notaFiscalItem->valorTotal);
        $vlTotServ += $notaFiscalItem->valorTotal;
        $vlTotBC += $notaFiscalItem->valorBCIss; 
        $vlTotISS += $notaFiscalItem->valorIss; 
    }
    if (number_format($totalItens,2,'.','') != number_format($notaFiscal->valorTotal,2,'.','')) {

        $arrErr = array("http_code" => "400", "message" => "Não foi possível emitir Nota Fiscal.(NFi02)", 
                                "erro" => "Valor dos itens não fecha com Valor Total da Nota. (".number_format($totalItens,2,'.','')." <> ".number_format($notaFiscal->valorTotal,2,'.','')." )");
        logErro("2", $arrErr, $notaFiscal);
        continue;
    }

    // identifica Municipio para emissão NFSe
    $municipio = new Municipio($db);
    $provedor = $municipio->buscaMunicipioProvedor($emitente->codigoMunicipio);

    $arqPhp = ''; 
    if ($provedor > '')
        $arqPhp = 'retry'.$provedor.'.php'; 

    if (($arqPhp>'') && (file_exists($arqPhp))) {
        include $arqPhp;
    }
    else {
    
        echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe."));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio.$arqPhp."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}

?>