<?php 
require_once ("../../fpdf/fpdf.php"); 
class relatPDF extends FPDF { 
  var $nome;          // nome do relatorio 
  var $cabecalho;     // cabecalho para as colunas 
  var $mostralogo = 1;    // id para mostrar logotipo no relat
  var $mostrapagina = 1;    // id para mostrar numeracao de pagina (0=não mostra 1=aliasnb 2=pggroup)

  function relatPDF($or) { // Construtor: Chama a classe FPDF 
    $this->FPDF($or); 
  } 

  function SetCabecalho($cab) { // define o cabecalho 
    $this->cabecalho = $cab; 
  } 

  function SetName($nomerel) { // nomeia o relatorio 
    $this->nome = $nomerel; 
  } 

  function SetMostraLogo($mostra = 1) { // define se mostra o logo
    $this->mostralogo = $mostra; 
  } 

  function SetMostraPagina($mostra = 1) { // define se mostra o logo
    $this->mostrapagina = $mostra; 
  } 

  function Header() { 
    $this->AliasNbPages(); // Define o numero total de paginas para a macro {nb} 
    $mrgEsq = $this->getMargemEsq();
    $mrgDir = $this->getMargemDir();
		if ($this->mostralogo==1)
	    $this->Image('figuras/logorelat.jpg', $mrgEsq, 10, 30); // importa uma imagem 
    $this->SetFont('Arial', 'B', 12);
    $this->SetXY(35+$mrgEsq, 10); 
    $this->Cell(($mrgDir-$this->GetX()-20), 10, $this->nome, 0, 0, 'C'); 
    $this->SetFont('Arial', '', 10); 
    $this->SetX(-20+($mrgEsq*(-1))); // -30 ou -35
    $this->Cell(30, 10, "Pg: ".$this->PageNo()."/{nb}", 0, 1); // imprime página X/Total de Páginas 
    $this->SetX($mrgDir); 
    $this->line($mrgEsq, 24, $this->GetX(), 24); // Desenha uma linha 
    if ($this->cabecalho) { // Se tem o cabecalho, imprime 
      $this->SetFont('Arial', '', 10); 
      $this->SetX($mrgEsq+35); 
      $this->Cell(($mrgDir-$this->GetX()-20), 4, $this->cabecalho, 0, 1, 'C'); 
    } 
    $this->SetXY($mrgEsq, 25); 
  } 

  function Footer() { // Rodapé : imprime a hora de impressao e Copyright 
    $mrgEsq = $this->getMargemEsq();
    $mrgDir = $this->getMargemDir();
    $this->SetXY(($mrgEsq*(-1)), -15); 
    $this->line($mrgEsq, $this->GetY()-2, $mrgDir, $this->GetY()-2); 
    $this->SetX($mrgEsq); 
    $this->SetFont('Courier', 'I', 8); 
    $data = strftime("%d/%m/%Y - %H:%M"); 
    $this->Cell(($mrgDir-$this->GetX()), 6, chr(169)."Autocom Informática - ".$data, 0, 0, 'C'); 
  } 

  function getOrientation() {
    return $this->DefOrientation;
  }

  function getMargemEsq() {
    if ($this->getOrientation() == 'L') 
      $mrgEsq = 15;
    else
      $mrgEsq = 10;
    return $mrgEsq;
  }

  function getMargemDir() {
    if ($this->getOrientation() == 'L') 
      $mrgEsq = 15;
    else
      $mrgEsq = 10;
    $this->SetX(($mrgEsq*(-1))-3); 
    $mrgDir = $this->GetX();
    return $mrgDir;
  }

  //Cell with horizontal scaling if text is too wide
  function CellFit($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='',$scale=0,$force=1) {
    //Get string width
    $str_width=$this->GetStringWidth($txt);

    //Calculate ratio to fit cell
    if ($w==0)
      $w=$this->w-$this->rMargin-$this->x;
    $ratio=($w-$this->cMargin*2)/$str_width;

    $fit=($ratio < 1 || ($ratio > 1 && $force == 1));
    if ($fit) {
      switch ($scale) {

        //Character spacing
        case 0:
          //Calculate character spacing in points
          $char_space=($w-$this->cMargin*2-$str_width)/max($this->MBGetStringLength($txt)-1,1)*$this->k;
          //Set character spacing
          $this->_out(sprintf('BT %.2f Tc ET',$char_space));
          break;

        //Horizontal scaling
        case 1:
          //Calculate horizontal scaling
          $horiz_scale=$ratio*100.0;
          //Set horizontal scaling
          $this->_out(sprintf('BT %.2f Tz ET',$horiz_scale));
          break;

      }
      //Override user alignment (since text will fill up cell)
      $align='';
    }

    //Pass on to Cell method
    $this->Cell($w,$h,$txt,$border,$ln,$align,$fill,$link);

    //Reset character spacing/horizontal scaling
    if ($fit)
      $this->_out('BT '.($scale==0 ? '0 Tc' : '100 Tz').' ET');
  }

  //Cell with horizontal scaling only if necessary
  function CellFitScale($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='') {
    $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,1,0);
  }

  //Cell with horizontal scaling always
  function CellFitScaleForce($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='') {
    $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,1,1);
  }

  //Cell with character spacing only if necessary
  function CellFitSpace($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='') {
    $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,0,0);
  }

  //Cell with character spacing always
  function CellFitSpaceForce($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='') {
    //Same as calling CellFit directly
    $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,0,1);
  }

  //Patch to also work with CJK double-byte text
  function MBGetStringLength($s) {
    if ($this->CurrentFont['type']=='Type0') {
      $len = 0;
      $nbbytes = strlen($s);
      for ($i = 0; $i < $nbbytes; $i++) {
        if (ord($s[$i])<128)
          $len++;
        else {
          $len++;
          $i++;
        }
      }
      return $len;
    }
    else
      return strlen($s);
  }

  //------------------------------------------------------------------------
  // Imprime quantidade de linhas justificadas e retorna o resto do texto
  function Justify($text, $w, $h, $nl, $b, $f) { 
    // texto, largura célula, altura linha, numero linhas p/ imprimir, borda
    $palavras = explode(' ', $text);
    $numPalavras = count($palavras);
    // *** Handle strings longer than paragraph width
    $k=0;
    $l=0;
    while ($k < $numPalavras) {
      $tamPal = strlen($palavras[$k]);
      if ($tamPal < ($w*.7) ) {
         $palavras2[$l] = $palavras[$k];
         $l++;    
      } 
      else {
        $m=0;
        $cadeiaLetra='';
        while ($m < $tamPal) {
          $letra = substr($palavras[$k], $m, 1);
          $tamCadeiaLetra = $this->GetStringWidth($cadeiaLetra.$letra);
          if ($tamCadeiaLetra > (($w*.7)-2)) {
            $palavras2[$l] = $cadeiaLetra . '-';
            $cadeiaLetra = $letra;
            $l++;
          } 
          else {
            $cadeiaLetra .= $letra;
          }
          $m++;
        }
        if ($cadeiaLetra) {
          $palavras2[$l] = $cadeiaLetra;
          $l++;
        }
      }
      $k++;
    }
    // *** Justified lines
    $numPalavras = count($palavras2);
    $i=0;
    $linha = '';
    $linhaImp = 0;
    while ($i < $numPalavras) {
      $palavra = $palavras2[$i];
      $tamLinha = $this->GetStringWidth($linha . ' ' . $palavra);
      if (($tamLinha > ($w-5)) && ($linha) && ($linhaImp < $nl)) {
        $tamLinha = $this->GetStringWidth($linha);
        $numCaract = strlen($linha);
        $ecart = (($w-2) - $tamLinha) / $numCaract;
        $this->_out(sprintf('BT %.3f Tc ET',$ecart*$this->k));
        $this->Cell($w,$h,$linha,$b,0,'L',$f);
        $linha = $palavra;
        $linhaImp++;
      }
      else {
        if ($linha)
          $linha .= ' ' . $palavra;
        else
          $linha = $palavra;
      }
      $i++;
    }
    $this->_out('BT 0 Tc ET');
    // *** Last line
    if ($linhaImp < $nl) {
      $this->_out('BT 0 Tc ET');
      $this->Cell($w, $h, $linha, $b, 0, 'L', $f);
      $linha = '';
    }
    return $linha;
  }

function WriteText($text) {
  $intPosIni = 0;
  $intPosFim = 0;
/*
  if (strpos($text,'<n>')!==false and strpos($text,'<s>')!==false and strpos($text,'<ns>')!==false) {
    if (strpos($text,'<n>') < strpos($text,'<s>')) {
      $this->Write(5,substr($text,0,strpos($text,'<')));
      $intPosIni = strpos($text,'<');
      $intPosFim = strpos($text,'>');
      $this->SetFont('','B');
      $this->Write(5,substr($text,$intPosIni+1,$intPosFim-$intPosIni-1));
      $this->SetFont('','');
      $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
    }
    else {
      $this->Write(5,substr($text,0,strpos($text,'[')));
      $intPosIni = strpos($text,'[');
      $intPosFim = strpos($text,']');
      $w=$this->GetStringWidth('a')*($intPosFim-$intPosIni-1);
      $this->Cell($w,$this->FontSize+0.75,substr($text,$intPosIni+1,$intPosFim-$intPosIni-1),1,0,'');
      $this->WriteText(substr($text,$intPosFim+1,strlen($text)));
    }
  }
  else {
*/
    if (strpos($text,'<n>') !== false) {
      $this->Write(5,substr($text,0,strpos($text,'<n>')));
      $intPosIni = strpos($text,'<n>');
      $intPosFim = strpos($text,'</n>');
      $this->SetFont('','B');
      $this->WriteText(substr($text,$intPosIni+3,$intPosFim-$intPosIni-3));
      $this->SetFont('','');
      $this->WriteText(substr($text,$intPosFim+4,strlen($text)));
    }
    elseif (strpos($text,'<ns>') !== false) {
      $this->Write(5,substr($text,0,strpos($text,'<ns>')));
      $intPosIni = strpos($text,'<ns>');
      $intPosFim = strpos($text,'</ns>');
      $this->SetFont('','BU');
      $this->WriteText(substr($text,$intPosIni+4,$intPosFim-$intPosIni-4));
      $this->SetFont('','');
      $this->WriteText(substr($text,$intPosFim+5,strlen($text)));
    }
    else {
      $this->Write(5,$text);
    }
//  }

}

var $angle=0;
function Rotate($angle,$x=-1,$y=-1) {
  if ($x==-1)
    $x=$this->x;
  if ($y==-1)
    $y=$this->y;
  if ($this->angle!=0)
    $this->_out('Q');
  $this->angle=$angle;
  if ($angle!=0) {
    $angle*=M_PI/180;
    $c=cos($angle);
    $s=sin($angle);
    $cx=$x*$this->k;
    $cy=($this->h-$y)*$this->k;
    $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
  }
}

function _endpage() {
  if ($this->angle!=0) {
    $this->angle=0;
    $this->_out('Q');
  }
  parent::_endpage();
}

//Computes the number of lines a MultiCell of width w will take
function numLines($w,$txt) {
  $cw=&$this->CurrentFont['cw'];
  if($w==0)
    $w=$this->w-$this->rMargin-$this->x;
  $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
  $s=str_replace("\r",'',$txt);
  $nb=strlen($s);
  if($nb>0 and $s[$nb-1]=="\n")
    $nb--;
  $sep=-1;
  $i=0; $j=0; $l=0; $nl=1;
  while($i<$nb) {
    $c=$s[$i];
    if($c=="\n") {
      $i++;
      $sep=-1;
      $j=$i;
      $l=0;
      $nl++;
      continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax) {
			if($sep==-1) {
				if($i==$j)
					$i++;
			}
			else
				$i=$sep+1;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
		}
		else
			$i++;
	}
  return $nl;
}

function i25($xpos, $ypos, $code, $basewidth=1, $height=10){

	$wide = $basewidth;
	$narrow = $basewidth / 3 ;

	// wide/narrow codes for the digits
	$barChar['0'] = 'nnwwn';
	$barChar['1'] = 'wnnnw';
	$barChar['2'] = 'nwnnw';
	$barChar['3'] = 'wwnnn';
	$barChar['4'] = 'nnwnw';
	$barChar['5'] = 'wnwnn';
	$barChar['6'] = 'nwwnn';
	$barChar['7'] = 'nnnww';
	$barChar['8'] = 'wnnwn';
	$barChar['9'] = 'nwnwn';
	$barChar['A'] = 'nn';
	$barChar['Z'] = 'wn';

	// add leading zero if code-length is odd
	if(strlen($code) % 2 != 0){
			$code = '0' . $code;
	}

	$this->SetFont('Arial','',10);
//	$this->Text($xpos, $ypos + $height + 4, $code);
	$this->SetFillColor(0);

	// add start and stop codes
	$code = 'AA'.strtolower($code).'ZA';

	for($i=0; $i<strlen($code); $i=$i+2){
			// choose next pair of digits
			$charBar = $code[$i];
			$charSpace = $code[$i+1];
			// check whether it is a valid digit
			if(!isset($barChar[$charBar])){
					$this->Error('Invalid character in barcode: '.$charBar);
			}
			if(!isset($barChar[$charSpace])){
					$this->Error('Invalid character in barcode: '.$charSpace);
			}
			// create a wide/narrow-sequence (first digit=bars, second digit=spaces)
			$seq = '';
			for($s=0; $s<strlen($barChar[$charBar]); $s++){
					$seq .= $barChar[$charBar][$s] . $barChar[$charSpace][$s];
			}
			for($bar=0; $bar<strlen($seq); $bar++){
					// set lineWidth depending on value
					if($seq[$bar] == 'n'){
							$lineWidth = $narrow;
					}else{
							$lineWidth = $wide;
					}
					// draw every second value, because the second digit of the pair is represented by the spaces
					if($bar % 2 == 0){
							$this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
					}
					$xpos += $lineWidth;
			}
	}
}

// ====================== PDF Page Group ======================
var $NewPageGroup;   // variable indicating whether a new group was requested
var $PageGroups;     // variable containing the number of pages of the groups
var $CurrPageGroup;  // variable containing the alias of the current page group

// create a new page group; call this before calling AddPage()
function StartPageGroup() {
	$this->NewPageGroup = true;
}

// current page in the group
function GroupPageNo() {
	return $this->PageGroups[$this->CurrPageGroup];
}

// alias of the current page group -- will be replaced by the total number of pages in this group
function PageGroupAlias() {
	return $this->CurrPageGroup;
}

function _beginpage($orientation, $format) {
	parent::_beginpage($orientation, $format);
	if($this->NewPageGroup) {
		// start a new group
		$n = sizeof($this->PageGroups)+1;
		$alias = "{nb$n}";
		$this->PageGroups[$alias] = 1;
		$this->CurrPageGroup = $alias;
		$this->NewPageGroup = false;
	}
	elseif($this->CurrPageGroup)
		$this->PageGroups[$this->CurrPageGroup]++;
}

function _putpages() {
	$nb = $this->page;
	if (!empty($this->PageGroups)) {
		// do page number replacement
		foreach ($this->PageGroups as $k => $v) {
			for ($n = 1; $n <= $nb; $n++)	{
				$this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
			}
		}
	}
	parent::_putpages();
}

} 

?>
