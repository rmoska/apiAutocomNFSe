<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
 
// include database and object files
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/emitente.php';
 
// instantiate database and product object
$database = new Database();
$db = $database->getConnection();
 
// initialize object
$emitente = new Emitente($db);
 
// get keywords
$keywords=isset($_GET["s"]) ? $_GET["s"] : "";
 
// query products
$stmt = $emitente->search($keywords);
$num = $stmt->rowCount();
 
// check if more than 0 record found
if($num>0){
 
    // emitente array
    $emitente_arr=array();
    $emitente_arr["records"]=array();
 
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // extract row
        // this will make $row['name'] to
        // just $name only
        extract($row);
 
        $emitente_item=array(
            "idEmitente" => $idEmitente,
            "documento" => $documento,
            "nome" => $nome,
            "nomeFantasia" => $nomeFantasia,
            "logradouro" => $logradouro,
            "numero" => $numero,
            "complemento" => $complemento,
            "bairro" => $bairro,
            "cep" => $cep,
            "codigoMunicipio" => $codigoMunicipio,
            "uf" => $uf,
            "pais" => $pais,
            "fone" => $fone,
            "celular" => $celular,
            "email" => $email
        );
 
        array_push($emitente_arr["records"], $emitente_item);
    }
 
    // set response code - 200 OK
    http_response_code(200);
 
    // show emitente data
    echo json_encode($emitente_arr);
}
 
else{
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user no emitente found
    echo json_encode(
        array("message" => "Nenhum Emitente encontrado.")
    );
}
?>