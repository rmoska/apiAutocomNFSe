<?php
 
/**
 * dados solicitados no cadastro da prefeitura
 * login 
 * senha
 */
if( empty($data->idEmitente) ||
    empty($data->login) || 
    empty($data->senhaWeb) ||
    empty($data->certificado) ||
    empty($data->senha) ) {
 
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}
    
include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
 
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $data->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio;
if ($autorizacao->check() == 0) {

    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->create($emitente->documento);
}
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $retorno = $autorizacao->update($emitente->documento);
}
    
if($retorno[0]){

    $aAutoChave = array("login" => $data->login, "senhaWeb" => $data->senhaWeb);

    $autorizacaoChave = new AutorizacaoChave($db);
    $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;

    foreach($aAutoChave as $chave => $valor) {

        $autorizacaoChave->chave = $chave;
        $autorizacaoChave->valor = $valor;
        $retorno = $autorizacaoChave->update();
    }

    include_once '../comunicacao/comunicaNFSe.php';
    $arraySign = array("sisEmit" => 2, "tpAmb" => "H", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
    $objNFSe = new ComunicaNFSe($arraySign);

    if ($objNFSe->errStatus){
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$objNFSe->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    $validade = $objNFSe->certDaysToExpire;
        
    //
    // emite nota de teste

    //			
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $dtEm = date('d/m/Y');
    $tipoTomador = 'F';
//        if (strlen(trim($tomador->documento))==14)
//            $tipoTomador = 'J';

    $xml->startElement("nfse");
        $xml->writeElement("nfse_teste", "1"); // define ambiente HOMOLOGAÇÃO
        $xml->startElement("nf");
            $xml->writeElement("valor_total", "2,00");
            $xml->writeElement("observacao", "Teste de Homologacao");
            $xml->writeElement("data_fato_gerador", $dtEm);
        $xml->endElement(); // nf
        $xml->startElement("prestador");
            $xml->writeElement("cpfcnpj", $emitente->documento);
            $xml->writeElement("cidade", "8233"); // Palhoça
        $xml->endElement(); // prestador
        $xml->startElement("tomador");
            $xml->writeElement("tipo", $tipoTomador); 
            $xml->writeElement("cpfcnpj", "03118290072");
            $xml->writeElement("email", "rodrigo@autocominformatica.com.br");
        $xml->endElement(); // tomador
        // ITENS
        $xml->startElement("itens");
            $xml->startElement("lista");
                $xml->writeElement("tributa_municipio_prestador", "S");
                $xml->writeElement("codigo_local_prestacao_servico", "8233");
                $xml->writeElement("unidade_codigo", "UN");
                $xml->writeElement("unidade_quantidade", "1,00");
                $xml->writeElement("unidade_valor_unitario", "2,00");
                $xml->writeElement("codigo_item_lista_servico", "0402"); // LC116
                $xml->writeElement("descritivo", "Servico para Teste de Homologacao");
                $xml->writeElement("aliquota_item_lista_servico", "0,00");
                $xml->writeElement("situacao_tributaria", "6"); // Isento
                $xml->writeElement("valor_tributavel", "2,00");
            $xml->endElement(); // lista
        $xml->endElement(); // itens
    $xml->endElement(); // nfse
    //
    $xmlNFe = $xml->outputMemory(true);
    $xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;

    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
    fwrite($arqNFe, $xmlNFe);
    fclose($arqNFe);

    $arqNFSe = "http://www.autocominformatica.com.br/".$dirAPI."/arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml";

    $xmlAss = $objNFSe->signXML($xmlNFe, 'nfse');
    if ($objNFSe->errStatus) {
    
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    $params = "login=".$emitente->documento."&senha=".$autorizacao->senhaWeb."&cidade=8233&fl=".$arqNFSe;

    $retEnv = $objNFSe->transmitirNFSeIpm( $params );

    $respEnv = $retEnv[0];
    $infoRet = $retEnv[1];

    print_r($respEnv);

    print_r($infoRet);
exit;

    $nuNF = 0;
    $cdVerif = '';

    if ($info['http_code'] == '200') {
        //
        $xmlNFRet = simplexml_load_string($result);
        $nuNF = (string) $xmlNFRet->numeroSerie;
        $cdVerif = (string) $xmlNFRet->codigoVerificacao;
    }
    else {

        if (substr($info['http_code'],0,1) == '5') {

            $cdVerif = "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !";
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
        }
        else {
    
            $msg = $result;
            $dados = json_decode($result);
            if (isset($dados->error)) {

                $cdVerif = "Erro no envio da NFSe ! (".$dados->error.") ".$dados->error_description;
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(1) (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            else {

                $xmlNFRet = simplexml_load_string(trim($result));
                $msgRet = (string) $xmlNFRet->message;
                $cdVerif = "Erro no envio da NFSe ! ".$msgRet;
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(2) (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
        }
    }

    http_response_code(201);
    echo json_encode(array("http_code" => 201, "message" => "Autorização atualizada", 
                            "token" => $autorizacao->token, 
                            "validade" => $validade." dias",
                            "nf-homolog" => $nuNF,
                            "verificacao-homolog" => $cdVerif));
}
else{

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1]));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}


?>