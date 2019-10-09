<?php
class AutorizacaoChave{
 
    // database connection and table name
    private $conn;
    private $tableName = "autorizacaoChave";
 
    // object properties
    public $idAutorizacao;
    public $chave;
    public $valor;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create autorizacao
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idAutorizacao=:idAutorizacao, chave=:chave, valor=:valor";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->idAutorizacao=htmlspecialchars(strip_tags($this->idAutorizacao));
        $this->chave=htmlspecialchars(strip_tags($this->chave));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
    
        // bind values
        $stmt->bindParam(":idAutorizacao", $this->idAutorizacao);
        $stmt->bindParam(":chave", $this->chave);
        $stmt->bindParam(":valor", $this->valor);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
        
    }    

    // update autorizacao
    function update(){
    
        // update query
        $query = "INSERT INTO " . $this->tableName . " (idAutorizacao, chave, valor) 
                    VALUES (:idAutorizacao, :chave, :valor)
                    ON DUPLICATE KEY UPDATE
                    idAutorizacao=:idAutorizacao, chave=:chave, valor=:valor";

        $query = "REPLACE INTO " . $this->tableName . " SET
        idAutorizacao=:idAutorizacao, chave=:chave, valor=:valor";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idAutorizacao=htmlspecialchars(strip_tags($this->idAutorizacao));
        $this->chave=htmlspecialchars(strip_tags($this->chave));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
    
        // bind values
        $stmt->bindParam(":idAutorizacao", $this->idAutorizacao);
        $stmt->bindParam(":chave", $this->chave);
        $stmt->bindParam(":valor", $this->valor);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    


    // search autorizacaoChave
    function buscaChave(){
    
        // select all query
        $query = "SELECT * FROM " . $this->tableName . " ac
                  WHERE ac.idAutorizacao = ? ";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idAutorizacao);
        $stmt->execute();
    
        return $stmt;
    }    

    
    // check autorizacao 
    function check(){
    
        // select query
        $query = "SELECT a.* FROM " . $this->tableName . " a
                  WHERE a.idEmitente = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
    
        // bind
        $stmt->bindParam(1, $this->idEmitente);
    
        // execute query
        $stmt->execute();
    
        return $stmt->rowCount();
    }    

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idEmitente = ? AND codigoMunicipio = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idEmitente);
        $stmt->bindParam(2, $this->codigoMunicipio);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->crt = $row['crt'];
        $this->aedf = $row['aedf'];
        $this->cmc = $row['cmc'];
        $this->senhaWeb = $row['senhaWeb'];
        $this->certificado = $row['certificado'];
        $this->senha = $row['senha'];
        $this->token = $row['token'];
        $this->nfhomologada = $row['nfhomologada'];

    }


}