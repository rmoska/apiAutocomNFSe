<?php
// provedor GissOnline - serviço GINFES
// ABRASF 1.0
// 
// São Bernardo do Campo
// 
// 



// Classe para emissão de NFSe PMF Homologação / Produção
//
include_once '../objects/autorizacaoChave.php';
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
    $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível incluir Nota Fiscal.(I01)', $retorno[1]." = ".$strData);
    exit;
}

//check / create itemVenda
$totalItens = 0;
$nfiOrdem = 0;
foreach ( $data->itemServico as $item ) {
    $nfiOrdem++;
    if(
        empty($item->codigo) ||
        empty($item->descricao) ||
        empty($item->cnae) ||
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
        $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível incluir Item da Nota Fiscal. Dados incompletos.', $strData);
        exit;
    }
    
    $itemVenda = new ItemVenda($db);
    $notaFiscalItem = new NotaFiscalServicoItem($db);

    $itemVenda->codigo = $item->codigo;
    if (($idItemVenda = $itemVenda->check()) > 0) {

        $notaFiscalItem->idItemVenda = $idItemVenda;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->codigoServico = $item->codigoServico;

        $itemVenda->updateVar();
    }
    else {

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->codigoServico = $item->codigoServico;
        $retorno = $itemVenda->create();
        if(!$retorno[0]){

            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível incluir Item Venda.(I01)', $retorno[1]." = ".$strData);
            exit;
        }
        else {
            $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
        }
    }

    $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
    $notaFiscalItem->numeroOrdem = $nfiOrdem;
    $notaFiscalItem->codigoServico = $item->codigoServico;
    $notaFiscalItem->unidade = "UN";
    $notaFiscalItem->quantidade = floatval($item->quantidade);
    $notaFiscalItem->valorUnitario = floatval($item->valor);
    $notaFiscalItem->valorTotal = (floatval($item->valor)*floatval($item->quantidade));
    $notaFiscalItem->cstIss = $item->cst;

    $totalItens += floatval($notaFiscalItem->valorTotal);

    $notaFiscalItem->valorBCIss = $notaFiscalItem->valorTotal;
    $notaFiscalItem->taxaIss = $item->taxaIss;
    $notaFiscalItem->valorIss = ($item->valor*$item->quantidade)*($item->taxaIss/100);

    $retorno = $notaFiscalItem->create();
    if(!$retorno[0]){

        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Nota Fiscal.(NFi01)", "erro" => $retorno[1], "codigo" => "A00"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Nota Fiscal.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível incluir Item Nota Fiscal.(I01)', $retorno[1]." = ".$strData);
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
    $logMsg->register('E', 'notaFiscal.createIPM', "Valor dos itens(".number_format($totalItens,2,'.','').") não fecha com Valor Total da Nota(".number_format($notaFiscal->valorTotal,2,'.','').")", $strData);
    exit;
}

// se houve problema na inclusão dos itens
if (count($arrayItemNF) == 0) {

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Erro na inclusão dos Itens da Nota Fiscal.", "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro na inclusão dos Itens da Nota Fiscal. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.createIPM', 'Erro na inclusão dos Itens da Nota Fiscal.', $strData);
    exit;
}
//
// fecha atualizações
$db->commit();
//
// 
// cria e transmite nota fiscal

// buscar dados conexão
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
     !isset($aAutoChave["senhaWeb"]) ||
     !isset($aAutoChave["optanteSN"]) ) {

        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. Dados de Autorização incompletos."));
        exit;
};

include_once '../comunicacao/comunicaNFSe.php';
$arraySign = array("sisEmit" => 2, "tpAmb" => "P", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
$objNFSe = new ComunicaNFSe($arraySign);

// montar xml nfse
$vlTotBC = 0; 
$vlTotISS = 0; 
$vlTotServ = 0; 
$cstIss = '';
$codServ = '';
foreach ( $arrayItemNF as $notaFiscalItem ) {
    if (($codServ != '') && ($notaFiscalItem->codigoServico != $codServ)) {

        http_response_code(400);
        echo json_encode(array("http_code" => "400", "message" => "Não foi possível gerar Nota Fiscal. Itens devem ter um único Código de Serviço."));
        exit;
    } 
    $codServ = $notaFiscalItem->codigoServico;
    $vlTotServ += $notaFiscalItem->valorTotal;
    $vlTotBC += $notaFiscalItem->valorBCIss; 
    $vlTotISS += $notaFiscalItem->valorIss; 
    $cstIss = $notaFiscalItem->cstIss;
}

$municipioEmitente = new Municipio($db);
$municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
$municipioEmitente->buscaMunicipioTOM($emitente->codigoMunicipio);

$municipioTomador = new Municipio($db);
$municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
$municTomadorTOM = $municipioTomador->buscaMunicipioTOM($tomador->codigoMunicipio);

if ($aAutoChave["incentivoCultural"] > '') $idIncCultural = $aAutoChave["incentivoCultural"];
else $idIncCultural = '2'; // Não
$dtEmissao = date("Y-m-d").'T'.date("H:i:s");

$codigoServico = new codigoServico($db);
//
// monta XML
include_once '../shared/utilities.php';
$utilities = new Utilities();
//			
$descServico = $codigoServico->buscaServico('LC116', $codServ);
$descServico = $utilities->limpaEspeciais($descServico);
$descServico = trim($utilities->limpaAcentos($descServico));
//
$xml = new XMLWriter;
$xml->openMemory();
//
// cria XML RPS

// Inicia o cabeçalho do documento XML
$xml->startElement("EnviarLoteRpsEnvio");
$xml->writeAttribute("xmlns", "http://www.ginfes.com.br/servico_enviar_lote_rps_envio_v03.xsd");
    $xml->startElement("LoteRps");
    $xml->writeAttribute("xmlns:tipos", "http://www.ginfes.com.br/tipos_v03.xsd");
    $xml->writeAttribute("id", "001");
        $xml->writeElement("tipos:NumeroLote", 1);
        $xml->writeElement("tipos:Cnpj", $emitente->documento);
        $xml->writeElement("tipos:InscricaoMunicipal", $autorizacao->cmc);
        $xml->writeElement("tipos:QuantidadeRps", 1);
        $xml->startElement("tipos:ListaRps");

            $xml->startElement("tipos:Rps");
                $xml->startElement("tipos:InfRps");
            //    $xml->writeAttribute("Id", $notaFiscal->idNotaFiscal);
                    $xml->startElement("tipos:IdentificacaoRps");
                        $xml->writeElement("tipos:Numero", $notaFiscal->idNotaFiscal); // ????????????
                        $xml->writeElement("tipos:Serie", 1);
                        $xml->writeElement("tipos:Tipo", 1);
                    $xml->endElement(); // IdentificacaoRps
                    $xml->writeElement("tipos:DataEmissao", $dtEmissao);
                    $xml->writeElement("tipos:NaturezaOperacao", $notaFiscalItem->cstIss);
                    $xml->writeElement("tipos:RegimeEspecialTributacao", 6); // 6 = ME/EPP
                    $xml->writeElement("tipos:OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1 = SIM
                    $xml->writeElement("tipos:IncentivadorCultural", $idIncCultural); // 2 = NAO
                    $xml->writeElement("tipos:Status", 1); // 1 = normal

                    $xml->startElement("tipos:Servico");
                        $xml->startElement("tipos:Valores");
                            $xml->writeElement("tipos:ValorServicos", number_format($vlTotServ,2,'.',''));

                            $xml->writeElement("tipos:ValorPis", "0.00");
                            $xml->writeElement("tipos:ValorCofins", "0.00");
                            $xml->writeElement("tipos:ValorCsll", "0.00");
            //                $xml->writeElement("ValorInss", "0.00");
            //                $xml->writeElement("ValorIr", "0.00");
            //                $xml->writeElement("ValorDeducoes", "0.00");
            //                $xml->writeElement("OutrasRetencoes", "0.00");
            //                $xml->writeElement("tipos:DescontoIncondicionado", 0.00);
            //                $xml->writeElement("tipos:DescontoCondicionado", 0.00);
                            $xml->writeElement("tipos:IssRetido", 2); // 1=Sim 2=Não
                            $xml->writeElement("tipos:ValorIss", number_format($notaFiscalItem->valorIss,2,'.',''));
                            $xml->writeElement("tipos:ValorIssRetido", '0.00');
                            $xml->writeElement("tipos:BaseCalculo", number_format($vlTotBC,2,'.',''));
                            $xml->writeElement("tipos:Aliquota", number_format($notaFiscalItem->taxaIss/100,4,'.','')); 
                            $xml->writeElement("tipos:ValorLiquidoNfse", number_format($vlTotServ,2,'.',''));
                        $xml->endElement(); // Valores

                        $xml->writeElement("tipos:ItemListaServico", $notaFiscalItem->codigoServico); 
            //            $xml->writeElement("tipos:CodigoCnae", "");
                        $xml->writeElement("tipos:Discriminacao", $descServico);
                        $xml->writeElement("tipos:CodigoTributacaoMunicipio", '620150101'); //$notaFiscalItem->codigoServico); // Município de prestação do serviço
                        $xml->writeElement("tipos:CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
                    $xml->endElement(); // Serviço

                    $xml->startElement("tipos:Prestador");
                        $xml->writeElement("tipos:Cnpj", $emitente->documento);
                        $xml->writeElement("tipos:InscricaoMunicipal", $autorizacao->cmc);
                    $xml->endElement(); // Prestador


                    $xml->startElement("tipos:Tomador");
                        $xml->startElement("tipos:IdentificacaoTomador");
                            $xml->startElement("tipos:CpfCnpj");
                            if (strlen($tomador->documento)==14) 
                                $xml->writeElement("tipos:Cnpj", $tomador->documento);
                            else
                                $xml->writeElement("tipos:Cpf", $tomador->documento);
                            $xml->endElement(); // CpfCnpj
                        $xml->endElement(); // IdentificacaoTomador
                        $xml->writeElement("tipos:RazaoSocial", $tomador->nome);
                        $xml->startElement("tipos:Endereco");
                            $xml->writeElement("tipos:Endereco", $tomador->logradouro);
                            $xml->writeElement("tipos:Numero", $tomador->numero);
                            $xml->writeElement("tipos:Complemento", $tomador->complemento);
                            $xml->writeElement("tipos:Bairro", $tomador->bairro);
                            $xml->writeElement("tipos:CodigoMunicipio", $tomador->codigoMunicipio);
                            $xml->writeElement("tipos:Uf", $tomador->uf);
                            $xml->writeElement("tipos:Cep", $tomador->cep);
                        $xml->endElement(); // Endereco
                        $xml->startElement("tipos:Contato");
                            $xml->writeElement("tipos:Email", $tomador->email);
                        $xml->endElement(); // Contato
                    $xml->endElement(); // Tomador
                $xml->endElement(); // InfRps
            $xml->endElement(); // Rps

        $xml->endElement(); // ListaRps
    $xml->endElement(); // LoteRps
$xml->endElement(); // EnviarLoteRpsEnvio

$xmlRps = $xml->outputMemory(true);

/*
//
// Inicia o cabeçalho do documento XML
$xml->startElement("EnviarLoteRpsEnvio");
$xml->writeAttribute("xmlns", "http://www.ginfes.com.br/servico_enviar_lote_rps_envio_v03.xsd");
    $xml->startElement("LoteRps");
    $xml->writeAttribute("xmlns:tipos", "http://www.ginfes.com.br/tipos_v03.xsd");
    $xml->writeAttribute("Id", "001");
        $xml->writeElement("tipos:NumeroLote", 1);
        $xml->writeElement("tipos:Cnpj", $emitente->documento);
        $xml->writeElement("tipos:InscricaoMunicipal", $autorizacao->cmc);
        $xml->writeElement("tipos:QuantidadeRps", 1);
        $xml->startElement("tipos:ListaRps");
            $xml->writeRaw($xmlRps); // $xmlAss
        $xml->endElement(); // ListaRps
    $xml->endElement(); // LoteRps
$xml->endElement(); // EnviarLoteRpsEnvio
//
$xmlLote = $xml->outputMemory(true);
*/

$xmlAss = $objNFSe->signXML($xmlRps, 'LoteRps', '');

//error_log(utf8_decode("[".date("Y-m-d H:i:s")."] XMLAss = ".$xmlAss."\n"), 3, "../arquivosNFSe/envNFSe.log");

$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
fwrite($arqNFe, $xmlAss);
fclose($arqNFe);

//
// transmite NFSe
$retEnv = $objNFSe->transmitirNFSeGINFES( $xmlAss, 'EnviarLoteRpsEnvio', $emitente->codigoMunicipio);

$respEnv = $retEnv[0];
$infoRet = $retEnv[1];

error_log(utf8_decode("[".date("Y-m-d H:i:s")."] ".$respEnv." = ".$infoRet."\n"), 3, "../arquivosNFSe/envNFSe.log");


print_r($result);
print_r($info);


if ($infoRet['http_code'] == '200') {

    // se retorna ListaNfse - processou com sucesso
    if(strstr($respEnv,'NovaNfse')){

        $respEnv = str_replace("<s:", "<", $respEnv);
        $respEnv = str_replace("</s:", "</", $respEnv);
        $msgResp = simplexml_load_string($respEnv);

        $idNFSe = $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse;
        $nuNF = (string) $idNFSe->Numero;
        $cdVerif = (string) $idNFSe->CodigoVerificacao;
        $linkNF = (string) $idNFSe->Link;
        $dtEm = (string) $idNFSe->dataEmissao;
        $dtProc = substr($xmlNFRet->dtEm,0,10).' '.substr($xmlNFRet->dtEm,11,8);

        $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
        $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
        $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
        fwrite($arqNFe, $xmlResp);
        fclose($arqNFe);
        $linkXml = "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet;

        $notaFiscal->numero = $nuNF;
        $notaFiscal->chaveNF = $cdVerif;
        $notaFiscal->linkNF = $linkNF;
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

        //erro na comunicacao SOAP
        if(strstr($respEnv,'Fault')){

            $respEnv = str_replace("<s:", "<", $respEnv);
            $respEnv = str_replace("</s:", "</", $respEnv);
            $msgResp = simplexml_load_string($respEnv);

            $codigo = (string) $msgResp->ListaMensagemRetorno->MensagemRetorno->Codigo;
            $msg = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Mensagem);
            $falha = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Fault);
            $cdVerif = $codigo.' - '.$msg.' - '.$falha;
            $cdVerif = "Erro no envio da NFSe ! Problemas de comunicação ! ".$cdVerif;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe de Homologação ! Problemas de comunicação !\n"), 3, "../arquivosNFSe/apiErrors.log");
        }
        //erros de validacao do webservice
        else if(strstr($respEnv,'ListaMensagemRetorno')){

            $respEnv = str_replace("<s:", "<", $respEnv);
            $respEnv = str_replace("</s:", "</", $respEnv);
            $msgResp = simplexml_load_string($respEnv);

            $codigo = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Codigo;
            $msg = (string) utf8_decode($msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Mensagem);
            $correcao = (string) utf8_decode($msgResp->Body->GerarNfseResponse->GerarNfseResult->ListaMensagemRetorno->MensagemRetorno->Correcao);
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

?>