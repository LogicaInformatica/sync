<?php
require_once("common.php");
set_time_limit(600); // aumenta il tempo max di cpu  

doMain();

function doMain()
{
	global $context;
	
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	$fields = "*";
	switch($task){
		case "sintesiAg":
			$query = "v_sintesi_insoluti WHERE IdAgenzia=".$_REQUEST['idA'];
			break;
		default:
			echo "{failure:true}";
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
			$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ', ';
		} 
		if ($_REQUEST['sort']>' ') 
			$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= "Agenzia,CodStatoRecupero";
		
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
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
