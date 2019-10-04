<?php

// Classe para emissão de NFSe PMF Homologação / Produção
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// get posted data
$data = json_decode(file_get_contents("php://input"));

include_once '../objects/itemVenda.php';
 
$itemVenda = new ItemVenda($db);

$itemVenda->codigo = $data->codigo;

$itemVenda->descricao = $data->descricao;
$itemVenda->cnae = $data->cnae;
$itemVenda->ncm = $data->nbs;
$itemVenda->listaServico = $data->listaServico;

print_r($data);

$retorno = $itemVenda->updateVar();
if(!$retorno[0]){

    echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Item Venda.(Vi01)", "erro" => $retorno[1]));
    exit;
}



?>