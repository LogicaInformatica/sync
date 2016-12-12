<?php
//
// Legge le liste di sintesi
// NB: non usa i parametri START e LIMIT (la pagina chiamante deve avere pagesize=0)
//     In questo modo si è risolto un problema di prestazioni
//
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
	set_time_limit(600); // aumenta il tempo max di cpu  
	
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	$fields = "*";
	switch ($task)
	{
		case "sintesiPerStato":
			$query = "v_sintesi_per_stato";
			$order = "StatoRecupero";
			break;
		case "sintesiPerAgenzia":
			$query = "v_sintesi_per_agenzia";
			$order = "Agenzia";
			break;
		case "sintesiPerOperatore":
			$query = "v_sintesi_per_operatore";
			$order = "NomeUtente";
			break;
		case "sintesiPerClasse":
			$query = "v_sintesi_per_classe";
			$order = "Classe";
			break;
		case "sintesiPerProdotto":
			$query = "v_sintesi_per_prodotto";
			$order = "Famiglia,Prodotto";
			break;
		case "sintesiPerLotto":
			$query = "v_sintesi_per_agenzia_e_lotto";
			$order = "DataFineAffido,Agenzia";
			break;
		case "sintesiPerAgenzia2":
			$query = "v_sintesi_per_agenzia_e_lotto";
			$order = "Agenzia,DataFineAffido";
			break;		
		case "sintesiPerAgente":
			$query = "v_sintesi_agenzia WHERE IdAgenzia=".$context["IdReparto"];
			
			/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilità affidi */ 
			//$dataMassima = $context["sysparms"]["DATA_ULT_VIS"]; 
			//if ($dataMassima=="") $dataMassima = '9999-12-31';
			//$query .= " AND DataInizioAffido<='$dataMassima'";
			//$query .= condData();
			$order = "Agente";
			break;
		case "sintesiStoricaPerAgente":
			$query = "v_sintesi_agenzia_storica WHERE IdAgenzia=".$context["IdReparto"];
			
			/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilità affidi */ 
			//$dataMassima = $context["sysparms"]["DATA_ULT_VIS"]; 
			//if ($dataMassima=="") $dataMassima = '9999-12-31';
			//$query .= " AND DataInizioAffido<='$dataMassima'";
			//$query .= condData();
					
			$order = "Agente";
			break;
		case "sintesiVistaDaAgente":
			$query = "v_sintesi_agente WHERE IdAgente=".$context["IdUtente"];
			
			/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilità affidi */ 
			//$dataMassima = $context["sysparms"]["DATA_ULT_VIS"]; 
			//if ($dataMassima=="") $dataMassima = '9999-12-31';
			//$query .= " AND DataInizioAffido<='$dataMassima'";
			//$query .= condData();
					
			$order = "DataFineAffido";
			break;
		case "sintesiLavInterna":
			$query = "v_sintesi_lavorazioni_interne v where true ";
			$order = "id asc";
			break;
		default:
			echo "{failure:true, task: '$task'}";
			return;
	}
	
	/* 
	 * Modificato per evitare la select count(*), considerando che è una lista non paginata
	*/
	 
	//$counter = getScalar("SELECT count(*) FROM $query");
	//if ($counter == NULL)
	//	$counter = 0;
	//if ($counter == 0) {
	//		$arr = array();
	//} else {
	 
	//	$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
	//	$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
		$sql = "SELECT $fields FROM $query ORDER BY ";
	
		if ($_REQUEST['groupBy']>' ') {
			if ($_REQUEST['groupBy']=="Lotto")
				$sql .= "DataFineAffido ". $_REQUEST['groupDir'] . ', ';
			else
				$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ', ';
		} 
		if ($_REQUEST['sort']>' ') 
			$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= $order;
		
	//	if ($start!='' || $end!='') {
	//   	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
	//	}
		
		$arr = getFetchArray($sql);
		$counter = count($arr);
	//}
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
//---------------------------------------------------------------------------------------
// crea una clausola per escludere le pratiche in affido oltre la data max di visibilità
// obsoleto, nelle viste di sintesi interessate non si può selezionare sulla base della
// data massima visibilità (a meno di non rendere più complicata la view)
//---------------------------------------------------------------------------------------
function condData()
{
	$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
	if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
	$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
	if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
	return " AND IdContratto IN (SELECT IdContratto FROM contratto WHERE
	         DataInizioAffido<='$dataMassima1' AND stato NOT IN ('STR1','STR2','LEG')
	         OR DataInizioAffido<='$dataMassima2' AND stato IN ('STR1','STR2','LEG'))";
}
?>
