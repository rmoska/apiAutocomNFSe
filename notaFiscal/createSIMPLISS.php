<?php

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
foreach ( $data->itemServico as $item )
{
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
    $notaFiscalItem = new NotaFiscalItem($db);

    $itemVenda->codigo = $item->codigo;
    if (($idItemVenda = $itemVenda->check()) > 0) 
    {
        $notaFiscalItem->idItemVenda = $idItemVenda;

        $itemVenda->descricao = $item->descricao;
        $itemVenda->listaServico = $item->codigoServico;

        $itemVenda->updateVar();
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
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(I01). Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível incluir Item Venda.(I01)', $retorno[1]." = ".$strData);
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
// cria e transmite nota fiscal
else {

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
         !isset($aAutoChave["senhaWeb"]) ) {

         $db->rollBack();
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
    foreach ( $arrayItemNF as $notaFiscalItem ) {
        $vlTotServ += $notaFiscalItem->valorTotal;
        $vlTotBC += $notaFiscalItem->valorBCIss; 
        $vlTotISS += $notaFiscalItem->valorIss; 
    }
    //

    $municipioEmitente = new Municipio($db);
    $municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
    $municipioEmitente->buscaMunicipioTOM($emitente->codigoMunicipio);

    $municipioTomador = new Municipio($db);
    $municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
    $municTomadorTOM = $municipioTomador->buscaMunicipioTOM($tomador->codigoMunicipio);

    include_once '../shared/utilities.php';
    $utilities = new Utilities();
    //			
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("sis:GerarNfse");
        $xml->startElement("sis:GerarNovaNfseEnvio");
            $xml->startElement("nfse:Prestador");
                $xml->writeElement("nfse:Cnpj", $emitente->documento);
                $xml->writeElement("nfse:InscricaoMunicipal", $autorizacao->cmc);
            $xml->endElement(); // Prestador
            $xml->startElement("nfse:InformacaoNfse");
            $xml->writeAttribute("Id", "lote1");
                $xml->writeElement("nfse:NaturezaOperacao", 3); // 3 = isento
                $xml->writeElement("nfse:RegimeEspecialTributacao", 6); // 6 = ME/EPP
                $xml->writeElement("nfse:OptanteSimplesNacional", 1); // 1 = SIM
                $xml->writeElement("nfse:IncentivadorCultural", 2); // 2 = NAO
                $xml->writeElement("nfse:Status", 1); // 1 = normal
                $dtEm = date("Y-m-d");
                $xml->writeElement("nfse:Competencia", $dtEm);
                $xml->startElement("nfse:Servico");
                    $xml->startElement("nfse:Valores");
                        $xml->writeElement("nfse:ValorServicos", 10.00);
                        $xml->writeElement("nfse:ValorDeducoes", 0.00);
                        $xml->writeElement("nfse:ValorPis", 0.00);
                        $xml->writeElement("nfse:ValorCofins", 0.00);
                        $xml->writeElement("nfse:ValorInss", 0.00);
                        $xml->writeElement("nfse:ValorIr", 0.00);
                        $xml->writeElement("nfse:ValorCsll", 0.00);
                        $xml->writeElement("nfse:OutrasRetencoes", 0.00);
                        $xml->writeElement("nfse:IssRetido", 2); 
                        $xml->writeElement("nfse:ValorIss", 0.00);
                        $xml->writeElement("nfse:Aliquota", 0.00); 
                        $xml->writeElement("nfse:BaseCalculo", 10.00);
                        $xml->writeElement("nfse:ValorLiquidoNfse", 10.00);
                        $xml->writeElement("nfse:DescontoIncondicionado", 0.00);
                        $xml->writeElement("nfse:DescontoCondicionado", 0.00);
                    $xml->endElement(); // Valores
/*
                    $xml->writeElement("nfse:ItemListaServico", "4.01"); //$aAutoChave["codigoServico"]); 
//                    $xml->writeElement("CodigoCnae", "6190699");
//                    $xml->writeElement("CodigoTributacaoMunicipio", "7.10"); // 4216602 Município de prestação do serviço
                    $xml->writeElement("nfse:Discriminacao", "Teste homologacao");
                    $xml->writeElement("nfse:CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
*/
                    $xml->writeElement("nfse:ItemListaServico", "4.02"); //$aAutoChave["codigoServico"]); 
//                    $xml->writeElement("CodigoCnae", "6190699");
//                    $xml->writeElement("CodigoTributacaoMunicipio", "7.10"); // 4216602 Município de prestação do serviço
                    $xml->writeElement("nfse:Discriminacao", "Teste homologacao");
                    $xml->writeElement("nfse:CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
                    
                    $xml->startElement("nfse:ItensServico");
                        $xml->writeElement("nfse:Descricao", "Consulta clinica");
                        $xml->writeElement("nfse:Quantidade", 1.00);
                        $xml->writeElement("nfse:ValorUnitario", 5.00);
                        $xml->writeElement("nfse:IssTributavel", 1);
                    $xml->endElement(); // ItensServico

                    $xml->startElement("nfse:ItensServico");
                        $xml->writeElement("nfse:Descricao", "Procedimento");
                        $xml->writeElement("nfse:Quantidade", 1.00);
                        $xml->writeElement("nfse:ValorUnitario", 5.00);
                        $xml->writeElement("nfse:IssTributavel", 1);
                    $xml->endElement(); // ItensServico

                    $xml->endElement(); // Servico

                $xml->startElement("nfse:Tomador");
                    $xml->startElement("nfse:IdentificacaoTomador");
                        $xml->startElement("nfse:CpfCnpj");
                            $xml->writeElement("nfse:Cpf", "03118290072");
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // IdentificacaoTomador
                    $xml->writeElement("nfse:RazaoSocial", "Tomador Teste");
                    $xml->startElement("nfse:Endereco");
                        $xml->writeElement("nfse:Endereco", "Rua Marechal Guilherme");
                        $xml->writeElement("nfse:Numero", "1475");
                        $xml->writeElement("nfse:Complemento", "sala 804");
                        $xml->writeElement("nfse:Bairro", "Estreito");
                        $xml->writeElement("nfse:CodigoMunicipio", "4205407");
                        $xml->writeElement("nfse:Uf", "SC");
                        $xml->writeElement("nfse:Cep", "88070700");
                    $xml->endElement(); // Endereco
                    $xml->startElement("nfse:Contato");
                        $xml->writeElement("nfse:Telefone", "4833330891");
                        $xml->writeElement("nfse:Email", "rodrigo@autocominformatica.com.br");
                    $xml->endElement(); // Contato
                $xml->endElement(); // Tomador
            $xml->endElement(); // InformacaoNfse
        $xml->endElement(); // GerarNovaNfseEnvio
        
        $xml->startElement("sis:pParam");
            $xml->writeElement("sis1:P1", $aAutoChave["login"]); 
            $xml->writeElement("sis1:P2", $aAutoChave["senhaWeb"]); 
        $xml->endElement(); // pParam
    $xml->endElement(); // GerarNfseEnvio
    //
    $xmlNFe = $xml->outputMemory(true);
    //
    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFSe = "../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml";
    $arqNFe = fopen($arqNFSe,"wt");
    fwrite($arqNFe, $xmlNFe);
    fclose($arqNFe);
    //	
}
//
// fecha atualizações
$db->commit();
//
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

$result = $retEnv[0];
//print_r($result);
$info = $retEnv[1];
//print_r($info);

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
            
                //
                // update notaFiscal
                $retorno = $notaFiscal->update();

                // set response code - 201 created
                http_response_code(201);
                echo json_encode(array("http_code" => "201", 
                                        "message" => "Nota Fiscal emitida", 
                                        "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                        "numeroNF" => $notaFiscal->numero,
                                        "xml" => $linkXml,
                                        "pdf" => $linkPDF));
//                $logMsg->register('S', 'notaFiscal.createIPM', 'Nota Fiscal emitida', $strData);
                exit;
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

    http_response_code(401);
    echo json_encode(array("http_code" => "401", 
                           "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                           "message" => "Erro no envio da NFSe !", 
                           "resposta" => $cdVerif, 
                           "codigo" => $codMsg));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! idNotaFiscal =".$notaFiscal->idNotaFiscal."  (".$cdVerif.") ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.createIPM', 'Erro no envio da NFPSe ! ('.$cdVerif.') ', $strData);
    exit;
}
else { // http_code <> 200

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
            $logMsg->register('E', 'notaFiscal.createIPM', 'Não foi possível atualizar Nota Fiscal. Serviço indisponível.', $retorno[1]);
            exit;
        }

        http_response_code(503);
        echo json_encode(array("http_code" => "503", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !",
                                "codigo" => "P05"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido). idNotaFiscal=".$notaFiscal->idNotaFiscal."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.createIPM', 'Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).', 'idNotaFiscal='.$notaFiscal->idNotaFiscal);
        exit;
    }
    else {

        //$notaFiscal->deleteCompletoTransaction();
        //$notaFiscal->updateSituacao("E");

        if ($xmlNFRet = @simplexml_load_string($result))
            $msgRet = (string)$xmlNFRet->mensagem->codigo;
        else 
            $msgRet = utf8_decode($result);
        
        $codMsg = "P00"; // $utilities->codificaMsg($msgRet);
        if ($codMsg=='P05')
            $notaFiscal->situacao = 'T';
        else
            $notaFiscal->situacao = 'E';
        $notaFiscal->update();

        http_response_code(401);
        echo json_encode(array("http_code" => "401", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe !", 
                                "resposta" => $msgRet, 
                                "codigo" => $codMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$msgRet.") ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.createIPM', 'Erro no envio da NFPSe ! ('.$msgRet.') ', $strData);
        exit;
    }
}

?>