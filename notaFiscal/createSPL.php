<?php

// Classe para emissão de NFSe PMF Homologação / Produção
//
// check / create tomador
if(
    empty($data->tomador->documento) ||
    empty($data->tomador->nome) ||
    empty($data->tomador->logradouro) ||
    empty($data->tomador->numero) ||
    empty($data->tomador->bairro) ||
    empty($data->tomador->cep) ||
    empty($data->tomador->codigoMunicipio) ||
    empty($data->tomador->uf) ||
    empty($data->tomador->email) 
){

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Tomador. Dados incompletos.", "codigo" => "A03"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Tomador. Dados incompletos.', $strData);
    exit;
}

//
// abre transação tomador - itens - nf - nfitens
$db->beginTransaction();

$tomador = new Tomador($db);

// set tomador property values
$tomador->documento = $data->tomador->documento;
$tomador->nome = $data->tomador->nome;
$tomador->logradouro = $data->tomador->logradouro;
$tomador->numero = $data->tomador->numero;
$tomador->complemento = $data->tomador->complemento;
$tomador->bairro = $data->tomador->bairro;
$tomador->cep = $data->tomador->cep;
$tomador->codigoMunicipio = $data->tomador->codigoMunicipio;
$tomador->uf = $data->tomador->uf;
$emailTomador = filter_var($data->tomador->email, FILTER_SANITIZE_EMAIL);
if (!filter_var($emailTomador, FILTER_VALIDATE_EMAIL)) {
    $emailTomador = $emitente->email;
}
$tomador->email = $emailTomador;

// check tomador
if (($idTomador = $tomador->check()) > 0) {

    $tomador->idTomador = $idTomador;
    $notaFiscal->idTomador = $idTomador;

    $retorno = $tomador->update();
    if(!$retorno[0]){

        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível atualizar Tomador.", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Tomador. Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Tomador.', $retorno[1]." = ".$strData);
        exit;
    }
}
// create tomador
else {

    $retorno = $tomador->create();
    if($retorno[0]){
        // set notaFiscal
        $notaFiscal->idTomador = $tomador->idTomador;
    }
    else{

        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Tomador.", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Tomador.', $retorno[1]." = ".$strData);
        exit;
    }
}

if ($tomador->uf != 'SC') $cfps = '9203';
else if ($tomador->codigoMunicipio != '4205407') $cfps = '9202';
else $cfps = '9201';
$notaFiscal->cfop = $cfps;

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
    if (($idItemVenda = $itemVenda->check()) > 0) 
    {
        $notaFiscalItem->idItemVenda = $idItemVenda;

        $itemVenda->descricao = $item->descricao;
        $itemVenda->cnae = $item->cnae;
        $itemVenda->ncm = $item->nbs;

        $itemVenda->updateVar();

    }
    else 
    {

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $itemVenda->descricao = $item->descricao;
//        $itemVenda->cnae = $item->cnae;
        $itemVenda->ncm = $item->nbs;

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
    $notaFiscalItem->cnae = $item->cnae; // código serviço SP
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

    $retorno = $notaFiscalItem->create();
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
// cria e transmite nota fiscal
else {

    // buscar token conexão
    $autorizacao = new Autorizacao($db);
    $autorizacao->idEmitente = $notaFiscal->idEmitente;
    $autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
    $autorizacao->readOne();

    if(($notaFiscal->ambiente=="P") && (is_null($autorizacao->aedf) || ($autorizacao->aedf==''))) {

        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. AEDFe não informado.", "codigo" => "A02"));
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal. AEDFe não informado.', $strData);
        exit;
    }
    else if(!$autorizacao->getToken($notaFiscal->ambiente)){

        $db->rollBack();
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

    $serieRPS = 'FC01'; // exclusivo FastConnect
    $nuRPS = $autorizacao->sequenciaLote + 1;
    $vlRPS = str_pad(strval(intval($notaFiscal->valorTotal * 100)), 15,'0', STR_PAD_LEFT);
    if (strlen(trim($tomador->documento))==11) $idDoc = '1';
    else if (strlen(trim($tomador->documento))==14) $idDoc = '2';
    else $idDoc = '3';


    $strSignRPS = $autorizacao->cmc.str_pad($serieRPS, 5, ' ').str_pad($nuRPS, 12, '0', STR_PAD_LEFT).
                  date("Ymd", strtotime($notaFiscal->dataEmissao)).trim($notaFiscalItem->cstIss).
                  'NN'.$vlRPS.'000000000000000'.str_pad($notaFiscalItem->cnae, 5, '0', STR_PAD_LEFT).
                  $idDoc.str_pad($tomador->documento, 14, '0', STR_PAD_LEFT).'300000000000000N';
    $hashStr = sha1($strSignRPS);

    //	
    include_once '../comunicacao/signNFSe.php';
    $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);

    $strRPS = new SignNFSe($arraySign);
    if($strRPS->errStatus) {

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg, "codigo" => "A02"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->msg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas com Certificado. Emitente='.$autorizacao->idEmitente, $nfse->msg);
        exit;
    }

    $xmlAss = $strRPS->signXML($xmlNFe, '');
    if ($strRPS->errStatus) {

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg, "codigo" => "A02"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas na assinatura do XML. Emitente='.$autorizacao->idEmitente, $nfse->msg);
        exit;
    }




    //
    include_once '../shared/utilities.php';
    $utilities = new Utilities();
    //			
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("PedidoEnvioRPS");
        $xml->startElement("Cabecalho");
        $xml->writeAttribute("Versao", "1");
        $xml->writeAttribute("xmlns", "");
            $xml->startElement("CPFCNPJRemetente");
                $xml->writeElement("CNPJ", $emitente->documento);
            $xml->endElement(); // xmlNfpse
        $xml->endElement(); // xmlNfpse
        $xml->startElement("RPS");
        $xml->writeAttribute("xmlns", "");
            $xml->writeElement("Assinatura", $assRPS);
            $xml->startElement("ChaveRPS");
                $xml->writeElement("InscricaoPrestador", $autorizacao->cmc);
                $xml->writeElement("SerieRPS", $serieRPS);
                $xml->writeElement("NumeroRPS", $numeroRPS);
            $xml->endElement(); // ChaveRPS
            $xml->writeElement("TipoRPS", "RPS");
            $xml->writeElement("DataEmissao", $notaFiscal->dataEmissao);
            $xml->writeElement("StatusRPS", "N");
            $xml->writeElement("TributacaoRPS", $notaFiscal->cstIss);
            $xml->writeElement("ValorServicos", $notaFiscal->valorTotal);
            $xml->writeElement("ValorDeducoes", "0,00");
            $xml->writeElement("CodigoServico", $notaFiscalItem->cnae); // ????????????????????
            $xml->writeElement("AliquotaServicos", $notaFiscalItem->taxaIss);
            $xml->writeElement("ISSRetido", $notaFiscalItem->valorIss);
            $xml->startElement("CPFCNPJTomador");
                if (strlen(trim($tomador->documento))==11)
                    $xml->writeElement("CPF", $tomador->documento);
                else 
                    $xml->writeElement("CNPJ", $tomador->documento);
            $xml->endElement(); // CPFCNPJTomador
            $xml->writeElement("RazaoSocialTomador", $tomador->nome);
            $xml->startElement("EnderecoTomador");
                $xml->writeElement("Logradouro", $tomador->logradouro);
                $xml->writeElement("NumeroEndereco", $tomador->numero);
                $xml->writeElement("ComplementoEndereco", $tomador->complemento);
                $xml->writeElement("Bairro", $tomador->bairro);
                $xml->writeElement("Cidade", $tomador->codigoMunicipio);
                $xml->writeElement("UF", $tomador->uf);
                $xml->writeElement("CEP", $tomador->cep);
            $xml->endElement(); // EnderecoTomador
            $xml->writeElement("EmailTomador", $tomador->email);
            $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
            $xml->writeElement("Discriminacao", $nmProd);
        $xml->endElement(); // RPS
    $xml->endElement(); // PedidoEnvioRPS







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
    foreach ( $arrayItemNF as $notaFiscalItem ) {

        $xml->startElement("itemServico");
        $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
        if ($notaFiscalItem->observacao > '')
            $nmProd .= ' - '.$notaFiscalItem->observacao;
        $xml->writeElement("descricaoServico", trim($nmProd));
        $xml->writeElement("idCNAE", trim($notaFiscalItem->cnae));
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

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg, "codigo" => "A02"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->msg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas com Certificado. Emitente='.$autorizacao->idEmitente, $nfse->msg);
        exit;
    }

    $xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($nfse->errStatus) {

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg, "codigo" => "A02"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível gerar Nota Fiscal(idNF='.$notaFiscal->idNotaFiscal.'). Problemas na assinatura do XML. Emitente='.$autorizacao->idEmitente, $nfse->msg);
        exit;
    }
}
//
// fecha atualizações
$db->commit();
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

        //$notaFiscal->deleteCompletoTransaction();
        //$notaFiscal->updateSituacao("E");

        $msg = $result;
        $dados = json_decode($result);

        if (isset($dados->error)) {

            $notaFiscal->situacao = 'E';
            $notaFiscal->textoResposta = "(".$dados->error.") ".$dados->error_description;
            $notaFiscal->update();
    
            http_response_code(500);
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