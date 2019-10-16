<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoFiscal : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->crt) ||
    empty($data->certificado) ||
    empty($data->senha) ||
    empty($data->optanteSN) ||
    empty($data->incentivoFiscal) ||
    empty($data->codigoServico) ) {

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
$autorizacao->crt = $data->crt;
$autorizacao->cmc = $data->cmc;
$autorizacao->certificado = $data->certificado;
$autorizacao->senha = $data->senha;

if ($autorizacao->check() == 0)
    $retorno = $autorizacao->create($emitente->documento);
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $retorno = $autorizacao->update($emitente->documento);
}

if($retorno[0]){

    $aAutoChave = array("optanteSN" => $data->optanteSN, "incentivoFiscal" => $data->incentivoFiscal, "codigoServico" => $data->codigoServico);

    $autorizacaoChave = new AutorizacaoChave($db);
    $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;

    foreach($aAutoChave as $chave => $valor) {

        $autorizacaoChave->chave = $chave;
        $autorizacaoChave->valor = $valor;
        $retorno = $autorizacaoChave->update();
    }

    include_once '../comunicacao/comunicaNFSe.php';
    $arraySign = array("sisEmit" => 1, "tpAmb" => "H", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
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
    $xml->startElement("GerarNfseEnvio");
    $xml->writeAttribute("xmlns", "http://www.betha.com.br/e-nota-contribuinte-ws");
        $xml->startElement("Rps");
            $xml->startElement("InfDeclaracaoPrestacaoServico");
            $xml->writeAttribute("Id", "lote1");
                $dtEm = date("Y-m-d");
                $xml->writeElement("Competencia", $dtEm);
                $xml->startElement("Servico");
                    $xml->startElement("Valores");
                        $xml->writeElement("ValorServicos", 10.00);
                        $xml->writeElement("ValorIss", 0.00);
                        $xml->writeElement("Aliquota", 0.00); 
                    $xml->endElement(); // Valores
                    $xml->writeElement("IssRetido", 2);
                    $xml->writeElement("ItemListaServico", $aAutoChave["codigoServico"]); //"0402");
                    $xml->writeElement("Discriminacao", "Consulta clinica");
                    $xml->writeElement("CodigoMunicipio", 0); // 4216602 Município de prestação do serviço
                    $xml->writeElement("ExigibilidadeISS", 3); // 3 = isento
//                        $xml->writeElement("MunicipioIncidencia", 0); // 4216602
                $xml->endElement(); // Servico
                $xml->startElement("Prestador");
                    $xml->startElement("CpfCnpj");
                        $xml->writeElement("Cnpj", $emitente->documento);
                    $xml->endElement(); // CpfCnpj
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
                        $xml->writeElement("Complemento", "sala 804");
                        $xml->writeElement("Bairro", "Estreito");
                        $xml->writeElement("CodigoMunicipio", "4205407");
                        $xml->writeElement("Uf", "SC");
                        $xml->writeElement("Cep", "88070700");
                    $xml->endElement(); // Endereco
                    $xml->startElement("Contato");
                        $xml->writeElement("Telefone", "4833330891");
                        $xml->writeElement("Email", "rodrigo@autocominformatica.com.br");
                    $xml->endElement(); // Contato
                $xml->endElement(); // Tomador
                $xml->writeElement("RegimeEspecialTributacao", $autorizacao->crt);
                $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1-Sim/2-Não
                $xml->writeElement("IncentivoFiscal", $aAutoChave["incentivoFiscal"]); // 1-Sim/2-Não
            $xml->endElement(); // InfDeclaracaoPrestacaoServico
        $xml->endElement(); // Rps
    $xml->endElement(); // GerarNfseEnvio
    //
    $xmlNFe = $xml->outputMemory(true);

    $xmlAss = $objNFSe->signXML($xmlNFe, 'InfDeclaracaoPrestacaoServico', 'Rps');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
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

    $retEnv = $objNFSe->transmitirNFSeBetha('GerarNfse', $xmlEnv, "H");

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

print_r($retEnv[1]);

    $nuNF = 0;
    $cdVerif = '';

    if ($infoRet['http_code'] == '200') {

        // se retorna ListaNfse - processou com sucesso
        if(strstr($respEnv,'ListaNfse')){

            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($respEnv);
            $xmlResp = $DomXml->textContent;
            $msgResp = simplexml_load_string($xmlResp);
            $nuNF = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->Numero;
            $cdVerif = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->CodigoVerificacao;
            $linkNF = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->OutrasInformacoes;
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