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
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos.", "codigo" => "A06"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização. Dados incompletos.', $strData);
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
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $objNFSe->errMsg, "codigo" => "A01"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$objNFSe->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Certificado.', 'Erro='.$objNFSe->errMsg.' Emitente='.$autorizacao->idEmitente);
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
    $date = new DateTime();
    $dtEm = date('d/m/Y');
    $tipoTomador = 'F';
//        if (strlen(trim($tomador->documento))==14)
//            $tipoTomador = 'J';

    $xml->startElement("nfse");
        $xml->writeElement("nfse_teste", "1"); // define ambiente HOMOLOGAÇÃO
        $xml->startElement("nf");
//            $xml->writeElement("data_fato_gerador", $dtEm);
            $xml->writeElement("valor_total", "2,00");
            $xml->writeElement("valor_desconto", "0,00");
            $xml->writeElement("valor_ir", "0,00");
            $xml->writeElement("valor_inss", "0,00");
            $xml->writeElement("valor_contribuicao_social", "0,00");
            $xml->writeElement("valor_rps", "0,00");
            $xml->writeElement("valor_pis", "0,00");
            $xml->writeElement("valor_cofins", "0,00");
            $xml->writeElement("observacao", "Teste de Homologacao");
        $xml->endElement(); // nf
        $xml->startElement("prestador");
            $xml->writeElement("cpfcnpj", $emitente->documento);
            $xml->writeElement("cidade", "8233"); // Palhoça
        $xml->endElement(); // prestador
        $xml->startElement("tomador");
            $xml->writeElement("tipo", $tipoTomador); 
            $xml->writeElement("cpfcnpj", "03118290072");
            $xml->writeElement("ie", "");
            $xml->writeElement("nome_razao_social", "Jose da Silva");
            $xml->writeElement("sobrenome_nome_fantasia", "Jose da Silva");
            $xml->writeElement("logradouro", "Rua 24 de Julho");
            $xml->writeElement("email", "rodrigo@autocominformatica.com.br");
            $xml->writeElement("numero_residencia", "1");
            $xml->writeElement("complemento", "Casa");
            $xml->writeElement("ponto_referencia", "Casa");
            $xml->writeElement("bairro", "Centro");
            $xml->writeElement("cidade", "8233");
            $xml->writeElement("cep", "88130001");
            $xml->writeElement("fone_residencial", "999990000");
        $xml->endElement(); // tomador
        // ITENS
        $xml->startElement("itens");
            $xml->startElement("lista");
                $xml->writeElement("tributa_municipio_prestador", "S");
                $xml->writeElement("codigo_local_prestacao_servico", "8233");
                $xml->writeElement("unidade_codigo", 1);
                $xml->writeElement("unidade_quantidade", "1,00");
                $xml->writeElement("unidade_valor_unitario", "2,00");
                $xml->writeElement("codigo_item_lista_servico", "402"); // LC116
                $xml->writeElement("descritivo", "Servico para Teste de Homologacao");
                $xml->writeElement("aliquota_item_lista_servico", "2,00");
                $xml->writeElement("situacao_tributaria", "0"); // Tributado
                $xml->writeElement("valor_tributavel", "2,00");
                $xml->writeElement("valor_deducao", "0,00");
                $xml->writeElement("valor_issrf", "0,00");
            $xml->endElement(); // lista
        $xml->endElement(); // itens
    $xml->endElement(); // nfse
    //
    $xmlNFe = $xml->outputMemory(true);

    // PALHOÇA não tem assinatura de nfse
/*
    $xmlAss = $objNFSe->signXML($xmlNFe, 'nfse');
    if ($objNFSe->errStatus) {
    
        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
*/
    //    $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
    $arqNFSe = "../arquivosNFSe/".$emitente->documento."/rps/000000-nfse.xml";
    $arqNFe = fopen($arqNFSe,"wt");
    fwrite($arqNFe, $xmlNFe);
    fclose($arqNFe);

    if (function_exists('curl_file_create')) { // php 5.5+
        $cFile = curl_file_create($arqNFSe);
    } else {
        $cFile = '@' . realpath($arqNFSe);
    }
    
//    'cidade' => '8233',
    $params = array(
        'login' => $data->login,
        'senha' => $data->senhaWeb,
        'f1' => $cFile
    );

    $retEnv = $objNFSe->transmitirNFSeIpm( $params );

    $result = $retEnv[0];
    $info = $retEnv[1];

//    echo '1'.$result;
//    print_r($info);
//    echo $info['http_code'];

    $nuNF = 0;
    $cdVerif = '';

    if ($info['http_code'] == '200') {
        //
        if ($xmlNFRet = @simplexml_load_string($result)) {
            $codRet = explode(" ", $xmlNFRet->mensagem->codigo);
            if (intval($codRet[0])==285) { // NFSe válida para emissao (IPM não emite NF homologação, apenas valida XML)
                $nuNF = 1; // seta número para considerar NF emitida
                $cdVerif = 'OK'; //
            }
            else {
                if (is_null($xmlNFRet->mensagem->codigo))
                    $cdVerif = "Erro no envio da NFSe ! (".utf8_decode($result).") - E01";
                else
                    $cdVerif = "Erro no envio da NFSe ! (".utf8_decode($xmlNFRet->mensagem->codigo).") - E02";
                $codMsg = 'P00';
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$cdVerif.")\n"), 3, "../arquivosNFSe/apiErrors.log");
                $logMsg->register('E', 'autorizacao.update', 'Erro no envio da NFPSe !', '('.$cdVerif.')');
            }
        }
        else {

            $cdVerif = "Erro no envio da NFSe ! (".utf8_decode($result).") - E03";
            $codMsg = 'P00';
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$cdVerif.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'autorizacao.update', 'Erro no envio da NFPSe !', '('.$cdVerif.')');
        }
    }
    else {

        if (substr($info['http_code'],0,1) == '5') {

            $cdVerif = "Erro no envio da NFSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido) !";
            $codMsg = 'P05';
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('A', 'autorizacao.update', 'Erro no envio da NFPSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido).', '');
        }
        else {
    
            if ($xmlNFRet = @simplexml_load_string($result)) 
                $cdVerif = "Erro no envio da NFSe ! (".utf8_decode($xmlNFRet->mensagem->codigo).") - E04";
            else 
                $cdVerif = "Erro no envio da NFSe ! (".utf8_decode($result).") - E05";
            $codMsg = 'P00';
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! (".$cdVerif.")\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'autorizacao.update', 'Erro no envio da NFPSe !', '('.$cdVerif.')');
        }
    }

    http_response_code(201);
    echo json_encode(array("http_code" => 201, "message" => "Autorização atualizada", 
                            "validade" => $validade." dias",
                            "nf-homolog" => $nuNF,
                            "verificacao-homolog" => $cdVerif,
                            "codigo" => $codMsg));        
}
else{

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1], "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização.', $strData);
    exit;
}


?>