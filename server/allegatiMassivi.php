<?php
/**
 * allegatiMassivi: esegue le varie fasi previste per il caricamento di allegati massivi:
 * 1) caricamento del file zip da form
 * 2) elaborazione del file zip, tramite la nomenclatura del file pdf si ricava il codice contratto della pratica
 *    e la tipologia di lettera da inserire
 * 3) allegare i file pdf alla pratica corrispondente
 * 4) restituzione del messaggio con il numero degli allegati inseriti
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
	case "importFile": importFile();
		break;
	default:
		echo "{failure:true, task: '$task', messaggio:'$task sconosciuto'}";
	}
}

/*
 * importFile : prende il file scelto nel form Importazione file, lo carica nella cartella import
 */

function importFile(){
		
	global $context;
	extract($_REQUEST);
	//$infoModuloImport = array($IdModulo1, $IdModulo2, $IdModulo3, $IdModulo4); //array delle chiavi dei modulo di import
	//$info = array(); //array che conterr� le informazioni dei vari moduli di import
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
		
		$zipFile = TMP_PATH."/$fileName";
				
		if (!move_uploaded_file ($tmpName, $zipFile))	{
			fail("Impossibile copiare il file nella cartella $localDir");
		}
		
		$zip = new ZipArchive;
		if (!$zip->open($zipFile)) {
			trace("Fallito open del file $zipFile");
			fail("Fallito open del file $zipFile");
		} else {
			$numFileExtract = 0;
			for ($i=0; ;$i++) {
				$fn = $zip->getNameIndex($i);
				if (!$fn) break;
				//prendo il nome del file senza estensione
				$noext = basename($fn,".pdf");
				$arr = explode('-',$noext);
				$codContratto = $arr[0];
				$pattern = $arr[1];
				//tramite pattern cerco la tipologia di lettera da inserire (TipoAllegato)
				$idtipo = getScalar("SELECT IdTipoAllegato FROM tipoallegato WHERE Pattern LIKE '%".$pattern."%'");
				
				if ($idtipo=='') continue;
				$numFileExtract++;
				$pratica = getRow("SELECT IdContratto, IdCompagnia FROM contratto WHERE CodContratto='".$codContratto."'");
				// 14/8/2011: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
				// del processo corrente
				if (function_exists('posix_getpwuid')) {
					$processUser = posix_getpwuid(posix_geteuid());
					$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$processUser['name']."/".$codContratto;
				} else {	
					$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$codContratto;
				}
				
				if (!file_exists($localDir)) { // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
					if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
						setLastError("Impossibile creare la cartella $localDir");
						trace("Impossibile creare la cartella $localDir");
						fail("Impossibile creare la cartella $localDir");
					}		
				}
				//trace("allegaDocumento 3",false);
				
				if ($zip->extractTo($localDir,$fn)) 
				{
					$titolo = quote_smart($noext);
					$IdUtente = $context["IdUtente"];
					$IdContratto = $pratica['IdContratto'];
					$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fn);			
					$userid = getUserName($IdUtente);
					$userid = quote_smart($userid);
					$riservato = quote_smart('N');
					
					$master=$context["master"];
					//trace("master ".$master);
					$varNameS='';
					$Master='';
					if($master!=''){
						$varNameS=',lastSuper';
						$Master=",'$master'";
					}
					
					$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,"
										." IdUtente,LastUser, IdTipoAllegato, FlagRiservato) "
										."VALUES($IdContratto,$titolo,$url,$IdUtente,$userid,$idtipo,$riservato)"; 
		//			trace("sql $sql");
					$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
					writeHistory($idAzione,"Allegato documento",$pratica['IdContratto'],"Documento: $titolo Contratto:".$codContratto);				
					if (!execute($sql)) {
						$msg = "Errore nell'insert dell' UrlAllegato=$url nella tabella Allegato";
						trace($msg,false);	
						fail($msg);
					}
				}
				else
				{
					setLastError("Impossibile copiare il file nel repository");
					trace("Impossibile copiare il file nel repository");
					fail("Impossibile copiare il file nel repository");
				}
			}
			$zip->close();
			unlink($zipFile); // elimina il file zip originario
		}
				
		//$info[]= array("filePath"=>$filePath);
		$info="Estratti numero $numFileExtract dal file massivo";
	}
	success($info);
}
?>