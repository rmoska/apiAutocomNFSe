<?php
class Database{
 
 // specify your own database credentials
 private $host = "www.autocominformatica.com.br";
 private $port = "3306";
 private $dbName = "autocom_FC0001";
 private $username = "autocom_FC0001";
 private $password = "atcfc@2608";
 public $conn;

 // get the database connection
 public function getConnection(){

     $this->conn = null;

     try{
         $this->conn = new PDO("mysql:host=" . $this->host . ";port=". $this->port . ";dbname=" . $this->dbName, $this->username, $this->password);
         $this->conn->exec("set names utf8");
     }catch(PDOException $exception){
         echo "Connection error: " . $exception->getMessage();
     }

     return $this->conn;
 }
}
?>