<?
// 
function danfeNFe($nuNF) {
    //
    include_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

//	require_once("funcoes.php");
	require_once("../../fpdf/qrcode/qrcode.class.php"); 

    // ---------------------------------------------------------------------------
	// ------------------------------ DADOS EMPRESA ------------------------------
/*
$sqlEmp = "SELECT c.nmrazsocial, c.nucpfcgc, c.nmendereco, c.numero, c.nmcomplemento,
										c.nmbairro, m.nome AS nmcidade, c.nmsiglaestado, c.nufone, c.nucep, cf.nucmc, cf.nuaedf
						 FROM confignfe as cf, cliente AS c, municipio AS m, estado AS uf
						 WHERE c.nmsiglaestado=uf.sigla AND c.numunicipio=m.codigo AND m.cduf=uf.codigo AND 
                                     c.nucliente = '1'";
*/
    $sqlEmit = "SELECT * FROM emitente AS e, autorizacao AS a, notafiscal AS nf 
                WHERE nf.idEmitente = e.idEmitente AND a.idEmitente = nf.idEmitente AND nf.idNotaFiscal = '$nuNF' ";
    $execEmit = mysql_query($sqlEmit, $db); 
	$rE = mysql_fetch_array($execEmit);
	$nuCMC = $rE["cmc"];
	$nuAEDF = $rE["aedf"];
	$nmEmpresa = $rE["nome"];
//	$fantasiaEmp = $rE["nmfantasia"];
	$cnpjEmp = $rE["documento"];
//	$inscrEstEmp = $rE["nuinscrestadual"];
	$enderecoEmp = $rE["logradouro"];
	$numeroEmp = $rE["numero"];
	$complEndEmp = $rE["complemento"];
	$cepEmp = $rE["cep"];
//	$municipioEmp = $rE["nmcidade"];
	$bairroEmp = $rE["bairro"];
	$ufEmp = $rE["uf"];
	$foneEmp = $rE["fone"];
	
	$nf = 0;
	$aNF = explode(',',$nuNF); 

	require_once("relatPDFNFe.php");
	$pdf=new relatPDFNFe('P','mm','form');
	$pdf->SetMargins(0,0);
	$pdf->Open();

	while (count($aNF) > $nf) {
		$nuNF = $aNF[$nf];
		$nf++;
		$item = 0;
		// --------------------------------------------------------------------------------
		// ------------------------------ DADOS CABEÇALHO NF ------------------------------
/*
$sqlCabNF = "SELECT nuseqnota, nunota, nmserie, nuchavenfe, nf.identradasaida, dtprocessamento,
												nmsituacao, idsitnfe, nucfop, tpcfop, 
												tpdestinatario, nudestinatario, nutpdocorigem, nudocorigem,
												IF(IFNULL(nf.nmdescricao,'')='', no.nmdescricao, nf.nmdescricao) AS nmdescricao, 
												no.idfuncao, dtemissao, dtinclusao, hrinclusao, vltotnota, vltotmercadorias, vlbaseiss, vltotiss, 
												nf.vldesconto, vltotimpfedaprox, vltotimpestaprox, vltotimpmunaprox, nf.nmobs, nf.nmobsretencao
								 FROM (notafiscal AS nf)
								 LEFT JOIN tiponaturezaoperacao AS no ON (nucfop = no.cdnatope)
								 WHERE nuseqnota = '$nuNF'";
*/
        $sqlCabNF = "SELECT nuseqnota, nunota, nmserie, nuchavenfe, nf.identradasaida, dtprocessamento,
                            nmsituacao, idsitnfe, nucfop, tpcfop, 
                            tpdestinatario, nudestinatario, nutpdocorigem, nudocorigem,
                            IF(IFNULL(nf.nmdescricao,'')='', no.nmdescricao, nf.nmdescricao) AS nmdescricao, 
                            no.idfuncao, dtemissao, dtinclusao, hrinclusao, vltotnota, vltotmercadorias, vlbaseiss, vltotiss, 
                            nf.vldesconto, vltotimpfedaprox, vltotimpestaprox, vltotimpmunaprox, nf.nmobs, nf.nmobsretencao
                    FROM (notafiscal AS nf)
                    LEFT JOIN tiponaturezaoperacao AS no ON (nucfop = no.cdnatope)
                    WHERE nuseqnota = '$nuNF'";

        $execCabNF = mysql_query($sqlCabNF, $db);
		$regCab = mysql_fetch_array($execCabNF);
		$nuNota = $regCab["numero"];
		$editSerie = $regCab["serie"];
		$nuChaveNFe = $regCab["chaveNFe"];
		$idSitNFe = $regCab["situacao"];
//		$tpDest = $regCab["tpdestinatario"];
		$nuDest = $regCab["idEmitente"];
//		$editNmNatOper = $regCab["nmdescricao"];
		$nuCfps = $regCab["cfop"];
		$editVlTotalNota = number_format($regCab["valorTotal"],2,',','.');
//		$editDtEmissao = formataDtBr($regCab["dataemissao"]);
		if(!isset($editDtEmissao) || strlen($editDtEmissao)==0 || ($editDtEmissao=='00/00/0000'))
			$editDtEmissao = date("d/m/Y"); 
		if(!isset($editDtInclusao) || strlen($editDtInclusao)==0 || ($editDtInclusao=='00/00/0000')) {
			$editDtInclusao = date("d/m/Y"); 
			$editHrInclusao = date("h:i"); 
		}
		$dtCarimbo = formataDtHrBr($regCab["dataProcessamento"]);
//		$editVlBaseIss = number_format($regCab["vlbaseiss"],2,',','.');
//		$editVlss = number_format($regCab["vltotiss"],2,',','.');
//		$editVlBaseSubst = number_format($regCab["vlbasesubst"],2,',','.');
//		$editVlSubst = number_format($regCab["vlicmssubst"],2,',','.');
//		$editVlDesconto = number_format($regCab["vldesconto"],2,',','.');
		//
		$nmMsg1 = trim($regCab["dadosAdicionais"]);
		$nmMsg2 = trim($regCab["obsImpostos"]);
		//
		//
		if ($nmMsg1>'')
			$nmInfoAdic = $nmMsg1.'    -    ';
		if ($nmMsg2>'')
			$nmInfoAdic .= $nmMsg2.'    -    ';
		$nmInfoAdic = substr($nmInfoAdic,0,256);

        // ------------------------------ DADOS CLIENTE ------------------------------
        $sqlCli = "SELECT c.nucliente AS nudest, c.nmrazsocial, c.nmfantasia, 
                                            c.nucpfcgc, c.nuinscrestadual, c.nmendereco, c.numero, c.nmcomplemento,
                                            c.nmbairro, m.nome AS nmcidade, c.nmsiglaestado, c.nufone, c.nucep
                                FROM cliente AS c
                                LEFT JOIN estado AS e ON (c.nmsiglaestado = e.sigla)
                                LEFT JOIN municipio AS m ON (e.codigo = m.cduf AND c.numunicipio = m.codigo)
                                WHERE c.nucliente = '$nuDest'";

        $sqlTom = "SELECT t.nome, t.documento, t.logradouro, t.numero, t.nmcomplemento,
                          t.nmbairro, t.codigoUFMunicipio, t.uf, t.fone, t.cep
                    FROM tomador AS t
                    WHERE t.idTomador = '$nuDest'";
        $execTom = mysql_query($sqlTom, $db); 
		if (mysql_num_rows($execTom) > 0) {
			$rD = mysql_fetch_array($execTom);
			$nomeDest = $rD["nome"];
			$cpfCnpjDest = $rD["documento"];
			$enderecoDest = $rD["logradouro"];
			if ($rD['numero'] > 0)
				$enderecoDest .= ' n.:'.$rD['numero'];
			if ($rD['complemento'] > '')
				$enderecoDest .= ' - '.$rD['complemento'];
			$bairroDest = $rD["bairro"];
			$cepDest = $rD["cep"];
//			$municipioDest = $rD["nmcidade"];
			$ufDest = $rD["uf"];
			$foneDest = $rD["fone"];
			$nmPais = 'BRASIL';
		}
		
		// ----------------------------------------------------------------------------
		// ------------------------------ DADOS ITENS NF ------------------------------
/*
$sqlItens = "SELECT p.nuproduto, p.nmproduto, p.qtunidade AS qtformavenda, tas.cdatividade, tas.nucnae, tas.nmdescricao AS nmcnae,
												p.nuncm, nfi.nuordem, nfi.qtunidade, nfi.vlunitliq, nfi.vltotliq, 
												nfi.nucodtribiss AS nucst, nfi.vlbaseiss, nfi.txaliquotaiss AS txiss, nfi.vliss										
								 FROM notafiscalitem AS nfi, produto AS p, tipoatividadeservico AS tas  
								 WHERE nfi.nuproduto = p.nuproduto AND nfi.cdatividadeserv = tas.cdatividade AND nfi.nuseqnota = '$nuNF'
								 ORDER BY p.nmproduto, p.nuproduto";
*/

        $sqlItens = "SELECT p.idItemVenda, p.descricao, nfi.unidade AS qtformavenda, nfi.cnae, 
                            p.ncm, nfi.numeroOrdem, nfi.quantidade, nfi.valorUnitario, nfi.valorTotal, 
                            nfi.cstIss, nfi.valorBCIss, nfi.taxaIss AS txiss, nfi.valorIss										
                     FROM notaFiscalItem AS nfi, itemVenda AS p
                     WHERE nfi.idItemVenda = p.idItemVenda AND nfi.idNotaFiscal = '$nuNF'
                     ORDER BY nfi.numeroOrdem, p.descricao, p.idItemVenda";
        $execItens = mysql_query($sqlItens, $db);//executa busca
		$numItens = mysql_num_rows($execItens);
		//	
		$pdf->StartPageGroup();
		while ($item < $numItens) {
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
			$pdf->MultiCell(90, 4, $nmEmpresa, 0, 'C', 0); 
//			$pdf->SetFontSize(7);
//			$pdf->Image('figuras/logo_nf.jpg', 20, 12, 40); // importa uma imagem 
			$pdf->SetFont('Arial', '', 9);
			$pdf->SetXY(10,23);
			$pdf->Cell(90, 4, $enderecoEmp.', '.$numeroEmp.' - '.$complEndEmp, 0, 1, 'C'); 
			$pdf->SetX(10);
			$pdf->Cell(90, 4, $bairroEmp.' - '.$municipioEmp.' - '.$ufEmp.' - '.$cepEmp, 0, 1, 'C'); 
			$pdf->SetX(10);
			$pdf->Cell(90, 4, 'Telefone: '.$foneEmp, 0, 1, 'C'); 
			$pdf->SetX(10);
//			$pdf->Cell(90, 4, 'CNPJ: '.formataCnpj($cnpjEmp), 0, 1, 'C'); 
			$pdf->Cell(90, 4, 'CNPJ: '.$cnpjEmp, 0, 1, 'C'); 
			$pdf->SetX(10);
			$pdf->Cell(90, 4, 'CMC: '.$nuCMC, 0, 1, 'C'); 
			//
			// Número da NF
			$pdf->SetFont('Arial', 'B', '10');
			$pdf->SetXY(100,12);
			$pdf->Cell(100, 5, 'DANFPS-E', 0, 1, 'C'); 
			$pdf->SetFont('Arial', 'B', '8');
			$pdf->SetX(100);
			$pdf->Cell(100, 5, 'Documento Auxiliar da Nota Fiscal de Prestação de Serviços Eletrônica', 0, 1, 'L'); 
			$pdf->SetX(100);
			$pdf->Cell(100, 4, 'Número: '.$nuNota, 0, 1, 'L'); 
			$pdf->SetX(100);
			$pdf->Cell(100, 4, 'Autorização: '.$nuAEDF, 0, 1, 'L'); 
			$pdf->SetX(100);
			$pdf->Cell(100, 4, 'Emissão: '.$editDtEmissao, 0, 1, 'L'); 
			$pdf->SetX(100);
			$nuCodVer = wordwrap($nuChaveNFe, 4, '-', true);
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
			$pdf->Cell(160, 5, $nomeDest, 0, 0, 'L'); 
			$pdf->SetXY(170,52);
			$pdf->Cell(30, 5, $nuCfps, 0, 0, 'L'); 
			$pdf->SetXY(10,59);
			$pdf->CellFitScale(95, 5, $enderecoDest, 0, 0, 'L'); 
			$pdf->SetXY(105,59);
			$pdf->Cell(65, 5, $bairroDest, 0, 0, 'L'); 
			$pdf->SetXY(170,59);
			$pdf->Cell(30, 5, $cepDest, 0, 0, 'L'); 
			$pdf->SetXY(10,67);
			$pdf->Cell(75, 5, $municipioDest, 0, 0, 'L'); 
			$pdf->SetXY(85,67);
			$pdf->Cell(20, 5, $ufDest, 0, 0, 'C'); 
			$pdf->SetXY(105,67);
			$pdf->Cell(30, 5, $nmPais, 0, 0, 'L'); 
			$pdf->SetXY(135,67);
//			$pdf->Cell(45, 5, formataDocto($cpfCnpjDest), 0, 0, 'L'); 
			$pdf->Cell(45, 5, $cpfCnpjDest, 0, 0, 'L'); 
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
			for ($x = $item; $x < $numItens; $x++){
		
				$rI=mysql_fetch_array($execItens);

				if ($rI['cstIss'] == '0') {
					$vlTotBC += $rI['valorBCIss']; 
					$vlTotISS += $rI['valorIss']; 
				}
		
				$nmProd = '('.$rI['nmcnae'].') '.$rI['descricao'];
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
				$nuProd = $rI['cnae'];
//				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 12, ($altItem*4)); 
				$pdf->CellFitScale(20, 4, $nuProd, 0, 0, 'C'); 
		
//				$pdf->Rect($pdf->GetX(), $pdf->GetY(), 58, ($altItem*4)); 
				$pdf->MultiCell(85, 4, $nmProd, 0, 'L', 0); 
		
				if ($rI['nmobs'] > '') {
					$pdf->SetX(27);
					$pdf->MultiCell(85, 4, $rI['nmobs'], 0, 'L', 0); 
				}
				$posY = $pdf->GetY();
		
				$qtdItem = number_format($rI['quantidade'],0,',','.'); 
				$vlUnit = number_format($rI['valorUnitario'],2,',','.'); 
				$vlTotItem = number_format($rI['valorTotal'],2,',','.'); 

//				$pdf->Rect(91, $y, 5, ($altItem*4)); 
				$pdf->SetXY(115,$y);
				$pdf->Cell(8, 4, $rI['cstIss'], 0, 0, 'C'); // cst/csosn
//				$pdf->Rect(96, $y, 7, ($altItem*4)); 

//				$pdf->Rect(186, $y, 7, ($altItem*3.5)); 
				$pdf->SetXY(123,$y);
				$pdf->CellFitScale(10, 4, number_format($rI['taxaIss'],2,',','.'), 0, 0, 'R');

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
			$pdf->Cell(38, 5, 'R$ '.$editVlTotalNota, 0, 0, 'C'); 

			// dados complementares
			$pdf->Rect(10, 243, 190, 17, 1, 'DF'); // informações complementares
			//
			$pdf->SetFont('Arial', 'B', '6');
			$pdf->SetXY(10,239);
			$pdf->Cell(190, 4, 'Dados Adicionais', 0, 0, 'L'); 

			$pdf->SetFont('Arial', '', '7');
			$pdf->SetXY(10,244);
			if ($nmInfoAdic != '') {
//				$pdf->SetX(11);
				$pdf->MultiCell(190, 3, $nmInfoAdic, 0, 'L', 0); 
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
			$pdf->Cell(85, 4, 'DATA DO CARIMBO: '.$dtCarimbo, 0, 0, 'L'); 

			$txt2 = 'A VALIDADE E AUTENTICIDADE DESTE DOCUMENTO AUXILIAR DA NOTA FISCAL DE PRESTAÇÃO DE SERVIÇO ELETRÔNICA PODERÃO SER COMPROVADAS MEDIANTE CONSULTA À PÁGINA DA';
			$txt2 .= 'SECRETARIA MUNICIPAL DA FAZENDA - SMF NA INTERNET, NO ENDEREÇO portal.pmf.sc.gov.br/sites/notaeletronica, EM VERIFICAR AUTENTICIDADE >> PRODUÇÃO, ';
			$txt2 .= 'INFORMANDO O CÓDIGO DE VERIFICAÇÃO: '.$nuChaveNFe.' E O NÚMERO DE INSCRIÇÃO DO EMITENTE NO CADASTRO MUNICIPAL DE CONTRIBUINTES - CMC: '.$nuCMC;
			$pdf->SetFont('Arial', '', '6');
			$pdf->SetXY(95,264);
			$pdf->MultiCell(105, 3, $txt2, 0, 'L', 0); 

			$chaveQR = 'http://nfps-e.pmf.sc.gov.br/consulta-frontend/#!/consulta?cod='.$nuChaveNFe.'&cmc='.$nuCMC;
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
		}
	} // endwhile
	//
//	$pdf->Output('relat/rNF'.$nuNF.'_'.$codUsuario.'.pdf','F');
//	$ts = time();
//	header('location:relat/rNF'.$nuNF.'_'.$codUsuario.'.pdf?t='.$ts);
	
//	$nmArq = 'danfpse_'.$nuNota.'.pdf';
//	$pdf->Output('arquivos/nfse/danfpse/'.$nmArq,'F');


    $dirPdf = "arquivosNFSe/".$emitente->documento."/danfpse/";
    $arqPdf = $emitente->documento."_".substr(str_pad($notaFiscal->numero,8,'0',STR_PAD_LEFT),0,8)."-nfse.pdf";
    $pdf->Output("../".$dirPdf.$arqPdf,'F');
    
	return $arqPdf;
   

}
?>
