<?php

// Classe para emissão de NFSe PMF Homologação / Produção

// get posted data
$data = json_decode(file_get_contents("php://input"));

include_once '../objects/itemVenda.php';
 
$itemVenda = new ItemVenda($db);

$itemVenda->codigo = $data->codigo;

$itemVenda->descricao = $data->descricao;
$itemVenda->cnae = $data->cnae;
$itemVenda->ncm = $data->nbs;
$itemVenda->listaServico = $data->listaServico;

$retorno = $itemVenda->updateVar();
if(!$retorno[0]){

    http_response_code(500);
    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1]));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Item Venda.(I01). Erro=".$retorno[1]."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}



?>