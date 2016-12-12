<?php
//----------------------------------------------------------------------------------------------------------------------
// listaMessaggiDifferiti.php
// Scopo: 		riceve dal Db la lista dei messaggi differiti ed esegue i vari task
//----------------------------------------------------------------------------------------------------------------------
require_once("workflowFunc.php");

doMain();

function doMain()
{
	global $context;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	$tipo = $_REQUEST['tipo']; 
	$IdMessaggioDifferito =($_REQUEST['IdMessaggioDifferito']) ? ($_REQUEST['IdMessaggioDifferito']) : null;
	$testo =($_REQUEST['testo']) ? ($_REQUEST['testo']) : null;
	$CodContratto =($_REQUEST['CodContratto']) ? ($_REQUEST['CodContratto']) : null;
	$tipo= ($_REQUEST['tipo']) ? ($_REQUEST['tipo']) : null;
	$IdContratto =($_REQUEST['IdContratto']) ? ($_REQUEST['IdContratto']) : null;
						
	if(!$task)
	{	
		echo '{success:false, msg:"Errore nell\'elaborazione della richiesta."}'; // entra per export
		return;
	}
	 
	try
	{
		$conn = getDbConnection();	// ottiene la connessione al db oppure esce
		if (!$conn)
			die('{success:false, msg:"Errore"}');				 
		
		switch($task){
			case "leggi":
				switch ($tipo)
				{
					case "SMS_INS":
						$where = " WHERE Tipo='Sms' AND IdModello=3";
						break;
					case "SMS_ESA":
						$where = " WHERE Tipo='Sms' AND IdModello=13";
						break;
					case "LET_INS":
						$where = " WHERE Tipo='Lettera' AND IdModello=5";
						break;
					case "LET_DEO":
						$where = " WHERE Tipo='Lettera' AND IdModello IN (7,8)";
						break;
					case "LET_DBT":
						$where = " WHERE Tipo='Lettera' AND IdModello IN (113,115)";
						break;
					case "SMS_PRE":
						$where = " WHERE Tipo='Sms' AND IdModello=2";
						break;
				}
				$sql = "SELECT * from v_messaggi_differiti $where";				
			
				// prende il numero delle occorrenze della query
				$counter = getScalar("SELECT count(*) from v_messaggi_differiti $where");
								
				// se non ricevo nulla dalla tab imposto il counter a 0 e non eseguo la query
				if ($counter == NULL)
					$counter = 0;
					
				if ($counter == 0) 
				    $arr = array();
				else // se ci ricevo dati dalla query
				{
					// controllo se ci sono limiti di paginazione pertanto imposto i valori di inizio e limite nella query
					$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
					$end =   isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
					
					$sql .= " ORDER BY ";
					if ($_POST['groupBy']>' ') {
						$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
					} 
					if ($_POST['sort']>' ') 
						$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
					else
						$sql .= " DataCreazione DESC";
		
					if ($start!='' || $end!='') {
			    		$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
					}
		
					$arr = getFetchArray($sql);
				}// end else
				$data = json_encode_plus($arr);  //encode the data in json format
				$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
				echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
			break;
			
			case "Cancella":
				 if($IdMessaggioDifferito=="")
				 {
					trace("Non � stato ricevuto nessun IdMessaggioDifferito");
	 				die('{success:false, msg:"Errore nella cancellazione del massaggio differito."}');	
				 }
				 
				 $Txt=getscalar("select TestoMessaggio from messaggiodifferito where IdContratto=$IdContratto");
				 $sql="DELETE FROM messaggiodifferito where IdMessaggioDifferito=$IdMessaggioDifferito";

				 if(!execute($sql))
				 {
					trace("Impossibile cancellare il messaggio differito con Id=$IdMessaggioDifferito");
	 				die('{success:false, msg:"Errore nella cancellazione del massaggio differito."}');	
				 }
				 writelog("APP","Gestione messaggi differiti","Cancellazione $tipo diff. contr.".$CodContratto.($Txt?" Testo: $Txt":''),"CANC_MSGD");
				 echo '{success:true, msg:"Cancellazione eseguita."}';
				 break;
			
			case "Sospendi":
				 if($IdMessaggioDifferito=="")
				 {
					trace("Non � stato ricevuto nessun IdMessaggioDifferito");
					die('{success:false, msg:"Errore nella sospensione del massaggio differito."}');
				 }

				 $Txt=getscalar("select TestoMessaggio from messaggiodifferito where IdContratto=$IdContratto");
				 $sql="UPDATE messaggiodifferito set Stato='S' where IdMessaggioDifferito = $IdMessaggioDifferito";
				 
				 if(!execute($sql))
				 {
					trace("Impossibile sospendere il messaggio differito con Id=$IdMessaggioDifferito");
	 				die('{success:false, msg:"Errore nella sospensione del massaggio differito."}');	
				 }
				 
				 writelog("APP","Gestione messaggi differiti","Sospensione $tipo diff. contr.".$CodContratto.($Txt?" Testo: $Txt":''),"SOSP_MSGD");
				 echo '{success:true, msg:"Il messaggio selezionato non sara\' incluso tra quelli da inviare."}';
				 break;

			case "Attiva":
				 if($IdMessaggioDifferito=="")
				 {
					trace("Non � stato ricevuto nessun IdMessaggioDifferito");
	 				die('{success:false, msg:"Errore nell\'attivazione del massaggio differito."}');	
				 }
				 
				 $Txt=getscalar("select TestoMessaggio from messaggiodifferito where IdContratto=$IdContratto");
				 $sql="UPDATE messaggiodifferito set Stato='C' where IdMessaggioDifferito = $IdMessaggioDifferito";
				 
				 if(!execute($sql))
				 {
					trace("Impossibile attivare il messaggio differito con Id=$idMessaggioDifferito");
	 				die('{success:false, msg:"Errore nella riattivazione del massaggio differito."}');	
				 }
				 
				 writelog("APP","Gestione messaggi differiti","Attivazione $tipo diff. contr.".$CodContratto.($Txt?" Testo: $Txt":''),"ATT_MSGD");
				 echo '{success:true, msg:"Operazione eseguita: il messaggio selezionato sara\' incluso tra quelli da inviare."}';
				 break;
				 
			case "reinviaSms":
				 
//				if($testo=="")
//				 {
				 	$IdModello = ($_REQUEST['IdModello']) ? ($_REQUEST['IdModello']) : null;
				 	
				 	if(!inviaSingoloSmsDiff($IdModello,$IdContratto,$IdMessaggioDifferito))
					{
						die('{success:false, msg:"Errore nell\'inivio del sms."}');		
					}
					echo '{success:true, msg:"Invio sms eseguito."}';				 	
			 break;
				 
			case "reinviaEmail":
				
				// se il testo non e stato generato o per errore o perche e nello stato "creato" 
				// invio di nuovo il sms con la funzione inviaSingolaEmailDiff
				
//				if($testo=="")
//				 {
					$IdModello = ($_REQUEST['IdModello']) ? ($_REQUEST['IdModello']) : null;
				 	
					if(!inviaSingolaEmailDiff($IdModello,$IdContratto,$IdMessaggioDifferito))
					{
						die('{success:false, msg:"Errore nell\'inivio dell\'email."}');	
					}
					die('{success:true, msg:"Invio email eseguito."}');
					
/*				 }
		    	 // altrimenti invio di nuovo la mail con il testo gi� generato
				 else
		    	 {	
			 		$email=getscalar("select e.email "
							."from contratto cn left join v_email e on cn.IdCliente=e.IdCliente"
							." where cn.CodContratto='$CodContratto'");

					 if($email=="")
					 {
						$ErrMsg="Nessun indirizzo email per il cliente.";				 	
						UpdateStatoMsgDiff($IdMessaggioDifferito,"N",$ErrMsg,$testo);
						die('{success:false, msg:"Nessun indirizzo email per il cliente."}');
					 }
					 
					 $arr=explode("$", $testo);
					 $subject=$arr[0];
					 $body=$arr[1];
					
$resp=true;  // da commentare una volta attivato l'invio degli sms
//					 $resp = sendMail("",$email,$subject,$body,'');   
					 
					 if($resp==FALSE)
					 {
					 	UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'invio email.",$testo);
					 	trace("Errore nell'invio email.");
					 	die('{success:false, msg:"Errore nell\'inivio dell\'email. Errore:'.$resp.'"}');
		  			 }

					 UpdateStatoMsgDiff($IdMessaggioDifferito,"E","Email inviata",$testo);
		  			 $idAzione=getscalar("select IdAzione from azione where TitoloAzione='Invio e-mail'");
					 writeHistory($idAzione,"Inviata email all'indirizzo $email",$IdContratto,$testo);
		  			 echo '{success:true, msg:"Invio email eseguito."}';
		    	 } // fine else
*/
		    break;
		    case "Sms": // visualizza testo Sms
				
		    	$IdModello=($_REQUEST['IdModello']) ? ($_REQUEST['IdModello']) : null;
				$nomeModelloSms = getScalar("SELECT FileName FROM modello WHERE IdModello=".$IdModello);
			  	if($nomeModelloSms=="")
			  	{
			      trace("Modello email" .$IdModello." non presente nella tabella Modello.",false);
			      die('{success:false, msg:"Il modello di messaggio SMS non � definito."}');
			  	}
			  	
			  	//apro il file json e lo decodifico
			  	$modelloSms = json_decode(file_get_contents(TEMPLATE_PATH.'/'.$nomeModelloSms));
		        if ($modelloSms==NULL)
			  	{
			      trace("Modello email" .$IdModello." non presente nella tabella Modello.",false);
			      die('{success:false, msg:"Il modello di messaggio SMS non � definito correttamente."}');
			    }
		        // prendo i dati dalla vista (usa contratto_precrimine perch� pi� completa
				$ins = getRow("SELECT * FROM v_contratto_precrimine WHERE IdContratto=".$IdContratto);
		        if(empty($ins))
		        {
		          trace("Nessun dato ricevuto dalla vista  v_contratto_precrimine per il contratto.".$IdContratto,false);
		          die('{success:false, msg:"Errore imprevisto, riprovare."}');	
		        }
        
		        // sostituzione dei parametri nel testo 
		        $testoSms = replaceVariables($modelloSms->testoSMS,$ins);
		    	//trace($testoSms);
		        die('{success:true, msg:'.json_encode_plus($testoSms).'}');
		    break;	
		    
		    case "Email":
		    	
		    	$IdModello=($_REQUEST['IdModello']) ? ($_REQUEST['IdModello']) : null;
		    	
				//prendo il nome del file modello
			  	$nomeModelloEmail = getScalar("SELECT FileName FROM modello WHERE IdModello=".$IdModello);
			  	if(!($nomeModelloEmail>""))
			  	{
			      trace("Modello email" .$IdModello." non presente nella tabella Modello.",false);
			      die('{success:false, msg:"Errore imprevisto, riprovare."}');	
			  	}
			  	
			  	// prendo dalla vista i dati 
				$ins = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=".$IdContratto);
			  	if(empty($ins))
			    {
			      trace("Non sono stati ricevuti dati dalla vista v_contratto_lettera per il contratto". $IdContratto,false);
			      die('{success:false, msg:"Errore imprevisto, riprovare."}');
			    }
			    $retArr=preparaEmail($nomeModelloEmail,$ins);
	    	    $testoEmail=$retArr[0]."<BR><BR>".$retArr[1];
				die('{success:true, msg:'.json_encode_plus($testoEmail).'}');
		    break;		

			default:
				 die('{success:false, msg:"Operazione richiesta eseguita"}');
			break;
			
		}//fine switch
	}// fine try
	catch (Exception $e)
	{
		trace($e->getMessage());
		die('{success:false, msg:"Errore imprevisto, riprovare."}');
	}
}
?>
