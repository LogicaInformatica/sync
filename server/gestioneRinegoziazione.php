<?php
require_once("userFunc.php");

$task = ($_POST['task']) ? ($_POST['task']) : null;

switch($task){
	case "insertPratiche":
		insertPraticheRinegoziazione();
		break;
	default:
		echo "{failure:true}";
		break;
}

//------------------------------------------------------------------------------//
// insertPraticheRinegoziazione                                                 // 
// Inserisce le pratiche selezionate tra quelle in proposta di rinegoziazione   //
//------------------------------------------------------------------------------//
function insertPraticheRinegoziazione()
{
	try
	{
		$numeroPratiche = json_decode($_POST['pratiche']);
		beginTrans(); 
    	for ($i=0; $i<count($numeroPratiche) ; $i++){
			$numPratica=$numeroPratiche[$i];
			$sql= "update contratto set IdStatoRinegoziazione=1 where CodContratto='".$numPratica."'";
			if(!execute($sql)) 
			{
			  echo "{success:false, error:\"".getLastError()."\"}";
			  rollback();
			}	
		}
		commit();
		echo "{'success':true}";
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}