<?php
require_once("common.php");
echo "Lettura allegati<br>";

$rows = getFetchArray("SELECT UrlAllegato,IdTipoAllegato FROM allegato");
echo "Numero totale allegati: ".count($rows)."<br>";
$totalSize = 0;
$counters = array();
for ($i = 0; $i<count($rows); $i++) {
	if ($i>0 && $i%100==0) echo "<br>$i) $totalSize";
	$path = dirname(__FILE__).'/../'.$rows[$i]['UrlAllegato'];
	//echo "<br>$path";
	if (file_exists($path))
		$totalSize += filesize($path);
	else
		echo "<br>non esiste $path";
	$tipo = $rows[$i]['IdTipoAllegato'];
	$counters[$tipo] = $counters[$tipo]>0?($counters[$tipo]+1):1;
}
echo "<br>Fine. Totalsize=$totalSize<br><br>Conteggi tipi allegato:<br>";
print_r($counters);
?>	