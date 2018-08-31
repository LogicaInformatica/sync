<?php
/*
 * FUNZIONI PER ESTRAZIONE E COMUNICAZIONE CON EURO INVESTIGATION
 */
require_once ('workflowFunc.php');
$EIPATH = ATT_PATH.'/euroInvestigation';

riceveDatiEuroInvestigation(); // per provare

/*
 * ftp_getFiles
* Restituisce una lista di file di una data cartella ftp
* li sposta in una nuova cartella sul server toyota
*/
function riceveDatiEuroInvestigation(){
	trace('riceveDatiEuroInvestigation',false);
	global $EIPATH;
	//path della cartella
	$path = 'FLUSSO_DATI/EURO_TOYOTA';

	$ftp = connectToEuroInvestigation();
	if(!$ftp) {
		trace("Fine accesso EuroInvestigation perché la connectToEuroInvestigation() ha restituito 0/false");
		return false;
	}
	try{
		trace("Esegue ftp_pasv",false);
		ftp_pasv($ftp,true);
		trace("Esegue ftp_nlist su $path con connessione = $ftp",false);
		$file_list = ftp_nlist($ftp,$path);
		if ($file_list==FALSE) {
			trace("Fallita ftp_nlist su $path");
			return false;			
		}
		trace("Risultato della ftp_nlist: ".print_r($file_list,true),false);
		if(count($file_list) == 0)
			trace("Nessun file da processare",false);
		else {
			trace("Individuati ".count($file_list)." files da processare",false);
		}
		foreach($file_list as $iFile=>$file){
			trace("File n.".($iFile).": $file",false);
			$pathInfo = pathInfo($file);
			$zipFileName = $pathInfo['basename'];
			if((substr($zipFileName,0,3) !== 'OLD' || preg_match('/OLD_TOYOTA_Evase n. 13/',$zipFileName)) && preg_match('/\.zip$/',$file)){
				trace("Il file $file viene elaborato",false);
				$local_file = downloadEuroInvFiles($ftp,$zipFileName,$file); // fa download del file
				if ($local_file!==false) {
					trace("Download riuscito: avvia unzip",false);
					$list = unzipAndDelete($local_file);
					foreach ($list as $filename) {
						if (pathinfo($filename,PATHINFO_EXTENSION)=='pdf') {
							allegaFileEuroinvestigation($EIPATH,$filename);
						} else {
							trace("File $filename non processato perche' non e' un file PDF",false);
						}
					}
					$newFile = $path."/OLD_".$zipFileName;
					if(!ftp_rename($ftp,$file,$newFile))
						trace("Fallito rename del file $file in $newFile");
				} else {
					trace("Fallito download del file");
				}
			}else{
				trace("Il file $file non viene elaborato",false);
				$lastUpdFile = ftp_mdtm($ftp,$file);
				if($lastUpdFile < time() - GIORNI_CANCELLAZIONE*24*3600){
					if(ftp_delete($ftp,$file)) 
						trace("File obsoleto $file eliminato con successo",false);
					else
						trace("Fallita ftp_delete del file obsoleto $file",false);
				}
			}
		}
	}catch (Exception $e){
		$error = $e->getMessage();
		return false;
	}
}

/*
 * connectToEuroInvestigation
 * Apre la connessione FTP con Euro Investigation
 * @return {Object} connessione aperta $ftp
 */

function connectToEuroInvestigation(){
	trace('connectToEuroInvestigation', false);
	//set_time_limit(500);
	//SSH Host
	$server = '88.33.222.78';
	//SSH Port
	$port = 21;
	//username
	$username = 'userw';
	//password
	$pwd = 'fltymi1abfcU#';
	
	//crea connessione ftp al server
	try{
	  $conn = ftp_connect($server, $port);
	  if(false === $conn){
	  	throw new Exception("Non e' possibile stabilire una connessione al server");
	  }
	  $loggedIn = ftp_login($conn, $username, $pwd);
	  if(true === $loggedIn){
	  	trace("ftp_login a $server eseguita con successo", false);
	  }else{
	  	throw new Exception("Login ftp a $server non riuscito, user=$username pwd=$pwd");
	  }
	}catch(Exception $e){
		trace($e->getMessage());
	}
  	
	return $conn;
}



/*
 * downloadEuroInvFiles
 * Prende un file dalla cartella di origine in Euro Toyota
 * e lo sposta tra gli allegati
 */

function downloadEuroInvFiles($ftp,$fileName,$file){
	global $EIPATH;
	$localFile = "$EIPATH/$fileName";
	$ftp_get = ftp_get($ftp, $localFile,$file,FTP_BINARY);
	if($ftp_get){
		trace("Scrittura su $localFile terminata con successo\n",false);
		return $localFile;
	}else{
		trace("Errore di scrittura su $localFile");
		return false;
	}
}


/**
 * unzipAndDelete Decomprime un file zip nella stessa cartella in cui risiede, lo cancella e restituisce la lista dei files in esso contenuti
 * @param {String} $zipFile path completo del file .zip
 * @return {Array} lista dei filenames contenuti nello zip (si presume che non contenga subpaths)
 */
function unzipAndDelete($zipFile) {
	global $EIPATH;
	try
	{
		$zip = new ZipArchive;
		if (!$zip->open($zipFile)) {
			trace("Fallita open del file $zipFile");
			return false;
		} else {
			$list = array();
			for ($i=0; ;$i++) {
				$fn = $zip->getNameIndex($i);
				if (!$fn) break;
				$list[] = $fn;
			}
			if (!$zip->extractTo($EIPATH)) {
				trace("Fallita unzip del file $zipFile in $EIPATH");
				return false;
			}
			$zip->close();
			unlink($zipFile); // elimina il file zip originario
			return $list;
		}
	}
	catch (Exception $e)
	{
		trace("Errore nell'unzip del file $zipFile: ".$e->getMessage(),true,true);
		return false;
	}
}

/**
 * allegaFileEuroinvestigation
 * @param {String} $path percorso completo della cartella Euroinvestigation
 * @param {String} $fileName nome del file pdf da allegare 
 */
function allegaFileEuroinvestigation($path,$fileName) {
	try
	{
		// Inizio processo
		trace("Elaborazione file da allegare: $path/$fileName",false);

		// Nella prima versione, il filePath è composto come CodContratto.pdf oppure CodContratto_x.pdf (dove x è un progressivo)
		preg_match('/^[A-Z0-9]+/',$fileName,$arr);
		$codContratto = $arr[0];
		
		trace('Trovato codice contratto: '.$codContratto, false);
		// ottiene l'id di tutti i contratti intestati al nome dato
		$ids = getColumn("select IdContratto from contratto c WHERE codContratto =".quote_smart($codContratto));//JOIN cliente cl ON cl.IdCliente=c.IdCliente WHERE IFNULL(Nominativo,RagioneSociale) LIKE ".quote_smart($nomeCliente));
		if (count($ids)==0) {
			trace("File $fileName non caricato tra gli allegati, perche' non risulta alcun contratto legato al codice $codContratto",false,true);
			return false;
		}

		// ottiene l'id del tipo di allegato (Dossier Euroinvestigation)
		$idTipo = getScalar("SELECT IdTipoAllegato FROM tipoallegato where CodTipoAllegato='DOSS'");
		if (!$idTipo) {
			trace("'Dossier Euroinvestigation' non definito nella tabella 'tipoallegato'",FALSE);
			return FALSE;
		}

		// Loop di creazione degli allegati
		foreach ($ids as $IdContratto) {
            // 2018-08-31: Per evitare che dossier prodotti sullo stesso cliente in epoche successive siano sovrascritti, prepone
            // ad ogni filename la data di oggi
            $prefix = date('Ymd');
            rename("$path/$fileName","$path/{$prefix}_$fileName");
            $fileName = "{$prefix}_$fileName";
            
			$url = quote_smart("attachments/euroInvestigation/$fileName");
			$titolo = quote_smart('Dossier Euroinvestigation sul contratto n. '.$codContratto);
			$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,LastUser, IdTipoAllegato, FlagRiservato)"
			." VALUES($IdContratto,$titolo,$url,'system',$idTipo,'N')";
			$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
			beginTrans();
			writeHistory($idAzione,"Allegato documento",$IdContratto,$titolo);
			if (!execute($sql)) {
				return false;
			}
			commit();
			return true;
		}
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione dell'allegato. ".$e->getMessage(),true,true);
		return false;
	}
}