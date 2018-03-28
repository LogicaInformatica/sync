<?php
require_once("userFunc.php");
require_once("workflowFunc.php");

$task = ($_POST['task']) ? ($_POST['task']) : null;
switch($task){
	case "saveDateModificate":
		saveDateModificate();
		break;
	case "read":
		read();
		break;
	default:
		//echo "{failure:true}";
		break;
}

// legge i giorni di affido e le trasforma nelle possibili date dei prossimi sei mesi
function read() {
	global $context;
	$rows = getColumn("SELECT DISTINCT(GiorniFissiInizio) as giorniAffido FROM regolaassegnazione WHERE TipoAssegnazione=2 AND DataFin > CURDATE() AND GiorniFissiInizio is not null order by GiorniFissiInizio");
	$giorni = array();
	$giorniDataAffido = array();
	$num = 0;
	//si crea un array con i giorni fissi
	foreach ($rows as $row) {
		if (stristr($row,',')) {
			  $arr = split(',',$row);
	          foreach ($arr as $val) {
	          	 $giorni[]=$val; 
			  }	
	    } else $giorni[]=$row;
	}
	//ordina in modo ascendente l'array dei giorni
	asort($giorni);
	//crea un array con le date di affido standard e le date già modificate dei prossimi mesi
	for ($i=0;$i<6;$i++) {
	    foreach ($giorni as $row) {
		   $dataAffido = mktime(0,0,0,date(m)+$i,$row,date(y));
		   //controlla se la possibile data di affido sia antecedente ad oggi + 3 giorni, in questo caso viene scartata
		   if ($dataAffido > strtotime("-3 days")) {
		 	 $giorniDataAffido[$num]['DateStandard']=date('Y-m-d', $dataAffido);
			 //controlla se la data di affido standard ha già subito una variazione
			 //se si viene salvata nell'array la data modificata altrimenti la data vuota   
			 $dataVariata = getScalar("SELECT DataAffidoVariata FROM dataaffido WHERE DataAffidoStandard = '".date('Y-m-d', $dataAffido)."'");  
			 if ($dataVariata!=='') {
			   $giorniDataAffido[$num]['DateVariate']=$dataVariata;	
			 } else {
			 	$giorniDataAffido[$num]['DateVariate']='';
			   }		
			 $num++;
		   }
		}
	}
	$total = count($giorniDataAffido);
	$error = "";
	$data = json_encode_plus($giorniDataAffido);  //encode the data in json format
	
   	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
	echo '({"total":"' . $total . '","results":' . $data . $error.'})';
}

//-----------------------------------------------------------------------
// saveDateModificate
// Inserisce o aggiorna le date di affido modificate nella tabella dataaffido
//-----------------------------------------------------------------------
function saveDateModificate(){
	try
	{
		global $context;

	    $totale = $_REQUEST['numDate'];
		$dateModificate= array();
		if ($totale>0) {
		  beginTrans();	
		  for ($i=0;$i<$totale;$i++) {
		  	//controllo se la data di affido standard è stata modificata e che sia diversa dalla standard
		  	if ($_REQUEST['DateVariate'.$i]!=='' && $_REQUEST['DateStandard'.$i]!==$_REQUEST['DateVariate'.$i]) {
		  	  $dataStandard = date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateStandard'.$i])));	
		  	  $dataVariata = date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateVariate'.$i])));	
		  	  //controllo che la data di affido standard sia già stata modificata
		  	  if (rowExistsInTable("dataaffido","DataAffidoStandard = '$dataStandard'")) {
			  	$dataModificaOld = getScalar("SELECT DataAffidoVariata FROM dataaffido WHERE DataAffidoStandard = '$dataStandard'");	
				//Se effettivamente variata effettuo l'update della data di affido
				//e cambio la data di fine affido precedente alla stessa
				if ($dataModificaOld!='' && $dataModificaOld!=$dataVariata) {
					//aggiorno la data di fine affido del lotto precedente	
				    if (!aggiornamentoDataFineAffido($dataModificaOld, $dataVariata)) {
				      fail("Errore durante la modifica della data di fine affido");	
				    }
					
					$setClause = "";	
				  	addSetClause($setClause,"DataAffidoVariata",$_REQUEST['DateVariate'.$i],"D");
					addSetClause($setClause,"LastUser",$context['Userid'],"S");
					addSetClause($setClause,"LastUpd","NOW()","G");
					
					if (!execute("UPDATE dataaffido $setClause WHERE DataAffidoStandard = '$dataStandard'")) {
						fail("Errore durante l'update della data di inizio affido variata");
					}
				}
			  } else {
			  	  //Inserisco la nuova data di affido inizio affido
				  //e cambio la data di fine affido precedente alla stessa	
			  	  if (!aggiornamentoDataFineAffido($dataStandard, $dataVariata)) {
				      fail("Errore durante la modifica della data di fine affido");	
				  }	
			  	  
			  	  $valList = "";
		          $colList = "";	
				  addInsClause($colList,$valList,"DataAffidoStandard",$_REQUEST['DateStandard'.$i],"D");
				  addInsClause($colList,$valList,"DataAffidoVariata",$_REQUEST['DateVariate'.$i],"D");
		          addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		          addInsClause($colList,$valList,"LastUpd","NOW()","G");
				  
				  $sql =  "INSERT INTO dataaffido ($colList) VALUES ($valList)";
				  //trace($sql);
				  // Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
				  // nel caso in cui non ci fosse nessuna modifica di valore)
				  if (!execute($sql)) {
					fail("Errore durante l'insert della data di inizio affido variata");  
				  }		
			  }
			} else {
				//Nel caso che la data variata sia uguale alla data standard
				//elimino la riga corrispondente 
				if ($_REQUEST['DateStandard'.$i]==$_REQUEST['DateVariate'.$i]) {
					$dataStandard = date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateStandard'.$i])));	
		  	        $dataVariata = date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateVariate'.$i])));	
				    $dataModificaOld = getScalar("SELECT DataAffidoVariata FROM dataaffido WHERE DataAffidoStandard = '$dataStandard'");	
					//Se effettivamente variata cambio la data di fine affido precedente alla stessa
					if ($dataModificaOld!='' && $dataModificaOld!=$dataVariata) {
						//aggiorno la data di fine affido del lotto precedente	
					    if (!aggiornamentoDataFineAffido($dataModificaOld, $dataVariata)) {
					      fail("Errore durante la modifica della data di fine affido");		
					    }
							
					  	if (!execute("DELETE FROM dataaffido WHERE DataAffidoStandard = '$dataStandard'")) {
							fail("Errore nella cancellazione delle date di affido variata");
						}
					}	
				}
			}
		  }	
		}
		success();
	}catch (Exception $e)
	 {
//			trace("Errore durante la scrittura del file avvisi agenzia".$e->getMessage());
			setLastSerror($e->getMessage());
			writeLog('saveDateModificate',"Gestione modifica date affido","Errore nella modifica delle date di affido.",$codMex);
			echo('{success:false,error:"Errore nella modifica delle date di affido"}');
	 }
}

//-----------------------------------------------------------------------
// aggiornamentoDataFineAffido
// date la data di inizio affido vecchia e la data di inizio affido modifica
// fa l'aggiornamento sulla tabella contratto della nuova Data di Fine affido 
// del lotto precedente
// fa l'aggiornamento sulla tabella assegnazione della nuova DataFin e DataFineAffidoContratto 
// fa l'aggiornamento sulla tabella storiainsoluto della nuova DataFineAffido 
// fa l'aggiornamento sulla tabella dettaglioprovvigione della nuova DataFineAffido e DataFineAffidoContratto
// fa l'aggiornamento sulla tabella provvigione della nuova DataFin 
//-----------------------------------------------------------------------
function aggiornamentoDataFineAffido($dataInizioAffido, $dataInizioAffidoVariata) {
	try
	{
		global $context;
		
		//Data fine affido attuale		
		$dataFineAffido= date('Y-m-d',strtotime($dataInizioAffido . " -1 day"));
		//Nuova Data di fine affido
		$dataFineAffidoVariata = date('Y-m-d',strtotime($dataInizioAffidoVariata . " -1 day"));
				
		$setClause = "";	
	  	addSetClause($setClause,"DataFineAffido",$dataFineAffidoVariata,"D");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"lastupd","NOW()","G");
		
		$rowsContratto = getRows("SELECT IdContratto FROM contratto WHERE DataFineAffido = '$dataFineAffido'");
		foreach ($rowsContratto as $row) {
			if (!execute("UPDATE contratto $setClause WHERE IdContratto = ".$row['IdContratto'])) {
				return false;
			} else {
				writeHistory("NULL","Variata Data fine affido al $dataFineAffidoVariata a fronte della modifica della data inizio affido lotto seguente",$row["IdContratto"],"");
			}
		}   
		
		//Aggiornameno DataFin e DataFineAffidoContratto della tabella assegnazione
		if (!execute("UPDATE assegnazione SET DataFin='$dataFineAffidoVariata' WHERE DataFin='$dataFineAffido'")) {
			return false;
		} 
		
        if (!execute("UPDATE assegnazione SET DataFineAffidoContratto='$dataFineAffidoVariata' WHERE DataFineAffidoContratto='$dataFineAffido'")) {
			return false;
		} 
		
		//Aggiornameno DataFineAffido della tabella storiainsoluto
		if (!execute("UPDATE storiainsoluto SET DataFineAffido='$dataFineAffidoVariata' WHERE DataFineAffido='$dataFineAffido'")) {
			return false;
		} 
		
		//Aggiornameno DataFineAffido e DataFineAffidoContratto della tabella dettaglioprovvigione
		if (!execute("UPDATE dettaglioprovvigione SET DataFineAffido='$dataFineAffidoVariata' WHERE DataFineAffido='$dataFineAffido'")) {
			return false;
		} 
		
        if (!execute("UPDATE dettaglioprovvigione SET DataFineAffidoContratto='$dataFineAffidoVariata' WHERE DataFineAffidoContratto='$dataFineAffido'")) {
			return false;
		} 
		
		//Aggiornameno DataFin e DataFin della tabella provvigione
		if (!execute("UPDATE provvigione SET DataFin='$dataFineAffidoVariata' WHERE DataFin='$dataFineAffido'")) {
			return false;
		} 
	    
	    return true;
	} catch (Exception $e)
	  {
//			trace("Errore durante la scrittura del file avvisi agenzia".$e->getMessage());
			setLastSerror($e->getMessage());
			writeLog('AggiornamentoDataFineAffido',"Gestione modifica date affido","Errore nella modifica della data di fine affido.",$codMex);
			echo('{success:false,error:"Errore nella modifica della data di fine affido."}');
	  }
}

?>
