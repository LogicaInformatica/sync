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
		case "deleteClassRules":delClassificazioni();//class
			break;
		case "deleteASSRules":delAssociateRuleOp();//ass
			break;
		case "deleteFasceRules":delFasceAssociate();//aff
			break;
		case "readAssMainGrid":readAssGrid();//aff
			break;
		case "readClassMainGrid":readClassGrid();//class
			break;
		case "readFasceGrid":readFasceListGrid();//aff
			break;
		case "readGenAssGrid":readAssGenGrid();//aff
			break;
		case "readRegoleAssOp":readRegOpAssGrid();//ass
			break;
		case "readAffOpGrid":readAOpGrid();//aff
			break;
		case "readClassDett":readRegClassificazione();//Class
			break;
		case "saveFascia":addFascia();//aff
			break;
		case "saveAssOp":addAssRule();//aff
			break;
		case "saveClassificazione":addClassRule();//Class
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
///////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle agenzie di assegnazione
///////////////////////////////////////////////////////////////////////
function readAssGrid()
{
	global $context;
	$fields = "*";
	$query = "v_assegnazioni_workflow";
	$counter = getScalar("SELECT count(*) FROM $query");
	$ordine="titoloufficio asc";
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query ORDER BY ";
		
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle classificazioni
/////////////////////////////////////////////////////////
function readClassGrid()
{
	global $context;
	$fields = "*,case when FlagNoAffido='Y' then 'Y' else 'N' end as FlagNONAffido,
				case when FlagRecupero='Y' then 'Y' else 'N' end as FlagRec";
	$query = "classificazione";
	$counter = getScalar("SELECT count(*) FROM $query");
	$ordine="ordine asc";
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query ORDER BY ";
		
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

//////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia/lista delle fasce da associare 
//////////////////////////////////////////////////////////////////////////
function readFasceListGrid()
{
	global $context;
	isset($_POST['idReg'])?$_POST['idReg']:0;
	$fields = "*";
	$query ="fasciaprovvigione";
	$where ="where idregolaprovvigione = ".$_POST['idReg'];
	$ordine = "valoresoglia asc";
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia generica delle associazioni 
/////////////////////////////////////////////////////////////////////////////////////
function readAssGenGrid()
{
	global $context;
	isset($_POST['idRep'])?$_POST['idRep']:'';
	isset($_POST['scelta'])?$_POST['scelta']:'';
	
	if($_POST['idRep']!='')
	{
		switch ($_POST['scelta'])
		{
			case "NumTipAff":
				$fields = "*";
				$from = "regolaprovvigione";
				$where = "IdReparto=".$_POST['idRep'];
				$ordine="TitoloRegolaProvvigione asc";
				break;
			case "NumRegAff":
				$fields = "ra.*,fp.TitoloFamiglia,cl.TitoloClasse,a.TitoloArea,
						(case ra.TipoDistribuzione 
					        when 'C' then 'Carico totale'
					        when 'I' then 'Carico giornaliero'end) as tipodistribuzioneConv";
				$from = "regolaassegnazione ra
						left join famigliaprodotto fp on(ra.IdFamiglia=fp.IdFamiglia)
						left join classificazione cl on(ra.IdClasse=cl.IdClasse)
						left join area a on(ra.idarea=a.idarea)";
				$where = "ra.tipoassegnazione=2 and ra.IdReparto=".$_POST['idRep'];
				$ordine="cl.TitoloClasse asc";
				break;
			case "NumRegAffOpe":
				$fields = "u.nomeutente,ra.*,fp.TitoloFamiglia,cl.TitoloClasse,
						(case ra.TipoDistribuzione 
							        when 'C' then 'Carico totale'
							        when 'I' then 'Carico giornaliero'end) as tipodistribuzioneConv";
				$from = "regolaassegnazione ra left join utente u on(ra.idutente=u.idutente)
						left join famigliaprodotto fp on(ra.IdFamiglia=fp.IdFamiglia)
						left join classificazione cl on(ra.IdClasse=cl.IdClasse)";
				$where = "ra.tipoassegnazione=3 and ra.IdReparto=".$_POST['idRep'];
				$ordine="u.nomeutente asc";
				break;
			default:
				break;
		}
		$counter = getScalar("SELECT count(*) FROM $from where $where");
	}else{
		$counter = 0;
	}
	
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from where $where ORDER BY ";
		//trace("sql $sql");
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle regole associate all'operatore scelto
/////////////////////////////////////////////////////////////////////////////////////
function readRegOpAssGrid()
{
	global $context;
	isset($_POST['IdOp'])?$_POST['IdOp']:'';
	
	if($_POST['IdOp']!='')
	{
		$fields = "ra.*,f.titolofamiglia,c.titoloclasse,r.titoloufficio,rp.codregolaprovvigione,
					(case ra.TipoDistribuzione 
					        when 'C' then 'Carico totale'
					        when 'I' then 'Carico giornaliero'end) as tipodistribuzioneConv,
				concat(re.TitoloUfficio,' (',rp.codregolaprovvigione,')') as Nominativo";
		$from = "regolaassegnazione ra 
				left join famigliaprodotto f on(ra.idfamiglia=f.idfamiglia) 
				left join classificazione c on(ra.idclasse=c.idclasse) 
				left join reparto r on(ra.idreparto=r.idreparto)
				left join regolaprovvigione rp on(ra.idregolaprovvigione=rp.idregolaprovvigione)
				left join reparto re on(re.idreparto=rp.idreparto)";
		$where = "ra.idutente=".$_POST['IdOp'];
		$ordine="rp.titoloregolaprovvigione asc";
		
		$counter = getScalar("SELECT count(*) FROM $from where $where");
	}else{
		$counter = 0;
	}
	
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from where $where ORDER BY ";
		//trace("sqlGrid $sql");
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
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

/////////////////////////////////////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle regole di assegnazione ad operatore per la regola specificata 
/////////////////////////////////////////////////////////////////////////////////////////////////////
function readAOpGrid()
{
	global $context;
	isset($_POST['idReg'])?$_POST['idReg']:'';
	isset($_POST['sceltaLettura'])?$_POST['sceltaLettura']:'';
	//dal dettaglio in editing
	$postCondition='';
	$linkCondition='';
	if($_POST['idReg']!='')
	{
		switch ($_POST['sceltaLettura'])
		{
			case "NumTipAff":
				$fields = "*,(select count(*) from fasciaprovvigione where idregolaprovvigione =".$_POST['idReg'].") as numFasce";
				$from = "regolaprovvigione";
				$where = "IdRegolaProvvigione=".$_POST['idReg'];
				$ordine="TitoloRegolaProvvigione asc";
				break;
			default:
				$fields="*,(case TipoDistribuzione 
					        when 'C' then 'Carico totale'
					        when 'I' then 'Carico giornaliero'
					        when 'P' then 'Preferito' end) as tipodistribuzioneConv";
				$from="regolaassegnazione";
				$where="idregolaassegnazione=".$_POST['idReg'];
				break;
		}
		//si chiama dall'editing e si specifica quale stato si sta editando
		$counter = getScalar("SELECT count(*) FROM $from where $where");
	}else{
		$counter=0;
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

/////////////////////////////////////////////////////////
//Funzione di lettura del dettaglio della classificazione 
/////////////////////////////////////////////////////////
function readRegClassificazione()
{
	global $context;
	isset($_POST['idClasse'])?$_POST['idClasse']:'';
	//dal dettaglio in editing
	$postCondition='';
	$linkCondition='';
	if($_POST['idClasse']!='')
	{
		$fields="c.*,
			    case when FlagNoAffido='Y' then true else false end as FlagNONAffido, 
			    case when FlagRecupero='Y' then true else false end as FlagRec,
			    case when FlagRecidivo='Y' then 'Y' 
			         when FlagRecidivo='N' then 'N' else -1 end as FlagRecidivoMAN,
				case when c.IdTipoPagamento is null then '' else tp.TitoloTipoPagamento end as TipoPagamentoMAN,
				case when c.IdFamiglia is null then '' else fp.TitoloFamiglia end as TitoloFamigliaMAN,c.ordine as gravita";
		$from="classificazione c
				left join tipopagamento tp on(c.IdTipoPagamento=tp.IdTipoPagamento)
				left join famigliaprodotto fp on(c.IdFamiglia=fp.IdFamiglia)";
		$where="IdClasse=".$_POST['idClasse'];
			
		//si chiama dall'editing e si specifica quale stato si sta editando
		$counter = getScalar("SELECT count(*) FROM $from where $where");
	}else{
		$counter=0;
	}
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $from where $where";
		//trace("sqlDett $sql");
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
//Funzione di aggiunta di fascia provvigione
/////////////////////////////////////////////////////////////
function addFascia()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	isset($_POST['idReg'])?$_POST['idReg']:'';
	isset($_POST['isMod'])?$_POST['isMod']:0;
	isset($_POST['oldAbbr'])?$_POST['oldAbbr']:'';
	
	//*****inserimento in fasciaprovvigione
	$counter = getScalar("Select count(*) FROM fasciaprovvigione where idregolaprovvigione=".$_REQUEST['idReg']." and AbbrFasciaProvvigione='".$_POST['nomeFascia']."'");
	if($counter==0 && $_POST['isMod']==0)
	{//caso: non è presente nel database un record con stesso idregola e nome, e non è in modifica; quindi è un nuovo record
		$regola=getFetchArray("SELECT TitoloRegolaProvvigione FROM regolaprovvigione where IdRegolaProvvigione=".$_POST['idReg']); 
		$mex="Inserimento nuova fascia per la regola ".$regola[0]['TitoloRegolaProvvigione'];
		$codMex="ADD_FARULE";
	
		addInsClause($colList,$valList,"IdRegolaProvvigione",$_POST['idReg'],"N");
		addInsClause($colList,$valList,"ValoreSoglia",$_POST['valSoglia'],"N");
		addInsClause($colList,$valList,"Formula",$_POST['Formula'],"S");
		addInsClause($colList,$valList,"AbbrFasciaProvvigione",$_POST['nomeFascia'],"S");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		$sqlInsFascia = "INSERT INTO fasciaprovvigione ($colList)  VALUES($valList)";
		//trace("INSAZIONEPROCEDURA $sqlInsFascia");
		if (execute($sqlInsFascia))
		//if(true)
		{
			$mexFinale="Registrazione correttamente eseguita";
			writeLog("APP","Gestione fasce provvigioni",$mex,$codMex);
			echo "{success:true, messaggio:\"$mexFinale\"}";
		}else{
			writeLog("APP","Gestione fasce provvigioni","\"".getLastError()."\"",$codMex);
			echo "{success:false, messaggio:\"".getLastError()."\"}";}
	}else{
		if($_POST['isMod']==1)
		{
			$regola=getFetchArray("SELECT TitoloRegolaProvvigione FROM regolaprovvigione where IdRegolaProvvigione=".$_POST['idReg']); 
			$mex="Modifica fascia per la regola ".$regola[0]['TitoloRegolaProvvigione'];
			$codMex="MOD_FARULE";
			$checkField='';
			if($counter==0)
			{//caso: è stato rinominato ed è in modifica
				$checkField=$_POST['oldAbbr'];
			}else{
				//caso: non è stato rinominato ed è in modifica
				$checkField=$_POST['nomeFascia'];
			}
			addSetClause($setClause,"IdRegolaProvvigione",$_POST['idReg'],"N");
			addSetClause($setClause,"ValoreSoglia",$_POST['valSoglia'],"N");
			addSetClause($setClause,"Formula",$_POST['Formula'],"S");
			addSetClause($setClause,"AbbrFasciaProvvigione",$_POST['nomeFascia'],"S");
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			$sqlModFascia = "UPDATE fasciaprovvigione $setClause WHERE IdRegolaProvvigione=".$_POST['idReg']." and AbbrFasciaProvvigione='".$_POST['oldAbbr']."'";
			//trace("ModZIONEPROCEDURA $sqlModFascia");
			if (execute($sqlModFascia))
			//if(true)
			{
				$mexFinale="Registrazione correttamente eseguita";
				writeLog("APP","Gestione fasce provvigioni",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{
				writeLog("APP","Gestione fasce provvigioni","\"".getLastError()."\"",$codMex);
				echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			writeLog("APP","Gestione fasce provvigioni","Questa fascia &egrave gi&agrave presente nel database.",$codMex);
			echo "{success:false, messaggio:\"Questa fascia &egrave gi&agrave presente nel database.\"}";}
	}
}
////////////////////////////////////////////////////////////////////////////////////////////
//Funzione di aggiunta/editing di una regola di affidamento ad operatore per l'agenzia,
//di regola per agenzia, o di editing/creazione tipologia di regola
////////////////////////////////////////////////////////////////////////////////////////////
function addAssRule()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	
	isset($_POST['idReg'])?$_POST['idReg']:'';
	isset($_POST['idRep'])?$_POST['idRep']:'';
	isset($_POST['scelta'])?$_POST['scelta']:'';
	isset($_POST['idTipoAss'])?$_POST['idTipoAss']:'';
	isset($_POST['cmbFamProdAA'])?$_POST['cmbFamProdAA']:'';
	isset($_POST['delOldFasce'])?$_POST['delOldFasce']:0;
	if($_POST['cmbFamProdAA']==-1)
		$_POST['cmbFamProdAA']='';
	if($_POST['cmbAreaAA']==-1)
		$_POST['cmbAreaAA']='';
	if($_POST['cmbClassAA']==-1)
		$_POST['cmbClassAA']='';

	if($_POST['scelta']!='NumTipAff')
	{
		//griglie per affidamento agenzia ed associazione utente
		//creazione o editing semplice
		//trace("reg ".$_POST['idReg']);
		if($_POST['idReg']!='')
		{
			//editing
			$mex="Editing della regola di affidamento n. ".$_POST['idReg'];
			$codMex="MOD_AAOPRL";
			
			//*****editing tabella regolaassegnazione
			if($_POST['idTipoAss']==3)
			{
				addSetClause($setClause,"IdUtente",$_POST['cmbAssOpeAA'],"N");
			}
			addSetClause($setClause,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
			addSetClause($setClause,"IdClasse",$_POST['cmbClassAA'],"N");
			addSetClause($setClause,"IdReparto",$_POST['idRep'],"N");
			addSetClause($setClause,"TipoDistribuzione",$_POST['cmbTipDisAA'],"S");
			if($_POST['idTipoAss']==2)
			{
				addSetClause($setClause,"DurataAssegnazione",$_POST['DurataAssegnazione'],"N");
				addSetClause($setClause,"GiorniFissiInizio",$_POST['GiorniFissiInizio'],"S");
				addSetClause($setClause,"GiorniFissiFine",$_POST['GiorniFissiFine'],"S");
				addSetClause($setClause,"IdArea",$_POST['cmbAreaAA'],"N");
			}
			addSetClause($setClause,"Condizione",$_POST['Condizione'],"S");
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			$sqlInsStato = "UPDATE regolaassegnazione $setClause WHERE IdRegolaAssegnazione=".$_POST['idReg'];
			$word="modificata";
			$wmex="regole assegnazione";
			//trace("Editing $sqlInsStato");
		}else{
			//creazione
			$mex="Inserimento della regola di affidamento";
			$codMex="ADD_AAOPRL";
			
			//*****inserimento in regolaassegnazione
			if($_POST['idTipoAss']==3)
			{
				addInsClause($colList,$valList,"IdUtente",$_POST['cmbAssOpeAA'],"N");
			}
			addInsClause($colList,$valList,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
			addInsClause($colList,$valList,"IdClasse",$_POST['cmbClassAA'],"N");
			addInsClause($colList,$valList,"IdReparto",$_POST['idRep'],"N");
			addInsClause($colList,$valList,"TipoDistribuzione",$_POST['cmbTipDisAA'],"S");
			addInsClause($colList,$valList,"TipoAssegnazione",$_POST['idTipoAss'],"N");
			if($_POST['idTipoAss']==2)
			{
				addInsClause($colList,$valList,"DurataAssegnazione",$_POST['DurataAssegnazione'],"N");
				addInsClause($colList,$valList,"GiorniFissiInizio",$_POST['GiorniFissiInizio'],"S");
				addInsClause($colList,$valList,"GiorniFissiFine",$_POST['GiorniFissiFine'],"S");
				addInsClause($colList,$valList,"IdArea",$_POST['cmbAreaAA'],"N");
			}
			addInsClause($colList,$valList,"Condizione",$_POST['Condizione'],"S");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			$sqlInsStato =  "INSERT INTO regolaassegnazione ($colList)  VALUES($valList)";
			$Nid=getInsertId();
			$mex.=" n.$Nid";
			$word="salvata";
			$wmex="regole assegnazione";
			//trace("Insert $sqlInsStato");
		}
	}else if($_POST['scelta']!=''){
	//creazione o editing semplice
		//trace("reg ".$_POST['idReg']);
		if($_POST['idReg']!='')
		{
			//editing
			$mex="Editing della tipologia di regola di provvigionamento n. ".$_POST['idReg'];
			$codMex="MOD_TRRL";
			
			//*****editing tabella regolaprovvigione
			addSetClause($setClause,"TitoloRegolaProvvigione",$_POST['TitoloRegolaProvvigione'],"S");
			addSetClause($setClause,"CodRegolaProvvigione",$_POST['CodRegolaProvvigione'],"S");
			addSetClause($setClause,"AbbrRegolaProvvigione",$_POST['AbbrRegolaProvvigione'],"S");
			addSetClause($setClause,"FormulaFascia",$_POST['cmbTipFascA'],"S");
			addSetClause($setClause,"Formula",$_POST['Formula'],"S");
			addSetClause($setClause,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
			addSetClause($setClause,"IdClasse",$_POST['cmbClassAA'],"N");
			addSetClause($setClause,"LastUser",$context['Userid'],"S");
			$sqlInsStato = "UPDATE regolaprovvigione $setClause WHERE IdRegolaProvvigione=".$_POST['idReg'];
			$word="modificata";
			$wmex="tipologia regole provvigionamento";
			//trace("Editing $sqlInsStato");
		}else{
			//creazione
			$mex="Inserimento della tipologia di regola di provvigionamento.";
			$codMex="ADD_TRRL";
			
			//*****inserimento in regolaprovvigione
			addInsClause($colList,$valList,"TitoloRegolaProvvigione",$_POST['TitoloRegolaProvvigione'],"S");
			addInsClause($colList,$valList,"CodRegolaProvvigione",$_POST['CodRegolaProvvigione'],"S");
			addInsClause($colList,$valList,"AbbrRegolaProvvigione",$_POST['AbbrRegolaProvvigione'],"S");
			addInsClause($colList,$valList,"FormulaFascia",$_POST['cmbTipFascA'],"S");
			addInsClause($colList,$valList,"Formula",$_POST['Formula'],"S");
			addInsClause($colList,$valList,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
			addInsClause($colList,$valList,"IdClasse",$_POST['cmbClassAA'],"N");
			addInsClause($colList,$valList,"IdReparto",$_POST['idRep'],"N");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			$sqlInsStato =  "INSERT INTO regolaprovvigione ($colList)  VALUES($valList)";
			$Nid=getInsertId();
			$mex.=" n.$Nid";
			$word="salvata";
			$wmex="tipologia regole provvigionamento";
			//trace("Insert $sqlInsStato");
		}
	}
	if(execute($sqlInsStato))
	//if(true)
	{
		if($_POST['delOldFasce']==1)
		{
			//cancella vecchie fasce associate
			$sqlDelFascia =  "DELETE FROM fasciaprovvigione where idregolaprovvigione=".$_REQUEST['idReg'];
			//trace("delfascia $sqlDelFascia");
			if(execute($sqlDelFascia))
			//if(true)
			{
				$mexFinale="Regola $word con successo.";
				writeLog("APP","Gestione $wmex",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{
				writeLog("APP","Gestione $wmex","\"".getLastError()."\"",$codMex);
				echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			$mexFinale="Regola $word con successo.";
			writeLog("APP","Gestione $wmex",$mex,$codMex);
			echo "{success:true, messaggio:\"$mexFinale\"}";
		}
	}else{
		writeLog("APP","Gestione $wmex","\"".getLastError()."\"",$codMex);
		echo "{success:false, messaggio:\"".getLastError()."\"}";}
}

/////////////////////////////////////////////////////
//Funzione di aggiunta/editing di una classificazione
/////////////////////////////////////////////////////
function addClassRule()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$falgOp=false;
	
	isset($_POST['idClass'])?$_POST['idClass']:'';
	isset($_POST['tClass'])?$_POST['tClass']:'';
	
	if($_POST['cmbRecidivo']==-1)
		$_POST['cmbRecidivo']='';
	if($_POST['cmbFamProdAA']==-1)
		$_POST['cmbFamProdAA']='';
	if($_POST['cmbTpaga']==-1)
		$_POST['cmbTpaga']='';
	if($_POST['ChkRecupero']=='on')
		$_POST['ChkRecupero']='Y';
	else
		$_POST['ChkRecupero']='N';
	if($_POST['ChkNonAff']=='on')
		$_POST['ChkNonAff']='Y';
	else
		$_POST['ChkNonAff']='N';

	//creazione o editing semplice
	//trace("class ".$_POST['idClass']);
	if($_POST['idClass']!='')
	{
		//editing
		$mex="Editing della classificazione: ".$_POST['tClass'];
		$codMex="MOD_CLSSRL";
				
		//*****editing tabella regolaassegnazione
		addSetClause($setClause,"IdTipoPagamento",$_POST['cmbTpaga'],"N");
		addSetClause($setClause,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
		addSetClause($setClause,"CodClasse",$_POST['CodClasse'],"S");
		addSetClause($setClause,"TitoloClasse",$_POST['TitoloClasse'],"S");
		addSetClause($setClause,"AbbrClasse",$_POST['AbbrClasse'],"S");
		addSetClause($setClause,"CodClasseLegacy",$_POST['CodClasseLegacy'],"S");
		addSetClause($setClause,"FlagRecupero",$_POST['ChkRecupero'],"S");
		addSetClause($setClause,"FlagNoAffido",$_POST['ChkNonAff'],"S");
		addSetClause($setClause,"NumInsolutiDa",$_POST['NumInsolutiDa'],"N");
		addSetClause($setClause,"NumInsolutiA",$_POST['NumInsolutiA'],"N");
		addSetClause($setClause,"NumRataDa",$_POST['NumRataDa'],"N");
		addSetClause($setClause,"NumRataA",$_POST['NumRataA'],"N");
		addSetClause($setClause,"ImpInsolutoDa",$_POST['ImpInsolutoDa'],"N");
		addSetClause($setClause,"ImpInsolutoA",$_POST['ImpInsolutoA'],"N");
		addSetClause($setClause,"NumGiorniDa",$_POST['NumGiorniDa'],"N");
		addSetClause($setClause,"NumGiorniA",$_POST['NumGiorniA'],"N");
		addSetClause($setClause,"FlagRecidivo",$_POST['cmbRecidivo'],"S");
		addSetClause($setClause,"FlagManuale",$_POST['cmbTclass'],"S");
		addSetClause($setClause,"Ordine",$_POST['gravita'],"N");
		addSetClause($setClause,"Condizione",$_POST['Condizione'],"S");
		addSetClause($setClause,"DataIni","2001-01-01","S");
		addSetClause($setClause,"DataFin","9999-12-31","S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		$sqlInsStato = "UPDATE classificazione $setClause WHERE IdClasse=".$_POST['idClass'];
		$word="modificata";
		$wmex="classificazioni";
		//trace("Editing $sqlInsStato");
	}else{
		//creazione
		$mex="Inserimento di una classificazione";
		$codMex="ADD_CLSSRL";
		
		//*****inserimento in regolaassegnazione
		addInsClause($colList,$valList,"IdTipoPagamento",$_POST['cmbTpaga'],"N");
		addInsClause($colList,$valList,"IdFamiglia",$_POST['cmbFamProdAA'],"N");
		addInsClause($colList,$valList,"CodClasse",$_POST['CodClasse'],"S");
		addInsClause($colList,$valList,"TitoloClasse",$_POST['TitoloClasse'],"S");
		addInsClause($colList,$valList,"AbbrClasse",$_POST['AbbrClasse'],"S");
		addInsClause($colList,$valList,"CodClasseLegacy",$_POST['CodClasseLegacy'],"S");
		addInsClause($colList,$valList,"FlagRecupero",$_POST['ChkRecupero'],"S");
		addInsClause($colList,$valList,"FlagNoAffido",$_POST['ChkNonAff'],"S");
		addInsClause($colList,$valList,"NumInsolutiDa",$_POST['NumInsolutiDa'],"N");
		addInsClause($colList,$valList,"NumInsolutiA",$_POST['NumInsolutiA'],"N");
		addInsClause($colList,$valList,"NumRataDa",$_POST['NumRataDa'],"N");
		addInsClause($colList,$valList,"NumRataA",$_POST['NumRataA'],"N");
		addInsClause($colList,$valList,"ImpInsolutoDa",$_POST['ImpInsolutoDa'],"N");
		addInsClause($colList,$valList,"ImpInsolutoA",$_POST['ImpInsolutoA'],"N");
		addInsClause($colList,$valList,"NumGiorniDa",$_POST['NumGiorniDa'],"N");
		addInsClause($colList,$valList,"NumGiorniA",$_POST['NumGiorniA'],"N");
		addInsClause($colList,$valList,"FlagRecidivo",$_POST['cmbRecidivo'],"S");
		addInsClause($colList,$valList,"FlagManuale",$_POST['cmbTclass'],"S");
		addInsClause($colList,$valList,"Ordine",$_POST['gravita'],"N");
		addInsClause($colList,$valList,"Condizione",$_POST['Condizione'],"S");
		addInsClause($colList,$valList,"DataIni","2001-01-01","S");
		addInsClause($colList,$valList,"DataFin","9999-12-31","S");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		$sqlInsStato =  "INSERT INTO classificazione ($colList)  VALUES($valList)";
		$Nid=getInsertId();
		$mex.=" n.$Nid";
		$word="salvata";
		$wmex="classificazioni";
		//trace("Insert $sqlInsStato");
	}
	
	if(execute($sqlInsStato))
	//if(true)
	{
		$mexFinale="Classificazione $word con successo.";
		writeLog("APP","Gestione $wmex",$mex,$codMex);
		echo "{success:true, messaggio:\"$mexFinale\"}";
	}else{
		writeLog("APP","Gestione $wmex","\"".getLastError()."\"",$codMex);
		echo "{success:false, messaggio:\"".getLastError()."\"}";
	}
}
///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle classificazioni
///////////////////////////////////////////////////////////////////
function delClassificazioni()
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
	$titoliLog = getFetchArray("SELECT TitoloClasse FROM classificazione where IdClasse in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i]['TitoloClasse'].",";
		else
		 	$list .=$titoliLog[$i]['TitoloClasse'];
	}	
		
	$codMex="CANC_CLASS";
	$mex="Cancellazione delle classificazioni ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		// serve per il log
		//se è una cancellazione tipologica cancellare anche le fasce associate
		$titolo = getFetchArray("SELECT TitoloClasse FROM classificazione where IdClasse=$values[$i]");
		$arrErrors[$i]['IdRule']="";
		$arrErrors[$i]['Result']='K';
		//eliminazione dalla tabella
		$sqlDelSA =  "DELETE FROM classificazione where idclasse=".$values[$i];
		//trace("Delete $sqlDelSA");
		if(!execute($sqlDelSA))
		//if(false)
		{
			$arrErrors[$i]['IdRule']="nella cancellazione della classificazione \'".$titolo[0]['TitoloClasse']."\'";
			$arrErrors[$i]['Result']='E';
		}
	}	
	//$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAzione']);
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdRule'];
		}
	}
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Classificazioni cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}

///////////////////////////////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle regole selezionate associate all'utente(tipologia1)
///////////////////////////////////////////////////////////////////////////////////////////
function delAssociateRuleOp()
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
	
	$codMex="CANC_ASSRULE";
	$mex="Cancellazione delle regole associate ($list)";
	$tab='';
	$Idr='';
	$chkField='';
	$str='';
	
	$tab='regolaassegnazione';
	$Idr='IdRegolaAssegnazione';
	$str='di un associazione per l\'operatore \"';
	
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		$arrErrors[$i]['IdRule']="";
		$arrErrors[$i]['Result']='K';
		
		//eliminazione dalla tabella
		$sqlDelSA =  "DELETE FROM $tab where $Idr=".$values[$i];
		//trace("Delete $tab $sqlDelSA");
		if(!execute($sqlDelSA))
		//if(false)
		{
			$arrErrors[$i]['IdRule']="nella cancellazione della regola n.".$values[$i];
			$arrErrors[$i]['Result']='E';
		}
	}	
	//$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	//trace("--Errore in prima posizione = ".$arrErrors[0]['IdAzione']);
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E')
		{
			$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdRule'];
		}
	}
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Regole cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}

////////////////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle fasce associate alle regole di provvigione
////////////////////////////////////////////////////////////////////////////
function delFasceAssociate()
{
	global $context;

	$stringaRitorno='';
	$valuesAbbr = explode('|', $_REQUEST['vectAbbr']);
	$numAbbr = count($valuesAbbr)-1;
	isset($_REQUEST['idRule'])?$_REQUEST['idRule']:'';
	$list = substr(join(",", $valuesAbbr),1); // toglie virgola iniziale
	$arrErrors=array();

	//trace("valori passati2: ".print_r($valuesAbbr,true));
	//trace("numero2. $numAbbr");
	//trace("Idregola. ".$_REQUEST['idRule']);
	$list="";
	for($i=1;$i<=$numAbbr;$i++)
	{
		if($i<$numAbbr)
			$list .=$valuesAbbr[$i]."- reg.".$_REQUEST['idRule'].",";
		else
		 	$list .=$valuesAbbr[$i]."- reg.".$_REQUEST['idRule'];
	}
	$codMex="CANC_FASCASS";
	$mex="Cancellazione delle fasce associate ($list)";
	beginTrans();
	for($i=1;$i<=$numAbbr;$i++)
	{
		// serve per il log
		//se è una cancellazione tipologica cancellare anche le fasce associate
		$arrErrors[$i]['IdDelFA']="";
		$arrErrors[$i]['Result']='K';
		$sqlDelFascia =  "DELETE FROM fasciaprovvigione where idregolaprovvigione=".$_REQUEST['idRule']." and AbbrFasciaProvvigione='".$valuesAbbr[$i]."'";
		//trace("delete>> $sqlDelFascia");
		//if(true)
		if(!execute($sqlDelFascia))
		{
			$arrErrors[$i]['IdDelFA']="nella cancellazione della fascia: '".$valuesAbbr[$i]."'.";
			$arrErrors[$i]['Result']='E';
		}
	}	
	//trace("Errori: ".print_r($arrErrors,true));
	
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
			$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdDelFA'];
		}
	}
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Fasce cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}
?>
