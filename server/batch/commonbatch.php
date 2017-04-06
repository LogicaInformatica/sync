<?php 
$_SESSION['userContext'] = array(); 
if (!function_exists("inviaSMS")) {
	require_once("../workflowFunc.php");
} else {
	require_once("../common.php");
}
error_reporting(E_ERROR | E_PARSE);
ini_set("display_errors",1);

//================================================================
// 	F U N Z I O N I  C O M U N I  A I   S E R V I Z I   B A T C H
//================================================================

//---------------------------------------------------------------------------------------------------------------------
// writeImportLog
// Scrive una nuova riga nell'import log (nuovo file in ingresso)
// Argomenti:
//		$from		sigla del sistema chiamante (ad es. tfsi)
//      $filetype   tipo di file (ad es. clienti)
//      $fileid     id numerico univoco del file per il sistema chiamante
//      $status		stato della riga (N=nuova,R=in corso di elaborazione,P=finito,in attesa di conferma esito
//                                    C=esito inviato e confermato)
//---------------------------------------------------------------------------------------------------------------------
function writeImportLog($from,$filetype,$fileid,$status)
{
	try
	{
		$from = strtoupper($from);
		// Decodifica il sistema mittente come sigla di committente
		$comp = getScalar("SELECT IdCompagnia FROM compagnia WHERE CodCompagnia='$from' AND IdTipoCompagnia=1 AND CURDATE() BETWEEN DataIni AND DataFin");
		if ($comp==NULL)
		{
			trace("Impossibile registrare sulla tabella ImportLog lo stato del batch: $from $filetype $fileid: '$from' non riconosiuto");
			return FALSE;
		}
		else
		{
			$filetype  = quote_smart($filetype);
			$idImportLog = getScalar("SELECT IdImportLog FROM importlog WHERE IdCompagnia=$comp AND FileType=$filetype AND FileId=$fileid");
			if ($idImportLog>0)
			{
				beginTrans();
				execute("DELETE FROM importmessage WHERE IdImportLog=$idImportLog");
				if (execute("UPDATE importlog SET ImportTime=NOW(),Status='$status',ImportResult=NULL,Message=NULL,LastUser='import' WHERE IdImportLog=$idImportLog"))
				{
					commit();
					return TRUE;
				}
				else
				{
					rollback();
					return FALSE;
				}
			}
			else
				return execute("INSERT INTO importlog (IdCompagnia,ImportTime,FileType,FileId,Status,LastUpd,LastUser)"
		                  ." VALUES($comp,NOW(),$filetype,$fileid,'$status',NOW(),'import')");
		}
	}
	catch (Exception $e) 
	{
		trace("Impossibile registrare sulla tabella ImportLog lo stato del batch: $from $filetype $fileid: ".$e->getMessage());
		return FALSE;
	}
}	  

//--------------------------------------------------------------------------------------------
// getCompanyId
// Determina l'ID della compagnia committente corrispondente alla sigla data
//--------------------------------------------------------------------------------------------
function getCompanyId($from)
{
	try
	{
		return (int)getScalar("SELECT IdCompagnia FROM compagnia WHERE UPPER(CodCompagnia)='".strtoupper($from)."'"
			." AND NOW() BETWEEN DataIni AND DataFin");
	}
	catch (Exception $e)
	{
		trace("getCompanyId: ".$e);
		return 0;
	}
}

//--------------------------------------------------------------------------------------------
// getImportLogId
// Ottiene l'ID della riga di importLog corrispondente al file in corso di elaborazione
// Argomenti:
//		$from		sigla del sistema chiamante (ad es. tfsi)
//      $filetype   tipo di file (ad es. clienti)
//      $fileid     id numerico univoco del file per il sistema chiamante
//      $dataFile   (by ref) data/ora di import del file
//--------------------------------------------------------------------------------------------
function getImportLogId($from,$filetype,$fileid,&$dataFile)
{
	try
	{
		$from = strtoupper($from);
		$row  = getRow("SELECT IdImportLog,ImportTime FROM importlog I,compagnia C WHERE I.IdCompagnia=C.IdCompagnia AND CodCompagnia='$from'"
		." AND NOW() BETWEEN C.DataIni AND C.DataFin AND FileType='$filetype' AND FileId=$fileid");
		$dataFile = $row["ImportTime"];
		if (!$dataFile)
			$dataFile = time();
		trace("getImportLogId: dataFile=".ISODate($dataFile,true),FALSE);
		return $row["IdImportLog"];
	}
	catch (Exception $e)
	{
		trace("getImportLogId: ".$e);
		return 0;
	}
}

//--------------------------------------------------------------------------------------------
// writeResult
// Aggiorna con l'esito complessivo di un job la riga di ImportLog con chiave data
// Argomenti:
//		$id		chiave della riga
//      $esito  U (file processato con successo) K (file rigettato)
//      $msg   	messaggio
//--------------------------------------------------------------------------------------------
function writeResult($id,$esito,$msg)
{
	global $sito;
	try
	{
		if ($id>0)
		{
			if ($esito=="K") // import fallito
				writeProcessLog(PROCESS_NAME,$msg,2);
				//sendMail("cnc".$sito."@toyota-fs.com",getSysParm("ADMIN_MAIL"),"Import fallito in Connecticut, sito=$sito",$msg);
			
				 
			$msg1 = quote_smart($msg);
			$sql = "UPDATE importlog SET ImportResult='$esito',Message=$msg1,LastUser='import',LastUpd=NOW()"
						." WHERE IdImportLog=$id";
			execute($sql);
			if (getAffectedRows()==0)
				trace("UPDATE Importlog non effettuato; Id=$id; Message=$msg");
			else if ($esito!='K') // se K, già tracciato dalla writeProcessLog
				trace($msg,FALSE);
		}
	}
	catch (Exception $e) 
	{
		trace("Impossibile registrare sulla tabella ImportLog lo stato del batch: $from $filename $fileid: $e");
	}
}

//--------------------------------------------------------------------------------------------
// writeRecordError
// Aggiorna con l'esito complessivo di un job la riga di ImportLog con chiave data
// Argomenti:
//		$id		chiave della riga di ImportLog
//      $esito  R(riga da reinviare al prossimo invio) E(riga errata)
//      $msg   	messaggio
//      $key    valore della chiave univoca del record (che serve al servizio Windows per
//              riconoscere il record errato)
//--------------------------------------------------------------------------------------------
function writeRecordError($id,$esito,$msg,$key)
{
	try
	{
		writeProcessLog(PROCESS_NAME,$msg,2);
 		
 		$msg = quote_smart($msg);
		$key = quote_smart($key);
		$sql = "INSERT INTO importmessage (IdImportLog,ErrorType,Message,RecordKey,LastUpd)"
			  ." VALUES ($id,'$esito',$msg,$key,NOW())";
		execute($sql);
	}
	catch (Exception $e) 
	{
		trace("Impossibile registrare sulla tabella ImportLog lo stato del batch: $from $filename $fileid: $e");
	}
}

//----------------------------------------------------------------
// changeImportStatus
// Modifica lo stato di una riga nell' ImportLog
//----------------------------------------------------------------
function changeImportStatus($idImportLog,$status)
{
	try
	{
		if ($status=="R")
		{
			execute("DELETE FROM importmessage WHERE IdImportLog=$idImportLog");
			execute("UPDATE importlog SET Status='$status',Message=NULL,LastUpd=NOW(),LastUser='import' WHERE IdImportLog=$idImportLog");
		}
		else
			execute("UPDATE importlog SET Status='$status',LastUpd=NOW(),LastUser='import' WHERE IdImportLog=$idImportLog");
	}
	catch (Exception $e) 
	{
		trace("Impossibile aggiornare sulla tabella ImportLog lo stato della riga $idImportLog: $e");
	}
}

//----------------------------------------------------------------
// checkSemaphore
// Controlla che il batch non sia già in esecuzione
// Torna FALSE se il job è già in esecuzione (cioè se il record di
// chiave 1 ha Status=R)
//----------------------------------------------------------------
function checkSemaphore()
{
	try
	{
		$status = getScalar("SELECT Status FROM importlog WHERE IdImportLog=1");
		return ($status!="R");
	}
	catch (Exception $e) 
	{
		trace($e->getMessage());
      	return FALSE;
	}
}

//--------------------------------------------------------------------
// checkAndSetSemaphore
// Controlla che il batch non sia già in esecuzione e mette il
// flag per tali controllo
// Torna FALSE se il job è già in esecuzione (cioè se il record di
// chiave 1 ha Status=R oppure se ImportResult non è U)
// (Se ImportResult è NULL significa che è ancora la vecchia gestione
// e quindi la condizione non si applica)
//--------------------------------------------------------------------
function checkAndSetSemaphore()
{
	try
	{
		if (rowExistsInTable("importlog","IdImportLog=1 AND (Status='R' OR ImportResult!='U')"))
			return FALSE; // già in esecuzione oppure invio files non completo
				
		if (!execute("UPDATE importlog SET Status='R' WHERE IdImportLog=1"))
           	return FALSE;
        else
        	return TRUE;
	}
	catch (Exception $e) 
	{
		trace($e->getMessage());
      	return FALSE;
	}
}

//----------------------------------------------------------------
// resetSemaphore
// Spegne il flag che segnala job batch in esecuzione
//----------------------------------------------------------------
function resetSemaphore()
{
	try
	{
		// Annulla lo Status, per consentire la partenza del job successivo, il giorno dopo
		// Mette P nell'importResult se questo è non nullo, il che indica che la nuova gestione con fineInvio.php è in funzione
		// altrimenti lascia NULL in tale campo (altrimenti alla ripartenza il batch successivo si blocca)
		execute("UPDATE importlog SET Status=NULL,ImportResult=IF(ImportResult IS NULL,NULL,'P'),Message='Elaborazione files terminata' WHERE IdImportLog=1");
	}
	catch (Exception $e) 
	{
		trace($e->getMessage());
      	return FALSE;
	}
}

//---------------------------------------------------------------------------------------------------------------------
// writeProcessLog
// Scrive una nuova riga nella tabella ProcessLog
// Argomenti:
//		$process	nome convenzionale del processo (ad es. "OCS_IMPORT")
//      $text       testo del messaggio da registrare
//      $level      (opzionale, default=0)
//                  0 - informativo, no action
//                  1 - errore, no action
//                  2 - errore con mail (accumulata nel messaggio finale)
//                  3 - errore con mail ed eventuale SMS
//                  4 - info con mail ed SMS
//---------------------------------------------------------------------------------------------------------------------
/*if (!function_exists('writeProcessLog')) {
	function writeProcessLog($process,$text,$level=0)
	{
		trace($text,false,$level>1); // invia mail per livello di log 2, 3 e 4 
		
		if ($level>=3) { // richiesto invio SMS
			$smsDest = getSysParm("ADMIN_SMS");
			if ($smsDest>'') {
				inviaSMS($smsDest,$text,$errmsg);
			}
		}
		
		$sql = "INSERT INTO processlog (ProcessName,LogMessage,LogLevel) VALUES('$process',".quote_smart($text).",$level)";
		return execute($sql,false); // evita traccia perché se MySql è KO , va in ricorsione
	}
}*/