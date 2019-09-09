<?php
class NotaFiscal{
 
    // database connection and table name
    private $conn;
    private $tableName = "notaFiscal";
 
    // object properties
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
    public $naturezaOperacao;
    public $idFinalidade;
    public $dataInclusao;
    public $dataEmissao;
    public $dataProcessamento;
    public $situacao;
    public $ambiente;
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

    // create nota fiscal
    function create(){
    
        // query to insert record
        $query = "INSERT INTO " . $this->tableName . " SET
                    idEmitente=:idEmitente, numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, 
                    cfop=:cfop, naturezaOperacao=:naturezaOperacao, idFinalidade=:idFinalidade, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, dataProcessamento=:dataProcessamento,
                    situacao=:situacao, ambiente=:ambiente,
                    reciboNF=:reciboNF, protocoloNF=:protocoloNF, textoResposta=:textoResposta,
                    textoJustificativa=:textoJustificativa, dataCancelamento=:dataCancelamento, 
                    valorTotalMercadorias=:valorTotalMercadorias, valorTotal=:valorTotal, valorFrete=:valorFrete,
                    valorSeguro=:valorSeguro, valorOutrasDespesas=:valorOutrasDespesas, valorDesconto=:valorDesconto,
                    obsImpostos=:obsImpostos, dadosAdicionais=:dadosAdicionais";
    
        // prepare query
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
        $this->naturezaOperacao=htmlspecialchars(strip_tags($this->naturezaOperacao));
        $this->idFinalidade=htmlspecialchars(strip_tags($this->idFinalidade));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->dataProcessamento=htmlspecialchars(strip_tags($this->dataProcessamento));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
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
        $stmt->bindParam(":naturezaOperacao", $this->naturezaOperacao);
        $stmt->bindParam(":idFinalidade", $this->idFinalidade);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        if (($this->dataProcessamento == "NULL") || ($this->dataProcessamento == "") || ($this->dataProcessamento == "0000-00-00"))
            $stmt->bindValue(":dataProcessamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataProcessamento", $this->dataProcessamento, PDO::PARAM_NULL);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":reciboNF", $this->reciboNF);
        $stmt->bindParam(":protocoloNF", $this->protocoloNF);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        if (($this->dataCancelamento == "NULL") || ($this->dataCancelamento == "") || ($this->dataCancelamento == "0000-00-00"))
            $stmt->bindValue(":dataCancelamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataCancelamento", $this->dataCancelamento, PDO::PARAM_NULL);
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
            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
    }    

    // update emitente
    function update(){
    
        // update query
        $query = "UPDATE " . $this->tableName . " SET
                    idEmitente=:idEmitente, numero=:numero, serie=:serie, chaveNF=:chaveNF, 
                    docOrigemTipo=:docOrigemTipo, docOrigemNumero=:docOrigemNumero, docOrigemParcela=:docOrigemParcela, 
                    idEntradaSaida=:idEntradaSaida, idTomador=:idTomador, 
                    cfop=:cfop, naturezaOperacao=:naturezaOperacao, idFinalidade=:idFinalidade, 
                    dataInclusao=:dataInclusao, dataEmissao=:dataEmissao, dataProcessamento=:dataProcessamento,
                    situacao=:situacao, ambiente=:ambiente,
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
        $this->naturezaOperacao=htmlspecialchars(strip_tags($this->naturezaOperacao));
        $this->idFinalidade=htmlspecialchars(strip_tags($this->idFinalidade));
        $this->dataInclusao=htmlspecialchars(strip_tags($this->dataInclusao));
        $this->dataEmissao=htmlspecialchars(strip_tags($this->dataEmissao));
        $this->dataProcessamento=htmlspecialchars(strip_tags($this->dataProcessamento));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->ambiente=htmlspecialchars(strip_tags($this->ambiente));
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
        $stmt->bindParam(":naturezaOperacao", $this->naturezaOperacao);
        $stmt->bindParam(":idFinalidade", $this->idFinalidade);
        $stmt->bindParam(":dataInclusao", $this->dataInclusao);
        $stmt->bindParam(":dataEmissao", $this->dataEmissao);
        if (($this->dataProcessamento == "NULL") || ($this->dataProcessamento == "") || ($this->dataProcessamento == "0000-00-00"))
            $stmt->bindValue(":dataProcessamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataProcessamento", $this->dataProcessamento, PDO::PARAM_NULL);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":ambiente", $this->ambiente);
        $stmt->bindParam(":reciboNF", $this->reciboNF);
        $stmt->bindParam(":protocoloNF", $this->protocoloNF);
        $stmt->bindParam(":textoResposta", $this->textoResposta);
        $stmt->bindParam(":textoJustificativa", $this->textoJustificativa);
        if (($this->dataCancelamento == "NULL") || ($this->dataCancelamento == "") || ($this->dataCancelamento == "0000-00-00"))
            $stmt->bindValue(":dataCancelamento", NULL, PDO::PARAM_NULL);
        else
            $stmt->bindParam(":dataCancelamento", $this->dataCancelamento, PDO::PARAM_NULL);
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

            return array(true);
        }
        else {

            $aErr = $stmt->errorInfo();
            return array(false, $aErr[2]);
        }
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

    // delete notaFiscal
    function deleteCompleto(){
    
        // delete query
        $query = "DELETE FROM notaFiscalItem WHERE idNotaFiscal = ?";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
        // bind id of record to delete
        $stmt->bindParam(1, $this->idNotaFiscal);
    
        // execute query
        if($stmt->execute()){

            // delete query
            $query = "DELETE FROM " . $this->tableName . " WHERE idNotaFiscal = ?";
        
            // prepare query
            $stmt = $this->conn->prepare($query);
            // bind id of record to delete
            $stmt->bindParam(1, $this->idNotaFiscal);

            // execute query
            if($stmt->execute()){

                return true;
            }

            return false;
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
        $this->naturezaOperacao = $row['naturezaOperacao'];
        $this->idFinalidade = $row['idFinalidade'];
        $this->dataInclusao = $row['dataInclusao'];
        $this->dataEmissao = $row['dataEmissao'];
        $this->dataProcessamento = $row['dataProcessamento'];
        $this->situacao = $row['situacao'];
        $this->ambiente = $row['ambiente'];
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

    //
    // check notaFiscal Venda.Emitente
    function checkVenda(){
    
        // select query
        $query = "SELECT nf.numero, nf.situacao FROM " . $this->tableName . " nf
                  WHERE nf.docOrigemNumero = ? AND nf.situacao IN ('P','F') LIMIT 1"; // Pendente / Faturada
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->docOrigemNumero=htmlspecialchars(strip_tags($this->docOrigemNumero));
    
        // bind
        $stmt->bindParam(1, $this->docOrigemNumero);
    
        // execute query
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $situacao = $row['situacao'];
        $nuNF = $row['numero'];
    
        return array("existe" => $stmt->rowCount(), "situacao" => $situacao, "numeroNF" => $nuNF);
    }    

    //
    // calcula Imposto Aproximado na Nota (IBPT)
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

                $this->obsImpostos = $msgIBPT;
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

    //
    // cria PDF da NFSe PMF
    function printDanfpse($idNotaFiscal, $db) {

        include_once "../../fpdf/qrcode/qrcode.class.php"; 
        include_once "../shared/relatPdfNFe.php";
        include_once "../shared/utilities.php";
        include_once '../objects/notaFiscalItem.php';
        include_once '../objects/emitente.php';
        include_once '../objects/autorizacao.php';
        include_once '../objects/tomador.php';
        include_once '../objects/municipio.php';

        $notaFiscal = new NotaFiscal($db);
        $notaFiscal->idNotaFiscal = $idNotaFiscal;
        $notaFiscal->readOne();

        $notaFiscalItem = new NotaFiscalItem($db);
        $arrayNotaFiscalItem = $notaFiscalItem->read($notaFiscal->idNotaFiscal);

        $autorizacao = new Autorizacao($db);
        $autorizacao->idEmitente = $notaFiscal->idEmitente;
        $autorizacao->readOne();

        $emitente = new Emitente($db);
        $emitente->idEmitente = $notaFiscal->idEmitente;
        $emitente->readOne();

        $tomador = new Tomador($db);
        $tomador->idTomador = $notaFiscal->idTomador;
        $tomador->readOne();

        $municipioEmitente = new Municipio($db);
        $municipioEmitente->codigoUFMunicipio = $emitente->codigoMunicipio;
        $municipioEmitente->readUFMunicipio();

        $municipioTomador = new Municipio($db);
        $municipioTomador->codigoUFMunicipio = $tomador->codigoMunicipio;
        $municipioTomador->readUFMunicipio();

        $utilities = new Utilities();
        //	
        $pdf=new relatPdfNFe('P','mm','form');
        //setlocale(LC_CTYPE, 'pt_BR.ISO-8859-1');
        //setlocale(LC_ALL,'pt_BR');
        $pdf->SetMargins(0,0);
        $pdf->Open();

        $pdf->StartPageGroup();

        //while ($item < $numItens) {
            $pdf->AddPage();
            $pdf->SetMargins(0,0,0);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetFillColor(255);
            $pdf->SetTextColor(0);
            $pdf->SetFont('Arial', '', 7);
            // empresa
            $pdf->Rect(10, 10, 90, 33, 'DF'); // dados empresa
            $pdf->Rect(100, 10, 100, 33, 'DF'); // danfe
            // 
            $pdf->SetFont('Arial', 'B', '10');
            $pdf->SetXY(10,14);
            $pdf->MultiCell(90, 4, utf8_decode($emitente->nome), 0, 'C', 0); 
        //			$pdf->SetFontSize(7);
        //			$pdf->Image('figuras/logo_nf.jpg', 20, 12, 40); // importa uma imagem 
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY(10,23);
            $pdf->Cell(90, 4, utf8_decode($emitente->logradouro.', '.$emitente->numero.' - '.$emitente->complemento), 0, 1, 'C'); 
            $pdf->SetX(10);
            $pdf->Cell(90, 4, utf8_decode($emitente->bairro.' - '.$municipioEmitente->nome.' - '.$emitente->uf.' - '.$utilities->mask($emitente->cep,"#####-###")), 0, 1, 'C'); 
            $pdf->SetX(10);
            $pdf->Cell(90, 4, 'Telefone: '.$emitente->fone, 0, 1, 'C'); 
            $pdf->SetX(10);
        //        $pdf->Cell(90, 4, 'CNPJ: '.formataCnpj($cnpjEmp), 0, 1, 'C'); 
//            $pdf->Cell(90, 4, 'CNPJ: '.$emitente->documento, 0, 1, 'C'); 
            $pdf->Cell(90, 4, 'CNPJ: '.$utilities->mask($emitente->documento,"##.###.###/####-##"), 0, 1, 'C'); 
            $pdf->SetX(10);
            $pdf->Cell(90, 4, 'CMC: '.$utilities->mask($autorizacao->cmc,"###.###-#"), 0, 1, 'C'); 
            //
            // Número da NF
            $pdf->SetFont('Arial', 'B', '10');
            $pdf->SetXY(100,12);
            $pdf->Cell(100, 5, 'DANFPS-E', 0, 1, 'C'); 
            $pdf->SetFont('Arial', 'B', '8');
            $pdf->SetX(100);
            $pdf->Cell(100, 5, utf8_decode('Documento Auxiliar da Nota Fiscal de Prestação de Serviços Eletrônica'), 0, 1, 'L'); 
            $pdf->SetX(100);
            $pdf->Cell(100, 4, utf8_decode('Número: ').$notaFiscal->numero, 0, 1, 'L'); 
            $pdf->SetX(100);
            $pdf->Cell(100, 4, utf8_decode('Autorização: ').$autorizacao->aedf, 0, 1, 'L'); 
            $pdf->SetX(100);

            $dtEm = new DateTime($notaFiscal->dataEmissao);
            $dataEmissao = $dtEm->format('d/m/Y');
            $pdf->Cell(100, 4, utf8_decode('Emissão: ').$dataEmissao, 0, 1, 'L'); 
            $pdf->SetX(100);
            $nuCodVer = wordwrap($notaFiscal->chaveNF, 4, '-', true);
            $pdf->Cell(100, 4, utf8_decode('Código de Verificação: ').$nuCodVer, 0, 1, 'L'); 

            // 
            // destinatário
            $pdf->Rect(10, 49, 160, 8, 'DF'); // razão social
            $pdf->Rect(170, 49, 30, 8, 'DF'); // cfps
            $pdf->Rect(10, 56, 95, 8, 'DF'); // endereço
            $pdf->Rect(105, 56, 65, 8, 'DF'); // bairro
            $pdf->Rect(170, 56, 30, 8, 'DF'); // cep
            $pdf->Rect(10, 63, 75, 8, 'DF'); // município
            $pdf->Rect(85, 63, 20, 8, 'DF'); // uf
            $pdf->Rect(105, 63, 30, 8, 'DF'); // país
            $pdf->Rect(135, 63, 35, 8, 'DF'); // cpf/cnpj
            $pdf->Rect(170, 63, 30, 8, 'DF'); // cmc
            // 
            $pdf->SetFont('Arial', 'B', '6');
            $pdf->SetXY(10,45);
            $pdf->Cell(190, 4, 'Dados do Tomador', 0, 0, 'L'); 
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY(10,49);
            $pdf->Cell(160, 3, utf8_decode('NOME / RAZÃO SOCIAL'), 0, 0, 'L'); 
            $pdf->SetXY(170,49);
            $pdf->Cell(30, 3, 'CFPS', 0, 0, 'L'); 
            $pdf->SetXY(10,56);
            $pdf->Cell(95, 3, utf8_decode('ENDEREÇO'), 0, 0, 'L'); 
            $pdf->SetXY(105,56);
            $pdf->Cell(65, 3, 'BAIRRO / DISTRITO', 0, 0, 'L'); 
            $pdf->SetXY(170,56);
            $pdf->Cell(30, 3, 'CEP', 0, 0, 'L'); 
            $pdf->SetXY(10,63);
            $pdf->Cell(75, 3, utf8_decode('MUNICÍPIO'), 0, 0, 'L'); 
            $pdf->SetXY(85,63);
            $pdf->Cell(20, 3, 'UF', 0, 0, 'L'); 
            $pdf->SetXY(105,63);
            $pdf->Cell(30, 3, utf8_decode('PAÍS'), 0, 0, 'L'); 
            $pdf->SetXY(135,63);
            $pdf->Cell(45, 3, 'CPF/CNPJ/Outros', 0, 0, 'L'); 
            $pdf->SetXY(170,63);
            $pdf->Cell(30, 3, 'CMC', 0, 0, 'L'); 
            //
            $pdf->SetFontSize(8);
            $pdf->SetXY(10,52);
            $pdf->Cell(160, 5, utf8_decode($tomador->nome), 0, 0, 'L'); 
            $pdf->SetXY(170,52);
            $pdf->Cell(30, 5, $notaFiscal->cfop, 0, 0, 'L'); 
            $pdf->SetXY(10,59);

            $enderecoTomador = $tomador->logradouro;
            if ($tomador->numero > 0)
                $enderecoDest .= ' n.:'.$tomador->numero;
            if ($tomador->complemento > '')
                $enderecoDest .= ' - '.$tomador->complemento;
            $pdf->CellFitScale(95, 5, utf8_decode($enderecoTomador), 0, 0, 'L'); 
            $pdf->SetXY(105,59);
            $pdf->Cell(65, 5, utf8_decode($tomador->bairro), 0, 0, 'L'); 
            $pdf->SetXY(170,59);
            $pdf->Cell(30, 5, $utilities->mask($tomador->cep,"#####-###"), 0, 0, 'L'); 
            $pdf->SetXY(10,67);
            $pdf->Cell(75, 5, utf8_decode($municipioTomador->nome), 0, 0, 'L'); 
            $pdf->SetXY(85,67);
            $pdf->Cell(20, 5, $tomador->uf, 0, 0, 'C'); 
            $pdf->SetXY(105,67);
            $pdf->Cell(30, 5, "BRASIL", 0, 0, 'L'); 
            $pdf->SetXY(135,67);
            $docTomador = $tomador->documento;
            if (strlen($docTomador) == 14)
                $docTomador = $utilities->mask($docTomador, "##.###.###/####-##");
            else if (strlen($docTomador) == 11)
                $docTomador = $utilities->mask($docTomador, "###.###.###-##");
            $pdf->Cell(45, 5, $docTomador, 0, 0, 'L'); 
            $pdf->SetXY(170,67);
            $pdf->Cell(30, 5, '', 0, 0, 'L'); 
            //
            // itens
            $pdf->Rect(10, 77, 20, 5, 'DF'); // código
            $pdf->Rect(10, 77, 20, 145, 'DF'); 
            $pdf->Rect(30, 77, 85, 5, 'DF'); // descrição
            $pdf->Rect(30, 77, 85, 145, 'DF'); // descrição
            $pdf->Rect(115, 77, 8, 5, 'DF'); // cst
            $pdf->Rect(115, 77, 8, 145, 'DF'); // cst
            $pdf->Rect(123, 77, 10, 5, 'DF'); // alíq. icms
            $pdf->Rect(123, 77, 10, 145, 'DF'); // alíq. icms
            $pdf->Rect(133, 77, 25, 5, 'DF'); // valor unitário
            $pdf->Rect(133, 77, 25, 145, 'DF'); // valor unitário
            $pdf->Rect(158, 77, 12, 5, 'DF'); // quantidade
            $pdf->Rect(158, 77, 12, 145, 'DF'); // quantidade
            $pdf->Rect(170, 77, 30, 5, 'DF'); // valor total
            $pdf->Rect(170, 77, 30, 145, 'DF'); // valor total
            //
            $pdf->SetFont('Arial', 'B', '6');
            $pdf->SetXY(10,73);
            $pdf->Cell(190, 4, utf8_decode('Dados do(s) Serviço(s)'), 0, 0, 'L'); 
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY(10,77);
            $pdf->Cell(20, 5, utf8_decode('Cód.Atividade'), 1, 0, 'L'); 
            $pdf->SetXY(30,77);
            $pdf->Cell(85, 5, utf8_decode('(Descrição CNAE) Descrição do Serviço'), 1, 0, 'L'); 
            $pdf->SetXY(115,77);
            $pdf->Cell(8, 5, 'CST', 1, 0, 'C'); 
            $pdf->SetXY(123,77);
            $pdf->Cell(10, 5, utf8_decode('Alíq.'), 1, 0, 'C'); 
            $pdf->SetXY(133,77);
            $pdf->Cell(25, 5, utf8_decode('Valor Unitário'), 1, 0, 'C'); 
            $pdf->SetXY(158,77);
            $pdf->Cell(12, 5, 'Qtde', 1, 0, 'C'); 
            $pdf->SetXY(170,77);
            $pdf->Cell(30, 5, 'Valor Total', 1, 0, 'C'); 
            //
            // -------------------- ITENS DA NOTA FISCAL ------------------------
            $pdf->SetY(83);
            $nuLinhas = 0; $posY=83;
            //

            $vlTotServ = 0;
            $vlTotBC = 0; 
            $vlTotISS = 0; 
            $vlBaseSubst = 0;
            $vlSubst = 0;

            foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {
                $vlTotServ += $notaFiscalItem->valorTotal;
                $vlTotBC += $notaFiscalItem->valorBCIss; 
                $vlTotISS += $notaFiscalItem->valorIss; 
                $notaFiscalItem->readItemVenda();

        //        $nmProd = '('.$rI['nmcnae'].') '.$rI['nmproduto'];
                $nmProd = '('.$notaFiscalItem->descricaoCnae.') '.$notaFiscalItem->descricaoItemVenda;
                $nlDescr = $pdf->numLines(85, $nmProd);
                $nlObs = 0;
        //        if ($rI['nmobs'] > '')
        //            $nlObs = $pdf->numLines(85, $rI['nmobs']);
                $altItem = $nlDescr + $nlObs;
                $nuLinhas += $altItem;

                if ($nuLinhas >= 30) {
                    $rI=mysql_data_seek($execItens, $item);
                    break;
                }

                $pdf->SetXY(10, $posY);
                $y = $pdf->GetY();
                $nuProd = $notaFiscalItem->cnae;
        //				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 12, ($altItem*4)); 
                $pdf->CellFitScale(20, 4, $nuProd, 0, 0, 'C'); 

        //				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 58, ($altItem*4)); 
                $pdf->MultiCell(85, 4, utf8_decode($nmProd), 0, 'L', 0); 

                /*
                if ($notaFiscalItem->obs '') {
                    $pdf->SetX(27);
                    $pdf->MultiCell(85, 4, $rI['nmobs'], 0, 'L', 0); 
                }
                */
                $posY = $pdf->GetY();

                $qtdItem = number_format($notaFiscalItem->quantidade,0,',','.'); 
                $vlUnit = number_format($notaFiscalItem->valorUnitario,2,',','.'); 
                $vlTotItem = number_format($notaFiscalItem->valorTotal,2,',','.'); 

        //				$pdf->Rect(91, $y, 5, ($altItem*4)); 
                $pdf->SetXY(115,$y);
                $pdf->Cell(8, 4, $notaFiscalItem->cstIss, 0, 0, 'C'); // cst/csosn
        //				$pdf->Rect(96, $y, 7, ($altItem*4)); 

        //				$pdf->Rect(186, $y, 7, ($altItem*3.5)); 
                $pdf->SetXY(123,$y);
                $pdf->CellFitScale(10, 4, number_format($notaFiscalItem->taxaIss,2,',','.'), 0, 0, 'R');

        //				$pdf->Rect(121, $y, 14, ($altItem*3.5)); 
                $pdf->SetXY(133,$y);
                $pdf->Cell(25, 4, $vlUnit, 0, 0, 'R'); 
        //				$pdf->Rect(110, $y, 11, ($altItem*4)); 
                $pdf->SetXY(158,$y);
                $pdf->Cell(12, 4, $qtdItem, 0, 0, 'C'); 
        //				$pdf->Rect(135, $y, 16, ($altItem*4)); 
                $pdf->SetXY(170,$y);
                $pdf->Cell(30, 4, $vlTotItem, 0, 0, 'R'); 
                $item++;

            }

            // impostos serviços
            $pdf->Rect(10, 228, 38, 9, 1, 'DF'); // base calc. icms
            $pdf->Rect(48, 228, 38, 9, 1, 'DF'); // valor icms
            $pdf->Rect(86, 228, 38, 9, 1, 'DF'); // base calc. icms subst.
            $pdf->Rect(124, 228, 38, 9, 1, 'DF'); // valor icms subst.
            $pdf->Rect(162, 228, 38, 9, 1, 'DF'); // valor icms subst.
            //
            $pdf->SetFont('Arial', 'B', '6');
            $pdf->SetXY(10,224);
            $pdf->Cell(190, 4, utf8_decode('Cálculo do Imposto'), 0, 0, 'L'); 
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY(10,228);
            $pdf->Cell(38, 3, utf8_decode('Base de Cálculo do ISSQN'), 0, 0, 'C'); 
            $pdf->SetXY(48,228);
            $pdf->Cell(38, 3, 'Valor do ISSQN', 0, 0, 'C'); 
            $pdf->SetXY(86,228);
            $pdf->Cell(38, 3, utf8_decode('Base de Cálculo do ISSQN Subst.'), 0, 0, 'C'); 
            $pdf->SetXY(124,228);
            $pdf->Cell(38, 3, 'Valor do ISSQN Subst.', 0, 0, 'C'); 
            $pdf->SetXY(162,228);
            $pdf->Cell(38, 3, utf8_decode('Valor Total dos Serviços'), 0, 0, 'C'); 
            //
            $pdf->SetFontSize(9);
            $pdf->SetXY(10,232);
            $pdf->Cell(38, 5, 'R$ '.number_format($vlTotBC,2,',','.'), 0, 0, 'C'); 
            $pdf->SetXY(48,232);
            $pdf->Cell(38, 5, 'R$ '.number_format($vlTotISS,2,',','.'), 0, 0, 'C'); 
            $pdf->SetXY(86,232);
            $pdf->Cell(38, 5, 'R$ '.number_format($vlBaseSubst,2,',','.'), 0, 0, 'C'); 
            $pdf->SetXY(124,232);
            $pdf->Cell(38, 5, 'R$ '.number_format($vlSubst,2,',','.'), 0, 0, 'C'); 
            $pdf->SetXY(162,232);
            $pdf->Cell(38, 5, 'R$ '.number_format($notaFiscal->valorTotal,2,',','.'), 0, 0, 'C'); 

            // dados complementares
            $pdf->Rect(10, 243, 190, 17, 1, 'DF'); // informações complementares
            //
            $pdf->SetFont('Arial', 'B', '6');
            $pdf->SetXY(10,239);
            $pdf->Cell(190, 4, 'Dados Adicionais', 0, 0, 'L'); 

            $pdf->SetFont('Arial', '', '7');
            $pdf->SetXY(10,244);
            if ($notaFiscal->obsImpostos != '') {
        //				$pdf->SetX(11);
                $pdf->MultiCell(190, 3, $notaFiscal->obsImpostos, 0, 'L', 0); 
            }

            // dados complementares
            $pdf->Rect(10, 263, 83, 20, 1, 'DF'); // informações complementares
            $pdf->Rect(95, 263, 105, 20, 1, 'DF'); // reservado ao fisco
            //
            $pdf->SetFont('Arial', '', '6');
            $pdf->SetXY(10,264);
            $pdf->CellFitScale(83, 4, utf8_decode('DANFPS-E DOCUMENTO AUXILIAR DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇOS ELETRÔNICA'), 0, 1, 'L'); 
            $pdf->SetFont('Arial', '', '7');
            $pdf->SetX(10);
            $pdf->Cell(85, 4, utf8_decode('SIGNATÁRIO: MUNICÍPIO DE FLORIANÓPOLIS'), 0, 1, 'L'); 
            $pdf->SetX(10);
            $pdf->Cell(85, 4, utf8_decode('CARIMBO DO TEMPO: PREFEITURA MUNICIPAL DE FLORIANÓPOLIS'), 0, 1, 'L'); 
            $pdf->SetX(10);
            $dtC = new DateTime($notaFiscal->dataProcessamento);
            $dataCarimbo = $dtC->format('d/m/Y H:i:s');
            $pdf->Cell(85, 4, 'DATA DO CARIMBO: '.$dataCarimbo, 0, 0, 'L'); 

            $txt2 = 'A VALIDADE E AUTENTICIDADE DESTE DOCUMENTO AUXILIAR DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇO ELETRÔNICA PODERÃO SER COMPROVADAS MEDIANTE CONSULTA À PÁGINA DA';
            $txt2 .= 'SECRETARIA MUNICIPAL DA FAZENDA - SMF NA INTERNET, NO ENDEREÇO portal.pmf.sc.gov.br/sites/notaeletronica, EM VERIFICAR AUTENTICIDADE >> PRODUÇÃO, ';
            $txt2 .= 'INFORMANDO O CÓDIGO DE VERIFICAÇÃO: '.$notaFiscal->chaveNF.' E O NÚMERO DE INSCRIÇÃO DO EMITENTE NO CADASTRO MUNICIPAL DE CONTRIBUINTES - CMC: '.$autorizacao->cmc;
            $pdf->SetFont('Arial', '', '6');
            $pdf->SetXY(95,264);
            $pdf->MultiCell(105, 3, utf8_decode($txt2), 0, 'L', 0); 

            if ($notaFiscal->ambiente == "P") // PRODUÇÃO
                $chaveQR = 'http://nfps-e.pmf.sc.gov.br/consulta-frontend/#!/consulta?cod='.$notaFiscal->chaveNF.'&cmc='.$autorizacao->cmc;
            else // HOMOLOGAÇÃO
                $chaveQR = 'http://nfps-e-hml.pmf.sc.gov.br/consulta-frontend/#!/consulta?cod='.$notaFiscal->chaveNF.'&cmc='.$autorizacao->cmc;
            $qrcode = new QRcode($chaveQR, 'M'); 
            $qrcode->disableBorder();
            $qrcode->displayFPDF(&$pdf, 175, 22, 20, $background=array(255,255,255), $color=array(0,0,0));

            //
            if ($notaFiscal->situacao == 'X') {
                $pdf->SetFont('Arial','B',40);
                $pdf->SetTextColor(240,0,0);
                $pdf->Rotate(45,48,192);
                $pdf->Text(30,190,'C A N C E L A D A');
                $pdf->Rotate(0);
            }
            //
            else if ($notaFiscal->ambiente == 'H') {
                $pdf->SetFont('Arial','B',40);
                $pdf->SetTextColor(255,100,100);
                $pdf->Rotate(45,48,192);
                $pdf->Text(30,190,utf8_decode('VERSÃO DE HOMOLOGAÇÃO'));
                $pdf->Text(70,205,utf8_decode('(sem valor fiscal)'));
                $pdf->Rotate(0);
            }
        //}

        $dirPdf = "arquivosNFSe/".$emitente->documento."/danfpse/";
        $arqPdf = $emitente->documento."_".substr(str_pad($notaFiscal->numero,8,'0',STR_PAD_LEFT),0,8)."-nfse.pdf";
        $pdf->Output("../".$dirPdf.$arqPdf,'F');

        return $dirPdf.$arqPdf;
    }

}
?>