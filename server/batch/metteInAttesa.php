<?php
require_once('../workflowFunc.php');

/* MODIFICARE IL CRITERIO DI SELEZIONE SECONDO NECESSITA' */
$criterio = $_SERVER["QUERY_STRING"];

// Esempi:
//   https://cnctest.tfsi.it/server/batch/metteInAttesa.php?CodContratto='LO372367'
//   https://cnctest.tfsi.it/server/batch/metteInAttesa.php?impinsoluto>20+and+(idstatorecupero=1+or+idclasse+is+null)
if ($criterio=="")
	die("Specificare la condizione da inserire nella WHERE come querystring");
else
{
	$criterio = urldecode($criterio);
	echo "criterio: $criterio<br>";
}

set_time_limit(9000); // aumenta il tempo max di cpu  
if (strtoupper(substr($criterio,0,6))=="SELECT")
	$sql = $criterio;
else
	$sql = "SELECT IdContratto from contratto c where $criterio ORDER BY IdContratto";
$ids = fetchValuesArray($sql);
trace("numero righe: ".count($ids)." sql=$sql",FALSE);
foreach ($ids as $IdContratto)
{
	metteInAttesa($IdContratto);	
}
trace("Fine elaborazione metteInAttesa.php",FALSE);
die("Fine elaborazione metteInAttesa.php");
?>	