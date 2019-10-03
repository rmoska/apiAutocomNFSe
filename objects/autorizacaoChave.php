<?php
class AutorizacaoChave{
 
    // database connection and table name
    private $conn;
    private $tableName = "autorizacaoChave";
 
    // object properties
    public $idAutorizacao;
    public $chave;
    public $valor;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create autorizacao
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idAutorizacao=:idAutorizacao, chave=:chave, valor=:valor";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->idAutorizacao=htmlspecialchars(strip_tags($this->idAutorizacao));
        $this->chave=htmlspecialchars(strip_tags($this->chave));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
    
        // bind values
        $stmt->bindParam(":idAutorizacao", $this->idAutorizacao);
        $stmt->bindParam(":chave", $this->chave);
        $stmt->bindParam(":valor", $this->valor);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
        
    }    

    // update autorizacao
    function update(){
    
        // update query
        $query = "INSERT INTO " . $this->tableName . " (idAutorizacao, chave, valor) 
                    VALUES (:idAutorizacao, :chave, :valor)
                    ON DUPLICATE KEY UPDATE
                    idAutorizacao=:idAutorizacao, chave=:chave, valor=:valor";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idAutorizacao=htmlspecialchars(strip_tags($this->idAutorizacao));
        $this->chave=htmlspecialchars(strip_tags($this->chave));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
    
        // bind values
        $stmt->bindParam(":idAutorizacao", $this->idAutorizacao);
        $stmt->bindParam(":chave", $this->chave);
        $stmt->bindParam(":valor", $this->valor);

        // execute query
        if($stmt->execute()){
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // check autorizacao 
    function check(){
    
        // select query
        $query = "SELECT a.* FROM " . $this->tableName . " a
                  WHERE a.idEmitente = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
    
        // bind
        $stmt->bindParam(1, $this->idEmitente);
    
        // execute query
        $stmt->execute();
    
        return $stmt->rowCount();
    }    

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idEmitente = ? AND codigoMunicipio = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idEmitente);
        $stmt->bindParam(2, $this->codigoMunicipio);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
        // set values to object properties
        $this->crt = $row['crt'];
        $this->aedf = $row['aedf'];
        $this->cmc = $row['cmc'];
        $this->senhaWeb = $row['senhaWeb'];
        $this->certificado = $row['certificado'];
        $this->senha = $row['senha'];
        $this->token = $row['token'];
        $this->nfhomologada = $row['nfhomologada'];

    }

    function createDir($documento) {

        $dirEmit = "../arquivosNFSe/".$documento;

        if(!is_dir($dirEmit))
            mkdir($dirEmit, 0755);
        if(!is_dir($dirEmit."/certificado"))
            mkdir($dirEmit."/certificado");
        if(!is_dir($dirEmit."/danfpse"))
            mkdir($dirEmit."/danfpse");
        if(!is_dir($dirEmit."/rps"))
            mkdir($dirEmit."/rps");
        if(!is_dir($dirEmit."/canceladas"))
            mkdir($dirEmit."/canceladas");
        if(!is_dir($dirEmit."/transmitidas"))
            mkdir($dirEmit."/transmitidas");

        return true;

    }

    function getToken($ambiente) {

        // busca token autorização
        $aBasic = base64_encode("autocom-ws-client:93c9fb168c6bc8fa3d9fa8ade999c087");
        $headers = array("Content-type: application/x-www-form-urlencoded",
                                         "Authorization: Basic ".$aBasic ); 
        $pwd = strtoupper(md5($this->senhaWeb));
        $fields = array(
        'grant_type' => 'password',
        'username' => $this->cmc, 
        'password' => $pwd,
        'client_id' => 'autocom-ws-client', 
        'client_secret' => '93c9fb168c6bc8fa3d9fa8ade999c087' 
        );
        //
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
        if ($ambiente == "P")
            curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/autenticacao/oauth/token");
        else
            curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/autenticacao/oauth/token"); // ===== HOMOLOGAÇÃO =====
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        $data = curl_exec($curl);
        $dados = json_decode($data);
        if (isset($dados->error)) {

            return false;
        }
        else {

            $this->token = $dados->access_token;
            return true;
        }
    }
}