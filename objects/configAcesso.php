<?php
class configAcesso{
 
    private $conn;
    private $tableName = "configAcesso";
 
    // object properties
    public $idConfig;
    public $codigoMunicipio;
    public $ambiente;
    public $metodo;
    public $namespace;
    public $wsdl;
    public $action;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create configAcesso
    function create(){
    
        $query = "INSERT INTO " . $this->tableName . " SET
                    codigomunicipio=:codigoMunicipio, ambiente=:ambiente, metodo=:metodo, 
                    namespace=:namespace, wsdl=:wsdl, action=:action";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
        $this->metodo=htmlspecialchars(strip_tags($this->metodo));
        $this->namespace=htmlspecialchars(strip_tags($this->namespace));
        $this->wsdl=htmlspecialchars(strip_tags($this->wsdl));
        $this->action=htmlspecialchars(strip_tags($this->action));
    
        // bind values
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":metodo", $this->metodo);
        $stmt->bindParam(":namespace", $this->namespace);
        $stmt->bindParam(":wsdl", $this->wsdl);
        $stmt->bindParam(":action", $this->action);

        if($stmt->execute()){
            
            $this->idConfig = $this->conn->lastInsertId();
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // update configAcesso
    function update(){
    
        $query = "UPDATE " . $this->tableName . " SET
                    codigomunicipio=:codigoMunicipio, ambiente=:ambiente, metodo=:metodo, 
                    namespace=:namespace, wsdl=:wsdl, action=:action
                  WHERE idConfig = :idConfig";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idConfig=htmlspecialchars(strip_tags($this->idConfig));
        $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
        $this->metodo=htmlspecialchars(strip_tags($this->metodo));
        $this->namespace=htmlspecialchars(strip_tags($this->namespace));
        $this->wsdl=htmlspecialchars(strip_tags($this->wsdl));
        $this->action=htmlspecialchars(strip_tags($this->action));

        // bind new values
        $stmt->bindParam(":idConfig", $this->idConfig);
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":metodo", $this->metodo);
        $stmt->bindParam(":namespace", $this->namespace);
        $stmt->bindParam(":wsdl", $this->wsdl);
        $stmt->bindParam(":action", $this->action);

        if($stmt->execute())

            return array(true);
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    function readOne(){
 
        $this->idConfig = 0; // conferência para registro não encontrado
 
        $query = "SELECT * FROM " . $this->tableName . " WHERE codigoMunicipio = ? AND ambiente = ? LIMIT 0,1";
        $stmt = $this->conn->prepare( $query );
     
        $stmt->bindParam(1, $this->codigoMunicipio);
        $stmt->bindParam(2, $this->ambiente);
     
        $stmt->execute();
     
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        $this->idConfig = $row['idConfig'];
        $this->codigoMunicipio = $row['codigoMunicipio'];
        $this->ambiente = $row['ambiente'];
        $this->metodo = $row['metodo'];
        $this->namespace = $row['namespace'];
        $this->wsdl = $row['wsdl'];
        $this->action = $row['action'];
    }
   
}
?>