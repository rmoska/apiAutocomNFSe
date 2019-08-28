<?php
class Database{
 
 // specify your own database credentials
 private $host = "localhost";
 //private $port = "3306";
 private $dbName = "autocom_fastconnect";
 private $username = "root";
 private $password = "autocom";
 public $conn;

 // get the database connection
 public function getConnection(){

     $this->conn = null;

     try{
//        $this->conn = new PDO("mysql:host=" . $this->host . ";port=". $this->port . ";dbname=" . $this->dbName, $this->username, $this->password);
        $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->username, $this->password);
         $this->conn->exec("set names utf8");
     }catch(PDOException $exception){
         echo "Connection error: " . $exception->getMessage();
     }

     return $this->conn;
 }
}
?>