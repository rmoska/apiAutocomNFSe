<?php
class Emitente{
 
    // database connection and table name
    private $conn;
    private $tableName = "emitente";
 
    // object properties
    public $idEmitente;
    public $documento;
    public $nome;
    public $nomeFantasia;
    public $logradouro;
    public $numero;
    public $complemento;
    public $bairro;
    public $cep;
    public $codigoMunicipio;
    public $uf;
    public $pais;
    public $fone;
    public $celular;
    public $email;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create emitente
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    documento=:documento, nome=:nome, nomefantasia=:nomeFantasia, 
                    logradouro=:logradouro, numero=:numero, complemento=:complemento, bairro=:bairro, cep=:cep, 
                    codigomunicipio=:codigoMunicipio, uf=:uf, pais=:pais, fone=:fone, celular=:celular, email=:email, dthrinc=:dthrinc";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->documento=htmlspecialchars(strip_tags($this->documento));
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->nomeFantasia=htmlspecialchars(strip_tags($this->nomeFantasia));
        $this->logradouro=htmlspecialchars(strip_tags($this->logradouro));
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->complemento=htmlspecialchars(strip_tags($this->complemento));
        $this->bairro=htmlspecialchars(strip_tags($this->bairro));
        $this->cep=htmlspecialchars(strip_tags($this->cep));
        $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
        $this->uf=htmlspecialchars(strip_tags($this->uf));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->fone=htmlspecialchars(strip_tags($this->fone));
        $this->celular=htmlspecialchars(strip_tags($this->celular));
        $this->email=htmlspecialchars(strip_tags($this->email));
    
        // bind values
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":nomeFantasia", $this->nomeFantasia);
        $stmt->bindParam(":logradouro", $this->logradouro);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":complemento", $this->complemento);
        $stmt->bindParam(":bairro", $this->bairro);
        $stmt->bindParam(":cep", $this->cep);
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":uf", $this->uf);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":fone", $this->fone);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":email", $this->email);
        $dthr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dthrinc", $dthr);
    
        // execute query
        if($stmt->execute()){
            $this->idEmitente = $this->conn->lastInsertId();
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // update emitente
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    nome = :nome, nomeFantasia = :nomeFantasia, 
                    logradouro = :logradouro, numero = :numero, complemento = :complemento,
                    cep = :cep, bairro = :bairro, uf = :uf, codigoMunicipio = :codigoMunicipio,
                    pais = :pais, fone = :fone, celular = :celular, email = :email, dthralt=:dthralt
                WHERE
                    idEmitente = :idEmitente";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->nomeFantasia=htmlspecialchars(strip_tags($this->nomeFantasia));
        $this->logradouro=htmlspecialchars(strip_tags($this->logradouro));
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->complemento=htmlspecialchars(strip_tags($this->complemento));
        $this->cep=htmlspecialchars(strip_tags($this->cep));
        $this->bairro=htmlspecialchars(strip_tags($this->bairro));
        $this->uf=htmlspecialchars(strip_tags($this->uf));
        $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->fone=htmlspecialchars(strip_tags($this->fone));
        $this->celular=htmlspecialchars(strip_tags($this->celular));
        $this->email=htmlspecialchars(strip_tags($this->email));

        // bind new values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":nomeFantasia", $this->nomeFantasia);
        $stmt->bindParam(":logradouro", $this->logradouro);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":complemento", $this->complemento);
        $stmt->bindParam(":cep", $this->cep);
        $stmt->bindParam(":bairro", $this->bairro);
        $stmt->bindParam(":uf", $this->uf);
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":fone", $this->fone);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":email", $this->email);
        $dthr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dthralt", $dthr);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // delete emitente
    function delete(){
    
        // delete query
        $query = "DELETE FROM " . $this->tableName . " WHERE idEmitente = ?";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
    
        // bind id of record to delete
        $stmt->bindParam(1, $this->idEmitente);
    
        // execute query
        if($stmt->execute()){
            return true;
        }
    
        return false;
        
    }

    // read emitente
    function read(){
    
        // select all query
        $query = "SELECT * FROM " . $this->tableName . " ORDER BY nome";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // execute query
        $stmt->execute();
    
        return $stmt;
    }

    // read emitente
    function readRegister(){
    
        // select all query
        $query = "SELECT e.*, m.nome FROM " . $this->tableName . " e
                  LEFT JOIN estado AS uf ON (e.uf = uf.sigla)
                  LEFT JOIN municipio AS m ON (e.codigoMunicipio = m.codigoUFMunicipio)
                  WHERE e.idEmitente = ? ";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idEmitente);
     
        // execute query
        $stmt->execute();
    
        return $stmt;
    }

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idEmitente = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idEmitente);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->idEmitente = $row['idEmitente'];
        $this->documento = $row['documento'];
        $this->nome = $row['nome'];
        $this->nomeFantasia = $row['nomeFantasia'];
        $this->logradouro = $row['logradouro'];
        $this->numero = $row['numero'];
        $this->complemento = $row['complemento'];
        $this->bairro = $row['bairro'];
        $this->cep = $row['cep'];
        $this->codigoMunicipio = $row['codigoMunicipio'];
        $this->uf = $row['uf'];
        $this->pais = $row['pais'];
        $this->fone = $row['fone'];
        $this->celular = $row['celular'];
        $this->email = $row['email'];
    }
    
    // search emitente
    function search($keywords){
    
        // select all query
        $query = "SELECT e.* FROM " . $this->tableName . " e
                  WHERE e.nome LIKE ? OR e.nomeFantasia LIKE ? 
                  ORDER BY e.nome DESC";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $keywords=htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
    
        // bind
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
    
        // execute query
        $stmt->execute();
    
        return $stmt;
    }    

    // check emitente
    function check(){
    
        // select query
        $query = "SELECT e.* FROM " . $this->tableName . " e
                  WHERE e.documento = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->documento=htmlspecialchars(strip_tags($this->documento));
    
        // bind
        $stmt->bindParam(1, $this->documento);
    
        // execute query
        $stmt->execute();
    
        $idEmitente = 0;
        if ($stmt->rowCount() >0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idEmitente = $row['idEmitente'];
        }

        return $idEmitente;
        
    }    

    // read emitente with pagination
    public function readPaging($from_record_num, $records_per_page){
    
        // select query
        $query = "SELECT e.idEmitente, e.documento, e.nome, e.email FROM " . $this->tableName . " e
                  ORDER BY e.nome LIMIT ?, ?";
    
        // prepare query statement
        $stmt = $this->conn->prepare( $query );
    
        // bind variable values
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
    
        // execute query
        $stmt->execute();
    
        // return values from database
        return $stmt;
    }    

    // used for paging products
    public function count(){
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->tableName . "";
    
        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return $row['total_rows'];
    }

}
?>