<?php

/**
 * crt
 * cnae
 * aedf (AEDFe é autorização para Produção, então aceita branco para testes de Homologação)
 * cmc
 * senhaWeb
 */
if( empty($data->idEmitente) ||
    empty($data->crt) ||
    empty($data->cnae) ||
    empty($data->cmc) ||
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
$autorizacao = new Autorizacao($db);
 
$autorizacao->idEmitente = $data->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio; 
$autorizacao->crt = $data->crt;
$autorizacao->cnae = $data->cnae;
$autorizacao->aedf = $data->aedf;
$autorizacao->cmc = $data->cmc;
$autorizacao->senhaWeb = $data->senhaWeb;
$autorizacao->certificado = $data->certificado;
$autorizacao->senha = $data->senha;

if ($autorizacao->check() == 0)
    $retorno = $autorizacao->create($emitente->documento);
else 
    $retorno = $autorizacao->update($emitente->documento);

if($retorno[0]){

    if (!$autorizacao->getToken("H")){ 

        http_response_code(401);
        echo json_encode(array("http_code" => 401, "message" => "Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado."));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
    else {

        include_once '../comunicacao/signNFSe.php';
        $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
        $certificado = new SignNFSe($arraySign);
        if ($certificado->errStatus){
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $certificado->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$certificado->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        $validade = $certificado->certDaysToExpire;
    }

    //
    // emite nota de teste
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("xmlProcessamentoNfpse");
    $xml->writeElement("bairroTomador", "Centro");
    $xml->writeElement("baseCalculo", 0.00);
    $xml->writeElement("cfps", "9201");
    $xml->writeElement("codigoMunicipioTomador", $emitente->codigoMunicipio);
    $xml->writeElement("codigoPostalTomador", "88015000");
    $dtEm = date("Y-m-d");
    $xml->writeElement("dataEmissao", $dtEm);
    $xml->writeElement("emailTomador", "rodrigo@autocominformatica.com.br");
    $xml->writeElement("identificacao", 1);
    $xml->writeElement("identificacaoTomador", "03118290072");
    //		
    // ITENS
    $xml->startElement("itensServico");
        $xml->startElement("itemServico");
        $xml->writeElement("aliquota", 0.00);
        $xml->writeElement("cst", "13");
        $xml->writeElement("descricaoServico", "Teste de Homologacao");
        $xml->writeElement("idCNAE", trim($autorizacao->cnae));
        $xml->writeElement("quantidade", 1.00);
        $xml->writeElement("baseCalculo", 0.00);
        $xml->writeElement("valorTotal", 2.00);
        $xml->writeElement("valorUnitario", 2.00);
        $xml->endElement(); // ItemServico
    $xml->endElement(); // ItensServico
    //
    $xml->writeElement("logradouroTomador", "Rua Marechal Guilherme");

    $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter
    $xml->writeElement("numeroAEDF", $nuAEDF);
    $xml->writeElement("numeroEnderecoTomador", "1");
    $xml->writeElement("numeroSerie", 1);
    $xml->writeElement("razaoSocialTomador", "Tomador Teste API");
    $xml->writeElement("ufTomador", "SC");
    $xml->writeElement("valorISSQN", 0.00);
    $xml->writeElement("valorTotalServicos", 2.00);
    $xml->endElement(); // xmlNfpse
    //
    $xmlNFe = $xml->outputMemory(true);
    $xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;

    $xmlAss = $certificado->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($certificado->errStatus) {

        http_response_code(401);
        echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$certificado->errMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }

    //
    // transmite NFSe	
    $headers = array( "Content-type: application/xml", "Authorization: Bearer ".$autorizacao->token ); 
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
    curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/processamento/notas/processa");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
    //
    $result = curl_exec($curl);
    $info = curl_getinfo( $curl );

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

    if ($nuNF > 0) {

        $autorizacao->nfhomologada = $nuNF;
        $autorizacao->update($emitente->documento);
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