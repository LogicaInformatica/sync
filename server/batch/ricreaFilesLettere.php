<?php 
//==============================================================================================
// Da  usare per ricreare (su tmp) i files lettere di un dato giorno, a partire dalle lettere
// già archiviate
//**********************************************************************************************
$_SESSION['userContext'] = array();
require_once("commonbatch.php");

// PARAMETRI: id del modello e giorno
$IdModello = '5'; // 5=INS, 7=DEO, 8=DEO garante, 9=TEK
$dataDa    = '2014-09-05';
$dataA     = '2014-09-16'; // NB: indicare (le ore 0 del) giorno successivo al fine range 
 
$nomeModello = getScalar("SELECT TitoloModello FROM modello WHERE IdModello=$IdModello");
$fileName = "File_".$nomeModello."_".str_replace(":","-",date('c')).".txt"; 
trace("Preparazione file di lettere Rotomail $fileName",FALSE);
$newFile = LETTER_PATH."/$fileName"; 

// leggo gli allegati preparati nel giorno dati
$array = fetchValuesArray("SELECT IdAllegato FROM messaggiodifferito WHERE DATE(DataEmissione) BETWEEN '$dataDa' AND '$dataA' AND IdModello =$IdModello AND IdAllegato IS NOT NULL"); 
foreach ($array as $IdAllegato)
{
	echo "trattamento allegato $IdAllegato<br>";
	// leggo il percorso dell'allegatoget
	$row = getRow("SELECT UrlAllegato,ImpInsoluto FROM allegato a,contratto c WHERE a.idContratto=c.IdContratto AND IdAllegato=$IdAllegato");
  	$UrlAllegato = $row["UrlAllegato"];
	if(!$UrlAllegato)     // se non ricevo il persorso dell'allegato 
		die("Errore nella lettura dell'url allegato con idAllegato = $IdAllegato"); 
	if (!($row["ImpInsoluto"]>=26)) {
		continue; //  contratto non più in recupero
	}		
	$strTxt = file_get_contents(ATT_PATH."/../$UrlAllegato");  // prendo il contenuto dell'allegato
  	if (!file_put_contents($newFile,"$strTxt".TEXT_NEWLINE,FILE_APPEND))                                   // aggiungo i dati nel nuovo file 
		die("Errore nella creazione del file unico $newFile (lettere Rotomail)"); 
}
if (!file_put_contents($newFile,"@E".count($ArrIdAllegati),FILE_APPEND))  // record di chiusura
	die("Errore nella creazione del file unico $newFile (lettere Rotomail)"); 

die ("File $newFile creato");
?>