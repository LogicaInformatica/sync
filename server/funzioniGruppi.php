<?php
require_once("common.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;

switch($task){
	case "read":readFunc();
		break;
	case "save":saveFunc();
		break;
	case "checkLoad":checkFunc();
		break;
	default:
			die("{failure:true}");
		break;
}

///////////////////////////////////
//Funzione di lettura della griglia
///////////////////////////////////
function readFunc()
{
	global $context;
	$fields = "*";
	$vectId='';
	$lista = explode('|', $_REQUEST['lista']);
	for($i=1;$i<count($lista)-1;$i++){
		$vectId.=$lista[$i].',';
	}
	$vectId.=$lista[(count($lista)-1)];
	$query = "funzione";
	$where = " IdFunzione != IdGruppo and IdGruppo in ($vectId) order by IdGruppo,IdFunzione";
	$whereCompl = " IdFunzione = IdGruppo and IdGruppo in ($vectId)";
	
	$counter = getScalar("SELECT count(*) FROM $query WHERE $where");
	$counterComp = getScalar("SELECT count(*) FROM $query WHERE $whereCompl");

	if ($counter == NULL)
		$counter = 0;
	
		$sql = "SELECT $fields FROM $query WHERE $where";
		$sqlGrup = "SELECT $fields FROM $query WHERE $whereCompl";
		//Azioni senza gruppi
		$arr=getFetchArray($sql);
		//Gruppi 
		$arrGr=getFetchArray($sqlGrup);
		
		$mono=0;
		$IndGr='';
		$IndGrMono='';
		$IndGrMonoInd='';
		$nele=count($arrGr);
		for($j=0;$j<$nele;$j++){
			$IndGr[$j]='';
			for($k=0;$k<count($arr);$k++)
			{
				//trova le prime azioni di un dato gruppo in ordine e ne salva l'indice
				//in un array: l' si infilerà il gruppo, in testa alle azioni che gli 
				//appartengono.
				if($arr[$k]['IdGruppo']==$arrGr[$j]['IdFunzione'] && $IndGr[$j]==''){
					$IndGr[$j]=$k;
					break;
				}
			}
			//se il gruppo è un monoide allora si salva in un apposito array di moniodi 
			//che verrà aggiunto in coda a quello dei gruppi con varie funzioni sotto di se
			if($IndGr[$j]=='' && $IndGr[$j]!= '0'){
				$IndGrMono[$j]=count($arr)+$mono;
				$IndGrMonoInd[$mono] = $j;
				$mono++;
			}
			//trace("indG ".$IndGr[$j]);
		}	
		//unione degli array degli indici dei gruppi "pieni" e dei monoidi messi in coda.
		if($IndGrMono!=''){
			$IndGr=array_merge($IndGr, $IndGrMono);
		
			$mono=0;
			for($h=0;$h<count($IndGrMono);$h++)
			{
				//Gli array indici vengono sistemati, i buchi dovuti ai monoidi tolti.
				$start = array_slice($IndGr, 0, $IndGrMonoInd[$mono]);
				$end = array_slice($IndGr, $IndGrMonoInd[$mono]+1);
				$IndGr=array_merge($start, $end);
				//trace("IndGr ".print_r($IndGr,true));
				//Gli array dati subiscono lo stesso trattamento per rispecchiare quello degli indici.
				$appoggioDatiMono = $arrGr[$IndGrMonoInd[$mono]];
				$start = array_slice($arrGr, 0, $IndGrMonoInd[$mono]);
				$end = array_slice($arrGr, $IndGrMonoInd[$mono]+1);
				$arrGr=array_merge($start, $end);
				$arrGr[]=$appoggioDatiMono;
				//trace("IndGrD ".print_r($arrGr,true));
				$mono++;
			}
		}
		//inserimento nei DATI dei record dei GRUPPI nei punti giusti per far visualizzare 
		//il nome corretto del gruppo.
		for($j=0;$j<count($arrGr);$j++){
			$start = array_slice($arr, 0, $IndGr[$j]+$j);
			$end = array_slice($arr, $IndGr[$j]+$j);
			$start[] = $arrGr[$j];
			$arr = array_merge($start, $end);
			//trace("pos [$j] ".print_r($arr,true));
		}
			
	//trace("pos ".print_r($arr,true));
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	$counter=$counter+$counterComp;
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

///////////////////////////////////////
//Funzione di salvataggio della griglia
///////////////////////////////////////
function saveFunc()
{
	global $context;
	
	$values = explode('|', $_REQUEST['vect']);
	$profilo = $_REQUEST['profilo'];
	$num = count($values);
	//trace("funzioni (#$num) ".print_r($values,true));
	//$gruppo = $values[$num];

	//query di condizione ->"SELECT * FROM profilofunzione where IdProfilo=$profilo and idfunzione=$gruppo";
	//$check = rowExistsInTable('profilofunzione',"IdProfilo=$profilo and idfunzione=$gruppo");
	//cancella il gruppo per inserirlo nuovamente aggiornato dopo O cancellarlo e basta
	$sqdel = "DELETE FROM profilofunzione WHERE (idprofilo=$profilo)";
	//execute($sqdel);
	//trace("del ".$sqdel);
	
	//se è 1 vuol dire che il form è stato deselezionato tutto ed è arrivato solo il gruppo di funzioni 
	//l'azione era quindi la cancellazione di tutte quelle funzionalità e quindi niente inserimento.
	if($num > 1)
	{//inserimento o modifica permessi
		//insert brutale
		$funzioni="";
		for($i=1;$i<$num-1;$i++)
		{
			$sqinsi = "INSERT INTO profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin,LastUser) VALUES ($profilo,$values[$i],'2001-01-01','9999-12-31','system')";
			//execute($sqinsi);
			//trace("del [".($i)."] ".$sqinsi);
			$funzioni.="'".getscalar("SELECT TitoloFunzione  FROM funzione  where IdFunzione=$values[$i]"). "'-";
		} 
			trace("Funzioni inserite ".$funzioni);
			//trace log
			$titoloProfilo= getscalar("SELECT TitoloProfilo from profilo WHERE IdProfilo = $profilo");
			writeLog("APP","Gestione profili","Assegnazione funzioni ($funzioni) al profilo: '$titoloProfilo'","MOD_PROF_GRP");
	}

	echo $num-2;	
}

///////////////////////////////////
//Funzione di retrive dei checkbox
///////////////////////////////////
function checkFunc()
{
	global $context;
	
	$profilo = $_REQUEST['profilo'];
	$vectId='';
	$arrFunc='';
	$lista = explode('|', $_REQUEST['lista']);
	for($i=1;$i<count($lista)-1;$i++){
		$vectId.=$lista[$i].',';
	}
	$vectId.=$lista[(count($lista)-1)];
	$sqlchk = "SELECT f.IdFunzione,f.IdGruppo FROM funzione f left join profilofunzione pf on (f.idfunzione=pf.idfunzione) WHERE idprofilo=$profilo and IdGruppo in ($vectId)";
	$result = getFetchArray($sqlchk);
	
	$monoidi;
	$finale = $result;
	$num = count($result);
	$arrFunc[]=$result[0]['IdFunzione'];
	for ($h=1; $h<$num; $h++){
		$arrFunc[]=$result[$h]['IdFunzione'];
	}
	//trace("arrfunc ".print_r($arrFunc,true));
	for ($i=0; $i<$num; $i++){
		if ($result[$i]['IdGruppo'] == $result[$i]['IdFunzione'])
		{
			//trovo la prima delle azioni del gruppo e inserisco il gruppo prima
			$sqlchkG = "SELECT f.IdFunzione FROM funzione f left join profilofunzione pf on (f.idfunzione=pf.idfunzione) WHERE idprofilo=$profilo and IdGruppo=".$result[$i]['IdGruppo'];
			$resG = getFetchArray($sqlchkG);
			//trace("num ".count($resG));
			$targhet=$resG[0]['IdFunzione'];
			//trace("targhet $targhet");
			if($targhet!=$result[$i]['IdGruppo'])//altrimenti è già al primo posto
			{
				//tolgo il gruppo
				$start = array_slice($finale, 0, $i);
				$end = array_slice($finale, $i+1);
				$finale=array_merge($start, $end);
				
				$index = array_search($targhet, $arrFunc);
				//trace("index $index");
				
				$start = array_slice($finale, 0, $index);
				$start[]=$result[$i];
				$end = array_slice($finale, $index);
				$finale=array_merge($start, $end);
				
				$result=$finale;
			}else{
				if(count($resG)==1){
					//monoide da mettere in fondo
					$monoidi[]=$result[$i];
					//tolgo il monide
					$start = array_slice($finale, 0, $i);
					$end = array_slice($finale, $i+1);
					$finale=array_merge($start, $end);
					$result=$finale;
					$num--;
				}
			}
		}
	}
	$result=array_merge($result, $monoidi);
	//trace("arr ".print_r($result,true));
	
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($result);  //encode the data in json format
	} else {
		$data = json_encode_plus($result);  //encode the data in json format
	}
	
	//trace("res: ".$data." gruppo: ".$gruppo);
	
	echo $data;
}
?>
