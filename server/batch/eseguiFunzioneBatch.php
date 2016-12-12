<?php
require_once("processImportedFiles.php");
require_once("mailsOverride.php");
require_once("estrattoSpeseRecupero.php");
require_once("../funzioni_experian.php");

if ($argc)
	$call = $argv[1];
else
	$call = $_SERVER["QUERY_STRING"];
// Esempi:
//   https://cnctest.tfsi.it/server/batch/eseguiFunzioneBatch.php?aggiornaProvvigioni()
if ($call=="")
	die("Specificare la funzione da chiamare, come querystring");
else
{
	set_time_limit(9000); // aumenta il tempo max di cpu  
	$call = urldecode($call);
	echo "Esecuzione di: $call<br>";

	trace("Esecuzione di: $call",FALSE);
	eval("\$result = $call;");

	var_dump($result);

	trace("risultato=$result",FALSE);
}

trace("Fine elaborazione $call",FALSE);
die("<br>Fine elaborazione $call");

?>