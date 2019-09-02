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

    function readUFMunicipio(){
 
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

}    
?>