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
        if ($tomador->check() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $tomador->idTomador = $row['idEmitente'];
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
                $notaFiscal->idTomador => $tomador->idTomador;
            }
            else{
                http_response_code(503);
                echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Emitente. Serviço indisponível."));
                exit;
            }
        }
    }

    // check emitente
    $emitente = new Emitente($db);
    $emitente-> $data->documento;
    if ($emitente->check() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // set notaFiscal
        $notaFiscal->idEmitente = $row['idEmitente'];
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

    // create notaFiscal
    if(!$notaFiscal->create()){
        http_response_code(503);
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Nota Fiscal. Serviço indisponível. (I01)"));
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
            !empty($item->txIss) 
        ){

            $itemVenda = new ItemVenda($db);
            $notaFiscalItem = new NotaFiscalItem($db);

            if ($itemVenda->check() > 0) 
            {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                // set notaFiscal
                $notaFiscalItem->idItemVenda = $row['idItemVenda'];
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
             
                $notaFiscalItem->cnae = $itemVenda->cnae;
                $notaFiscalItem->unidade = "UN";
                $notaFiscalItem->quantidade = $itemVenda->quantidade;
                $notaFiscalItem->valorUnitario = $itemVenda->valor;
                $notaFiscalItem->taxaIss = $itemVenda->taxaIss;

                if(!$notaFiscalItem->create()){
                    http_response_code(503);
                    echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Item Nota Fiscal. Serviço indisponível."));
                    exit;
                }

            }
//            $arrayItemVenda[] = $itemVenda;
        }
    }

    // set response code - 201 created
    http_response_code(201);

    // tell the user
    echo json_encode(array("http_code" => "201", "message" => "Nota Fiscal incluída"));
    
}
 
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Nota Fiscal. Dados incompletos."));
}
?>