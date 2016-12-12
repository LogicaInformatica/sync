<?php
require_once("workflowFunc.php");
require_once("userFunc.php");

doMain();

function doMain()
{
	global $context;
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch ($task)
	{
		case "saveProc":addProcedura();
			break;
		case "readList":readLP();
			break;
		case "delete":delPrcedura();
			break;
		case "deleteAzione":delAzionePrcedura();
			break;
		case "deleteAutomatismoAz":delAutomatismiAzPrcedura();
			break;
		case "deleteStatiProc":delStatiPrcedura();
			break;
		case "linkAutomatismoAz":linkAutomatismiAzPrcedura();
			break;
		case "readPMainGrid":readProcGrid();
			break;
		case "readAutListGrid":readAutListGrid();
			break;
		case "readAzProcGrid":readAzGrid();
			break;
		case "readStatiProcGrid":readStGrid();
			break;
		case "readAutAzProcGrid":readAutAzGrid();
			break;
		case "readAzWKF":readAzWKF();
			break;
		case "readAutAzWKF":readAutAzWKF();
			break;
		case "saveAzProc":addAzioneProcedura();
			break;
		case "saveStatProc":addStatoProcedura();
			break;
		case "saveAutoAzProc":addAutomatismoAzProcedura();
			break;
		case "readModPrec":readModPrecompilato();
			break;
		case "saveModAndLink":saveModAndLink();
			break;
		case "updatePropostaDBT":updatePropostaDBT();
		    break;	
		default: 
			echo "{failure:true, task: '$task'}";
	}
}
////////////////////////////////////////////////////////
//Funzione di lettura della Lista procedure cancellabili
////////////////////////////////////////////////////////
function readLP()
{
	global $context;
	$fields = "*";
	$query = "procedura";
	
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

////////////////////////////////////////////////////
//Funzione di lettura della griglia delle procedure 
////////////////////////////////////////////////////
function readProcGrid()
{
	global $context;
	$fields = "*";
	$query = "v_procedure_workflow";
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

//////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia/lista degli automatismi da associare 
//////////////////////////////////////////////////////////////////////////
function readAutListGrid()
{
	global $context;
	$fields = "*";
	$query =" automatismo a right join v_automatismi_tipi v on(a.tipoautomatismo=v.tipoautomatismo)";
	$where =" where IdAutomatismo not in (select IdAutomatismo from azioneautomatica where idazione = ".$_POST['idAzione'].")";
	$ordine = "v.tipoautomatismo asc";
	$counter = getScalar("SELECT count(*) FROM $query $where");
	
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query $where ORDER BY ";
		
		if ($_POST['groupBy']>' ') {
					$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
			} 
			if ($_POST['sort']>' ') 
					$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
			else
				$sql .= $ordine;
				
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

/////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle azioni collegate alla procedura selezionata 
/////////////////////////////////////////////////////////////////////////////////////
function readAzGrid()
{
	global $context;
	isset($_POST['idProc'])?$_POST['idProc']:'';
	$fields = "*";
	$from = "v_azione_procedura";
	$where = "v.IdProcedura=".$_POST['idProc'];
	$counter = getScalar("SELECT count(*) FROM $from v where $where");
	
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from v where $where";
		
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

/////////////////////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia degli stati collegati creati ad Hoc per la procedura selezionata 
/////////////////////////////////////////////////////////////////////////////////////////////////////
function readStGrid()
{
	global $context;
	isset($_POST['idProc'])?$_POST['idProc']:'';
	isset($_POST['idS'])?$_POST['idS']:'';
	isset($_POST['fc'])?$_POST['fc']:0;
	isset($_POST['link'])?$_POST['link']:1;
	//trace("fc ".$_POST['fc']);
	if($_POST['fc']=='')
		$_POST['fc']=0;
	$flagChangeStrings=false;
	if($_POST['fc']==1){//===true
		//si sta chiamando la funzione dalla griglia degli stati della procedura e se ne sta creando uno
		//quindi non si opera nulla
	}else{
		//si sta chiamando la funzione o dalla griglia e PER la visualizzazione della griglia stessa o
		//dal dettaglio dello stato in editing
		$postCondition='';
		$linkCondition='';
		if($_POST['idS']!='' && $_POST['idS']!=0){
			//si chiama dall'editing e si specifica quale stato si sta editando
			$postCondition = 'and sap.IdStatoRecuperoSuccessivo='.$_POST['idS'];
		}elseif($_POST['link']==1){
			//si sta facendo un link
			$linkCondition = '';
			$flagChangeStrings=true;
		}else{
			//si sta leggendo la griglia degli stati
			$linkCondition = "apr.IdProcedura=".$_POST['idProc']." and ";
		}
		
		if($flagChangeStrings){
			$fields="distinct IdStatorecupero as IdSRec, TitoloStatoRecupero as TitoloSRec, AbbrStatoRecupero as Abbr";
			$from="statorecupero";
			$where="CodStatoRecupero like 'WRK%'";
		}else{
			$fields = "distinct sap.IdStatoRecuperoSuccessivo as IdSRec,sr.TitoloStatoRecupero as TitoloSRec,sr.AbbrStatoRecupero as Abbr";
			$from = "azioneprocedura apr 
					left join statoazione sap on(apr.IdAzione=sap.IdAzione) 
					left join statorecupero sr on(sap.IdStatoRecuperoSuccessivo=sr.IdStatoRecupero)";
			$where = "$linkCondition
					sap.IdStatoRecuperoSuccessivo is not null
					and sr.CodStatoRecupero like 'WRK%' $postCondition";
		}
		$counter = getScalar("SELECT count(*) FROM $from where $where");
	}
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from where $where";
		//trace("sqlcmbStati $sql");
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

	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia degli automatismi collegati all'azione selezionata 
/////////////////////////////////////////////////////////////////////////////////////
function readAutAzGrid()
{
	global $context;
	$fields = "aut.*,m.titolomodello as TitoloModello,
				case 	when aut.TipoAutomatismo='email' then 'Email di notifica' 
						when aut.TipoAutomatismo='emailComp' then 'Email di risposta' 
						else aut.TipoAutomatismo end as TipoAut,
				case 	when aut.Destinatari='*APPROVER' then 'Approvatori' 
						when aut.Destinatari='*DESTINATARIRIF' then 'Destinatari di riferimento' 
            			when aut.Destinatari='*AUTHOR' then 'Autori' end as DestAut";
	$from = "azioneautomatica aa 
			left join automatismo aut on(aut.idautomatismo=aa.idautomatismo)
			left join modello m on(m.idmodello=aut.idmodello)";
	$where = "aa.idazione=".$_POST['idAzione'];
	$counter = getScalar("SELECT count(*) FROM $from where $where");
	
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from where $where";
		
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
///////////////////////////////////////
//Funzione di aggiunta/editing procedura
///////////////////////////////////////
function addProcedura()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$alfa = Array();
	$sql='';
	for($i=0;$i<26;$i++){
		$alfa[]=strtoupper(chr(ord('a')+$i));
	}

	isset($_POST['IdProcedura'])?$_POST['IdProcedura']:'';
	
	beginTrans();
	if($_POST['IdProcedura']!='')//giusta!
	{//editing
		$codiceNewProc=$_POST['CodProcedura'];
		addSetClause($setClause,"TitoloProcedura",$_POST['TitoloProcedura'],"S");
		addSetClause($setClause,"DataIni","2001-01-01","S");
		if($_POST['Attiva']){
			//attiva
			addSetClause($setClause,"DataFin","9999-12-31","S");
		}else{
			//inattiva
			addSetClause($setClause,"DataFin",date("Y-m-d",mktime(0, 0, 0, 01, 01, 2001)),"S");
		}
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		$sql = "UPDATE procedura $setClause WHERE IdProcedura=".$_POST['IdProcedura'];
		$mex="Editing procedura ".$_POST['TitoloProcedura'];
		$codMex="MOD_PROC";
	}else{
	//insert
		$indAlfa=0;
		$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], trim($_POST['TitoloProcedura'])));
		$TitoloP=preg_replace("/[^[:alpha:]]/",'',$TitoloP);
		$arrExpl=explode(' ',$_POST['TitoloProcedura']);
		$numW=count($arrExpl);
		$codiceNewProc= 'WF_'.substr($TitoloP,0,3);
		$flagGoodCod=false;
		$index=0;
		$indAlfa++;
		$lungS = strlen($TitoloP);
		do
		{
			$res=getScalar("select count(*) from procedura where CodProcedura ='$codiceNewProc'");
			if($res==0){
				$flagGoodCod=true;
			}else{
				$index++;
				if($index<=($lungS-3)){
					$codiceNewProc= 'WF_'.substr($TitoloP,$index,3);
				}else{
					if($numW!=1)
					{
						$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], $_POST['TitoloProcedura']));
						$indAlfa++;
						$index=0;
						$codiceNewProc= 'WF_'.substr($TitoloP,$index,3);
					}else{
						$indAlfa=27;
					}
				}	
			}
		}while((!$flagGoodCod)&&($indAlfa<=26));//buono!
		
		if($flagGoodCod){
			//codice creato
			addInsClause($colList,$valList,"CodProcedura","$codiceNewProc","S");
			addInsClause($colList,$valList,"TitoloProcedura",$_POST['TitoloProcedura'],"S");
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			if($_POST['Attiva']){
				//attiva
				addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			}else{
				//inattiva
				addInsClause($colList,$valList,"DataFin",date("Y-m-d",mktime(0, 0, 0, 01, 01, 2001)),"S");
			}
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			
			$sql =  "INSERT INTO procedura ($colList)  VALUES($valList)";
			$mex="Inserimento procedura ".$_POST['TitoloProcedura'];
			$codMex="ADD_PROC";
		}else{
			//codice impossibile da creare, abortisce l'inserimento
			rollback();
			$codMex="ADD_PROC";
			writeLog("APP","Gestione procedure","Impossibile generare un codice adatto per la procedura, prego cambiarne il nome.",$codMex);
			echo "{success:false, error:\"Impossibile generare un codice adatto per la procedura, prego cambiarne il nome.\"}";
			die();
		}
		
	}
	
	if (execute($sql)) {
		//$Nid=getInsertId();
		//trace("nid $Nid");
		if($codMex=='ADD_PROC'){
			//creazione funzione gruppo
			$codF="WF_".substr($codiceNewProc,3);
			$titF=$_POST['TitoloProcedura'];
			$sqlInFunc =  "INSERT INTO funzione (CodFunzione,TitoloFunzione,LastUser)  
							VALUES('$codF','Procedura ".strtolower($titF)."','".$context['Userid']."')";
			if (execute($sqlInFunc)){
				$Nid=getInsertId();
				$sqlUpFunc = "UPDATE funzione SET IdGruppo=$Nid WHERE IdFunzione=$Nid";
				if (execute($sqlUpFunc)){
					//creazione azione associata
					$valList = "";
					$colList = "";
					addInsClause($colList,$valList,"IdFunzione",$Nid,"N");
					addInsClause($colList,$valList,"CodAzione","$codF","S");
					addInsClause($colList,$valList,"TitoloAzione","$titF","S");
					addInsClause($colList,$valList,"DataIni","2001-01-01","S");
					addInsClause($colList,$valList,"DataFin","9999-12-31","S");
					addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
					addInsClause($colList,$valList,"FlagMultipla","Y","S");
					$sqlInAz =  "INSERT INTO azione ($colList)  VALUES($valList)";
					if (execute($sqlInAz)) {
						$NidAz=getInsertId();
						//assegnazione a profilo admin
						$sqlProfFun="INSERT INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser)  
							VALUES(1,$Nid,'2001-01-01','9999-12-31','".$context['Userid']."')";
						if (execute($sqlProfFun)) {
							//assegnazione dell'azione nuova al tipoazione
							$valList = "";
							$colList = "";
							addInsClause($colList,$valList,"IdAzione",$NidAz,"N");
							addInsClause($colList,$valList,"IdTipoAzione",9,"N");
							addInsClause($colList,$valList,"DataIni","2001-01-01","S");
							addInsClause($colList,$valList,"DataFin","9999-12-31","S");
							addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
							$sqlInTa =  "INSERT INTO azionetipoazione ($colList)  VALUES($valList)";
							if (execute($sqlInTa)) {
								commit();
								writeLog("APP","Gestione procedure",$mex,$codMex);
								echo "{success:true, messaggio:\"Registrazione correttamente eseguita\"}";
							}else{rollback(); writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
						}else{rollback(); writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
					}else{rollback(); writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
				}else{rollback(); writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{rollback(); writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			//editing
			commit();
			writeLog("APP","Gestione procedure",$mex,$codMex);
			echo "{success:true, messaggio:\"Registrazione correttamente eseguita\"}";
		}
	} else {
		rollback();
		writeLog("APP","Gestione procedure","\"".getLastError()."\"",$codMex);
		echo "{success:false, messaggio:\"".getLastError()."\"}";
	}
		
	//OLD VERSION
	/*addInsClause($colList,$valList,"CodProcedura",$_POST['CodProcedura'],"S");
	addInsClause($colList,$valList,"TitoloProcedura",$_POST['TitoloProcedura'],"S");
	addInsClause($colList,$valList,"DataIni","2001-01-01","S");
	addInsClause($colList,$valList,"DataFin","9999-12-31","S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	
	$sql =  "INSERT INTO Procedura ($colList)  VALUES($valList)";
	trace("sql->$sql");
	

	if (execute($sql)) {
		$Nid=getInsertId();
		//trace("nid $Nid");
		writeLog("APP","Gestione procedure","Inserimento procedura ".$_POST['TitoloProcedura'],"ADD_PROC");
		echo "{success:true, IdProcedura:\"".$Nid."\", CodProcedura:\"\"}";
	} else {
		echo "{success:false, error:\"".getLastError()."\"}";
	}*/
}

///////////////////////////////////////////
//Funzione di cancellazione delle procedure
///////////////////////////////////////////
function delPrcedura()
{
	global $context;
	
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	$arrErrors=array();
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	$titoliLog = getFetchArray("SELECT TitoloProcedura FROM procedura where idprocedura in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i]['TitoloProcedura'].",";
		else
		 	$list .=$titoliLog[$i]['TitoloProcedura'];
	}
	$codMex="CANC_PROC";
	$mex="Cancellazione delle procedure ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		//trace("I -> $i");
		// serve per il log
		$titoloProcedura = getFetchArray("SELECT TitoloProcedura FROM procedura where idprocedura = $values[$i]");
		//stessa cosa al contrario che per l'inserimento.
		//raccolta azioni di tale procedura( esclusa l'azione di procedura madre)
		$sqlIDAz="Select IdAzione from azioneprocedura where idprocedura = $values[$i]";
		$idAz=getFetchArray($sqlIDAz);
		//trace("--IdAzioneFiglie ".print_r($idAz,true));
		//recupero azione di procedura madre
		/*$sqlIDAzM="select fu.Idfunzione,az.IdAzione 
			from funzione fu 
			left join azione az on(az.IdFunzione=fu.IdFunzione)
			where IdGruppo=(SELECT IdGruppo FROM azioneprocedura ap
			left join azione a on(a.IdAzione=ap.IdAzione) 
			left join funzione f on(f.IdFunzione = a.IdFunzione)
			where idProcedura = $values[$i] 
			limit 1)
			and fu.IdFunzione=fu.IdGruppo";*/
		$sqlIDAzM="select a.IdAzione as IdAzione,a.IdFunzione as IdFunzione from azione a,procedura p,funzione f 
				where a.TitoloAzione=p.TitoloProcedura and a.idfunzione=f.idfunzione 
				and f.idfunzione=f.idgruppo and p.IdProcedura=".$values[$i];
		$idAzM=getFetchArray($sqlIDAzM);
		//trace("--IdAzioneMadre ".$idAzM[0]['IdAzione']." Func: ".$idAzM[0]['IdFunzione']);
		$flagAzioneTipoDel=true;
		//eliminazione da azioneTipoazione
		for($k=0;$k<count($idAz);$k++){
			$sqlDelTa =  "DELETE FROM azionetipoazione where idazione=".$idAz[$k]['IdAzione'];
			if(!execute($sqlDelTa))
			//if(false)
			{
				$flagAzioneTipoDel=false;
			}
			//trace("---Delete azionetipoazione (".$idAz[$k]['IdAzione'].")");
		}
		//trace("--flagAzioneTipoDel $flagAzioneTipoDel");
		if($flagAzioneTipoDel && count($idAz)>0)//se ci sono azioni figlie di questa procedura
		//if(true)
		{
			//eliminazione dalla lista di associazioni azioni-procedure
			$sqdelf = "DELETE FROM azioneprocedura where (idprocedura=$values[$i])";
			//trace("---eliminazione da azioneprocedura delle righe di idprocedura=$values[$i]");
			if(execute($sqdelf))
			//if(true)
			{
				//IdFunzioni delle azioni figlie dell'azione-procedura 
				$sqlIDFun="select IdFunzione from funzione where idgruppo=".$idAzM[0]['IdFunzione']." and IdFunzione != IdGruppo";
				$idFun=getFetchArray($sqlIDFun);
				//trace("----IdFunzioni figlie dell'azione procedura: ".print_r($idFun,true));
				//trace("----@ciclo per n.".count($idFun)." volte");
				for($j=0;$j<count($idFun);$j++)
				{
					$sqlDelPF =  "DELETE FROM profilofunzione where idfunzione=".$idFun[$j]['IdFunzione'];
					//trace("-----elimina da profilofunzione di funzione (".$idFun[$j]['IdFunzione'].")");
					if(execute($sqlDelPF))
					//if(true)
					{
						//cancallazione delle relazioni in statoazione
						$sqlDelSA =  "DELETE FROM statoazione where idazione=".$idAz[$j]['IdAzione'];
						//trace("-----elimina da statoazione di azione (".$idAz[$j]['IdAzione'].")");
						if(execute($sqlDelSA))
						//if(true)
						{
							$sqlDelAAu =  "DELETE FROM azioneautomatica where idazione=".$idAz[$j]['IdAzione'];
							//trace("-----elimina da azioneautomatica di azione (".$idAz[$j]['IdAzione'].")");
							if(execute($sqlDelAAu))
							//if(true)
							{
								//eliminazione da azione (da cambiare: deve cancellare anche tutte le possibili azioni->figlie associate a tale azione)
								$sqlDelAz =  "DELETE FROM azione where idazione=".$idAz[$j]['IdAzione'];
								//trace("-----elimina da azione di azione (".$idAz[$j]['IdAzione'].")");
								if(execute($sqlDelAz))
								//if(true)
								{
									$sqlDELFun="DELETE FROM funzione where IdFunzione=".$idFun[$j]['IdFunzione'];
									//trace("-----elimina da funzione di funzione (".$idFun[$j]['IdFunzione'].")");
									if(execute($sqlDELFun))
									//if(true)
									{
										$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
										$arrErrors[$j]['Result']='D';
									}else{
										$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
										$arrErrors[$j]['Result']='E';
									}
								}else{
									$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
									$arrErrors[$j]['Result']='E';
								}
							}else{
								$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
								$arrErrors[$j]['Result']='E';
							}
						}else{
							$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
							$arrErrors[$j]['Result']='E';
						}
					}else{
						$arrErrors[$j]['IdAzione']=	$idAz[$j]['IdAzione'];
						$arrErrors[$j]['Result']='E';
					}
				}
				//trace("----@fine ciclo");
			}else{
				$arrErrors[0]['IdAzione']=	'tutte le azioni associate alla procedura '.$titoloProcedura[0]['TitoloProcedura'];
				$arrErrors[0]['Result']='E';
			}
		}else{
			if(!$flagAzioneTipoDel){
				$arrErrors[0]['IdAzione']=	'tutte le azioni associate alla procedura '.$titoloProcedura[0]['TitoloProcedura'];
				$arrErrors[0]['Result']='E';
				//trace("----Errore cancellazione figli da azionetipoazione");
			}
			//trace("---no figli");
		}
		
		$numero = count($arrErrors);
		//trace("--numero errori n.$numero");
		//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAzione']);
		if($arrErrors[0]['IdAzione']!=('tutte le azioni associate alla procedura '.$titoloProcedura[0]['TitoloProcedura']))
		{
			//cancellazione dell'azione-procedura madre
			//cancellazione dell'associoazioni profilo-funzione madre
			$sqlDelPF =  "DELETE FROM profilofunzione where idfunzione=".$idAzM[0]['IdFunzione'];
			//trace("---eliminazione FM da profilofunzione (".$idAzM[0]['IdFunzione'].")");
			if(execute($sqlDelPF))
			//if(true)
			{
				//cancellazione da associazione azione-tipo di azione
				$sqlDelTa =  "DELETE FROM azionetipoazione where idazione=".$idAzM[0]['IdAzione'];
				//trace("---eliminazione FM da azionetipoazione (".$idAzM[0]['IdAzione'].")");
				if(execute($sqlDelTa))
				//if(true)
				{
					$sqlDelSA =  "DELETE FROM statoazione where idazione=".$idAzM[0]['IdAzione'];
					//trace("-----eliminazione FM da statoazione (".$idAzM[0]['IdAzione'].")");
					if(execute($sqlDelSA))
					//if(true)
					{
						//cancellazione dell'azione
						$sqlDelAz =  "DELETE FROM azione where idazione=".$idAzM[0]['IdAzione'];
						//trace("---eliminazione FM da azione (".$idAzM[0]['IdAzione'].")");
						if(execute($sqlDelAz))
						//if(true)
						{	
							//update funzione-gruppo
							$sqlUpFun="UPDATE funzione SET IdGruppo=null WHERE IdFunzione=".$idAzM[0]['IdFunzione'];
							//trace("---Update gruppo FM da funzione (".$idAzM[0]['IdFunzione'].")");
							if(execute($sqlUpFun))
							//if(true)
							{
								//eliminazione funzione
								$sqlDELFun="DELETE FROM funzione where IdFunzione=".$idAzM[0]['IdFunzione'];
								//trace("---Elimina FM da funzione (".$idAzM[0]['IdFunzione'].")");
								if(execute($sqlDELFun))
								//if(true)
								{
									$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
									$arrErrors[$num]['Result']='D';
								}else{
									$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
									$arrErrors[$num]['Result']='E';	
								}
							}else{
								$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
								$arrErrors[$num]['Result']='E';	
							}
						}else{
							$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
							$arrErrors[$num]['Result']='E';	
						}
					}else{
						$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
						$arrErrors[$num]['Result']='E';	
					}
				}else{
					$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
					$arrErrors[$num]['Result']='E';	
				}
			}else{
				$arrErrors[$num]['IdAzione']=$idAzM[0]['IdAzione'];
				$arrErrors[$num]['Result']='E';	
			}
			
			//cancella la procedura in tabella
			$sqdel = "DELETE FROM procedura WHERE (idprocedura=$values[$i])";
			if(execute($sqdel))
			//if(true)
			{
				//trace("--eliminazione della procedura $values[$i] ($titoloProcedura)");
				$stringaRitorno.='';
			}
			
			for($h=0;$h<count($arrErrors);$h++){
				$idAen='';
				if($arrErrors[$h]['Result']=='E'){
					$idAen=$arrErrors[$h]['IdAzione'].',';
				}
				if($idAen!='')
				{
					rollback();
					$idAen=substr($idAen, 0, (strlen($idAen)-1));
					$sqlAzErrName="select TitoloAzione from azione where IdAzione in($idAen)";
					$Aen=getFetchArray($sqlAzErrName);
					$stringaRitorno.="Errori per le seguenti cancellazioni:";
					foreach($Aen as $nomAz){
						$stringaRitorno .= '<br />'.' -'.$nomAz.'->'.$titoloProcedura[0]['TitoloProcedura'];	
					}
				}else{
					commit();
				}
			}
		}else{
			//trace("--errore presente da cancellazione figli");
			rollback();
			$stringaRitorno.="Errori per le seguenti cancellazioni:";
			$stringaRitorno .= '<br />'.$arrErrors[0]['IdAzione'];
			writeLog("APP","Gestione procedure",$stringaRitorno,$codMex);	
		}
		//trace("stringaritorno P $i = $stringaRitorno");

		writeLog("APP","Gestione procedure","Cancellazione procedura ".$titoloProcedura[0]['TitoloProcedura'].", riuscita.",$codMex);
	}
	echo $stringaRitorno;	
}

////////////////////////////////////////////////////////
//Funzione di lettura del dettaglio Azione-workflow
////////////////////////////////////////////////////////
function readAzWKF()
{
	global $context;
	$fields = "a.IdAzione as IdAzione,a.TitoloAzione as TitoloAzione,a.Ordine as Ordine,case when a.FlagMultipla='Y' then true else false end as FlagMultipla,a.TipoFormAzione as TipoFormAzione,
				case when a.DataFin>=date(now()) then true else false end as Attiva,sa.Condizione as Condizione, 
				sa.IdStatoRecupero as IdStatoRecupero,sa.IdClasseSuccessiva as IdClasseSuccessiva,
				sa.IdStatoRecuperoSuccessivo as IdStatoRecuperoSuccessivo,count(au.idautomatismo) as NumAut,
				case 
		        when a.FlagAllegato='Y' then true
		        else false end as FlagAllegato,a.FormWidth,a.FormHeight,a.GiorniEvasione,a.PercSvalutazione";
	$query = "azione a 
			left join statoazione sa on(a.idazione=sa.idazione)
			left join azioneautomatica au on(au.idazione=a.idazione)";
	$where = "a.IdAzione=".$_POST['ida'];
	
	$counter = getScalar("SELECT count(*) FROM $query where $where");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query where $where";
	
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
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

////////////////////////////////////////////////////////
//Funzione di lettura del dettaglio Automatismo-workflow
////////////////////////////////////////////////////////
function readAutAzWKF()
{
	global $context;
	$fields = "aut.*,m.titolomodello as TitoloModello,case when aut.FlagCumulativo='Y' then true else false end as Cumulativo";
	$query = "automatismo aut
		left join modello m on(m.idmodello=aut.idmodello)";
	$where = "aut.idautomatismo=".$_POST['idaut'];
	
	$counter = getScalar("SELECT count(*) FROM $query where $where");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query where $where";
	
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
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////////
//Funzione di aggiunta/editing Azione associata a procedura
/////////////////////////////////////////////////////////////
function addAzioneProcedura()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$alfa = Array();
	$sql='';
	for($i=0;$i<26;$i++){
		$alfa[]=strtoupper(chr(ord('a')+$i));
	}
	//dati per salvataggio profili
	$values = explode('|', $_REQUEST['vect']);
	$num = count($values)-1;
	
	isset($_POST['ida'])?$_POST['ida']:'';
	isset($_POST['idp'])?$_POST['idp']:'';
	$Psval = isset($_REQUEST['PercSvalutazione'])?$_REQUEST['PercSvalutazione']:0;
	$form=$_POST['TipoFormAzione'];
	/*switch($_POST['TipoFormAzione'])
	{
		case "Annullamento":$form='Annulla';
			break;
		case "Approvazione":$form='Autorizza';
			break;
		case "Semplice":$form='Base';
			break;
		case "Con data":$form='Data';
			break;
		case "Inoltro notifica":$form='InoltroWF';
			break;
		case "Rifiuto":$form='Rifiuta';
			break;
	}*/
	$flagM='';
	if($_POST['FlagMultipla']){
		$flagM='Y';
	}else{
		$flagM='N';
	}
	$flagAll='';
	if($_POST['allegato']){
		$flagAll='Y';
	}else{
		$flagAll='N';
	}
	if($_POST['IdStatoRecupero']==-1)
		$_POST['IdStatoRecupero']='';
	if($_POST['IdStatoRecuperoSuccessivo']==-1)
		$_POST['IdStatoRecuperoSuccessivo']='';
	if($_POST['IdClasseSuccessiva']==-1)
		$_POST['IdClasseSuccessiva']='';
	
	if($_POST['ida']!='' && $_POST['ida']!=0)
	{
//------------------------------------EDITING
		$mex="Editing azione di workflow ".$_POST['TitoloAzione'];
		$codMex="MOD_AZWF";
		//*****editing tabella Azione
		addSetClause($setClause,"TitoloAzione",$_POST['TitoloAzione'],"S");
		addSetClause($setClause,"Ordine",$_POST['Ordine'],"S");
		if($_POST['Attiva']){
			//attiva
			addSetClause($setClause,"DataFin","9999-12-31","S");
		}else{
			//inattiva
			addSetClause($setClause,"DataFin",date("Y-m-d",mktime(0, 0, 0, 01, 01, 2001)),"S");
		}
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"TipoFormAzione",$form,"S");
		addSetClause($setClause,"FlagMultipla",$flagM,"S");
		addSetClause($setClause,"FlagAllegato",$flagAll,"S");
		addSetClause($setClause,"FormWidth",$_POST['FormWidth'],"N");
		addSetClause($setClause,"FormHeight",$_POST['FormHeight'],"N");
		addSetClause($setClause,"GiorniEvasione",$_POST['GiorniEvasione'],"N");
		addSetClause($setClause,"PercSvalutazione",$Psval,"N");
		$sqlUpAzione = "UPDATE azione $setClause WHERE IdAzione=".$_POST['ida'];
		//trace("UPAZIONE $sqlUpAzione");
		//*****editing tabella Statoazione
		$setClause="";
		addSetClause($setClause,"Condizione",$_POST['Condizione'],"S");
		addSetClause($setClause,"IdStatoRecupero",$_POST['IdStatoRecupero'],"N");	
		addSetClause($setClause,"IdStatoRecuperoSuccessivo",$_POST['IdStatoRecuperoSuccessivo'],"N");	
		addSetClause($setClause,"IdClasseSuccessiva",$_POST['IdClasseSuccessiva'],"N");	
		$sqlUpStatoazione = "UPDATE statoazione $setClause WHERE IdAzione=".$_POST['ida'];
		//trace("UPSTATOAZIONE $sqlUpStatoazione");
		//*****editing tabella Profilofunzione
		$sqlIdFunc="select idfunzione from azione where idazione=".$_POST['ida'];
		$arrIdF=getFetchArray($sqlIdFunc);
		$IdFunzione=$arrIdF[0]['idfunzione'];
		
		//trovo gruppo
		$sqlIdFuncGRUP="select idgruppo from funzione where idfunzione=$IdFunzione";
		$arrIdFG=getFetchArray($sqlIdFuncGRUP);
		$IdGruppo=$arrIdFG[0]['idgruppo'];
		
		$sqlRetrivePFOld="Select idprofilo from profilofunzione where idfunzione=$IdFunzione";
		$arrIdProf=getFetchArray($sqlRetrivePFOld);
		$sqlDeletePF="Delete from profilofunzione where idfunzione=$IdFunzione";
		if(execute($sqlDeletePF))
		{
		//trace("cancella i profili associati: $sqlDeletePF");
		//if(true){
			//replace into ( per la visualizzazione del workflow)
			for($h=0;$h<count($arrIdProf);$h++)
			{
				$sqlDeletePFGR="Delete from profilofunzione where idfunzione=$IdGruppo and IdProfilo=".$arrIdProf[$h]['idprofilo'];
				if(!execute($sqlDeletePFGR)){
					$ErrMex = 'errore nella cancellazione di alcune relazioni nei profili.';
				}
			}
			
			$ErrMex='';
			for($k=1;$k<count($values);$k++){
				$valList="";
				$colList="";
				$idProf=$values[$k];
				$sqlCkGr="REPLACE INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser) VALUES ($idProf,$IdGruppo,'2001-01-01','9999-12-31','".$context['Userid']."')";
				execute($sqlCkGr);
				addInsClause($colList,$valList,"IdProfilo",$idProf,"N");
				addInsClause($colList,$valList,"IdFunzione",$IdFunzione,"N");
				addInsClause($colList,$valList,"DataIni","2001-01-01","S");
				addInsClause($colList,$valList,"DataFin","9999-12-31","S");
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				$sql =  "INSERT INTO profilofunzione ($colList)  VALUES($valList)";
				if(!execute($sql)){
				//trace("crea profilo associato: $sql");
				//if(false){
					$ErrMex = 'errore nell\'associazione di alcuni profili.';
				}
			}
		}else{echo "{success:false, messaggio:\"".getLastError()."\"}";}
		
		if(execute($sqlUpAzione)){
		//if(true){
			if(execute($sqlUpStatoazione)){
			//if(true){
				$mexFinale="Registrazione correttamente eseguita";
				if($ErrMex!=''){
					$mexFinale .="<br /> Vi è inoltre almeno un $ErrMex";
				}
				writeLog("APP","Gestione azioni di workflow",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{echo "{success:false, messaggio:\"".getLastError()."\"}";}
	}else{
	//------------------------------------INSERIMENTO
		$indAlfa=0;
		$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], trim($_POST['TitoloAzione'])));
		$TitoloP=preg_replace("/[^[:alpha:]]/",'',$TitoloP);
		$arrExpl=explode(' ',$_POST['TitoloAzione']);
		$numW=count($arrExpl);
		$codiceNewAz= 'WF_'.substr($TitoloP,0,3);
		$flagGoodCod=false;
		$index=0;
		$indAlfa++;
		$lungS = strlen($TitoloP);
		//calcolo codice azione
		do
		{
			$res=getScalar("select count(*) from azione where CodAzione ='$codiceNewAz'");
			if($res==0){
				$flagGoodCod=true;
			}else{
				$index++;
				if($index<=($lungS-3)){
					$codiceNewAz= 'WF_'.substr($TitoloP,$index,3);
				}else{
					if($numW!=1)
					{
						$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], $_POST['TitoloAzione']));
						$indAlfa++;
						$index=0;
						$codiceNewAz= 'WF_'.substr($TitoloP,$index,3);
					}else{
						$indAlfa=27;
					}
				}	
			}
		}while((!$flagGoodCod)&&($indAlfa<=26));//buono!
		
		if($flagGoodCod){
			//codice creato
			//*****inserimento in funzione
			$mex="Inserimento azione ".$_POST['TitoloAzione'];
			$codMex="ADD_AZWF";
			$gruppo=getScalar("select a.IdFunzione as gruppo from azione a,procedura p,funzione f 
				where a.TitoloAzione=p.TitoloProcedura and a.idfunzione=f.idfunzione 
				and f.idfunzione=f.idgruppo and p.IdProcedura=".$_POST['idp']);
			if (!$gruppo) {
				writeLog("APP","Gestione azioni di workflow",$msg="E' necessario creare prima un'Azione con lo stesso nome della Procedura",$codMex); 
				echo "{success:false, messaggio:\"$msg\"}";				
				return;
			}
			$codF="WF_".substr($codiceNewAz,3);
			$titF=$_POST['TitoloAzione'];
			$sqlInFunc =  "INSERT INTO funzione (CodFunzione,TitoloFunzione,LastUser,IdGruppo)  
							VALUES('$codF','Azione ".strtolower($titF)."','".$context['Userid']."',$gruppo)";
			//trace("INSFUNZIONE $sqlInFunc");
			if (execute($sqlInFunc))
			//if(true) 
			{
				$NidFunz=getInsertId();
				//*****inserimento in azione
				addInsClause($colList,$valList,"IdFunzione",$NidFunz,"N");
				addInsClause($colList,$valList,"CodAzione","$codiceNewAz","S");
				addInsClause($colList,$valList,"TitoloAzione",$_POST['TitoloAzione'],"S");
				addInsClause($colList,$valList,"Ordine",$_POST['Ordine'],"S");
				addInsClause($colList,$valList,"DataIni","2001-01-01","S");
				if($_POST['Attiva']){
					//attiva
					addInsClause($colList,$valList,"DataFin","9999-12-31","S");
				}else{
					//inattiva
					addInsClause($colList,$valList,"DataFin",date("Y-m-d",mktime(0, 0, 0, 01, 01, 2001)),"S");
				}
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				addInsClause($colList,$valList,"TipoFormAzione",$form,"S");
				addInsClause($colList,$valList,"FlagMultipla",$flagM,"S");
				addInsClause($colList,$valList,"FlagAllegato",$flagAll,"S");
				addInsClause($colList,$valList,"FormWidth",$_POST['FormWidth'],"N");
				addInsClause($colList,$valList,"FormHeight",$_POST['FormHeight'],"N");
				addInsClause($colList,$valList,"GiorniEvasione",$_POST['GiorniEvasione'],"N");
				addInsClause($colList,$valList,"PercSvalutazione",$Psval,"N");
				$sqlInsAzione =  "INSERT INTO azione ($colList)  VALUES($valList)";
				
				//trace("INSAZIONE $sqlInsAzione");
				if (execute($sqlInsAzione))
				//if(true) 
				{
					$NidAzione=getInsertId();
					//*****inserimento profilo funzione
					$ErrMex='';
					for($k=1;$k<count($values);$k++){
						$valList="";
						$colList="";
						$idProf=$values[$k];
						$sqlCkGr="REPLACE INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser) VALUES ($idProf,$gruppo,'2001-01-01','9999-12-31','".$context['Userid']."')";
						execute($sqlCkGr);
						addInsClause($colList,$valList,"IdProfilo",$idProf,"N");
						addInsClause($colList,$valList,"IdFunzione",$NidFunz,"N");
						addInsClause($colList,$valList,"DataIni","2001-01-01","S");
						addInsClause($colList,$valList,"DataFin","9999-12-31","S");
						addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
						$sql =  "INSERT INTO profilofunzione ($colList)  VALUES($valList)";
						//trace("INSPROFILO $sql");
						if(!execute($sql)){
						//if(false){
							$ErrMex = 'errore nell\'associazione di alcuni profili';
						}
					}
					
					//*****inserimento nella tabella Statoazione
					$valList="";
					$colList="";
					addInsClause($colList,$valList,"IdAzione",$NidAzione,"N");
					addInsClause($colList,$valList,"Condizione",$_POST['Condizione'],"S");
					addInsClause($colList,$valList,"DataIni","2001-01-01","S");
					addInsClause($colList,$valList,"DataFin","9999-12-31","S");
					addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
					addInsClause($colList,$valList,"IdStatoRecupero",$_POST['IdStatoRecupero'],"N");	
					addInsClause($colList,$valList,"IdStatoRecuperoSuccessivo",$_POST['IdStatoRecuperoSuccessivo'],"N");	
					addInsClause($colList,$valList,"IdClasseSuccessiva",$_POST['IdClasseSuccessiva'],"N");	
					$sqlInsStatoazione = "INSERT INTO statoazione ($colList)  VALUES($valList)";
					//trace("INSSTATOAZIONE $sqlInsStatoazione");
					if (execute($sqlInsStatoazione))
					//if(true) 
					{
						//*****inserimento in azioneprocedura
						$valList="";
						$colList="";
						addInsClause($colList,$valList,"IdAzione",$NidAzione,"N");
						addInsClause($colList,$valList,"IdProcedura",$_POST['idp'],"N");
						addInsClause($colList,$valList,"DataIni","2001-01-01","S");
						addInsClause($colList,$valList,"DataFin","9999-12-31","S");
						addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
						$sqlInsAzproc = "INSERT INTO azioneprocedura ($colList)  VALUES($valList)";
						//trace("INSAZIONEPROCEDURA $sqlInsAzproc");
						if (execute($sqlInsAzproc))
						//if(true)
						{
							$mexFinale="Registrazione correttamente eseguita";
							if($ErrMex!=''){
								$mexFinale .="<br /> Vi è inoltre almeno un $ErrMex";
								$mex .= ". ".$mexFinale;
							}
							writeLog("APP","Gestione azioni di workflow",$mex,$codMex);
							echo "{success:true, messaggio:\"$mexFinale\"}";
						}else{writeLog("APP","Gestione azioni di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
					}else{writeLog("APP","Gestione azioni di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
				}else{writeLog("APP","Gestione azioni di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{writeLog("APP","Gestione azioni di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
			
		}else{
			//codice impossibile da creare, abortisce l'inserimento
			writeLog("APP","Gestione azioni di workflow","Impossibile generare un codice adatto per l\'azione, prego cambiarne il nome.",$codMex); 
			echo "{success:false, error:\"Impossibile generare un codice adatto per l\'azione, prego cambiarne il nome.\"}";
		}
	}
}


/////////////////////////////////////////////////////////////
//Funzione di aggiunta/editing Automatismo associato ad azione
/////////////////////////////////////////////////////////////
function addAutomatismoAzProcedura()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$alfa = Array();
	$sql='';
	for($i=0;$i<26;$i++){
		$alfa[]=strtoupper(chr(ord('a')+$i));
	}
	
	isset($_POST['idaz'])?$_POST['idaz']:'';
	isset($_POST['idauto'])?$_POST['idauto']:'';
	isset($_POST['TipoAutoma'])?$_POST['TipoAutoma']:'';
	isset($_POST['modAutomatismo'])?$_POST['modAutomatismo']:'';
	
	$modello=$_POST['modAutomatismo'];
	$tipo=$_POST['TipoAutoma'];
	$destinatari='';
	switch($_POST['Destinatari'])
	{
		case "Approvatori":$destinatari='*APPROVER';
			break;
		case "Destinatari di riferimento":$destinatari='*DESTINATARIRIF';
			break;
		case "Autori":$destinatari='*AUTHOR';
			break;
		default: $destinatari='';
	}
	$flagC='';
	if($_POST['Cumulativo']){
		$flagC='Y';
	}else{
		$flagC='N';
	}
		
	if($_POST['idauto']!='' && $_POST['idauto']!=0)
	{
//------------------------------------EDITING
		$mex="Editing automatismo di workflow ".$_POST['TitoloAutomatismo'];
		$codMex="MOD_AUTWF";
		//*****editing tabella Automatismo
		if($modello==-1)
			$modello=null;
		addSetClause($setClause,"TitoloAutomatismo",$_POST['TitoloAutomatismo'],"S");
		addSetClause($setClause,"TipoAutomatismo",$tipo,"S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"Condizione",$_POST['Condizione'],"S");
		addSetClause($setClause,"FlagCumulativo",$flagC,"S");
		addSetClause($setClause,"IdModello",$modello,"N");
		addSetClause($setClause,"Destinatari",$destinatari,"S");
		$sqlUpAutomatismo = "UPDATE automatismo $setClause WHERE IdAutomatismo=".$_POST['idauto'];
		//trace("UPAUTOMA $sqlUpAutomatismo");
		
		if(execute($sqlUpAutomatismo)){
		//if(true){
			$mexFinale="Registrazione correttamente eseguita";
			writeLog("APP","Gestione automatismi di workflow",$mex,$codMex);
			echo "{success:true, messaggio:\"$mexFinale\"}";
		}else{writeLog("APP","Gestione automatismi di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
	}else{
	//------------------------------------INSERIMENTO
		//*****inserimento in funzione
		$mex="Inserimento automatismo di workflow ".$_POST['TitoloAutomatismo'];
		$codMex="ADD_AUTWF";

		//*****inserimento in automatismo
		addInsClause($colList,$valList,"TitoloAutomatismo",$_POST['TitoloAutomatismo'],"S");
		addInsClause($colList,$valList,"TipoAutomatismo",$tipo,"S");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"Condizione",$_POST['Condizione'],"S");
		addInsClause($colList,$valList,"FlagCumulativo",$flagC,"S");
		addInsClause($colList,$valList,"IdModello",$modello,"N");
		addInsClause($colList,$valList,"Destinatari",$destinatari,"S");
		$sqlInsAutoma =  "INSERT INTO automatismo ($colList)  VALUES($valList)";
		//trace("INSAUTOMA $sqlInsAutoma");
		if (execute($sqlInsAutoma))
		//if(true) 
		{
			$NidAuto=getInsertId();
			//*****inserimento azioneautomatica
			$valList="";
			$colList="";
			addInsClause($colList,$valList,"IdAzione",$_POST['idaz'],"N");
			addInsClause($colList,$valList,"IdAutomatismo",$NidAuto,"N");
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			$sql =  "INSERT INTO azioneautomatica ($colList)  VALUES($valList)";
			//trace("INSAZAUTO $sql");
			if(execute($sql))
			//if(true)
			{
				$mexFinale="Registrazione correttamente eseguita";
				writeLog("APP","Gestione automatismi di workflow",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{writeLog("APP","Gestione automatismi di workflow","\"".getLastError()."\"",$codMex);  echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{writeLog("APP","Gestione automatismi di workflow","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
	}
}

////////////////////////////////////////////////////////////////////////////////////////////
//Funzione di aggiunta/editing/collegamento di un nuovo stato o di uno associato ad azione
////////////////////////////////////////////////////////////////////////////////////////////
function addStatoProcedura()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$alfa = Array();
	$sql='';
	for($i=0;$i<26;$i++){
		$alfa[]=strtoupper(chr(ord('a')+$i));
	}
	
	isset($_POST['idp'])?$_POST['idp']:'';
	isset($_POST['linking'])?$_POST['linking']:'';
	isset($_POST['TitoloSRec'])?$_POST['TitoloSRec']:'';
	isset($_POST['Abbr'])?$_POST['Abbr']:'';
	isset($_POST['IdSRec'])?$_POST['IdSRec']:'';
	isset($_POST['cmbAzioneNome'])?$_POST['cmbAzioneNome']:'';
	isset($_POST['cmbStatoNome'])?$_POST['cmbStatoNome']:'';
	
	if($_POST['linking']==1)
	{
		//si sta facendo un collegamento
		$res=getFetchArray("select TitoloStatoRecupero from statorecupero where idstatorecupero =".$_POST['cmbStatoNome']);
		$mex="Collegamento stato di workflow ".$res[0]['TitoloStatoRecupero'];
		$codMex="LNK_STTWF";
		
		$chkPresente=getScalar("Select count(*) from statoazione where IdAzione=".$_POST['cmbAzioneNome']);
		if($chkPresente>0)
		{
			//editing della riga con l'aggiunta dello stato di arrivo
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			addSetClause($setClause,"IdStatoRecuperoSuccessivo",$_POST['cmbStatoNome'],"N");	
			$sqlInsStatoazione = "UPDATE statoazione $setClause WHERE IdAzione=".$_POST['cmbAzioneNome'];
			//trace("Linking $sqlInsStatoazione");
			if (execute($sqlInsStatoazione))
			//if(true) 
			{
				$mexFinale="Stato finale: ".$res[0]['TitoloStatoRecupero'].", collegato.";
				writeLog("APP","Gestione stati di workflow",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{
				writeLog("APP","Gestione stati di workflow","\"".getLastError()."\"",$codMex);
				echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			//errore, l'azione a cui associare lo stato deve essere predefinita
			writeLog("APP","Gestione stati di workflow","Errore, l'azione da associare sembra essere stata non correttamente salvata.",$codMex);
			echo "{success:false, messaggio:\"Errore, l\'azione da associare sembra essere stata non correttamente salvata.\"}";
		}
		
	}else{
		//creazione o editing semplice
		if($_POST['IdSRec']!=''){
			//editing
			$mex="Editing stato di workflow ".$_POST['TitoloSRec'];
			$codMex="MOD_STTWF";
			
			//*****editing tabella statorecupero
			addSetClause($setClause,"TitoloStatoRecupero",$_POST['TitoloSRec'],"S");
			addSetClause($setClause,"AbbrStatoRecupero",$_POST['Abbr'],"S");
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			$sqlInsStato = "UPDATE statorecupero $setClause WHERE IdStatoRecupero=".$_POST['IdSRec'];
			//trace("Editing $sqlInsStato");
		}else{
			//creazione
			$mex="Inserimento stato di workflow ".$_POST['TitoloSRec'];
			$codMex="ADD_STTWF";
			
			$indAlfa=0;
			$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], trim($_POST['TitoloSRec'])));
			$TitoloP=preg_replace("/[^[:alpha:]]/",'',$TitoloP);
			$arrExpl=explode(' ',$_POST['TitoloSRec']);
			$numW=count($arrExpl);
			$codiceNewAz= 'WRK'.substr($TitoloP,0,3);
			$flagGoodCod=false;
			$index=0;
			$indAlfa++;
			$lungS = strlen($TitoloP);
			//calcolo codice 
			do
			{
				$res=getScalar("select count(*) from statorecupero where CodStatoRecupero ='$codiceNewAz'");
				if($res==0){
					$flagGoodCod=true;
				}else{
					$index++;
					if($index<=($lungS-3)){
						$codiceNewAz= 'WRK'.substr($TitoloP,$index,3);
					}else{
						if($numW!=1)
						{
							$TitoloP=strtoupper(str_replace(' ', $alfa[$indAlfa], $_POST['TitoloSRec']));
							$indAlfa++;
							$index=0;
							$codiceNewAz= 'WRK'.substr($TitoloP,$index,3);
						}else{
							$indAlfa=27;
						}
					}	
				}
			}while((!$flagGoodCod)&&($indAlfa<=26));//buono!
			
			if($flagGoodCod)
			{
				//codice creato
				//*****inserimento in statorecupero
				addInsClause($colList,$valList,"TitoloStatoRecupero",$_POST['TitoloSRec'],"S");
				addInsClause($colList,$valList,"CodStatoRecupero",$codiceNewAz,"S");
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				addInsClause($colList,$valList,"AbbrStatoRecupero",$_POST['Abbr'],"S");
				addInsClause($colList,$valList,"DataIni","2001-01-01","S");
				addInsClause($colList,$valList,"DataFin","9999-12-31","S");
				$sqlInsStato =  "INSERT INTO statorecupero ($colList)  VALUES($valList)";
				//trace("Insert $sqlInsStato");
			}else{
				//codice impossibile da creare, abortisce l'inserimento
				writeLog("APP","Gestione stati di workflow","Impossibile generare un codice adatto per lo stato, prego cambiarne il nome.",$codMex);
				echo "{success:false, error:\"Impossibile generare un codice adatto per lo stato, prego cambiarne il nome.\"}";
				die();
			}
		}
		if(execute($sqlInsStato))
		//if(true)
		{
			if($_POST['cmbAzioneNome']!='')
			{
				$NidSrec=getInsertId();
				$chkPresente=getScalar("Select count(*) from statoazione where IdAzione=".$_POST['cmbAzioneNome']);
				if($chkPresente>0)
				{
					//editing della riga con l'aggiunta dello stato di arrivo
					addSetClause($setClause,"LastUser",$context['Userid'],"S");
					addSetClause($setClause,"IdStatoRecuperoSuccessivo",$NidSrec,"N");	
					$sqlInsStatoazione = "UPDATE statoazione $setClause WHERE IdAzione=".$_POST['cmbAzioneNome'];
					//trace("Linking $sqlInsStatoazione");
					if (execute($sqlInsStatoazione)){
						$mexFinale="Registrazione correttamente eseguita";
						writeLog("APP","Gestione stati di workflow",$mex,$codMex);
						echo "{success:true, messaggio:\"$mexFinale\"}";
					}else{
						writeLog("APP","Gestione stati di workflow","\"".getLastError()."\"",$codMex);
						echo "{success:false, messaggio:\"".getLastError()."\"}";}				
				}else{
					//errore, l'azione a cui associare lo stato deve essere predefinita
					writeLog("APP","Gestione stati di workflow","Errore, l'azione da associare sembra essere stata non correttamente salvata.",$codMex);
					echo "{success:false, messaggio:\"Errore, l\'azione da associare sembra essere stata non correttamente salvata.\"}";
				}
			}else{
				$mexFinale="Registrazione correttamente eseguita";
				writeLog("APP","Gestione stati di workflow",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}			
		}else{
			writeLog("APP","Gestione stati di workflow","\"".getLastError()."\"",$codMex);
			echo "{success:false, messaggio:\"".getLastError()."\"}";}
	}
}

///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle azioni legate ad una procedura
///////////////////////////////////////////////////////////////////
function delAzionePrcedura()
{
	global $context;
	//***********************************************
	//funzione								liv 1
	//	|___________
	//	|			|
	//azione	profilofunzione				liv 2
	//	|___________________
	//	|					|
	//statoazione		azioneprocedura		liv 3
	//***********************************************
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	$arrErrors=array();
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	$titoliLog = getFetchArray("SELECT TitoloAzione FROM azione where idazione in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i]['TitoloAzione'].",";
		else
		 	$list .=$titoliLog[$i]['TitoloAzione'];
	}
	$codMex="CANC_AZPROC";
	$mex="Cancellazione delle azioni ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		//trace("I -> $i");
		// serve per il log
		$titoloAzione = getFetchArray("SELECT TitoloAzione FROM azione where idazione = $values[$i]");
		
		$flagAzioneTipoDel=true;
		//eliminazione di terzo livello da azioneTipoazione
		$sqlDelSA =  "DELETE FROM statoazione where idazione=".$values[$i];
		//trace("liv 3 Delete statoazione $sqlDelSA");
		if(!execute($sqlDelSA))
		//if(false)
		{
			$flagAzioneTipoDel=false;
		}else{
			//eliminazione sullo stesso livello da azioneprocedura (x workflow)
			$sqlDelAZPro =  "DELETE FROM azioneprocedura where idazione=".$values[$i];
			//trace("liv 3 Delete azioneprocedura $sqlDelAZPro");
			if(!execute($sqlDelAZPro)){
			//if(false){
				$flagAzioneTipoDel=false;	
			}else{
				//eliminazione sullo stesso livello da azionetipoazione (x normale cancellazione)
				$sqlDelAZAZTpz =  "DELETE FROM azionetipoazione where idazione=".$values[$i];
				//trace("liv 3 Delete azionetipoazione $sqlDelAZAZTpz");
				if(!execute($sqlDelAZAZTpz)){
				//if(false){
					$flagAzioneTipoDel=false;	
				}else{
					//eliminazione sullo stesso livello da azioneautomatica (x normale cancellazione)
					$sqlDelAZAut =  "DELETE FROM azioneautomatica where idazione=".$values[$i];
					//trace("liv 3 Delete azioneautomatica $sqlDelAZAut");
					if(!execute($sqlDelAZAut)){
					//if(false){
						$flagAzioneTipoDel=false;	
					}
				}
			}
		}
		
		//trace("--flagAzioneTipoDel $flagAzioneTipoDel");
		if($flagAzioneTipoDel)//se non ci sono stati errori nell'eliminazione di terzo livello
		//if(true)
		{
			//funzione e gruppo di tale azione cancellata
			$sqlIDFun="select a.IdFunzione as IdFunzione, f.IdGruppo as IdGruppo
						from azione a
						left join funzione f on(f.idfunzione=a.idfunzione) where a.idazione=$values[$i]";
			$idFun=getFetchArray($sqlIDFun);
			//trace("funz e gruppo ".print_r($idFun,true));
			
			//eliminazione di secondo livello dalla tabella azioni
			$sqdelAz = "DELETE FROM azione where idazione=$values[$i]";
			//trace("liv 2 Delete azione $sqdelAz");
			if(execute($sqdelAz))
			//if(true)
			{
				//eliminazione di secondo livello da tabella profilofunzione
				$sqlDelPF =  "DELETE FROM profilofunzione where idfunzione=".$idFun[0]['IdFunzione'];
				//trace("liv 2 Delete profilofunzione $sqlDelPF");
				if(execute($sqlDelPF))
				//if(true)
				{
					//elimiinazione di primo livello della funzione
					$sqlDELFun="DELETE FROM funzione where IdFunzione=".$idFun[0]['IdFunzione'];
					//trace("liv 1 Delete funzione $sqlDELFun");
					if(execute($sqlDELFun))
					//if(true)
					{
						$stringaRitorno.='';
						$arrErrors[$i]['IdAzione']=	$idAz[0]['IdAzione'];
						$arrErrors[$i]['Result']='D';
						writeLog("APP","Gestione azioni","Cancellazione azione ".$titoloAzione[0]['TitoloAzione'],"CANC_AZNE");
					}else{
						$arrErrors[$i]['IdAzione']=	'nella tabella delle funzioni per \"'.$titoloAzione[0]['TitoloAzione'].'\"';
						$arrErrors[$i]['Result']='E';
					}
				}else{
					$arrErrors[$i]['IdAzione']=	'nella tabella delle relazioni tra profili e funzioni per \"'.$titoloAzione[0]['TitoloAzione'].'\"';
					$arrErrors[$i]['Result']='E';
				}
			}else{
				$arrErrors[$i]['IdAzione']=	'nella tabella delle azioni per \"'.$titoloAzione[0]['TitoloAzione'].'\"';
				$arrErrors[$i]['Result']='E';
			}
		}else{
			$arrErrors[$i]['IdAzione']=	'nella tabella degli stati o delle associazioni alle procedure per \"'.$titoloAzione[0]['TitoloAzione'].'\"';
			$arrErrors[$i]['Result']='E';
		}
	}	
	//$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAzione']);
	$messaggioErr='';
	for($h=1;$h<=count($arrErrors);$h++)
	{
		if($arrErrors[$h]['Result']=='E'){
			$messaggioErr .= '<br />'.' -'.$arrErrors[$h]['IdAzione'];
		}
	}
	if($messaggioErr!=''){
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
		rollback();
	}else{
		$mexFinale="Azioni cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}

///////////////////////////////////////////////////////////////////
//Funzione di collegamento degli automatismi specificati all'azione
///////////////////////////////////////////////////////////////////
function linkAutomatismiAzPrcedura()
{
	global $context;
	//***********************************************
	//azioneautomatica							liv 1
	//***********************************************
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	$arrErrors=array();
	isset($_POST['idAzione'])?$_POST['idAzione']:'';
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	$codMex="LINK_AUTAZ";
	$mex="Link degli automatismi ($list) per l'azione n."+$_POST['idAzione'];
	for($i=1;$i<=$num;$i++)
	{
		//trace("I -> $i");
		// serve per il log
		$titoloAutomatismo = getFetchArray("SELECT TitoloAutomatismo FROM automatismo where idAutomatismo = $values[$i]");
		
		//*****inserimento azioneautomatica
		$valList="";
		$colList="";
		addInsClause($colList,$valList,"IdAzione",$_POST['idAzione'],"N");
		addInsClause($colList,$valList,"IdAutomatismo",$values[$i],"N");
		addInsClause($colList,$valList,"DataIni","2001-01-01","S");
		addInsClause($colList,$valList,"DataFin","9999-12-31","S");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		$sql =  "INSERT INTO azioneautomatica ($colList)  VALUES($valList)";
		//trace("INSAZAUTO $sql");
		if(execute($sql))
		//if(true)
		{
			$arrErrors[$i]['IdAutomatismo']=$values[$i];
			$arrErrors[$i]['Result']='A';
		}else{
			$arrErrors[$i]['IdAutomatismo']="".$titoloAutomatismo[0]['TitoloAutomatismo']."";
			$arrErrors[$i]['Result']='E';
		}
		
		
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAutomatismo']);
	$messaggioErr='';
	for($h=1;$h<=count($arrErrors);$h++)
	{
		if($arrErrors[$h]['Result']=='E'){
			$messaggioErr .= '<br />'.' -'.$arrErrors[$h]['IdAutomatismo'];
		}
	}
	if($messaggioErr!=''){
		$stringaRitorno ="Errori almeno per la seguente associazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Links effettuati con successo.";
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mexFinale,$mex,$codMex);
	echo $stringaRitorno;	
}

///////////////////////////////////////////////////////////////////
//Funzione di cancellazione degli automatismi legati alle azioni
///////////////////////////////////////////////////////////////////
function delAutomatismiAzPrcedura()
{
	global $context;
	//***********************************************
	//automatismo								liv 1
	//	|
	//azioneautomatica							liv 2
	//***********************************************
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	$arrErrors=array();
	isset($_POST['erase'])?$_POST['erase']:0;
	isset($_POST['idAzione'])?$_POST['idAzione']:'';
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	$titoliLog = getFetchArray("SELECT TitoloAutomatismo FROM automatismo where idAutomatismo in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i]['TitoloAutomatismo'].",";
		else
		 	$list .=$titoliLog[$i]['TitoloAutomatismo'];
	}
	$codMex="CANC_AUTAZ";
	$mex="Cancellazione degli automatismi ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		//trace("I -> $i");
		// serve per il log
		$titoloAutomatismo = getFetchArray("SELECT TitoloAutomatismo FROM automatismo where idAutomatismo = $values[$i]");
		
		$flagAzioneTipoDel=true;
		//eliminazione di secondo livello da azioneautomatica
		if($_POST['erase'])
		{
			//si sta cancellando tutto
			$sqlDelAA =  "DELETE FROM azioneautomatica where idAutomatismo=".$values[$i];
		}else{
			//solo i collegamenti per quell'azione
			$sqlDelAA =  "DELETE FROM azioneautomatica where idAutomatismo=".$values[$i]." and idazione=".$_POST['idAzione'];
		}
		//trace("liv 2 Delete azioneautomatica $sqlDelAA");
		if(!execute($sqlDelAA))
		//if(false)
		{
			$flagAzioneTipoDel=false;
		}else{
			if($_POST['erase'])
			{
				//eliminazione di primo livello da automatismo
				$sqlDelAuto =  "DELETE FROM automatismo where idAutomatismo=".$values[$i];
				//trace("liv 1 Delete automatismo $sqlDelAuto");
				if(!execute($sqlDelAuto)){
				//if(false){
					$flagAzioneTipoDel=false;	
				}
			}
		}
		
		//trace("--flagAzioneTipoDel $flagAzioneTipoDel");
		if($flagAzioneTipoDel)//se non ci sono stati errori nell'eliminazione 
		{
			$stringaRitorno.='';
			$arrErrors[$i]['IdAutomatismo']=$values[$i];
			$arrErrors[$i]['Result']='D';
			writeLog("APP","Gestione automatismi","Cancellazione/scollegamento automatismo ".$titoloAutomatismo[0]['TitoloAutomatismo'],"CANC_AUTO");
		}else{
			$arrErrors[$i]['IdAutomatismo']=	'nella tabella delle associazioni alle azioni per \"'.$titoloAutomatismo[0]['TitoloAutomatismo'].'\"';
			$arrErrors[$i]['Result']='E';
		}
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAutomatismo']);
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
			$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdAutomatismo'];
		}
	}
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Automatismi cancellati con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}

///////////////////////////////////////////////////////////////////
//Funzione di cancellazione degli stati legati alle procedure
///////////////////////////////////////////////////////////////////
function delStatiPrcedura()
{
	global $context;
	//***********************************************
	//statorecupero								liv 1
	//	|
	//statoazione								liv 2
	//***********************************************
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	array_shift($values);
	//trace("values ".print_r($values,true));
	$stringaIn = implode(',',$values);
	//trace("stringa in $stringaIn");
	$arrErrors=array();
	isset($_POST['erase'])?$_POST['erase']:0;
	isset($_POST['idprocedura'])?$_POST['idprocedura']:0;
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	
	//trace("I -> $i");
	// serve per il log
	$titoliLog = getFetchArray("SELECT TitoloStatoRecupero FROM statorecupero where idstatorecupero in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i]['TitoloStatoRecupero'].",";
		else
		 	$list .=$titoliLog[$i]['TitoloStatoRecupero'];
	}
	$codMex="CANC_STATIPROC";
	$mex="Cancellazione degli stati ($list)";
	beginTrans();
	$TsrArray = getFetchArray("SELECT TitoloStatoRecupero FROM statorecupero where idstatorecupero in($stringaIn)");
	//trace("array ".print_r($TsrArray,true));
	$TitStatRec='';
	for($k=0;$k<count($TsrArray);$k++)
	{
		$TitStatRec.=$TsrArray[$k]['TitoloStatoRecupero'].",";
	}
	$TitStatRec=substr($TitStatRec, 0, -1);
	//trace("titoli ".$TitStatRec);
	$flagAzioneTipoDel=true;
	//eliminazione di secondo livello da statoazione
	if($_POST['erase'])
	{
		//si sta editando tutto
		//editing della riga dello stato di arrivo da eliminare
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"IdStatoRecuperoSuccessivo",'',"S");	
		$sqlDelSA = "UPDATE statoazione $setClause WHERE idstatorecuperosuccessivo in($stringaIn) or idstatorecupero in($stringaIn)";
		//$sqlDelSA =  "DELETE FROM statoazione where idstatorecuperosuccessivo in($stringaIn) or idstatorecupero in($stringaIn)";
	}else{
		//solo i collegamenti per quella procedura
		//recupero idazioni di quella procedura
		$IdAzArray = getFetchArray("SELECT IdAzione FROM azioneprocedura where Idprocedura=".$_POST['idprocedura']);
		//trace("array ".print_r($IdAzArray,true));
		$AzProc='';
		for($k=0;$k<count($IdAzArray);$k++)
		{
			$AzProc.=$IdAzArray[$k]['IdAzione'].",";
		}
		$AzProc=substr($AzProc, 0, -1);
		//eliminazione specifica
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"IdStatoRecuperoSuccessivo",'',"S");
		$sqlDelSA = "UPDATE statoazione $setClause WHERE (idstatorecuperosuccessivo in($stringaIn) or idstatorecupero in($stringaIn)) and idazione in($AzProc)";
		//$sqlDelSA =  "DELETE FROM statoazione where (idstatorecuperosuccessivo in($stringaIn) or idstatorecupero in($stringaIn)) and idazione in($AzProc)";
	}
	//trace("liv 2 Delete statoazione $sqlDelSA");
	if(!execute($sqlDelSA))
	//if(false)
	{
		$flagAzioneTipoDel=false;
	}else{
		if($_POST['erase'])
		{
			//eliminazione di primo livello da automatismo
			$sqlDelSRec =  "DELETE FROM statorecupero where idstatorecupero in($stringaIn)";
			//trace("liv 1 Delete statorecupero $sqlDelSRec");
			if(!execute($sqlDelSRec)){
			//if(false){
				$flagAzioneTipoDel=false;	
			}
		}
	}
	
	//trace("--flagAzioneTipoDel $flagAzioneTipoDel");
	if($flagAzioneTipoDel)//se non ci sono stati errori nell'eliminazione 
	{
		commit();
		$stringaRitorno ='';
		writeLog("APP","Gestione stati workflow","Cancellazione/scollegamento ".$TitStatRec,$codMex);
	}else{
		rollback();
		$stringaRitorno ="Errori nella cancellazione o nello scollegamento degli stati.";
		writeLog("APP","Gestione stati workflow",$stringaRitorno,$codMex);
	}
	//trace("stringaritorno = $stringaRitorno");

	echo $stringaRitorno;	
}

/////////////////////////////////////////////////////////////////////////////////////////////
//Funzione di creazione del modello ad hoc per l'automatismo(od azione associata) selezionato
/////////////////////////////////////////////////////////////////////////////////////////////
function readModPrecompilato ()
{	
	global $context;
	isset($_POST['idaut'])?$_POST['idaut']:'';
	isset($_POST['idaz'])?$_POST['idaz']:'';
	$idAut=$_POST['idaut'];
	$idAz=$_POST['idaz'];
	//trace("idaut $idAut");
	//trace("idaz $idAz");

	$genT="";
	$fields="*";
	$table="";
	$where="";
	$sqlCodeInfoRetrive='';
	if($idAut!=''){
		$genT="TitoloAutomatismo";
		$table="automatismo";
		$where="idautomatismo = $idAut";
	}elseif($idAz!=''){
		$genT="TitoloAzione";
		$table="azione";
		$where="idazione = $idAz";
	}

	//trace("sqlcount: SELECT count(*) FROM $table where $where");
	$CodeInfoRetriveCount=getScalar("SELECT count(*) FROM $table where $where");
	if ($CodeInfoRetriveCount == NULL)
		$CodeInfoRetriveCount = 0;
	if ($CodeInfoRetriveCount == 0) {
			$arr = array();
	} else {
		//trace("sql: SELECT $fields FROM $table where $where");
		$sql = "SELECT $fields FROM $table where $where";
		$arr=getFetchArray($sql); 
	}
	//trace("arr: ".print_r($arr,true));
	if($arr!=null && $arr!='')
	{
		//ora elaborazione
		$vect=array();
		//calcolo titolo
		$titleModel="";
		$subj="";
		$firstStringTxt="";
		$secondStringTxt="";
		$genNome=$arr[0][$genT];
		//trace("getNome $genNome");
		$genNome=strtolower($genNome);
		//trace("->lower $genNome");
		$values = explode(' ', $genNome);
		foreach($values as $parola){
			$parola=ucfirst($parola);
		}
		$nomeAlternativo = implode('', $values);
		//trace("->values: ".print_r($values,true));
		$lastW = ucfirst($values[count($values)-1]);
		//trace("->lastw $lastW");
		$indRespF=strpos($genNome, 'rifiut');
		$indRespS=strpos($genNome, 'respin');
		$indAccF=strpos($genNome, 'autorizz');
		$indAccS=strpos($genNome, 'accett');
		$indIno=strpos($genNome, 'inoltr');
		/*trace("indRespF $indRespF");
		trace("indRespS $indRespS");
		trace("indAccF $indAccF");
		trace("indAccS $indAccS");
		trace("indIno $indIno");*/
		
		if($indRespF!== false || $indRespS!== false){
			//automatismo di respingimento
			if($indRespF!== false){
				$titleModel = "MailRifiuta".$lastW;
				$subj = "Proposta ".strtolower($lastW)." rifiutata";
				$firstStringTxt="rifiutato";
			}else{
				$titleModel = "MailRespinge".$lastW;
				$subj = "Proposta ".strtolower($lastW)." respinta";
				$firstStringTxt="respinto";
			}
			$secondStringTxt=strtolower($lastW);
		}elseif($indAccF!== false || $indAccS!== false){
			//automatismo di accettazione
			if($indAccF!== false){
				$titleModel = "MailAutorizza".$lastW;
				$subj = "Proposta ".strtolower($lastW)." autorizzata";
				$firstStringTxt="autorizzato";
			}else{
				$titleModel = "MailAccetta".$lastW;
				$subj = "Proposta ".strtolower($lastW)." accettata";
				$firstStringTxt="accettato";
			}
			$secondStringTxt=strtolower($lastW);
		}elseif($indIno!==false){
			//automatismo di inoltro
			$titleModel = "MailInoltro".$lastW;
			$subj = "Inoltro proposta ".strtolower($lastW);
			$firstStringTxt="inoltrato";
			$secondStringTxt=strtolower($lastW);
		}else{
			//tipologia di automatismo non definita
			$titleModel = "Mail".$nomeAlternativo;
			$subj = "Notifica $genNome";
			$firstStringTxt="*INSERIRE TESTO*";
			$secondStringTxt="*INSERIRE TESTO*";
		}
		$existSimilar=getScalar("select count(*) from modello where TitoloModello = '$titleModel'");
		if($existSimilar>0)
		{
			$vect[0]['NomeM']="Attenzione: esiste gia' un modello similare...";
			$vect[0]['Subj']="Inserire un titolo ed un soggetto adeguato prego...";
		}else{
			$vect[0]['NomeM']=$titleModel;
			//soggetto
			$vect[0]['Subj']=$subj;
			
		}
		/*trace("TitoloModello ".$vect[0]['NomeM']);
		trace("Soggetto ".$vect[0]['Subj']);*/
		//calcolo tipomodello(workflow)
		$tipoM="W";
		$vect[0]['cTipo']=$tipoM;
		//trace("TipoModello ".$vect[0]['cTipo']);
		//calcolo flag riservato
		//$flagRis=false;
		//$vect[0]['FlagRiservato']=$flagRis;
		//trace("FlagRiservato ".$vect[0]['FlagRiservato']);
		//body
		$body="L'utente %NOMEAUTORE%, in data %DATARICHIESTA% ha $firstStringTxt la richiesta di"; 
		$body.=" $secondStringTxt per le seguenti pratiche:<br>%Modello.SubModMMail%";
		$body.=" <table><thead><tr><th>Contratto</th><th>Intestatario</th><th>Importo</th></tr></thead></table>";
		$body.=" <br>%NOTA%<br>[Messaggio generato automaticamente dal sistema]";
		$vect[0]['TTMail']=$body;
		//trace("Body ".$vect[0]['TTMail']);		
		$counter=1;
	}
	
	/*
	var locFields = Ext.data.Record.create([{name: 'IdTipoAllegato'},{name: 'TitoloTipoAllegato'}*/
	//debug
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($vect);  //encode the data in json format
	} else {
		$data = json_encode_plus($vect);  //encode the data in json format
	}
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	//trace("data ".print_r($data,true));
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

//////////////////////////////////////////////////////////////////////////
//Funzione di salvataggio del modello mail e collegamento all'automatismo
//////////////////////////////////////////////////////////////////////////
function saveModAndLink()
{
	global $context;
	
	$testoMAIL = $_REQUEST['TTMail'];
	$tipoMail = $_REQUEST['cTipo'];
	//trace("tipomail $tipoMail");
	$mod = $_POST['model'];
	$nome = $_POST['NomeM'];
	$nomeFile = $_POST['NomeFile'];
	$sogg = $_POST['Subj'];
	$riservato = $_POST['riservato'];
	$combo = $_POST['cAllegato'];
	$Operatore = $context['Userid'];
	$automatismo = $_POST['IdAut'];
	
	try
	{
		
		//trace("testo: ".$testo." |modello: ".$mod." |nomeF: ".$nome." |Subj: ".$sogg." |Res: ".$riservato." |cmb: ".$combo." |File: ".$nomeFile);
		//conversione testo con controllo di MAIL/SMS
		$extens='';
		if($testoMAIL!=''){

			$TXT="$sogg\n$testoMAIL";
						
			switch ($tipoMail)
			{
				case "Workflow":
					$tmodello='W';
					break;
			}
			$extens='.html';
		}
				
		//controllo bontà dati combo
		$regexp ="/^[0-9]$/";
		if (!preg_match($regexp,$combo))
		{
			//ha passato il displayField: cerca il corrispondente value nella tabella
			$sqlCombo="SELECT IdTipoAllegato FROM tipoallegato where TitoloTipoAllegato = '$combo'";
			$combo=getScalar($sqlCombo);
		}

		
		$number=0;
		//controllo se inserimento o modifica
		if($mod!=''){
			//modifica
			$codMex="MOD_MODLNK";
			$number=file_put_contents(TEMPLATE_PATH."/$nomeFile",$TXT);
			if(!($number>0)){
				//trace("errore nella modifica del file originale.");
				//trace("num $number");
				writeLog("APP","Gestione modello mail e link automatismi","Errore nella registrazione del modello",$codMex);
				echo "{success:false, error:\"Errore nella registrazione del modello\"}";
				die();
			}
		}else{
			//inserimento puro 
			$codMex="ADD_MODLNK";
			$number=file_put_contents(TEMPLATE_PATH."/$nome$extens",$TXT);
		}
		
		//$number=1;//prova
		if($number>0)
		{
			//inserimento in modelli
			//controllo se è una modifica
			/*if($mod!=''){
				$sqlDel="DELETE FROM modello WHERE IdModello=$mod";
				if(!execute($sqlDel)){
					echo "{success:false, error:\"".getLastError()."\"}";
					die();
				}
			}*/
			//trace("combo $combo");		
			$field='';
			$value='';
			$idfield='';
			$idvalue='';
			if($combo!=''){
				$field=',IdTipoAllegato';
				$value=$combo;
			}
			if($mod!=''){
				$idfield='IdModello';
				$idvalue=$mod;
				$desc=$nomeFile;
				if($field != '')
					$field=$field.'=';
				$sqinsNus = "UPDATE modello SET TitoloModello='$nome',FileName='$desc',TipoModello='$tmodello',FlagRiservato='$riservato',DataIni='2001-01-01',DataFin='9999-12-31',LastUser='$Operatore'$field$value WHERE $idfield=$idvalue";
			}else{
				$desc=$nome.''.$extens;
				if($combo!=''){
					$value=','.$value;
				}
				//trace("comboINS $value");
				$sqinsNus = "REPLACE INTO modello (".$idfield."TitoloModello,FileName,TipoModello,FlagRiservato,DataIni,DataFin,LastUser".$field.") VALUES (".$idvalue."'$nome','$desc','$tmodello','$riservato','2001-01-01','9999-12-31','$Operatore'".$value.")";
			}
			
			//trace("Q. ".$sqinsNus);//echo "{success:true, error:\"File salvato correttamente. Ricaricare la griglia.\"}";
			if(execute($sqinsNus)){
				//$NidModello=getInsertId();
				//$sqlAddingModelAuto="Update automatismo SET IdModello=$NidModello where IdAutomatismo=$automatismo";
				//if(execute($sqlAddingModelAuto)){
					if($mod!=''){
						writeLog("APP","Gestione modello mail e link automatismi","File modificato correttamente.",$codMex);
						echo "{success:true, error:\"File modificato correttamente.\"}";
					}else{
						writeLog("APP","Gestione modello mail e link automatismi","File salvato ed associato correttamente.",$codMex);
						echo "{success:true, error:\"File salvato ed associato correttamente.\"}";}	
				//}else{echo "{success:false, error:\"".getLastError()."\"}";}				
			}else{
				writeLog("APP","Gestione modello mail e link automatismi","\"".getLastError()."\"",$codMex);
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}else{
			writeLog("APP","Gestione modello mail e link automatismi","Errore nella registrazione del modello.",$codMex);
			echo "{success:false, error:\"Errore nella registrazione del modello\"}";
		}		
	}
	catch (Exception $e)
	{
		writeLog("APP","Gestione modello mail e link automatismi","Errore nella registrazione del modello".$e,$codMex);
		echo "{success:false, error:\"Errore nella registrazione del modello: ".$e."\"}";
	}
}

//////////////////////////////////////////////////////////////////////////
//Funzione di salvataggio del modello mail e collegamento all'automatismo
//////////////////////////////////////////////////////////////////////////
function updatePropostaDBT()
{
	
	global $context;
		
	$idContratto = $_REQUEST['idC']; 
	$idAzione = $_REQUEST['idAz'];
	$nota = $_REQUEST['nota'];
    $IdUser = $context['IdUtente'];
    $userid = $context['Userid'];
    $nomeutente = $context['NomeUtente'];
	$codMex="MOD_PROPDBT";	
	$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$idContratto");  //
	
	if(!$pratica){   
		writeLog("APP","Gestione proposta DBT","Operazione non riuscita a causa del seguente errore: ".getLastError(),$codMex);
	  Throw new Exception("Operazione non riuscita a causa del seguente errore: ".getLastError());
	}
	//forzatura a riaffido
	
	$idRegolaProvvigione = $_REQUEST["IdRegolaProvvigione"];
	$nome  = forzaAffidoAgenzia($idContratto,$idRegolaProvvigione,"","NULL",false,true);
	if($nome===FALSE){
		writeLog("APP","Gestione proposta DBT","Operazione non riuscita a causa del seguente errore: ".getLastError(),$codMex);
	  Throw new Exception(getLastError()); 
	}
//	$esitoAzione = $esitoAzione."<br/>Registrata forzatura del prossimo affidamento automatico all'agenzia ".$nome;
                
//	writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
				
	//gestione scadenza e proposta
	$parameters = array();
	$parameters["NOMEAUTORE"] = $nomeutente;
	$parameters["DATARICHIESTA"] = date("d/m/Y");
	$parameters["NOTA"] = $nota;
	$parameters["*AUTHOR"] = "U".$IdUser;
	//$parameters["AZIONE"] = $azione["TitoloAzione"];
	$parameters["*DESTINATARIRIF"]=array();
	// parametro per la chiamata da mail di workflow
	$parameters["PATH_WRKFLW"] = LINK_URL."main.php?wrkflw=WRFL";
	$idStatoAzione = getScalar("SELECT IdStatoAzione FROM statoazione WHERE IdAzione=".$idAzione);
	$data = italianDate($_REQUEST["data"]); // data di scadenza
	$dataScad = ISODate($_REQUEST["data"]);
	if($dataScad!=''){
	  $parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in ".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
	  $addEsito = "Operazione effettuata.";
	  $parameters['DATASCADENZA'] = ISODate($_REQUEST["data"]);
	  GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
	  $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
	}else{
	   $data="Non specificata";
	   $esitoAzione = "$addEsito";
	 }
	if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid)){
	 writeLog("APP","Gestione proposta DBT","Operazione non riuscita a causa del seguente errore: ".getLastError(),$codMex);
	 Throw new Exception(getLastError());
	}
    // cancellazione delle note in data precedente 
	if($_REQUEST["chkHidden"]==true)
	{
		$sql = "DELETE FROM nota where IdContratto = ".$idContratto." and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()";
		if(!execute($sql))
		{
			$esitoAzione = "Cortesemente riprovare nuovamente.";
		}
	}	
	 
    $dataVend = ISODate($_REQUEST["dataVendita"]);
	$flagIrr = $_REQUEST["chkFlag"]?'Y':'N';
    $flagIpo = $_REQUEST["chkFlagIpoteca"]?'Y':'N';
    $flagConc = $_REQUEST["chkFlagConcorsuale"]?'Y':'N';
        
    udpdateCampiPropostaDBT($dataVend,$flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"]);
    if(!udpdateCampiPropostaDBT($dataVend,$flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"])){ 
	  	writeLog("APP","Gestione proposta DBT","Operazione non riuscita a causa del seguente errore: ".getLastError(),$codMex);
    	Throw new Exception(getLastError());
    }
	writeLog("APP","Gestione proposta DBT","Modifica effettuata correttamente.",$codMex);
    echo "{success:true, messaggio:\"Modifica effettuata correttamente.\"}";  
}
?>
