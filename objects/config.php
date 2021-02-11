<?php
class Config {
 
    // database connection and table name
    private $conn;
    private $tableName = "config";
 
    // object properties
    public $ambiente;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    function info() {
 
        $query = "SELECT * FROM " . $this->tableName . " LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
     
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        $this->ambiente = $row['ambiente'];
    }

}    
?>