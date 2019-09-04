<?php
class Tomador{
 
    // database connection and table name
    private $conn;
    private $tableName = "tomador";
 
    // object properties
    public $idTomador;
    public $documento;
    public $nome;
    public $logradouro;
    public $numero;
    public $complemento;
    public $bairro;
    public $cep;
    public $codigoMunicipio;
    public $uf;
    public $email;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create tomador
    function create(){
    
echo 'tomador err';

        try { 
            // query to insert record
            $query = "INSERT INTO " . $this->tableName . " SET a=b,
                        documento=:documento, nome=:nome, 
                        logradouro=:logradouro, numero=:numero, complemento=:complemento, bairro=:bairro, cep=:cep, 
                        codigomunicipio=:codigoMunicipio, uf=:uf, email=:email";
        
            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->documento=htmlspecialchars(strip_tags($this->documento));
            $this->nome=htmlspecialchars(strip_tags($this->nome));
            $this->logradouro=htmlspecialchars(strip_tags($this->logradouro));
            $this->numero=htmlspecialchars(strip_tags($this->numero));
            $this->complemento=htmlspecialchars(strip_tags($this->complemento));
            $this->bairro=htmlspecialchars(strip_tags($this->bairro));
            $this->cep=htmlspecialchars(strip_tags($this->cep));
            $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
            $this->uf=htmlspecialchars(strip_tags($this->uf));
            $this->email=htmlspecialchars(strip_tags($this->email));
        
            // bind values
            $stmt->bindParam(":documento", $this->documento);
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":logradouro", $this->logradouro);
            $stmt->bindParam(":numero", $this->numero);
            $stmt->bindParam(":complemento", $this->complemento);
            $stmt->bindParam(":bairro", $this->bairro);
            $stmt->bindParam(":cep", $this->cep);
            $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
            $stmt->bindParam(":uf", $this->uf);
            $stmt->bindParam(":email", $this->email);

            // execute query
            if($stmt->execute()){
                $this->idTomador = $this->conn->lastInsertId();
                return true;
            }
            
        }
        catch(PDOException $e)
        {

            $aErr = $stmt->errorInfo();
            return $aErr[2];

        }
    
//        return false;
        
    }    

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idTomador = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idTomador);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->idTomador = $row['idTomador'];
        $this->documento = $row['documento'];
        $this->nome = $row['nome'];
        $this->logradouro = $row['logradouro'];
        $this->numero = $row['numero'];
        $this->complemento = $row['complemento'];
        $this->bairro = $row['bairro'];
        $this->cep = $row['cep'];
        $this->codigoMunicipio = $row['codigoMunicipio'];
        $this->uf = $row['uf'];
        $this->email = $row['email'];
    }

    // read emitente
    function readRegister(){

        // select all query
        $query = "SELECT t.*, m.nome FROM " . $this->tableName . " t
                    LEFT JOIN estado AS uf ON (t.uf = uf.sigla)
                    LEFT JOIN municipio AS m ON (t.codigoMunicipio = m.codigoUFMunicipio)
                    WHERE t.idTomador = ? ";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idTomador);

        // execute query
        $stmt->execute();
    
        return $stmt;
    }    
   
    // check emitente
    function check(){
    
        // select query
        $query = "SELECT t.* FROM " . $this->tableName . " t
                  WHERE t.documento = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->documento=htmlspecialchars(strip_tags($this->documento));
    
        // bind
        $stmt->bindParam(1, $this->documento);
    
        // execute query
        $stmt->execute();
    
        $idTomador = 0;
        if ($stmt->rowCount() >0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idTomador = $row['idTomador'];
        }

        return $idTomador;
    }    

}
?>