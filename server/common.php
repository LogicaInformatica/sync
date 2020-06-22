<?php 
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0"); // HTTP/1.1
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Pragma: public');

error_reporting(E_ERROR | E_PARSE);	// METTERE a 0 in produzione
ini_set("display_errors",1);
//error_reporting(0);
	
setlocale(LC_ALL, 'en_US'); // per evitare che metta virgole decimali facebdo operazioni su numeri

require_once("constant.php");
require_once("phpqrcode/qrlib.php"); //2020-05-08 Carica il plugin per generare qrcode 

if (!isset($_SESSION))
{
	session_cache_limiter('private');
	session_cache_expire(0);	
	session_start();
}

if (isset($_SESSION['userContext']))
	$context = $_SESSION['userContext'];

//==============================================================
// 				F U N Z I O N I    C O M U N I
//--------------------------------------------------------------
// 			PARTE PRIMA - FUNZIONI SUL DATABASE
//==============================================================
$connection = FALSE;
$inTransaction = FALSE;
$lastError = "";

//-------------------------------------------------------------- 
// openDb
// Connessione al DB (non persistente)
// NB: per disconnettere fare semplicemente mysqli_close(), che 
//     pu� anche essere omesso perch� a fine script ci pensa 
//     il sistema. Notare anche che la connect automaticamente
//     riusa l'eventuale connessione gi� disponibile.
//-------------------------------------------------------------- 
function openDb() {	// alias di getDbConnection
	return getDbConnection();
}

function getDbConnection()
{
	global $connection,$sito;
	if (!$connection) {
		$connection = @mysqli_connect(MYSQL_SERVER,MYSQL_USER, MYSQL_PASS, MYSQL_SCHEMA, MYSQL_PORT);
		if (!$connection) {
			trace("(Sito $sito) connessione fallita al server ".MYSQL_SERVER." porta ".MYSQL_PORT." user/pwd ".MYSQL_USER."/".MYSQL_PASS." schema ". MYSQL_SCHEMA." err: ".mysqli_connect_error(),TRUE,FALSE);
			Throw new Exception("Connessione al database non riuscita: ".mysqli_connect_error());				
		}
		execute("set @@lc_time_names='it_IT'");
		execute("SET collation_connection = 'utf8_general_ci'"); // Necessario perch� le view sono create da MySql WB in questa collation
	}
	return $connection;
}

function closeDb() {
	closeDbConnection();
}

function closeDbConnection()
{
	global $connection;
	if ($connection) {
		@mysqli_close($connection);
		$connection = FALSE;
	}
}

/**
 * enableForeignKeys Abilita o disabilita i controllid i foreign keys
 * @param {Boolean} $enable 1 per abilitare, 0 per disabilitare
 */
function enableForeignKeys($enable) {
	return execute("SET FOREIGN_KEY_CHECKS=".($enable?1:0),false);
}


//----------------------------------------------------------------------------------------------
// getLastError
// Restituisce l'ultimo msg di errore mySql
//----------------------------------------------------------------------------------------------
function getLastError()
{
	global $connection,$lastError;
	if (!$connection) 
		return "";
	else 
	{
		$last = mysqli_error($connection);
		if ($last>"") // c'� stato effettivamente un errore MySql
			$lastError = $last;
		return $lastError; 
	}
}

//----------------------------------------------------------------------------------------------
// setLastError
// Imposta un messaggio nella variabile lastError
//----------------------------------------------------------------------------------------------
function setLastError($msg)
{
	global $connection,$lastError;
	$lastError = $msg; 
}

//----------------------------------------------------------------------------------------------
// getAffectedRows
// Restituisce il numero di righe modificate dall'ultimo comando mySql
//----------------------------------------------------------------------------------------------
function getAffectedRows()
{
	global $connection;
	if (!$connection) 
		return -1;
	else
		return mysqli_affected_rows($connection);
}

//----------------------------------------------------------------------------------------------
// getInsertId
// Restituisce l'ultima chiave creata da INSERT
//----------------------------------------------------------------------------------------------
function getInsertId()
{
	global $connection;
	if (!$connection) 
		return -1;
	else
		return mysqli_insert_id($connection);
}

//-----------------------------------------------------------------------
// unquote_smart
// Esegue lo stripslashes solo se magic_quotes_gpc=on
//-----------------------------------------------------------------------
function unquote_smart($value) {
	if (get_magic_quotes_gpc()) {
		 return stripslashes($value);
	} else {
		return $value;
	}
}

//-----------------------------------------------------------------------
// quote_smart
// Quote variable to make safe
//-----------------------------------------------------------------------
function quote_smart($value) {
	if ($conn = getDbConnection()) { 		
		// Stripslashes
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
	
		$value = "'" . mysqli_real_escape_string($conn, $value) . "'";
	}
	return $value;
}

//-----------------------------------------------------------------------
// quote_smart_deep
// Recursive array-capable version of quote_smart
//-----------------------------------------------------------------------
function quote_smart_deep($value) { 
	$value = is_array($value) ? array_map('quote_smart_deep',$value) : quote_smart($value);
	return $value;
}

//-----------------------------------------------------------------------
// startTiming
// Salva il time() per la misurazione del tempo di esecuzione
//-----------------------------------------------------------------------
function startTiming()
{
	global $startTime;
	if (SQLTIMING)
		$startTime = microtime(true);
}
//-----------------------------------------------------------------------
// stopTiming
// Misurazione del tempo di esecuzione di un comando SQL
//-----------------------------------------------------------------------
function stopTiming($text)
{
	global $startTime;
	if (SQLTIMING)
	{
		trace("Timing: ".(microtime(true)-$startTime)." sql: $text",FALSE);
	}
}

//-----------------------------------------------------------------------
// getScalar
// Legge il valore di una singola colonna (torna NULL in caso di EOF)
//-----------------------------------------------------------------------
function getScalar($sql,$notrace=FALSE)
{
	$item = NULL;
	startTiming();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			$item = mysqli_fetch_row($res); 
			if ($item)	// se non ci sono righe � NULL
				$item = $item[0];		
			mysqli_free_result($res);
		} else if (!($notrace===true))
		{
			trace("Errore nella query  $sql: ".getLastError(),TRUE,TRUE);
		}
	}
	stopTiming($sql);
	return $item;
}
//-----------------------------------------------------------------------
// getRow
// Esegue una query e ritorna un array con le colonne della
// prima (e probabilmente unica) riga letta, di default, di tipo associativo
//-----------------------------------------------------------------------
function getRow($sql, $type=MYSQLI_ASSOC)
{
	$array = NULL;
	startTiming();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) 
		{
			if ($rec = mysqli_fetch_array($res, $type))
				$array = $rec;
			mysqli_free_result($res);
		} 
		else 
		{
			trace("Errore nella query  $sql: ".getLastError(),TRUE,TRUE);
		}
	}

	stopTiming($sql);
	return $array;
}

/**
 * getRows alias di getFetchArray
 */
function getRows($sql, $type=MYSQLI_ASSOC, &$error='') {
	return getFetchArray($sql, $type=MYSQLI_ASSOC, $error);
}

//-----------------------------------------------------------------------
// getFetchArray
// Esegue una query e ritorna un array con le righe estratte. Ogni riga � un array, di
// default, di tipo associativo
//-----------------------------------------------------------------------
function getFetchArray($sql, $type=MYSQLI_ASSOC, &$error='')
{
	startTiming();
	$arrayItem = array();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			while($rec = mysqli_fetch_array($res, $type)){
				$arrayItem[] = $rec;
			}
			mysqli_free_result($res);
			$error = "";
		} else {
			$error = getLastError();
			trace("Errore nella query  $sql: ".getLastError(),TRUE,TRUE);
		}
	}

	stopTiming($sql);
	return $arrayItem;
}
//-----------------------------------------------------------------------
// getFetchKeyValue
// Esegue una query e ritorna un array con key e value, scelti  
// con il secondo e terzo argomento 
//-----------------------------------------------------------------------
function getFetchKeyValue($sql, $key, $value, $type=MYSQLI_ASSOC)
{
	startTiming();
	$arrayItem = array();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			while($rec = mysqli_fetch_array($res, $type)){
				$arrayItem[$rec[$key]] = $rec[$value];
			}
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".getLastError(),TRUE,TRUE);
		}
	}

	stopTiming($sql);
	return $arrayItem;
}

/**
 * getColumn: alias di fetchValuesArray 
 */
function getColumn($sql) {
	return fetchValuesArray($sql);
}

//-----------------------------------------------------------------------
// fetchValuesArray
// Ritorna un array con il primo elemento di ogni record
// estratto con la query
//-----------------------------------------------------------------------
function fetchValuesArray($sql)
{
	startTiming();
	$array = array();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			while($rec = mysqli_fetch_array($res, MYSQLI_NUM)){
				$array[] = $rec[0];
			}
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".getLastError(),TRUE,TRUE);
		}
	}

	stopTiming($sql);
	return $array;
}

//----------------------------------------------------------------------------------------------
// rowExistsInTable
// Controlla se esiste almeno una riga che soddisfa una data condizione in una tabella data
//----------------------------------------------------------------------------------------------
function rowExistsInTable($table,$sqlWhere)
{
	startTiming();
	$result = FALSE;
	if ($conn = getDbConnection()) 
	{ 
		// 6/6/11: corretto messaggio di errore
		$sql = "SELECT 'x' FROM $table WHERE $sqlWhere LIMIT 1"	;	
		if ($res = mysqli_query($conn,$sql )) 
		{
			$result = (mysqli_num_rows($res)==1);
			mysqli_free_result($res);
		} else {
			trace("Errore nella query $sql: ".getLastError(),TRUE,TRUE);
		}
	}
	stopTiming($sql);
	return $result;
}

//----------------------------------------------------------------------------------------------
// execute
// Esegue un comando e gestisce il codice di errore
//----------------------------------------------------------------------------------------------
function execute($sql,$traceIfError=true)
{
	startTiming();
	if ($conn = getDbConnection()) { 		
		$result = mysqli_query($conn, $sql);
		if (!$result && $traceIfError) {
			$error = getLastError();
			// Intercetta gli errori di integrita' referenziale e li espone all'utente in un modo migliore
			if (preg_match('/foreign key constraint fails \(.+\.(.+),/',$error,$arr)) {
				$tab = $arr[1];
				setLastError("Operazione non consentita perche' esistono dati collegati (nella tabella $tab) a quello che si tenta di cancellare o modificare");
			}
			trace("$error (sql: $sql)",TRUE,TRUE);
		}
	}
	stopTiming($sql);
	return $result;
}

//----------------------------------------------------------------------------------------------
// beginTrans
// Inizia una transazione
//----------------------------------------------------------------------------------------------
function beginTrans()
{
	global $inTransaction;
	if ($inTransaction)
		return;
	if ($conn = getDbConnection()) 
	{ 		
		if (!mysqli_autocommit($conn, FALSE))
			trace("Errore nella beginTrans: ".getLastError());
		else
			$inTransaction = TRUE;
	}
	else
		trace("Errore nella connessione al DB: ".getLastError(),TRUE,TRUE);	
}

//----------------------------------------------------------------------------------------------
// commit
// Termina una transazione con commit
//----------------------------------------------------------------------------------------------
function commit()
{
	global $inTransaction;
	if (!$inTransaction)
		return;
	if ($conn = getDbConnection()) 
	{ 		
		if (!mysqli_commit($conn))
			trace("Errore nella commit: ".getLastError(),TRUE,TRUE);
		else
		{
			$inTransaction = FALSE;
			mysqli_autocommit($conn, TRUE);
		}
	}
	else
		trace("Errore nella connessione al DB: ".getLastError());	
}

//----------------------------------------------------------------------------------------------
// rollback
// Termina una transazione con rollback (conservando l'ultimo messaggio di errore)
//----------------------------------------------------------------------------------------------
function rollback()
{
	global $inTransaction;
	if (!$inTransaction)
		return;
	if ($conn = getDbConnection()) 
	{ 	
		setLastError(getLastError()); // conserva eventuale ultimo msg di errore	
		if (!mysqli_rollback($conn))
			trace("Errore nella rollback: ".getLastError(),TRUE,TRUE);
		else
		{
			$inTransaction = FALSE;
			mysqli_autocommit($conn, TRUE);
		}
	}
	else
		trace("Errore nella connessione al DB: ".getLastError(),TRUE,TRUE);	
}

//----------------------------------------------------------------------------------------------
// addSetClause
// Aggiunge un elemento ad una clausola SET data
// Argomenti:
//	1) $setClause (byref) clausola da modificare appendendo il nuovo termine
//  2) $fieldName		  nome del campo
//  3) $fieldValue		  espressione da assegnare al campo
//  4) $fieldtype		  N=numerico, S=stringa, D=Data, G=generico (espressione gi� completa)
//----------------------------------------------------------------------------------------------
function  addSetClause(&$setClause,$fieldName,$fieldValue,$fieldType)
{
	if ($setClause=="")
		$setClause = "SET $fieldName=";
	else
		$setClause .= ",$fieldName=";
	
	if ($fieldValue===NULL || $fieldValue==="")
		$setClause .= "NULL";
	else
	{
		switch ($fieldType)
		{
			case "N": // numerico
				$setClause .= str_replace(',','.', $fieldValue); // per sicurezza
				break;
			case "I": // importo formattazione italiana
				$setClause .= importo($fieldValue);
				break;
			case "G": // generico
				$setClause .= $fieldValue;
				break;
			case "S": // stringa
				$setClause .= quote_smart($fieldValue);
				break;
			case "SD": // data locale come string
			case "D": // data
				$setClause .= "'".ISODate($fieldValue)."'";
				break;
			case "X": // timestamp
				$setClause .= "'".strftime("%Y-%m-%d %H:%M:%S",$fieldValue)."'";
				break;
		}
	}
}

//----------------------------------------------------------------------------------------------
// addInsClause
// Aggiunge un elemento ad una lista colonne per INSERT e alla corrispondente lista valori
// Argomenti:
//	1) $colList (byref)   lista nomi colonne da modificare appendendo il nuovo termine
//	2) $valList (byref)   lista valori colonne da modificare appendendo il nuovo termine
//  3) $fieldName		  nome del campo
//  4) $fieldValue		  espressione da assegnare al campo
//  5) $fieldtype		  N=numerico, S=stringa, D=Data, G=generico (espressione gi� completa)
//----------------------------------------------------------------------------------------------
function addInsClause(&$colList,&$valList,$fieldName,$fieldValue,$fieldType)
{
	if ($colList=="")
		$colList = "$fieldName";
	else
	{
		$colList .= ",$fieldName";
		$valList .= ", ";
	}
	
	if ($fieldValue===NULL || $fieldValue==="")
		$valList .= "NULL";
	else
	{
		switch ($fieldType)
		{
			case "N": // numerico
				$valList .= str_replace(',','.', $fieldValue); // per sicurezza
				break;
			case "I": // importo formattazione italiana
				$valList .= importo($fieldValue);
				break;
			case "G": // generico
				$valList .= $fieldValue;
				break;
			case "S": // stringa
				$valList .= quote_smart($fieldValue);
				break;
			case "SD": // data locale come string
			case "D": // data
				$valList .= "'".ISODate($fieldValue)."'";
				break;
			case "X": // timestamp
				$valList .= "'".strftime("%Y-%m-%d %H:%M:%S",$fieldValue)."'";
				break;
		}
	}
}

/**
 * importo
 * Trasforma in standard un numero con virgola e punto separatore delle migliaia
 */
function importo($fieldValue) {
	if (preg_match('/\.[0-9]+,/',$fieldValue)) { // all'italiana con separatore di migliaia
	return str_replace(',','.', str_replace('.','', $fieldValue));
	} elseif (preg_match('/,[0-9]+\./',$fieldValue)) { // all'inglese con separatore di migliaia
		return str_replace(',','', $fieldValue);
	} elseif (substr_count($fieldValue,",")>1) { // solo separatori migliaia inglesi 
		return str_replace(',','', $fieldValue);
	} elseif (substr_count($fieldValue,".")>1) { // solo separatori migliaia italiani 
		return str_replace('.','', $fieldValue);
	} elseif (preg_match('/\,/',$fieldValue)) { // probabilmente una virgola all'italiana
		return str_replace(',','.', $fieldValue);
	} else {
		return $fieldValue;
	}
}

//==============================================================
// 			PARTE SECONDA - FUNZIONI DI TRACCIA
//==============================================================
//--------------------------------------------------------------
// trace
// Registra un messaggio, con data/ora, su file
//--------------------------------------------------------------
function trace($msg,$backTrace=TRUE,$sendMail=FALSE)
{
	global $sito,$context;
	try
	{
		if (TRACE)
		{
/*SCA*/
//			$trace_file = LOG_PATH."/trace_".date('Ymd').".txt";
			$trace_file = LOG_PATH."/trace.txt";
			if (!file_exists($trace_file)) { 
  				$handle = fopen($trace_file,'a');
 				fclose($handle);
				@chmod($trace_file,0777);
			} 
/*SCA*/
			$backt = "";
			if ($backTrace)
			{
				$backtrace = debug_backtrace();
				$nb = count($backtrace);
				$msgDone = false;
				$nrighe = 0;
				for ($i=0; $i<$nb; $i++) {
	//				if (basename($backtrace[$i]['file'])!="common.php" && $backtrace[$i]['function']!="trace") {
						if (!$msgDone) {
							$chiamata = basename($backtrace[$i]['file']).':'.$backtrace[$i]['line'].' ('.$backtrace[$i]['function'].')';
//SCA							file_put_contents(LOG_PATH."/trace.txt",date("Y-M-d H.i.s")." ".$chiamata." --- $msg\n",FILE_APPEND);
							error_log(date("Y-M-d H.i.s")." ".$context["Userid"]." ".$chiamata." --- $msg\n", 3, $trace_file);
							$nrighe++;
							if (BACKTRACE)
								$msgDone = true;
							else
								break;
						} else {
							$chiamata = "Modulo: ".basename($backtrace[$i]['file']).' linea: '.$backtrace[$i]['line'].' funzione: '.$backtrace[$i]['function'];
//SCA							file_put_contents(LOG_PATH."/trace.txt","$chiamata\n",FILE_APPEND);
							$backt .= "$chiamata\n"; // salva per la mail
							error_log("$chiamata\n", 3, $trace_file);
							$nrighe++;
						}
					}
//				}
			}
			else // senza backtrace (messaggi di traccia per cui si vuole solo la riga del messaggio)
			{
//SCA					file_put_contents(LOG_PATH."/trace.txt",date("Y-M-d H.i.s")." $msg\n",FILE_APPEND);
					error_log(date("Y-M-d H.i.s").' [IP '.$_SERVER['REMOTE_ADDR']."] $msg\n", 3, $trace_file);
			}
			if ($sendMail) // inviare una mail all'amministratore
			{
				 sendMail(MAIL_SENDER,getSysParm("ADMIN_MAIL"),"Messaggio da Connecticut sito=$sito",$msg);
			}
//SCA			@chmod(LOG_PATH."/trace.txt",0777); // caso mai l'avesse creato adesso
			return $trace_file;
		}
		else
			return FALSE;
	}
	catch (Exception $e)
	{
		echo "Errore nella scrittura di traccia: ".$e;
		return FALSE;
	}
}

//--------------------------------------------------------------
// writeLog
// Registra un messaggio sulla tabella Log del DB
// tipo: 	codice usato per eventuali query su DB
// source:  tipo dell'evento mostrato nel Giornale di bordo 
// msg:     testo descrittivo
// CodEvento: sub-codice usato per eventuali query su DB
//--------------------------------------------------------------
function writeLog($tipo,$source,$msg,$CodEvento)
{
	global $context;
	$nomeUtente = getUserName($IdUtente);
	if (!$IdUtente) { // non registra se IdUtente � null (accade alla cancellazione totale di utenti, credo se
			// l'utente � ancora loggato
		trace('WriteLog chiamata con messaggio '.quote_smart($msg).' e IdUtente NULL',false);
		return;
	}
	if (!execute("INSERT INTO log (TipoLog,Sorgente,DescrEvento,IdUtente,CodEvento)"
	            ." VALUES('$tipo',".quote_smart($source).",".quote_smart($msg).",$IdUtente,".quote_smart($CodEvento).")"))
		trace("Impossibile registrare sulla tabella Log il messaggio seguente: $msg");
}	        
//--------------------------------------------------------------
// returnError
// Termina con un messaggio su trace e file ed esegue un 
// die() con il prefisso K\t che indica al chiamante un errore
//--------------------------------------------------------------
function returnError($msg,$source,$logSuDb)
{
	trace("[".$source."] ".$msg,FALSE);
	//if ($logSuDb)
		//writeLog("ERROR",$source,$msg);	
	die("K\t$msg");
}

//==============================================================
// 			PARTE TERZA - FUNZIONI DI E-MAIL
//==============================================================
/**
*	Invia e-mail (versione originale CNC)
*
*   NB: il parametro $attachment � l'array di propriet� dato da $_FILES per un file uploaded
*/
function sendMail($mittente,$destinatario,$subject,$message,$attachment="",$ccDest="")
{
	global $context;
	
	if($mittente=='')
		$mittente = getSysParm("MAIL_SENDER","noreply@dcsys.it");
	
	// 27/6/2014: se la variabile di contesto notify_mode indica "deferred", significa che il messaggio deve essere
	//            accumulato per essere inviato solo al termine del processo (di acquisizione batch) in un unico
	//            messaggio cumulativo: in questo caso gli attachments vengono ignorati (non ci devono essere)
	if ($context["notify_mode"]=="deferred") {
		return deferMail($mittente,$destinatario,$subject,$message,$ccDest);
	}
	
	$ccVar="";
	if (MAIL_TEST>"")
	{
		if (MAIL_TEST=="dummy")
		{
			trace("Posta non inviata (dummy): from=$mittente,to=$destinatario,cc=$ccDest,subj=$subject",FALSE);
			return TRUE;
		}
		else // destinatario di test
		{
			trace("Posta deviata a ".MAIL_TEST.": from=$mittente,to=$destinatario,cc=$ccDest,subj=$subject",FALSE);
			$destinatario = MAIL_TEST;
		}
    }
    else
		trace("Posta da inviare: from=$mittente,to=$destinatario,cc=$ccDest,subj=$subject",FALSE);

	/* caso senza attachment */
    if ($attachment=="")
    {
    	if($ccDest!="" && MAIL_TEST=="")
    		$ccVar=MAIL_NEWLINE."Cc: $ccDest";
		$ret = mail($destinatario,$subject,$message,"From: $mittente".$ccVar
	    .MAIL_NEWLINE."MIME-Version: 1.0".MAIL_NEWLINE."Content-type: text/html; charset=UTF-8".MAIL_NEWLINE);
	
   		trace("Risultato invio mail a $destinatario - ret = $ret",FALSE);
   	return $ret;
    }
    /* con un attachment */
    $fileatt_type = $attachment["type"];    // mime Type
    $filename = $attachment["name"];        // nome del file originario
    $physfile = $attachment["tmp_name"];    // file fisico nome completo
    if (is_readable($physfile))
    {
        if(filesize($physfile)<=0)
        {
  			setLastError("Non � possibile allegare file di lunghezza zero (".$filename.")");
  			return false;
        }
        $file = fopen($physfile,'rb');
  		if ($file==false)
  		{
  			setLastError("Impossibile leggere il file $physfile");
  			return false;
  		}
  		else
  		{
  			$data = fread($file,filesize($physfile)); // legge il file
  			fclose($file);
  		}
	}
	else
	{
  		setLastError("Impossibile leggere il file $physfile");
		return false;
	}
    // crea la stringa di delimitazione multipart
    $mime_boundary = md5(time());

// NOTA: nonostante il protocollo SMTP preveda il doppio carattere \r\n per l'invio, se si usa questo
// come new line sotto linux, l'effetto � quello di vederlo ovunque raddoppiato (evidentemente lo fa la
// funzione mail chiamata al termine). Perci� i salti riga sono messi con la costante MAIL_NEWLINE
// (da constant.inc) che in Windows vale \r\n e in Linux solo \n.

        //&nbsp;crea header SMTP e include il testo del messaggio
    	$headers = "From: $mittente";
    if($ccDest!="" && MAIL_TEST=="")
    	$ccVar=MAIL_NEWLINE."Cc: $ccDest";
    //trace("head $headers");
    $headers .= $ccVar.MAIL_NEWLINE."MIME-Version: 1.0".MAIL_NEWLINE."Content-Type: multipart/mixed; boundary=\"{$mime_boundary}\"";
    $email_message = MAIL_NEWLINE."--{$mime_boundary}".MAIL_NEWLINE;
    $email_message .= "Content-Type:text/html; charset=\"iso-8859-1\"".MAIL_NEWLINE."Content-Transfer-Encoding: 8bit".MAIL_NEWLINE.MAIL_NEWLINE;
    $email_message .= $message.MAIL_NEWLINE.MAIL_NEWLINE;

        //&nbsp;converte il file in base64
    $data = chunk_split(base64_encode($data),76,MAIL_NEWLINE); // NB: divide anche in righe che terminano con \r\n

    // separatore e specifiche per la parte che contiene il file
    $email_message .= "--{$mime_boundary}".MAIL_NEWLINE."Content-Type: {$fileatt_type};".MAIL_NEWLINE." name=\"{$filename}\"";
	$email_message .= MAIL_NEWLINE."Content-Transfer-Encoding: base64";
    $email_message .= MAIL_NEWLINE."Content-Disposition: inline;".MAIL_NEWLINE." filename=\"{$filename}\"".MAIL_NEWLINE.MAIL_NEWLINE;
    $email_message .= $data.MAIL_NEWLINE."--{$mime_boundary}--".MAIL_NEWLINE;
        //echo "<b>headers</b>: ".str_replace(MAIL_NEWLINE,"<br>",$headers)."<br>";
        //echo "<b>message</b>: ".str_replace(MAIL_NEWLINE,"<br>",$email_message)."<br>";

	//tr	"dest=$destinatario headers=$headers",FALSE);
    $ret = mail($destinatario,$subject, $email_message, $headers);
    trace("Risultato invio mail a $destinatario - ret = $ret",FALSE);
    return $ret;
}


//==============================================================
// 			FUNZIONI DI E-MAIL
//==============================================================
/**
 * sendSimpleMail invio per emil di un testo semplice (senza allegati)
 * @param {String} $from mittente
 * @param {String} $to destinatari
 * @param {String} $subject oggetto
 * @param {String} $body corpo della mail (testo semplice)
 * @return {Boolean} 1 se invio avvenuto con successo
 */
function sendSimpleMail($from,$to,$subject,$body) {
	if (MAIL_TEST>"") { // destinatario di test
		if (MAIL_TEST=="dummy") { // NON INVIARE NULLA
			trace("Posta non inviata (dummy): from=$from,to=$to,subj=$subject");
			return TRUE;
		} else { // DEVIARE TUTTO VERSO EMAIL DI TEST
			trace("Posta deviata a ".MAIL_TEST.": from=$from,to=$to,subj=$subject");
			$to = MAIL_TEST;
		}
	}

	$headers = "From: $from".MAIL_NEWLINE;
	$headers .= "X-Sender: $from".MAIL_NEWLINE;
	$headers .= "X-mailer: php".MAIL_NEWLINE;
	$headers .= "X-Priority: 3".MAIL_NEWLINE;
	$headers .= "Return-Path: $from".MAIL_NEWLINE;

	//echo "Sending mail from $from to $to with subject: $subject";
	return mail($to,$subject,$body,$headers);
}

/**
 * sendHtmlMail Invio in formato testo e html, con o senza allegati
 * @param {String} $from mittente
 * @param {String} $to destinatari
 * @param {String} $subject oggetto
 * @param {String} $text corpo in formato testo semplice
 * @param {String} $html corpo in formato HTML
 * @param {String} $bcc  eventuali destinatari ccn
 * @param {Array} $attachment eventuale allegato (array con chiavi: filename, filetype, filepath)
 * @return {Boolean} 1 se invio avvenuto con successo
 */
function sendHtmlMail($from,$to,$subject,$text,$html,$bcc,$attachment) {
	if (MAIL_TEST>"") { // destinatario di test
		if (MAIL_TEST=="dummy") { // NON INVIARE NULLA
			trace("Posta non inviata (dummy): from=$from,to=$to,subj=$subject");
			return TRUE;
		} else { // DEVIARE TUTTO VERSO EMAIL DI TEST
			trace("Posta deviata a ".MAIL_TEST.": from=$from,to=$to,subj=$subject");
			$to = MAIL_TEST;
		}
	}

	$boundary1 = sha1(uniqid());
	$boundary2 = sha1(uniqid());

	$headers = "From: $from".MAIL_NEWLINE;
	if ($bcc>'') $headers .= "Bcc: $bcc".MAIL_NEWLINE;
	$headers .= "X-Sender: $from".MAIL_NEWLINE;
	$headers .= "X-mailer: php".MAIL_NEWLINE;
	$headers .= "X-Priority: 3".MAIL_NEWLINE;
	$headers .= "Return-Path: $from".MAIL_NEWLINE;
	$headers .= "MIME-Version: 1.0".MAIL_NEWLINE;
	if ($attachment) { // con attachment
		$headers .= "Content-Type: multipart/mixed; boundary=$boundary1".MAIL_NEWLINE;
		$body .= MAIL_NEWLINE."--$boundary1".MAIL_NEWLINE;
		$body .= "Content-Type: multipart/alternative; boundary=$boundary2".MAIL_NEWLINE;
		$body .= "Content-Transfer-Encoding: 7bit".MAIL_NEWLINE.MAIL_NEWLINE;
	} else {
		$headers .= "Content-Type: multipart/alternative; boundary=$boundary2".MAIL_NEWLINE;
		$headers .= "Content-Transfer-Encoding: 7bit".MAIL_NEWLINE;
		$body = "";
	}

	//define the text body
	if ($text>'') {
		$body .= "--$boundary2".MAIL_NEWLINE;
		$body .= "MIME-Version: 1.0".MAIL_NEWLINE;
		$body .= 'Content-Type: text/plain; charset="utf-8"'.MAIL_NEWLINE;
		$body .= 'Content-Transfer-Encoding: 7bit'.MAIL_NEWLINE;
		$body .= MAIL_NEWLINE.$text.MAIL_NEWLINE.MAIL_NEWLINE;
	}
	$chunkedHtml = quoted_printable_encode($html); // prepara in formato quoted_printable
	$chunkedHtml = str_replace("\r\n",MAIL_NEWLINE,$chunkedHtml); // corregge il new line
	$body .= "--$boundary2".MAIL_NEWLINE;
	$body .= "MIME-Version: 1.0".MAIL_NEWLINE;
	$body .= 'Content-Type: text/html; charset="utf-8"'.MAIL_NEWLINE;
	$body .= 'Content-Transfer-Encoding: quoted-printable'.MAIL_NEWLINE;
	$body .= MAIL_NEWLINE.$chunkedHtml.MAIL_NEWLINE.MAIL_NEWLINE;
	$body .= "--$boundary2--".MAIL_NEWLINE;

	if ($attachment) {
		$data = file_get_contents($attachment['filepath']);
		if (!$data) {
			fail("Allegato $filepath non trovato");
			return false;
		}
		$data = chunk_split(base64_encode($data),76); // prepara in formato Base64
		$body .= MAIL_NEWLINE."--$boundary1".MAIL_NEWLINE;
		$body .= "MIME-Version: 1.0".MAIL_NEWLINE;
		$body .= 'Content-Type: {$attachment["filetype"]}; name="{$attachment["filename"]}"'.MAIL_NEWLINE;
		$body .= 'Content-Transfer-Encoding: base64'.MAIL_NEWLINE;	
		$body .= 'Content-Disposition: inline; filename="{$attachment["filename"]}"'.MAIL_NEWLINE;
		$body .= MAIL_NEWLINE.$data.MAIL_NEWLINE.MAIL_NEWLINE;
		$body .= "--$boundary1--".MAIL_NEWLINE;
	}
	trace("mail function: to=$to,subject=$subject, gli headers sono qui sotto:");
	trace($headers);
	trace("SEGUE IL BODY:");
	trace($body);
	trace("FINE DEL BODY");
	
	$esito =  mail($to,$subject,$body,$headers);
	trace("esito: ".($esito?"invio riuscito":"invio fallito"));
	return $esito;
}

//==============================================================
// 			PARTE QUARTA - FUNZIONI DI UTILITA'
//==============================================================
//--------------------------------------------------------------
// dateFromString
// Converte in vera data una stringa formato YYYY-MM-DD 
// oppure dd/mm/yyyy
//--------------------------------------------------------------
function dateFromString($ISOdate,&$over)
{
	try
	{
		$parti = explode(" ",$ISOdate); // separa data da ora
		if (count($parti)==2)
		{
			$data = $parti[0];
			$ora  = $parti[1];
			$ore = explode(":",$ora);
			if (count($ore)==2)
				$ore[2] = 0;
			else if (count($ore)==1)
				$ore[1] = $ore[2] = 0;
		}
		else
		{
			$data = $ISOdate;
			$ore  = array(0,0,0);
		}
		
		$arr = explode("-",$data);
		if (count($arr)==3) {	// formato ISO aaaa-mm-gg
			$anno = 0+$arr[0];
			$mese = 0+$arr[1];
			$giorno = 0+$arr[2];
		} else {
			$arr = explode("/",$data);
			if (count($arr)==3) {	// formato italiano gg/mm/aaaa
				$anno = 0+$arr[2];
				$mese = 0+$arr[1];
				$giorno = 0+$arr[0];
			} else
				return $ISOdate; // � gi� una data
		}
		
		// Controllo intervallo di validit� per mktime
		$over = 0;
		if ($anno>2037) 
			$over = $anno - 2037;
		else 
			if ($anno<1902)
				$over = $anno - 1902;
		$anno -= $over;
		return mktime(0+$ore[0],0+$ore[1],0+$ore[2],$mese,$giorno,$anno);
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		return NULL;
	}
}
//--------------------------------------------------------------
// italianDate
// Converte in stringa con formato dd/mm/yyyy una data oppure
// una stringa in formato YYYY-MM-DD
//--------------------------------------------------------------
function italianDate($data="")
{
	if ($data=="")
		return date("d/m/Y");
	else {
		// dateFromString usa mktime che � limitata al gennaio 2038
		// in over vengono messi gli anni eccedenti a quelli utilizzabili
		$over = 0;
		$dataConv = date("d/m/Y",dateFromString($data,$over));
		if ($over != 0) {
			$over += substr($dataConv,6);
			$dataConv = substr_replace($dataConv, $over, 6);
		}
		return $dataConv;
	}
}

//-------------------------------------------------------------------
// ISODate
// Converte in stringa con formato YYYY-MM-DD HH:MM:SS una stringa in 
// formato italiano d/m/yyyy con o senza ora+minuti+secondi
// 9/11/15: aggiunta opzione per avere la parte time solo se richiesta
//--------------------------------------------------------------------
function ISODate($data,$long=false)
{
	// dateFromString usa mktime che � limitata al gennaio 2038
	// in over vengono messi gli anni eccedenti a quelli utilizzabili
	$over = 0;
	if ($long)
		$dataConv = date("Y-m-d H:i:s",dateFromString($data,$over));
	else 
		$dataConv = date("Y-m-d",dateFromString($data,$over));
	if ($over != 0) {
		$over += substr($dataConv,0,4);
		$dataConv = substr_replace($dataConv, $over, 0, 4);
	}
	return $dataConv;
}

//--------------------------------------------------------------
// getUserName
// Restituisce lo user name da usare per il campo LastUser del
// DB, oppure "system" se non si � nel portale web.
// Se viene passato l'argomento, ci mette la chiave IdUser
//--------------------------------------------------------------
function getUserName(&$IdUser="NULL")
{
	global $context;
	try
	{
		if ($context)
		{
			$IdUser = $context["IdUtente"];
			if (!$IdUser)
				$IdUser = "NULL"; // stringa adatta alla scrittura sul DB
			return $context["Userid"]; // userid del portale web 
		}
		else
		{
			$IdUser = "NULL"; // stringa adatta alla scrittura sul DB
			return "system";
		}
	}
	catch (Exception $e)
	{
		$IdUser = "NULL"; // stringa adatta alla scrittura sul DB
		return "system";
	}
}

//**********************************************************************************************************
// generaCombo
// Genera una combobox per usi semplici
// Argomenti: 1) label del campo
//            2) nome della colonna chiave (id)
//            3) nome della colonna da usare come testo visibile
//            4) query, a partire dalla parola FROM inclusa
//            5) (opzionale) listener specializzato per l'evento select
//            6) (opzionale) allow-blanks (default "false", mettere "true" se si vuole permettere riga blank)
//            7) (opzionale) extended (true se extendedComboBox - cio� con selezione multipla)
//            8) (opzionale) preload  (true se deve essere caricata all'inizio, cio� definita con lazyInit:false)
//            9) (opzionale) valore iniziale
//           10) width
//*****************************************************************************************************************
function generaCombo($label,$keyField,$displayField,$query,$selectListener="",$allowBlank="false",
                      $extended=false,$preload=false, $valoreIniziale=null, $width="")
{
	$listener = $selectListener;
	if ($listener != "")
		$listener = "listeners: {select: $listener},";
	
	// Se il chiamante ha chiesto di impostare un valore iniziale, deve aspettare la load dello store: quindi
	// genera un listener per l'evento load
	if ($valoreIniziale>'') {
		$v = is_numeric($valoreIniziale)?$valoreIniziale : "'$valoreIniziale'";
		$storeLoadListener = ",listeners: {
					load: function(store,record,option){
						Ext.getCmp('cmb_$keyField').setValue($v);
					}}";
	} else {
		$storeLoadListener = "";
	}		
	
	$lazyInit = $preload?'false':'true';
	$allowBlank = ($allowBlank=="true"||$allowBlank===true)?"true":"false";
	// Se la query non contiene un order by,  di default usa l'ordinamento alfabetico
	$sortInfo= stripos($query,"order by ")===false ? ",sortInfo: {field: '$displayField'}" : ''; 
		
	if ($width=='') {
		$w = 'anchor:"97%"';
	} elseif (preg_match('/%/',$width)) { // width specificata in percentuale
		$w = "anchor:'$width'";
	} else {
		$w = "width:$width";
	}  
	
	if (!$extended) // combobox normale
	{
		return <<<EOT
			{xtype: 'combo',
			id: 'cmb_$keyField',
			fieldLabel: '$label',
			hiddenName: '$keyField',
			$w,editable: false,hidden: false,
			typeAhead: false,triggerAction: 'all',
			lazyRender: $lazyInit,
			lazyInit: $lazyInit,
			allowBlank: $allowBlank,
			store: {xtype:'store',
				proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
				baseParams:{task: 'read', sql: "SELECT DISTINCT $keyField,$displayField $query"},
				reader:  new Ext.data.JsonReader(
							{root: 'results',id: '$keyField'},
							[{name: '$keyField'},{name: '$displayField'}]
       	     			),
				autoLoad: !$lazyInit
       	     	$sortInfo
				$storeLoadListener
			},
			$listener
			displayField: '$displayField',
			valueField: '$keyField',
			value: '$valoreIniziale'
			}
EOT;
	}
	else // extended combobox
	{
		return <<<EOT
			{xtype: 'extendedComboBox',
			id: 'cmb_$keyField',
			fieldLabel: '$label',
			hiddenName: '$keyField',
			$w,editable: false,hidden: false,
			typeAhead: false,triggerAction: 'all',
			lazyRender: $lazyInit,
			lazyInit:  $lazyInit,
			allowBlank: $allowBlank,
			// parametri aggiunti o modificati
			singleSelect: false,
			allSelectionText: 'Seleziona tutti',
			showSearch: false, // non fa comparire il campo di ricerca
			showSelectAll: true,
			checkField: 'Selected', // nome del campo dello store che contiene il valore selezionato Y/N
			store: new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
				baseParams:{task: 'read', sql: "SELECT DISTINCT $keyField,$displayField,Selected $query"},
				reader:  new Ext.data.JsonReader(
							{root: 'results',id: '$keyField'},
							[{name: '$keyField'},{name: '$displayField'},{name: 'Selected', type:'boolean'}]
       	     			),
				autoLoad: !$lazyInit
       	     	$sortInfo
				$storeLoadListener
			}),
			// fine parametri aggiunti o modificati
			
			$listener
			displayField: '$displayField',
			valueField: '$keyField',
			value: '$valoreIniziale'
			}
EOT;
	}
}	
	
//**********************************************************************************************************
// redirect
// Genera il redirect ad altra pagina
//**********************************************************************************************************
function redirect($relativeUrl)
{
	$host  = $_SERVER['HTTP_HOST'];
	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off')?"https":"http";
	$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header("Location: $protocol://$host$uri/$relativeUrl");
	die();
}

//**********************************************************************************************************
// internetTime
// Formatta una data o timestamp secondo il formato internet (che va bene anche per fare una data sortabile)
//**********************************************************************************************************
function internetTime($timestamp)
{
	// dateFromString usa mktime che � limitata al gennaio 2038
	// in over vengono messi gli anni eccedenti a quelli utilizzabili
	$over = 0;
	$dataConv = strftime("%Y-%m-%dT%H:%M:%S",dateFromString($timestamp,$over));
	if ($over != 0) {
		$over += substr($dataConv,0,4);
		$dataConv = substr_replace($dataConv, $over, 0, 4);
	}
	return $dataConv;
}


//**********************************************************************************************************
// internetTime
// Formatta una data o timestamp secondo il formato internet (che va bene anche per fare una data sortabile)
//**********************************************************************************************************
/*function internetTime($timestamp)
{
	$zone = strftime("%z",$timestamp);
	trace("zone $zone");
	return strftime("%Y-%m-%dT%H:%M:%S",$timestamp).substr($zone,0,3).":".substr($zone,3,2);
}*/

//**********************************************************************************************************
// htmlstr
// Mette i codici di entity HTML al posto dei caratteri speciali in stringhe che devono essere visualizzate
//**********************************************************************************************************
function htmlstr($stringa)
{
	$new = htmlentities($stringa,ENT_COMPAT,'ISO-8859-1',false);
	if (!$new)
		$new = htmlentities($stringa);
	return $new;
}

//**********************************************************************************************************
// htmlentities_deep
// Mette i codici di entity HTML al posto dei caratteri speciali in una array di stringhe
//**********************************************************************************************************
function htmlentities_deep($value)
{
	if (is_array($value)) {
		$value = array_map("htmlentities_deep",$value);
	} else {
		$value = htmlentities($value);
	}
	return $value;
}

//**********************************************************************************************************
// convertToUTF8 
// Funzione chiamata con array_map per convertire in UTF8 tutti i campi letti in un array di array 
//**********************************************************************************************************
function convertToUTF8($elem)
{
	if (is_array($elem)) // riga di array a due dimensioni (es. getFetchArray)
	{
		foreach ($elem as $key=>$fld)
		{
			if ($fld!==NULL && mb_detect_encoding($fld."a","ASCII,UTF-8,ISO-8859-1")=="ISO-8859-1")
			{
				$fld = mb_convert_encoding($fld ,"UTF-8");
				$elem[$key] = $fld;
			}
		}
	}
	else if ($elem!==NULL && mb_detect_encoding($elem."a","ASCII,UTF-8,ISO-8859-1")=="ISO-8859-1") // campo di array ad una dimensione (es. getRow)
//	else if ($elem!==NULL && mb_detect_encoding($fld."a","ASCII,UTF-8,ISO-8859-1")!="UTF-8") // campo di array ad una dimensione (es. getRow)
	{
		$elem = mb_convert_encoding($elem ,"UTF-8");
	}
	return $elem;
}

//**********************************************************************************************************
// json_encode_plus 
// Funzione per il json_encode (di scalari o di array a una o due dimensioni) che possono contenere 
// caratteri non UTF-8. Usarla dove si vuole, in sostituzione della json_encode 
// Ritorna una stringa in cui i caratteri non UTF8 sono sotituiti da sequenza \unnnn, che json_decode
// riconosce bene.
//**********************************************************************************************************
function json_encode_plus($x)
{
	if (is_array($x))
		$x = array_map("convertToUTF8",$x);
	else
		$x = mb_convert_encoding($x ,"UTF-8");
	
	return json_encode($x);
}


//**********************************************************************************************************
// formatCAP (by Aldo)
// Formatta il Cap aggiungendo gli zeri (0) mancanti se il cap != null oppure restituisce null se il cap==null oppure cap=="" 
//**********************************************************************************************************
function formatCap($cap)
{
	if(is_null($cap)|| $cap=="")  
	{
	 	return NULL;  
	}
	else
	{
	 	return sprintf("%05s",$cap); 
	}
}

//**********************************************************************************************************
// createIban   (by Aldo)
// Crea il codice IBAN dato in input CAB - ABI - ContoCorrente - SOLO PER ITALIA (IT)
// Restituisce : 0    se ci sono errori ed l'iban non si pu� calcolare oppure il codice iban regolarmente calcolato
//**********************************************************************************************************
function createIban($abi, $cab, $cc)
{
	$Country_Cod = "IT";
    $sum =0;
	$cab= trim($cab);
	$abi= trim($abi);
	$cc = trim($cc);
	$cin ="";
	$strBan="";
    $bban="";
    $testIban="";
    $Iban="";
	$checkDigit="00";
	$abi = strtoupper($abi);
	$cab = strtoupper($cab);
	$cc  = strtoupper($cc);
    
    
	//echo("\nricevuti abi:.$abi. - cab:.$cab - cc:.$cc");
	
	if($cab!="")
	{
	  $cab=	sprintf("%05s",$cab);
	}
	else 
	{
		return "";
	}
	
	if($abi!="")
	{
	  $abi=	sprintf("%05s",$abi);
	}
	else 
	{
		return "";
	}
	
	if($cc!="")
	{
	  $cc=	sprintf("%012s",$cc);
	}
	else 
	{
		return "";
	}
	
	
	//echo("<BR> normalizzati abi:.$abi. - cab:.$cab - cc:.$cc");
	
	$strBan .=$abi;
	$strBan .=$cab;
	$strBan .=$cc;
	
	// calcolo cin 	
	
	$numbers = "0123456789";
	$chars ="ABCDEFGHIJKLMNOPQRSTUVWXYZ-. ";
	$listaPari = Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28);
    $listaDispari = Array (1,0,5,7,9,13,15,17,19,21,2,4,18,20,11,3,6,8,12,14,16,10,22,25,24,23,27,28,26);
	$sum ="";
	
	//echo("<br>ho l'elemento".strpos($chars,"C"));
	
	for ($k = 0; $k < 22 ; $k++)
    {
        $element = substr($strBan,$k,1);
        $i = strpos($numbers,$element);
    	    	
    	if($i===false)
    	{
          $i = strpos($chars,$element);
    	}
    	if($i === false)
    	{
    	 return 0;	
    	}
        
        if (($k % 2) == 0)
        {
         // valore dispari
          $sum += $listaDispari[$i];
        }
        else
        {
         // valore pari
          $sum += $listaPari[$i];
        }
    }
    
    $cin = $chars{$sum%26};
    
    //echo("cin=.$cin");
	
	
	// calcolo check digit 
	
    //$charsArr = Array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$charsArr = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. ";
	$tempIban  = "";
	$tempIban .= $cin;
	$tempIban .= $strBan;
	$tempIban .= $Country_Cod;
	$tempIban .= $checkDigit;
	$sum ="";
	$div = 97;
	
	//echo($tempIban);
	
	for($i = 0; $i<strlen($tempIban); $i++)
	{
	    $a = substr($tempIban,$i,1);
		$b = strpos($charsArr,$a);
		$sum .=  $b;
	}
	
	$resto = BigMod($sum,$div,2);
	$checkDigit= sprintf("%02s",98 - $resto);

    //echo("\nCalcolato Cin:.$cin");
    //echo("\nCalcolato Iban:.$Iban");
    

	$Iban.=$Country_Cod;
	$Iban.=$checkDigit;
	$Iban.=$cin;
	$Iban.=$abi;
	$Iban.=$cab;
	$Iban.=$cc;
    //echo("<BR> IBAN:".$Iban);
    
    return $Iban;
    
}

//**********************************************************************************************************
// BigMod (by Aldo)
// Calcola il Mod per numeri grandi che non si possono gestire con l'operatore % in quanto causa un overflow
// Parametri   : $strNumeratore - numero da dividere sotto forma di stringa es. "10120012010102001221500150412010"
//			     $dividendo     - es. 97
//				 $dim           - numero di caratteri delle sottostringhe per la divisione parziale 
// Restituisce : il resto della divisione intera 
//**********************************************************************************************************
function BigMod($strNumeratore, $dividendo, $dim)
{
	$tempStr="";
	$tempResto="";
	for($i=0; $i<strlen($strNumeratore); $i+=$dim)
	{
	 	$tempStr=$tempResto.substr($strNumeratore,$i,$dim);
	 	$tempResto = ((int)$tempStr) %$dividendo;
	}
	return (int) $tempResto;
}

//***************************************************
//Funzioni di processamento dei file lottomatica
//***************************************************
//funzione di riformattazione data
function datareformat($data){ 
    $aa=substr($data, 0, 2); 
    $mm=substr($data, 2, 2); 
    $gg=substr($data, 4, 2); 
    $aa=$aa+2000;
    $datareformat="$gg"."/"."$mm"."/"."$aa";
    return $datareformat; 
}
//************************************************************************
// getSysParm
// Legge un parametro di sistema da DB, o in alternativa dalle costanti
// o da un default
//************************************************************************
function getSysParm($codparm,$default="")
{
	// senza trace di errore, per non generare ricorsione infinita in "trace" che chiama getSysParm
	$valore = getScalar("SELECT ValoreParametro FROM parametrosistema WHERE CodParametro='$codparm'",true);
	if ($valore>"")
		return $valore;
	$valore = constant($codparm); // prende dalle defines
	if ($valore>"")
		return $valore;
	else
		return $default;	
}

//************************************************************************
// setSysParm
// Imposta un parametro di sistema 
//************************************************************************
function setSysParm($codparm,$value) {
	execute("REPLACE INTO parametrosistema(CodParametro,ValoreParametro) VALUES('$codparm',".quote_smart($value).")");
}

//************************************************************************
// deferMail
// Salva un messaggio di mail su tabella, per il successivo invio
// differito, a fine processo
//************************************************************************
function deferMail($mittente,$destinatario,$subject,$message,$ccDest) {
	global $context;
	$colList = ""; // inizializza lista colonne
	$valList = ""; // inizializza lista valori
	addInsClause($colList,$valList,"ProcessName",$context["process_name"],"S");
	addInsClause($colList,$valList,"Mittente", $mittente,"S");
	addInsClause($colList,$valList,"Destinatario",$destinatario,"S");
	addInsClause($colList,$valList,"Soggetto",$subject,"S");
	addInsClause($colList,$valList,"ccDest",$ccDest,"S");
	addInsClause($colList,$valList,"Messaggio",$message,"S");
	addInsClause($colList,$valList,"DataCreazione","NOW()","G");

	$savenm = $context["notify_mode"];
	$context["notify_mode"] = "immediate"; // evita ricorsione su questa funzione, in caso di errore SQL
	if (execute("INSERT INTO maildifferita ($colList)  VALUES ($valList)")) {
		$context["notify_mode"] = $savenm; // ripristina
		return TRUE;
	} else {
		return FALSE; // lascia il deferred mode spento, visto che qualcosa � andato storto
	}
}

//*************************************************************************
// sendDeferMail
// Legge tutti i messaggi di mail a invio differito per un dato processo
// inviandoli con parametri fissi e componendo il body con la concatenazione
// dei body dei singoli messaggi. Per ora, pur essendo registrati mittente, 
// destinatario, ccDest e subject per ogni singolo messaggio, queste 
// informazioni non vengono utilizzate.
//*************************************************************************
function sendDeferMail() {
	global $sito,$context;
	$testi = fetchValuesArray("SELECT messaggio FROM maildifferita WHERE ProcessName='{$context["process_name"]}'"
				." AND DataInvio IS NULL AND DataCreazione>CURDATE()-INTERVAL 10 HOUR ORDER BY LastUpd");
	$body = "";
 
	if (is_array($testi)) {
		foreach ($testi as $testo) {
			$body .= "\n<br>$testo";
		}			
		echo $body; // anche su console, per le prove
		$context["notify_mode"] = "immediate"; // ripristina la modalit� di invio normale
		if (sendMail(MAIL_SENDER,getSysParm("ADMIN_MAIL"),"Messaggio da Connecticut sito=$sito",$body,"","")) {
			execute("UPDATE maildifferita SET DataInvio=NOW() WHERE ProcessName='{$context["process_name"]}'"
				." AND DataInvio IS NULL AND DataCreazione>CURDATE()-INTERVAL 10 HOUR ORDER BY LastUpd");
			return TRUE;		
		} else
			return FALSE;
	} else {
		return FALSE;
	}
}

/**
 * fail termina restituendo (in json) il messaggio di errore, scrive su traccia ed
 *      invia per email all'amministratore (se non inibito)
 * @param {String} $msg messaggio di errore
 * @param {Boolean} $sendMail indica se il messaggio deve essere spedito all'amministratore (default=true)
 */
function fail($msg,$sendMail=true) {
	global $file,$sendResult,$connection,$inTransaction;
	trace($msg,false,$sendMail);
	if ($connection) {
		if ($inTransaction) rollback();
		enableForeignKeys(true);
		closeDb();
	}
	// restituisce anche failure, per compatibilit� con il vecchio
	$response = json_encode_plus(array("success"=>false, "failure"=>true, "error"=>$msg));
	die($response);
}

/**
 * Success termina con esito OK restituendo una qualunque variabile (singola, array ecc.)
 *  in formato json nella forma {"success":true, "data": dati}. Opzionalmente, restituisce
 *  anche il numero totale di righe nei dati ritornati
 *  @param {Object} $data qualunque dato da restituire
 *  @param {Number} $total se non nullo, il valore viene restituito nella propriet� "total"
 */
function success($data, $total=null) {
	global $connection,$inTransaction;
	if ($connection) {
		if ($inTransaction) commit();
		closeDb();
	}
	if ($total==null) {
		$response = json_encode_plus(array("success"=>true, data=>$data));
	} else {
		$response = json_encode_plus(array("success"=>true, total=>$total, data=>$data));
	}
	if ($_REQUEST['callback']>'') // it is a JSONP call
		die($_REQUEST['callback'] . "($response)");
	else
		die($response);
}

/**
 * Funzione che trasforma un numero in lettere
 * La utilizziamo per la trasformazione del numero delle rate nei modelli delle lettere da inviare
 */

function numero_lettere($numero){
	if (($numero < 0) || ($numero > 999999999)){
		return "$numero";
	}

	$milioni = floor($numero / 1000000);  // Milioni
	$numero -= $milioni * 1000000;
	$migliaia = floor($numero / 1000);    // Migliaia
	$numero -= $migliaia * 1000;
	$centinaia = floor($numero / 100);     // Centinaia
	$numero -= $centinaia * 100;
	$decine = floor($numero / 10);       // Decine
	$unita = $numero % 10;               // Unit�

	$cifra_lettere = "";

	if ($milioni){
		$tmp = numero_lettere($milioni);
		$cifra_lettere .= ($tmp=='uno') ? '' : $tmp;
		$cifra_lettere .= ($milioni == '1') ? "un milione ":" milioni ";
	}
	if ($migliaia){
		$tmp = numero_lettere($migliaia);
		$cifra_lettere .= ($tmp=='uno') ? '' : $tmp;
		$cifra_lettere .= ($migliaia == '1') ? "mille":"mila ";
	}
	if ($centinaia){
		$tmp = numero_lettere($centinaia);
		$cifra_lettere .= ($tmp=='uno') ? '' : $tmp;
		$cifra_lettere .= "cento";
	}
	$array_primi = array("", "uno", "due", "tre", "quattro", "cinque", "sei",
			"sette", "otto", "nove", "dieci", "undici", "dodici", "tredici",
			"quattordici", "quindici", "sedici", "diciassette", "diciotto",
			"diciannove");
	$array_decine = array("", "", " venti", " trenta", " quaranta", " cinquanta", " sessanta",
			" settanta", " ottanta", " novanta");
	$array_decine_tronc = array("", "", " vent", " trent", " quarant", " cinquant", " sessant",
			" settant", " ottant", " novant");
	
	if ($decine || $unita){
		if ($decine < 2){
			$cifra_lettere .= $array_primi[$decine * 10 + $unita];
		}else{
			if ($unita == 1 || $unita == 8)
				$cifra_lettere .= $array_decine_tronc[$decine];
				else
					$cifra_lettere .= $array_decine[$decine];
					if ($unita){
						$cifra_lettere .= $array_primi[$unita];
					}
		}
	}


	if (empty($cifra_lettere)){
		$cifra_lettere = "zero";
	}

	return $cifra_lettere;
}

/**
 * calcolaNumeroBuild
 * Determina il numero di BUILD da indicare in coda al numero di versione
 * Il numero è dato dal numero di giorni trascorsi tra l'ultimo aggiornamento di files php/js e la data di inizio della versione,
 * indicata dalla define DATA_VERSIONE (se manca, DATA_VERSIONE='2016-06-01')
 */
function calcolaNumeroBuild() {
	return max(ultimaDataFile(dirname(__FILE__),'/../main.+php$/'), // files rilevanti della cartella root
		ultimaDataFile(dirname(__FILE__),'/php$/','/constant.php/'), // files rilevanti della cartella server
		ultimaDataFile(dirname(__FILE__).'/batch','/php$/'), // files rilevanti della cartella server/batch
		ultimaDataFile(dirname(__FILE__).'/charts','/php$/'), // files rilevanti della cartella server/charts
		ultimaDataFile(dirname(__FILE__).'/../js','/js$/')); // files rilevanti della cartella js
}

/**
 * ultimaDataFile
 * Determina la data di modifica più recente in una directory data, includendo tutti i file che corrispondono ad una data espressione
 * regolare ed eventualmente escludendo quelli che corrispondono ad una seconda espressione regolare
 */
function ultimaDataFile($folder,$include,$exclude='/***/') {
	$files = scandir($folder);
	$lastupd = 0;
	foreach ($files as $file) {
		if (preg_match($include,$file) && !preg_match($exclude,$file)) {
			$lastupd = max($lastupd,filemtime("$folder/$file"));
		}
	}
	return $lastupd;
}
//-----------------------------------------------------------------------
// Get a page using curl
// the options parameter is an array with any additional option allowed
// for the curl_setopt function
//-----------------------------------------------------------------------
/**
 * doCurl Chiama un URL con curl, speificando eventuali headers aggiuntivi
 * @param {String} $url URL da chiamare
 * @param {String $data dati da passare in caso di POST
 * @param {String} $headers HTTP headers aggiuntivi
 * @return {String} contenuto della pagina oppure FALSE
 */
global $curl;
function doCurl($url,$data=null,$headers=null) {
	global $curl;
	if (!$curl) {
		$curl = curl_init();
	}
	curl_setopt($curl,CURLOPT_URL,$url);
	if (constant('PROXY')) {
		curl_setopt($curl, CURLOPT_PROXY, PROXY.':'.PROXYPORT);      
		if (constant('PROXYUSERPWD'))
			curl_setopt($curl, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
		trace('PROXY usato per il curl: '.PROXY.':'.PROXYPORT.' userpwd=('.constant('PROXYUSERPWD').')',false);
	}
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	//curl_setopt($curl,CURLOPT_TIMEOUT,10); // aspetta al max 10 sec.
	curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
	if ($data>'') {
		curl_setopt($curl,CURLOPT_POST,true);
		curl_setopt($curl,CURLOPT_HTTPGET,true);
		curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
	} else {
		curl_setopt($curl,CURLOPT_POST,false);
		curl_setopt($curl,CURLOPT_HTTPGET,true);
	}
	if ($headers) {
		trace('Aggiunti headers HTTP: '.print_r($headers,true),false);
		curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
	}
	$resp = curl_exec($curl);
	$info = curl_getinfo($curl);

	if (!($info['http_code']==200 || $info['http_code']==201)) {
		trace("Failed HTTP curl request: $url; resp=$resp, info: ".print_r($info,true));
	}
	return $resp; // torna comunque la risposta
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
function writeProcessLog($process,$text,$level=0)
{
	global $debug; // settata dal chiamante se vuole vedere i messaggi a console

	if($debug) echo "$text<br>\n";
	
	trace($text,false,$level>1); // invia mail per livello di log 2, 3 e 4

	if ($level>=3) { // richiesto invio SMS
		$smsDest = getSysParm("ADMIN_SMS");
		if ($smsDest>'') {
			inviaSMS($smsDest,$text,$errmsg);
		}
	}
	$sql = "INSERT INTO processlog (ProcessName,LogMessage,LogLevel) VALUES(".quote_smart($process).",".quote_smart($text).",$level)";
	execute($sql,false); // evita traccia perch� se MySql � KO , va in ricorsione
	
	if ($level!=-2) {
		if (hasProcessLogInterrupt($process)) { // ricevuto comando di chiusura
			return false;
		}
	}
	return true;
}

/**
 * hasProcessLogInterrupt
 * Torna TRUE se nel processlog, per il processo dato, � stata registrata una richiesta di interruzione (LogLevel=-2)
 */
function hasProcessLogInterrupt($process) {
	return rowExistsInTable('processlog','LogLevel=-2 AND ProcessName='.quote_smart($process));
}

/**
* MODIFICA 2020-05-12 PER SITUAZIONE COVID-19
* generaQRCode genera un qr code contenente la stringa Sisal cosi' fatta come da esempio
* BP=9712000000784819590000173040260000025750896
* il tag fisso e' 'BP='
* il codice di pagamento e' '971200000078481959'
* cosi suddiviso  97 12 000000784819 59
* i caratteri 97 sono cosi ricavati: se il tipo contratto del titolare e' LO restituisce 97, se LE 98 altrimenti 99.
* Poi il 12 si riferisce al numero delle rate (se riesci a recuperarle bene altrimenti puoi inserire un valore fisso)
* I successivi 12 caratteri e' il codice del contratto   000000784819.
* gli ultimi 2 caratteri codice 59 , e' un codice fisso
* il c/c postale fisso à '000017304026'
* l'importo da recuperare e' '0000025750' ossia euro 257,50
* il tipo di avviso di pagamento (bollettino premarcato) fisso a '896'
* Per la generazione del codice QR viene utilizzata la libreria PHP qrlib.php:
* si usa la chiamata QRcode::png($testo,$filepath)
* Una volta creata viene convertita in base 64 e poi il file dell'immagine viene cancellata
* dalla cartella temporanea in cui si trova
* @param {Array] $contratto record relativo al contratto per cui viene generato il codice qr
* @param {String} $errConvertion (byRef) stringa che contiene messaggio di errore in caso di mancata
* @return {String} $base64 stringa in base64 dell'immagine
* generazione dell'immagine o non riconoscimento del codice contratto
*/
function generaQRCode($contratto,&$errConvertion){
	
	extract($contratto);
	
	if(preg_match("/([a-z]{2,})([0-9]{2,})/i",$CodContratto,$matches)){
		if($matches){
			$tipocontratto = "";
			switch($matches[1]){
				case "LO":
					$tipocontratto = "97";
					break;
				case "LE":
					$tipocontratto = "98";
					break;
				default:
					$tipocontratto = "99";
					break;
			}
			$codice = str_pad($matches[2],12,'0',STR_PAD_LEFT);
			$imptotaledebito= str_pad(str_replace(".","",str_replace(",","",$ImpInsolutoIT)),10,'0',STR_PAD_LEFT);
			$numerorate = str_pad($TotaleNumeroRate, 2, '0', STR_PAD_LEFT);
			$codpagamento = $tipocontratto.$numerorate.$codice."59";
			$ccpostale="000017304026";
			$tipopagamento = "896";
			$stringaSisal = "BP=".$codpagamento.$ccpostale.$imptotaledebito.$tipopagamento;
			trace("Stringa Sisal - contratto $CodContratto: $stringaSisal",false);

			//Genero filename con timestamp in testa in modo da renderlo univoco
			//al momento della cancellazione dopo la trasformazione in base 64
			$time = strtotime(date('Y-m-d h:i:s'));
			$filename = TMP_PATH."/".$time."qrcode_".$CodContratto.'.png';


			// generating
			if (!file_exists($filename)) {
				QRcode::png($stringaSisal,$filename);
				trace("Generato il file $filename",false);
				
				$type = pathinfo($filename, PATHINFO_EXTENSION);
				$data = file_get_contents($filename);
				$base64 = 'data:image/'.$type.';base64,'. base64_encode($data);
				trace("Cancello il file $filename",false);
				unlink($filename);
				return '"'.$base64.'"';
			}else{
				$errConvertion = "Errore nella generazione del file qrcode $filename per il contratto $CodContratto";
				return "";
			}
		}
	}else{
		$errConvertion = "Generazione del QR Code interrotta. Non e' stato possibile riconoscere il contratto $CodContratto dal formato del codice.";
		return "";
	}
}
?>
