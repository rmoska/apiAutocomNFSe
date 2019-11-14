<?php


class LogMsg {
 
    private $conn;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    
    public function register($nivel, $origem, $msg, $anexo) {

        include_once '../objects/logMensagem.php';
        $logMsg = new LogMensagem($this->conn);
    
        $logMsg->nivel = $nivel;
        $logMsg->origem = $origem;
        $logMsg->mensagem = $msg;
        $logMsg->anexo = $anexo;

        $logMsg->create();

    }

}
?>