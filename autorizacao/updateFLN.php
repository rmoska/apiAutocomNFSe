<?php

/**
 * crt = 
 * aedf - obrigatório 16/11/2020
 * cmc
 * senhaWeb
 */
if( empty($data->idEmitente) ||
    empty($data->documento) ||
    empty($data->crt) ||
    empty($data->aedf) ||
    empty($data->cmc) ||
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
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $data->idEmitente;
$autorizacao->codigoMunicipio = $emitente->codigoMunicipio; 
if ($autorizacao->check() == 0) {

    $autorizacao->crt = $data->crt;
    $autorizacao->cnae = $data->cnae;
    $autorizacao->aedf = $data->aedf;
    $autorizacao->cmc = $data->cmc;
    $autorizacao->senhaWeb = $data->senhaWeb;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $autorizacao->mensagemnf = $data->mensagemNF;
    $retorno = $autorizacao->create($emitente->documento);
}
else {

    $autorizacao->readOne(); // carregar idAutorizacao
    $autorizacao->crt = $data->crt;
    $autorizacao->cnae = $data->cnae;
    $autorizacao->aedf = $data->aedf;
    $autorizacao->cmc = $data->cmc;
    $autorizacao->senhaWeb = $data->senhaWeb;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;
    $autorizacao->mensagemnf = $data->mensagemNF;
    $autorizacao->nfhomologada = 0;
    $retorno = $autorizacao->update($emitente->documento);
}
if($retorno[0]){

    $nuNF = 0; // indicador dados não testados ou com erro
    if (!$autorizacao->getToken("P")) { // token passa a ser testado na produção - 16/11/2020

        http_response_code(401);
        echo json_encode(array("http_code" => 401, "message" => "Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado.", "codigo" => "A02"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'autorizacao.update', 'Autorização com dados inválidos (Confira CMC e senha PMF). Token de acesso rejeitado.', 'Emitente='.$autorizacao->idEmitente);
        exit;
    }
    else {

        include_once '../comunicacao/signNFSe.php';
        $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
        $certificado = new SignNFSe($arraySign);
        if ($certificado->errStatus){
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Certificado inválido.", "erro" => $certificado->errMsg, "codigo" => "A01"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Certificado inválido. Erro=".$certificado->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'autorizacao.update', 'Certificado inválido.', 'Erro='.$certificado->errMsg.' Emitente='.$autorizacao->idEmitente);
            exit;
        }
        $validade = $certificado->certDaysToExpire;
        $dataValidade = new DateTime(date('Y-m-d'));
        $dataValidade->add(new DateInterval('P'.$validade.'D'));
        $autorizacao->dataValidade = $dataValidade->format('Y-m-d');
    }

/* função de nota de teste desativada por inconsistências no ambiente homologação (senha diferente, usuário não habilitado)
    //
    // emite nota de teste
    $xml = new XMLWriter;
    $xml->openMemory();
    //
    // Inicia o cabeçalho do documento XML
    $xml->startElement("xmlProcessamentoNfpse");
    $xml->writeElement("identificacao", 1);
    $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter
    $xml->writeElement("numeroAEDF", $nuAEDF);
    $xml->writeElement("numeroSerie", 1);
    $dtEm = date("Y-m-d");
    $xml->writeElement("dataEmissao", $dtEm);
    $xml->writeElement("cfps", "9201");
    $xml->writeElement("baseCalculo", 0.00);
    $xml->writeElement("valorISSQN", 0.00);
    $xml->writeElement("valorTotalServicos", 2.00);
    $xml->writeElement("identificacaoTomador", "03118290072");
    $xml->writeElement("razaoSocialTomador", "Tomador Teste API");
    $xml->writeElement("logradouroTomador", "Rua Marechal Guilherme");
    $xml->writeElement("numeroEnderecoTomador", "1");
    $xml->writeElement("bairroTomador", "Centro");
    $xml->writeElement("codigoMunicipioTomador", $emitente->codigoMunicipio);
    $xml->writeElement("codigoPostalTomador", "88015000");
    $xml->writeElement("ufTomador", "SC");
    $xml->writeElement("emailTomador", "rodrigo@autocominformatica.com.br");
    //		
    // ITENS
    $xml->startElement("itensServico");
        $xml->startElement("itemServico");
        $xml->writeElement("descricaoServico", "Teste de Homologacao");
        $xml->writeElement("idCNAE", trim($autorizacao->cnae));
        $xml->writeElement("cst", "1");
        $xml->writeElement("aliquota", 0.00);
        $xml->writeElement("quantidade", 1.00);
        $xml->writeElement("baseCalculo", 0.00);
        $xml->writeElement("valorUnitario", 2.00);
        $xml->writeElement("valorTotal", 2.00);
        $xml->endElement(); // ItemServico
    $xml->endElement(); // ItensServico
    //
    $xml->endElement(); // xmlNfpse
    //
    $xmlNFe = $xml->outputMemory(true);
    $xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;

    $nuNF = 0; $cdVerif = ''; $codMsg = '';

    $xmlAss = $certificado->signXML($xmlNFe, 'xmlProcessamentoNfpse');
    if ($certificado->errStatus) {

        $cdVerif = "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML.";
        $codMsg = 'A02';
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'autorizacao.update', 'Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML.', $certificado->errMsg);
    }

    if ($cdVerif == '') {

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

        if ($info['http_code'] == '200') {
            //
            $xmlNFRet = simplexml_load_string($result);
            $nuNF = (string) $xmlNFRet->numeroSerie;
            $cdVerif = (string) $xmlNFRet->codigoVerificacao;
        }
        else {

            if (substr($info['http_code'],0,1) == '5') {

                $cdVerif = "Erro no envio da NFSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido) !";
                $codMsg = 'P05';
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
                $logMsg->register('A', 'autorizacao.update', 'Erro no envio da NFPSe ! Problemas no servidor (Indisponível ou Tempo de espera excedido).', '');
            }
            else {

                $msg = $result;
                $dados = json_decode($result);
                if (isset($dados->error)) {

                    $cdVerif = "Erro no envio da NFSe ! (".$dados->error.") ".$dados->error_description;
                    $codMsg = 'P00';
                    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(1) (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
                    $logMsg->register('E', 'autorizacao.update', 'Erro no envio da NFPSe !', '('.$dados->error.') '.$dados->error_description);
                }
                else {

                    include_once '../shared/utilities.php';
                    $utilities = new Utilities();

                    $xmlNFRet = simplexml_load_string(trim($result));
                    $msgRet = (string) $xmlNFRet->message;
                    $codMsg = $utilities->codificaMsg($msgRet);
                    $cdVerif = "Erro no envio da NFSe ! ".$msgRet;
                    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(2) (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
                    $logMsg->register('E', 'autorizacao.update', 'Erro no envio da NFPSe !', $msgRet);
                }
            }
        }
    }
    if ($nuNF > 0) {

        $autorizacao->nfhomologada = $nuNF;
        $autorizacao->update($emitente->documento);
    }
*/

    $nuNF = 1; // indicador dados testados com sucesso (token+certificado)
    $autorizacao->update($emitente->documento);

    http_response_code(201);
    $aRet = array("http_code" => 201, "message" => "Autorização atualizada", 
                    "token" => $autorizacao->token, 
                    "validade" => $validade." dias",
                    "nf-homolog" => $nuNF,
                    "verificacao-homolog" => $cdVerif,
                    "codigo" => $codMsg);
    echo json_encode($aRet);
    $logMsg->register('S', 'autorizacao.update', 'Autorização atualizada.', json_encode($aRet));
}
else{

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1], "codigo" => "A00"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'autorizacao.update', 'Não foi possível incluir Autorização.', $strData);
    exit;
}

?>