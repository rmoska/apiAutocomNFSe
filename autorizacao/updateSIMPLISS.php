<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoFiscal : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->login) ||
    empty($data->senhaWeb) ||
    empty($data->crt) ||
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
$autorizacao->crt = $data->crt;
$autorizacao->certificado = $data->certificado;
$autorizacao->senha = $data->senha;

if ($autorizacao->check() == 0)
    $retorno = $autorizacao->create($emitente->documento);
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $retorno = $autorizacao->update($emitente->documento);
}

if($retorno[0]){

    $aAutoChave = array("login" => $data->login, "senhaWeb" => $data->senhaWeb, 
                        "optanteSN" => $data->optanteSN, "incentivoCultural" => $data->incentivoFiscal);

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
    $xml->startElement("sis:GerarNfse");
        $xml->startElement("sis:GerarNovaNfseEnvio");
            $xml->startElement("nfse:Prestador");
                $xml->writeElement("nfse:Cnpj", $emitente->documento);
            $xml->endElement(); // Prestador
            $xml->startElement("nfse:InformacaoNfse");
            $xml->writeAttribute("Id", "lote1");
                $xml->writeElement("nfse:NaturezaOperacao", 3); // 3 = isento
                $xml->writeElement("nfse:RegimeEspecialTributacao", 6); // 6 = ME/EPP
                $xml->writeElement("nfse:OptanteSimplesNacional", 1); // 1 = SIM
                $xml->writeElement("nfse:IncentivadorCultural", 2); // 2 = NAO
                $xml->writeElement("nfse:Status", 1); // 1 = normal
                $dtEm = date("Y-m-d");
                $xml->writeElement("nfse:Competencia", $dtEm);

                $xml->startElement("nfse:Servico");
                    $xml->startElement("nfse:Valores");
                        $xml->writeElement("nfse:ValorServicos", 10.00);
                        $xml->writeElement("nfse:IssRetido", 2); 
                        $xml->writeElement("nfse:ValorIss", 0.00);
                        $xml->writeElement("nfse:Aliquota", 0.00); 
                    $xml->endElement(); // Valores

                    $xml->writeElement("nfse:ItemListaServico", $aAutoChave["codigoServico"]); //"0402");
//                    $xml->writeElement("CodigoTributacaoMunicipio", 0); // 4216602 Município de prestação do serviço
                    $xml->writeElement("nfse:Discriminacao", "Consulta clinica");
                    $xml->writeElement("nfse:CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço

                    $xml->startElement("nfse:ItensServico");
                        $xml->writeElement("nfse:Descricao", "Consulta clinica");
                        $xml->writeElement("nfse:Quantidade", 1.00);
                        $xml->writeElement("nfse:ValorUnitario", 10.00);
                    $xml->endElement(); // ItensServico
                $xml->endElement(); // Servico

                $xml->startElement("nfse:Tomador");
                    $xml->startElement("nfse:IdentificacaoTomador");
                        $xml->startElement("nfse:CpfCnpj");
                            $xml->writeElement("nfse:Cpf", "03118290072");
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // IdentificacaoTomador
                    $xml->writeElement("nfse:RazaoSocial", "Tomador Teste");
                    $xml->startElement("nfse:Endereco");
                        $xml->writeElement("nfse:Endereco", "Rua Marechal Guilherme");
                        $xml->writeElement("nfse:Numero", "1475");
                        $xml->writeElement("nfse:Complemento", "sala 804");
                        $xml->writeElement("nfse:Bairro", "Estreito");
                        $xml->writeElement("nfse:CodigoMunicipio", "4205407");
                        $xml->writeElement("nfse:Uf", "SC");
                        $xml->writeElement("nfse:Cep", "88070700");
                    $xml->endElement(); // Endereco
                    $xml->startElement("nfse:Contato");
                        $xml->writeElement("nfse:Telefone", "4833330891");
                        $xml->writeElement("nfse:Email", "rodrigo@autocominformatica.com.br");
                    $xml->endElement(); // Contato
                $xml->endElement(); // Tomador
            $xml->endElement(); // InformacaoNfse
        $xml->endElement(); // GerarNovaNfseEnvio
        
        $xml->startElement("sis:pParam");
            $xml->writeElement("sis1:P1", $aAutoChave["login"]); 
            $xml->writeElement("sis1:P2", $aAutoChave["senhaWeb"]); 
        $xml->endElement(); // pParam
    $xml->endElement(); // GerarNfseEnvio
    //
    $xmlNFe = $xml->outputMemory(true);

/*    
    $xmlAss = $objNFSe->signXML($xmlNFe, 'InfDeclaracaoPrestacaoServico', 'Rps');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
*/


    $retEnv = $objNFSe->transmitirNFSeSimplISS( $emitente->codigoMunicipio, $xmlNFe );

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

    print_r($infoRet);
exit;

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