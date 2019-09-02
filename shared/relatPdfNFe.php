<?php 
require_once ("./relatPdf.php"); 
class relatPdfNFe extends relatPDF { 

    var $nome;          // nome do relatorio 
    var $cabecalho;     // cabecalho para as colunas 
  
    function relatPDFLimpo($or) { // Construtor: Chama a classe FPDF 
      $this->FPDF($or); 
    } 
  
    function Header() { 
    } 
  
    function Footer() { 
    } 
  
    function EAN13($x, $y, $barcode, $h=16, $w=.35)
	{
			$this->Barcode($x,$y,$barcode,$h,$w,13);
	}
	
	function UPC_A($x, $y, $barcode, $h=16, $w=.35)
	{
			$this->Barcode($x,$y,$barcode,$h,$w,12);
	}
	
	function GetCheckDigit($barcode)
	{
			//Compute the check digit
			$sum=0;
			for($i=1;$i<=11;$i+=2)
					$sum+=3*$barcode[$i];
			for($i=0;$i<=10;$i+=2)
					$sum+=$barcode[$i];
			$r=$sum%10;
			if($r>0)
					$r=10-$r;
			return $r;
	}
	
	function TestCheckDigit($barcode)
	{
			//Test validity of check digit
			$sum=0;
			for($i=1;$i<=11;$i+=2)
					$sum+=3*$barcode[$i];
			for($i=0;$i<=10;$i+=2)
					$sum+=$barcode[$i];
			return ($sum+$barcode[12])%10==0;
	}
	
	function Barcode($x, $y, $barcode, $h, $w, $len)
	{
			//Padding
			$barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
			if($len==12)
					$barcode='0'.$barcode;
			//Add or control the check digit
			if(strlen($barcode)==12)
					$barcode.=$this->GetCheckDigit($barcode);
			elseif(!$this->TestCheckDigit($barcode))
					$this->Error('Incorrect check digit');
			//Convert digits to bars
			$codes=array(
					'A'=>array(
							'0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
							'5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
					'B'=>array(
							'0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
							'5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
					'C'=>array(
							'0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
							'5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
					);
			$parities=array(
					'0'=>array('A','A','A','A','A','A'),
					'1'=>array('A','A','B','A','B','B'),
					'2'=>array('A','A','B','B','A','B'),
					'3'=>array('A','A','B','B','B','A'),
					'4'=>array('A','B','A','A','B','B'),
					'5'=>array('A','B','B','A','A','B'),
					'6'=>array('A','B','B','B','A','A'),
					'7'=>array('A','B','A','B','A','B'),
					'8'=>array('A','B','A','B','B','A'),
					'9'=>array('A','B','B','A','B','A')
					);
			$code='101';
			$p=$parities[$barcode[0]];
			for($i=1;$i<=6;$i++)
					$code.=$codes[$p[$i-1]][$barcode[$i]];
			$code.='01010';
			for($i=7;$i<=12;$i++)
					$code.=$codes['C'][$barcode[$i]];
			$code.='101';
			//Draw bars
			for($i=0;$i<strlen($code);$i++)
			{
					if($code[$i]=='1')
							$this->Rect($x+$i*$w,$y,$w,$h,'F');
			}
			//Print text uder barcode
			$this->SetFont('Arial','',12);
			$this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
	}
	
	var $T128;                                             // tableau des codes 128
	var $ABCset="";                                        // jeu des caractères éligibles au C128
	var $Aset="";                                          // Set A du jeu des caractères éligibles
	var $Bset="";                                          // Set B du jeu des caractères éligibles
	var $Cset="";                                          // Set C du jeu des caractères éligibles
	var $SetFrom;                                          // Convertisseur source des jeux vers le tableau
	var $SetTo;                                            // Convertisseur destination des jeux vers le tableau
	var $JStart = array("A"=>103, "B"=>104, "C"=>105);     // Caractères de sélection de jeu au début du C128
	var $JSwap = array("A"=>101, "B"=>100, "C"=>99);       // Caractères de changement de jeu
	function Code128($x, $y, $code, $w, $h) {
	
			$this->T128[] = array(2, 1, 2, 2, 2, 2);           //0 : [ ]               // composition des caractères
			$this->T128[] = array(2, 2, 2, 1, 2, 2);           //1 : [!]
			$this->T128[] = array(2, 2, 2, 2, 2, 1);           //2 : ["]
			$this->T128[] = array(1, 2, 1, 2, 2, 3);           //3 : [#]
			$this->T128[] = array(1, 2, 1, 3, 2, 2);           //4 : [$]
			$this->T128[] = array(1, 3, 1, 2, 2, 2);           //5 : [%]
			$this->T128[] = array(1, 2, 2, 2, 1, 3);           //6 : [&]
			$this->T128[] = array(1, 2, 2, 3, 1, 2);           //7 : [']
			$this->T128[] = array(1, 3, 2, 2, 1, 2);           //8 : [(]
			$this->T128[] = array(2, 2, 1, 2, 1, 3);           //9 : [)]
			$this->T128[] = array(2, 2, 1, 3, 1, 2);           //10 : [*]
			$this->T128[] = array(2, 3, 1, 2, 1, 2);           //11 : [+]
			$this->T128[] = array(1, 1, 2, 2, 3, 2);           //12 : [,]
			$this->T128[] = array(1, 2, 2, 1, 3, 2);           //13 : [-]
			$this->T128[] = array(1, 2, 2, 2, 3, 1);           //14 : [.]
			$this->T128[] = array(1, 1, 3, 2, 2, 2);           //15 : [/]
			$this->T128[] = array(1, 2, 3, 1, 2, 2);           //16 : [0]
			$this->T128[] = array(1, 2, 3, 2, 2, 1);           //17 : [1]
			$this->T128[] = array(2, 2, 3, 2, 1, 1);           //18 : [2]
			$this->T128[] = array(2, 2, 1, 1, 3, 2);           //19 : [3]
			$this->T128[] = array(2, 2, 1, 2, 3, 1);           //20 : [4]
			$this->T128[] = array(2, 1, 3, 2, 1, 2);           //21 : [5]
			$this->T128[] = array(2, 2, 3, 1, 1, 2);           //22 : [6]
			$this->T128[] = array(3, 1, 2, 1, 3, 1);           //23 : [7]
			$this->T128[] = array(3, 1, 1, 2, 2, 2);           //24 : [8]
			$this->T128[] = array(3, 2, 1, 1, 2, 2);           //25 : [9]
			$this->T128[] = array(3, 2, 1, 2, 2, 1);           //26 : [:]
			$this->T128[] = array(3, 1, 2, 2, 1, 2);           //27 : [;]
			$this->T128[] = array(3, 2, 2, 1, 1, 2);           //28 : [<]
			$this->T128[] = array(3, 2, 2, 2, 1, 1);           //29 : [=]
			$this->T128[] = array(2, 1, 2, 1, 2, 3);           //30 : [>]
			$this->T128[] = array(2, 1, 2, 3, 2, 1);           //31 : [?]
			$this->T128[] = array(2, 3, 2, 1, 2, 1);           //32 : [@]
			$this->T128[] = array(1, 1, 1, 3, 2, 3);           //33 : [A]
			$this->T128[] = array(1, 3, 1, 1, 2, 3);           //34 : [B]
			$this->T128[] = array(1, 3, 1, 3, 2, 1);           //35 : [C]
			$this->T128[] = array(1, 1, 2, 3, 1, 3);           //36 : [D]
			$this->T128[] = array(1, 3, 2, 1, 1, 3);           //37 : [E]
			$this->T128[] = array(1, 3, 2, 3, 1, 1);           //38 : [F]
			$this->T128[] = array(2, 1, 1, 3, 1, 3);           //39 : [G]
			$this->T128[] = array(2, 3, 1, 1, 1, 3);           //40 : [H]
			$this->T128[] = array(2, 3, 1, 3, 1, 1);           //41 : [I]
			$this->T128[] = array(1, 1, 2, 1, 3, 3);           //42 : [J]
			$this->T128[] = array(1, 1, 2, 3, 3, 1);           //43 : [K]
			$this->T128[] = array(1, 3, 2, 1, 3, 1);           //44 : [L]
			$this->T128[] = array(1, 1, 3, 1, 2, 3);           //45 : [M]
			$this->T128[] = array(1, 1, 3, 3, 2, 1);           //46 : [N]
			$this->T128[] = array(1, 3, 3, 1, 2, 1);           //47 : [O]
			$this->T128[] = array(3, 1, 3, 1, 2, 1);           //48 : [P]
			$this->T128[] = array(2, 1, 1, 3, 3, 1);           //49 : [Q]
			$this->T128[] = array(2, 3, 1, 1, 3, 1);           //50 : [R]
			$this->T128[] = array(2, 1, 3, 1, 1, 3);           //51 : [S]
			$this->T128[] = array(2, 1, 3, 3, 1, 1);           //52 : [T]
			$this->T128[] = array(2, 1, 3, 1, 3, 1);           //53 : [U]
			$this->T128[] = array(3, 1, 1, 1, 2, 3);           //54 : [V]
			$this->T128[] = array(3, 1, 1, 3, 2, 1);           //55 : [W]
			$this->T128[] = array(3, 3, 1, 1, 2, 1);           //56 : [X]
			$this->T128[] = array(3, 1, 2, 1, 1, 3);           //57 : [Y]
			$this->T128[] = array(3, 1, 2, 3, 1, 1);           //58 : [Z]
			$this->T128[] = array(3, 3, 2, 1, 1, 1);           //59 : [[]
			$this->T128[] = array(3, 1, 4, 1, 1, 1);           //60 : [\]
			$this->T128[] = array(2, 2, 1, 4, 1, 1);           //61 : []]
			$this->T128[] = array(4, 3, 1, 1, 1, 1);           //62 : [^]
			$this->T128[] = array(1, 1, 1, 2, 2, 4);           //63 : [_]
			$this->T128[] = array(1, 1, 1, 4, 2, 2);           //64 : [`]
			$this->T128[] = array(1, 2, 1, 1, 2, 4);           //65 : [a]
			$this->T128[] = array(1, 2, 1, 4, 2, 1);           //66 : [b]
			$this->T128[] = array(1, 4, 1, 1, 2, 2);           //67 : [c]
			$this->T128[] = array(1, 4, 1, 2, 2, 1);           //68 : [d]
			$this->T128[] = array(1, 1, 2, 2, 1, 4);           //69 : [e]
			$this->T128[] = array(1, 1, 2, 4, 1, 2);           //70 : [f]
			$this->T128[] = array(1, 2, 2, 1, 1, 4);           //71 : [g]
			$this->T128[] = array(1, 2, 2, 4, 1, 1);           //72 : [h]
			$this->T128[] = array(1, 4, 2, 1, 1, 2);           //73 : [i]
			$this->T128[] = array(1, 4, 2, 2, 1, 1);           //74 : [j]
			$this->T128[] = array(2, 4, 1, 2, 1, 1);           //75 : [k]
			$this->T128[] = array(2, 2, 1, 1, 1, 4);           //76 : [l]
			$this->T128[] = array(4, 1, 3, 1, 1, 1);           //77 : [m]
			$this->T128[] = array(2, 4, 1, 1, 1, 2);           //78 : [n]
			$this->T128[] = array(1, 3, 4, 1, 1, 1);           //79 : [o]
			$this->T128[] = array(1, 1, 1, 2, 4, 2);           //80 : [p]
			$this->T128[] = array(1, 2, 1, 1, 4, 2);           //81 : [q]
			$this->T128[] = array(1, 2, 1, 2, 4, 1);           //82 : [r]
			$this->T128[] = array(1, 1, 4, 2, 1, 2);           //83 : [s]
			$this->T128[] = array(1, 2, 4, 1, 1, 2);           //84 : [t]
			$this->T128[] = array(1, 2, 4, 2, 1, 1);           //85 : [u]
			$this->T128[] = array(4, 1, 1, 2, 1, 2);           //86 : [v]
			$this->T128[] = array(4, 2, 1, 1, 1, 2);           //87 : [w]
			$this->T128[] = array(4, 2, 1, 2, 1, 1);           //88 : [x]
			$this->T128[] = array(2, 1, 2, 1, 4, 1);           //89 : [y]
			$this->T128[] = array(2, 1, 4, 1, 2, 1);           //90 : [z]
			$this->T128[] = array(4, 1, 2, 1, 2, 1);           //91 : [{]
			$this->T128[] = array(1, 1, 1, 1, 4, 3);           //92 : [|]
			$this->T128[] = array(1, 1, 1, 3, 4, 1);           //93 : [}]
			$this->T128[] = array(1, 3, 1, 1, 4, 1);           //94 : [~]
			$this->T128[] = array(1, 1, 4, 1, 1, 3);           //95 : [DEL]
			$this->T128[] = array(1, 1, 4, 3, 1, 1);           //96 : [FNC3]
			$this->T128[] = array(4, 1, 1, 1, 1, 3);           //97 : [FNC2]
			$this->T128[] = array(4, 1, 1, 3, 1, 1);           //98 : [SHIFT]
			$this->T128[] = array(1, 1, 3, 1, 4, 1);           //99 : [Cswap]
			$this->T128[] = array(1, 1, 4, 1, 3, 1);           //100 : [Bswap]                
			$this->T128[] = array(3, 1, 1, 1, 4, 1);           //101 : [Aswap]
			$this->T128[] = array(4, 1, 1, 1, 3, 1);           //102 : [FNC1]
			$this->T128[] = array(2, 1, 1, 4, 1, 2);           //103 : [Astart]
			$this->T128[] = array(2, 1, 1, 2, 1, 4);           //104 : [Bstart]
			$this->T128[] = array(2, 1, 1, 2, 3, 2);           //105 : [Cstart]
			$this->T128[] = array(2, 3, 3, 1, 1, 1);           //106 : [STOP]
			$this->T128[] = array(2, 1);                       //107 : [END BAR]
	
			for ($i = 32; $i <= 95; $i++) {                                            // jeux de caractères
					$this->ABCset .= chr($i);
			}
			$this->Aset = $this->ABCset;
			$this->Bset = $this->ABCset;
			for ($i = 0; $i <= 31; $i++) {
					$this->ABCset .= chr($i);
					$this->Aset .= chr($i);
			}
			for ($i = 96; $i <= 126; $i++) {
					$this->ABCset .= chr($i);
					$this->Bset .= chr($i);
			}
			$this->Cset="0123456789";
	
			for ($i=0; $i<96; $i++) {                                                  // convertisseurs des jeux A & B  
					@$this->SetFrom["A"] .= chr($i);
					@$this->SetFrom["B"] .= chr($i + 32);
					@$this->SetTo["A"] .= chr(($i < 32) ? $i+64 : $i-32);
					@$this->SetTo["B"] .= chr($i);
			}
	//------------------------------------------------------------
			$Aguid = "";                                                                      // Création des guides de choix ABC
			$Bguid = "";
			$Cguid = "";
			for ($i=0; $i < strlen($code); $i++) {
					$needle = substr($code,$i,1);
					$Aguid .= ((strpos($this->Aset,$needle)===false) ? "N" : "O"); 
					$Bguid .= ((strpos($this->Bset,$needle)===false) ? "N" : "O"); 
					$Cguid .= ((strpos($this->Cset,$needle)===false) ? "N" : "O");
			}
	
			$SminiC = "OOOO";
			$IminiC = 4;
	
			$crypt = "";
			while ($code > "") {
																																											// BOUCLE PRINCIPALE DE CODAGE
					$i = strpos($Cguid,$SminiC);                                                // forçage du jeu C, si possible
					if ($i!==false) {
							$Aguid [$i] = "N";
							$Bguid [$i] = "N";
					}
	
					if (substr($Cguid,0,$IminiC) == $SminiC) {                                  // jeu C
							$crypt .= chr(($crypt > "") ? $this->JSwap["C"] : $this->JStart["C"]);  // début Cstart, sinon Cswap
							$made = strpos($Cguid,"N");                                             // étendu du set C
							if ($made === false) {
									$made = strlen($Cguid);
							}
							if (fmod($made,2)==1) {
									$made--;                                                            // seulement un nombre pair
							}
							for ($i=0; $i < $made; $i += 2) {
									$crypt .= chr(strval(substr($code,$i,2)));                          // conversion 2 par 2
							}
							$jeu = "C";
					} else {
							$madeA = strpos($Aguid,"N");                                            // étendu du set A
							if ($madeA === false) {
									$madeA = strlen($Aguid);
							}
							$madeB = strpos($Bguid,"N");                                            // étendu du set B
							if ($madeB === false) {
									$madeB = strlen($Bguid);
							}
							$made = (($madeA < $madeB) ? $madeB : $madeA );                         // étendu traitée
							$jeu = (($madeA < $madeB) ? "B" : "A" );                                // Jeu en cours
	
							$crypt .= chr(($crypt > "") ? $this->JSwap[$jeu] : $this->JStart[$jeu]); // début start, sinon swap
	
							$crypt .= strtr(substr($code, 0,$made), $this->SetFrom[$jeu], $this->SetTo[$jeu]); // conversion selon jeu
	
					}
					$code = substr($code,$made);                                           // raccourcir légende et guides de la zone traitée
					$Aguid = substr($Aguid,$made);
					$Bguid = substr($Bguid,$made);
					$Cguid = substr($Cguid,$made);
			}                                                                          // FIN BOUCLE PRINCIPALE
	
			$check = ord($crypt[0]);                                                   // calcul de la somme de contrôle
			for ($i=0; $i<strlen($crypt); $i++) {
					$check += (ord($crypt[$i]) * $i);
			}
			$check %= 103;
	
			$crypt .= chr($check) . chr(106) . chr(107);                               // Chaine Cryptée complète
	
			$i = (strlen($crypt) * 11) - 8;                                            // calcul de la largeur du module
			$modul = $w/$i;
	
			for ($i=0; $i<strlen($crypt); $i++) {                                      // BOUCLE D'IMPRESSION
					$c = $this->T128[ord($crypt[$i])];
					for ($j=0; $j<count($c); $j++) {
							$this->Rect($x,$y,$c[$j]*$modul,$h,"F");
							$x += ($c[$j++]+$c[$j])*$modul;
					}
			}
	}

function Code39($x, $y, $code, $ext = true, $cks = false, $w = 0.4, $h = 20, $wide = true) {

    //Display code
    $this->SetFont('Arial', '', 6);
    $this->Text($x, $y+$h+2, $code);

    if($ext) {
        //Extended encoding
        $code = $this->encode_code39_ext($code);
    }
    else {
        //Convert to upper case
        $code = strtoupper($code);
        //Check validity
        if(!preg_match('|^[0-9A-Z. $/+%-]*$|', $code))
            $this->Error('Invalid barcode value: '.$code);
    }

    //Compute checksum
    if ($cks)
        $code .= $this->checksum_code39($code);

    //Add start and stop characters
    $code = '*'.$code.'*';

    //Conversion tables
    $narrow_encoding = array (
        '0' => '101001101101', '1' => '110100101011', '2' => '101100101011',
        '3' => '110110010101', '4' => '101001101011', '5' => '110100110101',
        '6' => '101100110101', '7' => '101001011011', '8' => '110100101101',
        '9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011',
        'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101',
        'F' => '101101100101', 'G' => '101010011011', 'H' => '110101001101',
        'I' => '101101001101', 'J' => '101011001101', 'K' => '110101010011',
        'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011',
        'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011',
        'R' => '110101011001', 'S' => '101101011001', 'T' => '101011011001',
        'U' => '110010101011', 'V' => '100110101011', 'W' => '110011010101',
        'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101',
        '-' => '100101011011', '.' => '110010101101', ' ' => '100110101101',
        '*' => '100101101101', '$' => '100100100101', '/' => '100100101001',
        '+' => '100101001001', '%' => '101001001001' );

    $wide_encoding = array (
        '0' => '101000111011101', '1' => '111010001010111', '2' => '101110001010111',
        '3' => '111011100010101', '4' => '101000111010111', '5' => '111010001110101',
        '6' => '101110001110101', '7' => '101000101110111', '8' => '111010001011101',
        '9' => '101110001011101', 'A' => '111010100010111', 'B' => '101110100010111',
        'C' => '111011101000101', 'D' => '101011100010111', 'E' => '111010111000101',
        'F' => '101110111000101', 'G' => '101010001110111', 'H' => '111010100011101',
        'I' => '101110100011101', 'J' => '101011100011101', 'K' => '111010101000111',
        'L' => '101110101000111', 'M' => '111011101010001', 'N' => '101011101000111',
        'O' => '111010111010001', 'P' => '101110111010001', 'Q' => '101010111000111',
        'R' => '111010101110001', 'S' => '101110101110001', 'T' => '101011101110001',
        'U' => '111000101010111', 'V' => '100011101010111', 'W' => '111000111010101',
        'X' => '100010111010111', 'Y' => '111000101110101', 'Z' => '100011101110101',
        '-' => '100010101110111', '.' => '111000101011101', ' ' => '100011101011101',
        '*' => '100010111011101', '$' => '100010001000101', '/' => '100010001010001',
        '+' => '100010100010001', '%' => '101000100010001');

    $encoding = $wide ? $wide_encoding : $narrow_encoding;

    //Inter-character spacing
    $gap = ($w > 0.29) ? '00' : '0';

    //Convert to bars
    $encode = '';
    for ($i = 0; $i< strlen($code); $i++)
        $encode .= $encoding[$code[$i]].$gap;

    //Draw bars
    $this->draw_code39($encode, $x, $y, $w, $h);
}

function checksum_code39($code) {

    //Compute the modulo 43 checksum

    $chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
                            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
                            'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%');
    $sum = 0;
    for ($i=0 ; $i<strlen($code); $i++) {
        $a = array_keys($chars, $code[$i]);
        $sum += $a[0];
    }
    $r = $sum % 43;
    return $chars[$r];
}

function encode_code39_ext($code) {

    //Encode characters in extended mode

    $encode = array(
        chr(0) => '%U', chr(1) => '$A', chr(2) => '$B', chr(3) => '$C',
        chr(4) => '$D', chr(5) => '$E', chr(6) => '$F', chr(7) => '$G',
        chr(8) => '$H', chr(9) => '$I', chr(10) => '$J', chr(11) => '£K',
        chr(12) => '$L', chr(13) => '$M', chr(14) => '$N', chr(15) => '$O',
        chr(16) => '$P', chr(17) => '$Q', chr(18) => '$R', chr(19) => '$S',
        chr(20) => '$T', chr(21) => '$U', chr(22) => '$V', chr(23) => '$W',
        chr(24) => '$X', chr(25) => '$Y', chr(26) => '$Z', chr(27) => '%A',
        chr(28) => '%B', chr(29) => '%C', chr(30) => '%D', chr(31) => '%E',
        chr(32) => ' ', chr(33) => '/A', chr(34) => '/B', chr(35) => '/C',
        chr(36) => '/D', chr(37) => '/E', chr(38) => '/F', chr(39) => '/G',
        chr(40) => '/H', chr(41) => '/I', chr(42) => '/J', chr(43) => '/K',
        chr(44) => '/L', chr(45) => '-', chr(46) => '.', chr(47) => '/O',
        chr(48) => '0', chr(49) => '1', chr(50) => '2', chr(51) => '3',
        chr(52) => '4', chr(53) => '5', chr(54) => '6', chr(55) => '7',
        chr(56) => '8', chr(57) => '9', chr(58) => '/Z', chr(59) => '%F',
        chr(60) => '%G', chr(61) => '%H', chr(62) => '%I', chr(63) => '%J',
        chr(64) => '%V', chr(65) => 'A', chr(66) => 'B', chr(67) => 'C',
        chr(68) => 'D', chr(69) => 'E', chr(70) => 'F', chr(71) => 'G',
        chr(72) => 'H', chr(73) => 'I', chr(74) => 'J', chr(75) => 'K',
        chr(76) => 'L', chr(77) => 'M', chr(78) => 'N', chr(79) => 'O',
        chr(80) => 'P', chr(81) => 'Q', chr(82) => 'R', chr(83) => 'S',
        chr(84) => 'T', chr(85) => 'U', chr(86) => 'V', chr(87) => 'W',
        chr(88) => 'X', chr(89) => 'Y', chr(90) => 'Z', chr(91) => '%K',
        chr(92) => '%L', chr(93) => '%M', chr(94) => '%N', chr(95) => '%O',
        chr(96) => '%W', chr(97) => '+A', chr(98) => '+B', chr(99) => '+C',
        chr(100) => '+D', chr(101) => '+E', chr(102) => '+F', chr(103) => '+G',
        chr(104) => '+H', chr(105) => '+I', chr(106) => '+J', chr(107) => '+K',
        chr(108) => '+L', chr(109) => '+M', chr(110) => '+N', chr(111) => '+O',
        chr(112) => '+P', chr(113) => '+Q', chr(114) => '+R', chr(115) => '+S',
        chr(116) => '+T', chr(117) => '+U', chr(118) => '+V', chr(119) => '+W',
        chr(120) => '+X', chr(121) => '+Y', chr(122) => '+Z', chr(123) => '%P',
        chr(124) => '%Q', chr(125) => '%R', chr(126) => '%S', chr(127) => '%T');

    $code_ext = '';
    for ($i = 0 ; $i<strlen($code); $i++) {
        if (ord($code[$i]) > 127)
            $this->Error('Invalid character: '.$code[$i]);
        $code_ext .= $encode[$code[$i]];
    }
    return $code_ext;
}

function draw_code39($code, $x, $y, $w, $h) {

    //Draw bars

    for($i=0; $i<strlen($code); $i++) {
        if($code[$i] == '1')
            $this->Rect($x+$i*$w, $y, $w, $h, 'F');
    }
}

// i25(float xpos, float ypos, string code [, float basewidth [, float height]])
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

//    $this->SetFont('Arial','',10);
//    $this->Text($xpos, $ypos + $height + 4, $code);
//    $this->SetFillColor(0);

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

} 


?>
