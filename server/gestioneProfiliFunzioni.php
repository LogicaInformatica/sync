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
		case "savePro":savePro();
			break;
		case "readFuncCk":readFuncCk();
			break;
		case "checkGrup":checkGrup();
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
///////////////////////////////////////
//Funzione di salvataggio info principali profilo
///////////////////////////////////////
function savePro()
{
	global $context;
	$Operatore = $context['Userid'];
	$arrIns=array();
	
	//trace("codP ".print_r($_REQUEST['CodProfilo'],true));
	//trace("NomeP ".print_r($_REQUEST['TitoloProfilo'],true));
	
	//raccolta campi
	$idP = ($_REQUEST['idP']) ? ($_REQUEST['idP']) : '';
	array_push($arrIns, $idP, 'IdProfilo');
	$codP = isset($_REQUEST['CodProfilo'])?$_REQUEST['CodProfilo']:'';
	array_push($arrIns, $codP, 'CodProfilo');
	$NomeTitoloP = isset($_REQUEST['TitoloProfilo'])?addslashes(htmlstr($_REQUEST['TitoloProfilo'])):'';
	array_push($arrIns, $NomeTitoloP, 'TitoloProfilo');
	$Abbreviazione = isset($_REQUEST['AbbrProfilo'])?addslashes(htmlstr($_REQUEST['AbbrProfilo'])):'';
	array_push($arrIns, $Abbreviazione, 'AbbrProfilo');
	$dataini = isset($_POST['DataIni'])?$_POST['DataIni']:date("Y-m-d");
	array_push($arrIns, ISODate($dataini), 'DataIni');
	$datafin = isset($_POST['DataFin'])?$_POST['DataFin']:date("Y-m-d");
	array_push($arrIns, ISODate($datafin), 'DataFin');
	//trace("arrin ".print_r($arrIns,true));
	$vectId='';
	$vectDEL='';
	$values = explode('|', $_REQUEST['vect']);
	$valuesGruppi = explode('|', $_REQUEST['vectGr']);
	$valuesGruppiTOT = explode('|', $_REQUEST['vectGrTOT']);
	//trace("vectGr ".print_r($valuesGruppi,true));
	
	for($l=1;$l<count($valuesGruppiTOT)-1;$l++){
		$vectDEL.=$valuesGruppiTOT[$l].',';
	}
	$vectDEL.=$valuesGruppiTOT[(count($valuesGruppiTOT)-1)];
	$profilo = $_REQUEST['idP'];
	$num = count($values);
	$numG = count($valuesGruppi);
	//trace("funzioni (#$num) ".print_r($values,true));
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
	//MODIFICA PROFILO
	//---------------
	$sqlUp="UPDATE profilo SET $query where idProfilo=$idP";
	//trace("upd - $sqlUp");
	if (execute($sqlUp)){
	//if (false){
		//query di condizione ->"SELECT * FROM profilofunzione where IdProfilo=$profilo and idfunzione=$gruppo";
		//$check = rowExistsInTable('profilofunzione',"IdProfilo=$profilo and idfunzione=$gruppo");
		//cancella il gruppo per inserirlo nuovamente aggiornato dopo O cancellarlo e basta
		$sqdel = "DELETE FROM profilofunzione where idprofilo=$idP and IdFunzione in (select IdFunzione from funzione where IdGruppo in($vectDEL))";
		execute($sqdel);
		//trace("del ".$sqdel);
		
		//se è 1 vuol dire che il form è stato deselezionato tutto ed è arrivato solo il gruppo di funzioni 
		//l'azione era quindi la cancellazione di tutte quelle funzionalità e quindi niente inserimento.
		if($num > 1)
		{//inserimento o modifica permessi
			//insert brutale delle funzioni
			$funzioni="";
			for($i=1;$i<$num;$i++)
			{
				$sqinsi = "REPLACE INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser) VALUES ($idP,$values[$i],'2001-01-01','9999-12-31','".$context['Userid']."')";
				execute($sqinsi);
				//trace("Ins [".($i)."] ".$sqinsi);
				$funzioni.="'".getscalar("SELECT TitoloFunzione  FROM funzione  where IdFunzione=$values[$i]"). "'-";
				//trace("funzioni titoli ".print_r($funzioni,true));
			}
			//insert brutale dei gruppi di funzioni 
			for($h=1;$h<$numG;$h++)
			{
				$sqinsi = "REPLACE INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser) VALUES ($idP,$valuesGruppi[$h],'2001-01-01','9999-12-31','".$context['Userid']."')";
				execute($sqinsi);
				//trace("Ins [".($h)."] ".$sqinsi);
				$funzioni.="'".getscalar("SELECT TitoloFunzione  FROM funzione  where IdFunzione=$valuesGruppi[$h]"). "'-";
				//trace("macrofunzioni titoli ".print_r($funzioni,true));
			} 
	// 14/12/12: inserisce una riga anche per autorizzare al gruppo di funzioni a cui appartiene ciascuna funzione (serve per i menu e i workflow, che altrimenti non si vedono)
			$sqinsi = "REPLACE INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser)
				SELECT IdProfilo,IdGruppo,MIN(pf.DataIni),MAX(pf.DataFin),pf.LastUser
				FROM profilofunzione pf JOIN funzione f ON f.IdFunzione=pf.IdFunzione
				WHERE IdProfilo=$idP AND IdGruppo IS NOT NULL GROUP BY IdProfilo,IdGruppo;";
			execute($sqinsi);
	trace($sqinsi,FALSE);
			// registra sul giornale di bordo
			$titoloProfilo= getscalar("SELECT TitoloProfilo from profilo WHERE IdProfilo = $idP");
			writeLog("APP","Gestione profili","Modifica del profilo ".$NomeTitoloP.", eseguita.","MOD_PROF");
			echo "{success:true, error:\"Successo\"}";
		}
	}else{
		writeLog("APP","Gestione profili","\"".getLastError()."\"","MOD_PROF");
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//////////////////////////////////////////
//Funzione di lettura del pannello Gruppi di funzioni
//////////////////////////////////////////
function readFuncCk()
{
	global $context;
	$jumpCount=false;
	$jumpQuery=false;
	$noLimits=false;
	$tsk = $_REQUEST['who'];
	$tskGroup = $_REQUEST['which'];
	$gruppo = isset($_REQUEST['idMGF'])?$_REQUEST['idMGF']:'';
	switch ($tsk)
	{
		case "funzioni":
			if($tskGroup!="Azioni"){
				$fields = "f.IdFunzione,TitoloFunzione";
				$query = "funzione f left join azione a on(f.idFunzione=a.idFunzione) where f.IdFunzione = IdGruppo and ((DataFin>CURDATE()) or DataFin is null)  
							and f.MacroGruppo = '$tskGroup' ORDER BY TitoloFunzione";
			}else{
				$fields = "ta.IdTipoAzione as IdFunzione,TitoloTipoAzione as TitoloFunzione, f.idgruppo as IdGruppo";
				$query = "tipoazione ta 
							left join azionetipoazione atz on(ta.idtipoazione=atz.idtipoazione)
							left join azione a on(a.idazione=atz.idazione) 
							left join funzione f on (a.idfunzione=f.idfunzione) where ta.idtipoazione not in (9,12,13) 
							group by ta.IdTipoAzione 
							order by TitoloTipoAzione asc;";
			}
			break;
		case "subfunzioni":
			$fields = "f.IdFunzione,TitoloFunzione, f.idgruppo as IdGruppo";
			if($tskGroup!="Azioni"){
				//trace("gruppo $gruppo");
				if($gruppo!=''){
						$query = "funzione f left join azione a on(f.idFunzione=a.idFunzione) where IdGruppo=$gruppo and f.idfunzione!=idgruppo and ((DataFin>CURDATE()) or DataFin is null) 
									and f.MacroGruppo = '$tskGroup' ORDER BY TitoloFunzione";
				}else{
					$idFunzione = getScalar("SELECT idFunzione FROM funzione where idFunzione = Idgruppo limit 1");
					$query = "funzione f left join azione a on(f.idFunzione=a.idFunzione) where IdGruppo=$idFunzione and f.idfunzione!=idgruppo and ((DataFin>CURDATE()) or DataFin is null)
								and f.MacroGruppo = '$tskGroup'";
				}
				//trace("Q: $query");
			}else{
				//trace("gruppoAzioni $gruppo");
				if($gruppo!=''){
					$duplicateInfo = getFetchArray("select f.idfunzione,at1.idazione,at1.IdTipoAzione as gr1, at2.IdTipoAzione as gr2,
													(select count(*) from azionetipoazione where idtipoazione = gr1) as numgr1,
													(select count(*) from azionetipoazione where idtipoazione = gr2) as numgr2
													from azionetipoazione at1
													join azionetipoazione at2 on(at1.idAzione=at2.idAzione)
													left join azione az on(at1.idAzione = az.idAzione)
													left join funzione f on(az.idfunzione = f.idfunzione)
													where at1.idtipoAzione!=at2.idtipoAzione
													group by at1.idazione");
					//trace("GRUPPO: $gruppo, gr1:".$duplicateInfo[0]['gr1']."->numgr1 ".$duplicateInfo[0]['numgr1']." | gr2:".$duplicateInfo[0]['gr2']."->numgr2 ".$duplicateInfo[0]['numgr2']);
					if($duplicateInfo[0]['gr1']!=$gruppo && $duplicateInfo[0]['gr2']!=$gruppo){//non stragiudiziale e legale
					//2016-05-08: se gruppo="Altre azioni" ci mette anche la creaz. note dalla lista 
					$condTipoAzione = $gruppo!=10?"tz.idtipoazione=$gruppo":"tz.idtipoazione IN (10,12,13)";
						$query = "azionetipoazione atz left join azione a on(a.idazione = atz.idazione)
								left join funzione f on(f.idfunzione=a.idfunzione) left join tipoazione tz on(atz.idtipoazione=tz.idtipoazione)
					 			where $condTipoAzione and a.codAzione not like 'WF%' and((a.DataFin>CURDATE()) or a.DataFin is null) ORDER BY TitoloFunzione";
					}else{
						//somma al gruppo dei due con cardinalità minore i record comuni
						$jumpCount=true;
						$jumpQuery=true;
						$noLimits=true;
						$minor = $duplicateInfo[0]['numgr1']>$duplicateInfo[0]['numgr2']?$duplicateInfo[0]['gr2']:$duplicateInfo[0]['gr1'];
						//trace("minor $minor");
						$query = "select * from 
								(select f.IdFunzione as IdFunzione,TitoloFunzione, f.idgruppo as IdGruppo from azionetipoazione atz left join azione a on(a.idazione = atz.idazione)
									left join funzione f on(f.idfunzione=a.idfunzione) left join tipoazione tz on(atz.idtipoazione=tz.idtipoazione)
						 			where tz.idtipoazione=$gruppo and a.codAzione not like 'WF%' and((a.DataFin>CURDATE()) or a.DataFin is null) 
						 			ORDER BY f.IdFunzione) as P
								LEFT JOIN 
								(select f.idfunzione,TitoloFunzione as TF, f.idgruppo as IdGruppo
									from azionetipoazione at1
									join azionetipoazione at2 on(at1.idAzione=at2.idAzione)
									left join azione az on(at1.idAzione = az.idAzione)
									left join funzione f on(az.idfunzione = f.idfunzione)
									where at1.idtipoAzione!=at2.idtipoAzione
									group by at1.idazione) as Q 
								ON(P.idfunzione=Q.idfunzione)
								where Q.idfunzione is null";
						if($gruppo==$minor){
							//sommali
							$query .=" UNION
									(select f.IdFunzione as IdFunzione,TitoloFunzione, f.idgruppo as IdGruppo, null as fake, null as faketitle, null as fakeGroup
										from azionetipoazione at1
										join azionetipoazione at2 on(at1.idAzione=at2.idAzione)
										left join azione az on(at1.idAzione = az.idAzione)
										left join funzione f on(az.idfunzione = f.idfunzione)
										where at1.idtipoAzione!=at2.idtipoAzione
										group by at1.idazione)";
						}
						$counterJumped = getFetchArray($query);
						//trace("counterJump: ".print_r($counterJumped,true));
						$counterJumped = count($counterJumped);
					}
				}else{
					$idFunzione = getScalar("SELECT idTipoAzione FROM tipoazione limit 1");
					$query = "azionetipoazione atz left join azione a on(a.idazione = atz.idazione)
								left join funzione f on(f.idfunzione=a.idfunzione) left join tipoazione tz on(atz.idtipoazione=tz.idtipoazione)
					 			where tz.idtipoazione=$idFunzione and a.codAzione not like 'WF%' and ((a.DataFin>CURDATE()) or a.DataFin is null) ORDER BY TitoloFunzione";
				}
				//trace("QAz: $query");
			}
			break;
	}
	
	if(!$jumpCount)
		$counter = getScalar("SELECT count(*) FROM $query");
	else
		$counter = $counterJumped;
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 	//trace("counter $counter");
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		if(!$jumpQuery)
			$sql = "SELECT $fields FROM $query";
		else
			$sql = $query;
	
		if(!$noLimits){
			if ($start!='' || $end!='') {
		    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
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
//Funzione di retrive dei checkbox funzioni di gruppo(dettaglio)
/////////////////////////////////////////////////////
function checkGrup()
{
	global $context;
	
	$idP = $_REQUEST['idP'];
	$tsk = $_REQUEST['who'];
	$tskGroup = $_REQUEST['which'];
	$gruppo = isset($_REQUEST['idMGF'])?$_REQUEST['idMGF']:'';
	switch ($tsk)
	{
		case "funzioni":
			$sqlchk = "select f.IdFunzione from (funzione f left join profilofunzione pf on(f.IdFunzione=pf.Idfunzione)) left join azione a on(f.idFunzione=a.idFunzione) where f.IdFunzione=f.IdGruppo and pf.IdProfilo=$idP and ((a.DataFin>CURDATE()) or a.DataFin is null)
						and f.MacroGruppo = '$tskGroup'";
			break;
		case "subfunzioni":
			if($gruppo!=''){
				$sqlchk = "select f.IdFunzione from (funzione f left join profilofunzione pf on(f.IdFunzione=pf.Idfunzione)) left join azione a on(f.idFunzione=a.idFunzione) where f.IdGruppo=$gruppo and pf.IdProfilo=$idP and ((a.DataFin>CURDATE()) or a.DataFin is null)
							and f.MacroGruppo = '$tskGroup'";
			}else{
				$idFunzione = getScalar("SELECT f.IdFunzione FROM funzione f left join azione a on(f.idFunzione=a.idFunzione) where f.idFunzione = Idgruppo and ((DataFin>CURDATE()) or DataFin is null) limit 1");
				$sqlchk = "select f.IdFunzione from (funzione f left join profilofunzione pf on(f.IdFunzione=pf.Idfunzione)) left join azione a on(f.idFunzione=a.idFunzione) where f.IdGruppo=$idFunzione and pf.IdProfilo=$idP and ((a.DataFin>CURDATE()) or a.DataFin is null)
							and f.MacroGruppo = '$tskGroup'";
			}
			break;
	}
	
	$result = fetchValuesArray($sqlchk);
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
