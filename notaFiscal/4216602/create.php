<?php

// Classe para emissão de NFSe PM São José/SC Homologação / Produção

include_once '../objects/notaFiscal.php';
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
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
            $itemVenda->listaServico = $item->codigoServico;

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
                        $xml->writeElement("Aliquota", $txIssPrinc); 
                    $xml->endElement(); // Valores
                    $xml->writeElement("IssRetido", 2); // 2=Não 
                    $xml->writeElement("ItemListaServico", $codServPrinc); //"0402");
                    $xml->writeElement("Discriminacao", $descricaoServicoUnico);
                    $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // 4216602 Município de prestação do serviço
                    $xml->writeElement("ExigibilidadeISS", $cstPrinc); // 3 = isento
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
                        if (strlen(trim($tomador->documento))==11)
                            $xml->writeElement("Cpf", $tomador->documento);
                        else 
                            $xml->writeElement("Cnpj", $tomador->documento);
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // IdentificacaoTomador
                    $xml->writeElement("RazaoSocial", $tomador->nome);
                    $xml->startElement("Endereco");
                        $xml->writeElement("Endereco", $tomador->logradouro);
                        $xml->writeElement("Numero", $tomador->numero);
                        $xml->writeElement("Complemento", $tomador->complemento);
                        $xml->writeElement("Bairro", $tomador->bairro);
                        $xml->writeElement("CodigoMunicipio", $tomador->codigoMunicipio);
                        $xml->writeElement("Uf", $tomador->uf);
                        $xml->writeElement("Cep", $tomador->cep);
                    $xml->endElement(); // Endereco
                    $xml->startElement("Contato");
                        $xml->writeElement("Email", $tomador->email);
                    $xml->endElement(); // Contato
                $xml->endElement(); // Tomador
                $xml->writeElement("RegimeEspecialTributacao", $autorizacao->crt);
                $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1-Sim/2-Não
                $xml->writeElement("IncentivoFiscal", $aAutoChave["incentivoFiscal"]); // 1-Sim/2-Não
            $xml->endElement(); // InfDeclaracaoPrestacaoServico
        $xml->endElement(); // Rps
    $xml->endElement(); // GerarNfseEnvio

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
    $xmlAss = $objNFSe->signXML($xmlNFe, 'InfDeclaracaoPrestacaoServico', 'Rps');
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
// monta bloco padrão Betha
$xmlEnv = '<nfseCabecMsg>';
$xmlEnv .= '<![CDATA[';
$xmlEnv .= '<cabecalho xmlns="http://www.betha.com.br/e-nota-contribuinte-ws" versao="2.02"><versaoDados>2.02</versaoDados></cabecalho>';
$xmlEnv .= ']]>';
$xmlEnv .= '</nfseCabecMsg>';
$xmlEnv .= '<nfseDadosMsg>';
$xmlEnv .= '<![CDATA[';
$xmlEnv .= $xmlAss;
$xmlEnv .= ']]>';
$xmlEnv .= '</nfseDadosMsg>';

$respEnv = $objNFSe->transmitirNFSe('GerarNfse', $xmlEnv, $notaFiscal->ambiente);

// se retorna ListaNfse - processou com sucesso
if(strstr($respEnv,'ListaNfse')){

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

        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => "201", 
                                "message" => "Nota Fiscal emitida", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "numeroNF" => $notaFiscal->numero,
                                "xml" => "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet,
                                "pdf" => $linkNF));
        exit;
    }
}
else {

    $notaFiscal->deleteCompletoTransaction();

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
        $msgRet = $codigo.' - '.$msg.' - '.$correcao;
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro Autorização => ".$msgRet."\n"), 3, "../arquivosNFSe/apiErrors.log");
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

?>