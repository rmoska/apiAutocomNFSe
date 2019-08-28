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
include_once '../objects/tomador.php';
 
$database = new Database();
$db = $database->getConnection();
 
$notaFiscal = new NotaFiscal($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));


idNotaFiscal, numero, serie, chaveNF, docOrigemTipo, docOrigemNumero, docOrigemParcela, idEntradaSaida, destinatarioTipo, destinatarioId, 
cfop, naturezaOperacao, idFinalidade, chaveNFReferencia, dataInclusao, horaInclusao, dataEmissao, horaEmissao, dataProcessamento, horaProcessamento, 
situacao, reciboNF, protocoloNF, textoResposta, textoJustificativa, dataCancelamento, horaCancelamento, valorTotalMercadorias, valorTotal, 
valorFrete, valorSeguro, valorOutrasDespesas, valorDesconto, idTransportador, idFrete, placa, ufPlaca, volume, quantVolume, marca, 
pesoLiquido, pesoBruto, obsImpostos, dadosAdicionais


// make sure data is not empty
if(
    !empty($data->idEmitente) &&
    !empty($data->idVenda) &&
    !empty($data->valorTotal) &&
    !empty($data->documento) &&
    !empty($data->codigoMunicipio) &&
    !empty($data->email) 
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
        $tomador->nome = $data->tomador->nome;
        $tomador->logradouro = $data->tomador->logradouro;
        $tomador->numero = $data->tomador->numero;
        $tomador->complemento = $data->tomador->complemento;
        $tomador->bairro = $data->tomador->bairro;
        $tomador->cep = $data->tomador->cep;
        $tomador->codigoMunicipio = $data->tomador->codigoMunicipio;
        $tomador->uf = $data->tomador->uf;
        $tomador->email = $data->tomador->email;
    
    if ($tomador->check() > 0) {

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $tomador->idTomador = $row['idEmitente'];

    }
    // create emitente
    else if($emitente->create()){
 
        // set response code - 201 created
        http_response_code(201);
 
        // tell the user
        echo json_encode(array("http_code" => "201", "message" => "Emitente incluído", "idEmitente" => $emitente->idEmitente));
    }
 
    // if unable to create emitente, tell the user
    else{
 
        // set response code - 503 service unavailable
        http_response_code(503);
 
        // tell the user
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Emitente. Serviço indisponível."));
    }
}





    }
    

    //check / create itemVenda
    foreach ( $data->itemVenda as $item )
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
    
        }
    }




    // set notaFiscal property values
    $notaFiscal->documento = $data->documento;
    $emitente->nome = $data->nome;
    $emitente->nomeFantasia = $data->nomeFantasia;
    $emitente->logradouro = $data->logradouro;
    $emitente->numero = $data->numero;
    $emitente->complemento = $data->complemento;
    $emitente->bairro = $data->bairro;
    $emitente->cep = $data->cep;
    $emitente->codigoMunicipio = $data->codigoMunicipio;
    $emitente->uf = $data->uf;
    $emitente->pais = $data->pais;
    $emitente->fone = $data->fone;
    $emitente->celular = $data->celular;
    $emitente->email = $data->email;
    
    if ($emitente->check() > 0) {

        // set response code - 400 bad request
        http_response_code(400);
    
        // tell the user
        echo json_encode(array("http_code" => "400", "message" => "Emitente já existe para este Documento:".$emitente->documento));

        exit;
    }
    // create emitente
    else if($emitente->create()){
 
        // set response code - 201 created
        http_response_code(201);
 
        // tell the user
        echo json_encode(array("http_code" => "201", "message" => "Emitente incluído", "idEmitente" => $emitente->idEmitente));
    }
 
    // if unable to create emitente, tell the user
    else{
 
        // set response code - 503 service unavailable
        http_response_code(503);
 
        // tell the user
        echo json_encode(array("http_code" => "503", "message" => "Não foi possível incluir Emitente. Serviço indisponível."));
    }
}
 
// tell the user data is incomplete
else{
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Emitente. Dados incompletos."));
}
?>