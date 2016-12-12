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
		case "readA":readA();
			break;
		case "deleteA":delAutomat();
			break;
		case "saveA":saveFuncA();
			break;
		case "readAzAut": readAzAut();
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
////////////////////////////////////////////
//Funzione di lettura della griglia Automatismi
////////////////////////////////////////////
function readA()
{
	global $context;
	$fields = "a.*,m.FileName,m.TitoloModello";
	$query = "automatismo a left join modello m on(a.IdModello=m.IdModello)";
	$gruppo = ($_REQUEST['group']) ? ($_REQUEST['group']) : null;
	
	switch ($gruppo)
	{
		case "TipoAutomatismo":
			$order = "a.TipoAutomatismo asc";
			break;
		default:
			die ("{failure:true, task: '$gruppo'}");
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
		
		if ($_REQUEST['groupBy']>' ') {
			$sql .= " ORDER BY ".$order;
			if ($_REQUEST['sort']>' '){ 
				$sql .= ",".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= ",a.IdAutomatismo asc";
			}
		} 
		else
		{
			if ($_REQUEST['sort']>' '){ 
				$sql .= " ORDER BY ".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= " ORDER BY a.IdAutomatismo asc";
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
/////////////////////////////////////////
//Funzione di cancellazione degli Automatismi
/////////////////////////////////////////
//function delAutomat_OLD()
//{
//	global $context;
//	
//	$idU = $_REQUEST['id'];
//	
//	//Delete
//	$codMex = "CANC_AUT";
//	beginTrans();
//	$sqchecku = "SELECT count(*) FROM automatismo where IdAutomatismo=$idU";
//	$momu=getScalar($sqchecku);
//	if($momu>0)
//	{
//		$sqdelAzAu = "DELETE FROM azioneautomatica where IdAutomatismo=$idU";
//		if (execute($sqdelAzAu)) {
//			// serve per il log prima di cancellare l'utente
//			$NomeAuto= getscalar("SELECT TitoloAutomatismo from automatismo where IdAutomatismo=$idU");
//			//cancella il profilo in tabella
//			$sqdel = "DELETE FROM automatismo where IdAutomatismo=$idU";
//	
//			if (execute($sqdel)) {
//				
//				// trace su log
//				writeLog("APP","Gestione automatismi","Cancellazione automatismo $NomeAuto riuscita.",$codMex);
//				commit();
//				echo "{success:true, error:\"L\'automatismo selezionato e\' stato cancellato\"}";
//			} else {
//				rollback();
//				writeLog('APP',"Gestione automatismi","\"".getLastError()."\"",$codMex);
//				echo "{success:false, error:\"".getLastError()."\"}";
//			}
//		}else{
//			writeLog('APP',"Gestione automatismi","Errore durante la cancellazione su tabella 'azioneautomatica'.",$codMex);
//			rollback();
//		}
//	}else{	
//		writeLog('APP',"Gestione automatismi","Questo automatismo non esiste.",$codMex);
//		echo "{success:true, error:\"Questo automatismo non esiste.\"}";
//	}
//}
///////////////////////////////////////////////////////////////////
//Funzione di cancellazione 
///////////////////////////////////////////////////////////////////
function delAutomat()
{
	global $context;
	$Operatore = $context['Userid'];
	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$valuesTitles = explode('|', $_REQUEST['vectit']);
	$titLogA=array_shift($valuesTitles);
	$listLog=implode(",<br />", $titLogA);
	$num = count($values)-1;
	$arrErrors=array();
	//trace("valori passati: ".print_r($values,true));
	//trace("valori passati: ".print_r($valuesTitles,true));
	//trace("numero. $num");
	//Delete
	//variabili
	$tab='automatismo';
	$idField = 'IdAutomatismo';
	$chkField= 'TitoloAutomatismo';
	$titleName = 'automatismo';
	$titField = 'TitoloAutomatismo';
	//tabella legata
	$tabLeg='azioneautomatica';

	$codMex="CANC_AUT";
	$mex="Cancellazione degli automatismi ($listLog)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		// serve per il log
		$arrErrors[$i]['Rule']='';
		$arrErrors[$i]['Result']='K';
		//eliminazione della relazione azione/automatismo
		$sqdelAzAu = "DELETE FROM $tabLeg where $idField=".$values[$i];
		//trace("Delete $tabLeg: $sqdelAzAu");
		if (execute($sqdelAzAu))
		//if(true) 
		{
			//eliminazione dalla tabella automatismo
			$sqlDel =  "DELETE FROM $tab where $idField=".$values[$i];
			//trace("Delete $tab: $sqlDel");
			if(!execute($sqlDel))
			//if(true)
			{
				$arrErrors[$i]['Rule']=' nella cancellazione dell\' elemento '.$titleName.' "'.$valuesTitles[$i].'"';
				$arrErrors[$i]['Result']='E';
			}
		}else{
			$arrErrors[$i]['Rule']=' nella cancellazione della relazione azione/automatismo dell\' elemento '.$titleName.' "'.$valuesTitles[$i].'"';
			$arrErrors[$i]['Result']='E';
		}
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	//trace("indiciErrori ".print_r($indiciErrori,true));
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		//trace("tindex $tindex");
		if($arrErrors[$tindex]['Result']=='E'){
			if($arrErrors[$tindex]['Rule']!='')
			{
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['Rule'];
			}
		}
	}
	//trace("arrErrors ".print_r($arrErrors,true));
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
///////////////////////////////////////
//Funzione di salvataggio automatismo
///////////////////////////////////////
function saveFuncA()
{
	global $context;
	$Operatore = $context['Userid'];

	$IdAut = ($_REQUEST['idAut']) ? ($_REQUEST['idAut']) : null;
	
	//dati per salvataggio generico
	$NomeTitoloAut = isset($_REQUEST['TitoloAutomatismo'])?$_REQUEST['TitoloAutomatismo']:'';
	$NomeTitoloAut = quote_smart(trim($_REQUEST['TitoloAutomatismo']));
	$NomeTitoloAut = substr($NomeTitoloAut,1,strlen($NomeTitoloAut)-2);
	$ComandoAut = trim($_REQUEST['Comando']);
	$CondizioneAut = trim($_REQUEST['Condizione']);
	$DestinatariAut = trim($_REQUEST['Destinatari']);
	$Modello = trim($_REQUEST['IdModello']);
	$FileM = trim($_REQUEST['FileName']);
	$FlagCum = $_REQUEST['FlagCumulativo'];
	$TipoAut = $_REQUEST['TipoAutomatismo'];
	//controlli sulla maschera d'inserimento

	if($FlagCum == 'on')
	{
		$FlagCum = 'Y';
	}else{$FlagCum = 'N';}

	$response = saveValidation();
	if($response=='')
	{
		if($IdAut!=null)
		{
			$codMex = "MOD_AUT";
			if(!is_numeric($Modello))
			{//caso di update senza cambiamento della box del modello
				$sretriveidMod = "select IdModello from modello where TitoloModello = '$Modello'";
				$Modello = getScalar($sretriveidMod);
			}
			if (!($Modello>0))
				$Modello = "NULL";

			//altri campi da salvare
			$sqlUp="UPDATE automatismo SET TipoAutomatismo='$TipoAut',TitoloAutomatismo='$NomeTitoloAut',Comando='$ComandoAut',Condizione='$CondizioneAut',Destinatari='$DestinatariAut',FlagCumulativo='$FlagCum',IdModello=$Modello,LastUser='$Operatore' where IdAutomatismo=$IdAut";
			//trace("update $sqlUp");
			if (execute($sqlUp)){
				// trace su log
				writeLog("APP","Gestione automatismi","Modificato automatismo".$NomeTitoloAut,$codMex);
			}else{
				writeLog("APP","Gestione automatismi","\"".getLastError()."\"",$codMex);
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}else{
			$codMex = "INS_AUT";
			//il profilo utente è in creazione
			$sqinsNus = "INSERT INTO automatismo (TipoAutomatismo,TitoloAutomatismo,Comando,Condizione,Destinatari,LastUser,IdModello,FlagCumulativo) VALUES ('$TipoAut','$NomeTitoloAut','$ComandoAut','$CondizioneAut','$DestinatariAut','$Operatore',$Modello,'$FlagCum')";
			//trace("insert $sqinsNus");
			if(execute($sqinsNus)){
				$utente = getInsertId();
				// trace su log
				writeLog("APP","Gestione automatismi","Inserimento automatismo '$NomeTitoloAut'",$codMex);
			}else{
				writeLog("APP","Gestione automatismi","\"".getLastError()."\"",$codMex);
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}
		echo "{success:true, error:\"Successo\"}";
	}else{
		writeLog("APP","Gestione automatismi",$response,$codMex);
		echo "{success:false, error:\"".$response."\"}";
	}
}

///////////////////////////////////////
//Funzione di dettaglio automatismo
///////////////////////////////////////
function readAzAut()
{
	$IdAut = ($_REQUEST['Aut']) ? ($_REQUEST['Aut']) : null;
	$fields = "az.IdAzione,a.IdAutomatismo,az.CodAzione,az.TitoloAzione";
	$query = "azioneautomatica a left join azione az on(a.IdAzione=az.IdAzione) where a.IdAutomatismo=$IdAut";
	
	$sqlCount = "SELECT count(*) FROM $query";
	$num = getScalar($sqlCount);
	
	$sqlUp = "SELECT $fields FROM $query";
	$arr=getFetchArray($sqlUp);
	
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	
	echo '({"total":"' . $num . '","results":' . $data . '})';
}
//controlla i campi necessari ed il loro formato
function saveValidation()
{
	//dati per controllo
	$Modello = trim($_REQUEST['IdModello']);
	$responso='';
	
	if ($Modello>'') {
		if(!is_numeric($Modello))
		{
			$sretriveidMod = "SELECT count(*) FROM modello where TitoloModello like '$Modello'";
			$Modello=getScalar($sretriveidMod);
		}else{
			$sretriveidMod = "SELECT count(*) FROM modello where IdModello=$Modello";
			$Modello=getScalar($sretriveidMod);
		}
		
		if($Modello == 0)
		{
			$responso .= "Nessun modello compatibile";
		}
	}
	return $responso;
}
?>
