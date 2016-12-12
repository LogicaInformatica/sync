<?php
require_once("common.php");
require_once("batch/estrattoSpeseRecupero.php");
/* Legge o scrive un singolo parametro di sistema */
switch($_REQUEST['task']) {
	case "read":
		read($_REQUEST['cod']);
		break;
	case "write":
		write(); //$_REQUEST['IdParametro'],$_REQUEST['CodParametro'],$_REQUEST["ValoreParametro"]);
		if ($_REQUEST['report']=='yes') { // richiesta produzione report spese di recupero
			estrattoSpeseRecupero();
		}
		break;
	default:
		//echo "{failure:true}";
		break;
}
//-------------------------------------------------------------
// read
// Legge il valore di un parametro di sistema di codice dato
//-------------------------------------------------------------
function read($CodParametro) 
{
	$nomi = split(",",$CodParametro);
	if (count($nomi)==1)
		$rows = getFetchArray("SELECT * FROM parametrosistema WHERE CodParametro='$CodParametro'");
	else
	{
		$rows = getFetchArray("SELECT * FROM parametrosistema WHERE CodParametro IN ('".join("','",$nomi)."')");
		$rows[0]["IdParametro2"] = $rows[1]["IdParametro"];
		$rows[0]["CodParametro2"] = $rows[1]["CodParametro"];
		$rows[0]["TitoloParametro2"] = $rows[1]["TitoloParametro"];
		$rows[0]["ValoreParametro2"] = $rows[1]["ValoreParametro"];
	}
	if (!is_array($rows))
	{
		$res = "null";
		$cnt = 0;
	}
	else
	{
		$res = json_encode_plus($rows);	
		$cnt = count($rows);
	}
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
    echo $cb . "({\"total\":$cnt,\"results\":$res})";   
}

//-------------------------------------------------------------
// write
// Aggiorna il valore di uno o due parametri di sistema 
//-------------------------------------------------------------
function write()
{
	if (writeParametro($_REQUEST['IdParametro'],$_REQUEST['CodParametro'],$_REQUEST["ValoreParametro"]))
	{
		if ($_REQUEST['IdParametro2']>"0")
		{
			if (writeParametro($_REQUEST['IdParametro2'],$_REQUEST['CodParametro2'],$_REQUEST["ValoreParametro2"]))
				echo('{success:true}');
		}
		else	
			echo('{success:true}');
	}
}
//-------------------------------------------------------------
// writeParametro
// Aggiorna il valore di un parametro di sistema 
//-------------------------------------------------------------
function writeParametro($id,$codice,$valore)
{
	try
	{
		$codMex = "WRT_SYSPARAM";
		if (substr($codice,0,4)=="DATA") // è un campo data
			$valore = "'".ISODate($valore)."'";  // aggiusta in formato standard
		else
			$valore = quote_smart($valore);
		if (!execute("UPDATE parametrosistema SET ValoreParametro=$valore WHERE IdParametro=$id"))
		{
			$err = getlastError();
			writeLog('APP',"Gestione parametro di sistema",$err,$codMex);
			echo "{success:false, error:\"$err\"}";
			return FALSE;
		}
		else
		{
			writeLog('APP',"Gestione parametro di sistema","Scrittura parametro di valore '$valore' eseguito.",$codMex);
			return TRUE;
		}
	}
	catch (Exception $e)
	{
		$err = $e->getMessage();
		setLastSerror($err);
		$err1 = getlastError();
		writeLog('APP',"Gestione parametro di sistema",$err1,$codMex);
		echo('{success:false,error:\"$err\"}');
		return FALSE;
	}
}
?>
