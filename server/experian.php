<?php
// Funzioni richiamate dalla pagina tabs_Experian per comporre le varie liste
require_once("userFunc.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

try {
	doMain();
} catch(Exception $e) {
	echo '{"failure":true, "error":'. $e->getMessage().'}';
}

function doMain()
{
	global $context;
	
	$fields = "*";
	switch ($_REQUEST['task'])
	{
		case "sintesi": // lista dei lotti inviati
			$query = "v_experian_sintesi";
			$order = "DataInvio DESC";
			break;
		case "generale": // lista delle pratiche esaminate (ultima occorrenza nel caso di stesso cliente interrogato più volte)
			$query = "v_experian_client";
			$order = "Nominativo";
			break;
		case "coda":  // lista delle pratiche accodate per il prossimo invio
			$query = "v_experian_queue";
			$order = "CodCliente";
			break;
		case "candidati": // lista delle pratiche candidate al prossimo invio
			$query = "v_experian_candidati";
			$order = "CodCliente";
			break;
		default: // niente echo, perché altrimenti non funziona il tasto Export
			//echo "{failure:true, task: '$task'}";
			return;
	}
	
	/* By specifying the start/limit params in ds.load 
	 * the values are passed here
	 * if using ScriptTagProxy the values will be in $_GET
	 * if using HttpProxy      the values will be in $_REQUEST (or $_REQUEST)
	 * the following two lines check either location, but might be more
	 * secure to use the appropriate one according to the Proxy being used
	*/
	 
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
		$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
		$sql = "SELECT $fields FROM $query ORDER BY ";
	
		if ($_REQUEST['groupBy']>' ') {
			if ($_REQUEST['groupBy']=="Lotto")
				$sql .= "DataFineAffido DESC,Ordine, ";
			else
				$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ", $order,";
		} 
		if ($_REQUEST['sort']>' ') 
			if ($_REQUEST['sort']=="Lotto")
				$sql .= "DataFineAffido DESC,ordine";
			else
				$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= $order;
		
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//trace($sql,false);
		$arr = getFetchArray($sql);
	}
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	
	   /* If using ScriptTagProxy:  In order for the browser to process the returned
	       data, the server must wrap te data object with a call to a callback function,
	       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
	       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}
?>
