<?php
class iniFile{

    private $iniData = array (); 

	// carrega array com dados
	function connect( $file ) { 
      
	  $iniArray = parse_ini_file($file, TRUE); 
	  $this->iniData[] = $iniArray; 

	  print_r($this->iniData);

	  error_log(utf8_decode("[".date("Y-m-d H:i:s")."] D=".$this->iniData[0][2927408-H][EnviarLoteRpsEnvio]."\n"), 3, "../arquivosNFSe/envNFSe.log");
//	  error_log(utf8_decode("[".date("Y-m-d H:i:s")."] S=".$section." K=".$key." D=".$arr."\n"), 3, "../arquivosNFSe/envNFSe.log");


	} 


	function getSections() { 
	
		$getData = $this->iniData[0]; 
		return(array_keys($getData)); 
	} 

    
	function getKeys($section) { 
	
		if (!$this->sectionExists($section)) 
    		return (FALSE); 
	
		$getData = $this->iniData[0][$section]; 
	
		return(array_keys($getData)); 
	} 


	function sectionExists($section) { 
	
		$sections = $this->getSections(); 
	
		for ($i = 0; $i < sizeof($sections); $i++) {
    		if ($sections[$i] == $section) 
	        	return (TRUE); 
        }
	
		return (FALSE); 
	} 


	function keyExists($section, $key) { 
	
		if (!$this->sectionExists($section)) 
    		return (FALSE); 
	
		$keys = $this->getKeys($section ); 
	
		for ($i = 0; $i < sizeof($keys); $i++) {
            if ($keys[$i] == $key) 
                return (TRUE); 
        }
	
		return (FALSE); 
	} 
	

    function read($section, $key ) { 
	
		if (sizeof($this->iniData) == 0) 
    		return (FALSE); 
	
		if (!$this->keyExists($section, $key)) 
    		return (FALSE); 
	
		return ($this->iniData[0][$section][$key]); 
	} 

}
?>