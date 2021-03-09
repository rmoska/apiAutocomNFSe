<?php
class LogRequisicao {
 
    // database connection and table name
    private $conn;
    private $tableName = "logRequisicao";
 
    // object properties
    public $idLogRequisicao;
    public $dataHora;
    public $origem;
    public $documento;
    public $idVenda;
    public $requisicao;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create logMensagem
    function create(){
    
        $query = "INSERT INTO " . $this->tableName . " SET
                    dataHora=:dataHora, origem=:origem, 
                    documento=:documento, idVenda=:idVenda, requisicao=:requisicao";

        $stmt = $this->conn->prepare($query);

        $this->origem=htmlspecialchars(strip_tags($this->origem));
        $this->documento=htmlspecialchars(strip_tags($this->documento));
        $this->idVenda=htmlspecialchars(strip_tags($this->idVenda));
        $this->requisicao=htmlspecialchars(strip_tags($this->requisicao), ENT_NOQUOTES);
    
        $dtHr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dataHora", $dtHr);
        $stmt->bindParam(":origem", $this->origem);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":idVenda", $this->idVenda);
        $stmt->bindParam(":requisicao", $this->requisicao);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {
            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    
    
}
?>