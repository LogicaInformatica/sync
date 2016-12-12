<?php
// Nuova versione del vecchio programma ana_modelli (ancora richiamato da alcuni .js)
require_once("common.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

$task = ($_POST['task']) ? ($_POST['task']) : null;

//switchboard for the CRUD task requested
switch($task){
    case "read":
        showData();
        break;
    case "readModels":
        readFunc();
        break;
    case "saveMM":
        saveMM();
        break;
   case "saveModelloWord":
        saveModelloWord();
        break;
    case "caricaJson":  
        readJson();
        break;
    case "caricaModelloEmail":  
        readModelloEmail();
        break;
    case "delete":
        delete();
        break;
    default:
    	echo "{failure:true}";
        break;
}//end switch

///////////////////////////////////
//Funzione di lettura della griglia
///////////////////////////////////
function showData() {

	$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
	$end =   isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');

	$sql = "SELECT *,m.DataIni as IniVal,m.DataFin as FinVal,m.lastupd as lastMod,m.lastUser as lastU, 
			(case tipomodello when 'E' then 'E-mail' when 'L' then 'Lettera' when 'S' then 'Sms' when 'X' then 'Sub Modello' 
			when 'P' then 'Pop-up iniziale' when 'H' then 'Lettera stampa online' when 'W' then 'E-mail workflow' end) as descrTMod 
			FROM modello m left join tipoallegato ta on(m.idtipoallegato=ta.idtipoallegato) where tipomodello!='P'";

	if (isset($_REQUEST['groupBy'])) {
		$sql .= ' ORDER BY ' . $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'];
	}

	if ($start!='' || $end!='') {
    		$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;	
	}

	$counter = getScalar("SELECT count(*) FROM modello m left join tipoallegato ta on(m.idtipoallegato=ta.idtipoallegato)");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
		$arr = array();
	} else {
		$arr = getFetchArray($sql);
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

}//end showData

/////////////////////////////////////////
//Funzione di lettura della lista modelli
/////////////////////////////////////////
function readFunc()
{
	global $context;
	$fieldsCase = "IdModello, tipomodello as tipo, (case tipomodello when 'E' then 'E-mail' 
													when 'L' then 'Lettera' 
													when 'S' then 'Sms' 
													when 'X' then 'Sub Modello' 
													when 'P' then 'Pop-up iniziale' 
													when 'H' then 'Lettera stampa online' end) as tipomodello";
	
	$query = "modello group by tipomodello";
	
	$counter = getScalar("SELECT count(distinct tipomodello) FROM modello");
	if ($counter == NULL)
		$counter = 0;
	
	$sql = "SELECT $fieldsCase FROM $query order by tipomodello asc";
	//Azioni
	$arr=getFetchArray($sql); 
		
	
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
/////////////////////////////////////////////
//Funzione di salvataggio/update modello mail
/////////////////////////////////////////////
function saveMM()
{
	global $context;
	
	isset($_REQUEST['client'])?$_REQUEST['client']:false;
	$client = $_REQUEST['client'];
	if($client!=false)
		$client=true;
	$testoMAIL = $_REQUEST['TTMail'];
	$testoSMS = $_REQUEST['sms'];
	$testoLettera = $_REQUEST['lettera'];
	$sottoModello = $_REQUEST['SubModello'];
	$tipoMail = $_REQUEST['cTipo'];
	//trace("tipomail $tipoMail");
	$mod = $_POST['model'];
	$nome = $_POST['NomeM'];
	$nomeFile = $_POST['NomeFile'];
	$sogg = $_POST['Subj'];
	$riservato = $_POST['riservato'];
	$combo = $_POST['cAllegato'];
	$Operatore = $context['Userid'];
	isset($_POST['tipoMod'])?$_POST['tipoMod']:'L';
	$tipoLettera = $_POST['tipoMod'];
	isset($_POST['condizioneH'])?$_POST['condizioneH']:'';
	//trace("tl $tipoLettera");
	$mex="Salvataggio dei modelli $tipoMail";
	$ckcond=false;
	try
	{
		//trace("testo: ".$testo." |modello: ".$mod." |nomeF: ".$nome." |Subj: ".$sogg." |Res: ".$riservato." |cmb: ".$combo." |File: ".$nomeFile);
		//conversione testo con controllo di MAIL/SMS
		$extens='.html';
		if($testoMAIL!=''){
			switch ($tipoMail)
			{
				case "Workflow":
					$tmodello='W';
					break;
				case "Email":
					$tmodello='E';
					break;
				case "Sottomodello":
					$tmodello='X';
					break;
			}
			if ($mod>0)  { // modello esistente, controlla il tipo
				if (stripos($nomeFile,".json")!==FALSE)
					$extens='.json';
			}
			if ($extens=="json") {
				$TXT = json_encode_plus( array("subject"=>$sogg, "body"=>$testoMAIL) );
/* 20/7/2015 sostituito con l'istruzione precedente
				$TXT='{"subject": "'.addslashes($sogg).'","body": "'.addslashes($testoMAIL).'"}';
				mb_convert_encoding($TXT,"HTML-ENTITIES");
						
				// Sostituisce i caratteri di ritorno a capo con le stringhe corrispondenti
				$order   = array("\r\n", "\n", "\r");
				$subst   = array('\r\n', '\n', '\r');
				$TXT= str_replace($order,$subst,$TXT);
*/
			} else { // standard dal 3/6/2013: salva in formato html
				$TXT="$sogg\n$testoMAIL";
					
			}
		}elseif($testoSMS!=''){
			$respDec=mb_detect_encoding($testoSMS);
			if($respDec != 'ASCII')
			{
				echo "{success:false, error:\"Non è possibile inserire caratteri accentati o speciali.\"}";
				die();
			}
			$order   = array("\r\n", "\n", "\r");
			$testoSMS= str_replace($order,' ',$testoSMS);
			
			$TXT='{"testoSMS": "'.$testoSMS.'"}';
			$tmodello='S';
			$extens='.json';
		}elseif($testoLettera!=''){
			$TXT=$testoLettera;
			$tmodello=$tipoLettera;
			$extens='.txt';
		}elseif($sottoModello!=''){
			$TXT=$sottoModello;
			$tmodello='X';
			$extens='.txt';
		}elseif($client){
			//file vuoto
			$tmodello=$tipoLettera;
			$extens='.xml';
			$TXT="";
			$ckcond=true;
		}
		
		//controlla il campo condizione
		if($ckcond)
		{
			if($_POST['condizioneH']=='')
				$_POST['condizioneH']='FALSE';
			//trace("cond ".$_POST['condizioneH']);
			//$evaluate = getFetchArray("select * from v_pratiche where ".$_POST['condizioneH'].";");
			$evaluate = getScalar("select count(*) from v_pratiche where ".$_POST['condizioneH'].";");
			//trace("res: $evaluate");
			//trace("err: ".getLastError());
			if(getLastError()!='')
			{
				writeLog("APP","Gestione modelli lettera","Errore nella condizione specificata.",$codMex);
				echo "{success:false, error:\"Errore nella condizione specificata\"}";
				die();
			}
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
				if(!$client)
				{//se non siamo in modalità protetta poi chè si sta accedendo da client
					$codMex="MOD_MMODEL";
					$number=file_put_contents(TEMPLATE_PATH."/$nomeFile",$TXT);
					if(!($number>0)){
						writeLog("APP","Gestione modelli lettera","Errore nella registrazione del modello",$codMex);
						echo "{success:false, error:\"Errore nella registrazione del modello ",TEMPLATE_PATH,"/$nomeFile\"}";
						die();
					}
				}
			}else{
				//inserimento puro 
				$codMex="ADD_MMODEL";
				$number=file_put_contents(TEMPLATE_PATH."/$nome$extens",$TXT);
			}
		
		//$number=1;//prova
		if($client)//nel caso modifica da client forza l'entrata
			$number=1;
		if($number>0)
		{
			//inserimento in modelli
			//controllo se è una modifica
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
				//rinomina file
				
				//trace("res ".$res);
				//if(rename($vecchioN,$nuovoN)){
				//	echo "{success:false, error:\"Errore nella scrittura di traccia\"}";
				//	die();
				//}
				//trace("comboUP $value");
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
				if($mod!=''){
					echo "{success:true, error:\"File modificato correttamente.\"}";
					writeLog("APP","Gestione modelli lettera","File modificato correttamente.",$codMex);
				}else{
					writeLog("APP","Gestione modelli lettera","File salvato correttamente.",$codMex);
					echo "{success:true, error:\"File salvato correttamente. Ricaricare la griglia.\"}";}
					
			}else{
				writeLog("APP","Gestione modelli lettera","\"".getLastError()."\"",$codMex);
				echo "{success:false, error:\"".getLastError()."\"}";
			}
		}else{
			writeLog("APP","Gestione modelli lettera","Errore nella registrazione del modello",$codMex);
			echo "{success:false, error:\"Errore nella registrazione del modello\"}";
		}	
	}
	catch (Exception $e)
	{
		writeLog("APP","Gestione modelli lettera","Errore nella registrazione del modello",$codMex);
		echo "{success:false, error:\"Errore nella registrazione del modello: ".$e."\"}";
	}

}
//////////////////////////////////////////////////////////////////////////
//Funzione di salvataggio/update del modello lettera in Word - 20/05/2016
//////////////////////////////////////////////////////////////////////////
function saveModelloWord(){
	
	global $context;
	
	$IdModello = $_POST['IdModello'];
	$titoloModello = $_POST['NomeM'];
	$tipoModello = $_POST['tipoMod'];
	$flagRiservato = $_POST['FlagRiservato'] == 'on' ? 'Y' : 'N';
	$IdTipoAllegato = $_POST['IdTipoAllegato'];
	$oper = $IdModello>0 ? "UPD":"INS";
	try{
		/*procedura allega documento*/
		if($_POST['condizioneH']>''){
			$evaluate = getScalar("select count(*) from v_pratiche where ".$_POST['condizioneH'], FALSE);
			if(getLastError()!='')
			{
				writeLog("APP","Gestione modelli lettera","Errore nella condizione specificata.",$oper."_MODEL");
				echo "{success:false, error:\"Errore nella condizione specificata\"}";
				die();
			}
		}

		if(count($_FILES) > 0 && is_array($_FILES['docPath']) && $_FILES['docPath']['tmp_name']>''){
			$tmpName= $_FILES['docPath']['tmp_name'];
			$fileName = $_FILES['docPath']['name'];
			$fileSize = $_FILES['docPath']['size'];
			$fileType = $_FILES['docPath']['type'];
		
			$fileName=urldecode($fileName);
		
			if(!get_magic_quotes_gpc())
				$fileName = addslashes($fileName);
		
			$localDir = TEMPLATE_PATH;
		
			if (move_uploaded_file ($tmpName, $localDir."/".$fileName)){
				chmod($localDir."/".$fileName,0777);
			}else{
					writeLog("APP","Gestione modelli lettera","Impossibile caricare il file",$oper."_MODEL");
					die('{success:false, error:"Impossibile caricare il file"}');
			}
		}else{ //allegato non c'è
			if($oper == 'INS'){ //se è avvenuto un insert di un nuovo documento
				writeLog("APP","Gestione modelli lettera","Manca il file allegato",$oper."_MODEL");
				die('{success:false, error:"Manca il file allegato"}');
			}
		}
		if($oper == 'INS'){
			/*procede per l'insert del documento word*/
			$valList = "";
			$colList = "";
			addInsClause($colList,$valList,"IdModello",$IdModello,"N");
			addInsClause($colList,$valList,"TitoloModello",$titoloModello,"S");
			addInsClause($colList,$valList,"FileName",$fileName,"S");
			addInsClause($colList,$valList,"TipoModello",$tipoModello,"S");
			addInsClause($colList,$valList,"FlagRiservato",$flagRiservato,"S");
			addInsClause($colList,$valList,"DataIni","'2001-01-01'","G");
			addInsClause($colList,$valList,"DataFin","'9999-12-31'","G");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdTipoAllegato",$IdTipoAllegato,"N");
			addInsClause($colList,$valList,"condizione",$_POST['condizioneH'],"S");
				
			
			$ins = "INSERT INTO modello($colList) VALUES($valList);";

			if(execute($ins)){
					writeLog("APP","Gestione modelli lettera","File salvato correttamente.",$oper."_MODEL");
					echo "{success:true, error:\"File salvato correttamente. Ricaricare la griglia.\"}";
			}else{
					writeLog("APP","Gestione modelli lettera","\"".getLastError()."\"",$oper."_MODEL");
					echo "{success:false, error:\"".getLastError()."\"}";
			}
		}else{ /*procede per l'update del documento word*/
				$setClause = "";
				addSetClause($setClause,"TitoloModello",$titoloModello,"S");
				addSetClause($setClause,"TipoModello",$tipoModello,"S");
				addSetClause($setClause,"FlagRiservato",$flagRiservato,"S");
				if($fileName>'')
					addSetClause($setClause,"FileName",$fileName,"S");
				addSetClause($setClause,"DataIni","'2001-01-01'","G");
				addSetClause($setClause,"DataFin","'9999-12-31'","G");
				addSetClause($setClause,"LastUpd","NOW()","G");
				addSetClause($setClause,"LastUser",$context['Userid'],"S");
				addSetClause($setClause,"IdTipoAllegato",$IdTipoAllegato,"N");
				addSetClause($setClause,"condizione",$_POST['condizioneH'],"S");
				
				$upd = "UPDATE modello $setClause WHERE IdModello=$IdModello";
				$codMex = UPD_MODEL;
				if(execute($upd)){
					writeLog("APP","Gestione modelli lettera","Modello modificato correttamente.",$oper."_MODEL");
					echo "{success:true, error:\"Modello modificato correttamente. Ricaricare la griglia.\"}";
				}else{
					writeLog("APP","Gestione modelli lettera","\"".getLastError()."\"",$oper."_MODEL");
					echo "{success:false, error:\"".getLastError()."\"}";
				}
			}

			
	}
	catch (Exception $e)
	{
		writeLog("APP","Gestione modelli lettera","Errore nella registrazione del modello",$oper."_MODEL");
		echo "{success:false, error:\"Errore nella registrazione del modello: ".$e."\"}";
	}

	
}

///////////////////////////////////////////////////
//Funzione di lettura del file sms/email in editing
///////////////////////////////////////////////////
function readJson()
{
	$file = file_get_contents(TEMPLATE_PATH."/".$_POST['nomef']);
	echo $file;
}

///////////////////////////////////////////////////
// Funzione di lettura del modello di mail
///////////////////////////////////////////////////
function readModelloEmail()
{
	$filepath = TEMPLATE_PATH."/".$_POST['nomef'];
	$contenuto = file_get_contents($filepath);
	if (stripos($filepath,".json")!==FALSE) {
		$json = json_decode($contenuto,true);
		echo $json["subject"]."\n".$json["body"];
	}
	else // formato testo o html
		echo $contenuto;
}

/////////////////////////////////////////////
//Funzione di eliminazione generale dei file
/////////////////////////////////////////////
function delete()
{
	$id = explode('|', $_REQUEST['idM']);
	$modello = explode('|', $_REQUEST['model']);
	$file = explode('|', $_REQUEST['nomeF']);
	$list = substr(join(",", $modello),1); // toglie virgola iniziale
	$vectId;
	
	for($i=1;$i<count($id)-1;$i++){
		$vectId.=$id[$i].',';
	}
	$vectId.=$id[(count($id)-1)];
	$codMex="CANC_MMODEL";
	$mex="Cancellazione dei modelli ($list)";
	$sqlSeek="SELECT idAutomatismo FROM automatismo where idModello in($vectId)";
	$arr=getFetchArray($sqlSeek);
	if(count($arr)>0){
		//messaggio ad utente
		writeLog("APP","Gestione modelli lettera","Modelli non removibili poich&egrave utilizzati da almeno un automatismo.",$codMex);
		echo "{success:false, error:\"Modelli non removibili poich&egrave utilizzati da almeno un automatismo.\"}";
	}else{
		/*//prima pulisce gli automatismi associati
		$sqlSeek="SELECT idAutomatismo FROM automatismo where idModello in($vectId)";
		$arr=getFetchArray($sqlSeek);
		for($j=0;$j<count($arr);$j++){
			$sqlCorrection="UPDATE automatismo SET IdModello=null WHERE IdAutomatismo=".$arr[$j]['idAutomatismo'];
		}*/
		//cancella modelli
		$sql = "DELETE FROM modello WHERE (idModello in ($vectId))";
		if(execute($sql)){
		 	for($i=1;$i<count($id);$i++){
				if(!unlink(TEMPLATE_PATH."/$file[$i]")){
						writeLog("APP","Gestione modelli lettera","Errore nella cancellazione di alcuni file. Eseguire l'operazione manualmente.",$codMex);
						echo "{success:false, error:\"Errore nella cancellazione di alcuni file. Eseguire l'operazione manualmente.\"}";
						die();
				}
			}
			if(count($id)-1>1){
				writeLog("APP","Gestione modelli lettera",$mex." con esito positivo",$codMex);
				echo "{success:true, error:\"Modelli rimossi.\"}";
			}else{
				writeLog("APP","Gestione modelli lettera",$mex." con esito positivo",$codMex);
				echo "{success:true, error:\"Modello rimosso.\"}";
			}
		}else{
			writeLog("APP","Gestione modelli lettera","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
		}
	}
}
?>