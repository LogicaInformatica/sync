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
		   if ($dataAffido > strtotime("+3 days")) {
		 	 $giorniDataAffido[$num]['DateStandard']=date('Y-m-d', $dataAffido);
			 //controlla se la data di affido standard ha già subito una variazione
			 //se si viene salvata nell'array la data modificata altrimenti la dat vuota   
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
		  for ($i=0;$i<$totale;$i++) {
		  	if ($_REQUEST['DateVariate'.$i]!='' && $_REQUEST['DateStandard'.$i]!=$_REQUEST['DateVariate'.$i]) {
		  	  	
		  	  if (rowExistsInTable("dataaffido","DataAffidoStandard = '".date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateStandard'.$i])))."'")) {
			  	$setClause = "";	
			  	addSetClause($setClause,"DataAffidoVariata",$_REQUEST['DateVariate'.$i],"D");
				addSetClause($setClause,"LastUser",$context['Userid'],"S");
				addSetClause($setClause,"LastUpd","NOW()","G");
				
				if (!execute("UPDATE dataaffido $setClause WHERE DataAffidoStandard = '".date('Y-m-d', strtotime(str_replace('/', '-',$_REQUEST['DateStandard'.$i])))."'")) {
					return false;
				}
			  } else {
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
					$esito = getLastError();
				  }		
			  }
			  
		  	}
		  }	
		}
		
	}catch (Exception $e)
	 {
//			trace("Errore durante la scrittura del file avvisi agenzia".$e->getMessage());
			setLastSerror($e->getMessage());
			writeLog('APP',"Gestione modifca date affido","Errore nella modifica delle date di affido.",$codMex);
			echo('{success:false,error:"Errore nella modifica delle date di affido"}');
	 }
}

?>
