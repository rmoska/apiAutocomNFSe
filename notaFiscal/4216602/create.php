<?php

// Classe para emissão de NFSe PM São José/SC Homologação / Produção

include_once '../objects/notaFiscal.php';
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../objects/municipio.php';
 
$notaFiscal = new NotaFiscal($db);
 
//
if(
    empty($data->documento) ||
    empty($data->idVenda) ||
    empty($data->valorTotal) || 
    ($data->valorTotal <= 0)
){

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
    
// set notaFiscal property values
$notaFiscal->ambiente = $ambiente;
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
    !empty($data->tomador->documento) &&
    !empty($data->tomador->nome) &&
    !empty($data->tomador->logradouro) &&
    !empty($data->tomador->numero) &&
    !empty($data->tomador->bairro) &&
    !empty($data->tomador->cep) &&
    !empty($data->tomador->codigoMunicipio) &&
    !empty($data->tomador->uf) &&
    !empty($data->tomador->email) 
){

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
}
else{

    $db->rollBack();
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Tomador. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
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

$cstPrim = $data->itemServico[0]->cst;
$txIssPrim = $data->itemServico[0]->taxaIss;
foreach ( $data->itemServico as $item )
{
    if (($item->cst <> $cstPrim) || ($item->taxaIss <> $txIssPrim)) {

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Itens da Nota Fiscal devem usar mesmo Situação Tributária e Taxa de ISS.(Vi00)", "erro" => $retorno[1]));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Itens da Nota Fiscal devem usar mesmo Situação Tributária e Taxa de ISS.(Vi00). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;   
    }
}

echo 'cst='.$cstPrim.'='.$txIssPrim;
exit;

//check / create itemVenda
$totalItens = 0;
$nfiOrdem = 0;
foreach ( $data->itemServico as $item )
{
    $nfiOrdem++;
    if(
        !empty($item->codigo) &&
        !empty($item->descricao) &&
        !empty($item->codigoServico) &&
        !empty($item->valor) &&
        !empty($item->cst) &&
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
        if (empty($item->quantidade)) $notaFiscalItem->quantidade = 1;
        else $notaFiscalItem->quantidade = floatval($item->quantidade);
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

            $notaFiscalItem->descricaoItemVenda = $item->descricao;
            $arrayItemNF[] = $notaFiscalItem;
        }
    }
    else{

        // set response code - 400 bad request
        $db->rollBack();
        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Item da Nota Fiscal. Dados incompletos."));
        $strData = json_encode($data);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item da Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}
if (number_format($totalItens,2,'.','') != number_format($notaFiscal->valorTotal,2,'.','')) {

    $db->rollBack();
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal.(NFi02)", 
                           "erro" => "Valor dos itens não fecha com Valor Total da Nota. (".number_format($totalItens,2,'.','')." <> ".number_format($notaFiscal->valorTotal,2,'.','')." )"));
    exit;
}

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

    // montar xml nfse
    $vlTotBC = 0; 
    $vlTotISS = 0; 
    $vlTotServ = 0; 
    foreach ( $arrayItemNF as $notaFiscalItem ) {
        $vlTotServ += $notaFiscalItem->valorTotal;
        $vlTotBC += $notaFiscalItem->valorBCIss; 
        $vlTotISS += $notaFiscalItem->valorIss; 
    }

    include_once '../shared/utilities.php';
    $utilities = new Utilities();
    
    //			
    $xml = new XMLWriter;
    $xml->openMemory();




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
                        $xml->writeElement("ValorServicos", $vlTotServ);
                        $xml->writeElement("ValorIss", $vlTotISS);
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
    foreach ( $arrayItemNF as $notaFiscalItem ) {

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

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas com Certificado. ".$nfse->msg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    $xmlAss = $nfse->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($nfse->errStatus) {

        $db->rollBack();
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. ".$nfse->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
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
        //
        $notaFiscal->numero = $nuNF;
        $notaFiscal->chaveNF = $cdVerif;
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
            //
            // gerar pdf
            include './'.$emitente->codigoMunicipio.'/gerarPdf.php';
            $gerarPdf = new gerarPdf();

            $arqPDF = $gerarPdf->printDanfpse($notaFiscal->idNotaFiscal, $db);

            // set response code - 201 created
            http_response_code(201);
            echo json_encode(array("http_code" => "201", 
                                    "message" => "Nota Fiscal emitida", 
                                    "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                    "numeroNF" => $notaFiscal->numero,
                                    "xml" => "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet,
                                    "pdf" => "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF));
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

                $notaFiscal->deleteCompletoTransaction();

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

            $notaFiscal->deleteCompletoTransaction();

            $msg = $result;
            $dados = json_decode($result);
            if (isset($dados->error)) {

                http_response_code(500);
                echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !!", "resposta" => "(".$dados->error.") ".$dados->error_description));
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !! (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
                exit;
            }
            else {

                $xmlNFRet = simplexml_load_string(trim($result));
                $msgRet = (string) $xmlNFRet->message;
                http_response_code(500);
                echo json_encode(array("http_code" => "500", "message" => "Erro no envio da NFSe !", "resposta" => $msgRet));
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
                exit;
            }
        }
    }

?>