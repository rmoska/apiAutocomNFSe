<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoCultural : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->login) ||
    empty($data->senhaWeb) ||
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

    $aAutoChave = array("login" => $data->login, "senhaWeb" => $data->senhaWeb, 
                        "optanteSN" => $data->optanteSN, "incentivoCultural" => $data->incentivoCultural);

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
    // Inicia o cabeçalho do documento XML

    $xml->startElement("Rps");
        $xml->startElement("InfRps");
        $xml->writeAttribute("id", "1");
            $xml->startElement("IdentificacaoRps");
                $xml->writeElement("Numero", 1);
                $xml->writeElement("Serie", 1);
                $xml->writeElement("Tipo", 1);
            $xml->endElement(); // IdentificacaoRps
            $dtEm = date("Y-m-d");
            $xml->writeElement("DataEmissao", $dtEm);
            $xml->writeElement("NaturezaOperacao", 3); // 3 = isento
//            $xml->writeElement("RegimeEspecialTributacao", 6); // 6 = ME/EPP
            $xml->writeElement("OptanteSimplesNacional", 1); // 1 = SIM
            $xml->writeElement("IncentivadorCultural", 2); // 2 = NAO
            $xml->writeElement("Status", 1); // 1 = normal
            $xml->startElement("Servico");
                $xml->startElement("Valores");
                    $xml->writeElement("ValorServicos", 10.00);
                    $xml->writeElement("IssRetido", 2); 
//                    $xml->writeElement("ValorIss", 0.00);
//                    $xml->writeElement("Aliquota", 0.00); 
                    $xml->writeElement("BaseCalculo", 10.00);
                $xml->endElement(); // Valores
                $xml->writeElement("ItemListaServico", "7.10"); //$aAutoChave["codigoServico"]); 
//                $xml->writeElement("CodigoCnae", "6190699");
//                $xml->writeElement("CodigoTributacaoMunicipio", "7.10"); // 4216602 Município de prestação do serviço
                $xml->writeElement("Discriminacao", "Teste homologacao");
                $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
            $xml->endElement(); // Servico
            $xml->startElement("Prestador");
                $xml->writeElement("Cnpj", $emitente->documento);
                $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
            $xml->endElement(); // Prestador

            $xml->startElement("Tomador");
                $xml->startElement("IdentificacaoTomador");
                    $xml->startElement("CpfCnpj");
                        $xml->writeElement("Cpf", "03118290072");
                    $xml->endElement(); // CpfCnpj
                $xml->endElement(); // IdentificacaoTomador
                $xml->writeElement("RazaoSocial", "Tomador Teste");
                $xml->startElement("Endereco");
                    $xml->writeElement("Endereco", "Rua Marechal Guilherme");
                    $xml->writeElement("Numero", "1475");
                    $xml->writeElement("Bairro", "Estreito");
                    $xml->writeElement("CodigoMunicipio", "4205407");
                    $xml->writeElement("Uf", "SC");
                $xml->endElement(); // Endereco
            $xml->endElement(); // Tomador
        $xml->endElement(); // InfRps
    $xml->endElement(); // Rps
    //
    $xmlNFe = $xml->outputMemory(true);

    $xmlAss = $objNFSe->signXML($xmlNFe, 'InfRps', '');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    error_log($xmlLoteAss."\n", 3, "../arquivosNFSe/apiNFe.xml");

    $xmlLote = new XMLWriter;
    $xmlLote->openMemory();

    $xmlLote->startElement("RecepcionarLoteRps");
    $xmlLote->writeAttribute("xmlns", "http://www.e-governeapps2.com.br/");
        $xmlLote->startElement("EnviarLoteRpsEnvio");
        $xmlLote->startElement("LoteRps");
            $xmlLote->writeElement("NumeroLote", 1);
            $xmlLote->writeElement("Cnpj", $emitente->documento);
            $xmlLote->writeElement("InscricaoMunicipal", $autorizacao->cmc);
            $xmlLote->writeElement("QuantidadeRps", 1);
            $xmlLote->startElement("ListaRps");
                $xmlLote->writeRaw($xmlAss);
            $xmlLote->endElement(); // ListaRps
            $xmlLote->endElement(); // LoteRps
        $xmlLote->endElement(); // EnviarLoteRpsEnvio
    $xmlLote->endElement(); // RecepcionarLoteRps
    //
    $xmlNFe = $xmlLote->outputMemory(true);

    $xmlLoteAss = $objNFSe->signXML($xmlNFe, 'LoteRps', 'EnviarLoteRpsEnvio');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    error_log($xmlLoteAss."\n", 3, "../arquivosNFSe/apiNFe.xml");

exit; 

    $retEnv = $objNFSe->transmitirNFSeSimplISS( $emitente->codigoMunicipio, $xmlNFe , 'GerarNfse');

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

    print_r($infoRet);
    print_r($respEnv);

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

                $DomXml=new DOMDocument('1.0', 'utf-8');
                $DomXml->loadXML($respEnv);
                $xmlResp = $DomXml->textContent;
                $msgResp = simplexml_load_string($xmlResp);
                $codigo = (string) $msgResp->ListaMensagemRetorno->MensagemRetorno->Codigo;
                $msg = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Mensagem);
                $correcao = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Correcao);
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
                        "token" => $autorizacao->token, 
                        "validade" => $validade." dias",
                        "nf-homolog" => $nuNF,
                        "verificacao-homolog" => $cdVerif,
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