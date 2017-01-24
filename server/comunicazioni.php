<?php
require_once("userFunc.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

doMain();

function doMain()
{
	global $context;
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	$extraCondition = $_POST['sqlExtraCondition'];
	
	$fields = "*";
	$query = "v_comunicazioni";
	$where = " WHERE CURDATE() BETWEEN DataIni AND DataFin AND ".userCondition();
	if ($context['InternoEsterno']=='E') // utente esterno (di agenzia)
	{  
		// se non può vedere che le sue
		if (!userCanDo('READ_AGENZIA')) {
			$condScad = " AND IdUtente={$context["IdUtente"]}";
		} else { // tutte quelle del suo reparto
			$condScad = " AND IdUtente NOT IN (SELECT IdUtente FROM utente WHERE IdReparto!=".$context["IdReparto"].")";
		}
	}
	else
		$condScad = "";
	
	switch ($task)
	{
		case "nonriservate":
			$where .= " AND TipoNota IN ('C','N') AND IFNULL(FlagRiservato,'N')!='Y'";
			$order = "LastUpd DESC";	
			break;
		case "riservate":
			$where .= " AND TipoNota IN ('C','N') AND FlagRiservato='Y'";
			$order = "LastUpd DESC";	
			break;
		case "nonlette":
			$where .= " AND IdNota IN (SELECT IdNota FROM nota n ".condNoteNonLette().")" ;
			$order = "LastUpd DESC";	
			break;
		case "lettirecenti": // messaggi letti di recente
			$where .= " AND IdNota IN (SELECT IdNota FROM notautente WHERE IdUtente="
			       . $context["IdUtente"]. " AND LastUpd>CURDATE()-INTERVAL 7 DAY)" ;
			$order = "LastUpd DESC";	
			break;
		case "scadenzegenerali":
			$where .= " AND DataScadenza>=CURDATE() AND IdContratto IS NULL".$condScad." $extraCondition ";
			$order = "DataScadenza ASC,LastUpd DESC";	
			break;
		case "scadenzepratiche":
			$where .= " AND DataScadenza>=CURDATE() AND IdContratto IS NOT NULL".$condScad." $extraCondition ";
			$order = "DataScadenza ASC,LastUpd DESC";	
			break;
		case "scadenzegeneraliGG":
			$where .= " AND cast(DataScadenza as date)='".$_REQUEST['ggNota']."' AND IdContratto IS NULL".$condScad;
			$order = "LastUpd DESC";	
			break;
		case "scadenzepraticheGG":
			$where .= " AND cast(DataScadenza as date)='".$_REQUEST['ggNota']."' AND IdContratto IS NOT NULL".$condScad;
			$order = "LastUpd DESC";	
			break;
		default:
			echo '{"failure":true, "task": "$task"}';
			return;
	}
	
//	$counter = getScalar("SELECT count(*) FROM $query $where");
	//	 
	//if ($counter == NULL)
	//	$counter = 0;
	//if ($counter == 0) {
	//		$arr = array();
	//} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
		$sql = "SELECT  SQL_CALC_FOUND_ROWS  $fields FROM $query $where ORDER BY ";
	
		if ($_REQUEST['groupBy']>' ') {
			$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ', ';
		} 
		if ($_REQUEST['sort']>' ') 
			$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= $order;
		
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//trace($sql);
		$arr = getFetchArray($sql);
	//}
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	$total = getScalar("SELECT FOUND_ROWS()");
	   /* If using ScriptTagProxy:  In order for the browser to process the returned
	       data, the server must wrap te data object with a call to a callback function,
	       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
	       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $total . '","results":' . $data . '})';
}
?>
