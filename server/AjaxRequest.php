<?php
//----------------------------------------------------------------
// Pagina richiamata per richieste Ajax usate nella composizione
// javascript
//----------------------------------------------------------------
require_once("userFunc.php");
require_once("workflowFunc.php");

try {
	doMain();
}
catch (Exception $e)
{
	trace($e->getMessage());
}

function doMain() {
	global $context,$exportingToExcel;
	
	$task = ($_REQUEST['task']) ? $_REQUEST['task'] : null;
	if (!$exportingToExcel)
		$exportingToExcel = $_GET["excel"]=='Y'; // passato nel modo nuovo
//	trace("Entrato AjaxRequest.php con task=$task exportingToExcel=$exportingToExcel",false);
		
	//trace("task=$task",FALSE);
	switch($task){
		case "session": // chiamata per il mantenimento in vita della sessione e per la preparazione
			            // del messaggio di popup: può restituire qualsiasi istruzione (o serie di istruzioni)
			            // javascript che vengono eseguite dal chiamante (vedi main.php). Viene usata per
			            // la comparsa a tempo del messaggio di avviso alle agenzie

			// 2016-06-19: se per qualche motivo la sessione php è morta, fa in modo che l'utente rifaccia login
			if (!$context['Userid']) {
				die('DCS.emetteMessaggioSessioneScaduta()');
			} else { // altrimenti chiama la funzione di userFunc.php che provvede all'eventuale popup di avviso
				$redisplay = $_REQUEST['redisplay']=='Y';
				die(displayPopupWarning($redisplay));
			}
			break;
		// 17/1072016: quando si chiede la lista allegati di una pratica, prima viene eseguito l'aggiornamento della tabella con i dati del nuovo documentale
		// poi l'elaborazione prosegue con la normale "read"
		case "readAllegati":
			aggiornaAllegati($_REQUEST['IdContratto']);
			/* FALL THRU */
		case "read":   // esegue comando SQL di tipo SELECT
			$query = trim(stripslashes($_REQUEST['sql']));
			if (strtoupper(substr($query,0,6))=="SELECT")
			{
				$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '';
				$end =   isset($_REQUEST['limit']) ? $_REQUEST['limit'] : '';
				$groupBy = isset($_REQUEST['groupBy']) ? $_REQUEST['groupBy'] : '';
				$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
				
				$hasLimit = ($start!='' || $end!='');
				
				if ($hasLimit || $groupBy!='' || $sort!='') {
					$sql = "SELECT * FROM ($query) cq";
					if ($groupBy>' ') {
						$sql .= ' ORDER BY ' . $groupBy . ' ' . $_POST['groupDir'];
					} 
					if ($sort>' ') {
						if ($groupBy>' ')
							$sql .= ', ';
						else
							$sql .= ' ORDER BY ';
						$sql .= $sort . ' ' . $_POST['dir'];
					}
						
					if ($hasLimit) {
		    			$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
					}
					
					$arr = getFetchArray($sql,MYSQL_ASSOC,$error);
				} else {
					$arr = getFetchArray($query,MYSQL_ASSOC,$error);
				}
				if ($error>"")
					$error = ",\"error\": ".json_encode_plus(getLastError()); // potrebbe esserci un errore
				else
					$error = "";
					
				if ($hasLimit) {
					$total = getScalar("SELECT count(*) FROM ($query) cq");
				} else {
					$total = count($arr);
				}
				$data = json_encode_plus($arr);  // funzione modificata per gestire letture no compatibili con UTF-8 
				break;
			}
			// Se non � una SELECT
		case "exec":   // esegue comando SQL generico
			$query = trim(stripslashes($_REQUEST['sql']));
			if (execute($query)) {
				$data = "[]";
				$total = getAffectedRows();
				$error = "";
			} else {
				$data = "[]";
				$error = ",\"error\": ".json_encode_plus(getLastError()); // potrebbe esserci un errore
				$total = 0;
			}
			break;
		case "getApprovers": // approvatori di un contratto dato
			//$toState = $_REQUEST["to"];  // sigla stato di arrivo
			//$arr = getApprovers($_REQUEST["id"],$toState); // ottiene array degli approvatori
			$idContratti = $_REQUEST['idcontratti']; 
			$idContratti = json_decode(stripslashes($idContratti)); 
			$fromState = $_REQUEST["from"];  // id statoAzione 
			$arr = getApproversAtStep($idContratti,$fromState); // ottiene array degli approvatori
			$data = json_encode_plus($arr);  //encode the data in json format
			$total = count($arr);
			break;
		case "getFiscalYears": // anni fiscali sulla base del contenuto della tabella provvigione
			$row = getRow("select DATE_FORMAT(MIN(dataFin),'%Y%m') as first, DATE_FORMAT(MAX(dataFin),'%Y%m') as last from provvigione WHERE IdReparto>0");
			if ($row) {
				$FY_min = substr($row['first'],0,4);
				$m = substr($row['first'],4)+0;
				$lastFyM = getSysParm("LAST_FY_MONTH","3");
				if ($m>$lastFyM) $FY_min++;
				
				$FY_max = substr($row['last'],0,4);
				$m = substr($row['last'],4)+0;
				if ($m>$lastFyM) $FY_max++;
				$arr = array();
				for ($i=$FY_min; $i<=$FY_max; $i++)
					$arr[] = array($i); // lo store deve essere un array di arrays (righe e campi)
				$data = json_encode_plus($arr);  //encode the data in json format
				die($data);
	//			$total = $FY_max - $FY_min + 1;
			}
			break;
		default:
			$total = 0;
			$data = "";
			break;
	}
	echo '({"total":"' . $total . '","results":' . $data . $error.'})';	//.', "ora":"'.gmdate('D, d M Y H:i:s').'"})';
}

/**
 * aggiornaAllegati Legge dal documentale la lista documenti e inserisce/aggiorna le corrispondenti righe della tabella allegato
 * @param {Number} IdContratto id del contratto in esame
 * @return {Boolean} False se la lettura non è andata a buon fine oppure non sono stati trovati allegati sul documentale
 */
function aggiornaAllegati($IdContratto) {
	if (!(DMS_API_LIST_URL>'')) return; // non è definita interfaccia con il DMS
	list($cod,$prefix) = getRow("SELECT SUBSTR(CodContratto,3),SUBSTR(CodContratto,1,2) FROM contratto WHERE IdContratto=$IdContratto",MYSQLI_NUM);
	if ($prefix=='LO') $prefix='CO';
	
	$url = sprintf(DMS_API_LIST_URL,$cod,$prefix);
	trace("Legge lista documenti dal Documentale web: $url",false);
	
	$headers =  array('Accept: application/json',
			          'Content-Type: application/json',
					  'Authorization: '.DMS_API_KEY);
	
	$json = doCurl($url,null,$headers);
	if (!$json) {
		trace("Nessuna risposta dal Documentale web: $url",false);
		return false;
	} else {
		$list = json_decode($json,true);
		if (!$list) {
			trace("Risposta imprevista dal Documentale web: $json",false);
			return false;
		} else {
			if (count($list['errors'])>0) {
				foreach ($list['$errors'] as $error) {
					trace($error['Description'],false);
				}
				return false;
			}
			trace("Risposta dal documentale: $json",false);
			// Cancella le righe nella tabella allegato provenienti dal DMS
			beginTrans();
			if (!execute("DELETE FROM allegato WHERE UrlAllegato LIKE '".substr(DMS_API_GET_URL,0,30)."%'")) {
				rollback();
			}
			$list = $list['data'];
			foreach ($list as $name=>$item) {
				foreach ($item as $doc) {  // c'è una doppia struttura, ma sembra sempre con un solo documento dentro
					// Compone link per il download
					$link = sprintf( DMS_API_GET_URL, $doc['document_id'], $doc['token']);
 
					$valList = $colList = ""; 
					$valList = ""; // inizializza lista valori
					addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
					addInsClause($colList,$valList,"TitoloAllegato", $name,"S");
					addInsClause($colList,$valList,"UrlAllegato",$link,"S");
					addInsClause($colList,$valList,"LastUser","system","S");
					addInsClause($colList,$valList,"LastUpd",$doc['last_modify'],"D");
					addInsClause($colList,$valList,"IdTipoAllegato",2,"N"); // documento generico 
					
					if (!execute("INSERT INTO allegato ($colList) VALUES ($valList)")) {
						rollback();
						return false;
					}
				}
			}
			commit();
			return count($list)>0;
		}
	}
}
?>
