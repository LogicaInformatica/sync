<?php
require_once('../engineFunc.php');

/* MODIFICARE IL CRITERIO DI SELEZIONE SECONDO NECESSITA' */
$criterio = $_SERVER["QUERY_STRING"];

// Esempi:
//   https://cnctest.tfsi.it/server/batch/classify.php?CodContratto='LO372367'
//   https://cnctest.tfsi.it/server/batch/classify.php?impinsoluto>20+and+(idstatorecupero=1+or+idclasse+is+null)
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

foreach ($ids as $IdContratto)
{
	segnaRecidivo($IdContratto);	
	classify($IdContratto,$changed);	
	if ($changed) 
	{
		echo "Contratto $IdContratto riclassificato<br>";
		$eventi = fetchValuesArray("SELECT DescrEvento FROM storiarecupero WHERE IdContratto=$IdContratto AND DataEvento>=NOW()-INTERVAL 10 SECOND");
		foreach ($eventi as $evento)
			echo "$evento<br>";
	}
	else
		echo "Contratto $IdContratto invariato<br>";
}
trace("Fine elaborazione classify.php",FALSE);
die("Fine elaborazione classify.php");
?>	