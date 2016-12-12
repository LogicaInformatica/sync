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
		case "readRipG":readRip();	//rip
			break;
		case "saveRipartizione":saveRipartizione();	//rip
			break;
		case "readDettRegRip":	readRipDetail();	//rip
			break;
		case "delRipartizione":	delRip();			//rip
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
////////////////////////////////////////////
//Funzione di lettura della griglia Ripartizioni
////////////////////////////////////////////
function readRip()
{
	global $context;
	$fields = "rr.*, rep.TitoloUfficio as Agenzia, concat(r.TitoloUfficio,' (',rp.CodRegolaProvvigione,')') as RegolaProvvigione,
				fp.TitoloFamiglia as Famiglia,cl.TitoloClasse as Classe";
	$query = "regolaripartizione rr 
				left join regolaprovvigione rp on (rr.IdRegolaProvvigione=rp.IdRegolaProvvigione)
				left join reparto r on (rp.IdReparto=r.IdReparto)
				left join reparto rep on (rr.IdReparto=rep.IdReparto)
				left join famigliaprodotto fp on(rr.IdFamiglia=fp.IdFamiglia)
				left join classificazione cl on(rr.IdClasse=cl.IdClasse)";
	$gruppo = ($_REQUEST['group']) ? ($_REQUEST['group']) : null;
	$order = "rr.IdRegolaProvvigione asc";
	
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
//Funzione di salvataggio ripartizioni
///////////////////////////////////////
function saveRipartizione()
{
	global $context;
	$CodLog='';

	$reparto = isset($_REQUEST['cmbRep'])?$_REQUEST['cmbRep']:'';
	if ($reparto==-1) $reparto='';
	$regProvv = isset($_REQUEST['cmbRegPro'])?$_REQUEST['cmbRegPro']:'';
	if ($regProvv==-1) $regProvv='';
	$famiglia = isset($_REQUEST['cmbFamProd'])?$_REQUEST['cmbFamProd']:'';
	if ($famiglia==-1) $famiglia='';
	$classe = isset($_REQUEST['cmbClass'])?$_REQUEST['cmbClass']:'';
	if ($classe==-1) $classe='';
	$FlagM = isset($_REQUEST['FlagMora'])?$_REQUEST['FlagMora']:'N';
	if($FlagM == 'on')
	{
		$FlagM = 'Y';
	}
	$percSpeseInc = isset($_REQUEST['PercSpeseIncasso'])?$_REQUEST['PercSpeseIncasso']:'0.00';
	$ImpSpeseInc = isset($_REQUEST['ImpSpeseIncasso'])?$_REQUEST['ImpSpeseIncasso']:'';
	$DataIni = isset($_REQUEST['DataIni'])?$_REQUEST['DataIni']:null;
	$DataFin = isset($_REQUEST['DataFin'])?$_REQUEST['DataFin']:null;
	
	$IdRegRip = ($_REQUEST['idRegRip']) ? ($_REQUEST['idRegRip']) : '';
	if($IdRegRip!='')
	{
		$RipLogTitle="Modificata regola ripartizione n. ".$IdRegRip;
		$CodLog='MOD_RIPART';				
		$setClause = "";
		
		addSetClause($setClause,"IdReparto",$reparto,"N");
		addSetClause($setClause,"IdRegolaProvvigione",$regProvv,"N");
		addSetClause($setClause,"IdFamiglia",$famiglia,"N");
		addSetClause($setClause,"IdClasse",$classe,"N");
		addSetClause($setClause,"FlagInteressiMora",$FlagM,"S");
		addSetClause($setClause,"PercSpeseIncasso",$percSpeseInc,"N");
		addSetClause($setClause,"ImpSpeseIncasso",$ImpSpeseInc,"N");
		addSetClause($setClause,"DataIni",$DataIni,"D");
		addSetClause($setClause,"DataFin",$DataFin,"D");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");

		$sql = "UPDATE regolaripartizione $setClause WHERE IdRegolaRipartizione=$IdRegRip";
		$ret = execute($sql);
	}
	else
	{
		$CodLog = 'ADD_RIPART';
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"IdReparto",$reparto,"N");
		addInsClause($colList,$valList,"IdRegolaProvvigione",$regProvv,"N");
		addInsClause($colList,$valList,"IdFamiglia",$famiglia,"N");
		addInsClause($colList,$valList,"IdClasse",$classe,"N");
		addInsClause($colList,$valList,"FlagInteressiMora",$FlagM,"S");
		addInsClause($colList,$valList,"PercSpeseIncasso",$percSpeseInc,"N");
		addInsClause($colList,$valList,"ImpSpeseIncasso",$ImpSpeseInc,"N");
		addInsClause($colList,$valList,"DataIni",$DataIni,"D");
		addInsClause($colList,$valList,"DataFin",$DataFin,"D");
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		$sql =  "INSERT INTO regolaripartizione ($colList)  VALUES($valList)";
		$ret = execute($sql);
		if ($ret)
		{
			$IdRegRip = getInsertId();
			$RipLogTitle = "Creata regola ripartizione n.".$IdRegRip;
		}
	}
	
	if ($ret)
	{
		writeLog("APP","Gestione ripartizioni",$RipLogTitle,$CodLog);
		echo "{success:true, error:\"$RipLogTitle.\"}";
	}
	else
	{
		writeLog("APP","Gestione ripartizioni","\"".getLastError()."\"",$CodLog);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//////////////////////////////////////////
//Funzione di lettura del dettaglio regola
//////////////////////////////////////////
function readRipDetail()
{
	global $context;
	isset($_POST['idRegRip'])?$_POST['idRegRip']:'';
	
	$fields = "ifnull(PercSpeseIncasso,0.00)as PercSpeseIncasso,ifnull(ImpSpeseIncasso,0.00)as ImpSpeseIncasso,
				ifnull(IdClasse,-1) as IdClasse,ifnull(IdFamiglia,-1) as IdFamiglia,ifnull(IdReparto,-1) as IdReparto,ifnull(IdRegolaProvvigione,-1) as IdRegolaProvvigione,
				case when FlagInteressiMora='Y' then true else false end as FlagMora,DataIni,DataFin";
	$query = "regolaripartizione";
	$where = "IdRegolaRipartizione=".$_POST['idRegRip'];
	
	if($_POST['idRegRip']!='')
	{
		$counter = getScalar("SELECT count(*) FROM $query where $where");
	}else{$counter=0;}
	
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

///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle ripartizioni della griglia principale
///////////////////////////////////////////////////////////////////
function delRip()
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
	$codMex="CANC_RIPART";
	$mex="Cancellazione delle regole di ripartizione ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		$flagAzioneTipoDel=true;
		//eliminazione di terzo livello da azioneTipoazione
		$sqlDelRip =  "DELETE FROM regolaripartizione where IdRegolaRipartizione=".$values[$i];
		//trace("Delete regola $sqlDelRip");
		if(!execute($sqlDelRip))
		//if(false)
		{
			$arrErrors[$i]['IdRipartizione']=	'cancellazione della regola n. "'.$values[$i].'"';
			$arrErrors[$i]['Result']='E';
		}		
	}	

	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
			$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdRipartizione'];
		}
	}
	if($messaggioErr!=''){
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
		rollback();
	}else{
		$mexFinale="Regole cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}
?>
