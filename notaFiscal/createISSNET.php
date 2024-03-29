<?php
// provedor NotaControl - serviço ISSNET
// ABRASF 1.0
// 
// Ribeirão Preto
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
    $retIss = 'N';
    $notaFiscalItem->valorIssRetido = 0;
    if ($item->retencaoIss == 'S') { // padrão retenção Iss = N
        $retIss = 'S';
        $notaFiscalItem->valorIssRetido = $notaFiscalItem->valorIss;
    }
    $notaFiscalItem->retencaoIss = $retIss;

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
$municipioEmitente->buscaMunicipioSIAFI();

$municipioTomador = new Municipio($db);
$municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
$municTomadorTOM = $municipioTomador->buscaMunicipioSIAFI();

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

/*
//
// busca configuração do provedor 
include_once '../objects/configAcesso.php';
$configAcesso = new configAcesso(); 
$configAcesso->codigoMunicipio = $emitente->codigoMunicipio;
$configAcesso->ambiente = $notafiscal->ambiente;
$configAcesso->readOne();

if ($configAcesso->idConfig > 0) {

    $objNFSe->urlServico = $configAcesso->wsdl;
    $objNFSe->urlNamespace = $configAcesso->namespace;
    $objNFSe->urlAction = $configAcesso->action;    
}
else {

    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal. Configurações do servidor não definidas. Município = ".$emitente->codigomunicipio));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal. Configurações do servidor não definidas. Municipio= ".$emitente->codigomunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
*/

//
$xml = new XMLWriter;
$xml->openMemory();
//
// cria XML RPS

$nuRps = $autorizacao->rps;
if (!($nuRps > 0))
    $nuRps = 0;
$nuRps++;

// Inicia o cabeçalho do documento XML
$xml->startElement("EnviarLoteRpsEnvio");
$xml->writeAttribute("xmlns", "http://www.issnetonline.com.br/webservice/nfd");
    $xml->startElement("LoteRps");
    $xml->writeAttribute("Id", "001");
        $xml->writeElement("NumeroLote", 1);
        $xml->writeElement("Cnpj", $emitente->documento);
        $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
        $xml->writeElement("QuantidadeRps", 1);
        $xml->startElement("ListaRps");

            $xml->startElement("Rps");
                $xml->startElement("InfRps");
            //    $xml->writeAttribute("Id", $notaFiscal->idNotaFiscal);
                    $xml->startElement("IdentificacaoRps");
                        $xml->writeElement("Numero", $nuRps);
                        $xml->writeElement("Serie", $autorizacao->serie);
                        $xml->writeElement("Tipo", 1);
                    $xml->endElement(); // IdentificacaoRps
                    $xml->writeElement("DataEmissao", $dtEmissao);
                    $xml->writeElement("NaturezaOperacao", $notaFiscalItem->cstIss);
                    $xml->writeElement("RegimeEspecialTributacao", 6); // 6 = ME/EPP
                    $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1 = SIM
                    $xml->writeElement("IncentivadorCultural", $idIncCultural); // 2 = NAO
                    $xml->writeElement("Status", 1); // 1 = normal

                    $xml->startElement("Servico");
                        $xml->startElement("Valores");
                            $xml->writeElement("ValorServicos", number_format($vlTotServ,2,'.',''));

            //                $xml->writeElement("ValorPis", "0.00");
            //                $xml->writeElement("ValorCofins", "0.00");
            //                $xml->writeElement("ValorCsll", "0.00");
            //                $xml->writeElement("ValorInss", "0.00");
            //                $xml->writeElement("ValorIr", "0.00");
            //                $xml->writeElement("ValorDeducoes", "0.00");
            //                $xml->writeElement("OutrasRetencoes", "0.00");
            //                $xml->writeElement("DescontoIncondicionado", 0.00);
            //                $xml->writeElement("DescontoCondicionado", 0.00);
                            $retIss = 2; if ($notaFiscalItem->retencaoIss=='S') $retIss = 1;
                            $xml->writeElement("IssRetido", $retIss); // 1=Sim 2=Não
                            $xml->writeElement("ValorIss", number_format($notaFiscalItem->valorIss,2,'.',''));
                            $xml->writeElement("ValorIssRetido", number_format($notaFiscalItem->valorIssRetido,2,'.',''));
                            $xml->writeElement("BaseCalculo", number_format($vlTotBC,2,'.',''));
                            $xml->writeElement("Aliquota", number_format($notaFiscalItem->taxaIss/100,4,'.','')); 
                            $xml->writeElement("ValorLiquidoNfse", number_format($vlTotServ,2,'.',''));
                        $xml->endElement(); // Valores

                        $xml->writeElement("ItemListaServico", $notaFiscalItem->codigoServico); 
            //            $xml->writeElement("CodigoCnae", "");
                        $xml->writeElement("Discriminacao", $descServico);
//                        $xml->writeElement("CodigoTributacaoMunicipio", '620150101'); //$notaFiscalItem->codigoServico); // Município de prestação do serviço
                        $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
                    $xml->endElement(); // Serviço

                    $xml->startElement("Prestador");
                        $xml->writeElement("Cnpj", $emitente->documento);
                        $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
                    $xml->endElement(); // Prestador


                    $xml->startElement("Tomador");
                        $xml->startElement("IdentificacaoTomador");
                            $xml->startElement("CpfCnpj");
                            if (strlen($tomador->documento)==14) 
                                $xml->writeElement("Cnpj", $tomador->documento);
                            else
                                $xml->writeElement("Cpf", $tomador->documento);
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
                $xml->endElement(); // InfRps
            $xml->endElement(); // Rps

        $xml->endElement(); // ListaRps
    $xml->endElement(); // LoteRps
$xml->endElement(); // EnviarLoteRpsEnvio

$xmlRps = $xml->outputMemory(true);

$xmlAss = $objNFSe->signXML($xmlRps, 'LoteRps', '');

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


//print_r($result);
//print_r($info);


if ($infoRet['http_code'] == '200') {

    // se retorna ListaNfse - processou com sucesso
    if(strstr($respEnv,'NovaNfse')){
        //
        // atualiza rps 
        $autorizacao->rps = $nuRps;
        $retorno = $autorizacao->update();
        if (!$retorno[0]) {
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar sequencial RPS. idNF=".$notaFiscal->idNotaFiscal." Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.createGINFES', 'Não foi possível atualizar sequencial RPS. idNF='.$notaFiscal->idNotaFiscal, $retorno[1]);
        }

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
        // atualiza notaFiscal
        $retorno = $notaFiscal->update();
    
        if(!$retorno[0]) {

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar Nota Fiscal.", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Nota Fiscal.', $retorno[1]);
            exit;
        }
        else {
    
            // set response code - 201 created
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

            $respEnv = htmlspecialchars_decode($respEnv, ENT_NOQUOTES);

            $arrRem = array('hom:', 'ns2:', 'ns3:', '&quot;', "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>");
            $arrSub = array('', '', '', "'", '');
            $respEnv = str_replace($arrRem, $arrSub, $respEnv);
            
            $respEnv = substr($respEnv, strpos($respEnv, '<RecepcionarLoteRpsV3Response'));
            $respEnv = substr($respEnv, 0, strpos($respEnv, '</RecepcionarLoteRpsV3Response>')+31);

            $msgResp = simplexml_load_string($respEnv);

            $codigo = (string) $msgResp->return->EnviarLoteRpsResposta->ListaMensagemRetorno->MensagemRetorno->Codigo;
            $msg = (string) $msgResp->return->EnviarLoteRpsResposta->ListaMensagemRetorno->MensagemRetorno->Mensagem;
            $correcao = (string) $msgResp->return->EnviarLoteRpsResposta->ListaMensagemRetorno->MensagemRetorno->Correcao;
            $cdVerif = $codigo.' - '.$msg.' - '.$correcao;
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro Autorização => ".$cdVerif."\n"), 3, "../arquivosNFSe/apiErrors.log");

            $arrOK = array("http_code" => "401", 
                           "message" => "Erro na emissão da Nota Fiscal - ".$cdVerif);
            echo json_encode($arrOK);

        }
        // erro inesperado
        else {

            $cdVerif .= "Erro no envio da NFSe ! Erro Desconhecido !";
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe Homologação !(2) (".$respEnv.")\n"), 3, "../arquivosNFSe/apiErrors.log");
        }
    }
}

?>