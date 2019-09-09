<?php

// Classe para emissão de NFSe PMF em ambiente de Homologação

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/notaFiscal.php';
include_once '../objects/emitente.php';
include_once '../objects/autorizacao.php';
 
$database = new Database();
$db = $database->getConnection();
 
$notaFiscal = new NotaFiscal($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

//
// make sure data is not empty
if(
    empty($data->idNotaFiscal) ||
    empty($data->motivo) 
){

    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não será possível CANCELAR Nota Fiscal. Dados incompletos."));
    exit;
}
    
// set notaFiscal property values
$notaFiscal->idNotaFiscal = $data->idNotaFiscal;
$notaFiscal->textoJustificativa = $data->motivo;

// check NF já gerada para esta Venda
$notaFiscal->readOne();
if (!($notaFiscal->numero > 0)) {

    http_response_code(503);
    echo json_encode(array("http_code" => "500", 
                            "message" => "Nota Fiscal não encontrada. Não foi possível cancelar."));
    exit;
}

// check emitente
$emitente = new Emitente($db);
$emitente->idEmitente = $notaFiscal->idEmitente;
$emitente->readOne();
if (!($emitente->documento > '')) {

    http_response_code(503);
    echo json_encode(array("http_code" => "503", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser cancelada."));
    exit;
}

// buscar token conexão
$autorizacao = new Autorizacao($db);
$autorizacao->idEmitente = $notaFiscal->idEmitente;
$autorizacao->readOne();
if(!$autorizacao->getToken($notaFiscal->ambiente)){

    http_response_code(503);
    echo json_encode(array("http_code" => "503", "message" => "Não foi possível gerar Nota Fiscal. Token não disponível."));
    exit;
}

include_once '../shared/utilities.php';
$utilities = new Utilities();

//			
$xml = new XMLWriter;
$xml->openMemory();
//
// Inicia o cabeçalho do documento XML
$xml->startElement("xmlCancelamentoNfpse");
$xml->writeElement("motivoCancelamento", trim($utilities->limpaEspeciais($notaFiscal->motivoCancela)));

if ($notaFiscal->ambiente == "P") // PRODUÇÃO
    $nuAEDF = $autorizacao->aedf; 
else // HOMOLOGAÇÃO
    $nuAEDF = substr($autorizacao->cmc,0,-1); // para homologação AEDF = CMC menos último caracter
$xml->writeElement("numeroAEDF", $nuAEDF);

$xml->writeElement("nuNotaFiscal", $notaFiscal->numero);
$xml->writeElement("codigoVerificacao", $notaFiscal->chaveNF);
$xml->endElement(); // xmlCancelamentoNfpse
//
$xmlNFe = $xml->outputMemory(true);
$xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;
//
$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."canc-nfse.xml","wt");
fwrite($arqNFe, $xmlNFe);
fclose($arqNFe);
//	
include_once '../comunicacao/signNFSe.php';
$arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
$nfse = new SignNFSe($arraySign);

$xmlAss = $nfse->signXML($xmlNFe, 'xmlCancelamentoNfpse');

//
//
// transmite NFSe	
$headers = array( "Content-type: application/xml", "Authorization: Bearer ".$autorizacao->token ); 
$curl = curl_init();
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 

if ($notaFiscal->ambiente == "P") // PRODUÇÃO
    curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/cancelamento/notas/cancela");
else // HOMOLOGAÇÃO
    curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/cancelamento/notas/cancela");

curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
//
$result = curl_exec($curl);
$info = curl_getinfo( $curl );

if ($info['http_code'] == '200') 
{
    //
    $xmlNFRet = simplexml_load_string($result);
    $dtCanc = substr($xmlNFRet->dataCancelamento,0,10).' '.substr($xmlNFRet->dataCancelamento,11,8);
    //
    $dirXmlRet = "arquivosNFSe/".$emitente->documento."/transmitidas/";
    $arqXmlRet = $emitente->documento."_".substr(str_pad($nuNF,8,'0',STR_PAD_LEFT),0,8)."-nfse.xml";
    $arqNFe = fopen("../".$dirXmlRet.$arqXmlRet,"wt");
    fwrite($arqNFe, $result);
    fclose($arqNFe);
    //
    $notaFiscal->situacao = "X";
    $notaFiscal->dataCancelamento = $dtCanc;
    //
    // update notaFiscal
    $retorno = $notaFiscal->update();
    if(!$retorno[0]){
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível atualizar Nota Fiscal.(A01)", "erro" => $retorno[1]));
        exit;
    }
    else {
        //
        // gerar pdf
        $arqPDF = $notaFiscal->printDanfpse($notaFiscal->idNotaFiscal, $db);

        $dirAPI = basename(dirname(dirname( __FILE__ )));
        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("http_code" => "201", 
                                "message" => "Nota Fiscal emitida", 
                                "idNotaFiscal" => $notaFiscal->idNotaFiscal,
                                "numeroNF" => $notaFiscal->numero,
                                "xml" => "http://www.autocominformatica.com.br/".$dirAPI."/".$dirXmlRet.$arqXmlRet,
                                "pdf" => "http://www.autocominformatica.com.br/".$dirAPI."/".$arqPDF));
        exit;
    }
}
else 
{
    if (substr($info['http_code'],0,1) == '5') {

        http_response_code(503);
        echo json_encode(array("message" => "Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !"));
        exit;
    }
    else {

        $msg = $result;
        $dados = json_decode($result);
        if (isset($dados->error)) {

            http_response_code(503);
            echo json_encode(array("message" => "Erro no envio da NFPSe !(1)", "resposta" => "(".$dados->error.") ".$dados->error_description));
            exit;
        }
        else {

            $xmlNFRet = simplexml_load_string(trim($result));
            $msgRet = (string) $xmlNFRet->message;
            http_response_code(503);
            echo json_encode(array("message" => "Erro no envio da NFPSe !(2)", "resposta" => $msgRet, "resposta2" => $result));
            exit;
        }
    }
}

?>