<?php
class NotaFiscalServico {
 
    private $conn;
    private $tableName = "notaFiscalServico";
 
    public $idNotaFiscal;
    public $idEmitente;
    public $numero;
    public $serie;
    public $chaveNF;
    public $docOrigemTipo;
    public $docOrigemNumero;
    public $docOrigemParcela;
    public $idEntradaSaida;
    public $idTomador;
    public $cfop;
    public $dataInclusao;
    public $dataEmissao;
    public $dataProcessamento;
    public $situacao;
    public $ambiente;
    public $textoResposta;
    public $textoJustificativa;
    public $dataCancelamento;
    public $valorTotal;
    public $valorOutrasDespesas;
    public $valorDesconto;
    public $obsImpostos;
    public $dadosAdicionais;
    public $linkNF;
    public $linkXml;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    //
    // create nota fiscal
    function create(){
    
        $query = "INSERT INTO " . $this->tableName . " SET
                    idEmitente=:idEmitente, numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, cfop=:cfop, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, dataProcessamento=:dataProcessamento,
                    situacao=:situacao, ambiente=:ambiente, textoResposta=:textoResposta,
                    textoJustificativa=:textoJustificativa, dataCancelamento=:dataCancelamento, 
                    valorTotal=:valorTotal, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    obsImpostos=:obsImpostos, dadosAdicionais=:dadosAdicionais, linkNF=:linkNF, linkXml=:linkXml";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->serie=htmlspecialchars(strip_tags($this->serie));
        $this->chaveNF=htmlspecialchars(strip_tags($this->chaveNF));
        $this->docOrigemTipo=htmlspecialchars(strip_tags($this->docOrigemTipo));
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
        $this->docOrigemParcela=htmlspecialchars(strip_tags($this->docOrigemParcela));
        $this->idEntradaSaida=htmlspecialchars(strip_tags($this->idEntradaSaida));
        $this->idTomador=htmlspecialchars(strip_tags($this->idTomador));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->dataProcessamento=htmlspecialchars(strip_tags($this->dataProcessamento));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
        $this->textoResposta=htmlspecialchars(strip_tags($this->textoResposta));
        $this->textoJustificativa=htmlspecialchars(strip_tags($this->textoJustificativa));
        $this->dataCancelamento=htmlspecialchars(strip_tags($this->dataCancelamento));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->obsImpostos=htmlspecialchars(strip_tags($this->obsImpostos));
        $this->dadosAdicionais=htmlspecialchars(strip_tags($this->dadosAdicionais));
        $this->linkNF=htmlspecialchars(strip_tags($this->linkNF));
        $this->linkXml=htmlspecialchars(strip_tags($this->linkXml));
    
        // bind values
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":chaveNF", $this->chaveNF);
        $stmt->bindParam(":docOrigemTipo", $this->docOrigemTipo);
        $stmt->bindParam(":docOrigemNumero", $this->docOrigemNumero);
        $stmt->bindParam(":docOrigemParcela", $this->docOrigemParcela);
        $stmt->bindParam(":idEntradaSaida", $this->idEntradaSaida);
        $stmt->bindParam(":idTomador", $this->idTomador);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        if (($this->dataProcessamento == "NULL") || ($this->dataProcessamento == "") || ($this->dataProcessamento == "0000-00-00"))
            $stmt->bindValue(":dataProcessamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataProcessamento", $this->dataProcessamento, PDO::PARAM_NULL);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        if (($this->dataCancelamento == "NULL") || ($this->dataCancelamento == "") || ($this->dataCancelamento == "0000-00-00"))
            $stmt->bindValue(":dataCancelamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataCancelamento", $this->dataCancelamento, PDO::PARAM_NULL);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":obsImpostos", $this->obsImpostos);
        $stmt->bindParam(":dadosAdicionais", $this->dadosAdicionais);
        $stmt->bindParam(":linkNF", $this->linkNF);
        $stmt->bindParam(":linkXml", $this->linkXml);

        // execute query
        if($stmt->execute()){

            $this->idNotaFiscal = $this->conn->lastInsertId();
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    //
    // update nota fiscal
    function update(){
    
        $query = "UPDATE " . $this->tableName . " SET
                    idEmitente=:idEmitente, numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, cfop=:cfop, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, dataProcessamento=:dataProcessamento,
                    situacao=:situacao, ambiente=:ambiente, textoResposta=:textoResposta,
                    textoJustificativa=:textoJustificativa, dataCancelamento=:dataCancelamento, 
                    valorTotal=:valorTotal, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    obsImpostos=:obsImpostos, dadosAdicionais=:dadosAdicionais, linkNF=:linkNF, linkXml=:linkXml
                  WHERE
                    idNotaFiscal = :idNotaFiscal";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
        $this->idEmitente=htmlspecialchars(strip_tags($this->idEmitente));
        $this->numero=htmlspecialchars(strip_tags($this->numero));
        $this->serie=htmlspecialchars(strip_tags($this->serie));
        $this->chaveNF=htmlspecialchars(strip_tags($this->chaveNF));
        $this->docOrigemTipo=htmlspecialchars(strip_tags($this->docOrigemTipo));
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
        $this->docOrigemParcela=htmlspecialchars(strip_tags($this->docOrigemParcela));
        $this->idEntradaSaida=htmlspecialchars(strip_tags($this->idEntradaSaida));
        $this->idTomador=htmlspecialchars(strip_tags($this->idTomador));
        $this->cfop=htmlspecialchars(strip_tags($this->cfop));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->dataProcessamento=htmlspecialchars(strip_tags($this->dataProcessamento));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
        $this->textoResposta=htmlspecialchars(strip_tags($this->textoResposta));
        $this->textoJustificativa=htmlspecialchars(strip_tags($this->textoJustificativa));
        $this->dataCancelamento=htmlspecialchars(strip_tags($this->dataCancelamento));
        $this->valorTotal=htmlspecialchars(strip_tags($this->valorTotal));
        $this->valorOutrasDespesas=htmlspecialchars(strip_tags($this->valorOutrasDespesas));
        $this->valorDesconto=htmlspecialchars(strip_tags($this->valorDesconto));
        $this->obsImpostos=htmlspecialchars(strip_tags($this->obsImpostos));
        $this->dadosAdicionais=htmlspecialchars(strip_tags($this->dadosAdicionais));
        $this->linkNF=htmlspecialchars(strip_tags($this->linkNF));
        $this->linkXml=htmlspecialchars(strip_tags($this->linkXml));
    
        // bind values
        $stmt->bindParam(":idNotaFiscal", $this->idNotaFiscal);
        $stmt->bindParam(":idEmitente", $this->idEmitente);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":chaveNF", $this->chaveNF);
        $stmt->bindParam(":docOrigemTipo", $this->docOrigemTipo);
        $stmt->bindParam(":docOrigemNumero", $this->docOrigemNumero);
        $stmt->bindParam(":docOrigemParcela", $this->docOrigemParcela);
        $stmt->bindParam(":idEntradaSaida", $this->idEntradaSaida);
        $stmt->bindParam(":idTomador", $this->idTomador);
        $stmt->bindParam(":cfop", $this->cfop);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        if (($this->dataProcessamento == "NULL") || ($this->dataProcessamento == "") || ($this->dataProcessamento == "0000-00-00"))
            $stmt->bindValue(":dataProcessamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataProcessamento", $this->dataProcessamento, PDO::PARAM_NULL);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        if (($this->dataCancelamento == "NULL") || ($this->dataCancelamento == "") || ($this->dataCancelamento == "0000-00-00"))
            $stmt->bindValue(":dataCancelamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataCancelamento", $this->dataCancelamento, PDO::PARAM_NULL);
        $stmt->bindParam(":valorTotal", $this->valorTotal);
        $stmt->bindParam(":valorOutrasDespesas", $this->valorOutrasDespesas);
        $stmt->bindParam(":valorDesconto", $this->valorDesconto);
        $stmt->bindParam(":obsImpostos", $this->obsImpostos);
        $stmt->bindParam(":dadosAdicionais", $this->dadosAdicionais);
        $stmt->bindParam(":linkNF", $this->linkNF);
        $stmt->bindParam(":linkXml", $this->linkXml);

        // execute query
        if($stmt->execute()){

            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    //
    // update emergencial
    function updateSituacao($situacao){
    
        $query = "UPDATE " . $this->tableName . " set situacao = '".$situacao."' WHERE idNotaFiscal = ?";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        if($stmt->execute()){

            return true;
        }
        return false;        
    }

    //
    // delete notaFiscal
    function delete(){
    
        $query = "DELETE FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
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

    //
    // delete notaFiscal - notaFiscalItem
    function deleteCompleto(){
    
        $query = "DELETE FROM notaFiscalServicoItem WHERE idNotaFiscal = ?";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        if($stmt->execute()){

            // delete query
            $query = "DELETE FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(1, $this->idNotaFiscal);

            // execute query
            if($stmt->execute()){

                return true;
            }
            return false;
        }
        return false;        
    }

    //
    // delete notaFiscal - notaFiscalItem
    function deleteCompletoTransaction(){
    
        try {

            $this->conn->beginTransaction();

            $query = "DELETE FROM notaFiscalServicoItem WHERE idNotaFiscal = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->idNotaFiscal);
            $stmt->execute();
        
            $query = "DELETE FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->idNotaFiscal);
            $stmt->execute();

            $this->conn->commit();

            return true;
        } catch (PDOException $e) {

            $this->conn->rollBack();
            return false;        
        }
    }

    //
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
        $this->idEmitente = $row['idEmitente'];
        $this->numero = $row['numero'];
        $this->serie = $row['serie'];
        $this->chaveNF = $row['chaveNF'];
        $this->docOrigemTipo = $row['docOrigemTipo'];
        $this->docOrigemNumero = $row['docOrigemNumero'];
        $this->docOrigemParcela = $row['docOrigemParcela'];
        $this->idEntradaSaida = $row['idEntradaSaida'];
        $this->idTomador = $row['idTomador'];
        $this->cfop = $row['cfop'];
        $this->dataInclusao = $row['dataInclusao'];
        $this->dataEmissao = $row['dataEmissao'];
        $this->dataProcessamento = $row['dataProcessamento'];
        $this->situacao = $row['situacao'];
        $this->ambiente = $row['ambiente'];
        $this->textoResposta = $row['textoResposta'];
        $this->textoJustificativa = $row['textoJustificativa'];
        $this->dataCancelamento = $row['dataCancelamento'];
        $this->valorTotal = $row['valorTotal'];
        $this->valorOutrasDespesas = $row['valorOutrasDespesas'];
        $this->valorDesconto = $row['valorDesconto'];
        $this->obsImpostos = $row['obsImpostos'];
        $this->dadosAdicionais = $row['dadosAdicionais'];
        $this->linkNF = $row['linkNF'];
        $this->linkXml = $row['linkXml'];
    }
    
    // check notaFiscal
    function check(){
    
        $query = "SELECT nf.numero, nf.situacao FROM " . $this->tableName . " nf
                  WHERE nf.idNotaFiscal = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->idNotaFiscal=htmlspecialchars(strip_tags($this->idNotaFiscal));
    
        // bind
        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $situacao = $row['situacao'];
        $nuNF = $row['numero'];
    
        return array("existe" => $stmt->rowCount(), "situacao" => $situacao, "numeroNF" => $nuNF);
    }    

    //
    // check notaFiscal Venda.Emitente
    function checkVenda(){
    
        $query = "SELECT nf.idNotaFiscal, nf.numero, nf.situacao FROM " . $this->tableName . " nf
                  WHERE nf.docOrigemNumero = ? AND nf.situacao IN ('T','F') LIMIT 1"; // Pendente Timeout / Faturada
        $stmt = $this->conn->prepare($query);
    
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
        $stmt->bindParam(1, $this->docOrigemNumero);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array("existe" => $stmt->rowCount(), "situacao" => $row['situacao'], "numero" => $row['numero'], "idNotaFiscal" => $row['idNotaFiscal']);
    }    

    //
    // read notaFiscal - Pendentes por Timeout/Serv.Indisponível
    function readPendente(){
    
        // select all query
        $query = "SELECT idNotaFiscal FROM " . $this->tableName . " WHERE situacao = 'T' ORDER BY idNotaFiscal";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // execute query
        $stmt->execute();
    
        return $stmt;
    }

    //
    // read notaFiscal - Pendentes por Timeout/Serv.Indisponível
    function readPendenteDiaMunic($data, $munic){

        $query = "SELECT idNotaFiscal FROM " . $this->tableName . " AS nf, emitente AS e
                  WHERE nf.idEmitente = e.idEmitente AND nf.situacao = 'P' AND 
                        nf.dataInclusao = :data AND e.codigoMunicipio = :idMunic
                  ORDER BY idNotaFiscal";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $stmt->bindParam(":data", $data);
        $stmt->bindParam(":idMunic", $munic);
    
        // execute query
        $stmt->execute();
    
        return $stmt;
    }

    //
    // calcula Imposto Aproximado na Nota (IBPT)
    function calcImpAprox(){

        // update query
        $query = "UPDATE notaFiscalServicoItem AS nfi, itemVenda AS iv, impostoIBPT AS ia
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
                      FROM notaFiscalServicoItem AS nfi WHERE nfi.idNotaFiscal = :idNotaFiscal";

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

                $this->obsImpostos = $msgIBPT;
                // update query
                $query = "UPDATE " . $this->tableName . " SET obsImpostos = :msgIBPT
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