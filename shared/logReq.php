<?php

class LogReq {
 
    private $conn;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    
    public function register($origem, $requisicao, $documento = "", $idVenda = "") { // parametros com default devem vir no final

        include_once '../objects/logRequisicao.php';
        $logReq = new LogRequisicao($this->conn);
    
        $logReq->origem = $origem;
        $logReq->documento = $documento;
        $logReq->idVenda = $idVenda;
        $logReq->requisicao = $requisicao;

        $logReq->create();

    }

}
?>