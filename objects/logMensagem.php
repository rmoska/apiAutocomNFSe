<?php
class LogMensagem {
 
    // database connection and table name
    private $conn;
    private $tableName = "logMensagem";
 
    // object properties
    public $idLogMensagem;
    public $dataHora;
    public $nivel;
    public $origem;
    public $mensagem;
    public $anexo;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create logMensagem
    function create(){
    
        $query = "INSERT INTO " . $this->tableName . " SET
                    dataHora=:dataHora, nivel=:nivel, 
                    origem=:origem, mensagem=:mensagem, anexo=:anexo";

        $stmt = $this->conn->prepare($query);

        $this->nivel=htmlspecialchars(strip_tags($this->nivel));
        $this->origem=htmlspecialchars(strip_tags($this->origem));
        $this->mensagem=htmlspecialchars(strip_tags($this->mensagem));
        $this->anexo=htmlspecialchars(strip_tags($this->anexo), ENT_NOQUOTES);
    
        $dtHr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dataHora", $dtHr);
        $stmt->bindParam(":nivel", $this->nivel);
        $stmt->bindParam(":origem", $this->origem);
        $stmt->bindParam(":mensagem", $this->mensagem);
        $stmt->bindParam(":anexo", $this->anexo);

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