<?php

include_once '../objects/autorizacaoChave.php';

//
// buscar token conexão
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $notaFiscal->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
$autorizacao->readOne();

$autorizacaoChave = new AutorizacaoChave($db);
$autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;
$stmt = $autorizacaoChave->buscaChave();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $aAutoChave[$row['chave']] = $row['valor'];
}
if ( !isset($aAutoChave["login"]) ||
        !isset($aAutoChave["senhaWeb"]) ) {

    $arrErr = array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. Dados de Autorização incompletos.. idNF=".$notaFiscal->idNotaFiscal, "codigo" => "A02");
    logErro($db, "1", $arrErr, $notaFiscal);
    return;
};
include_once '../comunicacao/comunicaNFSe.php';
$arraySign = array("sisEmit" => 2, "tpAmb" => "P", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
$objNFSe = new ComunicaNFSe($arraySign);

$municipioEmitente = new Municipio($db);
$municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
$municipioEmitente->buscaMunicipioTOM($emitente->codigoMunicipio);

$municipioTomador = new Municipio($db);
$municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
$municTomadorTOM = $municipioTomador->buscaMunicipioTOM($tomador->codigoMunicipio);

//			
$xml = new XMLWriter;
$xml->openMemory();
//
// Inicia o cabeçalho do documento XML
$xml->startElement("nfse");

if ($notaFiscal->ambiente == "H") // HOMOLOGAÇÃO
    $xml->writeElement("nfse_teste", "1"); // define ambiente HOMOLOGAÇÃO

    $xml->startElement("nf");
        $xml->writeElement("valor_total", number_format($vlTotServ,2,',',''));
        $xml->writeElement("valor_desconto", "0,00");
        $xml->writeElement("valor_ir", "0,00");
        $xml->writeElement("valor_inss", "0,00");
        $xml->writeElement("valor_contribuicao_social", "0,00");
        $xml->writeElement("valor_rps", "0,00");
        $xml->writeElement("valor_pis", "0,00");
        $xml->writeElement("valor_cofins", "0,00");
        if (($autorizacao->mensagemnf) || ($notaFiscal->dadosAdicionais>''))
            $xml->writeElement("observacao", $autorizacao->mensagemnf." ".$notaFiscal->dadosAdicionais);
    $xml->endElement(); // nf
    $xml->startElement("prestador");
        $xml->writeElement("cpfcnpj", $emitente->documento);
        $xml->writeElement("cidade", $municipioEmitente->codigoTOM); // Palhoça
    $xml->endElement(); // prestador
    $xml->startElement("tomador");
        if (strlen($tomador->documento)==14) $tipoTomador = 'J'; else $tipoTomador = 'F';
        $xml->writeElement("tipo", $tipoTomador); 
        $xml->writeElement("cpfcnpj", $tomador->documento);
        $xml->writeElement("ie", "");
        $xml->writeElement("nome_razao_social", $tomador->nome);
        $xml->writeElement("sobrenome_nome_fantasia", $tomador->nome);
        $xml->writeElement("logradouro", trim($utilities->limpaEspeciais($tomador->logradouro)));
        $xml->writeElement("email", $tomador->email);
        if ($tomador->numero>0)
            $xml->writeElement("numero_residencia", $tomador->numero);
        if($tomador->complemento > '')
            $xml->writeElement("complemento", $tomador->complemento);
        $xml->writeElement("bairro", $tomador->bairro);
        $xml->writeElement("cidade", $municipioTomador->codigoTOM);
        $xml->writeElement("cep", $tomador->cep);
    $xml->endElement(); // tomador
    // ITENS
    $xml->startElement("itens");

    foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {

        $notaFiscalItem->readItemVenda();
        $xml->startElement("lista");
            $xml->writeElement("tributa_municipio_prestador", "N");
            $xml->writeElement("codigo_local_prestacao_servico", $municipioEmitente->codigoTOM);
            $xml->writeElement("unidade_codigo", 1);
            $xml->writeElement("unidade_quantidade", number_format($notaFiscalItem->quantidade,0,',',''));
            $xml->writeElement("unidade_valor_unitario", number_format($notaFiscalItem->valorUnitario,4,',',''));
            $xml->writeElement("codigo_item_lista_servico", "402"); // LC116
            $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
            if ($notaFiscalItem->observacao > '')
                $nmProd .= ' - '.$notaFiscalItem->observacao;
            $xml->writeElement("descritivo", trim($nmProd));
            $xml->writeElement("aliquota_item_lista_servico", number_format($notaFiscalItem->taxaIss,2,',',''));
            $xml->writeElement("situacao_tributaria", $notaFiscalItem->cstIss); 
            $xml->writeElement("valor_tributavel", number_format($notaFiscalItem->valorUnitario,4,',',''));
            $xml->writeElement("valor_deducao", "0,00");
            $xml->writeElement("valor_issrf", "0,00");
        $xml->endElement(); // lista

    }
    $xml->endElement(); // itens
$xml->endElement(); // nfse
//
$xmlNFe = $xml->outputMemory(true);

$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFSe = "../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml";
$arqNFe = fopen($arqNFSe,"wt");
fwrite($arqNFe, $xmlNFe);
fclose($arqNFe);
//	
// transmite NFSe	
if (function_exists('curl_file_create')) { // php 5.5+
    $cFile = curl_file_create($arqNFSe);
} else {
    $cFile = '@' . realpath($arqNFSe);
}

$params = array(
    'login' => $aAutoChave["login"],
    'senha' => $aAutoChave["senhaWeb"],
    'f1' => $cFile
);

$retEnv = $objNFSe->transmitirNFSeIpm( $params );
//
$result = $retEnv[0];
$info = $retEnv[1];
    
if ($info['http_code'] == '200') {

    if ($xmlNFRet = @simplexml_load_string($result)) {

        $codRet = explode(" ", $xmlNFRet->mensagem->codigo);
        if ($notaFiscal->ambiente == "H") { // HOMOLOGAÇÃO
            if (intval($codRet[0])==285) { // NFSe válida para emissao (IPM não emite NF homologação, apenas valida XML)
                $nuNF = 1; // 
                $cdVerif = 'OK'; //
            }
            else {
                $cdVerif = (string)$xmlNFRet->mensagem->codigo;
            }
        } 
        else {
            if (intval($codRet[0]) == 1) { // sucesso

                $nuNF = $xmlNFRet->numero_nfse;
                $serieNF = $xmlNFRet->serie_nfse;
                $cdVerif = $xmlNFRet->cod_verificador_autenticidade;
                $dtNF = $xmlNFRet->data_nfse;
                $hrNF = $xmlNFRet->hora_nfse;
                $dtProc = substr($dtNF,6,4).'-'.substr($dtNF,3,2).'-'.substr($dtNF,0,2).' '.substr($hrNF,0,2).':'.substr($hrNF,3,2).':'.substr($hrNF,6,2);
                $linkPDF = (string)$xmlNFRet->link_nfse;

                $arqNFSe = "../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml";
                $xmlNFSe = simplexml_load_file($arqNFSe);
                $xmlNFSe->nf->addChild("numero_nfse", $nuNF);
                $xmlNFSe->nf->addChild("serie_nfse", $serieNF);
                $xmlNFSe->nf->addChild("data_nfse", $dtNF);
                $xmlNFSe->nf->addChild("hora_nfse", $hrNF);

                $xmlNF = $xmlNFSe->asXML();
                $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
                $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
                $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
                fwrite($arqNFe, $xmlNF);
                fclose($arqNFe);
                $linkXml = "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet;

                $notaFiscal->numero = $nuNF;
                $notaFiscal->chaveNF = $cdVerif;
                $notaFiscal->linkXml = $linkXml;
                $notaFiscal->linkNF = trim($linkPDF);
                $notaFiscal->situacao = "F";
                $notaFiscal->dataProcessamento = $dtProc;
                $notaFiscal->textoJustificativa = 'Reprocessada por Timeout';
            
                //
                // update notaFiscal
                $retorno = $notaFiscal->update();

                // set response code - 201 created
                $arrOK = (array("http_code" => "201", 
                                        "message" => "Nota Fiscal emitida", 
                                        "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                        "numeroNF" => $notaFiscal->numero,
                                        "xml" => $linkXml,
                                        "pdf" => $linkPDF));
                $retNFSe = json_encode($arrOK);
//                $logMsg->register('S', 'notaFiscal.retryIPM', 'Nota Fiscal emitida', $strData);

                // retorno FastConnect
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
            else { // resposta <> 1
                $codMsg = "P00"; // $utilities->codificaMsgIPM($msgRet);
                $cdVerif = (string)$xmlNFRet->mensagem->codigo;
            }
        }
    } 
    else { // retorno não é xml (acontece com IPM para login errado: "Não foi encontrado na tb.dcarq.unico a cidade(codmun) do Usuário:")
        $codMsg = "P00"; // $utilities->codificaMsgIPM($msgRet);
        $cdVerif = utf8_decode($result);
    }

    $notaFiscal->situacao = 'E';
    $notaFiscal->textoResposta = $cdVerif;
    $notaFiscal->update();

    $arrErr = (array("http_code" => "401", 
                     "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                     "message" => "Erro no envio da NFSe !", 
                     "resposta" => $cdVerif, 
                     "codigo" => $codMsg));
    logErro($db, "1", $arrErr, $notaFiscal);
    return;
}
else {

    if (substr($info['http_code'],0,1) == '5') {

        $arrErr = array("http_code" => "503", "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) ! idNF=".$notaFiscal->idNotaFiscal, "codigo" => "P05");
        logErro($db, "0", $arrErr, NULL);
        return;
    }
    else {

        if ($xmlNFRet = @simplexml_load_string($result))
            $msgRet = (string)$xmlNFRet->mensagem->codigo;
        else 
            $msgRet = utf8_decode($result);
        
        $notaFiscal->textoResposta = $msgRet;
        $notaFiscal->situacao = 'E';
        $notaFiscal->update();

        $arrErr = (array("http_code" => "401", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe !", 
                                "resposta" => $msgRet, 
                                "codigo" => $codMsg));
        logErro($db, "0", $arrErr, NULL);
        return;
    }
}

?>