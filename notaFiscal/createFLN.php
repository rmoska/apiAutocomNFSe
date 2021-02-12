<?php

// Classe para emissão de NFSe PMF Homologação / Produção
//
if ($tomador->uf != 'SC') $cfps = '9203';
else if ($tomador->codigoMunicipio != '4205407') $cfps = '9202';
else $cfps = '9201';
$notaFiscal->cfop = $cfps;


$logMsg->register('E', 'notaFiscal.createFLN', $checkNF["idNotaFiscal"], $strData);

//
// abre transação itemVenda - notaFiscal - notaFiscalItem
$db->beginTransaction();

// create notaFiscal
$notaFiscal->idEmitente = $emitente->idEmitente;
$retorno = $notaFiscal->create();
if(!$retorno[0]){

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Nota Fiscal.(I01)", "erro" => $retorno[1], "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Nota Fiscal.(I01)', $retorno[1]." = ".$strData);
    exit;
}

//check / create itemVenda
$totalItens = 0;
$nfiOrdem = 0;
foreach ( $data->itemServico as $item )
{
    $nfiOrdem++;
    if(
        empty($item->codigo) ||
        empty($item->descricao) ||
        empty($item->cnae) ||
        empty($item->nbs) ||
        empty($item->codigoServico) ||
        empty($item->quantidade) ||
        empty($item->valor) ||
        (!($item->cst>=0)) ||
        (!($item->taxaIss>=0)) 
    ){

        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Item da Nota Fiscal. Dados incompletos.", "codigo" => "A05"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item da Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Item da Nota Fiscal. Dados incompletos.', $strData);
        exit;
    }
    
    $itemVenda = new ItemVenda($db);
    $notaFiscalItem = new NotaFiscalServicoItem($db);

    $itemVenda->codigo = $item->codigo;
    if (($idItemVenda = $itemVenda->check()) > 0) {

        $notaFiscalItem->idItemVenda = $idItemVenda;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->cnae = $item->cnae;
        $itemVenda->ncm = $item->nbs;
        $itemVenda->codigoServico = $item->codigoServico;
        $itemVenda->updateVar();
    }
    else {

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->cnae = $item->cnae;
        $itemVenda->ncm = $item->nbs;
        $itemVenda->codigoServico = $item->codigoServico;

        $retorno = $itemVenda->create();
        if(!$retorno[0]){

            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(I01)", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Item Venda.(I01)', $retorno[1]." = ".$strData);
            exit;
        }
        else{
            $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
        }
    }

    $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
    $notaFiscalItem->numeroOrdem = $nfiOrdem;
    $notaFiscalItem->cnae = $item->cnae;
    $notaFiscalItem->codigoServico = $item->codigoServico;
    $notaFiscalItem->unidade = "UN";
    $notaFiscalItem->quantidade = floatval($item->quantidade);
    $notaFiscalItem->valorUnitario = floatval($item->valor);
    $notaFiscalItem->valorTotal = (floatval($item->valor)*floatval($item->quantidade));
    $notaFiscalItem->cstIss = $item->cst;

    $totalItens += floatval($notaFiscalItem->valorTotal);

    // 1=SN 3=SN+Ret 6=SN+ST 12=Isenta 13=NTrib
    if (($item->cst != '1') && ($item->cst != '3') && ($item->cst != '6') && ($item->cst != '12') && ($item->cst != '13') && ($item->taxaIss > 0)) {
        $notaFiscalItem->valorBCIss = $notaFiscalItem->valorTotal;
        $notaFiscalItem->taxaIss = $item->taxaIss;
        $notaFiscalItem->valorIss = ($item->valor*$item->quantidade)*($item->taxaIss/100);
    }
    else {
        $notaFiscalItem->valorBCIss = 0.00;
        $notaFiscalItem->taxaIss = 0.00;
        $notaFiscalItem->valorIss = 0.00;
    }

    $retorno = $notaFiscalServicoItem->create();
    if(!$retorno[0]){

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Nota Fiscal.(NFi01)", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Nota Fiscal.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Item Nota Fiscal.(I01)', $retorno[1]." = ".$strData);
        exit;
    }
    else{

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $arrayItemNF[] = $notaFiscalItem;
    }
}

if (number_format($totalItens,2,'.','') != number_format($notaFiscal->valorTotal,2,'.','')) {

    $db->rollBack();
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal.(NFi02)", 
                           "erro" => "Valor dos itens(".number_format($totalItens,2,'.','').") não fecha com Valor Total da Nota(".number_format($notaFiscal->valorTotal,2,'.','').")", 
                           "codigo" => "A04"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Valor dos itens(".number_format($totalItens,2,'.','').") não fecha com Valor Total da Nota(".number_format($notaFiscal->valorTotal,2,'.','').")".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', "Valor dos itens(".number_format($totalItens,2,'.','').") não fecha com Valor Total da Nota(".number_format($notaFiscal->valorTotal,2,'.','').")", $strData);
    exit;
}

// se houve problema na inclusão dos itens
if (count($arrayItemNF) == 0) {

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Erro na inclusão dos Itens da Nota Fiscal.", "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na inclusão dos Itens da Nota Fiscal. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Erro na inclusão dos Itens da Nota Fiscal.', $strData);
    exit;
}

//
// fecha atualizações
$db->commit();
// 
// cria e transmite nota fiscal
//
// buscar token conexão
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $notaFiscal->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
$autorizacao->readOne();

if(($notaFiscal->ambiente=="P") && (is_null($autorizacao->aedf) || ($autorizacao->aedf==''))) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. AEDFe não informado.", "codigo" => "A02"));
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal. AEDFe não informado.', $strData);
    exit;
}
else if(!$autorizacao->getToken($notaFiscal->ambiente)){

    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Token de acesso rejeitado (Confira CMC e senha PMF).", "codigo" => "A02"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Token de acesso rejeitado (Confira CMC e senha PMF). Emitente=".$autorizacao->idEmitente." AEDF=".$autorizacao->aedf." Pwd=".$autorizacao->senhaWeb." NF=".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal. Token de acesso rejeitado (Confira CMC e senha PMF).', $strData);
    exit;
}

// calcular Imposto Aproximado IBPT
$notaFiscal->calcImpAprox();

// montar xml nfse
$vlTotBC = 0; 
$vlTotISS = 0; 
$vlTotServ = 0; 
foreach ( $arrayItemNF as $notaFiscalItem ) {
    $vlTotServ += $notaFiscalItem->valorTotal;
    $vlTotBC += $notaFiscalItem->valorBCIss; 
    $vlTotISS += $notaFiscalItem->valorIss; 
}
//
include_once '../shared/utilities.php';
$utilities = new Utilities();
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
$xml->writeElement("razaoSocialTomador", substr($tomador->nome,0,80));
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
foreach ( $arrayItemNF as $notaFiscalItem ) {

    $xml->startElement("itemServico");
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
if (($autorizacao->mensagemnf > '') || ($notaFiscal->obsImpostos > '') || ($notaFiscal->dadosAdicionais>''))
    $xml->writeElement("dadosAdicionais", $autorizacao->mensagemnf." ".$notaFiscal->obsImpostos." ".$notaFiscal->dadosAdicionais);
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

    $notaFiscal->situacao = 'E';
    $notaFiscal->textoResposta = "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg;
    $notaFiscal->update();
    //
    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg, "codigo" => "A02"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->msg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas com Certificado. Emitente='.$autorizacao->idEmitente, $nfse->msg);
    exit;
}

$xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
if ($nfse->errStatus) {

    $notaFiscal->situacao = 'E';
    $notaFiscal->textoResposta = "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg;
    $notaFiscal->update();
    //
    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg, "codigo" => "A02"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas na assinatura do XML. Emitente='.$autorizacao->idEmitente, $nfse->msg);
    exit;
}
//
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
    //
    // update notaFiscal
    $retorno = $notaFiscal->update();
    if(!$retorno[0]) {

        // força update simples
        $notaFiscal->updateSituacao("F");

        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Nota Fiscal.", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Nota Fiscal.', $retorno[1]);
        exit;
    }
    else {
        //
        // gerar pdf
        include './gerarPdfFLN.php';
        $gerarPdf = new gerarPdf();
        $arqPDF = $gerarPdf->printDanfpse($notaFiscal->idNotaFiscal, $db);
        $linkNF = "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF;
        $notaFiscal->linkNF = $linkNF;
        $notaFiscal->update();

        // set response code - 201 created
        http_response_code(201);
        $arrOK = array("http_code" => "201", 
                                "message" => "Nota Fiscal emitida", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "numeroNF" => $notaFiscal->numero,
                                "xml" => $linkXml,
                                "pdf" => $linkNF);
        echo json_encode($arrOK);
//        $logMsg->register('S', 'notaFiscal.create', 'Nota Fiscal emitida', $strData);
        exit;
    }
}
else {

    if (substr($info['http_code'],0,1) == '5') {

        //
        $notaFiscal->situacao = "T";
        $notaFiscal->textoJustificativa = "Problemas no servidor (Indisponivel ou Tempo de espera excedido) !";

        // update notaFiscal
        $retorno = $notaFiscal->update();
        if(!$retorno[0]){

            //$notaFiscal->deleteCompletoTransaction();

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar a Nota Fiscal. Serviço indisponível.", "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal. Serviço indisponível. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Nota Fiscal. Serviço indisponível.', $retorno[1]);
            exit;
        }

        http_response_code(503);
        echo json_encode(array("http_code" => "503", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !",
                                "codigo" => "P05"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido). idNotaFiscal=".$notaFiscal->idNotaFiscal."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('A', 'notaFiscal.create', 'Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).', 'idNotaFiscal='.$notaFiscal->idNotaFiscal);
        exit;
    }
    else {

        $msg = $result;
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] result = (".$msg.")\n"), 3, "../arquivosNFSe/apiErrors.log");
        $dados = json_decode($result);

        if (isset($dados->error)) {

            $notaFiscal->situacao = 'E';
            $notaFiscal->textoResposta = "(".$dados->error.") ".$dados->error_description;
            $notaFiscal->update();
    
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Erro no envio da NFSe !!", "resposta" => "(".$dados->error.") ".$dados->error_description, "codigo" => "P00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !! (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Erro no envio da NFPSe !', '('.$dados->error.') '.$dados->error_description);
            exit;
        }
        else {

            $xmlNFRet = simplexml_load_string(trim($result));
            $msgRet = (string) $xmlNFRet->message;
            $notaFiscal->textoResposta = $msgRet;
            $codMsg = $utilities->codificaMsg($msgRet);
            if ($codMsg=='P05')
                $notaFiscal->situacao = 'T';
            else
                $notaFiscal->situacao = 'E';
            $notaFiscal->update();

            http_response_code(401);
            echo json_encode(array("http_code" => "401", 
                                   "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                   "message" => "Erro no envio da NFSe !", "resposta" => $msgRet, "codigo" => $codMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! idNotaFiscal =".$notaFiscal->idNotaFiscal."  (".$msgRet.") ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Erro no envio da NFPSe ! ('.$msgRet.') ', $strData);
            exit;
        }
    }
}

?>