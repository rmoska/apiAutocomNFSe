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
    public $codigoTOM;
    public $codigoModerna;
    public $codigoSIAFI;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    function readUFMunicipio(){
 
        $query = "SELECT * FROM " . $this->tableName . " WHERE codigoUFMunicipio = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->codigoUFMunicipio);
        $stmt->execute();
     
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->codigo = $row['codigo'];
        $this->idCodigoEstado = $row['idCodigoEstado'];
        $this->nome = $row['nome'];

    }

    function buscaMunicipioSIAFI($codMun){
 
        // query to read single record
        $query = "SELECT codigoSIAFI FROM municipioTOM WHERE codigoIBGE = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->codigoUFMunicipio);
        $stmt->execute();

        $this->codigoSIAFI = 0;
        if ($stmt->rowCount() >0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->codigoSIAFI = $row['codigo'];
        }
    }

    function buscaMunicipioTOM($codMun){
 
        // query to read single record
        $query = "SELECT codigo FROM municipioTOM WHERE codigoIBGE = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->codigoUFMunicipio);
        $stmt->execute();

        $this->codigoTOM = 0;
        if ($stmt->rowCount() >0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->codigoTOM = $row['codigo'];
        }
    }

    function buscaMunicipioModerna($codMun){
 
        // query to read single record
        $query = "SELECT codigoModerna FROM municipioTOM WHERE codigoIBGE = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $codMun);
        $stmt->execute();

        $this->codigoModerna = 0;
        if ($stmt->rowCount() >0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->codigoModerna = $row['codigoModerna'];
        }
    }

    function buscaMunicipioProvedor($codMun){
 
        // query to read single record
        $query = "SELECT provedor FROM municipioProvedor WHERE idCodigoUFMunicipio = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $codMun=htmlspecialchars(strip_tags($codMun));
        $stmt->bindParam(1, $codMun);
        $stmt->execute();

        $codigoTOM = 0;
        if ($stmt->rowCount() >0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $provedor = $row['provedor'];
        }

        return $provedor;
    }

}    
?>