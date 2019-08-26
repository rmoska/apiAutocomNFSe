<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
 
// include database and object files
include_once '../shared/utilities.php';
include_once '../config/core.php';
include_once '../config/database.php';
include_once '../config/http_response_code.php';
include_once '../objects/emitente.php';
 
// utilities
$utilities = new Utilities();
 
// instantiate database and emitente object
$database = new Database();
$db = $database->getConnection();
 
// initialize object
$emitente = new Emitente($db);
 
// query emitentes
$stmt = $emitente->readPaging($from_record_num, $records_per_page);
$num = $stmt->rowCount();
 
// check if more than 0 record found
if($num>0){
 
    // emitente array
    $emitente_arr=array();
    $emitente_arr["records"]=array();
    $emitente_arr["paging"]=array();
 
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        extract($row);
 
        $emitente_item=array(
            "idEmitente" => $idEmitente,
            "documento" => $documento,
            "nome" => $nome,
            "email" => $email
        );

        array_push($emitente_arr["records"], $emitente_item);
    }
 
 
    // include paging
    $total_rows=$emitente->count();
    $page_url="{$home_url}emitente/read_paging.php?";
    $paging=$utilities->getPaging($page, $total_rows, $records_per_page, $page_url);
    $emitente_arr["paging"]=$paging;
 
    // set response code - 200 OK
    http_response_code(200);
 
    // make it json format
    echo json_encode($emitente_arr);
}
 
else{
 
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user emitente does not exist
    echo json_encode(
        array("message" => "Nenhum Emitente encontrado.")
    );
}
?>