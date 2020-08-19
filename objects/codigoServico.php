<?php
class codigoServico {
 
    // database connection and table name
    private $conn;
    private $tableName = "codigoServico";
 
    // object properties
    public $origem;
    public $codigo;
    public $descricao;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    function buscaServico($origem, $codigo){
 
        // query to read single record
        $query = "SELECT descricao FROM codigoServico WHERE origem = ? AND codigo = ? LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $origem=htmlspecialchars(strip_tags($origem));
        $codigo=htmlspecialchars(strip_tags($codigo));
        $stmt->bindParam(1, $origem);
        $stmt->bindParam(2, $codigo);
        $stmt->execute();

        $descricao = '';
        if ($stmt->rowCount() >0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $descricao = $row['descricao'];
        }

        return $descricao;
    }

}    
?>