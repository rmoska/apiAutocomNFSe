<?php
class Database{
 
 // specify your own database credentials
 private $host = "beetobe.xyz:1001";
 private $dbName = "autocomNFSe";
 private $username = "root";
 private $password = "0004utocom000";
 public $conn;

 // get the database connection
 public function getConnection(){

     $this->conn = null;

     try{
         $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->username, $this->password);
         $this->conn->exec("set names utf8");
     }catch(PDOException $exception){
         echo "Connection error: " . $exception->getMessage();
     }

     return $this->conn;
 }
}
?>