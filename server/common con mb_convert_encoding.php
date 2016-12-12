<?php 
error_reporting(E_ERROR);	// METTERE a 0 in produzione
//error_reporting(0);
	
require_once("constant.php");

if (!isset($_SESSION))
{
	session_cache_limiter('private');
	session_cache_expire(0);	
	session_start();
}

setlocale(LC_ALL, 'it_IT', 'IT', 'ita');

/* SCA TOLTO IL REDIRECT A LOGIN.HTML PER INCLUSIONE BRIDGE 
$self_file = basename($_SERVER['PHP_SELF']);        
if (!isset($_SESSION['userContext']) && $self_file!="login.php") {
	$loc = 'index.html';
	if ($self_file!='main.php') {
		$loc .= '?r='.$_SERVER['PHP_SELF'];
	}
	redirect($loc);
	die();
}
*/
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
//     può anche essere omesso perché a fine script ci pensa 
//     il sistema. Notare anche che la connect automaticamente
//     riusa l'eventuale connessione già disponibile.
//-------------------------------------------------------------- 
function openDb() {	// alias di getDbConnection
	return getDbConnection();
}

function getDbConnection()
{
	global $connection;
	if (!$connection) {
		$connection = @mysqli_connect(MYSQL_SERVER,MYSQL_USER, MYSQL_PASS, MYSQL_SCHEMA, MYSQL_PORT);
		if (!$connection) {
			trace("Connessione fallita: ".mysqli_connect_error());
			Throw new Exception("Connessione al database non riuscita: ".mysqli_connect_error());				
		}
		if (!mysqli_set_charset($connection,"latin1")) 
		{
			trace("mysqli_set_charset fallita: ".mysqli_connect_error());
		}
	}
	return $connection;
}

function closeDbConnection()
{
	global $connection;
	if ($connection) {
		@mysqli_close($connection);
		$connection = FALSE;
	}
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
		if ($last>"") // c'è stato effettivamente un errore MySql
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
// convertEncoding
// Callback function per effettuare la conversione UTF-8 -> HTNML-ENTITIES
// su tutti gli elementi letti dal DB (per evitare problemi di visualizz.
// HTML dei caratteri accentati)
//-----------------------------------------------------------------------
function convertEncoding($value)
{
	if ($value==NULL || is_numeric($value))
		return $value;
	else
		return mb_convert_encoding($value,"HTML-ENTITIES");
}

//-----------------------------------------------------------------------
// getScalar
// Legge il valore di una singola colonna (torna NULL in caso di EOF)
//-----------------------------------------------------------------------
function getScalar($sql)
{
	$item = NULL;
	startTiming();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			$item = mysqli_fetch_row($res); 
			if ($item)	// se non ci sono righe è NULL
				$item = convertEncoding($item[0]);		
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".mysqli_error($conn));
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
function getRow($sql, $type=MYSQL_ASSOC)
{
	$array = NULL;
	startTiming();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) 
		{
			if ($rec = mysqli_fetch_array($res, $type))
				$array = array_map("convertEncoding",$rec);
			mysqli_free_result($res);
		} 
		else 
		{
			trace("Errore nella query  $sql: ".mysqli_error($conn));
		}
	}

	stopTiming($sql);
	return $array;
}
//-----------------------------------------------------------------------
// getFetchArray
// Esegue una query e ritorna un array con le righe estratte. Ogni riga è un array, di
// default, di tipo associativo
//-----------------------------------------------------------------------
function getFetchArray($sql, $type=MYSQL_ASSOC,&$error)
{
	startTiming();
	$arrayItem = array();
	if ($conn = getDbConnection()) { 	
		if ($res = mysqli_query($conn, $sql)) {
			while($rec = mysqli_fetch_array($res, $type)){
				$arrayItem[] = array_map("convertEncoding",$rec);
			}
			mysqli_free_result($res);
			$error = "";
		} else {
			$error = mysqli_error($conn);
			trace("Errore nella query  $sql: ".mysqli_error($conn));
		}
	}

	stopTiming($sql);
	return $arrayItem;
}
//-----------------------------------------------------------------------
// getFetchKeyValue
// Esegue una query e ritorna un array di righe con key e value. 
// Ogni riga è un array di colonne, di default, di tipo associativo. 
//-----------------------------------------------------------------------
function getFetchKeyValue($sql, $key, $value, $type=MYSQL_ASSOC)
{
	startTiming();
	$arrayItem = array();
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, $sql)) {
			while($rec = mysqli_fetch_array($res, $type)){
				$arrayItem[$rec[$key]] = convertEncoding($rec[$value]);
			}
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".mysqli_error($conn));
		}
	}

	stopTiming($sql);
	return $arrayItem;
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
			while($rec = mysqli_fetch_array($res, MYSQL_NUM)){
				$array[] = array_map("convertEncoding",$rec[0]);
			}
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".mysqli_error($conn));
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
	if ($conn = getDbConnection()) { 		
		if ($res = mysqli_query($conn, "SELECT 'x' FROM $table WHERE $sqlWhere LIMIT 1")) {
			$result = (mysqli_num_rows($res)==1);
			mysqli_free_result($res);
		} else {
			trace("Errore nella query  $sql: ".mysqli_error($conn));
		}
	}
	stopTiming($sql);
	return $result;
}

//----------------------------------------------------------------------------------------------
// execute
// Esegue un comando e gestisce il codice di errore
//----------------------------------------------------------------------------------------------
function execute($sql)
{
	startTiming();
	if ($conn = getDbConnection()) { 		
		$result = mysqli_query($conn, $sql);
		if (!$result)
			trace("Errore nella query  $sql: ".mysqli_error($conn));
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
			trace("Errore nella beginTrans: ".mysqli_error($conn));
		else
			$inTransaction = TRUE;
	}
	else
		trace("Errore nella connessione al DB: ".mysqli_error($conn));	
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
			trace("Errore nella commit: ".mysqli_error($conn));
		else
		{
			$inTransaction = FALSE;
			mysqli_autocommit($conn, TRUE);
		}
	}
	else
		trace("Errore nella connessione al DB: ".mysqli_error($conn));	
}

//----------------------------------------------------------------------------------------------
// rollback
// Termina una transazione con rollback
//----------------------------------------------------------------------------------------------
function rollback()
{
	global $inTransaction;
	if (!$inTransaction)
		return;
	if ($conn = getDbConnection()) 
	{ 		
		if (!mysqli_rollback($conn))
			trace("Errore nella rollback: ".mysqli_error($conn));
		else
		{
			$inTransaction = FALSE;
			mysqli_autocommit($conn, TRUE);
		}
	}
	else
		trace("Errore nella connessione al DB: ".mysqli_error($conn));	
}

//----------------------------------------------------------------------------------------------
// addSetClause
// Aggiunge un elemento ad una clausola SET data
// Argomenti:
//	1) $setClause (byref) clausola da modificare appendendo il nuovo termine
//  2) $fieldName		  nome del campo
//  3) $fieldValue		  espressione da assegnare al campo
//  4) $fieldtype		  N=numerico, S=stringa, D=Data, G=generico (espressione già completa)
//----------------------------------------------------------------------------------------------
function addSetClause(&$setClause,$fieldName,$fieldValue,$fieldType)
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
//  5) $fieldtype		  N=numerico, S=stringa, D=Data, G=generico (espressione già completa)
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
//==============================================================
// 			PARTE SECONDA - FUNZIONI DI TRACCIA
//==============================================================
//--------------------------------------------------------------
// trace
// Registra un messaggio, con data/ora, su file
//--------------------------------------------------------------
function trace($msg,$backTrace=TRUE)
{
	try
	{
		if (TRACE)
		{
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
							file_put_contents(LOG_PATH."/trace.txt",date("Y-M-d H.i.s")." ".$chiamata." --- $msg\n",FILE_APPEND);
							$nrighe++;
							if (BACKTRACE)
								$msgDone = true;
							else
								break;
						} else {
							$chiamata = "Modulo: ".basename($backtrace[$i]['file']).' linea: '.$backtrace[$i]['line'].' funzione: '.$backtrace[$i]['function'];
							file_put_contents(LOG_PATH."/trace.txt","$chiamata\n",FILE_APPEND);
							$nrighe++;
						}
					}
//				}
			}
			else // senza backtrace (messaggi di traccia per cui si vuole solo la riga del messaggio)
			{
					file_put_contents(LOG_PATH."/trace.txt",date("Y-M-d H.i.s")." $msg\n",FILE_APPEND);
			}
			@chmod(LOG_PATH."/trace.txt",0777); // caso mai l'avesse creato adesso
			return LOG_PATH."/trace.txt	 righe=$nrighe";
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
//--------------------------------------------------------------
function writeLog($tipo,$source,$msg,$CodEvento)
{
	global $context;
	if (!execute("INSERT INTO log (TipoLog,Sorgente,DescrEvento,IdUtente,CodEvento)"
	            ." VALUES('$tipo',".quote_smart($source).",".quote_smart($msg).",".$context["IdUtente"].",".quote_smart($CodEvento).")"))
		trace("Impossibile registrare sulla tabella Log il messaggio seguente: $msg");
}	        
//--------------------------------------------------------------
// returnError
// Termina con un messaggio su trace e file ed esegue un 
// die() con il prefisso K\t che indica al chiamante un errore
//--------------------------------------------------------------
function returnError($msg,$source,$logSuDb)
{
	trace("[".$source."] ".$msg);
	if ($logSuDb)
		//writeLog("ERROR",$source,$msg);	
	die("K\t$msg");
}

//==============================================================
// 			PARTE TERZA - FUNZIONI DI E-MAIL
//==============================================================
//**********************************************************************************************************
// sendMail
//**********************************************************************************************************
/**
*	Invia e-mail
*
*   NB:&nbsp;il parametro $attachment è l'array di proprietà dato da $_FILES per un file uploaded
*/
function sendMail($mittente,$destinatario,$subject,$message,$attachment)
{
	if (MAIL_TEST>"")
	{
		if (MAIL_TEST=="dummy")
		{
			trace("Posta non inviata (dummy): $mittente,$destinatario,$subject,$message,$attachment");
			return TRUE;
		}
		else // destinatario di test
		{
			trace("Posta deviata a ".MAIL_TEST.": $mittente,$destinatario,$subject,$message,$attachment");
			$destinatario = MAIL_TEST;
		}
    }
    else
		trace("$mittente,$destinatario,$subject,$message,$attachment");
    /* caso senza attachment */
    if ($attachment=="")
	    return mail($destinatario,$subject,$message,"From: ".getSysParm("MAIL_SENDER","noreply@dcsys.it")
	    .MAIL_NEWLINE."MIME-Version: 1.0".MAIL_NEWLINE."Content-type: text/html; charset=UTF-8".MAIL_NEWLINE);

    /* con un attachment */
    $fileatt_type = $attachment["type"];    // mime Type
    $filename = $attachment["name"];        // nome del file originario
    $physfile = $attachment["tmp_name"];    // file fisico nome completo
    if (is_readable($physfile))
    {
        if(filesize($physfile)<=0)
        {
  			echo "Non è possibile allegare file di lunghezza zero (".$filename.")<br>";
  			return false;
        }
        $file = fopen($physfile,'rb');
  		if ($file==false)
  		{
  			echo "Impossibile leggere il file ".$physfile."<br>";
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
		echo "Impossibile leggere il file ".$physfile."<br>";
		return false;
	}
    // crea la stringa di delimitazione multipart
    $mime_boundary = md5(time());

// NOTA: nonostante il protocollo SMTP preveda il doppio carattere \r\n per l'invio, se si usa questo
// come new line sotto linux, l'effetto è quello di vederlo ovunque raddoppiato (evidentemente lo fa la
// funzione mail chiamata al termine). Perciò i salti riga sono messi con la costante MAIL_NEWLINE
// (da constant.inc) che in Windows vale \r\n e in Linux solo \n.

        //&nbsp;crea header SMTP e include il testo del messaggio
    $headers = "From: ".getSysParm("MAIL_SENDER","noreply@dcsys.it");
    $headers .= MAIL_NEWLINE."MIME-Version: 1.0".MAIL_NEWLINE."Content-Type: multipart/mixed; boundary=\"{$mime_boundary}\"";
    $email_message = MAIL_NEWLINE."--{$mime_boundary}".MAIL_NEWLINE;
    $email_message .= "Content-Type:text/plain; charset=\"iso-8859-1\"".MAIL_NEWLINE."Content-Transfer-Encoding: 8bit".MAIL_NEWLINE.MAIL_NEWLINE;
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

    return mail($destinatario,$subject, $email_message, $headers);
}

//==============================================================
// 			PARTE QUARTA - FUNZIONI DI UTILITA'
//==============================================================
//--------------------------------------------------------------
// dateFromString
// Converte in vera data una stringa formato YYYY-MM-DD 
// oppure dd/mm/yyyy
//--------------------------------------------------------------
function dateFromString($ISOdate)
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
			$ora  = "";
		}
		
		$arr = explode("-",$data);
		if (count($arr)==3)
		{
			if ($ora>"")
				return mktime(0+$ore[0],0+$ore[1],0+$ore[2],
				              0+$arr[1],0+$arr[2],0+$arr[0]);
			else
				return mktime(0,0,0,0+$arr[1],0+$arr[2],0+$arr[0]);
		}
		else
		{
			$arr = explode("/",$ISOdate);
			if (count($arr)==3)
			{
				if ($ora>"")
					return mktime(0+$ore[0],0+$ore[1],0+$ore[2],
				              0+$arr[1],0+$arr[0],0+$arr[2]);
				else
					return mktime(0,0,0,0+$arr[1],0+$arr[0],0+$arr[2]);
			}	
			else
				return $ISOdate; // è già una data
		}
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
	else
		return date("d/m/Y",dateFromString($data));
}

//--------------------------------------------------------------
// ISODate
// Converte in stringa con formato YYYY-MM-DD HH:MM:SS una stringa in 
// formato italiano d/m/yyyy con o senza ora+minuti+secondi
//--------------------------------------------------------------
function ISODate($data)
{
	$data = dateFromString($data);
	return date("Y-m-d H:i:s",$data);
}

//--------------------------------------------------------------
// getUserName
// Restituisce lo user name da usare per il campo LastUser del
// DB, oppure "system" se non si è nel portale web.
// Se viene passato l'argomento, ci mette la chiave IdUser
//--------------------------------------------------------------
function getUserName(&$IdUser=NULL)
{
	global $context;
	try
	{
		if ($context)
		{
			$IdUser = $context["IdUtente"];
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
//            5) (opzionale) listener specializzato
//            6) (opzionale) allow-blanks (default "false", mettere "true" se si vuole permettere riga blank)
//**********************************************************************************************************
function generaCombo($label,$keyField,$displayField,$query,$selectListener="",$allowBlank="false")
{
	$listener = $selectListener;
	if ($listener != "")
		$listener = "listeners: {select: $listener},";
		
	return <<<EOT
	{xtype: 'combo',
	fieldLabel: '$label',
	hiddenName: '$keyField',
	anchor: '97%',editable: false,hidden: false,
	typeAhead: false,triggerAction: 'all',
	lazyRender: true,
	allowBlank: $allowBlank,
	store: {xtype:'store',
			proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
			baseParams:{task: 'read', sql: "SELECT $keyField,$displayField $query"},
			reader:  new Ext.data.JsonReader(
						{root: 'results',id: '$keyField'},
						[{name: '$keyField'},{name: '$displayField'}]
            			),
			sortInfo:{field: '$displayField', direction: "ASC"}
	},
	$listener
	
	displayField: '$displayField',
	valueField: '$keyField'
	}
EOT;
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
	$zone = strftime("%z",$timestamp);
	return strftime("%Y-%m-%dT%H:%M:%S",$timestamp).substr($zone,0,3).":".substr($zone,3,2);
}

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
// Restituisce : 0    se ci sono errori ed l'iban non si può calcolare oppure il codice iban regolarmente calcolato
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

?>