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
		case "delete":deletePartite();
			break;
		case "readMainGrid":readGrid();
			break;
		case "saveAgg":aggiornaTipo();
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
///////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle tipologie di regole
///////////////////////////////////////////////////////////////////////
function readGrid()
{
	global $context;
	$fields = "*";
	$fieldTipo = $_REQUEST['tipoReg'];
	$secondary=false;
	switch($fieldTipo)
	{
		case 'fasciaRecupero':
			$query = "target";
			$ordine="Gruppo asc";
			$campoTitolo="FasciaRecupero";
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
		
		$sql = "SELECT $fields FROM $query ORDER BY ";

		if ($_POST['groupBy']>' ') {
					$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
			} 
			if ($_POST['sort']>' '){ 
					$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
			}else{
				$sql .= $ordine;
			}
				
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		//correzione caratteri html
		for($i=0; $i<count($arr); $i++){
			$arr[$i][$campoTitolo] = htmlstr($arr[$i][$campoTitolo]);
			if($secondary){
				$arr[$i][$campoTitoloComp] = htmlstr($arr[$i][$campoTitoloComp]);
			}
		}
		  
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
//Funzione di salvataggio della regola
/////////////////////////////////////////////////////////////
function aggiornaTipo()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$categoria=false;
	isset($_POST['nuovo'])?$_POST['nuovo']:null;
	$nuovo=$_REQUEST['nuovo'];
	$fieldTipo = $_REQUEST['tipoReg'];
		
	$titolo=$_POST['TitoloReg'];
	$indexTab=$_POST['TitoloReg'];
	
	//trace("titolo $titolo");
	switch($fieldTipo)
	{
		case 'fasciaRecupero':
			//variabili
			$tab = 'target';
			$idField = 'FasciaRecupero';
			$titleName = 'fascia di recupero';
			$codMexName = 'FASCIA_RECUPERO';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'FasciaRecupero';
			break;
	}
	//trace("TA ".$_POST['TitoloTipoPartita']);
	//*****inserimento
	//$counter = getScalar("Select count(*) FROM $tab where $idField = $indexTab");
	if($nuovo=='true')
	{
		//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
		$counter = getScalar("Select count(*) FROM $tab where $idField = '$indexTab'");
		//trace("num $counter");
		if($counter==0)
		{
			$codMex="ADD_".$codMexName;
			$mex="Inserimento nuova regola $titleName: ".$titolo;
			addInsClause($colList,$valList,$titField,$titolo,"S");
			addInsClause($colList,$valList,'Valore',$_POST['valore'],"N");
			addInsClause($colList,$valList,'Ordine',$_POST['Ordine'],"N");
			addInsClause($colList,$valList,'Gruppo',$_POST['gruppo'],"S");
			addInsClause($colList,$valList,"DataIni",ISODate($_POST['DataIni']),"S");
			addInsClause($colList,$valList,"DataFin",ISODate($_POST['DataFin']),"S");
			addInsClause($colList,$valList,"FY",$_POST['ny'],"S");
			addInsClause($colList,$valList,"ENDFY",$_POST['endny'],"S");
			
			$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
			//trace("ins par: $sqlInsTipo");
			if (execute($sqlInsTipo))
			//if(true)
			{
				$mexFinale="$neww[0] $titleName '$titolo', $neww[1].";
				writeLog("APP","Gestione $titleName ",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{
				writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
				echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			writeLog("APP","Gestione $titleName ","Il nome utilizzato &egrave gi&agrave presente.",$codMex);
			echo "{success:false, messaggio:\"Il nome utilizzato &egrave gi&agrave presente.\"}";}
	}else{
			$codMex="MOD_".$codMexName;
			
			//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
			$counter = getFetchArray("Select * FROM $tab where $idField = '$indexTab'");
			//trace("arr ".print_r($counter,true)." lung ".count($counter));
			if((count($counter)==0)||(count($counter)==1 && $titolo==$_POST['oldName']))
			{
				$mex="Modifica $titleName: ".$titolo;
				addSetClause($setClause,$titField,$titolo,"S");
				addSetClause($setClause,'Valore',$_POST['valore'],"N");
				addSetClause($setClause,'Ordine',$_POST['Ordine'],"N");
				addSetClause($setClause,'Gruppo',$_POST['gruppo'],"S");
				addSetClause($setClause,"DataIni",ISODate($_POST['DataIni']),"S");
				addSetClause($setClause,"DataFin",ISODate($_POST['DataFin']),"S");
				addSetClause($setClause,"FY",$_POST['ny'],"S");
				addSetClause($setClause,"ENDFY",$_POST['endny'],"S");
				$sqlModTipo = "UPDATE $tab $setClause WHERE $idField='".$_POST['oldName']."'";
				//trace("Mod part: $sqlModTipo");
				if (execute($sqlModTipo))
				//if(true)
				{
					$mexFinale="Registrazione correttamente eseguita";
					writeLog("APP","Gestione $titleName ",$mex,$codMex);
					echo "{success:true, messaggio:\"$mexFinale\"}";
				}else{
					writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
					echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{
				writeLog("APP","Gestione $titleName ","Il nome utilizzato &egrave gi&agrave presente.",$codMex);
				echo "{success:false, messaggio:\"Il nome utilizzato &egrave gi&agrave presente.\"}";}
	}	
}
///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle partite selezionate
///////////////////////////////////////////////////////////////////
function deletePartite()
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
	$isAStringKey=false;
	$fieldTipo = $_REQUEST['tipoReg'];
	switch($fieldTipo)
	{
		case 'fasciaRecupero':
			//variabili
			$tab='target';
			$idField = 'FasciaRecupero';
			$chkField= 'FasciaRecupero';
			$titleName = 'fascia di recupero';
			$titField = 'FasciaRecupero';
			$isAStringKey = true;
			break;
	}
	$titoliLog = getFetchArray("SELECT $titField FROM $tab where $idField in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i][$titField].",";
		else
		 	$list .=$titoliLog[$i][$titField];
	}
	$codMex="CANC_REGO";
	$mex="Cancellazione delle regole ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		// serve per il log
		if(!$isAStringKey)
			$titolo = getFetchArray("SELECT $chkField FROM $tab where $idField=$values[$i]");
		else
			$titolo = getFetchArray("SELECT $chkField FROM $tab where $idField='$values[$i]'");
		$arrErrors[$i]['Rule']='';
		$arrErrors[$i]['Result']='K';
		
		//eliminazione dalla tabella
		if(!$isAStringKey)
			$sqlDel =  "DELETE FROM $tab where $idField=".$values[$i];
		else
			$sqlDel =  "DELETE FROM $tab where $idField='$values[$i]'";
		//trace("Delete $tab: $sqlDel");
		$noGoodDel=!execute($sqlDel);
			
		if($noGoodDel)
		//if(true)
		{
			$arrErrors[$i]['Rule']="nella cancellazione del tipo $titleName ".$titolo[0][$titField];
			$arrErrors[$i]['Result']='E';
		}
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	$messaggioErr='';
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
			if($arrErrors[$tindex]['Rule']!='')
			{
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['Rule'];
			}
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
?>
