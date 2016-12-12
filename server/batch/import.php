<?php
require_once('commonbatch.php');
require_once('processImportedFiles.php');
//----------------------------------------------------------------------------------------------------------------------
// import
// Scopo: 		Ricevitore dei file di import dai servizi Windows 
// Funzionamento: riceve un file, lo controlla e lo salva nel folder che sarà poi processato dal cron-job
// Argomenti:	type:	Tipo di file (ad es. "clienti")
//              from:   sistema mittente (ad es. TFSI)
//              id:     identificativo univoco del file, secondo il mittente
//
// Risposta:	U\t messaggio			validazione file OK (l'elaborazione è asincrona in background)
//	     		K\t messaggio			KO: errore di validazione
//----------------------------------------------------------------------------------------------------------------------
$pageurl = $_SERVER["REQUEST_URI"]; // nome pagina con parametri

//-------------------------------------------------------
// Controllo parametri
//-------------------------------------------------------
$type = $_REQUEST["type"].$_REQUEST["TYPE"];
$from = $_REQUEST["from"].$_REQUEST["FROM"];
$id   = $_REQUEST["id"].$_REQUEST["ID"];
 
if ($type=="")
	returnError("Parametro 'type' assente",$pageurl,FALSE);

if ($from=="")
	returnError("Parametro 'from' assente",$pageurl,FALSE);

if ($id=="")
	returnError("Parametro 'id' assente",$pageurl,FALSE);
 
//-------------------------------------------------------
// Controllo del file ricevuto
//-------------------------------------------------------
$files = $_FILES;
$nfiles = count($files);

if ($nfiles!=1)
	returnError("File upload non valido: sono stati inviati $nfiles files",$pageurl,FALSE);

// Trasferisce su directory temporanea
$filepath = $files["filename"]["tmp_name"]; // file temporaneo creato dal sistema

if (!$filepath) 
	returnError("File upload fallito: ".print_r($files["filename"],true)." max_upload_filesize=".ini_get('upload_max_filesize'),$pageurl,FALSE);

if($type!='allegato') // il file ricevuto dal sistema mittente non è un allegato
{			
		// Legge le righe e verifica che siano JSON ok
		$file = fopen($filepath,'r');
		if (!$file)
			returnError("File upload fallito per errore nella open del file temporaneo $filepath;",$pageurl,FALSE);
		
		// Conta e decodifica le righe JSON	
		for ($nrows=0; ($buffer = fgets($file)) !== false; $nrows++)	
		{
			$json = json_decode($buffer);
			if ($json == NULL)
				returnError("La riga n. " . ($nrows+1) . " del file ha un formato invalido",$pageurl,FALSE);
		}
		if (!feof($file)) 
			returnError("File upload fallito per errore nella lettura del file temporaneo $filepath (dopo $nrows righe)",$pageurl,FALSE);
		// L'ultima riga è il record finale che contiene il conto delle righe: controlla
		$rows = $json->rows;
		if ($rows == NULL)
			returnError("Il file ricevuto non contiene un record di chiusura valido",$pageurl,FALSE);
		else if ($rows != $nrows-1)
			returnError("Il record di chiusura indica un numero di righe ($rows) diverso dal numero di righe presenti prima di esso (" .($nrows-1).")",$pageurl,FALSE);
			
		fclose($file);
		
		//-------------------------------------------------------
		// Scrive su directory per il processo successivo
		//-------------------------------------------------------
		$prefix = str_pad($id,10,"0",STR_PAD_LEFT);
		$newfile = TMP_PATH ."/import/$from"."_".$prefix."_".$type;
		if (!copy($filepath,$newfile))
			returnError("File upload fallito per errore nella scrittura del file temporaneo $newfile",$pageurl,FALSE);

		//-------------------------------------------------------
		// Scrive su ImportLog lo stato del batch di import
		//-------------------------------------------------------
		if (writeImportLog($from,$type,$id,"N")) // registra come nuovo arrivo
		{
			trace("File $type ricevuto e convalidato; $nrows righe salvate in $newfile",FALSE);
			die("U\tFile ricevuto e convalidato; $nrows righe salvate in $newfile");
		}
		else
			die("K\tErrore nella writeImportLog");
}
else   // il file inviato dal sistema mittente è un allegato
{
	if (!writeImportLog($from,$type,$id,"N"))  // registra come nuovo arrivo
		die("K\tErrore nella writeImportLog");
	
	$idImportLog = getscalar("select IdImportLog from importlog where FileId=$id");	
	
	$titoloAllegato = $_REQUEST["titolo"];     // titolo allegato ricevuto dal sistema mittente
	$tipoAllegato = $_REQUEST["tipoAllegato"]; // tipo allegato ricevuto dal sistema mittente 'c' se contratto
	$codContratto = $_REQUEST["codContratto"]; // codice contratto ricevuto dal sistema mittente

	// processa l'allegato copiandolo nella cartella attachments ed inserendolo nella tab allegato
	if(!processAllegato($from,$type,$id,$codContratto,$titoloAllegato,$tipoAllegato,$idImportLog))
	{	
		writeResult($idImportLog,'K',"Errore durante elaborazione allegato");
		die("K\tErrore durante elaborazione allegato");
	}
	changeImportStatus($idImportLog,'P');
	writeResult($idImportLog,'U',"Acquisito allegato con fileId=$id per il contratto $codContratto");
	die("U\tAllegato elaborato");
}	
?>