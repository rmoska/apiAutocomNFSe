<?php

// Classe para emissão de NFSe PMF em ambiente de Homologação

include_once '../objects/autorizacao.php';
 
//
// make sure data is not empty
if(
    empty($data->idNotaFiscal) ||
    empty($data->motivo) 
){

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível cancelar Nota Fiscal. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível cancelar Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
    
// set notaFiscal property values
$notaFiscal->idNotaFiscal = $data->idNotaFiscal;

// check NF já gerada para esta Venda
$notaFiscal->readOne();
if (!($notaFiscal->numero > 0)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", 
                            "message" => "Nota Fiscal não encontrada. Não foi possível cancelar. idNF=".$data->idNotaFiscal));
    exit;
}
$notaFiscal->textoJustificativa = $data->motivo;

// check emitente
$emitente = new Emitente($db);
$emitente->idEmitente = $notaFiscal->idEmitente;
$emitente->readOne();
if (!($emitente->documento > '')) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser cancelada."));
    exit;
}

// buscar token conexão
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $notaFiscal->idEmitente;
$autorizacao->readOne();
if(!$autorizacao->getToken($notaFiscal->ambiente)){

    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível cancelar Nota Fiscal. Token não disponível."));
    exit;
}

include_once '../shared/utilities.php';
$utilities = new Utilities();

//			
$xml = new XMLWriter;
$xml->openMemory();
//
// Inicia o cabeçalho do documento XML
$xml->startElement("xmlCancelamentoNfpse");
$xml->writeElement("motivoCancelamento", trim($utilities->limpaEspeciais($notaFiscal->textoJustificativa)));

if ($notaFiscal->ambiente == "P") // PRODUÇÃO
    $nuAEDF = $autorizacao->aedf; 
else // HOMOLOGAÇÃO
    $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter
$xml->writeElement("nuAedf", $nuAEDF);

$xml->writeElement("nuNotaFiscal", $notaFiscal->numero);
$xml->writeElement("codigoVerificacao", $notaFiscal->chaveNF);
$xml->endElement(); // xmlCancelamentoNfpse
//
$xmlNFe = $xml->outputMemory(true);
$xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;
//
$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse-canc.xml","wt");
fwrite($arqNFe, $xmlNFe);
fclose($arqNFe);
//	
include_once '../comunicacao/signNFSe.php';
$arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
$nfse = new SignNFSe($arraySign);

$xmlAss = $nfse->signXML($xmlNFe, 'xmlCancelamentoNfpse');

//
//
// transmite NFSe	
$headers = array( "Content-type: application/xml", "Authorization: Bearer ".$autorizacao->token ); 
$curl = curl_init();
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 

if ($notaFiscal->ambiente == "P") // PRODUÇÃO
    curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/cancelamento/notas/cancela");
else // HOMOLOGAÇÃO
    curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/cancelamento/notas/cancela");

curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
//
$result = curl_exec($curl);
$info = curl_getinfo( $curl );

if ($info['http_code'] == '200') 
{
    //
    $xmlNFRet = simplexml_load_string($result);
    $dtCanc = substr($xmlNFRet->dataCancelamento,0,10).' '.substr($xmlNFRet->dataCancelamento,11,8);
    //
    $dirXmlRet = "arquivosNFSe/".$emitente->documento."/canceladas/";
    $arqXmlRet = $emitente->documento."_".substr(str_pad($notaFiscal->numero,8,'0',STR_PAD_LEFT),0,8)."-nfse-canc.xml";
    $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
    fwrite($arqNFe, $result);
    fclose($arqNFe);
    //
    $notaFiscal->situacao = "X";
    $notaFiscal->dataCancelamento = $dtCanc;
    //
    // update notaFiscal
    $retorno = $notaFiscal->update();
    if(!$retorno[0]){

        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Nota Fiscal.(A01)", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Tomador. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    else {

        // gerar pdf
        include './'.$emitente->codigoMunicipio.'/gerarPdf.php';
        $gerarPdf = new gerarPdf();

        $arqPDF = $gerarPdf->printDanfpse($notaFiscal->idNotaFiscal, $db);

        $dirAPI = basename(dirname(dirname( __FILE__ )));
        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => "201", 
                                "message" => "Nota Fiscal CANCELADA", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "numeroNF" => $notaFiscal->numero,
                                "xml" => "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet,
                                "pdf" => "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF));
        exit;
    }
}
else 
{
    if (substr($info['http_code'],0,1) == '5') {

        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Erro no cancelamento da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no cancelamento da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    else {

        $msg = $result;
        $dados = json_decode($result);
        if (isset($dados->error)) {

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !(1)", "resposta" => "(".$dados->error.") ".$dados->error_description));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(1) (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        else {

            $xmlNFRet = simplexml_load_string(trim($result));
            $msgRet = (string) $xmlNFRet->message;
            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !(2)", "resposta" => $msgRet));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(2) (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
    }
}

?>