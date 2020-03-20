<?php

// Classe para emissão de NFSe PMF em ambiente de Homologação
//

if( empty($data->idNotaFiscal) ||
    empty($data->idEmitente) ) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível cancelar Nota Fiscal. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível cancelar Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

include_once '../objects/autorizacao.php';
include_once '../objects/autorizacaoChave.php';
include_once '../objects/municipio.php';


// set notaFiscal property values
$notaFiscal->idNotaFiscal = $data->idNotaFiscal;

// check NF já gerada para esta Venda
$notaFiscal->readOne();
if (!($notaFiscal->numero > 0)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", 
                            "message" => "Nota Fiscal não encontrada. Não foi possível cancelar. idNF=".$data->idNotaFiscal));
    exit;
}
$notaFiscal->textoJustificativa = $data->motivo;

// check emitente
if ($notaFiscal->idEmitente != $data->idEmitente) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não confere com Nota original. Nota Fiscal não pode ser cancelada."));
    exit;
}
$emitente = new Emitente($db);
$emitente->idEmitente = $notaFiscal->idEmitente;
$emitente->readOne();
if (!($emitente->documento > '')) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser cancelada."));
    exit;
}

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

$municipioEmitente = new Municipio($db);
$municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
$municipioEmitente->buscaMunicipioTOM($emitente->codigoMunicipio);

//			
$xml = new XMLWriter;
$xml->openMemory();

// Inicia o cabeçalho do documento XML
$xml->startElement("nfse");
    $xml->startElement("nf");
    $xml->writeElement("numero", $notaFiscal->numero);
    $xml->writeElement("situacao", "C");
    $xml->writeElement("observacao", trim($utilities->limpaEspeciais($notaFiscal->textoJustificativa)));
    $xml->endElement(); // nf
    $xml->startElement("prestador");
        $xml->writeElement("cpfcnpj", $emitente->documento);
        $xml->writeElement("cidade", $municipioEmitente->codigoTOM); // Palhoça = 8233
    $xml->endElement(); // prestador
$xml->endElement(); // nfse
//
$xmlNFe = $xml->outputMemory(true);
$xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;
//
$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFSe = "../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."CANC-nfse.xml";
$arqNFe = fopen($arqNFSe,"wt");
fwrite($arqNFe, $xmlNFe);
fclose($arqNFe);
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
echo $result;
$info = $retEnv[1];
echo $info;

if ($info['http_code'] == '200') {

    if ($xmlNFRet = @simplexml_load_string($result)) {

        $codRet = explode(" ", $xmlNFRet->mensagem->codigo);
        if (intval($codRet[0]) == 1) { // sucesso

            $cdVerif = $xmlNFRet->cod_verificador_autenticidade;
            $dtNF = $xmlNFRet->data_nfse;
            $hrNF = $xmlNFRet->hora_nfse;
            $dtCanc = substr($dtNF,6,4).'-'.substr($dtNF,3,2).'-'.substr($dtNF,0,2).' '.substr($hrNF,6,2).':'.substr($hrNF,3,2).':'.substr($hrNF,0,2);
            $linkPDF = $xmlNFRet->link_nfse;

            $notaFiscal->chaveNF = $cdVerif;
//            $notaFiscal->linkXml = $linkXml;
            $notaFiscal->linkNF = $linkPDF;

            $notaFiscal->situacao = "X";
            $notaFiscal->dataCancelamento = $dtCanc;
        
            //
            // update notaFiscal
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
                http_response_code(201);
                echo json_encode(array("http_code" => "201", 
                                        "message" => "Nota Fiscal CANCELADA", 
                                        "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                        "numeroNF" => $notaFiscal->numero,
                                        "xml" => $linkXml,
                                        "pdf" => $linkPDF));
                $logMsg->register('S', 'notaFiscal.cancel', 'Nota Fiscal cancelada', $strData);
                exit;
            }
        }
        else { // resposta <> 1
            $codMsg = "P00a"; // $utilities->codificaMsgIPM($msgRet);
            $cdVerif = utf8_decode($xmlNFRet->mensagem->codigo);
        }
    } 
    else { // retorno não é xml (acontece com IPM para login errado: "Não foi encontrado na tb.dcarq.unico a cidade(codmun) do Usuário:")
        $codMsg = "P00b"; // $utilities->codificaMsgIPM($msgRet);
        $cdVerif = utf8_decode($result);
    }

    $notaFiscal->textoResposta = $cdVerif;
    $notaFiscal->update();

    http_response_code(401);
    echo json_encode(array("http_code" => "401", 
                           "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                           "message" => "Erro no cancelamento da NFSe !", 
                           "resposta" => $cdVerif, 
                           "codigo" => $codMsg));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no cancelamento da NFPSe ! idNotaFiscal =".$notaFiscal->idNotaFiscal."  (".$cdVerif.") ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Erro no cancelamento da NFPSe ! ('.$cdVerif.') ', $strData);
    exit;
}
else { // http_code <> 200

    if (substr($info['http_code'],0,1) == '5') {

        //
        if(!$retorno[0]){

            http_response_code(500);
            echo json_encode(array("http_code" => "500", "message" => "Não foi possível atualizar a Nota Fiscal. Serviço indisponível.", "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Nota Fiscal. Serviço indisponível. Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Nota Fiscal. Serviço indisponível.', $retorno[1]);
            exit;
        }

        http_response_code(503);
        echo json_encode(array("http_code" => "503", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no cancelamento da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !",
                                "codigo" => "P05"));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no cancelamento da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido). idNotaFiscal=".$notaFiscal->idNotaFiscal."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Erro no cancelamento da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).', 'idNotaFiscal='.$notaFiscal->idNotaFiscal);
        exit;
    }
    else {

        if ($xmlNFRet = @simplexml_load_string($result))   
            $msgRet = (string)$xmlNFRet->mensagem->codigo;
        else 
            $msgRet = utf8_decode($result);
        $codMsg = "P00c"; // $utilities->codificaMsg($msgRet);

        http_response_code(401);
        echo json_encode(array("http_code" => "401", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "message" => "Erro no cancelamento da NFSe !", 
                                "resposta" => $msgRet, 
                                "codigo" => $codMsg));
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no cancelamento da NFPSe ! (".$msgRet.") ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        $logMsg->register('E', 'notaFiscal.create', 'Erro no cancelamento da NFPSe ! ('.$msgRet.') ', $strData);
        exit;
    }
}

?>