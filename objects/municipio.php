<?php
class Municipio{
 
    // database connection and table name
    private $conn;
    private $tableName = "municipio";
 
    // object properties
    public $codigo;
    public $idCodigoEstado;
    public $nome;
    public $codigoUFMunicipio;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    public function readUFMunicipio(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE codigoUFMunicipio = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->codigoUFMunicipio);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->codigo = $row['codigo'];
        $this->idCodigoEstado = $row['idCodigoEstado'];
        $this->nome = $row['nome'];

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