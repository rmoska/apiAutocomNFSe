<?php

// Classe para emissão de NFSe PMF Homologação / Produção
/**
 * CÓDIGO ERROS RETORNO
 * P00	OUTROS		formatação de arquivo inválida ou dados inconsistentes
 * P01	AEDF		autorização do Emitente junto à Prefeitura
 * P02	CNAE		classificação fiscal do item de venda
 * P03	ALIQUOTA	tributação ou taxa do item de venda
 * P04	TOMADOR		documento ou endereço do Tomador
 * P05	TIMEOUT		servidor indisponível ou tempo de espera excedido
 * A00	DBERR		erro no banco de dados API
 * A01	INFO		dados inválidos, faltantes ou inconsistentes no arquivo recebido
 * A02	CERT		erro na leitura do certificado
*/

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=iso-8859-1");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../shared/http_response_code.php';
include_once '../shared/logMsg.php';
include_once '../shared/logReq.php';
include_once '../objects/config.php';
include_once '../objects/emitente.php';

$dirAPI = basename(dirname(dirname( __FILE__ )));

/*
// quando chamada for na base teste, sempre mandar para homologação
if ($dirAPI == "apiAutocomNFSe")
    $ambiente = "P"; // ===== PRODUÇÃO =====
else // if ( basename(dirname(dirname( __FILE__ ))) == "apiAutocomNFSe-teste")
    $ambiente = "H"; // ===== HOMOLOGAÇÃO =====
*/

$database = new Database();
$db = $database->getConnection();
$logMsg = new LogMsg($db);

$cfg = new Config($db);
$cfg->info();
if (($cfg->ambiente != 'H') && ($cfg->ambiente != 'P')) {
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível emitir Nota Fiscal. Ambiente não configurado.", "codigo" => "A01"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível emitir Nota Fiscal. Ambiente não configurado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível emitir Nota Fiscal. Ambiente não configurado.', $strData);
    exit;
}

// get posted data
$data = json_decode(file_get_contents("php://input"));
$strData = json_encode($data);
$logReq = new LogReq($db);
$logReq->register('notaFiscal.create', $strData, $data->documento, $data->idVenda);

//
// confere e busca Emitente
if(empty($data->documento)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível emitir Nota Fiscal. Emitente não identificado.", "codigo" => "A01"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível emitir Nota Fiscal. Emitente não identificado. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível emitir Nota Fiscal. Emitente não identificado.', $strData);
    exit;
}
//
$emitente = new Emitente($db);
$emitente->documento = $data->documento;
if (($idEmitente = $emitente->check()) == 0) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente não cadastrado. Nota Fiscal não pode ser emitida.", "codigo" => "A01"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente não cadastrado. Nota Fiscal não pode ser emitida. Emitente=".$data->documento."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Emitente não cadastrado. Nota Fiscal não pode ser emitida.', 'Emitente='.$data->documento);
    exit;
}
$emitente->idEmitente = $idEmitente;
$emitente->readOne();

//
// confere e busca Municipio
if (!isset($emitente->codigoMunicipio)) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Emitente sem Município definido no cadastro.", "codigo" => "A01"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Emitente sem Município definido no cadastro. Emitente=".$data->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Emitente sem Município definido no cadastro.', 'Emitente='.$data->documento);
    exit;
}

if( empty($data->documento) ||
    empty($data->idVenda) ||
    empty($data->valorTotal) || 
    ($data->valorTotal <= 0) ) {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos.", "codigo" => "A04"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Nota Fiscal. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Nota Fiscal. Dados incompletos.', $strData);
    exit;
}

include_once '../objects/notaFiscalServico.php';
include_once '../objects/notaFiscalServicoItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/codigoServico.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../objects/municipio.php';
 
$notaFiscal = new NotaFiscalServico($db);

// set notaFiscal property values
$notaFiscal->ambiente = $cfg->ambiente;
$notaFiscal->docOrigemTipo = "V"; // Venda
$notaFiscal->docOrigemNumero = $data->idVenda;
$notaFiscal->idEntradaSaida = "S";
$notaFiscal->situacao = "P"; // Pendente

$notaFiscal->valorTotal = $data->valorTotal;
$notaFiscal->dataInclusao = date("Y-m-d");
$notaFiscal->dataEmissao = date("Y-m-d");
$notaFiscal->dadosAdicionais = $data->observacao;

// check NF já gerada para esta Venda
$checkNF = $notaFiscal->checkVenda();
if ($checkNF["existe"] > 0) {

    switch ($checkNF["situacao"]) {
        case 'F': 
            $situacao = "Faturada"; break;
        case 'T': 
            $situacao = "Pendente por Timeout"; break;
        default: 
            $situacao = "ERRO"; break;
    }
    http_response_code(400);
    echo json_encode(array("http_code" => "400", 
                           "idNotaFiscal" => $checkNF["idNotaFiscal"],
                           "message" => "Nota Fiscal já processada para esta Venda. NF n. ".$checkNF["numero"]." - Situação ".$situacao, 
                           "codigo" => "A04"));
    $logMsg->register('E', 'notaFiscal.create', 'Nota Fiscal já processada para esta Venda. ID='.$checkNF["idNotaFiscal"].' NF n.'.$checkNF["numero"].' - Situação '.$situacao, $strData);
    exit;
}

//
// classes específicas por município
switch ($emitente->codigoMunicipio) {
    // AM
    case '1302603': // Manaus
        $arqPhp = 'createMANAUS.php'; break;
    // BA
    case '2927408': // Salvador
        $arqPhp = 'createABRASF1_0.php'; break;
    // MA
    case '2111300': // São Luis
        $arqPhp = 'updateDSF.php'; break;
    // MG
    case '3135456': // Jenipapo de Minas
        $arqPhp = 'createSINTESE.php'; break;
    // SC
    case '4205407': // Florianópolis ==> OK
        $arqPhp = 'createFLN.php'; break;
    case '4216602': // São José
    case '4209300': // Lages
            $arqPhp = 'createBETHA.php'; break;
    case '4202305': // Biguaçu
    case '4211900': // Palhoça ==> OK
        $arqPhp = 'createIPM.php'; break;
    case '4204202': // Chapecó
    case '4208203': // Itajaí
        $arqPhp = 'createPUBLICA.php'; break;
    case '4202008': // Balneário Camboriú
        $arqPhp = 'createSIMPLISS.php'; break;
    case '4218707': // Tubarão
        $arqPhp = 'createMODERNA.php'; break;
    // SP
    case '3543402': // Ribeirão Preto
        $arqPhp = 'createISSNET.php'; break;
    case '3550308': // São Paulo
        $arqPhp = 'createSPL.php'; break;
    case '3548708': // São Bernardo do Campo
        $arqPhp = 'createGINFES.php'; break;
    // RS
    case '4305108': // Caxias do Sul
        $arqPhp = 'createINFISC.php'; break;
    default:
        $arqPhp = ''; break;
}

// se existe município
if (file_exists($arqPhp)) {
    //
    // verifica ou cria tomador
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
    $tomador = new Tomador($db);
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

            http_response_code(400);
            echo json_encode(array("http_code" => "400", "message" => "Não foi possível atualizar Tomador.", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível atualizar Tomador. Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível atualizar Tomador.', $retorno[1]." = ".$strData);
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

            http_response_code(400);
            echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Tomador.", "erro" => $retorno[1], "codigo" => "A00"));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Tomador. Erro=".$retorno[1]." = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
            $logMsg->register('E', 'notaFiscal.create', 'Não foi possível incluir Tomador.', $retorno[1]." = ".$strData);
            exit;
        }
    }
    //
    // inclui arquivo do município
    include $arqPhp;
}
else {

    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Município não disponível para emissão da NFSe.", "codigo" => "A01"));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Município não disponível para emissão da NFSe. Município=".$emitente->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");
    $logMsg->register('E', 'notaFiscal.create', 'Município não disponível para emissão da NFSe.', 'Município='.$emitente->codigoMunicipio);
    exit;
}

?>