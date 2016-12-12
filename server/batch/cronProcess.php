<?php 
//==============================================================================================
// cronProcess.php     (by Aldo)
// Avvia i processi batch.
// =============================================================================================

//**********************************************************************************************
//****************************************MAIN**************************************************
//**********************************************************************************************
$_SESSION['userContext'] = array();
require_once("processImportedFiles.php");
require_once("mailsOverride.php");
require_once('funzioniStorico.php');
require_once("estrattoSpeseRecupero.php");
require_once("../funzioni_experian.php");
ini_set("memory_limit","2048M");

try
  {

	ob_implicit_flush(TRUE);
  	// query: preleva tutti gli eventi non sospesi ed eseguibili nell'orario di avvio  dalla tabella eventosistema
  	$sql  = "SELECT IdEvento FROM eventosistema"
		 ." WHERE CURTIME() between IFNULL(OraInizio,'00:00:00')"
		 ." And IFNULL(OraFine,'23:59:99') AND IFNULL(FlagSospeso,'N') IN ('N','U')";

	$ArrayIdEventi = fetchValuesArray($sql); // contiene tutti gli id degli eventi ricevuti dal db 
	//trace(print_r($ArrayIdEventi,TRUE),FALSE);
	// se non ce nessun evento di sistema da avviare
	if (count($ArrayIdEventi)==0)
	{
		trace("cronProcess.php: Nessun evento da avviare (sql: $sql)",FALSE);
		die("cronProcess.php: Nessun evento da avviare");
	}
	
	// se ci sono eventi di sistema da avviare, ciclo e chiamo la funzione che esegue gli automatismi dell'evento
	foreach ($ArrayIdEventi as $IdEvento)
	{
		trace("Verifica automatismi legati ad evento $IdEvento");
		if(!eseguiAutomatismiEvento($IdEvento))
		{
			trace("cronProcess.php: Errore nell'esecuzione degli Automatismi dell'evento $IdEvento.");
			echo "cronProcess.php: Errore nell'esecuzione degli Automatismi dell'evento $IdEvento.";
			// da vedere se bisogna abbandonare l'esecuzione oppure continuare / riprovare ad ri-eseguire l'evento
		}
		// Cambia il FlagSospeso=U in flagSospeso=Y (U significa esecuzione una sola volta)
		$sql = "UPDATE eventosistema SET FlagSospeso='Y' WHERE FlagSospeso='U' AND IdEvento=$IdEvento";
		execute($sql);
	} 	
 }
catch (Exception $e)
  {
	  trace ("\nErrore nell'avvio degli automatismi automatici:".$e->getMessage());
	  exit();
  } // end catch  

  
//******************************************************************************** 
//************************************Fine Main*********************************** 
//******************************************************************************** 
    
  
//******************************************************************************** 
//************************************FUNZIONI************************************ 
//******************************************************************************** 

//--------------------------------------------------------------------------------
// eseguiAutomatismiEventi
// legge tutti gli automatismi associati all'evento e li esegue
//--------------------------------------------------------------------------------
function eseguiAutomatismiEvento($IdEvento)
{
	try  
	{
		$sql = "select a.* from automatismoevento ae, automatismo a where ae.IdEvento=$IdEvento"
		." and ae.IdAutomatismo=a.IdAutomatismo ORDER BY a.IdAutomatismo";	

		$datiCnt = getFetchArray($sql);
		trace("Numero di automatismi: ".count($datiCnt),FALSE);
		foreach ($datiCnt as $value) 
		{
			$tipoautomatismo = strtoupper($value['TipoAutomatismo']);
			trace("Automatimo ".$tipoautomatismo." comando=".$value['Comando']." condizione=".$value["Condizione"],FALSE);
			if ($value["Condizione"]>"") // condizione booleana da verificare in SELECT senza FROM
			{
				$res = getScalar("SELECT ".$value["Condizione"]);
				$doIt = ($res==1); 
				if (!$doIt)
					trace("Automatismo non eseguito perché la condizione non è soddisfatta",FALSE);
			}
			else
				$doIt = TRUE;
				
			if ($doIt)
			{	
				switch (strtoupper($tipoautomatismo)) 
				{
    				case "PHP":
						trace("Esegue funzione php ".$value['Comando'],FALSE);
    					eval($value['Comando']);				
    					break;
    				case "SQL":
						trace("Esegue comando SQL ".$value['Comando'],FALSE);
						execute($value['Comando']);
    					break;
				}//end switch
			}
		}//end try
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		return FALSE;
	}
}

//================================================================================
// inviaEmailDifferite     (by Aldo)
// Invia email per il sollecito insoluti
// Restituisce:
//      true :	tutto OK
//      false:  errore nell'elaborazione oppure nessuna email da elaborare
//================================================================================
function inviaEmailDifferite()
{
  try
  {
  	  // leggo dalla tabella messaggioDifferito i record in stato ="C" e tipo="E"	
	  $sql = "SELECT * FROM messaggiodifferito M WHERE M.Stato ='C' AND M.Tipo='E'";
      $result = getFetchArray($sql);

	  if(empty($result))        // se non esistono messaggidifferiti da elaborare
	  {
	    trace("Nessuna email differita da inviare.",false);
	    return false;           // esco dalla funzione
	  }
	  
	  // se esistono messaggidifferiti da elaborare 
	  foreach($result as $row) // scorro l'array dei risultati  
	  {
	  	$ret=inviaSingolaEmailDiff($row['IdModello'],$row['IdContratto'],$row['IdMessaggioDifferito']);		
	  } // end foreach
	  return true;
  }// end try
  catch (Exception $e)
  {
	  trace("Errore nell'invio delle email differite:".$e->getMessage());
  	  return false;
  } // end catch  
}

//================================================================================


//================================================================================

// inviaSmsDifferiti     (by Aldo)
// Invia gli sms differiti
// Argomenti: $msg : il messaggio di ritorno (by reference) in caso di errore
// Restituisce:
//      true :	tutto OK
//      false:  errore nell'elaborazione oppure nessun messaggio da elaborare
//================================================================================
function inviaSmsDifferiti($condition="TRUE")
{
  try
  {
  	  // se disattivati, esce
  	  if (SMS_TEST_NR=='dummy')
  	  	return FALSE;
  	  	
  	  // leggo dalla tabella MessaggioDifferito i record in stato ="C" e tipo="S"	
	  $sql = "SELECT * FROM messaggiodifferito M WHERE M.Stato ='C' AND M.Tipo='S' AND $condition ";
      $result = getFetchArray($sql);
	  if (empty($result))        // se non esistono messaggidifferiti da elaborare
	  {
	    trace("Nessun SMS differito da inviare.",false);
	    return false;           // esco dalla funzione
	  }
	  
	  // se esistono messaggidifferiti da elaborare 
	  foreach($result as $row) // scorro l'array dei risultati  
	  {
	  	$ret=inviaSingoloSmsDiff($row['IdModello'],$row['IdContratto'],$row['IdMessaggioDifferito']);	
	  } // end foreach
	  
	  // Elimina le righe che erano in stato 'S' (sospese), in modo che non blocchino i futuri messaggi differiti
	  // sugli stessi contratti
	  execute("DELETE FROM messaggiodifferito WHERE Stato='S' AND Tipo='S'");
	 
	  return true;
  }// end try
  catch (Exception $e)
  {
	  trace("Errore nell'invio degli sms differiti: ".$e->getMessage());
	  return false;
  } // end catch  
}


//================================================================================
// elaboraLettere     (by Aldo)
// Elabora i messaggi differiti di tipo L (lettere rotomail)
//         creando le relative lettere ed il file unico 
// Restituisce:
//      UrlFile :	l'url del file unico creato
//      false:  errore nell'elaborazione oppure nessun messaggio da elaborare
//================================================================================
function elaboraLettere()
{
  global $context;
  $newFile="";
  try
  {
  	  // leggo dalla tabella MessaggioDifferito i record in stato ="C" e tipo="L"	
	  $sql = "SELECT * FROM messaggiodifferito M WHERE M.Stato ='C' AND M.Tipo='L' ORDER BY IdModello,IdContratto";
	  $result = getFetchArray($sql);
	  //trace("result ".print_r($result,true));
	  if(empty($result))        // se non esistono messaggidifferiti da elaborare
	  {
	    trace("Nessuna lettera da stampare.",false);
	    return false;           // esco dalla funzione
	  }
	  trace("Elaborazione lettere per invio massivo (num =  ".count($result).")",FALSE);
	  // Elabora tutte le lettere individuate
	  $lastModello = "";
	  foreach($result as $row) // scorro l'array dei risultati  
	  {
		if ($lastModello!=$row['IdModello']) // break sul tipo modello
		{
			if ($lastModello>"") // deve fare il file cumulativo fin qui creato
				creaFileLettere($ArrIdAllegati,$lastModello);
			$lastModello = $row['IdModello'];
			unset($ArrIdAllegati);	
		}
	  	
		// chiamo la funzione che genera l'allegato
	  	//trace("mod ".$row['IdModello']." idCont ".$row["IdContratto"]." idMdiff ".$row["IdMessaggioDifferito"]);
	  	$res = creaStampa($row['IdModello'],$row["IdContratto"],$row["IdMessaggioDifferito"]); // elabora l'allegato
	  	//trace("res ".$res);
	  	if($res>0)
			$ArrIdAllegati[] = $res;  // prendo tutti gli id degli allegati, lo uso dopo per riprendere gli allegati e fare il file unico
		else if ($res==-1) {}  // indica allegato non creato ma senza errori
		else {
			trace("Elaborazione interrotta per un errore nella creaStampa",FALSE);
			return FALSE;
		}
	  } // end foreach
	  
	  // Crea l'ultimo file di stampa
	  if (count($ArrIdAllegati)>0)
	 	 creaFileLettere($ArrIdAllegati,$lastModello);
	  trace("Fine elaborazione lettere",FALSE);
	  return TRUE;
  }// end try
  catch (Exception $e)
  {
	  trace("Errore nell'elaborazione lettere differite: ".$e->getMessage()); 
	  return false;
  } // end catch  
}

/**
 * creaFileLettere
 * Crea un file contenente tutte le lettere predisposte per l'invio massivo. Se sono di tipo txt, il file è semplicemente una concatenazione
 * di files; se sono di tipo pdf, il file è uno zip che le contiene tutte. Invia poi una mail di notifica agli utenti predefiniti
 * @param {Array} $array lista degli ID degli "allegati" da concatenare
 * @param {Number} $IdModello ID del modello da cui sono prodotte le lettere
 */
//----------------------------------------------------------------------------------------------------	
// creaFileLettere
// Crea il file per la stampa massiva e invia una mail di notifica agli utenti predefiniti
  
//----------------------------------------------------------------------------------------------------	
function creaFileLettere($array,$IdModello)
{
  try
  {
  	list($nomeModello,$filename) = getRow("SELECT TitoloModello,FileName FROM modello WHERE IdModello=$IdModello",MYSQLI_NUM);
  	
  	if (preg_match('/html?$/i',$filename)) {
  		// GENERAZIONE FILE ZIP Contenente tutte le lettere fprmato PDF
  		$fileName = "File_".$nomeModello."_".str_replace(":","-",date('c')).".zip";
  		trace("Preparazione file zippato di lettere $fileName",FALSE);
  		$newFile = LETTER_PATH."/$fileName";
  		
  		$zip = new ZipArchive;
  		if ($zip -> open($newFile, ZIPARCHIVE::CREATE)!==TRUE) {
  			trace("Impossibile creare il file $newFile",true,true);
  			return false;
  		}
  			
		// leggo gli allegati elaborati nel passo precedente e li aggiungo allo zip file
		foreach ($array as $IdAllegato)
		{
			// leggo il percorso dell'allegato
		  	$UrlAllegato = getScalar("SELECT UrlAllegato FROM allegato WHERE IdAllegato=$IdAllegato");
			if(!$UrlAllegato)  {    // se non ricevo il persorso dell'allegato
				trace("Errore nella lettura dell'url allegato con idAllegato = $IdAllegato",false); 
				return false;
			}
		  	$zip -> addFile(ATT_PATH."/../$UrlAllegato",  $addedfile = pathinfo($UrlAllegato,PATHINFO_BASENAME));
			trace("Aggiunto file $addedfile allo zip file",false);
		}
		$zip->close();
		
		// Invia mail all'amministratore con il link al file zip
		$zipUrl = LETTER_URL."/$fileName";
		sendMail("cnc".$sito."@toyota-fs.com",getSysParm("LETTERE_MAIL"),"File zip prodotto per stampa massiva lettere",
				"Il file zip contenente tutte le lettere generate in formato PDF e' scaricabile a questo indirizzo: <a href='$zipUrl'>$zipUrl</a>");
		
  	} else {
  		// GENERAZIONE FILE TESTO CUMULATIVO PER STAMPA Rotomail da testo  	 	
		$fileName = "File_".$nomeModello."_".str_replace(":","-",date('c')).".txt"; 
		trace("Preparazione file di lettere Rotomail $fileName",FALSE);
		$newFile = LETTER_PATH."/$fileName"; 
	
		// leggo gli allegati elaborati nel passo precedente e creo un file unico per tutti gli allegati 
		foreach ($array as $IdAllegato)
		{
			// leggo il percorso dell'allegato
		  	$UrlAllegato = getScalar("SELECT UrlAllegato FROM allegato WHERE IdAllegato=$IdAllegato");
		  	//trace("urlallegato $UrlAllegato");
			if(!$UrlAllegato)     // se non ricevo il persorso dell'allegato 
			{
				trace("Errore nella lettura dell'url allegato con idAllegato = $IdAllegato",false); 
				return false;
			}
			$strTxt = file_get_contents(ATT_PATH."/../$UrlAllegato");  // prendo il contenuto dell'allegato
			//trace("testoall $strTxt");
		  	if (!file_put_contents($newFile,"$strTxt".TEXT_NEWLINE,FILE_APPEND))                                   // aggiungo i dati nel nuovo file 
		  	{
				trace("Errore nella creazione del file unico $newFile (lettere Rotomail)",false); 
				return false;
		  	}	
		}
		if (!file_put_contents($newFile,"@E".count($ArrIdAllegati),FILE_APPEND))  // record di chiusura
	    {
		 	trace("Errore nella creazione del file unico $newFile (lettere Rotomail)",false); 
			return false;
		}	
		// Invia file all'amministratore di sistema
		$file=array();
		$file["tmp_name"] = $newFile;
		$file["name"] = $fileName;
		$file["type"] = filetype($newFile);
	  
		sendMail("cnc".$sito."@toyota-fs.com",getSysParm("LETTERE_MAIL"),"File '$nomeModello' prodotto per stampa Rotomail","Vedi allegato",$file);
  	}
 	  
	return TRUE;
  }// end try
  catch (Exception $e)
  {
	  trace("Errore nella preparazione del file di lettere: ".$e->getMessage()); 
	  return false;
  } // end catch  
}



//================================================================================
// processDelayedSend()     (by Aldo)
// Chiama le funzioni per l'invio dei messaggi differiti
//================================================================================
function processDelayedSend()
{
  //trace("\nInizio esecuzione processi differiti");
  
 elaboraLettere();        // elabora le lettere (allegato) differite e crea il file unico 
 inviaEmailDifferite();   // invia le email differite
 inviaSmsDifferiti("IdModello!=2");     // invia gli sms differiti escluso precrimine
  
  //trace("\nFine esecuzione processi differiti");
}

//================================================================================
// processPrecrimine()     
// Chiama la funzioni per l'invio dei messaggi precrimine
//================================================================================
function processPrecrimine()
{
 inviaSmsDifferiti("IdModello=2");     // invia gli sms precrimine
}


//================================================================================
// clean()     (by Aldo)
// Esegui le funzioni di pulizia del log e del trace (funzione batch da cron)
//================================================================================

function clean()
{
	cleanTrace();
	cleanOkImportedFiles();
	cleanImportLog();
	cleanLog();
	cleanLogMsgDiff();
	cleanScadenze();
}



//================================================================================
// cleanImportLog()     (by Aldo)
// Pulisce la tabella log degli import e dei messaggi. Anche le tabelle processlog
// e maildifferita
//================================================================================
function cleanImportLog()
{
	global $context;
	try
	{
		$dataScad=date("Y-m-d",mktime(0,0,0,date("m"),date("d")-getSysParm("GIORNI_CANCELLAZIONE","90"),date("Y")));
		
		$sql="delete FROM importmessage " 
			." where  IdImportLog  in" 
			." (select IdImportLog from importlog where lastupd < '".$dataScad."')";	
		if(!execute($sql))
			trace("Errore nella cancellazione dei messaggi di import: $sql",false);	

		$sql="UPDATE allegato SET IdImportLog=NULL " 
			." where  IdImportLog  in" 
			." (select IdImportLog from importlog where lastupd < '".$dataScad."')";	
		if(!execute($sql))
			trace("Errore nella cancellazione dei link a importlog: $sql",false);	
			
		$sql="delete from importlog where IdImportLog>1 AND lastupd < '".$dataScad."'";	
		if(!execute($sql))
			trace("Errore nella cancellazione del log degli import : $sql",false);		
			
		$sql="delete from processlog where lastupd < '".$dataScad."'";	
		if(!execute($sql))
			trace("Errore nella cancellazione del log dei processi : $sql",false);		

		$sql="delete from maildifferita where DataCreazione < '".$dataScad."'";	
		if(!execute($sql))
			trace("Errore nella cancellazione della tabella maildifferita : $sql",false);		
	}
	catch (Exception $e)
	{
	    trace("Errore nella cancellazione dei record della tabella importlog :  $e->getMessage()");
	    return false;
	} 
}

//================================================================================
// cleanLog()     (by Aldo)
// Pulisce la tabella log
//================================================================================
function cleanLog()
{
	global $context;
	try
	{
		$dataScad=date("Y-m-d",mktime(0,0,0,date("m"),date("d")-getSysParm("GIORNI_CANCELLAZIONE","90"),date("Y")));
		
		$sql="delete from log where lastupd <'".$dataScad."'";
			
		if(!execute($sql))
			trace("Errore nella cancellazione dei messaggi di import: $sql",false);	
        
    }
	catch (Exception $e)
	{
	    trace("Errore nella cancellazione dei record della tabella log:  $e->getMessage()");
	    return false;
	} 
}

//================================================================================
// cleanLogMsgDiff()     (by Aldo)
// Pulisce la tabella messaggidifferiti
//================================================================================
function cleanLogMsgDiff()
{
	global $context;
	try
	{
		$dataScad=date("Y-m-d",mktime(0,0,0,date("m"),date("d")-getSysParm("GIORNI_CANCELLAZIONE","90"),date("Y")));
		
		$sql="delete from messaggiodifferito where DataCreazione <'".$dataScad."'";
			
		if(!execute($sql))
			trace("Errore nella cancellazione dei messaggi di import: $sql",false);	
        
    }
	catch (Exception $e)
	{
	    trace("Errore nella cancellazione dei record della tabella messaggiodifferito:  $e->getMessage()");
	    return false;
	} 
}

//================================================================================
// cleanScadenze()     (by Aldo)
// Pulisce la tabella nota dalle scadenze 
//================================================================================
function cleanScadenze()
{
	global $context;
	try
	{
		$dataScad=date("Y-m-d",mktime(0,0,0,date("m"),date("d")-getSysParm("GIORNI_CANCELLAZIONE","90"),date("Y")));
		
		$sql="delete FROM notautente where IdNota IN (SELECT IdNota FROM nota WHERE TipoNota='S' and DataScadenza < '".$dataScad."')";
		if(execute($sql))
		{
			$sql="delete FROM nota where TipoNota='S' and DataScadenza < '".$dataScad."' and DataScadenza>'0000-00-00'";
			if(!execute($sql))
				trace("Errore nella cancellazione delle scadenze: $sql",false);	
		}
        
    }
	catch (Exception $e)
	{
	    trace("Errore nella cancellazione dei record (scadenze) nella tabella 'nota':  $e->getMessage()");
	    return false;
	} 
}
//================================================================================
// provaMail
//================================================================================
function provaMail()
{   
	if (function_exists("mail"))
    {
    	$ret = mail("giorgio.difalco@gmail.com","Prova invio mail", "body vuoto", "From: Toyota Financial Services <noreply@tfsi.it>");
    	trace("Risultato invio mail = $ret",FALSE);
    }
    else
    	trace("Funzione mail non disponibile",FALSE);
}

//================================================================================
// prova SMS
//================================================================================
function provaSMS()	
{		
	echo "Inizio prova di invio con curl verso https://secure.apisms.it/http/send_sms\n";	
	$buffer = array("authlogin" => SMS_USER,
						"authpasswd" => SMS_PWD,
						"sender" => base64_encode(SMS_SENDER),
						"body" => base64_encode("Prova invio n.1"),
						"destination" => "393483331774",
						"id_api" => SMS_API); // usare 477 per sms di ritorno//Inizializza e invia
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://secure.apisms.it/http/send_sms");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $buffer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$ret = curl_exec($ch);
	curl_close($ch);
	echo "Codice di ritorno=$ret\n";

	echo "Inizio prova di invio con funzione applicativa inviaSMS\n";	
	if (inviaSMS("3483331774","Prova invio n.2",$ErrMsg))
		echo "Codice di ritorno funzione: OK\n";
	else
		echo "Codice di ritorno funzione: KO $ErrMsg\n";
}

//================================================================================
// cleanOkImportedFiles()     (by Aldo)
// Cancella i files importati andati a buon fine (cartella okFiles) più vecchi di un mese
//================================================================================
function cleanOkImportedFiles()
{
	global $context;
	try
	{
		$dataScad=date("Ymd",mktime(0,0,0,date("n"),date("j")-getSysParm("GG_CANC_OK_IMP_FILES","90"),date("Y")));
		$dir = TMP_PATH.'/okFiles/';
		foreach (scandir($dir) as $item)
		{
		  if($item!='.' && $item!='..')
		  {
		  	if(date ("Ymd",filemtime($dir.$item))<$dataScad)
		  	{
		  		if(!unlink($dir.$item))
		  			trace("Errore durante la cancellazione file importato ".$dir.$item,false);
		  		else
		  			trace("Cancellato file importato ".$dir.$item,false);
		  	}
		  }
		}
	}
	catch (Exception $e)
	{
		trace("Errore durante la cancellazione file OK importati :  $e->getMessage()");
		return false;
	}
}
//================================================================================
// callUrl 
// Esegue con CURL la chiamata ad un URL residente nella cartella server/batch
//================================================================================
////// NOTA 13/12/2013: sembra giusti ma dà sempre FALSE. per ora non utilizzato
function callUrl($url)
{
	try {
		$url = LINK_URL."server/batch/$url"; // url completo
		$ch = curl_init();

		trace("callUrl: chiamata tramite curl della pagina $url",false);
		curl_setopt($ch, CURLOPT_URL, $url);		 // URL da chiamare
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);		 // chiede di non restituire anche gli header
		curl_setopt($ch, CURLOPT_PROXY, PROXY);
		curl_setopt($ch, CURLOPT_PROXYPORT, PROXYPORT);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
		curl_setopt($ch, CURLOPT_PROXYAUTH, PROXYAUTH);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$headers = array("Accept: text/plain, text/html");
		$headers[] = "Connection: keep-alive"; 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);				
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
		
		$ret = curl_exec($ch);
		trace("Ritorno da curl: ".($ret===false)?'false':$ret,false);
		echo "Ritorno da curl: ".($ret===false)?'false':$ret;
		curl_close($ch);
	} catch (Exception $e) {
		trace("errore callUrl $url: ".$e->getMessage(),true,true);
	}
}

/**
 * Prova experian da batch (da sostituire con la vera chiamata in job separato, presumibilmente)
 */
function provaExperian() {
	
	// Durante i test:
	error_reporting(E_ALL);
	ini_set("display_errors",1);
	
	try {
		if (creaFileExperian("CodRegolaProvvigione='25'",$error)) {
			echo "Invio OK";
		} else {
			echo "Invio fallito: $error";
		}
	} catch(Exception $e) {
		echo $e->getMessage();
	}
}
?>