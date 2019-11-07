<?php

    if ($tomador->uf != 'SC') $cfps = '9203';
    else if ($tomador->codigoMunicipio != '4205407') $cfps = '9202';
    else $cfps = '9201';
    $notaFiscal->cfop = $cfps;          
    
    // 
    // cria e transmite nota fiscal
    //
    // buscar token conexão
    $autorizacao = new Autorizacao($db);
    $autorizacao->idEmitente = $notaFiscal->idEmitente;
    $autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
    $autorizacao->readOne();

    if(($notaFiscal->ambiente=="P") && (is_null($autorizacao->aedf) || ($autorizacao->aedf==''))) {

        $arrErr = array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. AEDFe não informado.");
        logErro("1", $arrErr, NULL);
        continue;
    }
    else if(!$autorizacao->getToken($notaFiscal->ambiente)){

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Token de acesso rejeitado (Confira CMC e senha PMF).");
        logErro("1", $arrErr, NULL);
        continue;
    }

    //			
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("xmlProcessamentoNfpse");
    $xml->writeElement("bairroTomador", $tomador->bairro);
    $xml->writeElement("baseCalculo", number_format($vlTotBC,2,'.',''));
    if ($vlTotBCST>0)
        $xml->writeElement("baseCalculoSubstituicao", number_format($vlTotBCST,2,'.',''));
    $xml->writeElement("cfps", $notaFiscal->cfop);
    $xml->writeElement("codigoMunicipioTomador", $tomador->codigoMunicipio);
    $xml->writeElement("codigoPostalTomador", $tomador->cep);
    if($tomador->complemento > '')
        $xml->writeElement("complementoEnderecoTomador", $tomador->complemento);
    $xml->writeElement("dadosAdicionais", $notaFiscal->obsImpostos." ".$notaFiscal->dadosAdicionais);
    $xml->writeElement("dataEmissao", $notaFiscal->dataEmissao);
    $xml->writeElement("emailTomador", $tomador->email);
    $xml->writeElement("identificacao", $notaFiscal->idNotaFiscal);
    $xml->writeElement("identificacaoTomador", $tomador->documento);
    //		
    // ITENS
    $xml->startElement("itensServico");
    foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {

        $xml->startElement("itemServico");
        $xml->writeElement("aliquota", number_format(($notaFiscalItem->taxaIss/100),4,'.',''));
        $xml->writeElement("cst", $notaFiscalItem->cstIss);
        //
        $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
        if ($notaFiscalItem->observacao > '')
            $nmProd .= ' - '.$notaFiscalItem->observacao;
        $xml->writeElement("descricaoServico", trim($nmProd));
        //
        $xml->writeElement("idCNAE", trim($notaFiscalItem->cnae));
        $xml->writeElement("quantidade", number_format($notaFiscalItem->quantidade,0,'.',''));
        $xml->writeElement("baseCalculo", number_format($notaFiscalItem->valorBCIss,2,'.',''));
        $xml->writeElement("valorTotal", number_format($notaFiscalItem->valorTotal,2,'.',''));
        $xml->writeElement("valorUnitario", number_format($notaFiscalItem->valorUnitario,2,'.',''));
        $xml->endElement(); // ItemServico
    }
    $xml->endElement(); // ItensServico
    //
    $xml->writeElement("logradouroTomador", trim($utilities->limpaEspeciais($tomador->logradouro)));

    if ($notaFiscal->ambiente == "P") // PRODUÇÃO
        $nuAEDF = $autorizacao->aedf; 
    else // HOMOLOGAÇÃO
        $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter

    $xml->writeElement("numeroAEDF", $nuAEDF);
    if ($tomador->numero>0)
        $xml->writeElement("numeroEnderecoTomador", $tomador->numero);
    $xml->writeElement("numeroSerie", 1);
    $xml->writeElement("razaoSocialTomador", $tomador->nome);
//		if ($tomador->telefone > '')
//			$xml->writeElement("telefoneTomador", $tomador->telefone);
    if ($tomador->uf >'')
        $xml->writeElement("ufTomador", $tomador->uf);
    $xml->writeElement("valorISSQN", number_format($vlTotISS,2,'.',''));
    $xml->writeElement("valorTotalServicos", number_format($vlTotServ,2,'.',''));
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

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg);
        logErro("1", $arrErr, NULL);
        continue;
    }

    $xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($nfse->errStatus) {

        $arrErr = array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg);
        logErro("1", $arrErr, NULL);
        continue;
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
        // update notaFiscal
        $retorno = $notaFiscal->update();
        if(!$retorno[0]) {

            // força update simples
            $notaFiscal->updateSituacao("F");

            $arrErr = array("http_code" => "500", "message" => "Não foi possível atualizar Nota Fiscal.(A01)", "erro" => $retorno[1]);
            logErro("1", $arrErr, NULL);
            continue;
        }
        else {
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
        
//            if ($info['http_code'] == '200')
            array_push($arrOK, $info['http_code']);
            logErro("3", $arrOK, NULL);

            continue;
        }
    }
    else {

        if (substr($info['http_code'],0,1) == '5') {

            $arrErr = array("http_code" => "503", "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !");
            logErro("0", $arrErr, NULL);
            continue;
        }
        else {

            $msg = $result;
            $dados = json_decode($result);
            if (isset($dados->error)) {

                $arrErr = array("http_code" => "500", "message" => "Erro no envio da NFSe !(1)", "resposta" => "(".$dados->error.") ".$dados->error_description);
                logErro("2", $arrErr, $notaFiscal);
                continue;
            }
            else {

                $xmlNFRet = simplexml_load_string(trim($result));
                $msgRet = (string) $xmlNFRet->message;
                $arrErr = array("http_code" => "500", "message" => "Erro no envio da NFSe !(2)", "resposta" => $msgRet);
                logErro("2", $arrErr, $notaFiscal);
                continue;
            }
        }
    }

?>