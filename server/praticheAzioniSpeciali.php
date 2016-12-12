<?php
// Legge le liste delle pratiche con azioni ( speciali ) da convalidare

require_once("common.php");
require_once("userFunc.php");
require_once("workflowFunc.php");

$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;

switch ($task)
{
	case "read":
		read();
	break;	
	case "update":
		update();
	break;	
	default:
		echo "{failure:true, task: '$task'}";
		return;
}

function read()
{
	try
	{
		global $context;
		$stato = $_REQUEST['stato'];
		$idUtente = $_REQUEST["idUtente"];
		//Controllo utente esterno perchè può vedere solo le sue pratiche al contrario
		//dell'interno che può vedere tutte le pratiche con richiesta di convalida
		if ($context["InternoEsterno"]=="E")
		{
		  $query = "v_praticheAzioniSpeciali v where Stato = '$stato'";
		  $query .= filtroInsolutiAgenzia();
		} else {
			if ($idUtente>0) {
			 $query = "v_praticheAzioniSpeciali v where Stato = '$stato' AND $idUtente IN (IdOperatore,IdOperatoreContratto)";
			} else {
				$query = "v_praticheAzioniSpeciali v where Stato = '$stato' AND IdOperatore is null";
			  }
		}
		$order = "DataEvento";
		$fields = "*";
		$counter = getScalar("SELECT count(*) FROM $query");
		if ($counter == NULL)
			$counter = 0;
		if ($counter == 0) 
		{
				$arr = array();
		} 
		else 
		{
		 	$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
			$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
			$sql = "SELECT $fields FROM $query ORDER BY ";
	
			if ($_REQUEST['groupBy']>' ') 
			{
					$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ', ';
			} 
			
			if ($_REQUEST['sort']>' ') 
				$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
			else
				$sql .= $order;
		
			if ($start!='' || $end!='') 
			{
		   		$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
			$arr = getFetchArray($sql);
			//$counter = count($arr);
		}
		
		if (version_compare(PHP_VERSION,"5.2","<")) {    
			require_once("./JSON.php"); //if php<5.2 need JSON class
			$json = new Services_JSON();//instantiate new json object
			$data=$json->encode($arr);  //encode the data in json format
		} 
		else 
		{
			$data = json_encode_plus($arr);  //encode the data in json format
		}
	
	   /* If using ScriptTagProxy:  In order for the browser to process the returned
	       data, the server must wrap te data object with a call to a callback function,
	       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
	       If using HttpProxy no callback reference is to be specified */
		$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
		       
		echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
	}
			
	
}	

//------------------------------------------------------------------------------------------------------------
// update
// Esegue l'azione di convalida o respingimento 
//------------------------------------------------------------------------------------------------------------
function update()
{
	try
	{
		global $context;
		$nota  = quote_smart($_POST['Nota']);
		$idAzioneSpeciale = $_POST['idAzioneSpeciale'];
		$statoArrivo = quote_smart($_POST['statoDopo']);
		$statoPartenza =  quote_smart($_POST['statoPrima']);
		$titoloAzione = $_POST['TitoloAzione'];
		$IdUser = $context["IdUtente"];
		$sql ="";
		
		if($idAzioneSpeciale > 0)
		{
			// se l'utente ha modificato la data scadenza, esegue la modifica sull'Azione Speciale
			// il prolungamento dell'affido, se necessario, viene fatto nella funzione avviaConseguenzeConvalida
			if($_POST["DataScadenza"]!=null) {
			 	$sqlDataScadenza = "UPDATE azionespeciale set DataScadenza= '".ISODate($_POST["DataScadenza"])."'" 
					. " WHERE IdAzioneSpeciale =".$idAzioneSpeciale 
					. " AND DataScadenza<'".ISODate($_POST["DataScadenza"])."'";
					
				if (!execute($sqlDataScadenza))
					die ("{failure:true, msg: 'Operazione non eseguita'}");
			} else if($titoloAzione=="Richiesta di riaffido") { // per il riaffido la data è indispensabile
			  	die ("{failure:true, msg: 'La data di scadenza (termine riaffido) è obbligatoria'}");
			} 
			
			$sql = "UPDATE azionespeciale set Nota= ".$nota.",LastUpd = now(),LastUser=".quote_smart($context["Userid"]);
						
			if ($statoArrivo !=$statoPartenza)
			{
				$sql .= " ,Stato =".$statoArrivo. ", IdApprovatore=".$IdUser. ", DataApprovazione=now()";
				if ($statoArrivo=="'A'") // Approvazione: trae le conseguenze
					avviaConseguenzeConvalida($idAzioneSpeciale);
			}
			$sql .= " where IdAzioneSpeciale =".$idAzioneSpeciale;

			if (execute($sql))
				die ("{success:true, msg: 'Operazione eseguita'}");	
		}
		die ("{failure:true, msg: 'Operazione non eseguita'}");			
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		die ("{success:false, msg: 'Operazione non eseguita'}");	
	}
}	
//------------------------------------------------------------------------------------------------------------
// avviaConseguenzeConvalida
// Esegue gli eventuali passaggi di stato conseguenti ad una convalida
//------------------------------------------------------------------------------------------------------------
function avviaConseguenzeConvalida($idAzioneSpeciale)
{
	$dati = getRow("SELECT c.IdContratto,a.IdAzione,CodAzione,CodStatoRecupero,c.DataFineAffido,DataScadenza,a.PercSvalutazione
	                FROM azione a 
	                JOIN azionespeciale x ON a.IdAzione=x.IdAzione
	                JOIN contratto c ON c.IdContratto=x.IdContratto
	                JOIN statorecupero sr ON sr.IdStatoRecupero=c.IdStatoRecupero
	                 WHERE IdAzioneSpeciale=$idAzioneSpeciale");
	$CodAzione 	 = $dati["CodAzione"];
	$IdContratto = $dati["IdContratto"];
	$CodStatoRecupero = $dati["CodStatoRecupero"];
	switch ($CodAzione)
	{
		case 'RSS': // richiesta saldo e stralcio
			if (substr($CodStatoRecupero,0,3)!="WRK") // pratica non in workflow
			{
				// Salva lo stato di partenza
				$sql = "UPDATE contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero WHERE IdContratto=$IdContratto";
				if (!execute($sql))
					return FALSE;
			}
			// Avvia il workflow di Saldo e Stralcio
			return impostaStato('WRKPROPSS',$IdContratto);
		case 'PDR': // richiesta piano di rientro
			// Salva lo statopiano in convalida, del piano di rientro
			$sql = "UPDATE pianorientro SET IdStatoPiano = 2 WHERE IdContratto= $IdContratto";
			if(!execute($sql))
			  return FALSE;	
			break;
		case 'PPP': // richiesta prossimo passaggio a perdita
			if (substr($CodStatoRecupero,0,3)!="WRK") // pratica non in workflow
			{
				// Salva lo stato di partenza
				$sql = "UPDATE contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero WHERE IdContratto=$IdContratto";
				if (!execute($sql))
					return FALSE;
			}
			// Avvia il workflow di Write off
			return impostaStato('WRKPROPWO',$IdContratto);
		case 'RSD': // richiesta saldo e stralcio dilazionato
			if (substr($CodStatoRecupero,0,3)!="WRK") // pratica non in workflow
			{
				// Salva lo stato di partenza
				$sql = "UPDATE contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero WHERE IdContratto=$IdContratto";
				if (!execute($sql))
					return FALSE;
			}
			// Avvia il workflow di Saldo e stralcio dilazionato
			return impostaStato('WRKPROPSSD',$IdContratto);	
					  
	}
	
	if($dati["DataScadenza"]!=null) {
	  $dataScadenza = $dati["DataScadenza"];
	  $dataFineAffido = $dati["DataFineAffido"];
	  if ($dataScadenza>$dataFineAffido) {
	  	// Proroga affidamento ad agenzia
	  	$nome  = prorogaAgenzia($IdContratto,$dataScadenza);
		if ($nome===FALSE)
		   return FALSE;
		writeHistory($dati["IdAzione"],"Proroga affidamento fino al ".italianDate($dataScadenza),$IdContratto,'');	   
	  }	
	} 

	// se previsto, applica una percentuale di svalutazione
	applicaPercSvalutazione(0,$IdContratto,$dati["PercSvalutazione"]);
	
	return TRUE;
}

//--------------------------------------------------------------------
// filtroInsolutiAgenzia
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'agenzia sulla view v_insoluti
//--------------------------------------------------------------------
function filtroInsolutiAgenzia()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto (=subagenzia)
		$clause = "v.IdAgenzia=$IdReparto";
	else if (!userCanDo("READ_NONASSEGNATE")) // se non autorizzato a vedere le pratiche non assegnate, vede solo le proprie
		$clause = "IdAgente=$IdUtente";
	else                                 // altrimenti vede le proprie piu' quelle non assegnate
		$clause = "v.IdAgenzia=$IdReparto AND (IFNULL(IdAgente,0)=$IdUtente OR IdAgente IS NULL)";
	
	if (userCanDo("READ_AGENZIA")) // autorizzato a vedere tutte le pratiche della propria (super-)agenzia
	{
		$clause .= " OR v.IdAgenzia IN (SELECT IdReparto FROM reparto"
			." WHERE IdCompagnia = (SELECT IdCompagnia FROM reparto WHERE IdReparto=$IdReparto))";
	}
	/* aggiunge condizione per non vedere le pratiche affidate dopo il giorno limite di visibilità affidi */ 
	$dataMassima = $context["sysparms"]["DATA_ULT_VIS_STR"]; // vale la data visibilità massima per le pratiche STR/LEG
	if ($dataMassima=="") $dataMassima = '9999-12-31';
	return " AND v.DataInizioAffido<='$dataMassima' AND ($clause)";
}

?>
