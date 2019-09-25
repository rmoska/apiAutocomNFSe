<?php
 
// include database and object files
include_once '../objects/autorizacao.php';
 
// prepare emitente object
$autorizacao = new Autorizacao($db);
 
//    !empty($data->aedf) // AEDFe é autorização para Produção, então aceita branco para testes de Homologação
// make sure data is not empty
if(
    !empty($data->idEmitente) &&
    !empty($data->crt) &&
    !empty($data->certificado) &&
    !empty($data->senha)
){
    // set autorizacao property values
    $autorizacao->idEmitente = $data->idEmitente;
    $autorizacao->crt = $data->crt;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;

    if ($autorizacao->check() == 0)
        $retorno = $autorizacao->create($emitente->documento);
    else 
        $retorno = $autorizacao->update($emitente->documento);
 
    if($retorno[0]){

        include_once '../comunicacao/comunicaNFSe.php';
        $arraySign = array("sisEmit" => 0, "tpAmb" => "H", "cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
        $objNFSe = new ComunicaNFSe($arraySign);
        if ($objNFSe->errStatus){
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $certificado->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$certificado->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
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
//        $xml->startElement("GerarNfseEnvio");
//        $xml->writeAttribute("xmlns", "http://www.betha.com.br/e-nota-contribuinte-test-ws");
            $xml->startElement("Rps");
                $xml->startElement("InfDeclaracaoPrestacaoServico");
                $xml->writeAttribute("Id", "lote1");
                    $dtEm = date("Y-m-d");
                    $xml->writeElement("Competencia", $dtEm);
                    $xml->startElement("Servico");
                        $xml->startElement("Valores");
                            $xml->writeElement("ValorServicos", 10.00);
                        $xml->endElement(); // Valores
                        $xml->writeElement("IssRetido", 2);
                        $xml->writeElement("ItemListaServico", "0702");
                        $xml->writeElement("Discriminacao", "Programacao");
                        $xml->writeElement("CodigoMunicipio", 0); // 4216602
                        $xml->writeElement("ExigibilidadeISS", 1);
                        $xml->writeElement("MunicipioIncidencia", 0); // 4216602
                    $xml->endElement(); // Servico
                    $xml->startElement("Prestador");
                        $xml->startElement("CpfCnpj");
                            $xml->writeElement("Cnpj", 80449374000128);
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // Prestador
                    $xml->writeElement("OptanteSimplesNacional", 2);
                    $xml->writeElement("IncentivoFiscal", 2);
                $xml->endElement(); // InfDeclaracaoPrestacaoServico
            $xml->endElement(); // Rps
//        $xml->endElement(); // GerarNfseEnvio


        //
        $xmlNFe = $xml->outputMemory(true);

        $xmlAss = $objNFSe->signXML($xmlNFe, 'InfDeclaracaoPrestacaoServico');
        if ($objNFSe->errStatus) {
    
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }

        $xmlAss = preg_replace("/<\?xml.*\?>/", "", $xmlAss);
        $xmlAss = '<?xml version="1.0" encoding="utf-8"?><GerarNfseEnvio xmlns="http://www.betha.com.br/e-nota-contribuinte-ws">'.$xmlAss.'</GerarNfseEnvio>';

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

        $respEnv = $objNFSe->gerarNFSe($xmlEnv, "H");
echo "<pre>";
        print_r($objResp);

        $objResp = simplexml_load_string($respEnv);

        print_r($objResp);
        echo "<pre/>";

        //erro na comunicacao SOAP
        if(strstr($respEnv,'Fault')){

            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($respEnv);
            $error_msg='';
            foreach ($DomFaultXml->getElementsByTagName('faultstring') as $key => $value) {
                $error_msg.=$value->nodeValue.'<br/>';
            }

            //retornamos false indicando o erro e as mensagens de erro
//            return array(false,$error_msg);
            echo json_encode(array("http_code" => "500", "message" => "Erro ! Não foi possível incluir Autorização.", "erro" => $error_msg));
        }
        //erros de validacao do webservice
        if(strstr($respEnv,'Correcao')){
            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($respEnv);


            $error_msg='';
            foreach ($DomXml->getElementsByTagName('Correcao') as $key => $value) {

                $error_msg.=$value->nodeValue.'<br/>';
            }

            //retornamos false indicando o erro e as mensagens de erro
//            return array(false,$error_msg);
            echo json_encode(array("http_code" => "500", "message" => "Aviso ! Não foi possível incluir Autorização.", "erro" => $error_msg));
        }
        //se retornar o protocolo, o envio funcionou corretamente
        if(strstr($respEnv,'Protocolo')){
            //retornamos false indicando o erro e as mensagens de erro
            //echo htmlentities($respEnv);exit();
//            return array(true,$respEnv);
            echo json_encode(array("http_code" => "500", "message" => "Autorização OK.", "erro" => $respEnv));
        }

//        echo $respEnv;
exit;

//

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
}
else{
 
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>