<?php

// Classe para emissão de NFSe PMF Homologação / Produção
//
include_once '../objects/autorizacaoChave.php';

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
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Tomador. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Tomador.', $retorno[1]);
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
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Tomador.', $retorno[1]);
        exit;
    }
}

// create notaFiscal
$notaFiscal->idEmitente = $emitente->idEmitente;
$retorno = $notaFiscal->create();
if(!$retorno[0]){

    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Nota Fiscal.(I01)", "erro" => $retorno[1], "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Nota Fiscal.(I01)', $retorno[1]);
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
    $notaFiscalItem = new NotaFiscalItem($db);

    $itemVenda->codigo = $item->codigo;
    if (($idItemVenda = $itemVenda->check()) > 0) 
    {
        $notaFiscalItem->idItemVenda = $idItemVenda;

        $itemVenda->descricao = $item->descricao;
        $itemVenda->listaServico = $item->nbs;

        $itemVenda->updateVar();
    }
    else 
    {

        $notaFiscalItem->descricaoItemVenda = $item->descricao;
        $itemVenda->descricao = $item->descricao;
        $itemVenda->listaServico = $item->nbs;

        $retorno = $itemVenda->create();
        if(!$retorno[0]){

            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Item Venda.(I01)', $retorno[1]);
            exit;
        }
        else{
            $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
        }
    }

    $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
    $notaFiscalItem->numeroOrdem = $nfiOrdem;
    $notaFiscalItem->ncm = $item->nbs;
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
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Nota Fiscal.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Item Nota Fiscal.(I01)', $retorno[1]);
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


    $xml->startElement("nfse");

    if ($notaFiscal->ambiente == "H") // HOMOLOGAÇÃO
        $xml->writeElement("nfse_teste", "1"); // define ambiente HOMOLOGAÇÃO

        $xml->startElement("nf");
            $xml->writeElement("valor_total", number_format($vlTotServ,2,',',''));
            $xml->writeElement("valor_desconto", "0,00");
            $xml->writeElement("valor_ir", "0,00");
            $xml->writeElement("valor_inss", "0,00");
            $xml->writeElement("valor_contribuicao_social", "0,00");
            $xml->writeElement("valor_rps", "0,00");
            $xml->writeElement("valor_pis", "0,00");
            $xml->writeElement("valor_cofins", "0,00");
            if (($autorizacao->mensagemnf) || ($notaFiscal->dadosAdicionais>''))
                $xml->writeElement("observacao", $autorizacao->mensagemnf." ".$notaFiscal->dadosAdicionais);
        $xml->endElement(); // nf
        $xml->startElement("prestador");
            $xml->writeElement("cpfcnpj", $emitente->documento);
            $xml->writeElement("cidade", $municipioEmitente->codigoTOM); // Palhoça
        $xml->endElement(); // prestador
        $xml->startElement("tomador");
            if (strlen($tomador->documento)==14) $tipoTomador = 'J'; else $tipoTomador = 'F';
            $xml->writeElement("tipo", $tipoTomador); 
            $xml->writeElement("cpfcnpj", $tomador->documento);
            $xml->writeElement("ie", "");
            $xml->writeElement("nome_razao_social", $tomador->nome);
            $xml->writeElement("sobrenome_nome_fantasia", $tomador->nome);
            $xml->writeElement("logradouro", trim($utilities->limpaEspeciais($tomador->logradouro)));
            $xml->writeElement("email", $tomador->email);
            if ($tomador->numero>0)
                $xml->writeElement("numero_residencia", $tomador->numero);
            if($tomador->complemento > '')
                $xml->writeElement("complemento", $tomador->complemento);
            $xml->writeElement("bairro", $tomador->bairro);
            $xml->writeElement("cidade", $municipioTomador->codigoTOM);
            $xml->writeElement("cep", $tomador->cep);
        $xml->endElement(); // tomador
        // ITENS
        $xml->startElement("itens");

        foreach ( $arrayItemNF as $notaFiscalItem ) {

            $xml->startElement("lista");
                $xml->writeElement("tributa_municipio_prestador", "N");
                $xml->writeElement("codigo_local_prestacao_servico", $municipioEmitente->codigoTOM);
                $xml->writeElement("unidade_codigo", 1);
                $xml->writeElement("unidade_quantidade", number_format($notaFiscalItem->quantidade,0,'.',''));
                $xml->writeElement("unidade_valor_unitario", number_format($notaFiscalItem->valorUnitario,4,'.',''));
                $xml->writeElement("codigo_item_lista_servico", "402"); // LC116
                $nmProd = trim($utilities->limpaEspeciais($notaFiscalItem->descricaoItemVenda));
                if ($notaFiscalItem->observacao > '')
                    $nmProd .= ' - '.$notaFiscalItem->observacao;
                $xml->writeElement("descritivo", trim($nmProd));
                $xml->writeElement("aliquota_item_lista_servico", number_format(($notaFiscalItem->taxaIss/100),4,',',''));
                $xml->writeElement("situacao_tributaria", $notaFiscalItem->cstIss); 
                $xml->writeElement("valor_tributavel", number_format($notaFiscalItem->valorUnitario,4,'.',''));
                $xml->writeElement("valor_deducao", "0,00");
                $xml->writeElement("valor_issrf", "0,00");
            $xml->endElement(); // lista

        }
        $xml->endElement(); // itens
    $xml->endElement(); // nfse
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

$result = $retEnv[0];
$info = $retEnv[1];

if ($info['http_code'] == '200') {

    if ($xmlNFRet = @simplexml_load_string($result)) {

        $codRet = explode(" ", $xmlNFRet->mensagem->codigo);
        if ($notaFiscal->ambiente == "H") { // HOMOLOGAÇÃO
            if (intval($codRet[0])==285) { // NFSe válida para emissao (IPM não emite NF homologação, apenas valida XML)
                $nuNF = 1; // 
                $cdVerif = 'OK'; //
            }
            else {
                $cdVerif = $xmlNFRet->mensagem->codigo;
            }
        } 
        else {
            if (intval($codRet[0]) == 1) { // sucesso

                $nuNF = $xmlNFRet->numero_nfse;
                $cdVerif = $xmlNFRet->cod_verificador_autenticidade;
                $dtNF = $xmlNFRet->data_nfse;
                $hrNF = $xmlNFRet->hora_nfse;
                $dtProc = substr($dtNF,6,4).'-'.substr($dtNF,3,2).'-'.substr($dtNF,0,2).' '.substr($hrNF,6,2).':'.substr($hrNF,3,2).':'.substr($hrNF,0,2);
                $linkPDF = $xmlNFRet->link_nfse;
                $xmlNF = $xmlNFRet->codigo_html;
                $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
                $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
                $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
                fwrite($arqNFe, $xmlNF);
                fclose($arqNFe);
                $linkXml = "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet;

                $notaFiscal->numero = $nuNF;
                $notaFiscal->chaveNF = $cdVerif;
                $notaFiscal->linkXml = $linkXml;
                $notaFiscal->linkNF = $linkPDF;
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
            else { // resposta <> 1
                $codMsg = "P00a"; // $utilities->codificaMsgIPM($msgRet);
                $cdVerif = utf8_decode($xmlNFRet->mensagem->codigo);
            }
        }
    } 
    else { // retorno não é xml (acontece com IPM para login errado: "Não foi encontrado na tb.dcarq.unico a cidade(codmun) do Usuário:")
        $codMsg = "P00b"; // $utilities->codificaMsgIPM($msgRet);
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
    $logMsg->register('E', 'notaFiscal.create', 'Erro no envio da NFPSe ! ('.$cdVerif.') ', $strData);
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
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Nota Fiscal. Serviço indisponível.', $retorno[1]);
            exit;
        }

        http_response_code(503);
        echo json_encode(array("http_code" => "503", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !",
                                "codigo" => "P05"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido). idNotaFiscal=".$notaFiscal->idNotaFiscal."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).', 'idNotaFiscal='.$notaFiscal->idNotaFiscal);
        exit;
    }
    else {

        //$notaFiscal->deleteCompletoTransaction();
        //$notaFiscal->updateSituacao("E");

        if ($xmlNFRet = @simplexml_load_string($result))
            $msgRet = (string)$xmlNFRet->mensagem->codigo;
        else 
            $msgRet = utf8_decode($result);
        
        $codMsg = "P00c"; // $utilities->codificaMsg($msgRet);
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
        $logMsg->register('E', 'notaFiscal.create', 'Erro no envio da NFPSe ! ('.$msgRet.') ', $strData);
        exit;
    }
}

?>