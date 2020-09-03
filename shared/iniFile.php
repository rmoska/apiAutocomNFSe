<?php
class iniFile{

    $iniData = array (); 

	// carrega array com dados
	function connect( $file ) { 
      
	  $iniArray = parse_ini_file($file, TRUE); 
	  $this->iniData[] = $iniArray; 
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
	

    function read($secao, $chave ) { 
	
		if (sizeof($this->iniData) == 0) 
    		return (FALSE); 
	
		if (!$this->keyExists($section, $key)) 
    		return (FALSE); 
	
		return ($this->iniData[0][$section][$key]); 
	} 

}
?>