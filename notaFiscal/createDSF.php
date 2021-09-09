<?php

// Classe para emissão de NFSe PM São José/SC Homologação / Produção
//
if( empty($data->documento) ||
    empty($data->idVenda) ||
    empty($data->valorTotal) || 
    ($data->valorTotal <= 0) ) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

include_once '../objects/notaFiscalServico.php';
include_once '../objects/notaFiscalServicoItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
include_once '../objects/municipio.php';
 
$notaFiscal = new NotaFiscalServico($db);

// set notaFiscal property values
$notaFiscal->ambiente = $cfg->ambiente;
$notaFiscal->docOrigemTipo = "V"; // Venda
$notaFiscal->docOrigemNumero = $data->idVenda;
$notaFiscal->idEntradaSaida = "S";
$notaFiscal->situacao = "P"; // Pendente

$notaFiscal->valorTotal = $data->valorTotal;
$notaFiscal->dataInclusao = date("Y-m-d");
$notaFiscal->dataEmissao = date("Y-m-d");
$notaFiscal->dadosAdicionais = $data->observacao;

// check NF já gerada para esta Venda
$checkNF = $notaFiscal->checkVenda();
if ($checkNF["existe"] > 0) {

    ($checkNF["situacao"] == "F") ? $situacao = "Faturada" : $situacao = "Pendente"; 
    http_response_code(400);
    echo json_encode(array("http_code" => "400", 
                            "message" => "Nota Fiscal já gerada para esta Venda. NF n. ".$checkNF["numeroNF"]." - Situação ".$situacao));
    exit;
}

//
// abre transação tomador - itens - nf - nfitens
$db->beginTransaction();

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

    $db->rollBack();
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Tomador. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

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
$tomador->email = $data->tomador->email;

// check tomador
if (($idTomador = $tomador->check()) > 0) {

    $tomador->idTomador = $idTomador;
    $notaFiscal->idTomador = $idTomador;

    $retorno = $tomador->update();
    if(!$retorno[0]){

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Tomador.", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Tomador. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
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
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Tomador.", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}

// create notaFiscal
$notaFiscal->idEmitente = $emitente->idEmitente;
$retorno = $notaFiscal->create();
if(!$retorno[0]){

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Nota Fiscal.(I01)", "erro" => $retorno[1]));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

$codServPrinc = $data->itemServico[0]->codigoServico;
$cstPrinc = $data->itemServico[0]->cst;
$txIssPrinc = $data->itemServico[0]->taxaIss;
foreach ( $data->itemServico as $item )
{
    if (($item->cst <> $cstPrinc) || ($item->taxaIss <> $txIssPrinc)) {

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Itens da Nota Fiscal devem usar mesmo Situação Tributária e Taxa de ISS.(Vi00)", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Itens da Nota Fiscal devem usar mesmo Situação Tributária e Taxa de ISS.(Vi00). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
//        exit;   
    }
}

//check / create itemVenda
$totalItens = 0;
$nfiOrdem = 0;
$descricaoServicoUnico = "";
foreach ( $data->itemServico as $item )
{
    $nfiOrdem++;
    if(
        empty($item->codigo) ||
        empty($item->descricao) ||
        empty($item->codigoServico) ||
        empty($item->valor) ||
        (!($item->cst>=0)) ||
        (!($item->taxaIss>=0)) 
    ){

        // set response code - 400 bad request
        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Item da Nota Fiscal. Dados incompletos."));
        $strData = json_encode($data);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item da Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    
    $itemVenda = new ItemVenda($db);
    $notaFiscalItem = new NotaFiscalServicoItem($db);

    $itemVenda->codigo = $item->codigo;
    if (($idItemVenda = $itemVenda->check()) > 0) 
    {
        $notaFiscalItem->idItemVenda = $idItemVenda;
    }
    else 
    {

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->codigoServico = $item->codigoServico;

        $retorno = $itemVenda->create();
        if(!$retorno[0]){

            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1]));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(Vi01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        else{
            $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
        }
    }

    $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
    $notaFiscalItem->numeroOrdem = $nfiOrdem;
    $notaFiscalItem->cnae = $item->cnae;
    $notaFiscalItem->unidade = "UN";
    if (empty($item->quantidade)) $item->quantidade = 1;
    $notaFiscalItem->quantidade = floatval($item->quantidade);
    $notaFiscalItem->valorUnitario = floatval($item->valor);
    $notaFiscalItem->valorTotal = (floatval($item->valor)*floatval($item->quantidade));
    $notaFiscalItem->cstIss = $item->cst;
    $notaFiscalItem->valorBCIss = $notaFiscalItem->valorTotal;
    $notaFiscalItem->taxaIss = $item->taxaIss;
    $notaFiscalItem->valorIss = ($item->valor*$item->quantidade)*($item->taxaIss/100);

    $totalItens += floatval($notaFiscalItem->valorTotal);

    $retorno = $notaFiscalItem->create();
    if(!$retorno[0]){

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Nota Fiscal.(NFi01)", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Nota Fiscal.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    else{

        $descricaoServicoUnico .= $item->descricao." | ";
        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $arrayItemNF[] = $notaFiscalItem;
    }
}
if (number_format($totalItens,2,'.','') != number_format($notaFiscal->valorTotal,2,'.','')) {

    $db->rollBack();
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal.(NFi02)", 
                           "erro" => "Valor dos itens não fecha com Valor Total da Nota. (".number_format($totalItens,2,'.','')." <> ".number_format($notaFiscal->valorTotal,2,'.','')." )"));
    exit;
}
$descricaoServicoUnico = rtrim($descricaoServicoUnico, " | ");

// se houve problema na inclusão dos itens
if (count($arrayItemNF) == 0) {

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Erro na inclusão dos Itens da Nota Fiscal."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na inclusão dos Itens da Nota Fiscal. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
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

    $autorizacaoChave = new AutorizacaoChave($db);
    $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;
    $stmt = $autorizacaoChave->buscaChave();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $aAutoChave[$row['chave']] = $row['valor'];
    }
    if ( !isset($aAutoChave["optanteSN"]) ||
         !isset($aAutoChave["incentivoFiscal"]) ) {

         $db->rollBack();
         http_response_code(400);
         echo json_encode(array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. Dados de Autorização incompletos."));
         exit;
    };


    include_once '../shared/utilities.php';
    $utilities = new Utilities();

    $municipioEmitente = new Municipio($db);
    $municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
    $municipioEmitente->readUFMunicipio();
    $municipioEmitente->buscaMunicipioSIAFI($emitente->codigoMunicipio);

    $municipioTomador = new Municipio($db);
    $municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
    $municipioTomador->readUFMunicipio();
    $municipioTomador->buscaMunicipioSIAFI($tomador->codigoMunicipio);
    
    // montar xml nfse
    //			
    $xml = new XMLWriter;
    $xml->openMemory();

    // Inicia o cabeçalho do documento XML
	$xml->startElement("ns1:ReqEnvioLoteRPS");
	$xml->writeAttribute("xmlns:ns1", "http://localhost:8080/WsNFe2/lote");
	$xml->writeAttribute("xmlns:tipos", "http://localhost:8080/WsNFe2/tp");
	$xml->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$xml->writeAttribute("xsi:schemaLocation", "http://localhost:8080/WsNFe2/lote http://localhost:8080/WsNFe2/xsd/ReqEnvioLoteRPS.xsd");

	$xml->startElement("Cabecalho");
		$xml->writeElement("CodCidade", $municipioEmitente->codigoSIAFI); // 0921 = São Luís/MA
		$xml->writeElement("CPFCNPJRemetente", $emitente->documento);
		$xml->writeElement("RazaoSocialRemetente", limpaCaractNFe(retiraAcentos($nmRazSocialEmp)));
		$xml->writeElement("transacao", "true");
		$xml->writeElement("dtInicio", $notaFiscalServico->dataEmissao);
		$xml->writeElement("dtFim", $notaFiscalServico->dataEmissao);
		$xml->writeElement("QtdRPS", 1);
		$xml->writeElement("ValorTotalServicos", number_format($vlTotServ,2,'.','')); // ????????
		$xml->writeElement("ValorTotalDeducoes", 0.00);
		$xml->writeElement("Versao", 1);
		$xml->writeElement("MetodoEnvio", "WS");
	$xml->endElement(); // Cabecalho

	$xml->startElement("Lote");
	$xml->writeAttribute("Id", 'lote:'.$notaFiscalServico->idNotaFiscal); // ????????
		$xml->startElement("RPS");
		$xml->writeAttribute("Id", $notaFiscalServico->idNotaFiscal);
		//
			$concatRPS = str_pad($emitente->cmc,11,'0',STR_PAD_LEFT).'NF   '.str_pad($notaFiscalServico->idNotaFiscal,12,'0',STR_PAD_LEFT).
                         str_replace('-','',$notaFiscalServico->dataEmissao).'T NS'.
                         str_pad(number_format(($vlTotServ*100),0,'',''),15,'0',STR_PAD_LEFT).
                         str_pad('0',15,'0',STR_PAD_LEFT).str_pad($notaFiscalItem->cnae,10,'0',STR_PAD_LEFT).
                         str_pad($tomador->documento,14,'0',STR_PAD_LEFT);
			$hashRPS = sha1($concatRPS);
			//
			$xml->writeElement("Assinatura", $hashRPS);
			$xml->writeElement("InscricaoMunicipalPrestador", $emitente->cmc);
			$xml->writeElement("RazaoSocialPrestador", trim($utilities->limpaEspeciais($emitente->nome)));
			$xml->writeElement("TipoRPS", "RPS");
			$xml->writeElement("SerieRPS", "NF");
			$xml->writeElement("NumeroRPS", $notaFiscalServico->idNotaFiscal);
			$hrEm = date('H:i:s');
			$xml->writeElement("DataEmissaoRPS", $notaFiscalServico->dataEmissao.'T'.$hrEm);
			$xml->writeElement("SituacaoRPS", "N");
			$xml->writeElement("SeriePrestacao", "99");
			// tomador
//			$xml->writeElement("InscricaoMunicipalTomador", str_pad($nuInscrMunicDest,8,'0',STR_PAD_LEFT));
			$xml->writeElement("CPFCNPJTomador", $tomador->documento);
			$xml->writeElement("RazaoSocialTomador", trim($utilities->limpaEspeciais($tomador->nome)));
			$xml->writeElement("TipoLogradouroTomador", ""); // informação não disponível
			$xml->writeElement("LogradouroTomador", trim($utilities->limpaEspeciais($tomador->logradouro)));
			$xml->writeElement("NumeroEnderecoTomador", $tomador->numero);
			if($tomador->complemento > '')
				$xml->writeElement("ComplementoEnderecoTomador", trim($utilities->limpaEspeciais($tomador->complemento)));
			$xml->writeElement("TipoBairroTomador", "Bairro");
			$xml->writeElement("BairroTomador", trim($utilities->limpaEspeciais($tomador->bairro)));
			$xml->writeElement("CidadeTomador", $municipioTomador->codigoSIAFI);
			$xml->writeElement("CidadeTomadorDescricao", trim($utilities->limpaEspeciais($municipioTomador->nome)));
			$xml->writeElement("CEPTomador", $tomador->cep);
			$xml->writeElement("EmailTomador", $tomador->email);
			//
			$xml->writeElement("CodigoAtividade", str_pad($notaFiscalItem->cnae,9,'0',STR_PAD_LEFT));
			$xml->writeElement("AliquotaAtividade", $notaFiscalItem->taxaIss);
			$xml->writeElement("TipoRecolhimento", "R"); // "A" receber | "R"etido na fonte
			$xml->writeElement("MunicipioPrestacao", $municipioEmitente->codigoSIAFI); 
			$xml->writeElement("MunicipioPrestacaoDescricao", $municipioEmitente->nome); 
			$xml->writeElement("Operacao", "A"); // "A"=sem dedução  ??????????
			$xml->writeElement("Tributacao", $notaFiscalItem->cstIss); 
			$xml->writeElement("ValorPIS", 0.00);
			$xml->writeElement("ValorCOFINS", 0.00);
			$xml->writeElement("ValorINSS", 0.00);
			$xml->writeElement("ValorIR", 0.00);
			$xml->writeElement("ValorCSLL", 0.00);
			$xml->writeElement("AliquotaPIS", 0.00);
			$xml->writeElement("AliquotaCOFINS", 0.00);
			$xml->writeElement("AliquotaINSS", 0.00);
			$xml->writeElement("AliquotaIR", 0.00);
			$xml->writeElement("AliquotaCSLL", 0.00);
			$xml->writeElement("DescricaoRPS", $notaFiscalServico->dadosAdicionais);
			$xml->writeElement("DDDPrestador", '');
			$xml->writeElement("TelefonePrestador", '');
			$xml->writeElement("DDDTomador", '');
			$xml->writeElement("TelefoneTomador", '');
			//			
			$xml->startElement("Itens");


            foreach ( $arrayItemNF as $notaFiscalItem ) {

                $xml->startElement("item");
                $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
                if ($notaFiscalItem->observacao > '')
                    $nmProd .= ' - '.$notaFiscalItem->observacao;
                $xml->writeElement("DiscriminacaoServico", limpaCaractNFe(retiraAcentos($nmProd)));
                $xml->writeElement("Quantidade", number_format($notaFiscalItem->quantidade,4,'.',''));
                $xml->writeElement("ValorUnitario", number_format($notaFiscalItem->valorUnitario,4,'.',''));
                $xml->writeElement("ValorTotal", number_format($notaFiscalItem->valorTotal,2,'.',''));
                $xml->writeElement("Tributavel", "S"); // "S"=tributável
                $xml->endElement(); // Item
            }
            
            $xml->endElement(); // Itens
		$xml->endElement(); // RPS
	$xml->endElement(); // Lote
	$xml->endElement(); // ns1
	//	
    //
    $xmlNFe = $xml->outputMemory(true);
    //
    // salva xml rps
    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
    fwrite($arqNFe, $xmlNFe);
    fclose($arqNFe);

    //	
    // cria objeto certificado
    include_once '../comunicacao/comunicaNFSe.php';
    $arraySign = array("sisEmit" => 1, "tpAmb" => $notafiscal->ambiente, "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
    $objNFSe = new ComunicaNFSe($arraySign);
    if ($objNFSe->errStatus){
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível acessar Certificado.", "erro" => $objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível acessar Certificado. Erro=".$objNFSe->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    // assina documento
    $xmlAss = $objNFSe->signXML($xmlNFe, 'Lote');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

}
//
// fecha atualizações
$db->commit();

//
// monta bloco padrão DSF
$xmlEnv = '<?xml version="1.0" encoding="utf-8"?>';
$xmlEnv .= '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
$xmlEnv .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dsf="http://dsfnet.com.br">';
$xmlEnv .= '<soapenv:Body>';
$xmlEnv .= '<dsf:enviarSincrono soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
$xmlEnv .= '<mensagemXml xsi:type="xsd:string"><![CDATA['.$xmlAss.']]></mensagemXml>';
$xmlEnv .= '</dsf:enviarSincrono>';
$xmlEnv .= '</soapenv:Body>';
$xmlEnv .= '</soapenv:Envelope>';

$retEnv = $objNFSe->transmitirNFSeDSF($xmlEnv, $emitente->codigoMunicipio, 'GerarNfse');

$respEnv = $retEnv[0];
$infoRet = $retEnv[1];

if ($infoRet['http_code'] == '200') {

    // se retorna ListaNfse - processou com sucesso
    if(strstr($respEnv,'ListaNfse')) {

        $DomXml=new DOMDocument('1.0', 'utf-8');
        $DomXml->loadXML($respEnv);
        $xmlResp = $DomXml->textContent;
        $msgResp = simplexml_load_string($xmlResp);
        $nuNF = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->Numero;
        $cdVerif = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->CodigoVerificacao;
        $dtProc = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->DataEmissao;
        $dtProc = str_replace(" " , "", $dtProc);
        $dtProc = str_replace("T" , " ", $dtProc);
        $linkNF = (string) $msgResp->ListaNfse->CompNfse->Nfse->InfNfse->OutrasInformacoes;
        //            echo json_encode(array("http_code" => "500", "message" => "Autorização OK.", "erro" => $xmlResp));
        //            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Nota Fiscal homologação emitida."."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
        $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
        $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
        fwrite($arqNFe, $xmlResp);
        fclose($arqNFe);
        $linkXml = "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet;
        //
        $notaFiscal->numero = $nuNF;
        $notaFiscal->chaveNF = $cdVerif;
        $notaFiscal->linkXml = $linkXml;
        $notaFiscal->linkNF = $linkNF;
        $notaFiscal->situacao = "F";
        $notaFiscal->dataProcessamento = $dtProc;
        //
        // update notaFiscal
        $retorno = $notaFiscal->update();
        if(!$retorno[0]) {

            // força update simples
            $notaFiscal->updateSituacao("F");

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Nota Fiscal.(A01)", "erro" => $retorno[1]));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal.(A01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        else {

            // set response code - 201 created
            http_response_code(201);
            echo json_encode(array("http_code" => "201", 
                                    "message" => "Nota Fiscal emitida", 
                                    "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                    "numeroNF" => $notaFiscal->numero,
                                    "xml" => $linkXml,
                                    "pdf" => $linkNF));
            exit;
        }
    }
    else {

//        $notaFiscal->deleteCompletoTransaction();

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
            $msgRet = "Erro no envio da NFSe ! Problemas de comunicação ! ".$cdVerif;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na transmissão da NFSe ! Problemas de comunicação !\n"), 3, "../arquivosNFSe/apiErrors.log");
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
            $msgRet = $codigo.' - '.$msg.' - '.$correcao;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na emissão da NFSe => ".$msgRet."\n"), 3, "../arquivosNFSe/apiErrors.log");
        }
        // erro inesperado
        else {

            $msgRet = $respEnv;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Erro Desconhecido (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
        }

        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !", "resposta" => $msgRet));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
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
        if($retorno[0]){

//            $notaFiscal->deleteCompletoTransaction();

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar a Nota Fiscal. Serviço indisponível."));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal.(A01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }

        http_response_code(503);
        echo json_encode(array("http_code" => "503", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    else {

//        $notaFiscal->deleteCompletoTransaction();

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
            $msgRet = "Erro no envio da NFSe ! Problemas de comunicação ! ".$cdVerif;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na transmissão da NFSe ! Problemas de comunicação !\n"), 3, "../arquivosNFSe/apiErrors.log");
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
            $msgRet = $codigo.' - '.$msg.' - '.$correcao;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na emissão da NFSe => ".$msgRet."\n"), 3, "../arquivosNFSe/apiErrors.log");
        }
        // erro inesperado
        else {

            $msgRet = $respEnv;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Erro Desconhecido (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
        }

        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !", "resposta" => $msgRet));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}

?>