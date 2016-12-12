<?php
require_once("workflowFunc.php");
require_once("userFunc.php");

$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
$file = ($_FILES['docFLPath']['tmp_name']) ? ($_FILES['docFLPath']['tmp_name']) : '';
$ck = ($_REQUEST['check']) ? ($_REQUEST['check']) : false;
//trace("file ".print_r($_FILES['docFLPath'],true));
//trace("task $task");
switch ($task)
{
	case "read":read();
		break;
	case "delete":deleteFile();
		break;
	case "process":processLottomatica($file,$ck);
		break;
	case "insert":insert($file);
		break;
	case "dettaglioLott":dettaglioLott();
		break;
	default:
		die ("{failure:true, task: '$task'}");
}

/////////////////////
//Funzioni di Utilità
/////////////////////
function read(){
	global $context;
	$fields = "*";
	$query = "filelottomatica";
	$gruppo = ($_REQUEST['group']) ? ($_REQUEST['group']) : null;
	
	switch ($gruppo)
	{
		case "":
			$order = "DataCreazione desc";
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
				//$sql .= "";
			}
		} 
		else
		{
			if ($_REQUEST['sort']>' '){ 
				$sql .= " ORDER BY ".$_REQUEST['sort'] . ' ' . $_REQUEST['dir'];	
			}else{
				$sql .= "";
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

function deleteFile()
{
	global $context;
	
	$idU = $_REQUEST['id'];
	$codMex="CANC_LOTT";
	//Delete
	$sqchecku = "SELECT count(*) FROM filelottomatica where IdLottomatica=$idU";
	$momu=getScalar($sqchecku);
	if($momu>0)
	{
		$NomeAuto= getscalar("SELECT NomeFile from filelottomatica where IdLottomatica=$idU");
		$sqdelAzLo = "DELETE FROM filelottomatica where IdLottomatica=$idU";
		if (execute($sqdelAzLo)) {
			// serve per il log prima di cancellare l'utente
			//trace("PATH--> ".TMP_PATH."/lottomatica/$NomeAuto");
			if(!unlink(TMP_PATH."/lottomatica/$NomeAuto")){
					writeLog("APP","Gestione lottomatica","Errore nella cancellazione del file. Eseguire l'operazione manualmente.",$codMex);
					echo "{success:false, error:\"Errore nella cancellazione del file. Eseguire l'operazione manualmente.\"}";
					die();
			}else{				
				// trace su log
				writeLog("APP","Gestione lottomatica","Cancellazione file $NomeAuto",$codMex);
				echo "{success:true, error:\"Il file selezionato e\' stato eliminato\"}";
			}
		}
	}else{	
		writeLog("APP","Gestione lottomatica","Questo file non è registrato nel sistema.",$codMex);
		echo "{success:true, error:\"Questo file non è registrato nel sistema.\"}";
	}
}

function insert($fileS){
	insertPagLottomatica($fileS);
	echo "{success:true, error:\"Il file selezionato &egrave stato caricato.\"}";
}

function dettaglioLott(){
	//interno file
	$Idlottomatica = $_REQUEST["lottomatica"];

	$nomeFile = getScalar("Select NomeFile From filelottomatica where IdLottomatica=$Idlottomatica");
	$arrayFile= processLottomatica(TMP_PATH."/lottomatica/$nomeFile");
	$counter=count($arrayFile);
	$myInventory = json_encode_plus($arrayFile);
	echo '({"total":"' . $counter . '","results":' . $myInventory . '})'; 
}
//////////////////////////////////////////////////////////////////////////
//Funzioni di acquisizione e controllo della codifica dei file lottomatica
//////////////////////////////////////////////////////////////////////////


//insertPagLottomatica
// Elabora la il file passato,chimanado la function  processLottomatica
//inserisce i dati nella tabella storiarecupero
// Argomenti:
// 1)$filepath 	file di txt

 function insertPagLottomatica($filepath){
 	try
	{
		global $context;
		//variabili d'utilità
		$IdUser = $context['IdUtente'];
		$fileName='docFLPath';
		$tmpName= $_FILES[$fileName]['tmp_name'];
		$fileName = $_FILES[$fileName]['name'];     
		$fileSize = $_FILES[$fileName]['size'];
		$fileType = $_FILES[$fileName]['type'];
		$codMex="INSERT_PAGLOTTO";
		//salvataggio file in tabella 
		$colList = ""; // inizializza lista colonne
		$valList = ""; // inizializza lista valori
		addInsClause($colList,$valList,"NomeFile",$fileName,"S");	 	
		addInsClause($colList,$valList,"DataCreazione","NOW()","G");	
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		
		if (execute("INSERT INTO filelottomatica($colList)  VALUES ($valList)"))
		{
   			//writeResult($idImportLog,"K",mysqli_error($conn));		
			$righe=processLottomatica($filepath);
	   		//trace("righe ".print_r($righe,true));
			$i = 0;
			foreach($righe as $elemento)
			{
	            //trace("record ".print_r($elemento,true),false);
				$codiceCli	= $elemento['codiceContratto'];
				$dataTransazione	= $elemento['dataTransazione'];
				$importo	= $elemento['importo'];
		        //trace("riga[$i] codcont = ".$codiceCli);
		 
				if ($codiceCli > "") // codice contratto presente?
				{	// cerca il contratto con codice dato, con id dato (deve essercene uno solo)
					$SQLidC = "SELECT c.IdContratto  from contratto c,storiarecupero s ".
			           "where c.IdContratto=s.IdContratto  and c.codContratto='".$codiceCli."'";
					$idContratto = getScalar($SQLidC);
				}
				else //
				{
					//writeResult($idImportLog,"K",mysqli_error($conn));
						return 2;
				}
			
				if ($codiceCli!=NULL)
				{
					$importo = str_replace('.', ',', $importo); 
					writeHistory("NULL","Registrato pagamento Lottomatica di euro $importo",$idContratto,"Data transazione: $dataTransazione");
		    	}
		    	$i++;
			}
			//copia file in repository
			$fileName = urldecode($fileName);
			
			if(!get_magic_quotes_gpc())
				$fileName = addslashes($fileName);
			
			$localDir=TMP_PATH."/lottomatica";
			if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
				if (!mkdir($localDir,0777,true)){ // true --> crea le directory ricorsivamente
					writeLog("APP","Gestione lottomatica","Impossibile creare la cartella dei documenti.",$codMex);
					Throw new Exception("Impossibile creare la cartella dei documenti");				
				}
			if (move_uploaded_file ($tmpName, $localDir."/".$fileName))
			{
				//trace("File copiato nel repository",false);
				//$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
				//writeHistory($idAzione,"Import file lottomatica",$pratica['IdContratto'],"Documento: $titolo Contratto:".$pratica['CodContratto']);
				writeLog("APP","Gestione lottomatica","File di Lottomatica creato.",$codMex);				
				return true;
			}
			else
			{
				writeLog("APP","Gestione lottomatica","Impossibile copiare il file nel repository.",$codMex);
				setLastError("Impossibile copiare il file nel repository");
				//trace("Impossibile copiare il file nel repository");
				return FALSE;
			}
		}
	}
	catch (Exception $e)
	{
		writeLog("APP","Gestione lottomatica","Errore nell'elaborazione di un record del file Lottomatica: ".$e,$codMex);
		//trace("Errore nell'elaborazione di un record del file Lottomatica: ".$e);
		return 2;
	}
}

//  processLottomatica
// Scopo: processa uno dei file txt ricevuti
// Argomenti:
// 1)$filepath 	file di txt
// Ritorna:
//		array di array
function processLottomatica($filepath,$ck)
{
	//$ck = ($_REQUEST['check']) ? ($_REQUEST['check']) : false;
	$codMex="PROCESS_LOTT";
	if($filepath!='')
	{
		$lines=file($filepath);
		//trace("lines ".print_r($lines,true));
		if($lines != false)
		{
			$flagGood=false;
			//trace("flagGStart $flagGood");
			foreach($lines as $line)
			{ 
				$mom=substr($line,19,14);
				$mom = str_replace(' ', '', $mom);
				//trace("mom $mom");
				if($mom!='')
		     	{
		     		//ultima riga non calcolata
					$idTransazione=substr($line,0,15);
					//$idTrans=json_encode($idTransazione);
					$CcBeneficiario=substr($line,15,12);
					$CcBenef=json_encode_plus($CcBeneficiario);
					$dataTransazione=substr($line,27,6);
					$dataTransazione =datareformat($dataTransazione);
					$importo=substr($line,36,10);
					$ufficioSportello=substr($line,46,8); 
					$dataContbAccredito=substr($line,55,6); 
					$dataContbAccredito=datareformat($dataContbAccredito);
					//$codiceCliente=substr($line,61,16);
					
					if(substr($line,61,2)=='97'){
						$codiceCli=abs(substr($line,63,14));
			         	$codiceCli="LO".$codiceCli;
					}elseif(substr($line,61,2)=='98'){
			         	$codiceCli=abs(substr($line,63,14));
			            $codiceCli="RV".$codiceCli;
					}else{
						$codiceCli=abs(substr($line,63,14));
						$codiceCli="LE".$codiceCli;}
			        	  
			        $array=array();
			        $array['idTransazione']=$idTransazione;
			        $array['CcBeneficiario']=$CcBeneficiario;
			        $array['dataTransazione']=$dataTransazione;
			        $array['importo']= $importo/100;
			        $array['ufficioSportello']=$ufficioSportello;
			        $array['dataContbAccredito']=$dataContbAccredito;
			        $array['codiceContratto']= $codiceCli;
			        $arrayTot[]=$array;
			        
			        //trace("imp ".$importo/100);
			        $sqlChk = "Select count(*)as num from contratto where codcontratto='$codiceCli'";
			        $res = getScalar($sqlChk);
			       // trace("resNum ".$res);
			        if($res>0){
			        	//almeno un contratto associato
			        	$flagGood=true;
			        	//trace("flagG $flagGood");
			        }
			        //trace("flagG $flagGood");
			        //print_r($arrayTot);
		     	}
			}
			if($flagGood){
				//buono
				if($ck){
					//trace("good1");
					writeLog("APP","Gestione lottomatica","File di Lottomatica processato.",$codMex);
					echo "{success:true, error:\"ciao\"}";
				}else{
					//trace("arr".print_r($arrayTot,true))
					writeLog("APP","Gestione lottomatica","File di Lottomatica processato.",$codMex);
					return $arrayTot;	
				}
			}else{
				//file non adeguato
				if($ck){
					//trace("no good1.1");
					writeLog("APP","Gestione lottomatica","Il file selezionato non è di Lottomatica.",$codMex);
					echo "{success:false, error:\"Il file selezionato non è di Lottomatica.\"}";
				}else{
					//trace("no good1.2");
					writeLog("APP","Gestione lottomatica","Errore di processamento del file di Lottomatica.",$codMex);
					return false;	
				}
			}
		}else{
			if($ck){
				//trace("no good 2.1");
				writeLog("APP","Gestione lottomatica","Errore nell'aprire il file selezionato.",$codMex);
				echo "{success:false, error:\"Errore nell\'aprire il file selezionato.\"}";
			}else{
				//trace("no good 2.2");
				writeLog("APP","Gestione lottomatica","Errore di processamento del file di Lottomatica.",$codMex);
				return false;	
			}
		}
	    // echo  json_encode($arrayTot);
	    //print_r($arrayTot);
	}else{	
		if($ck){
			//trace("no good 3.1");
			writeLog("APP","Gestione lottomatica","Non si è selezionato nessun file.",$codMex);
			echo "{success:false, error:\"Non si è selezionato nessun file.\"}";
		}else{
			//trace("no good 3.2");
			writeLog("APP","Gestione lottomatica","Errore di processamento del file di Lottomatica.",$codMex);
			return false;	
		}
	}
}

?>

 
