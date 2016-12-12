<?php
require_once('../workflowFunc.php');

/* MODIFICARE IL CRITERIO DI SELEZIONE SECONDO NECESSITA' */
//$criterio = $_SERVER["QUERY_STRING"];
$criterio = $_GET["where"];
//$criterio = 'select c.idcontratto from contratto c join v_dettaglio_insoluto i on c.idcontratto=i.idcontratto where c.impspeserecupero!=i.speseincasso;';
//$criterio = 'select c.idcontratto from contratto c WHERE impinsoluto!=impcapitale+impinteressimora+impaltriaddebiti+impspeserecupero+impinteressimoraaddebitati';
// Esempi:
//   https://cnctest.tfsi.it/server/batch/aggiornaCampiDerivati.php?CodContratto='LO372367'
//   https://cnctest.tfsi.it/server/batch/aggiornaCampiDerivati.php?impinsoluto>20+and+(idstatorecupero=1+or+idclasse+is+null)
if ($criterio=="")
	die("Specificare la condizione da inserire nella WHERE come querystring");
else
{
//	$criterio = urldecode($criterio);
	echo "criterio: $criterio<br>";
}
set_time_limit(9000); // aumenta il tempo max di cpu  
if (strtoupper(substr($criterio,0,6))=="SELECT")
	$sql = $criterio;
else
	$sql = "SELECT IdContratto from contratto c where $criterio ORDER BY IdContratto";
$ids = fetchValuesArray($sql);
if (getLastError()>"")
	die (getLastError());
echo "elaborazione ".count($ids)." contratti<br>";
foreach ($ids as $IdContratto)
{
	/* processa insoluti */
	if (!aggiornaCampiDerivati($IdContratto)) {
		echo "Errore in aggiornamento contratto $IdContratto<br>";
		die();
	} else {
		echo "Aggiornato contratto $IdContratto<br>";
	}
}
trace("Fine elaborazione aggiornaCampiDerivati.php",FALSE);
die("Fine elaborazione aggiornaCampiDerivati.php");
?>	