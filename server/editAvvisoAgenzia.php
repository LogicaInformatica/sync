<?php
require_once("userFunc.php");
require_once("workflowFunc.php");

$task = ($_POST['task']) ? ($_POST['task']) : null;
switch($task){
	case "saveStato":
		update($_POST['AvvisoAttivo'],$_POST['IdModello']);
		break;
	case "saveTxt":
		saveTxt($_POST['NomeFile'],$_POST['TestoAvviso']);
		break;
	case "read":
		read();
		break;
	default:
		//echo "{failure:true}";
		break;
}
// legge l'avviso
function read() {
	global $context;
	$arr = getFetchArray("SELECT IdModello,FileName, case when (CURDATE() BETWEEN DataIni AND DataFin) THEN 'S' ELSE 'N' END as Attivo FROM modello where TipoModello='P'");
	$TestoAvviso = file_get_contents(TEMPLATE_PATH."/".$arr[0]['FileName']);
	$arr[0]['TestoAvviso'] = str_replace ( chr(10) , '<br>', $TestoAvviso );
	$total = count($arr);
	$error = "";
	$data = json_encode_plus($arr);  //encode the data in json format

   	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
	echo '({"total":"' . $total . '","results":' . $data . $error.'})';
}

//-----------------------------------------------------------------------
// saveTxt
// Salva il contenuto del file
//-----------------------------------------------------------------------
function saveTxt($NomeFile,$TestoAvviso){
	try
	{
		$codMex='ADD_AVV_AG';
			$TestoAvviso = str_replace ( chr(10) , '<br>', $TestoAvviso );
			$TestoAvviso = str_replace("​","",$TestoAvviso); // strano carattere messo in testa al testo dall'editor
			$number = file_put_contents(TEMPLATE_PATH."/$NomeFile",$TestoAvviso);
			if(!($number>0)){
				writeLog('APP',"Gestione avviso agenzia","Errore nella scrittura del file avvisi agenzia.",$codMex);
				echo "{success:false, error:\"Errore nella scrittura del file avvisi agenzia\"}";
				die();
			}
			writeLog('APP',"Gestione avviso agenzia","Scrittura del file avvisi agenzia eseguita.",$codMex);
			echo('{success:true}');
		
	}catch (Exception $e)
	 {
//			trace("Errore durante la scrittura del file avvisi agenzia".$e->getMessage());
			setLastSerror($e->getMessage());
			writeLog('APP',"Gestione avviso agenzia","Errore nella scrittura del file avvisi agenzia.",$codMex);
			echo('{success:false,error:"Errore durante la scrittura del file avvisi agenzia"}');
	 }
}


//-----------------------------------------------------------------------
// update
// Attivazione dell'avviso (alterando la data di fine)
//-----------------------------------------------------------------------
function update($AvvisoAttivo,$IdModello) {
	global $context;
	$setClause = "";
	$codMex='MOD_AVV_AG';
	if ($AvvisoAttivo=="S"){
		// deve essere disattivato
		$date = date("Y-m-d", strtotime("-1 day"));
		addSetClause($setClause,"DataFin",$date,"S","N");
		
	}else{
		// deve essere attivato
		addSetClause($setClause,"DataFin",'9999-12-31',"S","N");
	}

	$sql =  "UPDATE modello $setClause WHERE IdModello=".$IdModello;
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	$conn = getDbConnection();
	if (execute($sql)) {
		writeLog('APP',"Gestione avviso agenzia","Modifica del file avvisi agenzia eseguito.",$codMex);
		echo "{success:true}";
	} else {
		writeLog('APP',"Gestione avviso agenzia","\"".mysqli_error($conn)."\"",$codMex);
		echo "{success:false, error:\"".mysqli_error($conn)."\"}";
	}
}

?>
