<?php
class Autorizacao{
 
    // database connection and table name
    private $conn;
    private $tableName = "autorizacao";
 
    // object properties
    public $idAutorizacao;
    public $idEmitente;
    public $codigoMunicipio;
    public $crt;
    public $cnae;
    public $aedf;
    public $cmc;
    public $senhaWeb;
    public $certificado;
    public $senha;
    public $mensagemnf;
    public $token;
    public $nfhomologada;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create autorizacao
    function create($documento){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idEmitente=:idEmitente, codigoMunicipio=:codigoMunicipio, crt=:crt, cnae=:cnae, 
                    aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, certificado=:certificado, senha=:senha, mensagemnf=:mensagemnf, dthrinc=:dthrinc";
    
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
        $this->mensagemnf=htmlspecialchars(strip_tags($this->mensagemnf));
    
        // bind values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":crt", $this->crt);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":aedf", $this->aedf);
        $stmt->bindParam(":cmc", $this->cmc);
        $stmt->bindParam(":senhaWeb", $this->senhaWeb);
        $stmt->bindParam(":certificado", $this->certificado);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":mensagemnf", $this->mensagemnf);
        $dthr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dthrinc", $dthr);
    
        // execute query
        if($stmt->execute()){

            $this->idAutorizacao = $this->conn->lastInsertId();

            include_once '../objects/emitente.php';
            $emitente = new Emitente($this->conn);
            $emitente->idEmitente = $this->idEmitente;
            $emitente->readOne();

            if ($this->createDir($emitente->documento)){

                $limpaDir = "../arquivosNFSe/".$documento."/certificado/cert".$documento."*.*";
                foreach(glob($limpaDir) as $arqDel){
                    unlink($arqDel);
                }

                $nomeArq = "../arquivosNFSe/".$emitente->documento."/certificado/cert".$emitente->documento.".pfx";
                $arqCert = fopen($nomeArq,"w");
                $certificado = base64_decode($this->certificado);
                $contCert = fwrite($arqCert, $certificado);
                fclose($arqCert);
            }

            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // update autorizacao
    function update($documento){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    crt=:crt, cnae=:cnae, aedf=:aedf, cmc=:cmc, senhaWeb=:senhaWeb, 
                    certificado=:certificado, senha=:senha, mensagemnf=:mensagemnf, nfhomologada=:nfhomologada, dthralt=:dthralt
                  WHERE
                    idEmitente = :idEmitente AND codigoMunicipio=:codigoMunicipio";

        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->codigoMunicipio=htmlspecialchars(strip_tags($this->codigoMunicipio));
        $this->crt=htmlspecialchars(strip_tags($this->crt));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->aedf=htmlspecialchars(strip_tags($this->aedf));
        $this->cmc=htmlspecialchars(strip_tags($this->cmc));
        $this->senhaWeb=htmlspecialchars(strip_tags($this->senhaWeb));
        $this->certificado=htmlspecialchars(strip_tags($this->certificado));
        $this->senha=htmlspecialchars(strip_tags($this->senha));
        $this->mensagemnf=htmlspecialchars(strip_tags($this->mensagemnf));
        $this->nfhomologada=htmlspecialchars(strip_tags($this->nfhomologada));

        // bind new values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":codigoMunicipio", $this->codigoMunicipio);
        $stmt->bindParam(":crt", $this->crt);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":aedf", $this->aedf);
        $stmt->bindParam(":cmc", $this->cmc);
        $stmt->bindParam(":senhaWeb", $this->senhaWeb);
        $stmt->bindParam(":certificado", $this->certificado);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":mensagemnf", $this->mensagemnf);
        $stmt->bindParam(":nfhomologada", $this->nfhomologada);
        $dthr = date('Y-m-d H:i:s');
        $stmt->bindParam(":dthralt", $dthr);

        // execute query
        if($stmt->execute()){

            include_once '../objects/emitente.php';
            $emitente = new Emitente($this->conn);
            $emitente->idEmitente = $this->idEmitente;
            $emitente->readOne();

            if ($this->createDir($documento)){

                $limpaDir = "../arquivosNFSe/".$documento."/certificado/cert".$documento."*.*";
                foreach(glob($limpaDir) as $arqDel){
                    unlink($arqDel);
                }

                $nomeArq = "../arquivosNFSe/".$documento."/certificado/cert".$documento.".pfx";
                $arqCert = fopen($nomeArq,"w");
                $certificado = base64_decode($this->certificado);
                $contCert = fwrite($arqCert, $certificado);
                fclose($arqCert);
            }

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
        $query = "SELECT * FROM " . $this->tableName . " WHERE idEmitente = ? AND codigoMunicipio = ? LIMIT 0,1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // bind
        $stmt->bindParam(1, $this->idEmitente);
        $stmt->bindParam(2, $this->codigoMunicipio);
    
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
        $this->idAutorizacao = $row['idAutorizacao'];
        $this->crt = $row['crt'];
        $this->aedf = $row['aedf'];
        $this->cmc = $row['cmc'];
        $this->senhaWeb = $row['senhaWeb'];
        $this->certificado = $row['certificado'];
        $this->senha = $row['senha'];
        $this->mensagemnf = $row['mensagemnf'];
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