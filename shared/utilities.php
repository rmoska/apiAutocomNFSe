<?php
class Utilities{
 
    public function getPaging($page, $total_rows, $records_per_page, $page_url){
 
        // paging array
        $paging_arr=array();
 
        // button for first page
        $paging_arr["first"] = $page>1 ? "{$page_url}page=1" : "";
 
        // count all products in the database to calculate total pages
        $total_pages = ceil($total_rows / $records_per_page);
 
        // range of links to show
        $range = 2;
 
        // display links to 'range of pages' around 'current page'
        $initial_num = $page - $range;
        $condition_limit_num = ($page + $range)  + 1;
 
        $paging_arr['pages']=array();
        $page_count=0;
         
        for($x=$initial_num; $x<$condition_limit_num; $x++){
            // be sure '$x is greater than 0' AND 'less than or equal to the $total_pages'
            if(($x > 0) && ($x <= $total_pages)){
                $paging_arr['pages'][$page_count]["page"]=$x;
                $paging_arr['pages'][$page_count]["url"]="{$page_url}page={$x}";
                $paging_arr['pages'][$page_count]["current_page"] = $x==$page ? "yes" : "no";
 
                $page_count++;
            }
        }
 
        // button for last page
        $paging_arr["last"] = $page<$total_pages ? "{$page_url}page={$total_pages}" : "";
 
        // json format
        return $paging_arr;
    }
 
    
    public function mask($val, $mask)
    {
        $maskared = '';
        $k = 0;
        for($i = 0; $i<=strlen($mask)-1; $i++)
        {
            if($mask[$i] == '#')
            {
                if(isset($val[$k]))
                    $maskared .= $val[$k++];
            }
            else
            {
                if(isset($mask[$i]))
                    $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }
    
    /**
     * Remove acentos do texto
     */
    public function limpaEspeciais($texto) {
        $caractInv = array('&','<','>','"','\'','\´','º','º','ª','Ø','±','µ','²','°','–','²');
        $caractSub = array('e','-','+',' ',' ',' ','.','.','a','0','~','m','2','.','-','2');
        for($i=0;$i<count($caractInv);$i++){
            $texto = ereg_replace($caractInv[$i],$caractSub[$i],$texto);	
        }
        return $texto;
    }

    /**
     * Remove acentos do texto
     */
    public function limpaAcentos($texto) {

        $caractInv = array('&','á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü',
            'ç','Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ü','Ç');
        $caractSub = array('e','a','a','a','a','e','e','i','o','o','o','u','u',
            'c','A','A','A','A','E','E','I','O','O','O','U','U','C');
        for($i=0;$i<count($caractInv);$i++){
            $texto = ereg_replace($caractInv[$i],$caractSub[$i],$texto);	
        }
        return $texto;
    }//fim cleanString


    public function codificaMsg($msg) {

        $codMsg = 'P00'; //'OUTROS';
        if ( (stristr($msg, 'Sintaxe do XML')) || (stristr($msg, 'Problema com integridade')) || 
             (stristr($msg, 'Arquivo Invalido')) || (stristr($msg, 'invalid_token')) || 
             (stristr($msg, 'Erro na validação da assinatura digital')) || (stristr($msg, 'Unexpected end of file')) ||
             (stristr($msg, 'Erro ao realizar o processamento'))) {
            $codMsg = 'P05'; //'ERRO DE ARQUIVO/TIMEOUT';
        }
        else if ( (stristr($msg, 'tomador')) || (stristr($msg, 'país inv')) || (stristr($msg, 'município inv')) || (stristr($msg, 'uf inv')) ) {
            $codMsg = 'P04'; //'TOMADOR';
        }
        else if ( (stristr($msg, 'alíquota')) || (stristr($msg, 'cst')) || (stristr($msg, 'issqn')) ) {
            $codMsg = 'P03'; //'ALIQUOTA';
        }
        else if (stristr($msg, 'cnae')) {
            $codMsg = 'P02'; //'CNAE';
        }
        else if (stristr($msg, 'aedf')) {
            $codMsg = 'P01'; //'AEDF';
        }
        
        return $codMsg;
    }

    public function codificaMsgIPM($codRet) {

        $aTomador = array();
        $aTributo = array();
        $aItens = array();

        $codMsg = 'P00'; //'OUTROS';
        if ( (stristr($msg, 'Sintaxe do XML')) || (stristr($msg, 'Problema com integridade')) || 
             (stristr($msg, 'Arquivo Invalido')) || (stristr($msg, 'invalid_token')) || 
             (stristr($msg, 'Erro na validação da assinatura digital')) || (stristr($msg, 'Unexpected end of file')) ||
             (stristr($msg, 'Erro ao realizar o processamento'))) {
            $codMsg = 'P05'; //'ERRO DE ARQUIVO/TIMEOUT';
        }
        else if ( (stristr($msg, 'tomador')) || (stristr($msg, 'país inv')) || (stristr($msg, 'município inv')) || (stristr($msg, 'uf inv')) ) {
            $codMsg = 'P04'; //'TOMADOR';
        }
        else if ( (stristr($msg, 'alíquota')) || (stristr($msg, 'cst')) || (stristr($msg, 'issqn')) ) {
            $codMsg = 'P03'; //'ALIQUOTA';
        }
        else if (stristr($msg, 'cnae')) {
            $codMsg = 'P02'; //'CNAE';
        }
        else if (stristr($msg, 'aedf')) {
            $codMsg = 'P01'; //'AEDF';
        }
        
        return $codMsg;
    }

}
?>