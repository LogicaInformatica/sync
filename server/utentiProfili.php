<?php
require_once("workflowFunc.php");
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
	switch ($task)
	{
		case "readU":readU();
			break;
		case "gruppoCodice":regrup();
			break;
		case "readProfMain":readgrup();
			break;
		case "read":readP();
			break;
		case "addP":addProfile();
			break;
		case "delete":delProfile();
			break;
		case "deleteU":delProfileUser();
			break;
		case "checkP":checkFunc();
			break;
		case "checkPAzione":checkAzioniFunc();
			break;
		case "saveP":saveFuncP();
			break;
		case "invioSms":invioUsms();
			break;
		case "invioMail":invioUmail();
			break;
		case "impUser":impersona();
			break;
		default:
			trace("Parametro $task non riconosciuto",true);
			// ritorno misto per compatibilità vecchio/nuovo
			echo "{success:false, failure:true, task: '$task', error: 'Errore nei parametri passati al server: segnalalo all'amministratore del sistema}";
	}
}

////////////////////////////////////////////
//Funzione di lettura della griglia utenti
////////////////////////////////////////////
function readU()
{
	global $context;
	//$fields = "u.*, r.TitoloUfficio, su.TitoloStatoUtente";
	//$query = "(utente u left join reparto r on(u.IdReparto=r.IdReparto))left join statoutente su on(u.IdStatoUtente=su.IdStatoUtente)";
	$fields = "u.*, r.TitoloUfficio, su.TitoloStatoUtente, GROUP_CONCAT(pr.AbbrProfilo ORDER BY pr.IdProfilo ASC SEPARATOR ',') as profiliUt";
	$query = "(utente u left join reparto r on(u.IdReparto=r.IdReparto))left join statoutente su on(u.IdStatoUtente=su.IdStatoUtente)left join profiloutente pu on(u.IdUtente=pu.IdUtente)left join profilo pr on(pu.IdProfilo=pr.IdProfilo)";
	$gruppo = ($_REQUEST['group']) ? ($_REQUEST['group']) : null;
	
	switch ($gruppo)
	{
		case "TitoloUfficio":
			$order = "r.TitoloUfficio asc";
			break;
		default:
			die ("{failure:true, task: '$gruppo'}");
	}
	
	$counter = getScalar("SELECT count(distinct u.idutente) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query";
		
		if ($_REQUEST['groupBy']>' ') {
			$sql .= " group by u.idutente ORDER BY ".$order;
			if ($_REQUEST['sort']>' '){ 
				$sql .= ",".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= ",u.NomeUtente asc";
			}
		} 
		else
		{
			if ($_REQUEST['sort']>' '){ 
				$sql .= " group by u.idutente ORDER BY ".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= " group by u.idutente ORDER BY u.NomeUtente asc";
			}
		}
				
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		
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
////////////////////////////////////////////
//Funzione di lettura della griglia Profili Old
////////////////////////////////////////////
function regrup()
{
	global $context;
	$fields = "p.*,f.IdFunzione,IdGruppo,codfunzione,titolofunzione";
	$query = "profilo p";
	$where = " IdGruppo is not null and f.IdFunzione=IdGruppo";
	$order = "idprofilo,IdGruppo";
	
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$query .= " join funzione f ";
		$sql = "SELECT $fields FROM $query WHERE $where";
	
		if (isset($_REQUEST['sort'])) {
			$sql .= ' ORDER BY ' . $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		}
		else
			$sql .= " ORDER BY $order";
	
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		
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
////////////////////////////////////////////
//Funzione di lettura della griglia Profili New
////////////////////////////////////////////
function readgrup()
{
	global $context;
	$fields = "*";
	$query = "v_profili_utenti";
	$order = "TitoloProfilo";
	
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query ";
	
		if (isset($_REQUEST['sort'])) {
			$sql .= ' ORDER BY ' . $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		}
		else
			$sql .= " ORDER BY $order";
	
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
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

//////////////////////////////////////////
//Funzione di lettura del pannello profili
//////////////////////////////////////////
function readP()
{
	global $context;
	$fields = "*";
	$query = "profilo";
	
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query";
	
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		
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

//////////////////////////////
//Funzione di aggiunta profili
//////////////////////////////
function addProfile()
{
	global $context;
	$valList = "";
	$colList = "";
	addInsClause($colList,$valList,"CodProfilo",$_POST['CodProfilo'],"S");
	addInsClause($colList,$valList,"TitoloProfilo",$_POST['TitoloProfilo'],"S");
	addInsClause($colList,$valList,"DataIni","1970-01-01","S");
	addInsClause($colList,$valList,"DataFin","2999-12-31","S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	
	$sql =  "INSERT INTO profilo ($colList)  VALUES($valList)";
	
	if (execute($sql)) {
		writeLog("APP","Gestione profili","Inserimento profilo ".$_POST['TitoloProfilo'],"INS_PROF");
		echo "{success:true}";
	} else {
		writeLog("APP","Gestione profili","\"".getLastError()."\"","INS_PROF");
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

///////////////////////////////////////
//Funzione di cancellazione dei profili
///////////////////////////////////////
function delProfile()
{
	global $context;
	
	$values = explode('|', $_REQUEST['vect']);
	$num = count($values)-1;

	//Delete
	for($i=1;$i<=$num;$i++)
	{
		$sqcheck = "SELECT count(*) FROM profiloutente where idprofilo=$values[$i]";
		$mome=getScalar($sqcheck);
		//trace("profilo: ".$values[$i]." | check: ".$mome);
		if($mome>0)
		{
			//$arrUnd[$i-1] = $values[$i];
			//$arrUnd .= $values[$i].",";
			$sqlNomi = "SELECT TitoloProfilo FROM profilo where idProfilo in ($values[$i])";
			$result = getRow($sqlNomi);
			$data .= $result['TitoloProfilo'].", ";
		}else{
			
			// serve per il log
			$titoloProfilo = getscalar("SELECT TitoloProfilo FROM profilo where idProfilo = $values[$i]");
						
			//cancella le associazioni funzionali a quel profilo in profilofunzione
			$sqdelf = "DELETE FROM profilofunzione where (idProfilo=$values[$i])";
			execute($sqdelf);
			
			//cancella il profilo in tabella
			$sqdel = "DELETE FROM profilo WHERE (idprofilo=$values[$i])";
			execute($sqdel);
			
			// trace su log
			writeLog("APP","Gestione profili","Cancellazione profilo ".$titoloProfilo,"CANC_PROF");
		}
	}
	
	$sub = substr($data,0,strrpos($data,','));
	echo $sub;		
}
/////////////////////////////////////////
//Funzione di cancellazione degli utenti
/////////////////////////////////////////
function delProfileUser()
{
	global $context;
	
	$idU = $_REQUEST['id'];
	
	// serve per il log prima di cancellare l'utente
	$NomeUtente= getscalar("SELECT Userid from utente where IdUtente=$idU"); 
	beginTrans();
	if ($_REQUEST['type']=='cancel') { // Cancellazione fisica
		if (!execute("UPDATE allegato SET IdUtente=NULL WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
			}
		if (!execute("UPDATE assegnazione SET IdAgente=NULL WHERE IdAgente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE assegnazione SET IdOperatore=NULL WHERE IdOperatore=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE azionespeciale SET IdApprovatore=NULL WHERE IdApprovatore=$idU")) {
			rollback();
					echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE azionespeciale SET IdUtente=NULL WHERE IdUtente=$idU")) {
			rollback();
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		if (!execute("UPDATE contratto SET IdOperatore=NULL WHERE IdOperatore=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE contratto SET IdAgente=NULL WHERE IdAgente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE dettaglioprovvigione SET IdAgente=NULL WHERE IdAgente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE incasso SET IdUtente=NULL WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE log l JOIN utente u ON u.IdUtente=l.IdUtente"
			. " SET l.IdUtente=NULL,UseridCancellato=u.Userid WHERE l.IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		// Le scadenze dell'utente vengono cancellate
		if (!execute("DELETE FROM nota WHERE TipoNota='S' AND (IdUtente=$idU OR IdUtenteDest=$idU)")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		// Le comunicazioni dirette all'utente vengono cancellate
		if (!execute("DELETE FROM nota WHERE TipoNota='C' AND IdUtenteDest=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		// Le altre vengono staccate
		if (!execute("UPDATE nota l JOIN utente u ON u.IdUtente=l.IdUtente"
			. " SET l.IdUtente=NULL,UseridCancellato=u.Userid WHERE l.IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE nota SET IdUtenteDest=NULL WHERE IdUtenteDest=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE nota l JOIN utente u ON u.IdUtente=l.IdSuper"
			. " SET l.IdSuper=NULL,UseridCancellato=u.Userid WHERE l.IdSuper=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("DELETE FROM notautente WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("DELETE FROM profiloutente WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("DELETE FROM regolaassegnazione WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE storiainsoluto SET IdOperatore=NULL WHERE IdOperatore=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE storiainsoluto SET IdAgente=NULL WHERE IdAgente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE storiarecupero l JOIN utente u ON u.IdUtente=l.IdUtente"
			. " SET l.IdUtente=NULL,UseridCancellato=u.Userid WHERE l.IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("UPDATE storiarecupero l JOIN utente u ON u.IdUtente=l.IdSuper"
			. " SET l.IdSuper=NULL,UseridCancellato=u.Userid WHERE l.IdSuper=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		if (!execute("DELETE FROM utente WHERE IdUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		commit();
		writeLog("APP","Gestione utenti","Utente $NomeUtente cancellato","CANC_UT");
		echo "{success:true, error:\"L'utente $NomeUtente &egrave; stato cancellato\"}";
	} else { // Cancellazione logica
		
		if (!execute("UPDATE utente SET DataFin=DATE_SUB(NOW(),INTERVAL 1 DAY),IdStatoUtente=2 where idUtente=$idU")) {
			rollback();
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		if (!execute("UPDATE regolaassegnazione SET DataFin=DATE_SUB(NOW(),INTERVAL 1 DAY) where idUtente=$idU")) {
			rollback();
			echo "{success:false, error:\"".getLastError()."\"}";
		}
		commit();
		writeLog("APP","Gestione utenti","Utente $NomeUtente disattivato","CANC_UT");
		echo "{success:true, error:\"L'utente $NomeUtente &egrave; stato disattivato\"}";
	}
}
/////////////////////////////////////////////////////
//Funzione di retrive dei checkbox profili(dettaglio utente)
/////////////////////////////////////////////////////
function checkFunc()
{
	global $context;
	
	$utente = $_REQUEST['idUtente'];
	
	$sqlchk = "select idprofilo from profiloutente where idUtente =$utente";
	$result = fetchValuesArray($sqlchk);
	
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($result);  //encode the data in json format
	} else {
		$data = json_encode_plus($result);  //encode the data in json format
	}
	
	echo $data;
}

///////////////////////////////////////////////////////////////////
//Funzione di retrive dei checkbox profili(dettaglio azione workflor)
///////////////////////////////////////////////////////////////////
function checkAzioniFunc()
{
	global $context;
	
	$azione = $_REQUEST['idAzione'];
	
	$sqlchk = "select pf.idprofilo as idprofilo
			from azione a
			left join profilofunzione pf on(a.idfunzione=pf.idfunzione)
			where idazione=$azione";
	$result = fetchValuesArray($sqlchk);
	
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($result);  //encode the data in json format
	} else {
		$data = json_encode_plus($result);  //encode the data in json format
	}
	
	echo $data;
}
///////////////////////////////////////
//Funzione di salvataggio dei profili
///////////////////////////////////////
function saveFuncP()
{
	global $context;
	$Operatore = $context['CodUtente'];

	//dati per salvataggio profili
	$values = explode('|', $_REQUEST['vect']);
	$utente = ($_REQUEST['idUtente']) ? ($_REQUEST['idUtente']) : null;
	$num = count($values)-1;
	
	//dati per salvataggio generico
	$NomeUtente = isset($_REQUEST['NomeUtente'])?$_REQUEST['NomeUtente']:'';
	$NomeUtente = quote_smart(trim($_REQUEST['NomeUtente']));
	$NomeUtente = substr($NomeUtente,1,strlen($NomeUtente)-2);
	$CellulareUtente = trim($_REQUEST['CellulareUtente']);
	$Telefono = trim($_REQUEST['Telefono']);
	$EmailUtente = trim($_REQUEST['EmailUtente']);
	$CodUtente = trim($_REQUEST['CodUtente']);
	$Userid = trim($_REQUEST['Userid']);
	$IdReparto = $_REQUEST['IdRep'];
	$IdStatoUtente = $_REQUEST['IdStatoUtente'];
	$dataini = isset($_POST['DataIni'])?$_POST['DataIni']:date("Y-m-d");
	$datafin = isset($_POST['DataFin'])?ISODate($_POST['DataFin']):"9999-12-31";
	$dataini = ISODate($dataini);
	
	//controlli sulla maschera d'inserimento
	$response = saveValidation($num);
	if($response=='')
	{
		//se il profilo utente è in modifica
		if($utente!=null)
		{
			//pulizia profili
			$sqdel = "DELETE FROM profiloutente WHERE (idUtente=$utente)";
			if (execute($sqdel)){
				//altri campi da salvare
				$sqlUp="UPDATE utente SET IdStatoUtente=$IdStatoUtente,NomeUtente='$NomeUtente',DataIni='$dataini',DataFin='$datafin',
						Cellulare='$CellulareUtente',Telefono='$Telefono',Email='$EmailUtente',CodUtente='$CodUtente',
						Userid='$Userid',IdReparto=$IdReparto,LastUser='$Operatore' where IdUtente=$utente";
				if (execute($sqlUp)){
					// trace su log
					writeLog("APP","Gestione utenti","Modificato utente ".$NomeUtente,"MOD_UT");
					//trace("good update");
				}else{
					writeLog("APP","Gestione utenti","\"".getLastError()."\"","MOD_UT");
					echo "{success:false, error:\"".getLastError()."\"}";
				}
			}else{
				writeLog("APP","Gestione utenti","\"".getLastError()."\"","MOD_UT");
				echo "{success:false, error:\"".getLastError()."\"}";
			}		
		}else{
			//il profilo utente è in creazione
			$sqinsNus = "INSERT INTO utente (IdStatoUtente,CodUtente,NomeUtente,Userid,IdReparto,Cellulare,Telefono,Email,DataIni,DataFin,LastUser) 
			VALUES ($IdStatoUtente,'$CodUtente','$NomeUtente','$Userid',$IdReparto,'$CellulareUtente','$Telefono','$EmailUtente','$dataini','$datafin','$Operatore')";
			if(execute($sqinsNus))
			{
				$utente = getInsertId();
				trace("Inserito utente $NomeUtente id = $utente",false);
				// trace su log
				writeLog("APP","Gestione utenti","Inserimento utente ".$NomeUtente,"INS_UT");
			}else{
				writeLog("APP","Gestione utenti","\"".getLastError()."\"","INS_UT");
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}
		//salvataggio dei profili associati
		//insert brutale
		for($i=1;$i<=$num;$i++)
		{
			$sqinsi = "INSERT INTO profiloutente (idUtente,IdProfilo,DataIni,DataFin,LastUser) 
			                  VALUES ($utente,$values[$i],'2001-01-01','9999-12-31','$Operatore')";
			if(execute($sqinsi)){
				
				// trace su log
				$titoloProfilo= getscalar("SELECT TitoloProfilo from profilo WHERE IdProfilo = $values[$i]");
				writeLog("APP","Gestione utenti","Assegnazione profilo '$titoloProfilo' all'utente ".$NomeUtente,"INS_PROF_UT");
				//trace("good ins");
			}else{
				writeLog("APP","Gestione utenti","\"".getLastError()."\"","INS_PROF_UT");
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}
		echo "{success:true, error:\"".$num."\"}";
	}else{
		if((strlen($response)-1)==strrpos($response,',')){
			$response = substr($response,0,strrpos($response,','));
		}	
		writeLog("APP","Gestione utenti",$response,"GEST_UT");	
		echo "{success:false, error:\"".$response."\"}";
	}
}
//controlla i campi necessari ed il loro formato
function saveValidation($num)
{
	//dati per controllo
	$NomeUtente = trim($_REQUEST['NomeUtente']);
	$NomeUtente = isset($NomeUtente)?$NomeUtente:'';
	$NomeUtente = quote_smart(trim($_REQUEST['NomeUtente']));
	$NomeUtente = substr($NomeUtente,1,strlen($NomeUtente)-2);
	$CellulareUtente = trim($_REQUEST['CellulareUtente']);
	$Telefono = trim($_REQUEST['Telefono']);
	$EmailUtente = trim($_REQUEST['EmailUtente']);
	$Userid = trim($_REQUEST['Userid']);
	$Userid = isset($Userid)?$Userid:'';
	$IdReparto = trim($_REQUEST['IdRep']);
	$IdReparto = isset($IdReparto)?$IdReparto:'';
	$responso='';
	
	if($NomeUtente == '')
	{
		$responso = "Nome non specificato,";
	}
	if(!filter_var($EmailUtente, FILTER_VALIDATE_EMAIL) and $EmailUtente!='')
	{
		$responso .= "E-mail non valida,";
	}
	if($Userid == '')
	{
		$responso .= "User Id non specificato,";
	}
	if($IdReparto == '')
	{
		$responso .= "Reparto non specificato,";
	}
	if($Telefono!='')
	{
		$Telefono = preg_replace( '/[^0-9]/i', 'not', $Telefono);
		if(strstr($Telefono,'not')!=false){$responso .= "Telefono errato o non specificato,";}		
	}
	if($CellulareUtente!='')
	{
		$CellulareUtente = preg_replace( '/[^0-9]/i', 'not', $CellulareUtente);
		if(strstr($CellulareUtente,'not')!=false){$responso .= "Cellulare errato o non specificato,";}
	}	
	if($num == 0)
	{
		$responso .= "Nessun profilo selezionato";
	}
	return $responso;
}
////////////////////////////////////////////
//Funzione di invio sms della griglia utenti
////////////////////////////////////////////
function invioUsms(){
	$CellulareUtente = trim($_REQUEST['Cellulare']);
	$testo = trim($_REQUEST["nota"]);
	$ErrMsg='';
	
	if($testo != ''){
		//trace("cell: ".$CellulareUtente." | nota: ".$testo);
	    if (inviaSMS($CellulareUtente,$testo,$ErrMsg)==false){
			$NomeUtente=trim($_REQUEST["NomeUtente"]);
			writeLog("APP","Gestione utenti sms","Inviato SMS all'utente $NomeUtente, numero  $CellulareUtente testo:$testo ","SMS_UT");
			echo "{success:false,msg:\"".$ErrMsg."\"}";
		}else{
			writeLog("APP","Gestione utenti sms","Messaggio inviato","SMS_UT");
			echo '{"success":"true","msg":"Messaggio inviato"}';}
		//echo '{"success":"true","msg":"Messaggio inviato"}';//da togliere
	}else{
		writeLog("APP","Gestione utenti sms","Nessun testo","SMS_UT");
		echo '{"success":"false","msg":"Nessun testo"}';}
}
/////////////////////////////////////////////
//Funzione di invio mail della griglia utenti
/////////////////////////////////////////////
function invioUmail(){
	$oggetto = trim($_REQUEST["oggetto"]);
	$email = trim($_REQUEST["email"]);
	$testo = trim($_REQUEST["nota"]);
	if($testo != '' and $testo != '<br>' and $oggetto != ''){
		trace("mail: ".$email." | nota: ".$testo." | subj ".$oggetto);
		if (!sendMail("",$email,$oggetto,$testo,'')){
			echo '{"success":"false","msg":"Errore nell\' invio"}';
		}else{
			$NomeUtente=trim($_REQUEST["NomeUtente"]);
			writeLog("APP","Gestione utenti email","Inviata email all'utente $NomeUtente, indirizzo  $email.","EMAIL_UT");
			echo '{"success":"true","msg":"Messaggio inviato"}';}
		//echo '{"success":"true","msg":"Messaggio inviato"}';//da togliere
	}else{
		writeLog("APP","Gestione utenti email","Email incompleta","EMAIL_UT");
		echo '{"success":"false","msg":"Email incompleta"}';}
}
/////////////////////////////////////////////////////
//Funzione di impersonificazione della griglia utenti
/////////////////////////////////////////////////////
function impersona(){
	//inserisci nel contesto il master impersonificatore
	global $context;
	
	// Imposta il 'master' con l'utente corrente
	$context["master"] = $context['Userid'];

	// Imposta lo userid con quello da impersonare
	$context["Userid"] = $_REQUEST["useridSlave"];

	$_SESSION['userContext'] = $context;
	writeLog("APP","Gestione utenti - impersonificazione di {$_REQUEST["useridSlave"]} da parte di {$context["master"]}","Operazione riuscita","IMERS_UT");
	//writeLog("APP","Gestione utenti impersonificazione","Operazione riuscita","IMERS_UT");
	echo '{"success":"true", "msg":"Operazione riuscita"}';
}
?>
