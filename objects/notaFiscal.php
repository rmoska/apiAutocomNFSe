<?php
class NotaFiscal{
 
    // database connection and table name
    private $conn;
    private $tableName = "notaFiscal";
 
    // object properties
    public $idNotaFiscal;
    public $numero;
    public $serie;
    public $chaveNF;
    public $docOrigemTipo;
    public $docOrigemNumero;
    public $docOrigemParcela;
    public $idEntradaSaida;
    public $idTomador;
    public $cfop;
    public $naturezaOperacao;
    public $idFinalidade;
    public $dataInclusao;
    public $dataEmissao;
    public $situacao;
    public $reciboNF;
    public $protocoloNF;
    public $textoResposta;
    public $textoJustificativa;
    public $dataCancelamento;
    public $valorTotalMercadorias;
    public $valorTotal;
    public $valorFrete;
    public $valorSeguro;
    public $valorOutrasDespesas;
    public $valorDesconto;
    public $obsImpostos;
    public $dadosAdicionais;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create emitente
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, 
                    cfop=:cfop, naturezaOperacao=:naturezaOperacao, idFinalidade=:idFinalidade, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, situacao=:situacao,
                    reciboNF=:reciboNF, protocoloNF=:protocoloNF, textoResposta=:textoResposta,
                    textoJustificativa=:textoJustificativa, dataCancelamento=:dataCancelamento, 
                    valorTotalMercadorias=:valorTotalMercadorias, valorTotal=:valorTotal, valorFrete=:valorFrete,
                    valorSeguro=:valorSeguro, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    obsImpostos=:obsImpostos, dadosAdicionais=:dadosAdicionais";
    
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->serie=htmlspecialchars(strip_tags($this->serie));
        $this->chaveNF=htmlspecialchars(strip_tags($this->chaveNF));
        $this->docOrigemTipo=htmlspecialchars(strip_tags($this->docOrigemTipo));
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
        $this->docOrigemParcela=htmlspecialchars(strip_tags($this->docOrigemParcela));
        $this->idEntradaSaida=htmlspecialchars(strip_tags($this->idEntradaSaida));
        $this->idTomador=htmlspecialchars(strip_tags($this->idTomador));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->naturezaOperacao=htmlspecialchars(strip_tags($this->naturezaOperacao));
        $this->idFinalidade=htmlspecialchars(strip_tags($this->idFinalidade));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->reciboNF=htmlspecialchars(strip_tags($this->reciboNF));
        $this->protocoloNF=htmlspecialchars(strip_tags($this->protocoloNF));
        $this->textoResposta=htmlspecialchars(strip_tags($this->textoResposta));
        $this->textoJustificativa=htmlspecialchars(strip_tags($this->textoJustificativa));
        $this->dataCancelamento=htmlspecialchars(strip_tags($this->dataCancelamento));
        $this->valorTotalMercadorias=htmlspecialchars(strip_tags($this->valorTotalMercadorias));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorFrete=htmlspecialchars(strip_tags($this->valorFrete));
        $this->valorSeguro=htmlspecialchars(strip_tags($this->valorSeguro));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->obsImpostos=htmlspecialchars(strip_tags($this->obsImpostos));
        $this->dadosAdicionais=htmlspecialchars(strip_tags($this->dadosAdicionais));
    
        // bind values
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":chaveNF", $this->chaveNF);
        $stmt->bindParam(":docOrigemTipo", $this->docOrigemTipo);
        $stmt->bindParam(":docOrigemNumero", $this->docOrigemNumero);
        $stmt->bindParam(":docOrigemParcela", $this->docOrigemParcela);
        $stmt->bindParam(":idEntradaSaida", $this->idEntradaSaida);
        $stmt->bindParam(":idTomador", $this->idTomador);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":naturezaOperacao", $this->naturezaOperacao);
        $stmt->bindParam(":idFinalidade", $this->idFinalidade);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":reciboNF", $this->reciboNF);
        $stmt->bindParam(":protocoloNF", $this->protocoloNF);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        $stmt->bindParam(":dataCancelamento", $this->dataCancelamento);
        $stmt->bindParam(":valorTotalMercadorias", $this->valorTotalMercadorias);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorFrete", $this->valorFrete);
        $stmt->bindParam(":valorSeguro", $this->valorSeguro);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":obsImpostos", $this->obsImpostos);
        $stmt->bindParam(":dadosAdicionais", $this->dadosAdicionais);
    
        // execute query
        if($stmt->execute()){
            $this->idNotaFiscal = $this->conn->lastInsertId();
            return true;
        }
    
        return false;
        
    }    

    // update emitente
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, 
                    cfop=:cfop, naturezaOperacao=:naturezaOperacao, idFinalidade=:idFinalidade, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, situacao=:situacao,
                    reciboNF=:reciboNF, protocoloNF=:protocoloNF, textoResposta=:textoResposta,
                    textoJustificativa=:textoJustificativa, dataCancelamento=:dataCancelamento, 
                    valorTotalMercadorias=:valorTotalMercadorias, valorTotal=:valorTotal, valorFrete=:valorFrete,
                    valorSeguro=:valorSeguro, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    obsImpostos=:obsImpostos, dadosAdicionais=:dadosAdicionais
                  WHERE
                    idNotaFiscal = :idNotaFiscal";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->serie=htmlspecialchars(strip_tags($this->serie));
        $this->chaveNF=htmlspecialchars(strip_tags($this->chaveNF));
        $this->docOrigemTipo=htmlspecialchars(strip_tags($this->docOrigemTipo));
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
        $this->docOrigemParcela=htmlspecialchars(strip_tags($this->docOrigemParcela));
        $this->idEntradaSaida=htmlspecialchars(strip_tags($this->idEntradaSaida));
        $this->idTomador=htmlspecialchars(strip_tags($this->idTomador));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->naturezaOperacao=htmlspecialchars(strip_tags($this->naturezaOperacao));
        $this->idFinalidade=htmlspecialchars(strip_tags($this->idFinalidade));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->reciboNF=htmlspecialchars(strip_tags($this->reciboNF));
        $this->protocoloNF=htmlspecialchars(strip_tags($this->protocoloNF));
        $this->textoResposta=htmlspecialchars(strip_tags($this->textoResposta));
        $this->textoJustificativa=htmlspecialchars(strip_tags($this->textoJustificativa));
        $this->dataCancelamento=htmlspecialchars(strip_tags($this->dataCancelamento));
        $this->valorTotalMercadorias=htmlspecialchars(strip_tags($this->valorTotalMercadorias));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorFrete=htmlspecialchars(strip_tags($this->valorFrete));
        $this->valorSeguro=htmlspecialchars(strip_tags($this->valorSeguro));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->obsImpostos=htmlspecialchars(strip_tags($this->obsImpostos));
        $this->dadosAdicionais=htmlspecialchars(strip_tags($this->dadosAdicionais));
    
        // bind values
        $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":chaveNF", $this->chaveNF);
        $stmt->bindParam(":docOrigemTipo", $this->docOrigemTipo);
        $stmt->bindParam(":docOrigemNumero", $this->docOrigemNumero);
        $stmt->bindParam(":docOrigemParcela", $this->docOrigemParcela);
        $stmt->bindParam(":idEntradaSaida", $this->idEntradaSaida);
        $stmt->bindParam(":idTomador", $this->idTomador);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":naturezaOperacao", $this->naturezaOperacao);
        $stmt->bindParam(":idFinalidade", $this->idFinalidade);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":reciboNF", $this->reciboNF);
        $stmt->bindParam(":protocoloNF", $this->protocoloNF);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        $stmt->bindParam(":dataCancelamento", $this->dataCancelamento);
        $stmt->bindParam(":valorTotalMercadorias", $this->valorTotalMercadorias);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorFrete", $this->valorFrete);
        $stmt->bindParam(":valorSeguro", $this->valorSeguro);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":obsImpostos", $this->obsImpostos);
        $stmt->bindParam(":dadosAdicionais", $this->dadosAdicionais);

        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        echo "PDO::errorCode(): ", $stmt->errorCode();

        return false;
    }    

    // delete notaFiscal
    function delete(){
    
        // delete query
        $query = "DELETE FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
    
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
        $query = "SELECT * FROM " . $this->tableName . " ORDER BY numero";
    
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
        $this->idTomador = $row['idTomador'];
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

    function calcImpAprox(){

        // update query
        $query = "UPDATE notaFiscalItem AS nfi, itemVenda AS iv, impostoIBPT AS ia
                    SET nfi.valorImpAproxFed = ((nfi.valorTotal * ia.taxaNacional)/100),
                        nfi.valorImpAproxEst = ((nfi.valorTotal * ia.taxaEstadual)/100),
                        nfi.valorImpAproxMun = ((nfi.valorTotal * ia.taxaMunicipal)/100)
                    WHERE (iv.ncm = ia.codigo AND ia.tipoImposto='NBS') AND
                          nfi.idItemVenda = iv.idItemVenda AND nfi.idNotaFiscal = :idNotaFiscal";

        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);

        // execute the query
        if($stmt->execute()){

            // update query
            $query = "SELECT SUM(nfi.valorImpAproxFed) AS vlTotFed, SUM(nfi.valorImpAproxEst) AS vlTotEst, SUM(nfi.valorImpAproxMun) AS vlTotMun 
                      FROM notaFiscalItem AS nfi WHERE nfi.idNotaFiscal = :idNotaFiscal";

            // prepare query statement
            $stmt = $this->conn->prepare($query);
            // bind values
            $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (($row['vlTotFed']>0) || ($row['vlTotEst']>0) || ($row['vlTotMun']>0)) {

                $msgIBPT = 'Trib aprox R$: '. number_format($row["vlTotFed"],2,',','.').' Federal';
                if ($row['vlTotEst']>0)
                    $msgIBPT .= ' e '.number_format($row["vlTotEst"],2,',','.').' Estadual';
                if ($row['vlTotMun']>0)
                    $msgIBPT .= ' e '.number_format($row["vlTotMun"],2,',','.').' Municipal';
                $msgIBPT .= ' - Fonte: IBPT';

                // update query
                $query = "UPDATE notaFiscal SET 
                            obsImpostos = :msgIBPT
                          WHERE idNotaFiscal = :idNotaFiscal";

                // prepare query statement
                $stmt = $this->conn->prepare($query);
                // bind values
                $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
                $stmt->bindParam(":msgIBPT", $msgIBPT);
                $stmt->execute();
  
            }
        }
    
        return false;
   }
}
?>