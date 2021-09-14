<?php

/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoFiscal : 1=sim 2=nao
 */
if( empty($data->idEmitente) ||
    empty($data->documento) ||
    empty($data->cmc) ||
    empty($data->crt) ||
    empty($data->certificado) ||
    empty($data->senha) ||
    empty($data->optanteSN) ||
    empty($data->codigoServico) ) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

include_once '../objects/autorizacao.php';
 
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $data->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio; 
if ($autorizacao->check() == 0) {
    $autorizacao->crt = $data->crt;
    $autorizacao->cmc = $data->cmc;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $autorizacao->mensagemnf = $data->mensagemNF;
    $retorno = $autorizacao->create($emitente->documento);
}
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $autorizacao->crt = $data->crt;
    $autorizacao->cmc = $data->cmc;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $autorizacao->mensagemnf = $data->mensagemNF;
    $retorno = $autorizacao->update($emitente->documento);
}

if($retorno[0]){

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
	$dataValidade = new DateTime(date('Y-m-d'));
	$dataValidade->add(new DateInterval('P'.$validade.'D'));
	$autorizacao->dataValidade = $dataValidade->format('Y-m-d');

	include_once '../shared/utilities.php';
	$utilities = new Utilities($db);
	include_once '../objects/municipio.php';

	$municipioEmitente = new Municipio($db);
    $municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
    $municipioEmitente->readUFMunicipio();
    $municipioEmitente->buscaMunicipioSIAFI($emitente->codigoMunicipio);

    //
    // emite nota de teste
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
	$xml->startElement("ns1:ReqEnvioLoteRPS");
	$xml->writeAttribute("xmlns:ns1", "http://localhost:8080/WsNFe2/lote");
	$xml->writeAttribute("xmlns:tipos", "http://localhost:8080/WsNFe2/tp");
	$xml->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$xml->writeAttribute("xsi:schemaLocation", "http://localhost:8080/WsNFe2/lote http://localhost:8080/WsNFe2/xsd/ReqEnvioLoteRPS.xsd");

	$xml->startElement("Cabecalho");
		$xml->writeElement("CodCidade", $municipioEmitente->codigoSIAFI); // 0921 = São Luís/MA
		$xml->writeElement("CPFCNPJRemetente", $emitente->documento);
		$xml->writeElement("RazaoSocialRemetente", trim($utilities->limpaEspeciais($emitente->nome)));
		$xml->writeElement("transacao", "true");
		$xml->writeElement("dtInicio", date('Y-m-d')); // $notaFiscalServico->dataEmissao);
		$xml->writeElement("dtFim", date('Y-m-d')); //$notaFiscalServico->dataEmissao);
		$xml->writeElement("QtdRPS", 1);
		$xml->writeElement("ValorTotalServicos", 1.00); //number_format($vlTotServ,2,'.','')); // ????????
		$xml->writeElement("ValorTotalDeducoes", 0.00);
		$xml->writeElement("Versao", 1);
		$xml->writeElement("MetodoEnvio", "WS");
	$xml->endElement(); // Cabecalho

	$xml->startElement("Lote");
	$xml->writeAttribute("Id", 'lote:1'); //$notaFiscalServico->idNotaFiscal); // ????????
		$xml->startElement("RPS");
		$xml->writeAttribute("Id", 1); //$notaFiscalServico->idNotaFiscal);
		//
		/*
			$concatRPS = str_pad($emitente->cmc,11,'0',STR_PAD_LEFT).'NF   '.str_pad($notaFiscalServico->idNotaFiscal,12,'0',STR_PAD_LEFT).
						str_replace('-','',$notaFiscalServico->dataEmissao).'T NS'.
						str_pad(number_format(($vlTotServ*100),0,'',''),15,'0',STR_PAD_LEFT).
						str_pad('0',15,'0',STR_PAD_LEFT).str_pad($notaFiscalItem->cnae,10,'0',STR_PAD_LEFT).
						str_pad($tomador->documento,14,'0',STR_PAD_LEFT);
		*/
			$concatRPS = str_pad($emitente->cmc,11,'0',STR_PAD_LEFT).'NF   '.str_pad(1,12,'0',STR_PAD_LEFT).
                         date('Ymd').'T NS'.
                         str_pad(number_format((100),0,'',''),15,'0',STR_PAD_LEFT).
                         str_pad('0',15,'0',STR_PAD_LEFT).str_pad($autorizacao->cnae,10,'0',STR_PAD_LEFT).
                         str_pad($tomador->documento,14,'0',STR_PAD_LEFT);
			$hashRPS = sha1($concatRPS);
			//
			$xml->writeElement("Assinatura", $hashRPS);
			$xml->writeElement("InscricaoMunicipalPrestador", str_pad($emitente->cmc,11,'0',STR_PAD_LEFT));
			$xml->writeElement("RazaoSocialPrestador", trim($utilities->limpaEspeciais($emitente->nome)));
			$xml->writeElement("TipoRPS", "RPS");
			$xml->writeElement("SerieRPS", "NF");
			$xml->writeElement("NumeroRPS", 1); // $notaFiscalServico->idNotaFiscal);
			$xml->writeElement("DataEmissaoRPS", date('Y-m-d').'T'.date('H:i:s'));
			$xml->writeElement("SituacaoRPS", "N");
			$xml->writeElement("SeriePrestacao", "99");
			// tomador
			$xml->writeElement("InscricaoMunicipalTomador", '00000000000');
			$xml->writeElement("CPFCNPJTomador", '03118290072'); //$tomador->documento);
			$xml->writeElement("RazaoSocialTomador", 'Tomador Teste API'); //trim($utilities->limpaEspeciais($tomador->nome)));
			$xml->writeElement("TipoLogradouroTomador", ""); // informação não disponível
			$xml->writeElement("LogradouroTomador", 'Rua Marechal Guilherme'); //trim($utilities->limpaEspeciais($tomador->logradouro)));
			$xml->writeElement("NumeroEnderecoTomador", 1); //$tomador->numero);
			$xml->writeElement("TipoBairroTomador", "Bairro");
			$xml->writeElement("BairroTomador", 'Centro'); //trim($utilities->limpaEspeciais($tomador->bairro)));
			$xml->writeElement("CidadeTomador", $municipioEmitente->codigoSIAFI);
			$xml->writeElement("CidadeTomadorDescricao", trim($utilities->limpaEspeciais($municipioEmitente->nome)));
			$xml->writeElement("CEPTomador", $emitente->cep);
			$xml->writeElement("EmailTomador", $emitente->email);
			//
			$xml->writeElement("CodigoAtividade", '008630561'); //str_pad($autorizacao->cnae,9,'0',STR_PAD_LEFT)); // str_pad($notaFiscalItem->cnae,9,'0',STR_PAD_LEFT));
			$xml->writeElement("CodigoServico", '0401'); //str_pad($autorizacao->cnae,5,'0',STR_PAD_LEFT)); // str_pad($notaFiscalItem->cnae,9,'0',STR_PAD_LEFT));
			$xml->writeElement("AliquotaAtividade", "2.0000"); //$notaFiscalItem->taxaIss);
			$xml->writeElement("TipoRecolhimento", "A"); // "A" receber | "R"etido na fonte
			$xml->writeElement("MunicipioPrestacao", $municipioEmitente->codigoSIAFI); 
			$xml->writeElement("MunicipioPrestacaoDescricao", $municipioEmitente->nome); 
			$xml->writeElement("Operacao", "A"); // "A"=sem dedução  ??????????
			$xml->writeElement("Tributacao", 'C'); //$notaFiscalItem->cstIss); 
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
			$xml->writeElement("DescricaoRPS", 'Teste observação'); //$notaFiscalServico->dadosAdicionais);
			$xml->writeElement("DDDPrestador", '');
			$xml->writeElement("TelefonePrestador", '');
			$xml->writeElement("DDDTomador", '');
			$xml->writeElement("TelefoneTomador", '');
			//			
			$xml->startElement("Itens");


//            foreach ( $arrayItemNF as $notaFiscalItem ) {

                $xml->startElement("Item");
                $xml->writeElement("DiscriminacaoServico", 'Serviço Teste'); //limpaCaractNFe(retiraAcentos($nmProd)));
                $xml->writeElement("Quantidade", 1.00); //number_format($notaFiscalItem->quantidade,4,'.',''));
                $xml->writeElement("ValorUnitario", 1.00); //number_format($notaFiscalItem->valorUnitario,4,'.',''));
                $xml->writeElement("ValorTotal", 1.00); //number_format($notaFiscalItem->valorTotal,2,'.',''));
                $xml->writeElement("Tributavel", "S"); // "S"=tributável
                $xml->endElement(); // Item
//            }
            
            $xml->endElement(); // Itens
		$xml->endElement(); // RPS
	$xml->endElement(); // Lote
	$xml->endElement(); // ns1
	//	
    //
    $xmlNFe = $xml->outputMemory(true);

    $xmlAss = $objNFSe->signXML($xmlNFe, 'Lote');
    if ($objNFSe->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    //
    // monta bloco padrão DSF
    $xmlEnv = '<?xml version="1.0" encoding="utf-8"?>';
    $xmlEnv .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                                  xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                                  xmlns:dsf="http://sistemas.semfaz.saoluis.ma.gov.br/WsNFe2/LoteRps.jws">';
    $xmlEnv .= '<soapenv:Body>';
    $xmlEnv .= '<dsf:enviar soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
    $xmlEnv .= '<mensagemXml xsi:type="xsd:string"><![CDATA['.$xmlAss.']]></mensagemXml>';
    $xmlEnv .= '</dsf:enviar>';
    $xmlEnv .= '</soapenv:Body>';
    $xmlEnv .= '</soapenv:Envelope>';


    // salva xml rps
    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
    fwrite($arqNFe, $xmlEnv);
    fclose($arqNFe);


    $retEnv = $objNFSe->transmitirNFSeDSF($xmlEnv, $emitente->codigoMunicipio, 'GerarNfse');

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

	echo '<pre>';
	print_r($retEnv);
	echo '</pre>';

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

	$autorizacao->nfhomologada = $nuNF;
	$autorizacao->update($emitente->documento);

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