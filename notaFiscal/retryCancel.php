<?php

// Classe para repetir tentativa de emissão de NFSe PMF pendentes por Servidor Indisponível / Timeout

//
// statusErr 
// 0 = situação mantida (timeout) 
// 1 = erro autorização ou processamento, situação nf alterada 
// 3 = emitida com sucesso
function logErro($db, $statusErr, $arrMsg, $objNF){

    include_once '../shared/logMsg.php';
    $logMsg = new LogMsg($db);
    
    $strData = json_encode($arrMsg);

    if ($statusErr == 0) {

        $logMsg->register('A', 'notaFiscal.retryCancel', $arrMsg['message'], $strData);
    }
    else if ($statusErr == 1) {

        //$objNF->deleteCompletoTransaction();
        $objNF->situacao = 'E';
        $objNF->textoResposta = $arrMsg['message'];
        $objNF->textoJustificativa = $arrMsg['error'];
        $objNF->update();

        $logMsg->register('E', 'notaFiscal.retryCancel', $arrMsg['message'], $arrMsg['error']);
    }
    else if ($statusErr == 3) {

        $logMsg->register('S', 'notaFiscal.retryCancel', $arrMsg['message'], $strData);
    }   
}

include_once '../config/database.php';
include_once '../objects/notaFiscalServico.php';
 
$database = new Database();
$db = $database->getConnection();

$dirAPI = basename(dirname(dirname( __FILE__ )));

$notaFiscal = new NotaFiscalServico($db);
 
$stmt = $notaFiscal->readPendente('C'); // pendentes por Timeout

//
// se não encontrou registros, encerra processamento
if($stmt->rowCount() == 0)
    exit;
 
include_once '../objects/emitente.php';
include_once '../objects/municipio.php';
include_once '../shared/logMsg.php';
include_once '../shared/utilities.php';
$logMsg = new LogMsg($db);
$utilities = new Utilities();

while ($rNF = $stmt->fetch(PDO::FETCH_ASSOC)){

    $notaFiscal = new NotaFiscalServico($db);
    $notaFiscal->idNotaFiscal = $rNF["idNotaFiscal"];
    $notaFiscal->readOne();

    $tomador = new Tomador($db);
    $tomador->idTomador = $notaFiscal->idTomador;
    $tomador->readOne();

    $emitente = new Emitente($db);
    $emitente->idEmitente = $notaFiscal->idEmitente;
    $emitente->readOne();

    // identifica Municipio para emissão NFSe
    $municipio = new Municipio($db);
    $provedor = $municipio->buscaMunicipioProvedor($emitente->codigoMunicipio);

    $jsonObj = '{"idNotaFiscal": '.$notaFiscal->idNotaFiscal.',
                 "idEmitente": '.$notaFiscal->idEmitente.',
                 "motivo": "Venda Cancelada"}';
    $data = json_decode($jsonObj);

    $arqPhp = ''; 
    if ($provedor > '')
        $arqPhp = 'cancel'.$provedor.'.php'; 

    if (($arqPhp>'') && (file_exists($arqPhp))) {
        include $arqPhp;
    }
    else {
    
        $arrErr = array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe idNF=".$notaFiscal->idNotaFiscal, 
                        "error" => "Município emitente = ".$emitente->codigoMunicipio,
                        "codigo" => "A02" );
        logErro($db, "1", $arrErr, $notaFiscal);
        continue;
    }
}

?>