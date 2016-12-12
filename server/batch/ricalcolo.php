<?php
require_once("processImportedFiles.php");

/* RICALCOLO COMPLESSIVO: MODIFICARE IL CRITERIO DI SELEZIONE SECONDO NECESSITA' */
//$criterio = $_SERVER["QUERY_STRING"];
$criterio = " idstatorecupero=2 AND IFNULL(IdClasse,0) IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,39)";

// Esempi:
//   https://cnctest.tfsi.it/server/batch/ricalcolo.php?CodContratto='LO372367'
//   https://cnctest.tfsi.it/server/batch/ricalcolo.php?impinsoluto>20+and+(idstatorecupero=1+or+idclasse+is+null)
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
	$sql = "SELECT IdContratto from contratto where $criterio ORDER BY IdContratto";
$ids = fetchValuesArray($sql);

$idImportLog = 0;				
$listaClienti = array();

// la data di riferimento è quella dell'ultimo arrivo da OCS elaborato correttamente. Se infatti un contratto
// non è arrivato, vuol dire che nulla è cambiato fino alla data suddetta. Non è invece corretto usare
// la data dell'ultimo movimento dello specifico contratto, perché questo impedirebbe di vedere RID futuri 
// già registrati in data precedente la scadenza di rata
$dataRif = getScalar("SELECT MAX(ImportTime) FROM importlog WHERE FileType='movimenti' AND ImportResult='U'");
trace("Inizio ricalcolo per ".count($ids)." contratti. Data di riferimento: ".ISODate($dataRif)."\n",FALSE);
foreach ($ids as $IdContratto)
{
//	$dataRif = getScalar("SELECT MAX(DATE(LastUpd)) FROM movimento WHERE IdContratto=$IdContratto");
//	$haInsoluti = rowExistsInTable("insoluto","IdContratto=$IdContratto");
//	if ($dataRif>"2011-01-01" || $haInsoluti)
//	{
		//$ret = processInsoluti($IdContratto);	
		//$ret = processAndClassify($IdContratto,$dataRif);	

		$IdClasse = classify($IdContratto,$changed); // classificazione contratto
		if ($IdClasse==FALSE) // da controllare
		{
			die("Classify fallita idContratto=$IdContratto classe=$IdClasse");
		}
		
		echo "\nContratto $IdContratto riclassificato idClasse=$IdClasse<br>";
//	}
//	aggiornaCampiDerivati($IdContratto);
}

//affidaTutti($listaClienti);

trace("Fine elaborazione ricalcolo.php",FALSE);
die("Fine elaborazione ricalcolo.php");
?>	