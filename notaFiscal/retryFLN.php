<?php

    if ($tomador->uf != 'SC') $cfps = '9203';
    else if ($tomador->codigoMunicipio != '4205407') $cfps = '9202';
    else $cfps = '9201';
    $notaFiscal->cfop = $cfps;          
    
    //
    // buscar token conexão
    $autorizacao = new Autorizacao($db);
    $autorizacao->idEmitente = $notaFiscal->idEmitente;
    $autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
    $autorizacao->readOne();

    if(($notaFiscal->ambiente=="P") && (is_null($autorizacao->aedf) || ($autorizacao->aedf==''))) {

        $arrErr = array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. AEDFe não informado. idNF=".$notaFiscal->idNotaFiscal, "codigo" => "A02");
        logErro($db, "1", $arrErr, $notaFiscal);
        return;
    }
    else if(!$autorizacao->getToken($notaFiscal->ambiente)){

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Token de acesso rejeitado (Confira CMC e senha PMF). idNF=".$notaFiscal->idNotaFiscal, "codigo" => "A02");
        logErro($db, "1", $arrErr, $notaFiscal);
        return;
    }

    //			
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("xmlProcessamentoNfpse");
    if ($notaFiscal->ambiente == "P") // PRODUÇÃO
        $nuAEDF = $autorizacao->aedf; 
    else // HOMOLOGAÇÃO
        $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter
    $xml->writeElement("numeroAEDF", $nuAEDF);
    $xml->writeElement("identificacao", $notaFiscal->idNotaFiscal);
    $xml->writeElement("numeroSerie", 1);
    $xml->writeElement("dataEmissao", $notaFiscal->dataEmissao);
    $xml->writeElement("cfps", $notaFiscal->cfop);
    $xml->writeElement("baseCalculo", number_format($vlTotBC,2,'.',''));
    if ($vlTotBCST>0)
        $xml->writeElement("baseCalculoSubstituicao", number_format($vlTotBCST,2,'.',''));
    $xml->writeElement("valorISSQN", number_format($vlTotISS,2,'.',''));
    $xml->writeElement("valorTotalServicos", number_format($vlTotServ,2,'.',''));

    $xml->writeElement("identificacaoTomador", $tomador->documento);
    $xml->writeElement("razaoSocialTomador", $tomador->nome);
    $xml->writeElement("logradouroTomador", trim($utilities->limpaEspeciais($tomador->logradouro)));
    if ($tomador->numero>0)
        $xml->writeElement("numeroEnderecoTomador", $tomador->numero);
    if($tomador->complemento > '')
        $xml->writeElement("complementoEnderecoTomador", $tomador->complemento);
    $xml->writeElement("bairroTomador", $tomador->bairro);
    $xml->writeElement("codigoMunicipioTomador", $tomador->codigoMunicipio);
    $xml->writeElement("codigoPostalTomador", $tomador->cep);
    if ($tomador->uf >'')
        $xml->writeElement("ufTomador", $tomador->uf);
    $xml->writeElement("emailTomador", $tomador->email);
    //		
    // ITENS
    $xml->startElement("itensServico");

    foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {

        $xml->startElement("itemServico");
        $notaFiscalItem->readItemVenda('CNAE-FLN');
        $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
        if ($notaFiscalItem->observacao > '')
            $nmProd .= ' - '.$notaFiscalItem->observacao;
        $xml->writeElement("descricaoServico", trim($nmProd));
        $xml->writeElement("idCNAE", trim($notaFiscalItem->codigoServico));
        $xml->writeElement("cst", $notaFiscalItem->cstIss);
        $xml->writeElement("aliquota", number_format(($notaFiscalItem->taxaIss/100),4,'.',''));
        $xml->writeElement("quantidade", number_format($notaFiscalItem->quantidade,0,'.',''));
        $xml->writeElement("baseCalculo", number_format($notaFiscalItem->valorBCIss,4,'.',''));
        $xml->writeElement("valorTotal", number_format($notaFiscalItem->valorTotal,4,'.',''));
        $xml->writeElement("valorUnitario", number_format($notaFiscalItem->valorUnitario,4,'.',''));
        $xml->endElement(); // ItemServico
    }
    $xml->endElement(); // ItensServico
    if (($notaFiscal->obsImpostos > '') || ($notaFiscal->dadosAdicionais>''))
        $xml->writeElement("dadosAdicionais", $notaFiscal->obsImpostos." ".$notaFiscal->dadosAdicionais);
    //
    $xml->endElement(); // xmlNfpse
    //
    $xmlNFe = $xml->outputMemory(true);
    $xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;
    //
    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
    fwrite($arqNFe, $xmlNFe);
    fclose($arqNFe);
    //	
    include_once '../comunicacao/signNFSe.php';
    $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);

    $nfse = new SignNFSe($arraySign);
    if($nfse->errStatus) {

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. idNF=".$notaFiscal->idNotaFiscal, "error" => $nfse->errMsg, "codigo" => "A02");
        logErro($db, "1", $arrErr, $notaFiscal);
        return;
    }

    $xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($nfse->errStatus) {

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. idNF=".$notaFiscal->idNotaFiscal, "error" => $nfse->errMsg, "codigo" => "A02");
        logErro($db, "1", $arrErr, $notaFiscal);
        return;
    }

    //
    // transmite NFSe	
    $headers = array( "Content-type: application/xml", "Authorization: Bearer ".$autorizacao->token ); 
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 

    if ($notaFiscal->ambiente == "P") // PRODUÇÃO
        curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/processamento/notas/processa");
    else // HOMOLOGAÇÃO
        curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/processamento/notas/processa");

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
    //
    $result = curl_exec($curl);
    $info = curl_getinfo( $curl );

    if ($info['http_code'] == '200') {

        //
        $xmlNFRet = simplexml_load_string($result);
        $nuNF = $xmlNFRet->numeroSerie;
        $cdVerif = $xmlNFRet->codigoVerificacao;
        $dtProc = substr($xmlNFRet->dataProcessamento,0,10).' '.substr($xmlNFRet->dataProcessamento,11,8);
        //
        $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
        $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
        $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
        fwrite($arqNFe, $result);
        fclose($arqNFe);
        $linkXml = "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet;
        //
        $notaFiscal->numero = $nuNF;
        $notaFiscal->chaveNF = $cdVerif;
        $notaFiscal->linkXml = $linkXml;
        $notaFiscal->situacao = "F";
        $notaFiscal->dataProcessamento = $dtProc;
        $notaFiscal->textoJustificativa = 'Reprocessada por Timeout';
        //
        $retorno = $notaFiscal->update();
        //
        // gerar pdf
        include_once './gerarPdfFLN.php';
        $gerarPdf = new gerarPdf();
        $arqPDF = $gerarPdf->printDanfpse($notaFiscal->idNotaFiscal, $db);
        $linkNF = "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF;
        $notaFiscal->linkNF = $linkNF;
        $notaFiscal->update();

        $arrOK = array("http_code" => "201", 
                        "message" => "Nota Fiscal emitida", 
                        "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                        "numeroNF" => $notaFiscal->numero,
                        "xml" => "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet,
                        "pdf" => "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF);
        $retNFSe = json_encode($arrOK);

        $headers = array( "Content-type: application/json" ); 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
    
        if ($notaFiscal->ambiente == "P") // PRODUÇÃO
            curl_setopt($curl, CURLOPT_URL, "https://ws.fpay.me/crm/me/nfe/callback-status-nfe");
        else // HOMOLOGAÇÃO
            curl_setopt($curl, CURLOPT_URL, "http://fastpay-api-intranet-teste.fastconnect.com.br/crm/me/nfe/callback-status-nfe");
    
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $retNFSe);
        //
        $result = curl_exec($curl);
        $info = curl_getinfo( $curl );
    
        array_push($arrOK, $info['http_code']);
        logErro($db, "3", $arrOK, NULL);

        return;
    }
    else {

        if (substr($info['http_code'],0,1) == '5') {

            $arrErr = array("http_code" => "503", "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) ! idNF=".$notaFiscal->idNotaFiscal, "codigo" => "P05");
            logErro($db, "0", $arrErr, NULL);
            return;
        }
        else {

            $msg = $result;
            $dados = json_decode($result);
            if (isset($dados->error)) {

                $arrErr = array("http_code" => "500", "message" => "Erro no envio da NFSe ! idNF=".$notaFiscal->idNotaFiscal, "error" => "(".$dados->error.") ".$dados->error_description, "codigo" => "P00");
                logErro($db, "1", $arrErr, $notaFiscal);
                return;
            }
            else {

                $xmlNFRet = simplexml_load_string(trim($result));
                $msgRet = (string) $xmlNFRet->message;
                $codMsg = $utilities->codificaMsg($msgRet);
                if ($codMsg=='P05')
                    $codMsg=='P00'; // Não insistir no Timeout quando for erro de arquivo

                $arrErr = array("http_code" => "500", "message" => "Erro no envio da NFSe ! idNF=".$notaFiscal->idNotaFiscal, "error" => $msgRet, "codigo" => $codMsg);
                logErro($db, "1", $arrErr, $notaFiscal);
                return;
            }
        }
    }

?>