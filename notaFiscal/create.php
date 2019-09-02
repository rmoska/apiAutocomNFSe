<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/notaFiscal.php';
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/emitente.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
 
$database = new Database();
$db = $database->getConnection();
 
$notaFiscal = new NotaFiscal($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

// make sure data is not empty
if(
    !empty($data->documento) &&
    !empty($data->idVenda) &&
    !empty($data->valorTotal) 
){
    // check / create tomador
    if(
        !empty($data->tomador->documento) &&
        !empty($data->tomador->nome) &&
        !empty($data->tomador->logradouro) &&
        !empty($data->tomador->numero) &&
        !empty($data->tomador->complemento) &&
        !empty($data->tomador->bairro) &&
        !empty($data->tomador->cep) &&
        !empty($data->tomador->codigoMunicipio) &&
        !empty($data->tomador->uf) &&
        !empty($data->tomador->email) 
    ){

        $tomador = new Tomador($db);

        // set tomador property values
        $tomador->documento = $data->tomador->documento;

        // check tomador
        if (($idTomador = $tomador->check()) > 0) {

            $tomador->idTomador = $idTomador;
            $notaFiscal->idTomador = $idTomador;
            $tomador->readOne();

        }
        // create tomador
        else {

            $tomador->nome = $data->tomador->nome;
            $tomador->logradouro = $data->tomador->logradouro;
            $tomador->numero = $data->tomador->numero;
            $tomador->complemento = $data->tomador->complemento;
            $tomador->bairro = $data->tomador->bairro;
            $tomador->cep = $data->tomador->cep;
            $tomador->codigoMunicipio = $data->tomador->codigoMunicipio;
            $tomador->uf = $data->tomador->uf;
            $tomador->email = $data->tomador->email;

            if($tomador->create()){
                // set notaFiscal
                $notaFiscal->idTomador = $tomador->idTomador;
            }
            else{
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Tomador. Serviço indisponível."));
                exit;
            }
        }
    }

    // check emitente
    $emitente = new Emitente($db);
    $emitente->documento = $data->documento;
    if (($idEmitente = $emitente->check()) > 0) {
        $notaFiscal->idEmitente = $idEmitente;
    }
    else{
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Emitente não cadastrado. Nota Fiscal não emitida."));
        exit;
    }
    $emitente->readOne();

    // set notaFiscal property values
    $notaFiscal->docOrigemTipo = "V";
    $notaFiscal->docOrigemNumero = $data->idVenda;
    $notaFiscal->idEntradaSaida = "S";
    $notaFiscal->situacao = "A";
    $notaFiscal->valorTotal = $data->valorTotal;
    $notaFiscal->dataEmissao = date("Y-m-d");
    $notaFiscal->dadosAdicionais = $data->observacao;

    if ($tomador->uf != 'SC') $cfps = '9203';
    else if ($tomador->codigoMunicipio != '4205407') $cfps = '9202';
    else $cfps = '9201';
    $notaFiscal->cfop = $cfps;

    // create notaFiscal
    if(!$notaFiscal->create()){
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Nota Fiscal. Serviço indisponível. (I01)"));
        exit;
    }

    //check / create itemVenda
    foreach ( $data->itemServico as $item )
    {
        if(
            !empty($item->codigo) &&
            !empty($item->descricao) &&
            !empty($item->cnae) &&
            !empty($item->nbs) &&
            !empty($item->quantidade) &&
            !empty($item->valor) &&
            !empty($item->taxaIss) 
        ){

            $itemVenda = new ItemVenda($db);
            $notaFiscalItem = new NotaFiscalItem($db);

            $itemVenda->codigo = $item->codigo;
            if (($idItemVenda = $itemVenda->check()) > 0) 
            {
                $notaFiscalItem->idItemVenda = $idItemVenda;
            }
            else 
            {

                $notaFiscalItem->descricaoItemVenda = $item->descricao;
                $itemVenda->descricao = $item->descricao;
                $itemVenda->cnae = $item->cnae;
                $itemVenda->ncm = $item->nbs;

                if($itemVenda->create()){
                    // set notaFiscal
                    $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
                }
                else{
                    http_response_code(503);
                    echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Venda. Serviço indisponível."));
                    exit;
                }
            }

            $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
            $notaFiscalItem->cnae = $item->cnae;
            $notaFiscalItem->unidade = "UN";
            $notaFiscalItem->quantidade = $item->quantidade;
            $notaFiscalItem->valorUnitario = $item->valor;
            $notaFiscalItem->valorTotal = ($item->valor*$item->quantidade);
            $notaFiscalItem->cstIss = $item->cst;
            $notaFiscalItem->taxaIss = $item->taxaIss;
            $notaFiscalItem->valorIss = ($item->valor*$item->quantidade)*($item->taxaIss/100);

            if(!$notaFiscalItem->create()){
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Nota Fiscal. Serviço indisponível."));
                exit;
            }
            else{

                $notaFiscalItem->descricaoItemVenda = $item->descricao;
                $arrayItemNF[] = $notaFiscalItem;

            }

        }
    }

    if (count($arrayItemNF) > 0)
    {

        // calcular Imposto Aproximado IBPT
        $notaFiscal->calcImpAprox();

        // buscar token conexão
        $autorizacao = new Autorizacao($db);
        $autorizacao->idEmitente = $notaFiscal->idEmitente;
        $autorizacao->readOne();

        if(!$autorizacao->getToken()){

            http_response_code(503);
            echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Nota Fiscal. Token não disponível."));
            exit;

        }

        // montar xml nfse

		$vlTotBC = 0; 
		$vlTotISS = 0; 
		$vlTotServ = 0; 

        foreach ( $arrayItemNF as $notaFiscalItem ) {
            $vlTotServ += $notaFiscalItem->valorTotal;
			if ($notaFiscalItem->cstIss != '1') {
				$vlTotBC += $notaFiscalItem->valorBCIss; 
				$vlTotISS += $notaFiscalItem->valorIss; 
			}
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
	//			$xml->writeElement("homologacao", 'true');
		$xml->writeElement("identificacao", $notaFiscal->idNotaFiscal);
		$xml->writeElement("identificacaoTomador", $tomador->documento);
//		if($tomador->inscricaoMunicipal > '')
//			$xml->writeElement("inscricaoMunicipalTomador", $tomador->inscricaoMunicipal);
		//		
		// ITENS
		$it = 0;
		$qtItem = count($arrayItemNF);
        $xml->startElement("itensServico");
        foreach ( $arrayItemNF as $notaFiscalItem ) 
        {

			$xml->startElement("itemServico");
			$xml->writeElement("aliquota", number_format(($notaFiscalItem->taxaIss/100),4,'.',''));
			$xml->writeElement("cst", $notaFiscalItem->cstIss);
            //
            
//            $nmProd = trim(limpaCaractNFe(retiraAcentos($notaFiscalItem->descricao)));
            $nmProd = trim($notaFiscalItem->descricaoItemVenda);

			if ($notaFiscalItem->observacao > '')
				$nmProd .= ' - '.$notaFiscalItem->observacao;
			$xml->writeElement("descricaoServico", trim($nmProd));
			//
			$xml->writeElement("idCNAE", trim($notaFiscalItem->cnae));
			$xml->writeElement("quantidade", number_format($notaFiscalItem->quantidade,0,'.',''));
			if ($notaFiscalItem->taxaIss > 0)
				$xml->writeElement("baseCalculo", number_format($notaFiscalItem->valorTotal,2,'.',''));
			else
				$xml->writeElement("baseCalculo", number_format(0,2,'.',''));
			$xml->writeElement("valorTotal", number_format($notaFiscalItem->valorTotal,2,'.',''));
			$xml->writeElement("valorUnitario", number_format($notaFiscalItem->valorUnitario,2,'.',''));
			$xml->endElement(); // ItemServico
		}
		$xml->endElement(); // ItensServico
		//
		$xml->writeElement("logradouroTomador", $tomador->logradouro);

        $nuAEDF = $autorizacao->aedf; 
//        if ($autorizacao->ambiente = 0)
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
		$xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
		//
       	//
        // transmite NFSe	
        $headers = array( "Content-type: application/xml", "Authorization: Bearer ".$autorizacao->token ); 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
//        curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/processamento/notas/processa");
        curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/processamento/notas/processa"); // homologação
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
        //
        $result = curl_exec($curl);
        //
        $info = curl_getinfo( $curl );

        if ($info['http_code'] == '200') 
        {
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
            //
            // gerar pdf


            //
            $notaFiscal->numero = $nuNF;
            $notaFiscal->chaveNF = $cdVerif;
            $notaFiscal->situacao = "F";
            $notaFiscal->dataProcessamento = $dtProc;
            include_once '../notaFiscal/printPdf.php';
//            $notaFiscal->gerarPDF();

            // update notaFiscal
            if(!$notaFiscal->update()){
                http_response_code(503);
                echo json_encode(array("message" => "Não foi possível atualizar a Nota Fiscal. Serviço indisponível."));
                exit;
            }
            else {
                // set response code - 201 created
                http_response_code(201);
                echo json_encode(array("http_code" => "201", 
                                       "message" => "Nota Fiscal emitida", 
                                       "xml" => "http://www.autocominformatica.com.br/apiAutocomNFSe/".$dirXmlRet.$arqXmlRet,
                                       "pdf" => "http://www.autocominformatica.com.br/apiAutocomNFSe/".$dirXmlRet.$arqXmlRet));
                exit;
            }


            //
//            $idNF = $proxNota; 
//            include 'nfse_email_envia.php';
            //


            
        }
        else 
        {
            if (substr($info['http_code'],0,1) == '5') {
                http_response_code(503);
                echo json_encode(array("message" => "Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !"));
                exit;
            }
            else {
                $msg = $result;
                $dados = json_decode($result);
                if (isset($dados->error)) {
                    http_response_code(503);
                    echo json_encode(array("message" => "Erro no envio da NFPSe !(1)", "resposta" => "(".$dados->error.") ".$dados->error_description));
                    exit;
                }
                else {

                    $xmlNFRet = simplexml_load_string(trim($result));
                    $msg = utf8_decode($xmlNFRet->message);
                    http_response_code(503);
                    echo json_encode(array("message" => "Erro no envio da NFPSe !(2)", "resposta" => $result));
                    exit;
                }
            }
            //
            $notaFiscal->situacao = "R";
            $notaFiscal->textoJustificativa = $msg;

            // update notaFiscal
            if(!$notaFiscal->update()){
                http_response_code(503);
                echo json_encode(array("message" => "Não foi possível atualizar a Nota Fiscal. Serviço indisponível."));
                exit;
            }

        }
    



    }
    else{

        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Erro na inclusão dos Itens da Nota Fiscal."));
        exit;

    }

    
}
 
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos."));
}
?>