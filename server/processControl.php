<?php
/**
 * processControl: esegue le varie fasi previste per il caricamento di un file nell'import:
 * 1) caricamento dei files da form
 * 2) lancio del programma di import generalizzato importProcessor.php o di un programma di import personalizzato nelle tre modalit�:
 * v: verifica
 * p: precaricamento nelle tabelle transitorie temp_import_*****
 * l: caricamento nelle tabelle definitive con rimpiazzo totale del lotto (quindi preceduto da uno stornoLotto)
 * u: caricamento nelle tabelle definitive con aggiornamento del lotto gi� caricato
 * 
 */
require_once("workflowFunc.php");
require_once("userFunc.php");
/*
 * chiude la sessione per evitare di bloccare le richieste ajax concorrenti
 */
session_write_close();
doMain();

function doMain()
{
	global $context;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch($task)
	{
	case "importFile":importFile();
		break;
	case "cmdUnix":cmdUnix();
		break;
	case "esegueImportProcessor":
		esegueImportProcessor();
		break;
	default:
		echo "{failure:true, task: '$task', messaggio:'$task sconosciuto'}";
	}
}

/*
 * importFile : prende il file scelto nel form Importazione file, lo carica nella cartella import
 */

function importFile(){
	
	extract($_REQUEST);
	$infoModuloImport = array($IdModulo1, $IdModulo2, $IdModulo3, $IdModulo4); //array delle chiavi dei modulo di import
	$info = array(); //array che conterr� le informazioni dei vari moduli di import
	//trace(print_r($_FILES, TRUE), false);
	foreach($_FILES as $key=>$file){
	
		$tmpName  = $file['tmp_name'];
		
		if($tmpName == '') continue;
		$fileName = $file['name'];
		$fileSize = $file['size'];
		$fileType = $file['type'];
	
		$fileName=urldecode($fileName);
		if(!get_magic_quotes_gpc())
			$fileName = addslashes($fileName);
		
		$localDir = TMP_PATH."/wizard";
		if (!file_exists($localDir)) {
			if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
				fail("Impossibile creare la cartella $localDir");
			}
		}
		$filePath = "$localDir/$fileName";
		if (!move_uploaded_file ($tmpName, $filePath))	{
			fail("Impossibile copiare il file nella cartella $localDir");
		}
		// autorizza alla scrittura ?
		//if (!chown($filePath,0)) {
		//	trace("Fallita chown su $filePath",0);
		//}
		chmod($filePath,0777);
		
	    $i = substr($key,-1); //
	    $index = $infoModuloImport[$i-1]; //variabile che identifica l'IdModulo relativo al file che si sta importando
	    $info[]= array("filePath"=>$filePath, "IdModulo"=>$index);
	}
	success($info);
}
/*
 * funzione cmd post importFile
 * Da php si esegue un comando unix per lanciare il nome del programma di import associato al modulo di import
 * Il programma deve essere chiamato in tre fasi (tipi di operazione):
 * v: verifica
 * p: precaricamento nelle tabelle transitorie temp_import_*****
 * l: caricamento nelle tabelle definitive (modalit� "rimpiazza")
 * u: caricamento nelle tabelle definitive (modalit� "aggiorna")
 * 
 * PARAMETRI (ricevuti da form):
 * - comando: nome del programma da eseguire, ad es. importProcessor.php
 * - IdLotto: Id del lotto che si sta elaborando
 * - oper: tipo di operazione v/p/l/u (vedi commento sopra)
 * - processName: nome convenzionale dato al processo (stringa generata random) per riconoscerne le righe in processlog
 */
	
function cmdUnix(){
	global $context;
	extract($_REQUEST);
	$infofile = json_decode($info, true);
	foreach ($infofile as $numFile=>$f){
		$comando = getScalar("SELECT Comando FROM moduloimport WHERE IdModulo =".$f['IdModulo']);//nome programma che verr� eseuito
		$cmd = "php -d display_errors=on -f ".__DIR__."/$comando {$IdLotto} {$f['IdModulo']} \"{$f['filePath']}\" $oper \"$processName\" $numFile {$context['Userid']}";
		trace("Esecuzione del comando Unix: $cmd",false);
		exec($cmd, $output, $ret);
		if (count($output)>0) {
			trace("Output del programma: \n".print_r($output,true),false);
		}
		//if ($oper=='l' or $oper=='u') { // la 3a fase non deve essere eseguita per ciascun file, ma solo una volta per tutti
		//	break;
		//}
	}
	switch($oper) {
		case 'v':
			$msg = "<b>Fine verifica</b>. Se non sono state segnalate anomalie gravi, puoi usare il pulsante 'Caricamento preliminare' per caricare i dati nelle tabelle transitorie";
			break;
		case 'p':
			$msg = "<b>Fine caricamento preliminare</b>. Se non sono state segnalate anomalie gravi, puoi usare il pulsante 'Caricamento finale' per caricare i dati nelle tabelle definitive,"
			." oppure il tasto 'Anteprima delle tabelle' per vedere i dati caricati.";
			break;
		case 'l':
		case 'u':
			$msg = "<b>Fine aggiornamento lotto</b>. Se non sono state segnalate anomalie gravi, i dati del lotto sono adesso visibili con le funzioni ordinarie di DCSys";
			break;
	}
	writeProcessLog($processName, $msg, -1);
	// Se si tratta di un run di tipo "p" (precaricamento), determina quali tabelle risultano caricate con dati di questo lotto
	// e ne restituisce l'elenco al chiamante
	if ($oper=='p') {
		$result = array();
		foreach ( array("cliente","contratto","garante","recapito","posizione","movimento") as $table) {
			if (rowExistsInTable("temp_import_$table","IdLotto=$IdLotto")) {
				$result[] = $table;
			}
		}
	} else {
		$result = null;
	}
	success($result);
}

/*
 * funzione esegueImportProcessor
 * Nuova funzione per eseguire il programma di import senza usare il comando exec di Unix, che provoca problemi nell'autorizzazione a leggere i files
 * (su alcuni server, sui quali l'utente che crea i file [apache] non ha i poteri per mettere chown 0)
 * In questa versione, la chiamata avviene includendo il particolare file php e chiamando la funzione importProcessorMain, che è necessario sia
 * contenuta in ogni importProcessor (così come lo è in quello standard). PER ORA E' NECESSARIO CHE IN UNA DATA ELABORAZIONE SI USI SOLO UNO STESSO
 * importProcessor per TUTTI i file del gruppo, altrimenti la "import" carica funzione duplicata e va quindi in errore sul secondo import
 * 
 * L'importProcessor viene chiamato in tre fasi consecutive (tipi di operazione):
 * 1a fase: v: verifica
 * 2a fase: p: precaricamento nelle tabelle transitorie temp_import_*****
 * 3a fase: l: caricamento nelle tabelle definitive (modalità "rimpiazza")
 * 			u: caricamento nelle tabelle definitive (modalità "aggiorna")
 *
 * In ogni fase processa ciascuno degli N files caricati
 * 
 * PARAMETRI (ricevuti da form):
 * - comando: nome del programma da eseguire, ad es. importProcessor.php
 * - IdLotto: Id del lotto che si sta elaborando
 * - oper: tipo di operazione v/p/l/u (vedi commento sopra)
 * - processName: nome convenzionale dato al processo (stringa generata random) per riconoscerne le righe in processlog
 */

function esegueImportProcessor(){
	extract($_REQUEST);
	$infofile = json_decode($info, true);
	foreach ($infofile as $numFile=>$f){
		$comando = getScalar("SELECT Comando FROM moduloimport WHERE IdModulo =".$f['IdModulo']); // nome file php che verrà eseguito
		if(!$comando){
			$error = "Manca la definizione del modulo con id=".$f['IdModulo'];
			writeProcessLog($processName, $error, 1);
			writeProcessLog($processName, "Elaborazione interrotta a causa dell'errore indicato nel messaggio precedente", -1);
			writeLog("APP","Importazione lotto",$error,"IMP_LOTTO");
			return;
		}
		try {
			
			trace("Caricamento del modulo di import {$f['IdModulo']}, programma=$comando",false);
			include_once $comando;
			trace("Caricamento riuscito",false);
			
			importProcessorMain($IdLotto,$f['IdModulo'],$f['filePath'],$oper,$processName,$numFile);
			trace("Elaborazione completata",false);
		} catch(Exception $e) {
			$error = $e->getMessage();
			writeProcessLog($processName, $error, 1);
			writeProcessLog($processName, "Elaborazione interrotta a causa dell'errore indicato nel messaggio precedente", -1);
			writeLog("APP","Importazione lotto",$error,"IMP_LOTTO");
		}			
	}
	switch($oper) {
		case 'v':
			$msg = "<b>Fine verifica</b>. Se non sono state segnalate anomalie gravi, puoi usare il pulsante 'Caricamento preliminare' per caricare i dati nelle tabelle transitorie";
			break;
		case 'p':
			$msg = "<b>Fine caricamento preliminare</b>. Se non sono state segnalate anomalie gravi, puoi usare il pulsante 'Caricamento finale' per caricare i dati nelle tabelle definitive";
			break;
		case 'l':
		case 'u':
			$msg = "<b>Fine aggiornamento lotto</b>. Se non sono state segnalate anomalie gravi, i dati sono adesso visibili con le funzioni ordinarie di DCSys";
			break;
	}
	writeProcessLog($processName, $msg, -1);
	// Se si tratta di un run di tipo "p" (precaricamento), determina quali tabelle risultano caricate con dati di questo lotto
	// e ne restituisce l'elenco al chiamante
	if ($oper=='p') {
		$result = array();
		foreach ( array("cliente","contratto","garante","recapito","posizione","movimento") as $table) {
			if (rowExistsInTable("temp_import_$table","IdLotto=$IdLotto")) {
				$result[] = $table;
			}
		}
	} else {
		$result = null;
	}
	success($result);
}
