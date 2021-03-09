<?php

class LogReq {
 
    private $conn;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    
    public function register($origem, $requisicao, $idEmitente = "", $idVenda = "") { // parametros com default devem vir no final

        include_once '../objects/logRequisicao.php';
        $logReq = new LogRequisicao($this->conn);
    
        $logReq->origem = $origem;
        $logReq->idEmitente = $idEmitente;
        $logReq->idVenda = $idVenda;
        $logReq->requisicao = $requisicao;

        $logReq->create();

    }

}
?>