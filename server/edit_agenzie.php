<?php
require_once("common.php");


$task = ($_POST['task']) ? ($_POST['task']) : null;

switch($task){
	case "insert":
		insert();
		break;
	case "read":
		read();
		break;
	case "update":
		update();
		break;
	case "readCompagnie":
		readCompagnie();
		break;
	default:
		echo "{failure:true}";
		break;
}

function readCompagnie() {

	$sql = 'SELECT IdCompagnia, TitoloCompagnia, NomeTitolare, Indirizzo, CAP, Localita, SiglaProvincia, ' .
		'Telefono as TelefonoTitolare, Fax as FaxTitolare, EmailTitolare FROM compagnia WHERE IdTipoCompagnia=2 AND NOW() BETWEEN DataIni AND DataFin';
	$arr = getFetchArray($sql);

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
       
	echo $cb . '({"results":' . $data . '})';
}

//-----------------------------------------------------------------------
// read
// Lettura valori per ...
//-----------------------------------------------------------------------
function read() {
		
	$id = $_POST['id'];

	$sql = 'SELECT C.IdCompagnia, C.TitoloCompagnia, C.NomeTitolare, C.Indirizzo, C.CAP, C.Localita, C.SiglaProvincia, ' .
		'C.Telefono as TelefonoTitolare, C.Fax as FaxTitolare, C.EmailTitolare, R.IdReparto, ' .
		'R.CodUfficio, R.TitoloUfficio, R.NomeReferente, R.Telefono, R.Fax, R.EmailReferente, R.EmailFatturazione, R.LastUpd ' .
		'FROM compagnia C JOIN reparto R WHERE C.IdTipoCompagnia=2 AND C.IdCompagnia = R.IdCompagnia AND R.IdReparto=' . $id .
		' ORDER BY C.TitoloCompagnia,R.TitoloUfficio,R.CodUfficio';
/*
	`DataIni` date NOT NULL,
	`DataFin` date NOT NULL,
	`LastUpd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	`LastUser` varchar(20) default NULL,
*/	
	//trace($sql);
	$arr = getFetchArray($sql);
	
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
       
	echo $cb . '({"results":' . $data . '})';
	
}

//-----------------------------------------------------------------------
// insert
// Inserimento nuovo record Agenzia
//-----------------------------------------------------------------------
function insert() {
	$sql = sprintf("INSERT INTO reparto (`IdTipoReparto`,`IdCompagnia`,`CodUfficio`," .
		"`TitoloUfficio`,`NomeReferente`,`Telefono`,`Fax`,`EmailReferente`," .
		"`EmailFatturazione`,`DataIni`,`DataFin`,`LastUser`) VALUES(%s,%s,%s,%s,%s,%s,%s,%s,%s,'%s','%s','%s')",
		2, 
		quote_smart($_POST['IdCompagnia']),
		quote_smart($_POST['CodUfficio']),
		quote_smart($_POST['TitoloUfficio']),
		quote_smart($_POST['NomeReferente']),
		quote_smart($_POST['Telefono']),
		quote_smart($_POST['Fax']),
		quote_smart($_POST['EmailReferente']),
		quote_smart($_POST['EmailFatturazione']), 
		'1900-01-01',
		'9999-12-31',
		'user');

	if (execute($sql))
	{
		$newID = getInsertId();
		writeLog("APP","Gestione agenzia","Inserimento nuova agenzia, newID:".$newID,'ADD_AGENZ');
		echo "{success:true, newID:".$newID."}";	// torna la chiave del record inserito
    } else {
    	writeLog("APP","Gestione agenzia","\"".getLastError()."\"",'ADD_AGENZ');
		echo "{success:false, error:\"".getLastError()."\"}"; //if we want to trigger the false block we should redirect somewhere to get a 404 page
   	}
}

//-----------------------------------------------------------------------
// update
// Salvataggio record Agenzia
//-----------------------------------------------------------------------
function update() {
	global $connection;
	
	beginTrans();
	$datLup = ("SELECT r.LastUpd FROM reparto r where r.IdReparto='" .$_POST['IdReparto']. "' FOR UPDATE");
	$darr = getRow($datLup);
	$d = $darr["LastUpd"];
	
	if(( $_POST['LastUpd'] == $d)and($darr != 0))
	{
		/*$datLup = 'SELECT r.LastUpd FROM reparto r where r.IdReparto=' .$_POST['IdReparto']. '';
		$arr = getFetchArray($datLup);
		print_r($arr);*/
			
		$sql =  sprintf("UPDATE reparto SET CodUfficio=%s, TitoloUfficio=%s, NomeReferente=%s, " .
			"Telefono=%s, Fax=%s, EmailReferente=%s, EmailFatturazione=%s WHERE IdReparto=%s",
			quote_smart($_POST['CodUfficio']),
			quote_smart($_POST['TitoloUfficio']),
			quote_smart($_POST['NomeReferente']),
			quote_smart($_POST['Telefono']),
			quote_smart($_POST['Fax']),
			quote_smart($_POST['EmailReferente']),
			quote_smart($_POST['EmailFatturazione']),
			quote_smart($_POST['IdReparto']));
	
		// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
		// nel caso in cui non ci fosse nessuna modifica di valore) 
		if (execute($sql)) {
			// aggiorna anche la Compagnia
			writeLog("APP","Gestione agenzia","Aggiornamento agenzia, ID:".$_POST['IdReparto'],'MOD_AGENZ');
			echo updateCompagnia();
		} else {
			writeLog("APP","Gestione agenzia","\"".getLastError()."\"",'MOD_AGENZ');
			echo "{success:false,inter error:\"".getLastError()."\"}";
			rollback();
		}
	}
	else
	{
		writeLog("APP","Gestione agenzia","I dati sono stati editati da una terza parte durante l'attuale operazione di aggiornamento.",'MOD_AGENZ');
		echo "{success:false, error:\"I dati sono stati editati da una terza parte durante l'attuale operazione di aggiornamento.\"}";
		rollback();
	}
	commit();
}

function updateCompagnia() {
	
	$setClause = "";
	addSetClause($setClause,"NomeTitolare",$_POST['NomeTitolare'],"S");
	addSetClause($setClause,"Indirizzo",$_POST['Indirizzo'],"S");
	addSetClause($setClause,"CAP",$_POST['CAP'],"S");
	addSetClause($setClause,"Localita",$_POST['Localita'],"S");	
	addSetClause($setClause,"SiglaProvincia",$_POST['SiglaProvincia'],"S");
	addSetClause($setClause,"EmailTitolare",$_POST['EmailTitolare'],"S");	
	addSetClause($setClause,"Telefono",$_POST['TelefonoTitolare'],"S");
	addSetClause($setClause,"Fax",$_POST['FaxTitolare'],"S");
	
	$idC = $_POST['IdCompagnia'];
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	if (execute("UPDATE compagnia $setClause WHERE IdCompagnia=$idC")) {
		writeLog("APP","Gestione agenzia","Aggiornamento della compagnia, ID:$idC",'MOD_AGENZ_CMP');
		return "{success:true}";
	} else {
		writeLog("APP","Gestione agenzia","\"".getLastError()."\"",'MOD_AGENZ_CMP');
		return "{success:false, error:\"".getLastError()."\"}";
		rollback();
	}
}

?>
