<?php
class ItemVenda{
 
    // database connection and table name
    private $conn;
    private $tableName = "itemVenda";
 
    // object properties
    public $idItemVenda;
    public $codigo;
    public $descricao;
    public $cnae;
    public $ncm;
    public $codigoServico;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create itemVenda
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    codigo=:codigo, descricao=:descricao, cnae=:cnae, ncm=:ncm, codigoServico=:codigoServico";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->codigo=htmlspecialchars(strip_tags($this->codigo));
        $this->descricao=htmlspecialchars(strip_tags($this->descricao));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->ncm=htmlspecialchars(strip_tags($this->ncm));
        $this->codigoServico=htmlspecialchars(strip_tags($this->codigoServico));
    
        // bind values
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":ncm", $this->ncm);
        $stmt->bindParam(":codigoServico", $this->codigoServico);

        // execute query
        if($stmt->execute()){

            $this->idItemVenda = $this->conn->lastInsertId();
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // update itemVenda campos variáveis - altera apenas campos com conteúdo
    function updateVar(){
            
        // lista de campos alteráveis
        $alterados = array("descricao","cnae","ncm","codigoServico");

        $params = array(); // recebe conteúdo da alteração
        $params['codigo'] = $this->codigo;
        $strSql = ""; // preenche sql alteração
        foreach ($alterados as $campo) {

            if (isset($this->$campo)) {

                $strSql .= "`$campo` = :$campo,";
                $params[$campo] = $this->$campo;
            }
        }
        $strSql = rtrim($strSql, ",");
        if ($strSql == "")
            return array(true);

        $query = "UPDATE itemVenda SET $strSql WHERE codigo = :codigo";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute($params)){

            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idItemVenda = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idItemVenda);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->idItemVenda = $row['idItemVenda'];
        $this->codigo = $row['codigo'];
        $this->descricao = $row['descricao'];
        $this->cnae = $row['cnae'];
        $this->ncm = $row['ncm'];
        $this->codigoServico = $row['codigoServico'];
    }
    
    // check itemVenda
    function check(){
    
        // select query
        $query = "SELECT iv.* FROM " . $this->tableName . " iv
                  WHERE iv.codigo = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->codigo=htmlspecialchars(strip_tags($this->codigo));
    
        // bind
        $stmt->bindParam(1, $this->codigo);
    
        // execute query
        $stmt->execute();
    
        $idItemVenda = 0;
        if ($stmt->rowCount() >0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idItemVenda = $row['idItemVenda'];
        }

        return $idItemVenda;

    }    

}
?>