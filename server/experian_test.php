<?php
/**
 * FUNZIONI PER ESTRAZIONE E COMUNICAZIONE CON Experian
 */
require_once("common.php");
require_once("funzioni_experian.php");

// Durante i test:
error_reporting(E_ALL);
ini_set("display_errors",1);

// Controlla che il server Experian sia raggiungibile come DNS
//$host = "st.uk.experian.com";
//echo gethostbyname($host);
//var_export (dns_get_record ($host) );
//$dns = array("8.8.8.8","8.8.4.4");
//var_export (dns_get_record ($host,  DNS_ALL , $dns ));


try {
	/**** TEST INVIO
	if (creaFileExperian("CodRegolaProvvigione='25'",$error)) {
		echo "Invio OK";
	} else {
		echo "Invio fallito: $error";
	}
    */
	if (readAllExperianResponses($error)) {
		echo "Acquisizione OK";
	} else {
		echo "Acquisizione fallita: $error";
	}

	//inviaDatiExperian(3);
} catch(Exception $e) {
	echo $e->getMessage();
}
?>
