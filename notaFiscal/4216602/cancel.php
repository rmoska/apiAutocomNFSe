<?php

// Classe para emissão de NFSe PMF em ambiente de Homologação

include_once '../objects/autorizacao.php';
 
//
// make sure data is not empty
if(
    empty($data->idNotaFiscal)
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
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
$autorizacao->readOne();

//			
$xml = new XMLWriter;
$xml->openMemory();

// Inicia o cabeçalho do documento XML
$xml->startElement("CancelarNfseEnvio");
$xml->writeAttribute("xmlns", "http://www.betha.com.br/e-nota-contribuinte-ws");
    $xml->startElement("Pedido");
        $xml->startElement("InfPedidoCancelamento");
        $xml->writeAttribute("Id", "1");
            $xml->startElement("IdentificacaoNfse");
                $xml->writeElement("Numero", 619); //$notaFiscal->numero);
                $xml->startElement("CpfCnpj");
                    $xml->writeElement("Cnpj", $emitente->documento);
                $xml->endElement(); // CpfCnpj
                $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio);
            $xml->endElement(); // IdentificacaoNfse
            $xml->writeElement("CodigoCancelamento", 1); // 1=Erro | 2=Serviço nao prestado | 4=Duplicidade
        $xml->endElement(); // InfPedidoCancelamento
    $xml->endElement(); // Pedido
$xml->endElement(); // CancelarNfseEnvio

//
$xmlNFe = $xml->outputMemory(true);
$xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;
//
$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse-canc.xml","wt");
fwrite($arqNFe, $xmlNFe);
fclose($arqNFe);
//	
//	
// cria objeto certificado
include_once '../comunicacao/comunicaNFSe.php';
$arraySign = array("sisEmit" => 1, "tpAmb" => $notafiscal->ambiente, "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
$objNFSe = new ComunicaNFSe($arraySign);
if ($objNFSe->errStatus){
    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível acessar Certificado.", "erro" => $objNFSe->errMsg));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível acessar Certificado. Erro=".$objNFSe->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

// assina documento
$xmlAss = $objNFSe->signXML($xmlNFe, 'InfPedidoCancelamento', 'Pedido');
if ($objNFSe->errStatus) {

    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$objNFSe->errMsg));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

//
// monta bloco padrão Betha
$xmlEnv = '<nfseCabecMsg>';
$xmlEnv .= '<![CDATA[';
$xmlEnv .= '<cabecalho xmlns="http://www.betha.com.br/e-nota-contribuinte-ws" versao="2.02"><versaoDados>2.02</versaoDados></cabecalho>';
$xmlEnv .= ']]>';
$xmlEnv .= '</nfseCabecMsg>';
$xmlEnv .= '<nfseDadosMsg>';
$xmlEnv .= '<![CDATA[';
$xmlEnv .= $xmlAss;
$xmlEnv .= ']]>';
$xmlEnv .= '</nfseDadosMsg>';

$respEnv = $objNFSe->transmitirNFSe('CancelarNfse', $xmlEnv, $notaFiscal->ambiente);

print_r($respEnv);

exit;














exit;
//
//

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