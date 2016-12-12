<?php
//----------------------------------------------------------------------------------------------------------------------
// listaFileImport.php
// Scopo: 		riceve dal Db la lista dei file importati --> &tasc = file
//				riceve dal Db la lista dei messaggi di errore del file ($tasc = msg e idFile presente)
// Argomenti:	$ tasc       - il tipo di interrogazione
//				$idFile      - il file per il quale ricevere gli mess
//				$idCompagnia - ricevuto dal context, serve per prendere i file importati solo di quella data compagnia
// Risposta:	ritorna i valori in formato json ricavati dal db
//----------------------------------------------------------------------------------------------------------------------

require_once("common.php");

doMain();

function doMain()
{
	global $context;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	$idLog = ($_REQUEST['idLog']) ? ($_REQUEST['idLog']) : null;
	$idCompagnia = $context["idCompagnia"];
	try
	{
		
		$conn = getDbConnection();	// ottiene la connessione al db oppure esce
		if (!$conn)
			die("{failure:true}");
			
		switch($task){
			case "file":
				if(!$idCompagnia)
				{
				 	trace("Nessun idCompagnia ricevuto.");
					die("{failure:true}"); 	
				}
				
				// ricevo i file importati da importLog della compagnia con idCompagnia specificato nel context 
				$query1 = "SELECT IdImportLog, ImportTime, FileType, FileId, ImportResult, Status, Message,lastupd from ";
				$query2 = "v_fileimport where IdCompagnia=$idCompagnia";
				break;
			case "monitor":
				$idInizio = getScalar("SELECT MAX(IdProcessLog) FROM processlog l WHERE ProcessName='OCS_IMPORT'
							 AND LogMessage like 'Inizio del processo%'");
				$query1 = "select IF(LogLevel IN (0,4),'Info','Errore') AS Severity,LogMessage,lastupd from ";
				$query2 = "processlog where ProcessName='OCS_IMPORT' AND IdProcessLog>=0$idInizio";
				break;
			case "msg":
				if(!$idLog)
				{
				 	trace("Nessun idImportLog ricevuto.");
					die("{failure:true}"); 	
				}
				// ricevo i messaggi di errore per un dato log
				$query1 = "select * from ";
				$query2 = "v_msg_err_import_file where idLog=$idLog";
				break;
			default:
				echo "{failure:true}";
				return;
			break;
			}
			
			// prende il numero delle occorrenze della query
			$counter = getScalar("SELECT count(*) FROM $query2");
						
			// se non ricevo nulla dalla tab imposto il counter a 0 e non eseguo la query
			if ($counter == NULL)
				$counter = 0;
				
			if ($counter == 0) 
			{
					$arr = array();
				
			}
			else // se ci ricevo dati dalla query
			{
				// controllo se ci sono limiti di paginazione pertanto imposto i valori di inizio e limite nella query
				$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
				$end =   isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
				
				if ($start!='' || $end!='') {
		    		$query2 .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
				}
				
				$sql = $query1;
				$sql .= $query2;
				$arr = getFetchArray($sql);
				
			}// end else
			
			$data = json_encode_plus($arr);  //encode the data in json format
		
			/* If using ScriptTagProxy:  In order for the browser to process the returned
		       data, the server must wrap te data object with a call to a callback function,
		       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
		       If using HttpProxy no callback reference is to be specified */
			$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
		       
			echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
			
	}// fine try
	catch (Exception $e)
	{
		trace($e->getMessage());
		die("{failure:true}");
	}
}			
?>
