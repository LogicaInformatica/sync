<?php
require_once("common.php");
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
	$vista = ($_REQUEST['vista']) ? ($_REQUEST['vista']) : null;
	
	 try
	 {
	 	if($vista=="storiarecupero")
		{
			$query='v_log_storiarecupero';
		}
		else
		{
			if($vista=="log")
			{
				$query='v_log';
			}
			else
			{
				echo "{failure:true}";
				return;	
			}
		}
		$fields = "*";
		
		switch ($task)
		{
			case "utente":
				$order = "DataOra DESC,Evento";
				break;
			case "data":
				$order = "DataOra DESC";
				break;
			default:
				die ("{failure:true, task: '$task'}");
		}
		
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
				$sql .= $order;
			
			if ($start!='' || $end!='') {
		    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
			
			$arr = getFetchArray($sql);
			//trace($arr);
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
	catch (Exception $e)
	{
		trace("listaLog.php ".$e->getMessage());
		echo  json_encode_plus(array("success"=>false,"msg"=>$e->getMessage()));	
	}
} 
?>
