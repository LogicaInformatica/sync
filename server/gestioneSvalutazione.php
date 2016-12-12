<?php
//
// Operazioni sulla tabella StoricoSvalutazione
//
require_once("userFunc.php");
doMain();

function doMain()
{
	global $context;

	$task =  $_REQUEST['task'];
	$fy   =  $_REQUEST['fy']; // fiscal year
	
	switch ($task)
	{
		case "archive":
		 	archive($fy);
		  	break;
		case "read":
		  	read($fy);
		  	break;
		case "list":
		  	listYears();
		  	break;
	}
}

//------------------------------------------------------------------------
// Archivia le svalutazioni per l'anno fiscale corrente o appena terminato
//------------------------------------------------------------------------
function archive($fy)
{
	global $context;

	$username = $context['Userid'];
	
	beginTrans();
	if (rowExistsInTable("storicosvalutazione","YEAR(DataStorico)=$fy"))
	{ // anno fiscale già archiviato, cancella e reinserisce
		$isnew = "false";
		if (!execute("DELETE FROM storicosvalutazione WHERE YEAR(DataStorico)=$fy"))
		{
			rollback();
			die("{success:false, error:\"".getLastError()."\"}");
		}
	}
	else
	{
		$isnew = "true";
	}
	$sql = "INSERT INTO storicosvalutazione (IdContratto,DataStorico,PercSvalutazione,ImpDebito,LastUser,LastUpd)"
		 . " SELECT IdContratto,CONCAT(YEAR(CURDATE())+IF(MONTH(CURDATE())>4,1,0),'-03-31'),PercSvalutazione,ImpInsoluto,"
		 . "'$username',NOW()"
		 . " from contratto WHERE PercSvalutazione>0 AND IdStatoRecupero NOT IN (79,84)";
	if (execute($sql))
	{
		commit();
	    echo "{success:true, isnew: $isnew, error:\"Archiviazione eseguita.\"}";
	} 
	else 
	{
		rollback();
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}
//------------------------------------------------------------------------
// Legge gli anni  fiscali archiviati
//------------------------------------------------------------------------
function listYears()
{
	$sql = "SELECT DISTINCT YEAR(DataStorico) FROM storicosvalutazione";
	$arr = fetchValuesArray($sql);
	if (getLastError()>"")
		echo "{success:false, error:\"".getLastError()."\"}";
	else
	{
		$resp["success"] = true;
		$resp["error"]   = "";
		$resp["years"]   = $arr;
		echo json_encode_plus($resp);	
	}
}
//------------------------------------------------------------------------
// Legge le pratiche archiviate per uno specifico fiscal year
//------------------------------------------------------------------------
function read($fy)
{
	$counter = getScalar("SELECT count(*) FROM v_storico_svalutazione WHERE Anno=$fy");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) 
		$arr = array();
	else 
	{
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		$sql = "SELECT * FROM v_storico_svalutazione WHERE Anno=$fy ORDER BY ";

		if ($_REQUEST['groupBy']>' ') 
			$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'];
	 	else if ($_REQUEST['sort']>' ') 
			$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= "CodContratto";
	
		if ($start!='' || $end!='')  
    		$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
	
		$arr = getFetchArray($sql);
	}
	
	$data = json_encode_plus($arr);  //encode the data in json format
	echo '({"total":"' . $counter . '","results":' . $data . '})';
}
?>