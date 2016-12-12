<?php
try 
{
	$cmd = $_GET["cmd"]; // comando da lanciare in formato riga comando con path relativo alla cartella server, ad esempio 'batch/testBatch.php Y'	
	if ($cmd=="")
		die("Specificare argomento cmd=<comando da lanciare>");
echo "/usr/bin/php ".dirname(__FILE__)."/../server/$cmd &gt;/dev/null &<br>";
	exec ("/usr/bin/php ".dirname(__FILE__)."/../server/$cmd >/dev/null &");
echo "php ".dirname(__FILE__)."/../server/$cmd &gt;/dev/null &<br>";
	exec ("php ".dirname(__FILE__)."/../server/$cmd >/dev/null &");
}
catch (Exception $e)
{
	die("Errore: ".$e->getMessage());
}
?>
