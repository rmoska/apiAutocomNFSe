<?php 

include_once "../../fpdf/qrcode/qrcode.class.php"; 
include_once "../shared/relatPdfNFe.php";

include_once '../objects/notaFiscalItem.php';
include_once '../objects/emitente.php';
include_once '../objects/municipio.php';

//$emitente = new Emitente($db);
//$emitente->idEmitente = $notaFiscal->idEmitente;
//$emitente->readOne();
$municipioEmitente = new Municipio($db);
$municipioEmitente = $emitente->idCodigoMunicipio;
$municipioEmitente->readUFMunicipio();

/*
$notaFiscalItem = new NotaFiscalItem($db);
$notaFiscalItem->idNotaFiscal;
$stmt = $notaFiscalItem->readItemVenda();

if($stmt->rowCount()>0){

    // emitente array
    $arrayNotaFiscalItem=array();

    // retrieve our table contents
    while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)){
        echo $row;
    }

}
*/

//$tomador = new Tomador($db);
//$tomador->idTomador = $notaFiscal->idTomador;
$municipioTomador = new Municipio($db);
$municipioTomador = $tomador->idCodigoMunicipio;
$municipioTomador->readUFMunicipio();

/*    
// ---------------------------------------------------------------------------
// ------------------------------ DADOS EMPRESA ------------------------------
$sqlEmp = "SELECT c.nmrazsocial, c.nucpfcgc, c.nmendereco, c.numero, c.nmcomplemento,
                                    c.nmbairro, m.nome AS nmcidade, c.nmsiglaestado, c.nufone, c.nucep, cf.nucmc, cf.nuaedf
                    FROM confignfe as cf, cliente AS c, municipio AS m, estado AS uf
                    WHERE c.nmsiglaestado=uf.sigla AND c.numunicipio=m.codigo AND m.cduf=uf.codigo AND 
                                c.nucliente = '1'";
$execEmp = mysql_query($sqlEmp); 
$rE = mysql_fetch_array($execEmp);
$nuCMC = $rE["nucmc"];
$nuAEDF = $rE["nuaedf"];
$nmEmpresa = $rE["nmrazsocial"];
$fantasiaEmp = $rE["nmfantasia"];
$cnpjEmp = $rE["nucpfcgc"];
$inscrEstEmp = $rE["nuinscrestadual"];
$enderecoEmp = $rE["nmendereco"];
$numeroEmp = $rE["numero"];
$complEndEmp = $rE["nmcomplemento"];
$cepEmp = $rE["nucep"];
$municipioEmp = $rE["nmcidade"];
$bairroEmp = $rE["nmbairro"];
$ufEmp = $rE["nmsiglaestado"];
$foneEmp = $rE["nufone"];

$item = 0;
// --------------------------------------------------------------------------------
// ------------------------------ DADOS CABEÇALHO NF ------------------------------
$sqlCabNF = "SELECT nuseqnota, nunota, nmserie, nuchavenfe, nf.identradasaida, dtprocessamento,
                                        nmsituacao, idsitnfe, nucfop, tpcfop, 
                                        tpdestinatario, nudestinatario, nutpdocorigem, nudocorigem,
                                        IF(IFNULL(nf.nmdescricao,'')='', no.nmdescricao, nf.nmdescricao) AS nmdescricao, 
                                        no.idfuncao, dtemissao, dtinclusao, hrinclusao, vltotnota, vltotmercadorias, vlbaseiss, vltotiss, 
                                        nf.vldesconto, vltotimpfedaprox, vltotimpestaprox, vltotimpmunaprox, nf.nmobs, nf.nmobsretencao
                        FROM (notafiscal AS nf)
                        LEFT JOIN tiponaturezaoperacao AS no ON (nucfop = no.cdnatope)
                        WHERE nuseqnota = '$nuNF'";
$execCabNF = mysql_query($sqlCabNF,$con);
$regCab = mysql_fetch_array($execCabNF);
$nuNota = $regCab["nunota"];
$editSerie = $regCab["nmserie"];
$nuChaveNFe = $regCab["nuchavenfe"];
$idSitNFe = $regCab["idsitnfe"];
$idES = $regCab["identradasaida"];
$tpDest = $regCab["tpdestinatario"];
$nuDest = $regCab["nudestinatario"];
$editNmNatOper = $regCab["nmdescricao"];
$nuCfps = $regCab["nucfop"];
$editVlTotalNota = number_format($regCab["vltotnota"],2,',','.');
$editDtEmissao = formataDtBr($regCab["dtemissao"]);
if(!isset($editDtEmissao) || strlen($editDtEmissao)==0 || ($editDtEmissao=='00/00/0000'))
    $editDtEmissao = date("d/m/Y"); 
if(!isset($editDtInclusao) || strlen($editDtInclusao)==0 || ($editDtInclusao=='00/00/0000')) {
    $editDtInclusao = date("d/m/Y"); 
    $editHrInclusao = date("h:i"); 
}
$dtCarimbo = formataDtHrBr($regCab["dtprocessamento"]);
$editVlBaseIss = number_format($regCab["vlbaseiss"],2,',','.');
$editVlss = number_format($regCab["vltotiss"],2,',','.');
$editVlBaseSubst = number_format($regCab["vlbasesubst"],2,',','.');
$editVlSubst = number_format($regCab["vlicmssubst"],2,',','.');
$editVlDesconto = number_format($regCab["vldesconto"],2,',','.');
if ($idES != "E" && $idES != "S")
    $idES = "S";
//
$nmMsg1 = trim(limpaCaractNFe(retiraAcentos($regCab["nmobs"])));
$nmMsg2 = trim(limpaCaractNFe(retiraAcentos($regCab["nmobsretencao"])));
//
if ($nmMsg1>'')
    $nmInfoAdic = $nmMsg1.'    -    ';
if ($nmMsg2>'')
    $nmInfoAdic .= $nmMsg2.'    -    ';
$nmInfoAdic = substr($nmInfoAdic,0,256);

//
// ------------------------------ DADOS CLIENTE ------------------------------
$sqlCli = "SELECT c.nucliente AS nudest, c.nmrazsocial, c.nmfantasia, 
                                    c.nucpfcgc, c.nuinscrestadual, c.nmendereco, c.numero, c.nmcomplemento,
                                    c.nmbairro, m.nome AS nmcidade, c.nmsiglaestado, c.nufone, c.nucep
                    FROM cliente AS c
                    LEFT JOIN estado AS e ON (c.nmsiglaestado = e.sigla)
                    LEFT JOIN municipio AS m ON (e.codigo = m.cduf AND c.numunicipio = m.codigo)
                    WHERE c.nucliente = '$nuDest'";
$execDest = mysql_query($sqlCli); 
if (mysql_num_rows($execDest) > 0) {
    $rD = mysql_fetch_array($execDest);
    $nuDest = $rD["nudest"];
    $nomeDest = $rD["nmrazsocial"];
    $nmFantasiaDest = $rD["nmfantasia"];
    $cpfCnpjDest = $rD["nucpfcgc"];
    $inscrEstDest = $rD["nuinscrestadual"];
    $enderecoDest = $rD["nmendereco"];
    if ($rD['numero'] > 0)
        $enderecoDest .= ' n.:'.$rD['numero'];
    if ($rD['nmcomplemento'] > '')
        $enderecoDest .= ' - '.$rD['nmcomplemento'];
    $bairroDest = $rD["nmbairro"];
    $cepDest = $rD["nucep"];
    $municipioDest = $rD["nmcidade"];
    $ufDest = $rD["nmsiglaestado"];
    $foneDest = $rD["nufone"];
    $nmPais = 'BRASIL';
}

// ----------------------------------------------------------------------------
// ------------------------------ DADOS ITENS NF ------------------------------
$sqlItens = "SELECT p.nuproduto, p.nmproduto, p.qtunidade AS qtformavenda, tas.cdatividade, tas.nucnae, tas.nmdescricao AS nmcnae,
                                        p.nuncm, nfi.nuordem, nfi.qtunidade, nfi.vlunitliq, nfi.vltotliq, 
                                        nfi.nucodtribiss AS nucst, nfi.vlbaseiss, nfi.txaliquotaiss AS txiss, nfi.vliss										
                        FROM notafiscalitem AS nfi, produto AS p, tipoatividadeservico AS tas  
                        WHERE nfi.nuproduto = p.nuproduto AND nfi.cdatividadeserv = tas.cdatividade AND nfi.nuseqnota = '$nuNF'
                        ORDER BY p.nmproduto, p.nuproduto";
$execItens = mysql_query($sqlItens,$con);//executa busca
$numItens = mysql_num_rows($execItens);

*/

//	
$pdf=new relatPdfNFe('P','mm','form');
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
    $pdf->MultiCell(90, 4, $emitente->nome, 0, 'C', 0); 
//			$pdf->SetFontSize(7);
//			$pdf->Image('figuras/logo_nf.jpg', 20, 12, 40); // importa uma imagem 
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10,23);
    $pdf->Cell(90, 4, $emitente->logradouro.', '.$emitente->numero.' - '.$emitente->complemento, 0, 1, 'C'); 
    $pdf->SetX(10);
    $pdf->Cell(90, 4, $emitente->bairro.' - '.$emitente->municipioNome.' - '.$emitente->uf.' - '.$emitente->cep, 0, 1, 'C'); 
    $pdf->SetX(10);
    $pdf->Cell(90, 4, 'Telefone: '.$emitente->fone, 0, 1, 'C'); 
    $pdf->SetX(10);
//        $pdf->Cell(90, 4, 'CNPJ: '.formataCnpj($cnpjEmp), 0, 1, 'C'); 
    $pdf->Cell(90, 4, 'CNPJ: '.$emitente->documento, 0, 1, 'C'); 
    $pdf->SetX(10);
    $pdf->Cell(90, 4, 'CMC: '.$emitente->cmc, 0, 1, 'C'); 
    //
    // Número da NF
    $pdf->SetFont('Arial', 'B', '10');
    $pdf->SetXY(100,12);
    $pdf->Cell(100, 5, 'DANFPS-E', 0, 1, 'C'); 
    $pdf->SetFont('Arial', 'B', '8');
    $pdf->SetX(100);
    $pdf->Cell(100, 5, 'Documento Auxiliar da Nota Fiscal de Prestação de Serviços Eletrônica', 0, 1, 'L'); 
    $pdf->SetX(100);
    $pdf->Cell(100, 4, 'Número: '.$notaFiscal->numero, 0, 1, 'L'); 
    $pdf->SetX(100);
    $pdf->Cell(100, 4, 'Autorização: '.$notaFiscal->aedf, 0, 1, 'L'); 
    $pdf->SetX(100);
    $pdf->Cell(100, 4, 'Emissão: '.$notaFiscal->dataEmissao, 0, 1, 'L'); 
    $pdf->SetX(100);
    $nuCodVer = wordwrap($notaFiscal->chaveNF, 4, '-', true);
    $pdf->Cell(100, 4, 'Código de Verificação: '.$nuCodVer, 0, 1, 'L'); 

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
    $pdf->Cell(160, 3, 'NOME / RAZÃO SOCIAL', 0, 0, 'L'); 
    $pdf->SetXY(170,49);
    $pdf->Cell(30, 3, 'CFPS', 0, 0, 'L'); 
    $pdf->SetXY(10,56);
    $pdf->Cell(95, 3, 'ENDEREÇO', 0, 0, 'L'); 
    $pdf->SetXY(105,56);
    $pdf->Cell(65, 3, 'BAIRRO / DISTRITO', 0, 0, 'L'); 
    $pdf->SetXY(170,56);
    $pdf->Cell(30, 3, 'CEP', 0, 0, 'L'); 
    $pdf->SetXY(10,63);
    $pdf->Cell(75, 3, 'MUNICÍPIO', 0, 0, 'L'); 
    $pdf->SetXY(85,63);
    $pdf->Cell(20, 3, 'UF', 0, 0, 'L'); 
    $pdf->SetXY(105,63);
    $pdf->Cell(30, 3, 'PAÍS', 0, 0, 'L'); 
    $pdf->SetXY(135,63);
    $pdf->Cell(45, 3, 'CPF/CNPJ/Outros', 0, 0, 'L'); 
    $pdf->SetXY(170,63);
    $pdf->Cell(30, 3, 'CMC', 0, 0, 'L'); 
    //
    $pdf->SetFontSize(8);
    $pdf->SetXY(10,52);
    $pdf->Cell(160, 5, $tomador->nome, 0, 0, 'L'); 
    $pdf->SetXY(170,52);
    $pdf->Cell(30, 5, $notaFiscal->cfop, 0, 0, 'L'); 
    $pdf->SetXY(10,59);
    $pdf->CellFitScale(95, 5, $tomador->logradouro, 0, 0, 'L'); 
    $pdf->SetXY(105,59);
    $pdf->Cell(65, 5, $tomador->bairro, 0, 0, 'L'); 
    $pdf->SetXY(170,59);
    $pdf->Cell(30, 5, $tomador->cep, 0, 0, 'L'); 
    $pdf->SetXY(10,67);
    $pdf->Cell(75, 5, $municipioTomador->nome, 0, 0, 'L'); 
    $pdf->SetXY(85,67);
    $pdf->Cell(20, 5, $tomador->uf, 0, 0, 'C'); 
    $pdf->SetXY(105,67);
    $pdf->Cell(30, 5, "", 0, 0, 'L'); 
    $pdf->SetXY(135,67);
//        $pdf->Cell(45, 5, formataDocto($cpfCnpjDest), 0, 0, 'L'); 
    $pdf->Cell(45, 5, $tomador->documento, 0, 0, 'L'); 
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
    $pdf->Cell(190, 4, 'Dados do(s) Serviço(s)', 0, 0, 'L'); 
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(10,77);
    $pdf->Cell(20, 5, 'Cód.Atividade', 1, 0, 'L'); 
    $pdf->SetXY(30,77);
    $pdf->Cell(85, 5, '(Descrição CNAE) Descrição do Serviço', 1, 0, 'L'); 
    $pdf->SetXY(115,77);
    $pdf->Cell(8, 5, 'CST', 1, 0, 'C'); 
    $pdf->SetXY(123,77);
    $pdf->Cell(10, 5, 'Alíq.', 1, 0, 'C'); 
    $pdf->SetXY(133,77);
    $pdf->Cell(25, 5, 'Valor Unitário', 1, 0, 'C'); 
    $pdf->SetXY(158,77);
    $pdf->Cell(12, 5, 'Qtde', 1, 0, 'C'); 
    $pdf->SetXY(170,77);
    $pdf->Cell(30, 5, 'Valor Total', 1, 0, 'C'); 
    //
    // -------------------- ITENS DA NOTA FISCAL ------------------------
    $pdf->SetY(83);
    $nuLinhas = 0; $posY=83;
    //

    $vlTotBC = 0; 
    $vlTotISS = 0; 
    $vlBaseSubst = 0;
    $vlSubst = 0;
/*
    for ($x = $item; $x < $numItens; $x++){

        $rI=mysql_fetch_array($execItens);

        if ($rI['nucst'] == '0') {
            $vlTotBC += $rI['vlbaseiss']; 
            $vlTotISS += $rI['vliss']; 
        }

        $nmProd = '('.$rI['nmcnae'].') '.$rI['nmproduto'];
        $nlDescr = $pdf->numLines(85, $nmProd);
        $nlObs = 0;
        if ($rI['nmobs'] > '')
            $nlObs = $pdf->numLines(85, $rI['nmobs']);
        $altItem = $nlDescr + $nlObs;
        $nuLinhas += $altItem;

        if ($nuLinhas >= 30) {
            $rI=mysql_data_seek($execItens, $item);
            break;
        }

        $pdf->SetXY(10, $posY);
        $y = $pdf->GetY();
        $nuProd = $rI['cdatividade'];
//				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 12, ($altItem*4)); 
        $pdf->CellFitScale(20, 4, $nuProd, 0, 0, 'C'); 

//				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 58, ($altItem*4)); 
        $pdf->MultiCell(85, 4, $nmProd, 0, 'L', 0); 

        if ($rI['nmobs'] > '') {
            $pdf->SetX(27);
            $pdf->MultiCell(85, 4, $rI['nmobs'], 0, 'L', 0); 
        }
        $posY = $pdf->GetY();

        $qtdItem = number_format($rI['qtunidade'],0,',','.'); 
        $vlUnit = number_format($rI['vlunitliq'],2,',','.'); 
        $vlTotItem = number_format($rI['vltotliq'],2,',','.'); 

//				$pdf->Rect(91, $y, 5, ($altItem*4)); 
        $pdf->SetXY(115,$y);
        $pdf->Cell(8, 4, $rI['nucst'], 0, 0, 'C'); // cst/csosn
//				$pdf->Rect(96, $y, 7, ($altItem*4)); 

//				$pdf->Rect(186, $y, 7, ($altItem*3.5)); 
        $pdf->SetXY(123,$y);
        $pdf->CellFitScale(10, 4, number_format($rI['txiss'],2,',','.'), 0, 0, 'R');

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
*/    


    // impostos serviços
    $pdf->Rect(10, 228, 38, 9, 1, 'DF'); // base calc. icms
    $pdf->Rect(48, 228, 38, 9, 1, 'DF'); // valor icms
    $pdf->Rect(86, 228, 38, 9, 1, 'DF'); // base calc. icms subst.
    $pdf->Rect(124, 228, 38, 9, 1, 'DF'); // valor icms subst.
    $pdf->Rect(162, 228, 38, 9, 1, 'DF'); // valor icms subst.
    //
    $pdf->SetFont('Arial', 'B', '6');
    $pdf->SetXY(10,224);
    $pdf->Cell(190, 4, 'Cálculo do Imposto', 0, 0, 'L'); 
    $pdf->SetFont('Arial', '', 5);
    $pdf->SetXY(10,228);
    $pdf->Cell(38, 3, 'Base de Cálculo do ISSQN', 0, 0, 'C'); 
    $pdf->SetXY(48,228);
    $pdf->Cell(38, 3, 'Valor do ISSQN', 0, 0, 'C'); 
    $pdf->SetXY(86,228);
    $pdf->Cell(38, 3, 'Base de Cálculo do ISSQN Subst.', 0, 0, 'C'); 
    $pdf->SetXY(124,228);
    $pdf->Cell(38, 3, 'Valor do ISSQN Subst.', 0, 0, 'C'); 
    $pdf->SetXY(162,228);
    $pdf->Cell(38, 3, 'Valor Total dos Serviços', 0, 0, 'C'); 
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
    $pdf->Cell(38, 5, 'R$ '.$notaFiscal->valorTotal, 0, 0, 'C'); 

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
    $pdf->CellFitScale(83, 4, 'DANFPS-E DOCUMENTO AUXILIAR DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇOS ELETRÔNICA', 0, 1, 'L'); 
    $pdf->SetFont('Arial', '', '7');
    $pdf->SetX(10);
    $pdf->Cell(85, 4, 'SIGNATÁRIO: MUNICÍPIO DE FLORIANÓPOLIS', 0, 1, 'L'); 
    $pdf->SetX(10);
    $pdf->Cell(85, 4, 'CARIMBO DO TEMPO: PREFEITURA MUNICIPAL DE FLORIANÓPOLIS', 0, 1, 'L'); 
    $pdf->SetX(10);
//        $pdf->Cell(85, 4, 'DATA DO CARIMBO: '.$dtCarimbo, 0, 0, 'L'); 
    $pdf->Cell(85, 4, 'DATA DO CARIMBO: '.$notaFiscal->dataProcessamento, 0, 0, 'L'); 

    $txt2 = 'A VALIDADE E AUTENTICIDADE DESTE DOCUMENTO AUXILIAR DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇO ELETRÔNICA PODERÃO SER COMPROVADAS MEDIANTE CONSULTA À PÁGINA DA';
    $txt2 .= 'SECRETARIA MUNICIPAL DA FAZENDA - SMF NA INTERNET, NO ENDEREÇO portal.pmf.sc.gov.br/sites/notaeletronica, EM VERIFICAR AUTENTICIDADE >> PRODUÇÃO, ';
    $txt2 .= 'INFORMANDO O CÓDIGO DE VERIFICAÇÃO: '.$notaFiscal->chaveNF.' E O NÚMERO DE INSCRIÇÃO DO EMITENTE NO CADASTRO MUNICIPAL DE CONTRIBUINTES - CMC: '.$notaFiscal->cmc;
    $pdf->SetFont('Arial', '', '6');
    $pdf->SetXY(95,264);
    $pdf->MultiCell(105, 3, $txt2, 0, 'L', 0); 

    $chaveQR = 'http://nfps-e.pmf.sc.gov.br/consulta-frontend/#!/consulta?cod='.$notaFiscal->chaveNF.'&cmc='.$notaFiscal->cmc;
    $qrcode = new QRcode($chaveQR, 'M'); 
    $qrcode->disableBorder();
    $qrcode->displayFPDF(&$pdf, 175, 22, 20, $background=array(255,255,255), $color=array(0,0,0));

    //
    if ($idSitNFe=='X') {
        $pdf->SetFont('Arial','B',40);
        $pdf->SetTextColor(240,0,0);
        $pdf->Rotate(45,48,192);
        $pdf->Text(30,190,'C A N C E L A D A');
        $pdf->Rotate(0);
    }
//}

$dirPdf = "arquivosNFSe/".$emitente->documento."/danfpse/";
$arqPdf = $emitente->documento."_".substr(str_pad($notaFiscal->numero,8,'0',STR_PAD_LEFT),0,8)."-nfse.pdf";
$pdf->Output("../".$dirPdf.$arqPdf,'F');

return $arqPdf;

?>