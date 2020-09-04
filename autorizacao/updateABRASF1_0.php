<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoCultural : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->cmc) ||
    empty($data->crt) ||
    empty($data->optanteSN) ||
    empty($data->incentivoCultural) ||
    empty($data->certificado) ||
    empty($data->senha) ) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
 
$autorizacao = new Autorizacao($db);   
$autorizacao->idEmitente = $data->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio; 
if ($autorizacao->check() == 0) {

    $autorizacao->cmc = $data->cmc;
    $autorizacao->crt = $data->crt;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->create($emitente->documento);
}
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $autorizacao->cmc = $data->cmc;
    $autorizacao->crt = $data->crt;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->update($emitente->documento);
}
if($retorno[0]){

    $aAutoChave = array("optanteSN" => $data->optanteSN, "incentivoCultural" => $data->incentivoCultural);

    $autorizacaoChave = new AutorizacaoChave($db);
    $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;

    foreach($aAutoChave as $chave => $valor) {

        $autorizacaoChave->chave = $chave;
        $autorizacaoChave->valor = $valor;
        $retorno = $autorizacaoChave->update();
    }

    include_once '../comunicacao/comunicaNFSe.php';
    $arraySign = array("sisEmit" => 3, "tpAmb" => "H", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
    $objNFSe = new ComunicaNFSe($arraySign);
    if ($objNFSe->errStatus){
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$objNFSe->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    $validade = $objNFSe->certDaysToExpire;

    //
    // emite nota de teste
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    //
    // cria XML RPS
    $xml->startElement("InfRps");
    $xml->writeAttribute("id", $notaFiscal->idNotaFiscal);
        $xml->startElement("IdentificacaoRps");
            $xml->writeElement("Numero", $notaFiscal->idNotaFiscal); // ????????????
            $xml->writeElement("Serie", 1);
            $xml->writeElement("Tipo", 1);
        $xml->endElement(); // IdentificacaoRps
        $xml->writeElement("DataEmissao", $dtEm);
        $xml->writeElement("NaturezaOperacao", $notaFiscalItem->cstIss);
        $xml->writeElement("RegimeEspecialTributacao", 6); // 6 = ME/EPP
        $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1 = SIM
        $xml->writeElement("IncentivadorCultural", $idIncCultural); // 2 = NAO
        $xml->writeElement("Status", 1); // 1 = normal

        $xml->startElement("Servico");
            $xml->startElement("Valores");
                $xml->writeElement("ValorServicos", $vlTotServ);
                $xml->writeElement("IssRetido", 2); // 1=Sim 2=Não
                $xml->writeElement("ValorIss", $notaFiscalItem->valorIss);
                $xml->writeElement("BaseCalculo", $vlTotBC);
                $xml->writeElement("Aliquota", $notaFiscalItem->taxaIss); 
                $xml->writeElement("ValorLiquidoNfse", $vlTotServ);
            $xml->endElement(); // Valores

            $xml->writeElement("ItemListaServico", $notaFiscalItem->codigoServico); 
            $xml->writeElement("CodigoCnae", "");
            $xml->writeElement("Discriminacao", $descServico);
            $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
        $xml->endElement(); // Serviço

        $xml->startElement("Prestador");
            $xml->writeElement("Cnpj", $emitente->documento);
            $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
        $xml->endElement(); // Prestador


        $xml->startElement("Tomador");
            $xml->startElement("IdentificacaoTomador");
                $xml->startElement("CpfCnpj");
                if (strlen($tomador->documento)==14) 
                    $xml->writeElement("Cnpj", $tomador->documento);
                else
                    $xml->writeElement("Cpf", $tomador->documento);
                $xml->endElement(); // CpfCnpj
            $xml->endElement(); // IdentificacaoTomador
            $xml->writeElement("RazaoSocial", $tomador->nome);
            $xml->startElement("Endereco");
                $xml->writeElement("Endereco", $tomador->logradouro);
                $xml->writeElement("Numero", $tomador->numero);
                $xml->writeElement("Complemento", $tomador->complemento);
                $xml->writeElement("Bairro", $tomador->bairro);
                $xml->writeElement("CodigoMunicipio", $tomador->codigoMunicipio);
                $xml->writeElement("Uf", $tomador->uf);
                $xml->writeElement("Cep", $tomador->cep);
            $xml->endElement(); // Endereco
        $xml->endElement(); // Tomador
    $xml->endElement(); // InfRps

    $xmlRps = $xml->outputMemory(true);

    $xmlAss = $objNFSe->signXML($xmlRps, 'InfRps', '');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("EnviarLoteRpsEnvio");
    $xml->writeAttribute("xmlns", "http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd");
        $xml->startElement("LoteRps");
        $xml->writeAttribute("id", "001");
            $xml->writeElement("NumeroLote", 1);
            $xml->writeElement("Cnpj", $emitente->documento);
            $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
            $xml->writeElement("QuantidadeRps", 1);
            $xml->startElement("ListaRps");
                $xml->writeRaw($xmlAss);
            $xml->endElement(); // ListaRps
        $xml->endElement(); // LoteRps
    $xml->endElement(); // EnviarLoteRpsEnvio
    //
    $xmlLote = $xml->outputMemory(true);
    //
    $xmlAss = $objNFSe->signXML($xmlLote, 'LoteRps', '');

//    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$xmlNFe."\n"), 3, "../arquivosNFSe/nfBCret.log");

    $retEnv = $objNFSe->transmitirNFSeABRASF1_0( $xmlNFe , 'EnviarLoteRpsEnvio', $emitente->codigoMunicipio);

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$respEnv."\n"), 3, "../arquivosNFSe/nfBCret.log");
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".json_encode($infoRet)."\n"), 3, "../arquivosNFSe/nfBCret.log");

    $nuNF = 0;
    $cdVerif = '';

    if ($infoRet['http_code'] == '200') {

        // se retorna ListaNfse - processou com sucesso
        if(strstr($respEnv,'NovaNfse')){
/*
            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($respEnv);
            $xmlResp = $DomXml->textContent;
            $msgResp = simplexml_load_string($xmlResp);
            $nuNF = (string) $msgResp->NovaNfse->IdentificacaoNfse->Numero;
            $cdVerif = (string) $msgResp->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
            $linkNF = (string) $msgResp->NovaNfse->IdentificacaoNfse->Link;
*/
            $respEnv = str_replace("<s:", "<", $respEnv);
            $respEnv = str_replace("</s:", "</", $respEnv);
            $msgResp = simplexml_load_string($respEnv);

            $nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
            $cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
            $linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;
           

            $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
            $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
            $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
            fwrite($arqNFe, $xmlResp);
            fclose($arqNFe);
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
                $cdVerif = "Erro no envio da NFSe ! Problemas de comunicação ! ".$cdVerif;
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe de Homologação ! Problemas de comunicação !\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            //erros de validacao do webservice
            else if(strstr($respEnv,'ListaMensagemRetorno')){

                $respEnv = str_replace("<s:", "<", $respEnv);
                $respEnv = str_replace("</s:", "</", $respEnv);
                $msgResp = simplexml_load_string($respEnv);
    
                $codigo = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Codigo;
                $msg = (string) utf8_decode($msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Mensagem);
                $correcao = (string) utf8_decode($msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Correcao);
                $cdVerif = $codigo.' - '.$msg.' - '.$correcao;
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro Autorização => ".$cdVerif."\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            // erro inesperado
            else {

                $cdVerif .= "Erro no envio da NFSe ! Erro Desconhecido !";
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe Homologação !(2) (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
        }
    }

    if ($nuNF > 0) {

        $autorizacao->nfhomologada = $nuNF;
        $autorizacao->update($emitente->documento);
    }

    http_response_code(201);
    echo json_encode(array("http_code" => 201, "message" => "Autorização atualizada", 
                           "validade" => $validade." dias",
                           "nf-homolog" => $nuNF,
                           "verificacao-homolog" => utf8_decode($cdVerif),
                           "linkNF" => $linkNF));
    exit;
}
else{

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1]));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>