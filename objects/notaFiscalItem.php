
<?php
class NotaFiscalItem{
 
    // database connection and table name
    private $conn;
    private $tableName = "notaFiscalItem";

    // object properties
    public $idNotaFiscal; 
    public $numeroOrdem; 
    public $idItemVenda; 
    public $unidade; 
    public $quantidade; 
    public $valorUnitario; 
    public $valorUnitarioLiquido; 
    public $valorTotal; 
    public $valorTotalLiquido; 
    public $cnae; 
    public $cstISS; 
    public $valorBCIss; 
    public $taxaIss; 
    public $valorIss; 
    public $cfop; 
    public $origem; 
    public $cstIcms; 
    public $valorBCIcms; 
    public $taxaIcms; 
    public $valorIcms; 
    public $taxaReducaoBC; 
    public $taxaMVA; 
    public $valorBCST; 
    public $taxaST; 
    public $valorST; 
    public $cstPis; 
    public $valorBCPis; 
    public $taxaPis; 
    public $valorPis; 
    public $cstCofins; 
    public $valorBCCofins; 
    public $taxaCofins; 
    public $valorCofins; 
    public $valorFrete; 
    public $valorSeguro; 
    public $valorOutrasDespesas; 
    public $valorDesconto; 
    public $valorImpAproxFed; 
    public $valorImpAproxEst; 
    public $valorImpAproxMun; 
    public $observacao;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create emitente
    function create(){

        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idNotaFiscal=:idNotaFiscal, numeroOrdem=:numeroOrdem, idItemVenda=:idItemVenda, unidade=:unidade, 
                    quantidade=:quantidade, valorUnitario=:valorUnitario, valorUnitarioLiquido=:valorUnitarioLiquido, 
                    valorTotal=:valorTotal, valorTotalLiquido=:valorTotalLiquido, cnae=:cnae, 
                    cstISS=:cstISS, cstISS=:cstISS, valorBCIss=:valorBCIss, taxaIss=:taxaIss, valorIss=:valorIss, 
                    cfop=:cfop, origem=:origem, cstIcms=:cstIcms, valorBCIcms=:valorBCIcms, taxaIcms=:taxaIcms, valorIcms=:valorIcms, 
                    taxaReducaoBC=:taxaReducaoBC, taxaMVA=:taxaMVA, valorBCST=:valorBCST, taxaST=:taxaST, valorST=:valorST, 
                    cstPis=:cstPis, valorBCPis=:valorBCPis, taxaPis=:taxaPis, valorPis=:valorPis,
                    cstCofins=:cstCofins, valorBCCofins=:valorBCCofins, taxaCofins=:taxaCofins, valorCofins=:valorCofins,
                    valorFrete=:valorFrete, valorSeguro=:valorSeguro, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    valorImpAproxFed=:valorImpAproxFed, valorImpAproxEst=:valorImpAproxEst, valorImpAproxMun=:valorImpAproxMun, observacao=:observacao";
    
echo '000<br>';
        // prepare query
        $stmt = $this->conn->prepare($query);

echo '111<br>';
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
        $this->numeroOrdem=htmlspecialchars(strip_tags($this->numeroOrdem));
        $this->idItemVenda=htmlspecialchars(strip_tags($this->idItemVenda));
        $this->unidade=htmlspecialchars(strip_tags($this->unidade));
        $this->quantidade=htmlspecialchars(strip_tags($this->quantidade));
        $this->valorUnitario=htmlspecialchars(strip_tags($this->valorUnitario));
        $this->valorUnitarioLiquido=htmlspecialchars(strip_tags($this->valorUnitarioLiquido));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorTotalLiquido=htmlspecialchars(strip_tags($this->valorTotalLiquido));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->cstISS=htmlspecialchars(strip_tags($this->cstISS));
        $this->valorBCIss=htmlspecialchars(strip_tags($this->valorBCIss));
        $this->taxaIss=htmlspecialchars(strip_tags($this->taxaIss));
        $this->valorIss=htmlspecialchars(strip_tags($this->valorIss));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->origem=htmlspecialchars(strip_tags($this->origem));
        $this->cstIcms=htmlspecialchars(strip_tags($this->cstIcms));
        $this->valorBCIcms=htmlspecialchars(strip_tags($this->valorBCIcms));
        $this->taxaIcms=htmlspecialchars(strip_tags($this->taxaIcms));
        $this->valorIcms=htmlspecialchars(strip_tags($this->valorIcms));
        $this->taxaReducaoBC=htmlspecialchars(strip_tags($this->taxaReducaoBC));
        $this->taxaMVA=htmlspecialchars(strip_tags($this->taxaMVA));
        $this->valorBCST=htmlspecialchars(strip_tags($this->valorBCST));
        $this->taxaST=htmlspecialchars(strip_tags($this->taxaST));
        $this->valorST=htmlspecialchars(strip_tags($this->valorST));
        $this->cstPis=htmlspecialchars(strip_tags($this->cstPis));
        $this->valorBCPis=htmlspecialchars(strip_tags($this->valorBCPis));
        $this->taxaPis=htmlspecialchars(strip_tags($this->taxaPis));
        $this->valorPis=htmlspecialchars(strip_tags($this->valorPis));
        $this->cstCofins=htmlspecialchars(strip_tags($this->cstCofins));
        $this->valorBCCofins=htmlspecialchars(strip_tags($this->valorBCCofins));
        $this->taxaCofins=htmlspecialchars(strip_tags($this->taxaCofins));
        $this->valorCofins=htmlspecialchars(strip_tags($this->valorCofins));
        $this->valorFrete=htmlspecialchars(strip_tags($this->valorFrete));
        $this->valorSeguro=htmlspecialchars(strip_tags($this->valorSeguro));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->valorImpAproxFed=htmlspecialchars(strip_tags($this->valorImpAproxFed));
        $this->valorImpAproxEst=htmlspecialchars(strip_tags($this->valorImpAproxEst));
        $this->valorImpAproxMun=htmlspecialchars(strip_tags($this->valorImpAproxMun));
        $this->observacao=htmlspecialchars(strip_tags($this->observacao));
echo '222<br>';
    
        // bind values
        $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
        $stmt->bindParam(":numeroOrdem", $this->numeroOrdem);
        $stmt->bindParam(":idItemVenda", $this->idItemVenda);
        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":valorUnitario", $this->valorUnitario);
        $stmt->bindParam(":valorUnitarioLiquido", $this->valorUnitarioLiquido);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorTotalLiquido", $this->valorTotalLiquido);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":cstISS", $this->cstISS);
        $stmt->bindParam(":valorBCIss", $this->valorBCIss);
        $stmt->bindParam(":taxaIss", $this->taxaIss);
        $stmt->bindParam(":valorIss", $this->valorIss);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":origem", $this->origem);
        $stmt->bindParam(":cstIcms", $this->cstIcms);
        $stmt->bindParam(":valorBCIcms", $this->valorBCIcms);
        $stmt->bindParam(":taxaIcms", $this->taxaIcms);
        $stmt->bindParam(":valorIcms", $this->valorIcms);
        $stmt->bindParam(":taxaReducaoBC", $this->taxaReducaoBC);
        $stmt->bindParam(":taxaMVA", $this->taxaMVA);
        $stmt->bindParam(":valorBCST", $this->valorBCST);
        $stmt->bindParam(":taxaST", $this->taxaST);
        $stmt->bindParam(":valorST", $this->valorST);
        $stmt->bindParam(":cstPis", $this->cstPis);
        $stmt->bindParam(":valorBCPis", $this->valorBCPis);
        $stmt->bindParam(":taxaPis", $this->taxaPis);
        $stmt->bindParam(":valorPis", $this->valorPis);
        $stmt->bindParam(":cstCofins", $this->cstCofins);
        $stmt->bindParam(":valorBCCofins", $this->valorBCCofins);
        $stmt->bindParam(":taxaCofins", $this->taxaCofins);
        $stmt->bindParam(":valorCofins", $this->valorCofins);
        $stmt->bindParam(":valorFrete", $this->valorFrete);
        $stmt->bindParam(":valorSeguro", $this->valorSeguro);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":valorImpAproxFed", $this->valorImpAproxFed);
        $stmt->bindParam(":valorImpAproxEst", $this->valorImpAproxEst);
        $stmt->bindParam(":valorImpAproxMun", $this->valorImpAproxMun);
        $stmt->bindParam(":observacao", $this->observacao);
    
        try{
            // execute query
            if($stmt->execute()){
                $this->idNotaFiscal = $this->conn->lastInsertId();
                return true;
            }
        }catch(PDOException $e){
            echo $e->getMessage();
        }
    
        return false;
        
    }    

    // update emitente
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    idNotaFiscal=:idNotaFiscal, numeroOrdem=:numeroOrdem, idItemVenda=:idItemVenda, unidade=:unidade, 
                    quantidade=:quantidade, valorUnitario=:valorUnitario, valorUnitarioLiquido=:valorUnitarioLiquido, 
                    valorTotal=:valorTotal, valorTotalLiquido=:valorTotalLiquido, cnae=:cnae, 
                    cstISS=:cstISS, cstISS=:cstISS, valorBCIss=:valorBCIss, taxaIss=:taxaIss, valorIss=:valorIss, 
                    cfop=:cfop, origem=:origem, cstIcms=:cstIcms, valorBCIcms=:valorBCIcms, taxaIcms=:taxaIcms, valorIcms=:valorIcms, 
                    taxaReducaoBC=:taxaReducaoBC, taxaMVA=:taxaMVA, valorBCST=:valorBCST, taxaST=:taxaST, valorST=:valorST, 
                    cstPis=:cstPis, valorBCPis=:valorBCPis, taxaPis=:taxaPis, valorPis=:valorPis,
                    cstCofins=:cstCofins, valorBCCofins=:valorBCCofins, taxaCofins=:taxaCofins, valorCofins=:valorCofins,
                    valorFrete=:valorFrete, valorSeguro=:valorSeguro, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    valorImpAproxFed=:valorImpAproxFed, valorImpAproxEst=:valorImpAproxEst, valorImpAproxMun=:valorImpAproxMun, observacao=:observacao
                  WHERE
                    idNotaFiscal = :idNotaFiscal";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
        $this->numeroOrdem=htmlspecialchars(strip_tags($this->numeroOrdem));
        $this->idItemVenda=htmlspecialchars(strip_tags($this->idItemVenda));
        $this->unidade=htmlspecialchars(strip_tags($this->unidade));
        $this->quantidade=htmlspecialchars(strip_tags($this->quantidade));
        $this->valorUnitario=htmlspecialchars(strip_tags($this->valorUnitario));
        $this->valorUnitarioLiquido=htmlspecialchars(strip_tags($this->valorUnitarioLiquido));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorTotalLiquido=htmlspecialchars(strip_tags($this->valorTotalLiquido));
        $this->cnae=htmlspecialchars(strip_tags($this->cnae));
        $this->cstISS=htmlspecialchars(strip_tags($this->cstISS));
        $this->valorBCIss=htmlspecialchars(strip_tags($this->valorBCIss));
        $this->taxaIss=htmlspecialchars(strip_tags($this->taxaIss));
        $this->valorIss=htmlspecialchars(strip_tags($this->valorIss));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->origem=htmlspecialchars(strip_tags($this->origem));
        $this->cstIcms=htmlspecialchars(strip_tags($this->cstIcms));
        $this->valorBCIcms=htmlspecialchars(strip_tags($this->valorBCIcms));
        $this->taxaIcms=htmlspecialchars(strip_tags($this->taxaIcms));
        $this->valorIcms=htmlspecialchars(strip_tags($this->valorIcms));
        $this->taxaReducaoBC=htmlspecialchars(strip_tags($this->taxaReducaoBC));
        $this->taxaMVA=htmlspecialchars(strip_tags($this->taxaMVA));
        $this->valorBCST=htmlspecialchars(strip_tags($this->valorBCST));
        $this->taxaST=htmlspecialchars(strip_tags($this->taxaST));
        $this->valorST=htmlspecialchars(strip_tags($this->valorST));
        $this->cstPis=htmlspecialchars(strip_tags($this->cstPis));
        $this->valorBCPis=htmlspecialchars(strip_tags($this->valorBCPis));
        $this->taxaPis=htmlspecialchars(strip_tags($this->taxaPis));
        $this->valorPis=htmlspecialchars(strip_tags($this->valorPis));
        $this->cstCofins=htmlspecialchars(strip_tags($this->cstCofins));
        $this->valorBCCofins=htmlspecialchars(strip_tags($this->valorBCCofins));
        $this->taxaCofins=htmlspecialchars(strip_tags($this->taxaCofins));
        $this->valorCofins=htmlspecialchars(strip_tags($this->valorCofins));
        $this->valorFrete=htmlspecialchars(strip_tags($this->valorFrete));
        $this->valorSeguro=htmlspecialchars(strip_tags($this->valorSeguro));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->valorImpAproxFed=htmlspecialchars(strip_tags($this->valorImpAproxFed));
        $this->valorImpAproxEst=htmlspecialchars(strip_tags($this->valorImpAproxEst));
        $this->valorImpAproxMun=htmlspecialchars(strip_tags($this->valorImpAproxMun));
        $this->observacao=htmlspecialchars(strip_tags($this->observacao));
    
        // bind values
        $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
        $stmt->bindParam(":numeroOrdem", $this->numeroOrdem);
        $stmt->bindParam(":idItemVenda", $this->idItemVenda);
        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":valorUnitario", $this->valorUnitario);
        $stmt->bindParam(":valorUnitarioLiquido", $this->valorUnitarioLiquido);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorTotalLiquido", $this->valorTotalLiquido);
        $stmt->bindParam(":cnae", $this->cnae);
        $stmt->bindParam(":cstISS", $this->cstISS);
        $stmt->bindParam(":valorBCIss", $this->valorBCIss);
        $stmt->bindParam(":taxaIss", $this->taxaIss);
        $stmt->bindParam(":valorIss", $this->valorIss);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":origem", $this->origem);
        $stmt->bindParam(":cstIcms", $this->cstIcms);
        $stmt->bindParam(":valorBCIcms", $this->valorBCIcms);
        $stmt->bindParam(":taxaIcms", $this->taxaIcms);
        $stmt->bindParam(":valorIcms", $this->valorIcms);
        $stmt->bindParam(":taxaReducaoBC", $this->taxaReducaoBC);
        $stmt->bindParam(":taxaMVA", $this->taxaMVA);
        $stmt->bindParam(":valorBCST", $this->valorBCST);
        $stmt->bindParam(":taxaST", $this->taxaST);
        $stmt->bindParam(":valorST", $this->valorST);
        $stmt->bindParam(":cstPis", $this->cstPis);
        $stmt->bindParam(":valorBCPis", $this->valorBCPis);
        $stmt->bindParam(":taxaPis", $this->taxaPis);
        $stmt->bindParam(":valorPis", $this->valorPis);
        $stmt->bindParam(":cstCofins", $this->cstCofins);
        $stmt->bindParam(":valorBCCofins", $this->valorBCCofins);
        $stmt->bindParam(":taxaCofins", $this->taxaCofins);
        $stmt->bindParam(":valorCofins", $this->valorCofins);
        $stmt->bindParam(":valorFrete", $this->valorFrete);
        $stmt->bindParam(":valorSeguro", $this->valorSeguro);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":valorImpAproxFed", $this->valorImpAproxFed);
        $stmt->bindParam(":valorImpAproxEst", $this->valorImpAproxEst);
        $stmt->bindParam(":valorImpAproxMun", $this->valorImpAproxMun);
        $stmt->bindParam(":observacao", $this->observacao);

        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }    

    // delete notaFiscal
    function delete(){
    
        // delete query
        $query = "DELETE * FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
    
        // bind id of record to delete
        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        if($stmt->execute()){
            return true;
        }
    
        return false;
        
    }

    // read notaFiscal
    function read(){
    
        // select all query
        $query = "SELECT * FROM " . $this->tableName . " ORDER BY numeroOrdem";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // execute query
        $stmt->execute();
    
        return $stmt;
    }

    function readOne(){
 
        // query to read single record
        $query = "SELECT * FROM " . $this->tableName . " WHERE idNotaFiscal = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
     
        // bind id of product to be updated
        $stmt->bindParam(1, $this->idNotaFiscal);
     
        // execute query
        $stmt->execute();
     
        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // set values to object properties
        $this->idNotaFiscal = $row['idNotaFiscal'];
        $this->numero = $row['numero'];
        $this->serie = $row['serie'];
        $this->chaveNF = $row['chaveNF'];
        $this->docOrigemTipo = $row['docOrigemTipo'];
        $this->docOrigemNumero = $row['docOrigemNumero'];
        $this->docOrigemParcela = $row['docOrigemParcela'];
        $this->idEntradaSaida = $row['idEntradaSaida'];
        $this->destinatarioTipo = $row['destinatarioTipo'];
        $this->destinatarioId = $row['destinatarioId'];
        $this->cfop = $row['cfop'];
        $this->naturezaOperacao = $row['naturezaOperacao'];
        $this->idFinalidade = $row['idFinalidade'];
        $this->dataInclusao = $row['dataInclusao'];
        $this->dataEmissao = $row['dataEmissao'];
        $this->situacao = $row['situacao'];
        $this->reciboNF = $row['reciboNF'];
        $this->protocoloNF = $row['protocoloNF'];
        $this->textoResposta = $row['textoResposta'];
        $this->textoJustificativa = $row['textoJustificativa'];
        $this->dataCancelamento = $row['dataCancelamento'];
        $this->valorTotalMercadorias = $row['valorTotalMercadorias'];
        $this->valorTotal = $row['valorTotal'];
        $this->valorFrete = $row['valorFrete'];
        $this->valorSeguro = $row['valorSeguro'];
        $this->valorOutrasDespesas = $row['valorOutrasDespesas'];
        $this->valorDesconto = $row['valorDesconto'];
        $this->obsImpostos = $row['obsImpostos'];
        $this->dadosAdicionais = $row['dadosAdicionais'];
    }
    
    // check notaFiscal
    function check(){
    
        // select query
        $query = "SELECT nf.* FROM " . $this->tableName . " nf
                  WHERE nf.idNotaFiscal = ? LIMIT 1";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
    
        // bind
        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        $stmt->execute();
    
        return $stmt->rowCount();
    }    

}
?>