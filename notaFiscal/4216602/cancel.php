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
                $xml->writeElement("Numero", $notaFiscal->numero);
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

// se retorna ListaNfse - processou com sucesso
if(strstr($respEnv,'RetCancelamento')){

    $DomXml=new DOMDocument('1.0', 'utf-8');
    $DomXml->loadXML($respEnv);
    $xmlResp = $DomXml->textContent;
    $msgResp = simplexml_load_string($xmlResp);

    $dtCanc = (string) $msgResp->RetCancelamento->NfseCancelamento->Confirmacao->DataHora;
    $dtCanc = str_replace(" " , "", $dtCanc);
    $dtCanc = str_replace("T" , " ", $dtCanc);
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

        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => "201", 
                                "message" => "Nota Fiscal CANCELADA", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "numeroNF" => $notaFiscal->numero,
                                "xml" => $notaFiscal->linkXml,
                                "pdf" => $notaFiscal->linkNF));
        exit;
    }
}
else {

    //erro na comunicacao SOAP
    if(strstr($respEnv,'Fault')){

        $DomXml=new DOMDocument('1.0', 'utf-8');
        $DomXml->loadXML($respEnv);
        $xmlResp = $DomXml->textContent;
        $msgResp = simplexml_load_string($xmlResp);
        $codigo = (string) $msgResp->ListaMensagemRetorno->MensagemRetorno->Codigo;
        $msg = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Mensagem);
        $falha = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Fault);
        $cdVerif = $codigo.' - '.$msg.' - '.$falha;
        $msgRet = "Erro no envio da NFSe ! Problemas de comunicação ! ".$cdVerif;
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na transmissão da NFSe ! Problemas de comunicação !\n"), 3, "../arquivosNFSe/apiErrors.log");
    }
    //erros de validacao do webservice
    else if(strstr($respEnv,'ListaMensagemRetorno')){

        $DomXml=new DOMDocument('1.0', 'utf-8');
        $DomXml->loadXML($respEnv);
        $xmlResp = $DomXml->textContent;
        $msgResp = simplexml_load_string($xmlResp);
        $codigo = (string) $msgResp->ListaMensagemRetorno->MensagemRetorno->Codigo;
        $msg = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Mensagem);
        $correcao = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Correcao);
        $msgRet = $codigo.' - '.$msg.' - '.$correcao;
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no cancelamento da NFSe => ".$msgRet."\n"), 3, "../arquivosNFSe/apiErrors.log");
    }
    // erro inesperado
    else {

        $msgRet = $respEnv;
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Erro Desconhecido (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
    }

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !", "resposta" => $msgRet));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;

}

?>