<?php
 
// include database and object files
include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
 
$autorizacao = new Autorizacao($db);
 
/**
 * crt : Regime Tributario (0|1|2|3|4|5|6)
 * optanteSN : Simples Nacional 1=sim 2=nao
 * incentivoFiscal : 1=sim 2=nao
 */
if(
    !empty($data->idEmitente) &&
    !empty($data->crt) &&
    !empty($data->certificado) &&
    !empty($data->senha) &&
    !empty($data->optanteSN) &&
    !empty($data->incentivoFiscal) &&
    !empty($data->codigoServico)
){

    $autorizacao->idEmitente = $data->idEmitente;
    $autorizacao->codigoMunicipio = "4216602"; // São José/SC
    $autorizacao->crt = $data->crt;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;

    if ($autorizacao->check() == 0)
        $retorno = $autorizacao->create($emitente->documento);
    else 
        $retorno = $autorizacao->update($emitente->documento);
 
    if($retorno[0]){

        $aAutoChave = array("optanteSN" => $data->optanteSN, "incentivoFiscal" => $data->incentivoFiscal, "codigoServico" => $data->codigoServico);

        $autorizacaoChave = new AutorizacaoChave($db);
        $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;

        foreach($aAutoChave as $chave => $valor) {

echo 'chave='.$chave.'='.$valor;

            $autorizacaoChave = new AutorizacaoChave($db);
            $autorizacaoChave->idAutorizacao = $autorizacao->idAutorizacao;
            $autorizacaoChave->chave = $chave;
            $autorizacaoChave->valor = $valor;
            $retorno = $autorizacaoChave->update();

            echo json_encode(array("erro" => $retorno[1]));

        }

        exit;
/*
        $autorizacaoChave->chave = "optanteSN";
        $autorizacaoChave->valor = $data->optanteSN;
        $autorizacaoChave->update();
        $autorizacaoChave->chave = "incentivoFiscal";
        $autorizacaoChave->valor = $data->incentivoFiscal;
        $autorizacaoChave->update();
        $autorizacaoChave->chave = "codigoServico";
        $autorizacaoChave->valor = $data->codigoServico;
        $autorizacaoChave->update();
*/
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
        $xml->startElement("GerarNfseEnvio");
        $xml->writeAttribute("xmlns", "http://www.betha.com.br/e-nota-contribuinte-ws");
            $xml->startElement("Rps");
                $xml->startElement("InfDeclaracaoPrestacaoServico");
                $xml->writeAttribute("Id", "lote1");
                    $dtEm = date("Y-m-d");
                    $xml->writeElement("Competencia", $dtEm);
                    $xml->startElement("Servico");
                        $xml->startElement("Valores");
                            $xml->writeElement("ValorServicos", 10.00);
                            $xml->writeElement("ValorIss", 10.00);
                            $xml->writeElement("Aliquota", 0.00); 
                        $xml->endElement(); // Valores
                        $xml->writeElement("IssRetido", 2);
                        $xml->writeElement("ItemListaServico", $aAutoChave["codigoServico"]); //"0402");
                        $xml->writeElement("Discriminacao", "Consulta clinica");
                        $xml->writeElement("CodigoMunicipio", 0); // 4216602
                        $xml->writeElement("ExigibilidadeISS", 3); // isento
//                        $xml->writeElement("MunicipioIncidencia", 0); // 4216602
                    $xml->endElement(); // Servico
                    $xml->startElement("Prestador");
                        $xml->startElement("CpfCnpj");
                            $xml->writeElement("Cnpj", $emitente->documento);
                        $xml->endElement(); // CpfCnpj
                    $xml->endElement(); // Prestador
                    $xml->writeElement("RegimeEspecialTributacao", $autorizacao->crt);
                    $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1-Sim/2-Não
                    $xml->writeElement("IncentivoFiscal", $aAutoChave["incentivoFiscal"]); // 1-Sim/2-Não
                $xml->endElement(); // InfDeclaracaoPrestacaoServico
            $xml->endElement(); // Rps
        $xml->endElement(); // GerarNfseEnvio


        //
        $xmlNFe = $xml->outputMemory(true);

        $xmlAss = $objNFSe->signXML($xmlNFe, 'InfDeclaracaoPrestacaoServico', 'Rps');
        if ($objNFSe->errStatus) {
    
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }

        error_log($xmlAss, 3, "../arquivosNFSe/xmlAss.xml");

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
            echo json_encode(array("http_code" => "400", "message" => "Erro Autorização", "erro" => $cdVerif));
        }
        //erros de validacao do webservice
        if(strstr($respEnv,'Correcao') || strstr($respEnv,'Mensagem')){

            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($respEnv);
            $xmlResp = $DomXml->textContent;
            $msgResp = simplexml_load_string($xmlResp);
            $codigo = (string) $msgResp->ListaMensagemRetorno->MensagemRetorno->Codigo;
            $msg = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Mensagem);
            $correcao = (string) utf8_decode($msgResp->ListaMensagemRetorno->MensagemRetorno->Correcao);
            $cdVerif = $codigo.' - '.$msg.' - '.$correcao;
            echo json_encode(array("http_code" => "400", "message" => "Erro Autorização", "erro" => $cdVerif));
        }
        //se retornar o protocolo, o envio funcionou corretamente
        if(strstr($respEnv,'Protocolo')){

            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($respEnv);
            $xmlResp = $DomXml->textContent;
            $msgResp = simplexml_load_string($xmlResp);

            echo json_encode(array("http_code" => "500", "message" => "Autorização OK.", "erro" => $respEnv));
        }

        print_r($respEnv);
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