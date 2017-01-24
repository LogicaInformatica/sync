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
		case "readAz":readAz();
			break;
		case "saveAz":saveFuncAz();
			break;
		case "readProcCk":readProcCk();
			break;
		case "checkProc":checkProc();
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
////////////////////////////////////////////
//Funzione di lettura della griglia Azioni
////////////////////////////////////////////
function readAz()
{
	global $context;
	//$fields = "a.IdAzione,a.IdFunzione,p.IdProcedura,a.CodAzione,a.CodAzioneLegacy,a.TitoloAzione,f.TitoloFunzione,p.TitoloProcedura,a.TipoFormAzione,a.FlagMultipla,a.LastUpd,a.LastUser";
	//$query = "azione a left join funzione f on(a.IdFunzione=f.IdFunzione) left join (azioneprocedura ap left join procedura p on(ap.IdProcedura=p.IdProcedura)) on(a.IdAzione=ap.IdAzione)";
	$fields = "*";
	$query = "v_anagrafica_azione";
	$gruppo = ($_REQUEST['group']) ? ($_REQUEST['group']) : null;
	
	switch ($gruppo)
	{
		case "TipoAutomatismo":
			$order = "TipoAutomatismo asc";
			break;
		default:
			$order = "TitoloAzione asc";
			break;
	}
	
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query";
		//trace("sql>> $sql");
		if ($_REQUEST['groupBy']>' ') {
			$sql .= " ORDER BY ".$order;
			if ($_REQUEST['sort']>' '){ 
				$sql .= ",".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= ",$order";
			}
		} 
		else
		{
			if ($_REQUEST['sort']>' '){ 
				$sql .= " ORDER BY ".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= " ORDER BY $order";
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

///////////////////////////////////////
//Funzione di salvataggio azioni
///////////////////////////////////////
function saveFuncAz()
{
	global $context;
	$Operatore = $context['Userid'];
	$arrIns=array();
	$goodEnd=false;
	$AzLogTitle='';
	$CodLog='';
	
	$IdAz = ($_REQUEST['idAz']) ? ($_REQUEST['idAz']) : '';
	$sRec=isset($_POST['StatoRecupero'])?$_POST['StatoRecupero']:'';
	if($sRec==-1){
		$sRec='';
	}
	$cAdd=isset($_POST['Condizione'])?$_POST['Condizione']:'';
	if($IdAz!='')
	{
		//editing
		//raccolta campi
		array_push($arrIns, $IdAz, 'IdAzione');
		//$idF = ($_REQUEST['vectF']) ? ($_REQUEST['vectF']) : '';
		//array_push($arrIns, $idF, 'IdFunzione');
		$codAz = isset($_REQUEST['CodAzione'])?$_REQUEST['CodAzione']:'';
		array_push($arrIns, $codAz, 'CodAzione');
		$NomeTitoloAz = isset($_REQUEST['TitoloAzione'])?addslashes(htmlstr($_REQUEST['TitoloAzione'])):'';
		array_push($arrIns, $NomeTitoloAz, 'TitoloAzione');
		$codAzLeg = isset($_REQUEST['CodAzioneLegacy'])?$_REQUEST['CodAzioneLegacy']:'';
		array_push($arrIns, $codAzLeg, 'CodAzioneLegacy');
		$TformAz = isset($_REQUEST['CFazione'])?$_REQUEST['CFazione']:'';
		array_push($arrIns, $TformAz, 'TipoFormAzione');
		$FlagM = isset($_REQUEST['FlagMultipla'])?$_REQUEST['FlagMultipla']:'N';
		if($FlagM == 'on')
		{
			$FlagM = 'Y';
		}
		array_push($arrIns, $FlagM, 'FlagMultipla');
		$FlagSpeciale = isset($_REQUEST['speciale'])?$_REQUEST['speciale']:'N';
	    if($FlagSpeciale == 'on')
		{
			$FlagSpeciale = 'Y';
		}
		array_push($arrIns, $FlagSpeciale, 'FlagSpeciale');
		$TAllegato = isset($_REQUEST['allegato'])?$_REQUEST['allegato']:'';
		array_push($arrIns, $TAllegato, 'FlagAllegato');
		$GiorniEvasione = isset($_REQUEST['GiorniEvasione'])?$_REQUEST['GiorniEvasione']:0;
		array_push($arrIns, $GiorniEvasione, 'GiorniEvasione');
		$Flar = isset($_REQUEST['FormWidth'])?$_REQUEST['FormWidth']:0;
		array_push($arrIns, $Flar, 'FormWidth');
		$Falt = isset($_REQUEST['FormHeight'])?$_REQUEST['FormHeight']:0;
		array_push($arrIns, $Falt, 'FormHeight');
		$Psval = isset($_REQUEST['PercSvalutazione'])?$_REQUEST['PercSvalutazione']:0;
		array_push($arrIns, $Psval, 'PercSvalutazione');
		
		$AzLogTitle="Modificata azione ".$NomeTitoloAz;
		$CodLog='MOD_AZIONE';				
		//variabili
		$query = '';
		
		//costruzione query
		for ($i=0; $i<count($arrIns); $i++)
		{
			if ($arrIns[$i] == '')
			{
				$query .= $arrIns[$i+1]."=null,";
			}else{
				$query .= $arrIns[$i+1]."='".$arrIns[$i]."',";
			}
			$i++;		
		}	
		$query=substr($query,0,$query.length-1);
		
		//---------------
		//MODIFICA AZIONE
		//---------------
		$sqlUp="UPDATE azione SET $query where IdAzione=$IdAz";
		//trace("s $sqlUp");
		if (execute($sqlUp)){
		//if(true){
			//aggiornamento statoazione
			$setClause='';
			addSetClause($setClause,"Condizione",$cAdd,"S");
			addSetClause($setClause,"IdStatoRecupero",$sRec,"N");
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			$sqlUpStatoAzione = "UPDATE statoazione $setClause WHERE IdAzione=$IdAz";
			//trace("Edsa $sqlUpStatoAzione");
			if (execute($sqlUpStatoAzione)){
			//if(true){
				$goodEnd=true;
			}
		}
		
	}else{
		//creazione
		$codAz = isset($_REQUEST['CodAzione'])?$_REQUEST['CodAzione']:'';
		$NomeTitoloAz = isset($_REQUEST['TitoloAzione'])?addslashes(htmlstr($_REQUEST['TitoloAzione'])):'';
		$codAzLeg = isset($_REQUEST['CodAzioneLegacy'])?$_REQUEST['CodAzioneLegacy']:'';
		$TformAz = isset($_REQUEST['CFazione'])?$_REQUEST['CFazione']:'';
		$FlagM = isset($_REQUEST['FlagMultipla'])?$_REQUEST['FlagMultipla']:'N';
		if($FlagM == 'on')
		{
			$FlagM = 'Y';
		}
		isset($_POST['Ordine'])?$_POST['Ordine']:'';
		//$TSpeciale = isset($_REQUEST['speciale'])?$_REQUEST['speciale']:'N';
		$FlagSpeciale = isset($_REQUEST['speciale'])?$_REQUEST['speciale']:'N';
	    if($FlagSpeciale == 'on')
		{
			$FlagSpeciale = 'Y';
		}
		/*$FlagAllegato = isset($_REQUEST['allegato'])?$_REQUEST['allegato']:'N';
		if($FlagAllegato == 'on')
		{
			$FlagAllegato = 'Y';
		}*/
		$TSAllegato = isset($_REQUEST['allegato'])?$_REQUEST['allegato']:'';
		$GiorniEvasione = isset($_REQUEST['GiorniEvasione'])?$_REQUEST['GiorniEvasione']:0;
		$Flar = isset($_REQUEST['FormWidth'])?$_REQUEST['FormWidth']:0;
		$Falt = isset($_REQUEST['FormHeight'])?$_REQUEST['FormHeight']:0;
		$Psval = isset($_REQUEST['PercSvalutazione'])?$_REQUEST['PercSvalutazione']:0;
		
		//creazione funzione
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"CodFunzione","AZIONE_".strtoupper($codAz),"S");
		addInsClause($colList,$valList,"TitoloFunzione",$NomeTitoloAz,"S");
		addInsClause($colList,$valList,"IdGruppo",199,"N");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		$sqlNAzione =  "INSERT INTO funzione ($colList)  VALUES($valList)";
		//trace("na $sqlNAzione");
		if (execute($sqlNAzione)){
		//if(true){
			//associazione in profilofunzione
			$valList = "";
			$colList = "";
			$IdFunc=getInsertId();
			addInsClause($colList,$valList,"IdProfilo",1,"N");
			addInsClause($colList,$valList,"IdFunzione",$IdFunc,"N");			
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			$sqlNProf =  "INSERT INTO profilofunzione ($colList)  VALUES($valList)";
			//trace("np $sqlNProf");
			if (execute($sqlNProf)){
			//if(true){
				//creazione azione
				$valList = "";
				$colList = "";
				addInsClause($colList,$valList,"IdFunzione",$IdFunc,"N");
				addInsClause($colList,$valList,"CodAzione",strtoupper($codAz),"S");
				addInsClause($colList,$valList,"TitoloAzione",$NomeTitoloAz,"S");
				//addInsClause($colList,$valList,"FlagSpeciale",$TSpeciale,"S");
				addInsClause($colList,$valList,"FlagSpeciale",$FlagSpeciale,"S");
				//addInsClause($colList,$valList,"FlagAllegato",$FlagAllegato,"S");
				addInsClause($colList,$valList,"FlagAllegato",$TSAllegato,"S");
				addInsClause($colList,$valList,"GiorniEvasione",$GiorniEvasione,"N");
				addInsClause($colList,$valList,"FormWidth",$Flar,"N");
				addInsClause($colList,$valList,"FormHeight",$Falt,"N");
				addInsClause($colList,$valList,"PercSvalutazione",$Psval,"N");
				addInsClause($colList,$valList,"Ordine",$_POST['Ordine'],"S");
				addInsClause($colList,$valList,"DataIni","2001-01-01","S");
				addInsClause($colList,$valList,"DataFin","9999-12-31","S");
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				addInsClause($colList,$valList,"TipoFormAzione",$TformAz,"S");
				addInsClause($colList,$valList,"FlagMultipla",$FlagM,"S");
				$sqlInsAzione =  "INSERT INTO azione ($colList)  VALUES($valList)";
				//trace("nA $sqlInsAzione");
				if (execute($sqlInsAzione)){
				//if(true){
					$IdAz=getInsertId();
					//creazione di una riga nello statoazione
					$valList = "";
					$colList = "";
					addInsClause($colList,$valList,"IdAzione",$IdAz,"N");
					addInsClause($colList,$valList,"Condizione",$cAdd,"S");
					addInsClause($colList,$valList,"IdStatoRecupero",$sRec,"N");
					addInsClause($colList,$valList,"DataIni","2001-01-01","S");
					addInsClause($colList,$valList,"DataFin","9999-12-31","S");
					addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
					$sqlInsStatoAzione =  "INSERT INTO statoazione ($colList)  VALUES($valList)";
					//trace("nSA $sqlInsStatoAzione");
					$AzLogTitle="Creazione azione ";
					$CodLog='ADD_AZIONE';
					if (execute($sqlInsStatoAzione)){
					//if(true){
						$goodEnd=true;
					}					
				}
			}else{
				echo "{success:false, error:\"".getLastError()."\"}";
				die();
			}
		}else{
			echo "{success:false, error:\"".getLastError()."\"}";
			die();
		}
	}
	
	if($goodEnd)
	{
		// trace su log
		//$idP = ($_REQUEST['vect']) ? ($_REQUEST['vect']) : '';
		
		//---------------
		//PULIZIA E POI RINNOVO CONNESSIONI CON LE PROCEDURE 
		//---------------
		/*$idP = explode('|', $_REQUEST['vect']);
		//trace("arrP ".print_r($idP,true));
		//trace("Np ".count($idP));
		$sqlDelP = "Delete from azioneprocedura where IdAzione=$IdAz";
		if (execute($sqlDelP)){
			for ($d=1;$d<count($idP);$d++)
			{
				$sqlUpP="INSERT INTO azioneprocedura (IdAzione,IdProcedura,DataIni,DataFin,LastUser) VALUES ($IdAz,$idP[$d],'2001-01-01','9999-12-31','$Operatore')";
				//trace(">> P - $sqlUpP");
				execute($sqlUpP);
			}*/
			
			//---------------
			//PULIZIA E POI RINNOVO CONNESSIONI CON I TIPI DI AZIONE
			//---------------
			$idTA = explode('|', $_REQUEST['vectF']);
			//trace("arrTA ".print_r($idTA,true));
			//trace("NTA ".count($idTA));
			$sqlDelATA = "Delete from azionetipoazione where IdAzione=$IdAz";
			//trace("qa ".$sqlDelATA);
			if (execute($sqlDelATA)){
			//if(true){
				for ($g=1;$g<count($idTA);$g++)
				{
					$sqlUpATA="INSERT INTO azionetipoazione (IdAzione,IdTipoAzione,DataIni,DataFin,LastUser) VALUES ($IdAz,$idTA[$g],'2001-01-01','9999-12-31','$Operatore')";
					//trace(">> NTA - $sqlUpATA");
					if(!execute($sqlUpATA))
						writeLog("APP","Gestione azioni","(Continua)Nell inserimento su 'azionetipoazione': (Az:$IdAz,TipAz:$idTA[$g])",$CodLog);
				}
				
				//---------------
				//PULIZIA E POI RINNOVO CONNESSIONI CON GLI AUTOMATISMI
				//---------------
				$idAA = explode('|', $_REQUEST['vectA']);
				//trace("arrAA ".print_r($idAA,true));
				//trace("AA ".count($idAA));
				$sqlDelAAut = "Delete from azioneautomatica where IdAzione=$IdAz";
				//trace("qa ".$sqlDelAAut);
				if(execute($sqlDelAAut)){
				//if (true){
					for ($g=1;$g<count($idAA);$g++)
					{
						$sqlUpAA="INSERT INTO azioneautomatica (IdAzione,IdAutomatismo,DataIni,DataFin,LastUser) VALUES ($IdAz,$idAA[$g],'2001-01-01','9999-12-31','$Operatore')";
						//trace(">> NAA - $sqlUpAA");
						if(!execute($sqlUpAA))
							writeLog("APP","Gestione azioni","(Continua)Nell inserimento su 'azioneautomatica': (Az:$IdAz,Aut:$idAA[$g])",$CodLog);
					}
				}else{
					writeLog("APP","Gestione azioni","Nelle cancellazioni su 'azioneautomatica': \"".getLastError()."\"",$CodLog);
					echo "{success:false, error:\"".getLastError()."\"}";
					die();
				}
			}else{
				writeLog("APP","Gestione azioni","Nelle cancellazioni su 'azionetipoazione': \"".getLastError()."\"",$CodLog);
				echo "{success:false, error:\"".getLastError()."\"}";
				die();
			}
		/*}else{
			echo "{success:false, error:\"".getLastError()."\"}";
			die();
		}*/
		echo "{success:true, error:\"Successo\"}";
		writeLog("APP","Gestione azioni",$AzLogTitle,$CodLog);
	}else{
		writeLog("APP","Gestione azioni","\"".getLastError()."\"",$CodLog);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//////////////////////////////////////////
//Funzione di lettura dei pannelli di check
//////////////////////////////////////////
function readProcCk()
{
	global $context;
	$tsk = $_REQUEST['who'];
	$where = '';
	switch ($tsk)
	{
		case "procedure":
			$fields = "IdProcedura,TitoloProcedura";
			$query = "procedura";
			break;
		case "tipoazioni":
			$fields = "IdTipoAzione,TitoloTipoAzione";
			$query = "tipoazione";
			$where = " where IdTipoAzione not in(12,13)";
			break;
		case "funzioni":
			$fields = "IdFunzione,TitoloFunzione";
			$query = "funzione order by idgruppo";
			break;
		case "Automatismi":
			$fields = "IdAutomatismo,TitoloAutomatismo";
			$query = "automatismo";
			break;
	}
	
	$counter = getScalar("SELECT count(*) FROM $query$where");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query$where";
	
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		$arr=htmlentities_deep($arr);
		//trace("arr ".print_r($arr,true));
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

/////////////////////////////////////////////////////
//Funzione di retrive dei checkbox procedure(dettaglio)
/////////////////////////////////////////////////////
function checkProc()
{
	global $context;
	
	$azione = isset($_REQUEST['IdAzione'])?$_REQUEST['IdAzione']:'';
	$tsk = $_REQUEST['who'];
	switch ($tsk)
	{
		case "procedure":
			$sqlchk = "select IdProcedura from azione a left join azioneprocedura ap on(a.IdAzione=ap.IdAzione) where ap.IdAzione=$azione";
			break;
		case "funzioni":
			$sqlchk = "select IdFunzione from azione where IdAzione=$azione";
			break;
		case "tipoazioni":
			$sqlchk = "select IdTipoAzione from azionetipoazione where idAzione=$azione";
			break;
		case "Automatismi":
			$sqlchk = "select IdAutomatismo from azioneautomatica where idAzione=$azione";
			break;
	}
	if($azione!=''){
		$result = fetchValuesArray($sqlchk);
	}else{
		$result='';
	}
	//trace("res ".print_r($result,true));
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($result);  //encode the data in json format
	} else {
		$data = json_encode_plus($result);  //encode the data in json format
	}
	
	echo $data;
}
?>
