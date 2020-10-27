<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoFiscal : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->cmc) ||
    empty($data->optanteSN) ||
    empty($data->incentivoFiscal) ||
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
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->create($emitente->documento);
}
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $autorizacao->cmc = $data->cmc;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->update($emitente->documento);
}
if($retorno[0]){

    $aAutoChave = array("optanteSN" => $data->optanteSN, "incentivoFiscal" => $data->incentivoFiscal);

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
    // cria XML RPS
    $xml->startElement("GerarNfseEnvio");
    $xml->writeAttribute("xmlns", "http://www.abrasf.org.br/nfse.xsd");
        $xml->startElement("Rps");
            $xml->startElement("InfDeclaracaoPrestacaoServico");
            $xml->writeAttribute("Id", "RPS1");
                $xml->startElement("Rps");
                    $xml->startElement("IdentificacaoRps");
                        $xml->writeElement("Numero", 9999999999); // ????????????
                        $xml->writeElement("Serie", "UNICA");
                        $xml->writeElement("Tipo", 1);
                    $xml->endElement(); // IdentificacaoRps
                    $dtEm = date("Y-m-d");
                    $xml->writeElement("DataEmissao", $dtEm);
                    $xml->writeElement("Status", 1); // 1 = normal
                $xml->endElement(); // Rps
                $xml->writeElement("Competencia", $dtEm);

                $xml->writeElement("OptanteSimplesNacional", 2); // 1 = SIM
                $xml->writeElement("IncentivoFiscal", 2); // 2 = NAO

                $xml->startElement("Servico");
                    $xml->startElement("Valores");
                        $xml->writeElement("ValorServicos", 10);
                        $xml->writeElement("ValorIss", 0.20);
                        $xml->writeElement("Aliquota", 2.00); 
                    $xml->endElement(); // Valores
                    $xml->writeElement("IssRetido", 2); // 1=Sim 2=Não

                    $xml->writeElement("ItemListaServico", "4.01"); 
    //                $xml->writeElement("CodigoCnae", "8630502");
                    $xml->writeElement("Discriminacao", "Consulta Medica");
                    $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
                    $xml->writeElement("ExigibilidadeIss", 1); // 1=Exigivel 2=Não Incidencia 3=Isenção
    //                $xml->writeElement("MunicipioIncidencia", $emitente->codigoMunicipio); // Município de incidência do ISS
                $xml->endElement(); // Serviço

                $xml->startElement("Prestador");
                    $xml->writeElement("Cnpj", $emitente->documento); //"80449374000128"); //
                    $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
                $xml->endElement(); // Prestador

                $xml->startElement("TomadorServico");
                    $xml->startElement("IdentificacaoTomador");
                        $xml->startElement("CpfCnpj");
                            $xml->writeElement("Cpf", "03118290072");
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // IdentificacaoTomador
                    $xml->writeElement("RazaoSocial", "Tomador Teste API");
                    $xml->startElement("Endereco");
                        $xml->writeElement("Endereco", "Rua Marechal Guilherme");
                        $xml->writeElement("Numero", "1");
                        $xml->writeElement("Bairro", "Centro");
                        $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio);
                        $xml->writeElement("Uf", "SC");
                        $xml->writeElement("Cep", "88015000");
                    $xml->endElement(); // Endereco
                $xml->endElement(); // Tomador
            $xml->endElement(); // InfRps
        $xml->endElement(); // Rps
    $xml->endElement(); // GerarNfseEnvio

    $xmlRps = $xml->outputMemory(true);

    $xmlAss = $objNFSe->signXML($xmlRps, 'InfDeclaracaoPrestacaoServico', '');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
    fwrite($arqNFe, $xmlAss);
    fclose($arqNFe);
    
    $retEnv = $objNFSe->transmitirNFSeSINTESE( $xmlAss, 'GerarNfseEnvio', $emitente->codigoMunicipio);
    $respEnv = htmlspecialchars_decode($retEnv[0]);
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

            //erro no processamento
            if(strstr($respEnv,'Fault')){

                $respEnv = str_replace("<s:", "<", $respEnv);
                $respEnv = str_replace("</s:", "</", $respEnv);
                $msgResp = simplexml_load_string($respEnv);
                $msgRet = $msgResp->Body->Fault;
                $codigo = (string) $msgRet->faultcode;
                $msg = (string) $msgRet->faultstring;
                $cdVerif = $codigo.' - '.$msg;
                $cdVerif = utf8_decode("Erro NFSe Homologacao ! Falha de processamento ! ".$cdVerif);
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$cdVerif."\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            //erros de validacao do webservice
            else if(strstr($respEnv,'ListaMensagemRetorno')){

                $respEnv = str_replace("<s:", "<", $respEnv);
                $respEnv = str_replace("</s:", "</", $respEnv);
                $msgResp = simplexml_load_string($respEnv);
                $msgRet = $msgResp->Body->EnviarLoteRPSResponse->EnviarLoteRPSResult->EnviarLoteRpsResposta->ListaMensagemRetorno->MensagemRetorno;
                $codigo = (string) $msgRet->Codigo;
                $msg = (string) $msgRet->Mensagem;
                $correcao = (string) utf8_decode($msgRet->Correcao);
                $cdVerif = utf8_decode("Erro NFSe Homologação ! ".$msg.' - '.$correcao.' ('.$codigo.')');
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$cdVerif."\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            // erro inesperado
            else {

                $cdVerif .= "Erro no envio da NFSe ! Erro Desconhecido !";
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe Homologação !(2) (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
        }
    }
    else{

        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização. Erro NF.", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Erro NF. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização. Erro NF.', $strData);
        exit;
    }

    if ($nuNF > 0) {

        $autorizacao->nfhomologada = $nuNF;
        $autorizacao->update($emitente->documento);
    }

    http_response_code(201);
    $aRet = array("http_code" => 201, "message" => "Autorização atualizada", 
                    "validade" => $validade." dias",
                    "nf-homolog" => $nuNF,
                    "verificacao-homolog" => utf8_encode($cdVerif),
                    "linkNF" => $linkNF);
    echo json_encode($aRet);
    $logMsg->register('S', 'autorizacao.update', 'Autorização atualizada.', json_encode($aRet));
    exit;
}
else{

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização. Erro BD.", "erro" => $retorno[1]));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Erro BD. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>