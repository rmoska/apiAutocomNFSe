<?php

//
	// transmite NFSe	
	$headers = array( "Content-type: application/xml",
										"Authorization: Bearer ".$nuToken ); 
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
	curl_setopt($curl, CURLOPT_URL, "https://nfps-e.pmf.sc.gov.br/api/v1/processamento/notas/processa");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlAss);
	//
	$result = curl_exec($curl);
	//
	$info = curl_getinfo( $curl );


?>