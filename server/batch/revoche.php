<?php
require_once("processImportedFiles.php");

/* Revoche di massa: FARE ATTENZIONE */
$criterio = $_SERVER["QUERY_STRING"];
// Esempi:
//   https://cnctest.tfsi.it/server/batch/ricalcolo.php?CodContratto='LO372367'
//   https://cnctest.tfsi.it/server/batch/ricalcolo.php?impinsoluto>20+and+(idstatorecupero=1+or+idclasse+is+null)
//if ($criterio=="")
//	die("Specificare la condizione da inserire nella WHERE come querystring");
//else
//{
//	$criterio = urldecode($criterio);
//	echo "criterio: $criterio<br>";
//}
// revoche del 5/5/14
$criterio="idcontratto in (select distinct c.idcontratto from contratto c
join movimento m on m.idcontratto=c.idcontratto
where date(m.dataregistrazione)='2014-05-05' and m.importo<0 and datainizioaffido='2014-05-05' and idtipomovimento=163
and ifnull(impinsoluto,0)<26)";

set_time_limit(9000); // aumenta il tempo max di cpu  
if (strtoupper(substr($criterio,0,6))=="SELECT")
	$sql = $criterio;
else
	$sql = "SELECT IdContratto from contratto where $criterio ORDER BY IdContratto";
$ids = fetchValuesArray($sql);
if (getLastError()>'')
	die(getLastError());
$idImportLog = 0;				
$listaClienti = array();

trace("Inizio revoche per ".count($ids)." contratti.",FALSE);
echo "<br>Inizio revoche per ".count($ids)." contratti.";

//die("<br>Fine di sicurezza, prima dell'esecuzione: togliere l'istruzione 'die'");
foreach ($ids as $IdContratto)
{
	revocaAgenzia($IdContratto,TRUE);
	if (!execute("UPDATE contratto SET idstatorecupero=1 WHERE IdContratto=$IdContratto"))
		die(getLastError());
}
updateOptInsoluti($criterio); // aggiorna l'ottimizzazione  

///// ATTENZIONE: se necessario mettere in stato ATT/NOR o altro
//execute("UPDATE contratto SET idstatorecupero=1 WHERE $criterio"); 

/////// ATTENZIONE: mette in attesa di affido STR: vale per correzioni del 1/7/13
//execute("UPDATE contratto SET idstatorecupero=25 WHERE $criterio"); 



trace("Fine elaborazione revoche.php",FALSE);
die("<br>Fine elaborazione revoche.php");
?>	