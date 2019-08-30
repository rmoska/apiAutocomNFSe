<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/notaFiscal.php';
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/emitente.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
 
$database = new Database();
$db = $database->getConnection();
 
$notaFiscal = new NotaFiscal($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

// make sure data is not empty
if(
    !empty($data->documento) &&
    !empty($data->idVenda) &&
    !empty($data->valorTotal) 
){
    // check / create tomador
    if(
        !empty($data->tomador->documento) &&
        !empty($data->tomador->nome) &&
        !empty($data->tomador->logradouro) &&
        !empty($data->tomador->numero) &&
        !empty($data->tomador->complemento) &&
        !empty($data->tomador->bairro) &&
        !empty($data->tomador->cep) &&
        !empty($data->tomador->codigoMunicipio) &&
        !empty($data->tomador->uf) &&
        !empty($data->tomador->email) 
    ){

        $tomador = new Tomador($db);

        // set tomador property values
        $tomador->documento = $data->tomador->documento;
    
        // check tomador
        if (($idTomador = $tomador->check()) > 0) {
            $notaFiscal->idTomador = $idTomador;
        }
        // create tomador
        else {

            $tomador->nome = $data->tomador->nome;
            $tomador->logradouro = $data->tomador->logradouro;
            $tomador->numero = $data->tomador->numero;
            $tomador->complemento = $data->tomador->complemento;
            $tomador->bairro = $data->tomador->bairro;
            $tomador->cep = $data->tomador->cep;
            $tomador->codigoMunicipio = $data->tomador->codigoMunicipio;
            $tomador->uf = $data->tomador->uf;
            $tomador->email = $data->tomador->email;

            if($tomador->create()){
                // set notaFiscal
                $notaFiscal->idTomador = $tomador->idTomador;
            }
            else{
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Tomador. Serviço indisponível."));
                exit;
            }
        }
    }

    // check emitente
    $emitente = new Emitente($db);
    $emitente->documento = $data->documento;
    if (($idEmitente = $emitente->check()) > 0) {
        $notaFiscal->idEmitente = $idEmitente;
    }
    else{
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Emitente não cadastrado. Nota Fiscal não emitida."));
        exit;
    }

    // set notaFiscal property values
    $notaFiscal->docOrigemTipo = "V";
    $notaFiscal->docOrigemNumero = $data->idVenda;
    $notaFiscal->idEntradaSaida = "S";
    $notaFiscal->situacao = "A";
    $notaFiscal->valorTotal = $data->valorTotal;
    $notaFiscal->dadosAdicionais = $data->observacao;

    if ($tomador->uf != 'SC') $cfps = '9203';
    else if ($tomador->codigoMunicipio != '5407') $cfps = '9202';
    else $cfps = '9201';
    $notaFiscal->cfop = $cfps;

    // create notaFiscal
    if(!$notaFiscal->create()){
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Nota Fiscal. Serviço indisponível. (I01)"));
        exit;
    }

    //check / create itemVenda
    foreach ( $data->itemServico as $item )
    {
        if(
            !empty($item->codigo) &&
            !empty($item->descricao) &&
            !empty($item->cnae) &&
            !empty($item->nbs) &&
            !empty($item->quantidade) &&
            !empty($item->valor) &&
            !empty($item->taxaIss) 
        ){

            $itemVenda = new ItemVenda($db);
            $notaFiscalItem = new NotaFiscalItem($db);

            $itemVenda->codigo = $item->codigo;
            if (($idItemVenda = $itemVenda->check()) > 0) 
            {
                $notaFiscalItem->idItemVenda = $idItemVenda;
            }
            else 
            {
                $itemVenda->descricao = $item->descricao;
                $itemVenda->cnae = $item->cnae;
                $itemVenda->ncm = $item->nbs;

                if($itemVenda->create()){
                    // set notaFiscal
                    $notaFiscalItem->idItemVenda = $itemVenda->idItemVenda;
                }
                else{
                    http_response_code(503);
                    echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Venda. Serviço indisponível."));
                    exit;
                }
            }

            $notaFiscalItem->idNotaFiscal = $notaFiscal->idNotaFiscal;
            $notaFiscalItem->cnae = $item->cnae;
            $notaFiscalItem->unidade = "UN";
            $notaFiscalItem->quantidade = $item->quantidade;
            $notaFiscalItem->valorUnitario = $item->valor;
            $notaFiscalItem->valorTotal = ($item->valor*$item->quantidade);
            $notaFiscalItem->taxaIss = $item->taxaIss;
            $notaFiscalItem->valorIss = ($item->valor*$item->quantidade)*($item->taxaIss/100);

            if(!$notaFiscalItem->create()){
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Nota Fiscal. Serviço indisponível."));
                exit;
            }
            else{

                $arrayItemNF[] = $notaFiscalItem;

            }

        }
    }

    if (count($arrayItemNF) > 0){

        $notaFiscal->calcImpAprox();

        $autorizacao = new Autorizacao($db);

        $autorizacao->idEmitente = $notaFiscal->idEmitente;

        $autorizacao->readOne();

        if(!$autorizacao->getToken()){

            http_response_code(503);
            echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Nota Fiscal. Serviço indisponível."));
            exit;

        }

        // set response code - 201 created
        http_response_code(201);

        // tell the user
        echo json_encode(array("http_code" => "201", "message" => "Nota Fiscal incluída", "token" => $autorizacao->token));

    }
    else{

        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Erro na inclusão dos Itens da Nota Fiscal."));
        exit;

    }

    
}
 
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos."));
}
?>