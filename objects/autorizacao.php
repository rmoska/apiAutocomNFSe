<?php
class Autorizacao{
 
    // database connection and table name
    private $conn;
    private $tableName = "autorizacao";
 
    // object properties
    public $idAutorizacao;
    public $idEmitente;
    public $crt;
    public $cnae;
    public $aedf;
    public $cmc;
    public $senhaWeb;
    public $certificado;
    public $senha;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create autorizacao
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idEmitente=:idEmitente, crt=:crt, cnae=:cnae, 
                    aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, certificado=:certificado, senha=:senha";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->crt=htmlspecialchars(strip_tags($this->crt));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->aedf=htmlspecialchars(strip_tags($this->aedf));
        $this->cmc=htmlspecialchars(strip_tags($this->cmc));
        $this->senhaWeb=htmlspecialchars(strip_tags($this->senhaWeb));
        $this->certificado=htmlspecialchars(strip_tags($this->certificado));
        $this->senha=htmlspecialchars(strip_tags($this->senha));
    
        // bind values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":crt", $this->crt);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":aedf", $this->aedf);
        $stmt->bindParam(":cmc", $this->cmc);
        $stmt->bindParam(":senhaWeb", $this->senhaWeb);
        $stmt->bindParam(":certificado", $this->certificado);
        $stmt->bindParam(":senha", $this->senha);
    
        // execute query
        if($stmt->execute()){
            return true;
        }

        echo "PDO::errorCode(): ", $stmt->errorCode();

        return false;
        
    }    

    // update autorizacao
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    idEmitente=:idEmitente, crt=:crt, cnae=:cnae, 
                    aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, certificado=:certificado, senha=:senha
                  WHERE
                    idAutorizacao = :idAutorizcao";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->documento=htmlspecialchars(strip_tags($this->idEmitente));
        $this->nome=htmlspecialchars(strip_tags($this->crt));
        $this->nomeFantasia=htmlspecialchars(strip_tags($this->cnae));
        $this->logradouro=htmlspecialchars(strip_tags($this->aedf));
        $this->numero=htmlspecialchars(strip_tags($this->cmc));
        $this->complemento=htmlspecialchars(strip_tags($this->senhaWeb));
        $this->bairro=htmlspecialchars(strip_tags($this->certificado));
        $this->cep=htmlspecialchars(strip_tags($this->senha));

        // bind new values
        $stmt->bindParam(":documento", $this->idEmitente);
        $stmt->bindParam(":nome", $this->crt);
        $stmt->bindParam(":nomeFantasia", $this->cnae);
        $stmt->bindParam(":logradouro", $this->aedf);
        $stmt->bindParam(":numero", $this->cmc);
        $stmt->bindParam(":complemento", $this->senhaWeb);
        $stmt->bindParam(":bairro", $this->certificado);
        $stmt->bindParam(":cep", $this->senha);

        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }    

}