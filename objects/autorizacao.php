<?php
class Autorizacao{
 
    // database connection and table name
    private $conn;
    private $tableName = "autorizacao";
 
    // object properties
    public $idAutorizacao;
    public $idEmitente;
    public $crt;
    public $cnae;
    public $aedf;
    public $cmc;
    public $senhaWeb;
    public $certificado;
    public $senha;
    public $token;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create autorizacao
    function create($documento){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idEmitente=:idEmitente, crt=:crt, cnae=:cnae, 
                    aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, certificado=:certificado, senha=:senha";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->crt=htmlspecialchars(strip_tags($this->crt));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->aedf=htmlspecialchars(strip_tags($this->aedf));
        $this->cmc=htmlspecialchars(strip_tags($this->cmc));
        $this->senhaWeb=htmlspecialchars(strip_tags($this->senhaWeb));
        $this->certificado=htmlspecialchars(strip_tags($this->certificado));
        $this->senha=htmlspecialchars(strip_tags($this->senha));
    
        // bind values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":crt", $this->crt);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":aedf", $this->aedf);
        $stmt->bindParam(":cmc", $this->cmc);
        $stmt->bindParam(":senhaWeb", $this->senhaWeb);
        $stmt->bindParam(":certificado", $this->certificado);
        $stmt->bindParam(":senha", $this->senha);
    
        // execute query
        if($stmt->execute()){

            $nomeArq = "./certificado/cert".$documento.".pfx";
            $arqCert = fopen($nomeArq,"w");
            $certificado = base64_decode($this->certificado);
            $contCert = fwrite($arqCert, $certificado);
            fclose($arqCert);

            return true;
            
        }

        echo "PDO::errorCode(): ", $stmt->errorCode();

        return false;
        
    }    

    // update autorizacao
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    idEmitente=:idEmitente, crt=:crt, cnae=:cnae, 
                    aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, certificado=:certificado, senha=:senha
                  WHERE
                    idAutorizacao = :idAutorizcao";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->documento=htmlspecialchars(strip_tags($this->idEmitente));
        $this->nome=htmlspecialchars(strip_tags($this->crt));
        $this->nomeFantasia=htmlspecialchars(strip_tags($this->cnae));
        $this->logradouro=htmlspecialchars(strip_tags($this->aedf));
        $this->numero=htmlspecialchars(strip_tags($this->cmc));
        $this->complemento=htmlspecialchars(strip_tags($this->senhaWeb));
        $this->bairro=htmlspecialchars(strip_tags($this->certificado));
        $this->cep=htmlspecialchars(strip_tags($this->senha));

        // bind new values
        $stmt->bindParam(":documento", $this->idEmitente);
        $stmt->bindParam(":nome", $this->crt);
        $stmt->bindParam(":nomeFantasia", $this->cnae);
        $stmt->bindParam(":logradouro", $this->aedf);
        $stmt->bindParam(":numero", $this->cmc);
        $stmt->bindParam(":complemento", $this->senhaWeb);
        $stmt->bindParam(":bairro", $this->certificado);
        $stmt->bindParam(":cep", $this->senha);

        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }    

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idEmitente = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idEmitente);
     
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

    }

    function getToken() {

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
        curl_setopt($curl, CURLOPT_URL, "https://nfps-e-hml.pmf.sc.gov.br/api/v1/autenticacao/oauth/token"); // homolog
//        curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/autenticacao/oauth/token");
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