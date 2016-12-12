<?php 
// Test del collegamento ssh ad Experian
if (!function_exists('ssh2_connect')) 
	die("\nLA FUNZIONE ssh2_connect NON ESISTE\n");

// Connect
if (ssh2_connect('st.uk.experian.com', 22))
	die("\nCOLLEGAMENTO RIUSCITO\n");
else
	die("\nCOLLEGAMENTO FALLITO\n");
	
?>