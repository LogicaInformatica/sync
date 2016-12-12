<?php
//
// Esegue la lettura dei dati per tutte le liste di pratiche
//
require_once("userFunc.php");
require_once("customFunc.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

try {
	doMain();
}
catch (Exception $e)
{
	trace($e->getMessage());
}

function doMain()
{
	global $context,$exportingToExcel;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	$idC = ($_REQUEST['idc']) ? ($_REQUEST['idc']) : null;
	$version = ($_REQUEST['schema']) ? ($_REQUEST['version']) : null;

	$idUtente = $context["IdUtente"];
	$schema = $_REQUEST['schema'];
	switch($task){
		case "read":
			// dal 20/10/2011 anche gli esterni vedono lo stesso partitario degli interni
			// per distinguere però se il js chiamante è quello vecchio (cache), riconosce se c'è il nuovo 
			// parametro version
			if ($context['InternoEsterno']=='E' && $version!='new') // utente esterno (di agenzia) e vecchia pagina js
			{
				$sql = "SELECT IdContratto,NumRata,MAX(TitoloTipoInsoluto) AS TitoloTipoInsoluto,"
					."MAX(DataScadenza) AS DataScadenza,MAX(DataPagamento) AS DataPagamento,"
                   ."MAX(IFNULL(CausalePagamento,' ')) AS CausalePagamento,"
					."MAX(Rata) AS Rata,SUM(Debito) AS Debito"
					." FROM v_partite_semplici where IdContratto=$idC"
					." GROUP BY NumRata ORDER BY NumRata";
			}else{
				$sql = "SELECT * FROM $schema.v_partite where IdContratto=$idC";
			}
			// Ottimizzazione del 12/10/2011
			break;
		
		default:
			return;
	}

	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);  //encode the data in json format

	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
	echo $cb . '({"total":"' . count($arr) . '","results":' . $data . '})';
}
