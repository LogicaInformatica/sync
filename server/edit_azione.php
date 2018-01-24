<?php
require_once("engineFunc.php");
require_once('riempimentoOptInsoluti.php');

try
{
	$idStatoAzione = $_REQUEST['idstatoazione'];
	if (!$idStatoAzione) { // se viene passato il codice azione
		$idStatoAzione = getScalar("SELECT IdStatoAzione from statoazione sa JOIN azione a ON a.IdAzione=sa.IdAzione where CodAzione='".$_REQUEST['codazione']."'");
	}
	$idContratti = $_REQUEST['idcontratti']; 

	$idContratti = json_decode(stripslashes($idContratti)); 
	$descrEvento = "";
	$nota = $_REQUEST['nota'];

	// Controllo parametro
	if (!is_array($idContratti))
		Throw new Exception("Nessuna pratica selezionata per l'operazione; contratti=".var_dump($idcontratti)); 
		
	// Legge la riga Azione
	$azione    = getRow("SELECT a.* FROM azione a,statoazione sa WHERE IdStatoAzione=$idStatoAzione AND a.IdAzione=sa.IdAzione");
	if (!$azione)  // niente form specificato
		Throw new Exception("Operazione non riuscita perche' non correttamente configurata nel database (IdStatoAzione=$idStatoAzione)"); 
		
	$idAzione = $azione["IdAzione"];
	$form = $azione['TipoFormAzione']; 		// sigla del form azione utilizzato
	$descrEvento = $azione['TitoloAzione']; 

	$IdUser = $context['IdUtente'];
	$userid = $context['Userid'];
	$nomeutente = $context['NomeUtente'];
	
	$azioneSpeciale = $azione["FlagSpeciale"];
	
	//-----------------------------------------------------------------------
	// Prepara alcuni parametri standard per la eseguiAzione
	//-----------------------------------------------------------------------
	$parameters = array();
	//$parameters["TIPORICHIESTA"] = "abbuono";
	$parameters["NOMEAUTORE"] = $nomeutente;
	$parameters["DATARICHIESTA"] = date("d/m/Y");
	$parameters["NOTA"] = $nota;
	$parameters["*AUTHOR"] = "U".$IdUser;
	$parameters["AZIONE"] = $azione["TitoloAzione"];
	$parameters["*DESTINATARIRIF"]=array();
	// parametro per la chiamata da mail di workflow
	$parameters["PATH_WRKFLW"] = LINK_URL."main.php?wrkflw=WRFL";
	
	//-----------------------------------------------------------------------
	// Prepara l'array dei destinatari per la revoca dei contratti selezionati
	//-----------------------------------------------------------------------
	$manipolatori = array();
	//-----------------------------------------------------------------------
	// Loop di registrazioni specifiche per le varie azioni
	//-----------------------------------------------------------------------
	foreach ($idContratti as $idContratto) 
	{
		$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$idContratto");  //
		if (!$pratica)   
			Throw new Exception("Operazione non riuscita perche' la pratica con id=$idContratto non esiste");
				
		// Crea il parametro HREF con il link alla pratica
		$parameters["HREF"] = "$protocol://$host$uri/main.php?idcontratto=$idContratto&numpratica="
			. $pratica["CodContratto"]."&idcliente=".$pratica["IdCliente"]."&cliente=".$pratica["NomeCliente"];
		// Azioni specifiche
		// ATTENZIONE: TENERE I VARI "CASE" IN ORDINE ALFABETICO
		
			
		//trace($form);
		switch($form)
		{
			case "AffidaAg":	// affida il contratto ad un'agenzia
				$ids = split(",",$_REQUEST["IdAgenzia"]); // id dell'agenzia + "," + idregolaprovvigione
				$idagenzia = $ids[0]; // id dell'agenzia 
				$idprovv = $ids[1]; // id della regola provvigionale 
				$data = ISODate($_REQUEST["data"]);  // Data fine affido
//				trace("edit azione chiama affidaAgenzia con $idProvv=$idprovv",FALSE);
				$nome  = affidaAgenzia($idContratto,$idagenzia,$data,FALSE,NULL,$idprovv);
				if ($nome===FALSE)
					Throw new Exception("Affidamento non riuscito a causa del seguente errore: ".getLastError()); 
					
				assignAgent($idContratto); // assegna automaticamente ad un operatore di agenzia secondo le regole

				assign($idContratto); // assegna automaticamente ad un operatore TFSI secondo le regole
				
				resetBloccoAffido($idContratto,'N');	
				
				$esitoAzione = "Effettuato affidamento all'agenzia ".$nome;
				if ($idprovv>0)
				{
					$codprovv = getScalar("SELECT CodRegolaProvvigione FROM regolaprovvigione WHERE IdRegolaProvvigione=$idprovv");
					writeHistory($idAzione,"Affidamento all'agenzia $nome (cod.provv. $codprovv)",$idContratto,$nota);	
				}	
				else
					writeHistory($idAzione,"Affidamento all'agenzia $nome",$idContratto,$nota);	
				break;
			
			case "Allega":
				//trace("allega doc. {$_REQUEST["titolo"]}",false);
				if (allegaDocumento($pratica,$_REQUEST["IdTipoAllegato"],$_REQUEST["titolo"],$_REQUEST["FlagRiservato"]=="on" ?'Y':'N'))
				{
					$fileName = $_FILES['docPath']['name'];     
					$esitoAzione = "Allegato documento ".urldecode($fileName);
					//writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
				}
				else
					Throw new Exception("Operazione non riuscita a causa del seguente errore: ".getLastError());				
				break;

			case "Annulla": // annullamento richiesta in un workflow
				//ripristinaStato($idContratto); fatto tramite azioni automatiche
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception("Annullamento non riuscito");
				writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				break;

			case "AnnullaCES": // annullamento richiesta di Cessione
				$annulloMultiplo=$_REQUEST["annulloMultiplo"];
		        if($annulloMultiplo=='true'){
		          //ripristinaStato($idContratto); fatto tramite azioni automatiche
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Annullamento non riuscito");
				  writeHistory($idAzione,$descrEvento,$idContratto,$nota);	
		        } else {
		        	//ripristinaStato($idContratto); fatto tramite azioni automatiche
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
		            if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception("Annullamento non riuscito");
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		        }
						        				
				break;		
			
			case "AnnullaDBT": 
				$annulloMultiplo=$_REQUEST["annulloMultiplo"];
		        if($annulloMultiplo=='true'){
		          //ripristinaStato($idContratto); fatto tramite azioni automatiche
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Annullamento non riuscito");
				  writeHistory($idAzione,$descrEvento,$idContratto,$nota);	
		        } else {
					// annullamento richiesta in un workflow
					//ripristinaStato($idContratto); fatto tramite azioni automatiche
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Annullamento non riuscito");
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }	
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        break;
			
			case "AnnullaSS": // annullamento richiesta in un Saldo e stralcio
				$annulloMultiplo=$_REQUEST["annulloMultiplo"];
				
				if(!udpdateCampiPropostaSS($idContratto,NULL,NULL)) 
				     Throw new Exception(getLastError());
				
		        if($annulloMultiplo=='true'){
		          //ripristinaStato($idContratto); fatto tramite azioni automatiche
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Annullamento non riuscito");
				  writeHistory($idAzione,$descrEvento,$idContratto,$nota);	
		        } else {
		        	//ripristinaStato($idContratto); fatto tramite azioni automatiche
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
	                if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Annullamento non riuscito");
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          
				break;
				
			case "AnnullaSSDIL": // annullamento richiesta in un Saldo e stralcio dilazionato
				
				if(!udpdateCampiPropostaSS($idContratto,NULL,NULL)) 
				     Throw new Exception(getLastError());
				
				//ripristinaStato($idContratto); fatto tramite azioni automatiche
				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
	            if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
				  Throw new Exception("Annullamento non riuscito");
				writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		        				
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				break;	

			case "AnnullaWO": // annullamento richiesta del Write off
				$annulloMultiplo=$_REQUEST["annulloMultiplo"];
		        if($annulloMultiplo=='true'){
		          //ripristinaStato($idContratto); fatto tramite azioni automatiche
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Annullamento non riuscito");
				  writeHistory($idAzione,$descrEvento,$idContratto,$nota);	
		        } else {
		        	//ripristinaStato($idContratto); fatto tramite azioni automatiche
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
		            if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception("Annullamento non riuscito");
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }
						        				
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        break;		
				
			case "AssegnaAg":	// assegna il contratto ad un operatore di agenzia
				$newop = $_REQUEST["IdUtente"]; // id dell'operatore assegnato
				$nome  = getScalar("SELECT NomeUtente FROM utente WHERE IdUtente=0$newop"); // nome dell'operatore assegnato
				if (!assegnaAgente($idContratto,$newop,false))
					Throw new Exception("Assegnazione non riuscita a causa del seguente errore: ".getLastError()); 
				$esitoAzione = "Effettuata assegnazione all'operatore di agenzia ".$nome;
				writeHistory($idAzione,"Assegnazione all'operatore di agenzia $nome",$idContratto,$nota);		
				break;
				
			case "AssegnaOp":	// assegna il contratto ad un operatore
				$newop = $_REQUEST["IdUtente"]; // id dell'operatore assegnato
				$testoAzione = $newop>''?"Assegnazione":"Revoca assegnazione";
				$nome  = getScalar("SELECT NomeUtente FROM utente WHERE IdUtente=0$newop"); // nome dell'operatore assegnato
				if (!assegnaOperatore($idContratto,$newop,false))
					Throw new Exception("$testoAzione non riuscita a causa del seguente errore: ".getLastError()); 
				$esitoAzione = "Effettuata $testoAzione all'operatore ".$nome;
				writeHistory($idAzione,"$testoAzione all'operatore $nome",$idContratto,$nota);		
				break;

			case "AssegnaTeam":	// assegna il contratto ad un team (=reparto interno)
				$newop = $_REQUEST["IdReparto"]; // id del team assegnato
				$nome  = getScalar("SELECT TitoloUfficio FROM reparto WHERE IdReparto=0$newop"); // nome del teamassegnato
				if (!assegnaTeam($idContratto,$newop,false))
					Throw new Exception("Assegnazione non riuscita a causa del seguente errore: ".getLastError());
				$esitoAzione = "Effettuata assegnazione a '$nome'";
				writeHistory($idAzione,"Assegnazione al '$nome'",$idContratto,$nota);
				break;
				
			case "Autorizza":	// Autorizzazione (in tutti i workflow)
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Approvazione non riuscita");
				//trova i responsabili precedenti a cui inoltrare le mails di accettazione
				$codAzione = $azione['CodAzione'];
				emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				$parameters["*DESTINATARIRIF"]=$manipolatori;
				writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				break;				

			case "AutorizzaCES":	// Autorizzazione Cessione
				
				$autorizMultiplo=$_REQUEST["autorizMultiplo"];
		        if($autorizMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in Cessione".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
									
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  } else{
					  $data="Non specificata";
					  $esitoAzione = "$addEsito";
				    }
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  
				  //creazione documentazione cessione
				  creaDocumentazione($idContratto, $pratica);
				  
				  //creazione file zip
				  $fileZip=creaZipDatiCessione($pratica);
					
				  $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;	
				  
				   writeHistory($idAzione,$descrEvento,$idContratto,$nota);
		        } else {
				    //gestione scadenza e proposta
					$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in Cessione ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
					
		            //creazione documentazione cessione
				    creaDocumentazione($idContratto, $pratica);
					
					//creazione file zip
					$fileZip=creaZipDatiCessione($pratica);
					$link = REL_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/".$fileZip;
					$esitoAzione .= "<br>Allegata documentazione della pratica al link: <a href='$link'>$link</a>";
		            										
					$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }	
		        
				  // chiusura forzata richieste in attesa di convalida
				  chiudeConvalide($idContratto);
		         break;		
				
			case "AutorizzaDBT":	// Autorizzazione DBT
				$autorizMultiplo=$_REQUEST["autorizMultiplo"];
		        if($autorizMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  }else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
				   }
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception("Approvazione non riuscita");
				  //trova i responsabili precedenti a cui inoltrare le mails di accettazione
				  $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
				  writeHistory($idAzione,$descrEvento.".". $esitoAzione ,$idContratto,$nota);
		        } else {
		        	//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
		        	//forzatura a riaffido
			        $idOldRegolaProvvigione = $_REQUEST["idOldRegolaProvvigione"]; 
					$idRegolaProvvigione = $_REQUEST["IdRegolaProvvigione"];
					if($idOldRegolaProvvigione!=$idRegolaProvvigione && $idRegolaProvvigione!='-1' && $idRegolaProvvigione!='-2') {
					  $nome  = forzaAffidoAgenzia($idContratto,$idRegolaProvvigione,"",$idAzione,false,true);
					  if ($nome===FALSE)
						Throw new Exception(getLastError()); 
					  $esitoAzione = $esitoAzione."<br/>Registrata forzatura del prossimo affidamento automatico all'agenzia ".$nome;
	                
					  //writeHistory($idAzione,$esitoAzione,$idContratto,$nota);	
					}
					
					$flagIrr = $_REQUEST["chkFlag"]?'Y':'N';
	                $flagIpo = $_REQUEST["chkFlagIpoteca"]?'Y':'N';
	                $flagConc = $_REQUEST["chkFlagConcorsuale"]?'Y':'N';
					if($_REQUEST["dataVendita"]!=''){
					  $dataVend = "'".ISODate($_REQUEST["dataVendita"])."'";
					  if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"],$dataVend)) 
					  Throw new Exception(getLastError());
					} else {
						if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"])) 
					    Throw new Exception(getLastError());
					}
	                //writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota); 
					
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Approvazione non riuscita");
					//trova i responsabili precedenti a cui inoltrare le mails di accettazione
					$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,$descrEvento.".". $esitoAzione,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }
				break;
				
			case "AutorizzaSS":	// Autorizzazione Saldo e Stralcio
				$autorizMultiplo=$_REQUEST["autorizMultiplo"];
		        if($autorizMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in Saldo e stralcio ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  }else{
					 $data="Non specificata";
					 $esitoAzione = "$addEsito";
				   }
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  
				  $totDebResiduo=number_format(str_replace('.','',$_REQUEST["totDebResiduo"]),2,'.','');
				  $totImpProposti=number_format(str_replace('.','',$_REQUEST["totImpProposti"]),2,'.','');
				  $impoAbbonato = $totDebResiduo - $totImpProposti;
				  $percAbbuono= round((($impoAbbonato/$totDebResiduo)*100),2); // arrotondo ai due decimali
	              
				  if(!updatePercSvalutazione($percAbbuono,$idContratto))
				       Throw new Exception(getLastError());

  				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception("Approvazione non riuscita");
				  //trova i responsabili precedenti a cui inoltrare le mails di accettazione
				  $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
				  writeHistory($idAzione,$descrEvento,$idContratto,$nota);
		        } else {
		        	//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in Saldo e stralcio ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
					
					$percAbb=str_replace(' ','',$_REQUEST["percAbbuono"]);
					$percAbbuono=explode("%", $percAbb);
					
					if(!updatePercSvalutazione($percAbbuono[0],$idContratto))
				       Throw new Exception(getLastError());
				       
					$dataSS = ISODate($_REQUEST["dataPagamento"]);
	                $impSS  = $_REQUEST["importoProposto"];
	                $htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
	                if (!udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)) 
					  Throw new Exception(getLastError());

					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Approvazione non riuscita");
					//trova i responsabili precedenti a cui inoltrare le mails di accettazione
					$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }
				
				break;

			case "AutorizzaSSDIL":	// Autorizzazione Saldo e Stralcio dilazionato
				
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio Saldo e stralcio dilazionato ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
									
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());
				//$dataSS = ISODate($_REQUEST["dataPagamento"]);
	            $impSS  = $_REQUEST["importoProposto"];
	            //Inserimento saldo e stralcio dilazionato
	            if (!udpdateCampiPropostaSS($idContratto,NULL,$impSS)) 
				  	Throw new Exception(getLastError());
				$dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
				$dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
				//Inserimento piano di rientro
				if(!updateCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
				  Throw new Exception(getLastError());
				if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
				  Throw new Exception("Approvazione non riuscita");
				//trova i responsabili precedenti a cui inoltrare le mails di accettazione
				$codAzione = $azione['CodAzione'];
				emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				$parameters["*DESTINATARIRIF"]=$manipolatori;
				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
				writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		        
				break;	
				
			case "AutorizzaWO":	// Autorizzazione Write off
				
				$autorizMultiplo=$_REQUEST["autorizMultiplo"];
		        if($autorizMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in write off ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
									
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  } else{
					  $data="Non specificata";
					  $esitoAzione = "$addEsito";
				    }
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
					
				  $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;	
				  
				   writeHistory($idAzione,$descrEvento,$idContratto,$nota);
		        } else {
				// Scrive nella tabella writeoff
					if (!saveWriteoff($idContratto))
						Throw new Exception(getLastError());
				
		        	//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in Write off ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["Verifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
										
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
					
					$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		        }	
				// chiusura forzata richieste in attesa di convalida
				chiudeConvalide($idContratto);
		        
				break;		

			case "Base": // Form base: solo la nota e il titolo dell'azione, pi� azioni specifiche
				//trace("in singolo: ".$azione["CodAzione"]);
				switch ($azione["CodAzione"])
				{					
					case "EXIT": // forza fuori recupero
						if (!forzaFuoriRecupero($idContratto))
							Throw new Exception("Forzatura non riuscita a causa del seguente errore: ".getLastError()); 
						$esitoAzione = "Contratto forzato fuori dalla gestione recupero";
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
						break;

					case "EXPERIAN": // accoda l'id del contratto a quelli da inviare al prossimo invio Experian
						if (!execute("REPLACE INTO experianqueue VALUES({$pratica['IdCliente']})"))
							Throw new Exception(getLastError());
						$esitoAzione = "Contratto accodato per il prossimo invio di richieste ad Experian";
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
						break;
						
						
					case "ATS": // forza in stato "in attesa di affido STR/LEG"
					case "ATT": // forza in stato "in attesa di affido"
					case "ATP": // forza in stato "in attesa di PAP/CES"
					case "INT": // forza in stato "in lavorazione interna"
						if ($pratica["IdAgenzia"]>0) // contratto in affidamento: revoca
							if (!revocaAgenzia($idContratto,TRUE))
								Throw new Exception("Cambio di stato non riuscito a causa del seguente errore: ".getLastError());
								
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid)) // automatismo: il cambio stato � descritto dalla riga di statoazione
							Throw new Exception("Cambio di stato non riuscito");

						if ($azione["CodAzione"]!='INT')
							resetBloccoAffido($idContratto,'U');	
						
						$esitoAzione = ($azione["CodAzione"]=="ATT")?"Pratica portata in stato 'in attesa di affido'":
										(($azione["CodAzione"]=="ATS")?"Pratica portata in stato 'in attesa di affido STR/LEG'":
						                  (($azione["CodAzione"]=="ATP")?"Pratica portata in stato 'in attesa di PAP/CES'":"Pratica portata in stato 'in lavorazione interna'"));
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
						
						toglieClasseExit($idContratto); // toglie classificazione EXIT se ce l'ha
						break;
						
					case "PPRO": // Proponi proroga per una data agenzia
						//trova la data dell'ultima proposta
						$sqlLPP="select DATE(sr.DataEvento) as DataLastPP
								from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
								where sr.idcontratto = $idContratto and sr.idazione=140 
								and DATE(sr.DataEvento) <= v.DataFineAffido
								Order by DATE(sr.DataEvento) desc";
						$rDateLP = getRow($sqlLPP);
						$dataLP = $rDateLP['DataLastPP'];
						//trace("data $dataLP");
						
						if($dataLP!='')
						{
							//trova se dopo l'ultima proposta ci son state risposte
							$sqlPA="select count(*)
									from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
									where sr.idcontratto = $idContratto and sr.idazione=8
									and DATE(sr.DataEvento) <= v.DataFineAffido
									and DATE(sr.DataEvento) >= '$dataLP'";
							$resA = getScalar($sqlPA);
							//trace("resA $resA");
							$sqlPR="select count(*)
									from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
									where sr.idcontratto = $idContratto and sr.idazione=142
									and DATE(sr.DataEvento) <= v.DataFineAffido
									and DATE(sr.DataEvento) >= '$dataLP'";
							$resR = getScalar($sqlPR);
							//trace("resR $resR");
								
							//se ci sono state risposte allora � possibile inoltrare di nuovo una richiesta di proroga
							//altrimenti � ancora in attesa
							if(($resA!=0)||($resR!=0))
							{
								//trace("proposta per $idContratto");
								$esitoAzione = "Proposta proroga della pratica";
								writeHistory($idAzione,$esitoAzione,$idContratto,$nota);	
							}
						}else{
							//trace("proposta per $idContratto");
							$esitoAzione = "Proposta proroga della pratica";
							writeHistory($idAzione,$esitoAzione,$idContratto,$nota);	
						}
						break;
					case "RIFPPRO": // Rifiuta proroga per una data agenzia
						$sql="select count(*)
								from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
								where sr.idcontratto = $idContratto and sr.idazione=140 
								AND DATE(sr.DataEvento) BETWEEN v.DataInizioAffido AND v.DataFineAffido
								AND DATE(NOW()) BETWEEN v.DataInizioAffido AND v.DataFineAffido";
								//and DATE(sr.DataEvento) <= v.DataFineAffido";
						$res = getScalar($sql);
						//trace("azione $idAzione");
						//trace("res $res");
						//se vi � una richiesta VALIDA allora la si prende in considerazione(caso in cui si sbagli e 
						//si provi a rifiutare una pratica su cui non vi � stata fatta richiesta di proroga)
						if($res>0)
						{
							//trova la data dell'ultima proposta di proroga e usala per sapere se dopo vi son
							//state risposte.
							$sql="select DATE(sr.DataEvento) as DataPP
								from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
								where sr.idcontratto = $idContratto and sr.idazione=140
								AND DATE(sr.DataEvento) BETWEEN v.DataInizioAffido AND v.DataFineAffido
								Order by DATE(sr.DataEvento) desc";
							$rDate = getRow($sql);
							$dataPP = $rDate['DataPP'];
							//trace("data $dataPP");
							
							$sqlAcc="select count(*)
									from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
									where sr.idcontratto = $idContratto and sr.idazione=8
									and DATE(DataEvento) BETWEEN '$dataPP' AND DataFineAffido";
									//and DATE(sr.DataEvento) <= v.DataFineAffido";
							$resAcc = getScalar($sqlAcc);
							
							/*$sqlRif="select count(*)
									from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
									where sr.idcontratto = $idContratto and sr.idazione=142
									and DATE(DataEvento) BETWEEN '$dataPP' AND DataFineAffido";
									//and DATE(sr.DataEvento) <= v.DataFineAffido";
							$resRif = getScalar($sqlRif);*/
							//per ora pu� rifiutare + volte la stessa pratica lo stesso giorno anche se � logicamente 
							//non accettabile come cosa visto che lo stato appare come rifiutato di gi�
							//un ulteriore controllo se necessario va implementato sull'ultima data ed ORA dell'ultimo rifiuto.
							//trace("resAcc $resAcc");
							//trace("resRif $resRif");
							//se non vi sono state risposte alla richiesta in questione ne negative ne positive la 
							//si rifiuta salvandone lo stato, altrimenti l'azione non � valida e viene ignorata
							if($resAcc==0)
							{
								//trace("Rifiuto per $idContratto");
								$esitoAzione = "Rifiuto proroga della pratica";
								writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
							}
						}			
						break;
						/* obsoleto
					case "WF_PASSAGGIO_DBT": 
						//cambia lo stato settandolo a quello precedente alla procedura di Workflow ()
						//cambia la classe del contratto e la setta su quella di workflow
						//ripristina stato
						ripristinaStato($idContratto);
						//cambia classe
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Inoltro non riuscito");
						
						writeHistory($idAzione,$descrEvento,$idContratto,$nota);
						break;
						*/
					case "WF_CONSB": //cambia lo stato settandolo a quello di consegna
						//cambio stato
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Operazione non riuscita");
						writeHistory($idAzione,$descrEvento,$idContratto,$nota);
						break;
					case "WF_CONFB": //cambia lo stato settandolo 
						//cambio stato
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception("Operazione non riuscita");
						writeHistory($idAzione,$descrEvento,$idContratto,$nota);
						break;
					
					default: 
						// le azioni speciali vengono registrate nella relativa tabella	
						/*if ($azioneSpeciale == "Y")
						{
							$idAzioneSpeciale = azioneSpeciale($idAzione,$idContratto,$nota);
							if ($idAzioneSpeciale == false)
							Throw new Exception("Inoltro non riuscito");
							writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale);
						}
						else
						{
						// le azioni normali (non speciali) generiche producono solo azioni automatiche e registrazione nella history
							if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
								Throw new Exception("Inoltro non riuscito");
							writeHistory($idAzione,$descrEvento,$idContratto,$nota);	
						}*/	
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
						writeHistory($idAzione,$descrEvento,$idContratto,$nota);
						
						break;
				}
				break;
				
            
			case "BaseConImporto": // Form base con importo residuo: solo la nota e il titolo dell'azione, pi� azioni specifiche
				//trace("in singolo: ".$azione["CodAzione"]);
				switch ($azione["CodAzione"])
				{					
					case "WF_OPO": // write off
						if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError()); 
						
						$esitoAzione = "Richiesta di write off per un importo di ".$_REQUEST["ImportoDebito"];
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
						break;
						
						$esitoAzione = "Richiesta di write off per un importo di ".$_REQUEST["ImportoDebito"];
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
						break;
						
					default: // tutte le altre azioni generiche producono solo azioni automatiche e registrazione nella history
						writeHistory($idAzione,$descrEvento,$idContratto,$nota);
						break; 
				}
				break; 
							
			case 'BaseLeg':
		        if ($azioneSpeciale == "Y") // controlla, perch� alcune azioni possono essere cambiate in "normali" dopo
				                            // la loro progettazione
				{
					registraAzioneSpeciale();
				}
				else
				{
					// data di verifica	
				    $parameters['TESTOSCADENZA'] = "Verifica $descrEvento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				    $addEsito = "Richiesta di $descrEvento effettuata.";
				    $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataScadenza"]);
				    GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				    $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				    // history
				    writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
				}
				
				break; 
			case 'CambioCategoria':
				$idCategoria = $_REQUEST["IdCategoria"]; // id della categoria
				$categoria  = cambioCategoria($idContratto,$idCategoria);
				if ($categoria===FALSE)
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuato cambio categoria";
				if($categoria =="")
					$categoria = "Rimossa categoria";
				else
					$categoria = "Assegnata categoria $categoria";	
				writeHistory($idAzione,$categoria,$idContratto,$nota);	
			break;
            case 'CambioCatMaxirata':
				$idCategoriaMaxirata = $_REQUEST["IdCategoriaMaxirata"]; // id della categoria
				$nota = $_REQUEST["nota"]; //nota inserita
				$categoriaMaxirata  = cambioCategoriaMaxirata($idContratto,$idCategoriaMaxirata);
				if ($categoriaMaxirata===FALSE)
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuato cambio categoria maxirata";
				if($categoriaMaxirata =="")
					$categoriaMaxirata = "Rimossa categoria maxirata";
				else
					$categoriaMaxirata = "Assegnata categoria maxirata $categoriaMaxirata";	
				writeHistory($idAzione,$categoriaMaxirata,$idContratto,$nota);	
			break;
			case 'CambioCatRiscLeasing':
				$idCategoriaRiscattoLeasing = $_REQUEST["IdCategoriaRiscattoLeasing"]; // id della categoria
				$nota = $_REQUEST["nota"]; //nota inserita
				$categoriaRiscattoLeasing  = cambioCategoriaRiscattoLeasing($idContratto,$idCategoriaRiscattoLeasing);
				if ($categoriaRiscattoLeasing===FALSE)
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuato cambio categoria riscatti scaduti";
				if($categoriaRiscattoLeasing =="")
					$categoriaRiscattoLeasing = "Rimossa categoria riscatti scaduti";
				else
					$categoriaRiscattoLeasing = "Assegnata categoria riscatti scaduti $categoriaRiscattoLeasing";	
				writeHistory($idAzione,$categoriaRiscattoLeasing,$idContratto,$nota);	
			break;
			case 'CambioDataRiscScad':
				$dataChiusura = ISODate($_REQUEST["dataChiusura"]); // data chiusura da aggiornare
				if (!cambioDataRiscattoScaduto($idContratto,$dataChiusura))
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuato cambio data chiusura riscatto leasing scaduto";
				writeHistory($idAzione,"Effettuato cambio data chiusura riscatto leasing scaduto",$idContratto,$nota);	
			break;
			case 'CambioStatoLegale':
				$idStatoLegale = $_REQUEST["IdStatoLegale"]; // id dello stato legale
				$statolegale  = cambioStatoLegale($idContratto,$idStatoLegale);
				if ($statolegale===FALSE)
					Throw new Exception(getLastError());
				$esitoAzione = "Effettuato cambio di stato legale";
				if($statolegale =="")
					$statolegale = "Rimosso lo stato legale";
				else
					$statolegale = "Assegnato stato legale $statolegale";
				writeHistory($idAzione,$statolegale,$idContratto,$nota);
				break;
			case 'CambioStatoStragiud':
				$idStatoStragiudiziale = $_REQUEST["IdStatoStragiudiziale"]; // id dello stato stragiudiziale
				$statostragiudiziale  = cambioStatoStragiud($idContratto,$idStatoStragiudiziale);// vedi funzione cambioStatoLegale
				if ($statostragiudiziale===FALSE)
					Throw new Exception(getLastError());
					$esitoAzione = "Effettuato cambio di stato stragiudiziale";
					if($statostragiudiziale =="")
						$statostragiudiziale = "Rimosso lo stato stragiudiziale";
					else
						$statostragiudiziale = "Assegnato stato stragiudiziale $statostragiudiziale";
				writeHistory($idAzione,$statostragiudiziale,$idContratto,$nota);
				break;	
			case "CambiaClasse":	// Cambio classificazione
				$sql = "UPDATE contratto SET IdClasse={$_REQUEST["IdClasse"]},"
				. "LastUser='$userid',DataCambioClasse=CURDATE() WHERE IdContratto=$idContratto";
				if (!execute($sql))
					Throw new Exception(getLastError());
				$esitoAzione = "Effettuato cambio di classificazione";
				$classe = getScalar("SELECT TitoloClasse FROM classificazione WHERE IdClasse={$_REQUEST["IdClasse"]}");
				writeHistory($idAzione,"Effettuato cambio di classificazione in '$classe'",$idContratto,$nota);
				break;

			case "CambioStato":	// Cambio stato recupero  
				$sql = "UPDATE contratto SET IdStatoRecupero={$_REQUEST["IdStatoRecupero"]},"
				. "LastUser='$userid',DataCambioStato=CURDATE() WHERE IdContratto=$idContratto";
				if (!execute($sql))
					Throw new Exception(getLastError());
				$esitoAzione = "Effettuato cambio di stato";
				$stato = getScalar("SELECT TitoloStatoRecupero FROM statorecupero WHERE IdStatoRecupero={$_REQUEST["IdStatoRecupero"]}");
				writeHistory($idAzione,"Effettuato cambio di stato in '$stato'",$idContratto,$nota);
				break;
				
			case "Chiusura":	// Chiusura lavorazione
				$esito = $_REQUEST["esito"]; // esito P/N
				$titoloEsito = $esito=='P'?'positivo':'negativo';
				if (!chiudiLavorazione($idContratto,$esito))
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuata chiusura lavorazione con esito $titoloEsito";
				writeHistory($idAzione,"Chiusura lavorazione contratto con esito $titoloEsito",$idContratto,$nota);		
				break;
			
			case "Data":	// Azione con data di scadenza
				$data = italianDate($_REQUEST["data"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["data"],true);
				if ($dataScad!=''){
					switch ($azione["CodAzione"])
					{
						case "LMS": // lasciato messaggio
							$parameters['TESTOSCADENZA'] = "Verifica cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"]
								." (lasciato messaggio il ".italianDate().")";
							break;
						case "PPG": // promessa di pagamento
							$parameters['TESTOSCADENZA'] = "Verifica promessa di pagamento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
							break;
						case "RICH": // richiamare
							$parameters['TESTOSCADENZA'] = "Richiamare cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
							break;
						case "RCC": // richiedi copia contratto
							$parameters['TESTOSCADENZA'] = "Verificare archiviazione copia documenti contrattuali per pratica n. ".$pratica["CodContratto"];
							break;
						case "VPG": // verifica pagamento
							$parameters['TESTOSCADENZA'] = "Verifica pagamento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
							break;
						/*case "WF_PROP_DBT": // proposta DBT
							$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
							$addEsito = "Passaggio di stato effettuato. ";
							break;*/
						default:
							$parameters['TESTOSCADENZA'] = "Verifica cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
							break;
					}
					//trace ($parameters['TESTOSCADENZA']);
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
									
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError()); 
				
				writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota);		
				break;

			case "Esito":	// Azione con esito
				switch ($azione["CodAzione"])
				{
					case "TEG": // telefonata a garante
						$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]
						." dopo Telefonata a garante/coobbligato per pratica n. ".$pratica["CodContratto"];
						break;
					case "TEC": // telefonata a cliente
						$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]
						." dopo Telefonata per pratica n. ".$pratica["CodContratto"];
						break;
					default:
						$parameters['TESTOSCADENZA'] = "Verifica cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						break;
				}
				
				deleteScadenze($idContratto);
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"])." ".$_REQUEST["ora"];
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				$esito = $_REQUEST["IdTipoEsito"]; // id della classe assegnata
				writeHistory($idAzione,$descrEvento,$idContratto,$nota,$esito);
				break;
			
			case "EsitoNegativo":	// Azione con esito negativo
				switch ($azione["CodAzione"])
				{
					case "ENEG": // Contatto con il cliente
						$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]
						." per pratica n. ".$pratica["CodContratto"];
						break;
					default:
						$parameters['TESTOSCADENZA'] = "Verifica cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						break;
				}
				
				deleteScadenze($idContratto);
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"])." ".$_REQUEST["ora"];
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				$esito = $_REQUEST["IdTipoEsito"]; // id della classe assegnata
				writeHistory($idAzione,$descrEvento,$idContratto,$nota,$esito);
				break;
				
			case "Incasso":				
//				if (isset($_FILES['docPath']))
				$IdAllegato='NULL';
				if ($_FILES['docPath']['name'] != "")
				{
					allegaDocumento($pratica,
							getScalar("SELECT IdTipoAllegato FROM tipoallegato WHERE CodTipoAllegato='GEN'"),
							"Ricevuta di pagamento",'N');

					$IdAllegato=getInsertId();		
				}
				$descrEvento.=" - importo: ".$_REQUEST["importo"];
				writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				$esitoAzione = insertIncasso($idContratto,$pratica["IdCliente"],$IdAllegato);
				break;
			case "RegistraPianiRientro":				
				
				//controllo che non esista gi� un piano di rientro
				
				$resp = getScalar("select count(*) from pianorientro where IdContratto = ".$idContratto);
				if($resp > 0)
					Throw new Exception("Esiste gi� un piano di rientro");
				else
				{	
					if (insertPianoRientro($idContratto,$_REQUEST["ImportoPag"],$_REQUEST["NumeroRate"],$_REQUEST["DataInizioPagamento"],$_REQUEST["NotePianoRientro"]))
					{
						$descrEvento.="Inserito piano di rientro  di importo ".$_REQUEST["ImportoPag"]." distribuito in n.".$_REQUEST["NumeroRate"]."rate a partire dal ".$_REQUEST["DataInizioPagamento"];
						writeHistory($idAzione,$descrEvento,$idContratto,$_REQUEST["NotePianoRientro"]);
						$esitoAzione = "Registrazione effettuata";
					}
					else
						Throw new Exception(getLastError()); 
				}
				break;
			case "RegistraRateazione":				
					
					$arrayPianoRateazione = null;
					
					$arrayPianoRateazione["DecorrenzaRate"] = $_REQUEST["DecorrenzaRate"];
					$arrayPianoRateazione["DurataAnni"] 	= $_REQUEST["DurataAnni"];
 					//$arrayPianoRateazione["PrimoImporto"] = $_REQUEST["ImportoDebito"];
 					$arrayPianoRateazione["Nota"] 			= $_REQUEST["Nota"];
 					$arrayPianoRateazione["Periodicita"] 	= $_REQUEST["Periodicita"];
 					$arrayPianoRateazione["RataCrescente"]  = $_REQUEST["RataCrescente"];
 					$arrayPianoRateazione["Spese"] 			= $_REQUEST["Spese"];
 					$arrayPianoRateazione["Tasso"] 			= $_REQUEST["Tasso"];
 					$arrayPianoRateazione["IdPianoRientro"] = $_REQUEST["idPianoRientro"];
 					$arrayPianoRateazione["IdContratto"] 	= $idContratto;
 					$arrayPianoRateazione["ImportoRata"] 	= $_REQUEST["importoRata"];
 					$arrayPianoRateazione["NumRate"] 		= $_REQUEST["numRate"];
 					$arrayPianoRateazione["Rate"] 			= $_REQUEST["arrayRate"];
 					
 					if($_REQUEST["idPianoRientro"]<=0)
 					{
 						$arrayPianoRateazione["IdStatoPiano"] = 1;
 					}
 				
					if (savePianoRateazione($arrayPianoRateazione)!=false)
					{
						$descrEvento.="Inserito piano di rateazione  di importo ".$_REQUEST["importoRata"]." distribuito in n.".$_REQUEST["numRate"]." rate a partire dal ".$_REQUEST["DecorrenzaRate"];
						writeHistory($idAzione,$descrEvento,$idContratto,$_REQUEST["NotePianoRientro"]);
						$esitoAzione = "Registrazione effettuata";
					}
					else
						Throw new Exception(getLastError()); 
				break;
				
			// Saldo e stralcio	
			case "ImportoResiduo":
				// data di verifica	
				$parameters['TESTOSCADENZA'] = "Verifica saldo e stralcio cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				//trace ($parameters['TESTOSCADENZA']);
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["DataVerifica"],true);		
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				// data di pagamento
				$parameters['TESTOSCADENZA'] = "Verifica pagamento saldo e stralcio cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				//trace ($parameters['TESTOSCADENZA']);
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["DataInizioPagamento"]);		
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				// azione
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError()); 
				// history
				$descrEvento.="Richiesta di saldo e stralcio. Importo dovuto ".$_REQUEST["ImportoDebito"]." Importo disposto a pagare ".$_REQUEST["ImportoDispostoAPagare"]." il ".$_REQUEST["DataInizioPagamento"];
				writeHistory($idAzione,$descrEvento,$idContratto,$_REQUEST["NoteSaldoStralcio"]);
				$esitoAzione = "Registrazione effettuata";
				break;
			case "Inoltro": // Inoltro semplice (richiesta a mandante e proposta piano di rientro, ad es.)
				//in caso di Richiesta mandante scrivi nota su contratto
				//$dataEndConv = date("Y-m-d H:i:s",dateFromString($_REQUEST["data"]));
				if($azione["CodAzione"]=='RIC'){
					$conNota='';
					if($nota != '')
					{
						$conNota =  " con nota: \"$nota\"";
					}
					$testo="$descrEvento per ".$_REQUEST['TitoloTipoRichiesta']."$conNota";
					$valList = "";
					$colList = "";
					addInsClause($colList,$valList,"IdUtente",$IdUser,"N");
					addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
					addInsClause($colList,$valList,"TipoNota",'N',"S");
					addInsClause($colList,$valList,"TestoNota",$testo,"S");
					addInsClause($colList,$valList,"DataCreazione","NOW()","G");
					addInsClause($colList,$valList,"DataScadenza",$_REQUEST["data"],"D");
					addInsClause($colList,$valList,"DataIni","CURDATE()","G");
					addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
					addInsClause($colList,$valList,"LastUser",$userid,"S");
					addInsClause($colList,$valList,"FlagRiservato",'N',"S");
					
					$master=$context["master"];
					//trace("master ".$master);
					if($master!=''){
						$sqlIdMaster="SELECT IdUtente FROM utente where userid='$master'";
						$IdM = getScalar($sqlIdMaster);
						addInsClause($colList,$valList,"IdSuper",$IdM ,"N");
					}
					
					$sqlNota="INSERT INTO nota ($colList)  VALUES($valList)";
					/*if(!execute($sqlNota)){
						trace("nota non inserita");
					}*/
				}
				
				break;
				
			case "InoltroWF": // Tutte le azioni di inoltro per approvazione (in un workflow)
				
				//$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				$parameters["TESTOSCADENZA"] = "Verifica $descrEvento pratica $pratica";
				$parameters["DATASCADENZA"] = getDefaultDate($idAzione);
				// Aggiunge ai parametri tutti i campi del contratto
				$parameters += $pratica;

				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError()); 
					
				//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				//$nomi  = join(",",$nomi);
				$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota);		
				break;

			case "InoltroWFCES": // Inoltro Workflow specifico per Write off
				$inoltroMultiplo=$_REQUEST["inoltroMultiplo"];
		        if($inoltroMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  } else{
					  $data="Non specificata";
					  $esitoAzione = "$addEsito";
					}
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  
		          //$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  $parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  // Aggiunge ai parametri tutti i campi del contratto
				  $parameters += $pratica;
		
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError()); 
							
				  //$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				  //$nomi  = join(",",$nomi);
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");

				  writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota);
		        } else {
					//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if($dataScad!=''){
					  $parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					  $addEsito = "Operazione effettuata.";
					  $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					  GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					  $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					} else{
					    $data="Non specificata";
					    $esitoAzione = "$addEsito";
					  }
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError());
					
					//$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					// Aggiunge ai parametri tutti i campi del contratto
					$parameters += $pratica;
		
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError()); 
							
					//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
					//$nomi  = join(",",$nomi);
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }			
		        
				break;	
				
			case "InoltroWFDBT": // Inoltro Workflow specifico per DBT
		        $inoltroMultiplo=$_REQUEST["inoltroMultiplo"];
		        if($inoltroMultiplo=='true'){
				  //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  }else{
					 $data="Non specificata";
					 $esitoAzione = "$addEsito";
				   }
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  
				  //$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  $parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  // Aggiunge ai parametri tutti i campi del contratto
				  $parameters += $pratica;
	
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError()); 
						
				  //$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				  //$nomi  = join(",",$nomi);
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				  /*$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	              $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";*/
				  writeHistory($idAzione,"$descrEvento per $proc. $esitoAzione ",$idContratto,$nota);		
				} else {
					//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
					$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
					
					//forzatura a riaffido
					$idOldRegolaProvvigione = $_REQUEST["idOldRegolaProvvigione"]; 
					$idRegolaProvvigione = $_REQUEST["IdRegolaProvvigione"];
					if($idOldRegolaProvvigione!=$idRegolaProvvigione && $idRegolaProvvigione!='-1' && $idRegolaProvvigione!='-2') {
					  $nome  = forzaAffidoAgenzia($idContratto,$idRegolaProvvigione,"",$idAzione,false,true);
					  if ($nome===FALSE)
						Throw new Exception(getLastError()); 
					  $esitoAzione = $esitoAzione."<br/>Registrata forzatura del prossimo affidamento automatico all'agenzia ".$nome;
	                  //writeHistory($idAzione,$esitoAzione,$idContratto,$nota);	
					}
												
	                $flagIrr = $_REQUEST["chkFlag"]?'Y':'N';
	                $flagIpo = $_REQUEST["chkFlagIpoteca"]?'Y':'N';
	                $flagConc = $_REQUEST["chkFlagConcorsuale"]?'Y':'N';
					if($_REQUEST["dataVendita"]!=''){
					  $dataVend = "'".ISODate($_REQUEST["dataVendita"])."'";
					  if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"],$dataVend)) 
					  Throw new Exception(getLastError());
					} else {
						if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"])) 
					    Throw new Exception(getLastError());
					}
					//writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota);  
					
					//$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					// Aggiunge ai parametri tutti i campi del contratto
					$parameters += $pratica;
	
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError()); 
						
					//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
					//$nomi  = join(",",$nomi);
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,"$descrEvento per $proc. $esitoAzione ",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);		
				} 
				
				break;
				
			case "InoltroWFSS": // Inoltro Workflow specifico per Saldo e stralcio
				$inoltroMultiplo=$_REQUEST["inoltroMultiplo"];
		        if($inoltroMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
			  	  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  }else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
				  
				  //$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  $parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  // Aggiunge ai parametri tutti i campi del contratto
				  $parameters += $pratica;
	
				  if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError()); 
						
				  //$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				  //$nomi  = join(",",$nomi);
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					
				  writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota);		
		        } else {
		        	//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if ($dataScad!=''){
						$parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
						$addEsito = "Operazione effettuata.";
						$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
						GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
						$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					}else{
						$data="Non specificata";
						$esitoAzione = "$addEsito";
					}
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());

					$dataSS = ISODate($_REQUEST["dataPagamento"]);
	                $impSS  = $_REQUEST["importoProposto"];
	                $htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					if (!udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)) 
					  Throw new Exception(getLastError());
					//$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					// Aggiunge ai parametri tutti i campi del contratto
					$parameters += $pratica;
	
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError()); 
						
					//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
					//$nomi  = join(",",$nomi);
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);		
		          }
				
				break;
			case "InoltroWFSSDIL": // Inoltro Workflow specifico per Saldo e stralcio dilazionato
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if($dataScad!=''){
				  $parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				  $addEsito = "Operazione effettuata.";
				  $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
				  GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				  $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				} else{
				    $data="Non specificata";
				    $esitoAzione = "$addEsito";
				  }
				if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
				  Throw new Exception(getLastError());
									
				//$dataSS = ISODate($_REQUEST["dataPagamento"]);
	            $impSS  = $_REQUEST["importoProposto"];
	            //Inserimento saldo e stralcio
	            if(!udpdateCampiPropostaSS($idContratto,NULL,$impSS)) 
				  Throw new Exception(getLastError());
				$dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
				$dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
				//Inserimento piano di rientro
				if(!updateCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
				  Throw new Exception(getLastError());    
	            //$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				// Aggiunge ai parametri tutti i campi del contratto
				$parameters += $pratica;
	
				if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
				  Throw new Exception(getLastError()); 
						
				//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				//$nomi  = join(",",$nomi);
				$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
				writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);		
		        
				break;					
            
			case "InoltroWFWO": // Inoltro Workflow specifico per Write off
				$inoltroMultiplo=$_REQUEST["inoltroMultiplo"];
		        if($inoltroMultiplo=='true'){
		          //gestione scadenza e proposta
				  deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		          $data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				  $dataScad = ISODate($_REQUEST["dataVerifica"],true);
				  if($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				  } else{
					  $data="Non specificata";
					  $esitoAzione = "$addEsito";
					}
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
										
				  //$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  $parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
				  // Aggiunge ai parametri tutti i campi del contratto
				  $parameters += $pratica;
		
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError()); 
							
				  //$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
				  //$nomi  = join(",",$nomi);
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");

				  writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota);
		        } else {
					// Scrive nella tabella writeoff
					if (!saveWriteoff($idContratto))
						Throw new Exception(getLastError());
		        	//gestione scadenza e proposta
				    deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
		        	$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
					$dataScad = ISODate($_REQUEST["dataVerifica"],true);
					if($dataScad!=''){
					  $parameters['TESTOSCADENZA'] = "Verifica ".$descrEvento." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					  $addEsito = "Operazione effettuata.";
					  $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					  GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					  $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					} else{
					    $data="Non specificata";
					    $esitoAzione = "$addEsito";
					  }
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError());
										
					//$apprid = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					$parameters["*APPROVER"] = json_decode(stripslashes($_REQUEST['idAttuatori'])); // id degli approvatori assegnati
					// Aggiunge ai parametri tutti i campi del contratto
					$parameters += $pratica;
		
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError()); 
							
					//$nomi  = fetchValuesArray("SELECT NomeUtente FROM utente WHERE IdUtente in (".join(",",$apprid).")");
					//$nomi  = join(",",$nomi);
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					writeHistory($idAzione,"$descrEvento per $proc",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }			
		        
				break;			
				
			case "InviataMail": // registra invio mail
				$email = $_REQUEST["email"]; // email destinatario (opzionale)
				$data = italianDate($_REQUEST["data"]); // data di verifica
				$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]." dopo invio e-mail per pratica n. ".$pratica["CodContratto"];
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario

//				if (isset($_FILES['docPath']))
				if ($_FILES['docPath']['name'] != "")
				{
					allegaDocumento($pratica,
							getScalar("SELECT IdTipoAllegato FROM tipoallegato WHERE CodTipoAllegato='EMAIL'"),
							"Email inviata il ".italianDate(),'N');
				}
				
				$esitoAzione = "Registrazione effettuata";
				writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
								
				break;
				
			case "InviatoSMS": // registra invio SMS
				$cell = $_REQUEST["cellulare"]; // num.tel. destinatario (opzionale)
				$data = italianDate($_REQUEST["data"]); // data di verifica
				$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]." dopo invio SMS per pratica n. ".$pratica["CodContratto"];
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario

				$esitoAzione = "Registrazione effettuata";
				writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
								
				break;

			case "InvioEmail":
				$invio=$_REQUEST["InvioMultiplo"];
				if ($invio)
				{ 
					if (!(isset($strFile)) && !(isset($modelTex))){
						$strFile = getScalar("SELECT FileName FROM modello WHERE IdModello=".$_REQUEST['IdModello']);
						$modelText = json_decode(file_get_contents(TEMPLATE_PATH.'/'.$strFile));
					}
					$praticaTmp = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=$idContratto");	
					if (!$praticaTmp){ 
					   $praticaTmp = $pratica;
					}
					
					$oggetto = replaceVariables($modelText->subject,$praticaTmp);
					$nota = replaceVariables($modelText->body,$praticaTmp);
					
					$indirizziEmail = getScalar("SELECT Email FROM v_email WHERE IdCliente=".$pratica["IdCliente"]);
		    		$indirizziEmail=explode(';', $indirizziEmail);
					$arrayIndirizzi=array();
					foreach ($indirizziEmail as $indirizzo)
					{
						if(filter_var(trim($indirizzo), FILTER_VALIDATE_EMAIL))
						{ 
							$arrayIndirizzi[]=trim($indirizzo);
						}
					}
					$email = implode(';',$arrayIndirizzi);
	
//					trace("Invio Multiplo ".$idContratto." - ".$email." - ".$oggetto." - ".$nota);

					if (!sendMail("",$email,$oggetto,$nota,"")){
						Throw new Exception(getLastError());
					}	
				}else{
					$email = $_REQUEST["email"];
					$allegato = $_FILES["docPath"];
					
					if (!($allegato['name']>''))
						$allegato = null;
					trace("Chiama sendmail per destinatario {$_REQUEST["email"]} - Oggetto: {$_REQUEST["oggetto"]} ".
						($allegato?' con allegato ':' senza allegato').' '.print_r($_FILES,true),false);
					if (!sendMail("",$_REQUEST["email"],$_REQUEST["oggetto"],$_REQUEST["nota"],$allegato)){
						Throw new Exception(getLastError());
					}	
				}		 
				$data = italianDate($_REQUEST["data"]); // data di verifica
				$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]." dopo invio email per pratica n. ".$pratica["CodContratto"];
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);
				deleteScadenze($idContratto);
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				$esitoAzione = "Inviata email a ".$email; //$_REQUEST["email"];
				writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
				break;
				
			case "InvioLettera":
				break;

			case "InvioSMS":
				$invio=$_REQUEST["InvioMultiplo"];
				if ($invio)
				{ 
					if (!(isset($strFileSMS)) && !(isset($modelTex))){
						$strFileSMS = getScalar("SELECT FileName FROM modello WHERE IdModello=".$_REQUEST['IdModello']);
						$modelText = json_decode(file_get_contents(TEMPLATE_PATH.'/'.$strFileSMS));
					}
					$praticaTmp = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=$idContratto");	
					if (!$praticaTmp){ 
					   $praticaTmp = $pratica;
					}
					$nota = replaceVariables($modelText->testoSMS,$praticaTmp);
					
					$cellulare= getScalar("SELECT Cellulare FROM v_cellulare WHERE IdCliente=".$pratica["IdCliente"]);
					
//					trace("Invio Multiplo ".$idContratto." - ".$cellulare." - ".$nota);
					
					if (!inviaSMS($cellulare,$nota,$errmsg)){
						Throw new Exception($errmsg);
					}	
				}else{
					$cellulare=$_REQUEST["Cellulare"];
					//trace("cel $cellulare");
					if (!inviaSMS($_REQUEST["Cellulare"],$_REQUEST["nota"],$errmsg))
						Throw new Exception($errmsg);
				}		 
				$data = italianDate($_REQUEST["data"]); // data di verifica
				$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]." dopo invio SMS per pratica n. ".$pratica["CodContratto"];
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);
				deleteScadenze($idContratto);
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario

				$esitoAzione = "Inviato SMS al numero ".$cellulare;//$_REQUEST["Cellulare"];
				writeHistory($idAzione,$esitoAzione,$idContratto,$nota);		
				break;
				
			case "PropostaCES":	//Proposta di Cessione

				//gestione scadenza e proposta
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta di cessione ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());

				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
                writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);  
				break;		
				
			case "PropostaDBT":	//Proposta di passaggio in CM o DBT
				
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());
								
				//forzatura a riaffido
				annullaForzaturePrecedenti($idContratto); // annulla le forzature eventuali messe in precedenza
				$idRegolaProvvigione = $_REQUEST["IdRegolaProvvigione"];
				if ($idRegolaProvvigione!='-1' && $idRegolaProvvigione!='-2') 
				{
				  $nome  = forzaAffidoAgenzia($idContratto,$idRegolaProvvigione,"",$idAzione,false,true);
				  if ($nome===FALSE)
					Throw new Exception(getLastError()); 
				  $esitoAzione = $esitoAzione."<br/>Registrata forzatura del prossimo affidamento automatico all'agenzia ".$nome;
                  //writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
				}
				$flagIrr = $_REQUEST["chkFlag"]?'Y':'N';
                $flagIpo = $_REQUEST["chkFlagIpoteca"]?'Y':'N';
                $flagConc = $_REQUEST["chkFlagConcorsuale"]?'Y':'N';
                $htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
				if($_REQUEST["dataVendita"]!=''){
				  $dataVend = "'".ISODate($_REQUEST["dataVendita"])."'";
				  if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"],$dataVend)) 
				     Throw new Exception(getLastError());
				} else {
					if (!udpdateCampiPropostaDBT($flagIrr,$flagIpo,$flagConc,$idContratto,$pratica["IdCliente"])) 
				    Throw new Exception(getLastError());
				}
                writeHistory($idAzione,"$descrEvento (prossima data: $data). $esitoAzione ",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);  
				break;
				
			case "PropostaSS":	//Proposta di Saldo e stralcio

				annullaForzaturePrecedenti($idContratto); // annulla le forzature eventuali messe in precedenza
				
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta passaggio in DBT".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());
								
				$dataSS = ISODate($_REQUEST["dataPagamento"]);
				$impSS  = $_REQUEST["importoProposto"];
                $htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
				if (!udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)) 
				  Throw new Exception(getLastError());
                writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);  
				break;				

			case "PropostaSSDIL":	//Proposta di Saldo e stralcio dilazionato

				annullaForzaturePrecedenti($idContratto); // annulla le forzature eventuali messe in precedenza
				
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta Saldo e stralcio dilazionato".$_REQUEST["isCMDBT"]." ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());

				$impSS  = $_REQUEST["importoProposto"];
				//Inserimento saldo e stralcio
				if(!udpdateCampiPropostaSS($idContratto,NULL,$impSS)) 
				  Throw new Exception(getLastError());
				
				$dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
			   	$dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
			   	//Inserimento piano di rientro
			   	if(!insertCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
				  Throw new Exception(getLastError());
			   	
				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
                writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);  
				break;

				case "StampaWO":	// Stampa Write off, nel cvaso di chiamata dal pulsante interno al form
					// Prima salva nella tabella writeoff
					if (!saveWriteoff($idContratto))
						Throw new Exception(getLastError());
					// la stampa vera e propria la lancia la parte js
				break;
				
				case "ComSaldoStralcio":	// Stampa Saldo e Stralcio, nel caso di chiamata dal pulsante interno al form
					// Prima salva dati
			   	 	$dataSS = ISODate($_REQUEST["dataPagamento"]);
			   	    $impSS  = $_REQUEST["importoProposto"];
					if (!udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)) 
						  Throw new Exception(getLastError());
 					// la stampa vera e propria la lancia la parte js
				break;

				case "ComSaldoStralcioDil":	// Stampa Saldo e Stralcio, nel caso di chiamata dal pulsante interno al form
					 // Prima salva dati
		            $impSS  = $_REQUEST["importoProposto"];
		            //Inserimento saldo e stralcio
		            if(!udpdateCampiPropostaSS($idContratto,NULL,$impSS)) 
						Throw new Exception(getLastError());
					$dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
					$dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
					//Inserimento o aggiornamento piano di rientro
					if (rowExistsInTable("pianorientro","IdContratto = $IdContratto")) {
					   	if (!updateCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
							 Throw new Exception(getLastError());
					} else {
					   	if (!insertCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
							 Throw new Exception(getLastError());
					}
 					// la stampa vera e propria la lancia la parte js
				break;
					
				case "PropostaWO":	//Proposta di Write off

				annullaForzaturePrecedenti($idContratto); // annulla le forzature eventuali messe in precedenza
				// Scrive nella tabella writeoff
				if (!saveWriteoff($idContratto))
					Throw new Exception(getLastError());
				
				//gestione scadenza e proposta
				deleteScadenze($idContratto); // se richiesto, elimina le scadenze future preesistenti
				$data = italianDate($_REQUEST["dataVerifica"]); // data di scadenza
				$dataScad = ISODate($_REQUEST["dataVerifica"],true);
				if ($dataScad!=''){
					$parameters['TESTOSCADENZA'] = "Verifica proposta write off ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
					$addEsito = "Operazione effettuata.";
					$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataVerifica"],true);		
					GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
					$esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				}else{
					$data="Non specificata";
					$esitoAzione = "$addEsito";
				}
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						Throw new Exception(getLastError());

				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
                writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);  
				break;	
				
			case "ProrogaAg":	// proroga la data fine affidamento ad un'agenzia
				$data = $_REQUEST["data"]; // data di verifica
				$nome  = prorogaAgenzia($idContratto,$_REQUEST["data"]);
				if ($nome===FALSE)
					Throw new Exception(getLastError()); 
				$esitoAzione = "Effettuata proroga affidamento all'agenzia ".$nome." fino al ".italianDate($data);
				writeHistory($idAzione,"Proroga affidamento all'agenzia $nome fino al ".italianDate($data),$idContratto,$nota);	
				break;
				
			case "RevocaAg":	// revoca affidamento
				$nome  = revocaAgenzia($idContratto,false);
				if ($nome===FALSE)
					Throw new Exception(getLastError()); 
				if ($nome===NULL) // non era assegnata: non dovrebbe essere possibile eseguire questa azione
					Throw new Exception("Revoca non effettuata perch&eacute; la pratica non &egrave; in affido"); 
				$esitoAzione = "Effettuata revoca affidamento all'agenzia ".$nome;
				writeHistory($idAzione,"Revoca affidamento all'agenzia $nome",$idContratto,$nota);		
				break;

			case "Riaffido":	// forza prossimo affidamento ad una specifica agenzia+provvigione
				$idRegolaProvvigione = $_REQUEST["IdRegolaProvvigione"]; 
				$nome  = forzaAffidoAgenzia($idContratto,$idRegolaProvvigione,$nota,$idAzione);
				if ($nome===FALSE)
					Throw new Exception(getLastError()); 
					
				break;
			
			case "RichiestaPR":
		        $idallegati = $_REQUEST['idallegati'];
		        //controllo se sto allegando o effettuando la RichiestaPR 
			    if ($idallegati=='')  // RichiestaPR
			    {
			      //controllo che non esista gi� un piano di rientro
				  $resp = getScalar("select count(*) from pianorientro where IdContratto = ".$idContratto);
				  if($resp > 0){
				    $idAzioneSpeciale = $_REQUEST['idAzioneSpeciale'];
				    $storiaRecuperoAllegati = $_REQUEST['storiaRecuperoAllegati'];
			 	 	if (!deleteAzioneSpeciale($idAzioneSpeciale,$storiaRecuperoAllegati))
			   	 		Throw new Exception(getLastError());
					Throw new Exception("Esiste gi� un piano di rientro");
				  } else
				  {	
					 if (!registraAzioneSpeciale())
//			 	 	 if (!confermaAzioneSpeciale($idAzioneSpeciale,$idAzione,$idContratto,$nota))
			   	 		Throw new Exception(getLastError());
			   	 	 $dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
			   	 	 $dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
			   	 	 if (insertCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
					 {
						$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                        $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					 	$descrEvento="Inserito piano di rientro  di importo ".$_REQUEST['importoPag']." distribuito in n&deg; ".$_REQUEST['numeroRate']." rate a partire dal ".$_REQUEST['decorrenzaRata'];
						writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale,$htmlAzione,$valoriHtmlAzione);	
						$esitoAzione = "Registrazione effettuata";
					 }
					 else
						Throw new Exception(getLastError()); 
				  }
			    } 
			    else //allegato 
			    {  
			       $idAzioneSpeciale = azioneSpecialeAllegato($idAzione,$idContratto,$nota,$idallegati);
			       if ($idAzioneSpeciale == false) 
				      	Throw new Exception(getLastError());
				   /*else
					   writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale);*/	
			   	} 
			    break;	
			    
			case "RichiestaRIN": // Richiesta rinegoziazione
				$stato = $_REQUEST["IdStatoRinegoziazione"];

				if (!execute("UPDATE contratto SET IdStatoRinegoziazione=$stato WHERE IdContratto=$idContratto"))
					Throw new Exception(getLastError());
				
				// provoca altre possibili conseguenze (forzatura a Nicol)
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				
				$esito = getScalar("SELECT TitoloStatoRinegoziazione FROM statorinegoziazione WHERE IdStatoRinegoziazione=$stato");
				writeHistory($idAzione,$esito. " - cliente ".$pratica["NomeCliente"]." - pratica n. ".$pratica["CodContratto"],$idContratto,$nota);	
				break;    
			
			case "RichiestaSS":
		        $idallegati = $_REQUEST['idallegati'];
		        //controllo se sto allegando o effettuando la RichiestaSS 
			    if ($idallegati=='')  // RichiestaSS
			    {
			 	 	$idAzioneSpeciale = $_REQUEST['idAzioneSpeciale'];
			    	if (!registraAzioneSpeciale($idAzioneSpeciale))
//			    	if (!confermaAzioneSpeciale($idAzioneSpeciale,$idAzione,$idContratto,$nota))
			    		Throw new Exception(getLastError());
			   	 	$dataSS = ISODate($_REQUEST["dataPagamento"]);
			   	    $impSS  = $_REQUEST["importoProposto"];
				    $htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                    $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
                    if(!udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)) 
				     Throw new Exception(getLastError());
			        else
					   writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale,$htmlAzione,$valoriHtmlAzione);	
			    } 
			    else //allegato 
			    {  
			       $idAzioneSpeciale = azioneSpecialeAllegato($idAzione,$idContratto,$nota,$idallegati);
			       if ($idAzioneSpeciale == false) 
				      	Throw new Exception(getLastError());
				   /*else
					   writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale);*/	
			   	} 
			    break;
			    
			 case "RichiestaSSD":
		        $idallegati = $_REQUEST['idallegati'];
		        //controllo se sto allegando o effettuando la RichiestaSS 
			    if ($idallegati=='')  // RichiestaSS
			    {
			 	 	$idAzioneSpeciale = $_REQUEST['idAzioneSpeciale'];
			    	//if (!confermaAzioneSpeciale($idAzioneSpeciale,$idAzione,$idContratto,$nota))
			    	if (!registraAzioneSpeciale($idAzioneSpeciale,$idAzione,$idContratto,$nota))
			    		Throw new Exception(getLastError());
			   	 	
					$impSS  = $_REQUEST["importoProposto"];
					//Inserimento saldo e stralcio
					if(!udpdateCampiPropostaSS($idContratto,NULL,$impSS)) 
					  Throw new Exception(getLastError());
					
					$dataPagamentoPrimImp = ISODate($_REQUEST["dataPagPrimoImporto"]);	
				   	$dataDecorrenzaRata = ISODate($_REQUEST["decorrenzaRata"]);
				   	//Inserimento piano di rientro
				   	if(!insertCampiPianoRientro($idContratto,$_REQUEST["primoImporto"],$dataPagamentoPrimImp,$_REQUEST["numeroRate"],$dataDecorrenzaRata,$_REQUEST["importoRata"]))
					  Throw new Exception(getLastError());
				   	$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
	                writeHistory($idAzione,"$descrEvento (prossima data: $data)",$idContratto,$nota,"NULL",$idAzioneSpeciale,$htmlAzione,$valoriHtmlAzione);     
			    } 
			    else //allegato 
			    {  
			       $idAzioneSpeciale = azioneSpecialeAllegato($idAzione,$idContratto,$nota,$idallegati);
			       if ($idAzioneSpeciale == false) 
				      	Throw new Exception(getLastError());
				   /*else
					   writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale);*/	
			   	} 
			    break;   

			 case "RichiestaWO": //Richiesta Write off
		        
				// Scrive nella tabella writeoff
				if (!saveWriteoff($idContratto))
					Throw new Exception(getLastError());
			 	
				if ($azioneSpeciale == "Y") // controlla, perch� alcune azioni possono essere cambiate in "normali" dopo
						                    // la loro progettazione
				{
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
                    $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";
					// data di verifica	
				    $parameters['TESTOSCADENZA'] = "Verifica $descrEvento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				    $addEsito = "Richiesta di $descrEvento effettuata.";
				    $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataScadenza"],true);
				    GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				    $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
					$dataScadenza = ISODate($_REQUEST["dataScadenza"],true);
				    // le azioni speciali vengono registrate nella relativa tabella	
					$idAzioneSpeciale = azioneSpeciale($idAzione,$idContratto,$nota,$dataScadenza);
					if($idAzioneSpeciale == false) 
				  		Throw new Exception(getLastError());
				  	else
						writeHistory($idAzione,$esitoAzione,$idContratto,$nota,"NULL",$idAzioneSpeciale,$htmlAzione,$valoriHtmlAzione);
				}
				else
				{
					// data di verifica	
				    $parameters['TESTOSCADENZA'] = "Verifica $descrEvento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
				    $addEsito = "Richiesta di $descrEvento effettuata.";
				    $parameters['DATASCADENZA'] = ISODate($_REQUEST["dataScadenza"],true);
				    GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
				    $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
				    // history
				    writeHistory($idAzione,$esitoAzione,$idContratto,$nota);
				}
			    
			    break;   
			
			case "Rifiuta": // respinge una richiesta in workflow
				//ripristina lo stato e manda le mail ai precedenti manipolatori del contratto.
				//l'azione � considerata equivalente ad una revoca della proposta di workflow.
				//ripristinaStato($idContratto); fatto tramite azioni automatiche
				$codAzione = $azione['CodAzione'];
				emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				$parameters["*DESTINATARIRIF"]=$manipolatori;

				$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				
				if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
							Throw new Exception(getLastError());
							
				writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota);	
				break;
				
			case "RifiutaCES": // respinge una richiesta di Write off
				
				$rifiutoMultiplo=$_REQUEST["rifiutoMultiplo"];
		        if($rifiutoMultiplo=='true'){
		          $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
		
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
						
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					 Throw new Exception(getLastError());
                  writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota);	 
				} else {
			        $codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
		
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
						
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError());
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";			
					writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }		
		          
				break;		
				
			case "RifiutaDBT": // respinge una richiesta in workflow DBT
				$rifiutoMultiplo=$_REQUEST["rifiutoMultiplo"];
		        if($rifiutoMultiplo=='true'){
		          $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
	              
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				  	
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota);
				  		
		        } else {
		        	$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
	
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
								Throw new Exception(getLastError());
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";			
					writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);	
		          }
                break;

			case "RifiutaSS": // respinge una richiesta di saldo e stralcio
				if(!udpdateCampiPropostaSS($idContratto,NULL,NULL)) 
				     Throw new Exception(getLastError());
				
				$rifiutoMultiplo=$_REQUEST["rifiutoMultiplo"];
		        if($rifiutoMultiplo=='true'){
		          $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
	              
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
				  	
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					Throw new Exception(getLastError());
				  writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota);	
		        } else {
		        	$codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
	
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
								Throw new Exception(getLastError());
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	                $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";			
					writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);	
		          }
				break;	

			case "RifiutaSSDIL": // respinge una richiesta di Saldo e stralcio dilazionato
				if(!udpdateCampiPropostaSS($idContratto,NULL,NULL)) 
				     Throw new Exception(getLastError());
				
		        $codAzione = $azione['CodAzione'];
				emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				$parameters["*DESTINATARIRIF"]=$manipolatori;
	
				$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
					
				if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
				  Throw new Exception(getLastError());
				$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
	            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";			
				writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);	
		          
				break;	

			case "RifiutaWO": // respinge una richiesta di Write off
				
				// Scrive nella tabella writeoff
				if (!saveWriteoff($idContratto))
					Throw new Exception(getLastError());
				
				$rifiutoMultiplo=$_REQUEST["rifiutoMultiplo"];
		        if($rifiutoMultiplo=='true'){
		          $codAzione = $azione['CodAzione'];
				  emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
				  $parameters["*DESTINATARIRIF"]=$manipolatori;
		
				  $proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
						
				  if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					 Throw new Exception(getLastError());
                  writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota);	 
				} else {
			        $codAzione = $azione['CodAzione'];
					emailListaUtentiContratti($idContratto,$codAzione,$manipolatori);
					$parameters["*DESTINATARIRIF"]=$manipolatori;
		
					$proc = getScalar("SELECT TitoloProcedura FROM procedura p JOIN azioneprocedura pa ON pa.IdProcedura=p.IdProcedura AND IdAzione=$idAzione");
						
					if(!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
					  Throw new Exception(getLastError());
					$htmlAzione = "'".addslashes($_REQUEST["txtHTML"])."'";
		            $valoriHtmlAzione = "'".addslashes($_REQUEST["valuesHtml"])."'";			
					writeHistory($idAzione,"Proposta per $proc respinta.",$idContratto,$nota,"NULL","NULL",$htmlAzione,$valoriHtmlAzione);
		          }		
		          
				break;		
				
			case 'Speciale':
				if ($azioneSpeciale == "Y") // controlla, perch� alcune azioni possono essere cambiate in "normali" dopo
				                            // la loro progettazione
				{
			   	 	if (!registraAzioneSpeciale())
			 	 		Throw new Exception(getLastError());
				}
				else
				{
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						 Throw new Exception(getLastError());
					writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				}
				break;
				
			case 'SpecialeAllegato';
		   	 	if (!registraAzioneSpeciale())
		 	 		Throw new Exception(getLastError());
			   break;	
			   
			case 'SpecialeFlag':
				$chkFlag = $_REQUEST["chkFlag"]?'Y':'N';
				switch ($azione["CodAzione"])
				{
					case "REP": // reperibilit�
						if (!execute("UPDATE cliente SET FlagIrreperibile='$chkFlag' WHERE IdCliente = ".$pratica["IdCliente"]))
				      		Throw new Exception(getLastError());
						if ($chkFlag=='Y')
							$descrEvento = "Intestatario del contratto marcato come 'irreperibile'";
						else
							$descrEvento = "Intestatario del contratto marcato come 'reperibile'";
						break;
					case "IPO": // ipoteca
						if (!execute("UPDATE contratto SET FlagIpoteca='$chkFlag' WHERE IdContratto=$idContratto"))
				      		Throw new Exception(getLastError());
						if ($chkFlag=='Y')
							$descrEvento = "Segnalata presenza ipoteca sul bene finanziato";
						else
							$descrEvento = "Segnalata assenza ipoteca sul bene finanziato";
						break;
					case "IPT": // ipoteca
						if (!execute("UPDATE contratto SET FlagIpoteca='$chkFlag' WHERE IdContratto=$idContratto"))
				      		Throw new Exception(getLastError());
						if ($chkFlag=='Y')
							$descrEvento = "Segnalata presenza ipoteca sul bene finanziato";
						else
							$descrEvento = "Segnalata assenza ipoteca sul bene finanziato";
						break;	
				}
				
				if ($azioneSpeciale == "Y") // controlla, perch� alcune azioni possono essere cambiate in "normali" dopo
				                            // la loro progettazione
				{
					// le azioni speciali vengono registrate nella relativa tabella	
					$idAzioneSpeciale = azioneSpeciale($idAzione,$idContratto,$nota);
					if($idAzioneSpeciale == false) 
				  		Throw new Exception(getLastError());
				  	else
						writeHistory($idAzione,$descrEvento,$idContratto,$nota,"NULL",$idAzioneSpeciale);
				}
				else
				{
					if (!eseguiAzione($idStatoAzione,$idContratto,$parameters,$userid))
						 Throw new Exception(getLastError());
					writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				}
				break;
			   
			case "StampaLettera":
				writeHistory($idAzione,$descrEvento,$idContratto,"Stampata '".$_REQUEST["lettera"]."'");

				deleteScadenze($idContratto);
				$parameters['TESTOSCADENZA'] = "Verificare cliente ".$pratica["NomeCliente"]." dopo invio lettera per pratica n. ".$pratica["CodContratto"];
				$parameters['DATASCADENZA'] = ISODate($_REQUEST["data"],true);
				GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
			break;
			
			case 'Svalutazione':
				$percSva = $_REQUEST["percentualeS"];
				if (!applicaPercSvalutazione($idAzione,$idContratto,$percSva,$nota))
				  	Throw new Exception(getLastError());
				
			break;
				
			default:
				writeHistory($idAzione,$descrEvento,$idContratto,$nota);
				break;
		}
		// Applica la percentuale di svalutazione, se prevista e l'azione non prevede convalida
		if ($form!="Svalutazione" && $azioneSpeciale!="Y")
		{
			applicaPercSvalutazione(0,$idContratto,$azione["PercSvalutazione"]); // non indica nella history l'azione, pech� � un effetto collaterale
		}	
		
		// Aggiorna campo DataUltimaAzione
		if ($IdUser>0) // un vero utente, non "system" o "import"
			execute("UPDATE contratto SET DataUltimaAzione=NOW() WHERE IdContratto=$idContratto");
	} // fine loop sui contratti

	// Aggiorna righe ottimizzate per le view
	updateOptInsoluti("IdContratto in (".implode(",",$idContratti).")"); // aggiorna record ottimizzazione

	//automatismi cumulativi
	if (!eseguiAutomatismiCumulativi($idStatoAzione,$idContratti,$parameters))
		Throw new Exception("Esecuzione automatismi non riuscita"); 

	header("Content-Type: text/html");
	if ($esitoAzione=="" || count($idContratti)>1)
		$esitoAzione = "Operazione eseguita";
	echo  json_encode_plus(array("success"=>true,"msg"=>$esitoAzione));	
}
catch (Exception $e)
{
	trace("edit_azione.php ".$e->getMessage());
	header("Content-Type: text/html");
	echo  json_encode_plus(array("success"=>false,"msg"=>$e->getMessage()));	
}

//---------------------------------------------------------
//
//---------------------------------------------------------
function insertIncasso($idContratto,$idCliente,$IdAllegato) {
	
	global $context;
	
	$esito = "";
	$valList = "";
	$colList = "";

	addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
	addInsClause($colList,$valList,"IdAllegato",$IdAllegato,"N");
	addInsClause($colList,$valList,"IdTipoIncasso",$_POST['IdTipoIncasso'],"N");
	if ($_POST['flag_modalita']=='E') {
		addInsClause($colList,$valList,"DataRegistrazione","curdate()","G");
		addInsClause($colList,$valList,"DataDocumento",$_POST['dataDoc'],"D");
	} else {
		addInsClause($colList,$valList,"DataRegistrazione",$_POST['dataOp'],"D");
		addInsClause($colList,$valList,"DataDocumento","","D");
	}
	addInsClause($colList,$valList,"NumDocumento",$_POST['nrDoc'],"S");
	addInsClause($colList,$valList,"ImpPagato",$_POST['importo'],"N");
	addInsClause($colList,$valList,"ImpCapitale",$_POST['capitaleI'],"I");
	addInsClause($colList,$valList,"ImpInteressi",$_POST['interessiMoraI'],"I");
	addInsClause($colList,$valList,"ImpAltriAddebiti",$_POST['altriAddebitiI'],"I");
	addInsClause($colList,$valList,"ImpSpese",$_POST['speseIncassoI'],"I");
	addInsClause($colList,$valList,"FlagModalita",$_POST['flag_modalita'],"S");
	addInsClause($colList,$valList,"Nota",$_POST['nota'],"S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	addInsClause($colList,$valList,"LastUpd","NOW()","G");
	addInsClause($colList,$valList,"IdUtente",$context['IdUtente'],"S");
	
	$sql =  "INSERT INTO incasso ($colList) VALUES ($valList)";
	//trace($sql);
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	if (!execute($sql)) {
		$esito = getLastError();
	}
	return $esito;
}

//**********************************************************************************************************
// deleteScadenze
// Cancella tutte le scadenze di una pratica da oggi in avanti se il flag appodsito (chkHidden) � ON
// (operazione che viene anche eseguita prima di generare una nuova scadenza)
//**********************************************************************************************************
function deleteScadenze($idContratto)
{
	// cancellazione delle note in data precedente 
	if($_REQUEST["chkHidden"]==true)
	{
		$sql = "DELETE FROM nota where IdContratto = ".$idContratto." and TipoNota='S' and DataScadenza >= CURDATE()";
		execute($sql);
	}		
}
//**********************************************************************************************************
// funzione di estrazione agenzie d'affido
//**********************************************************************************************************
function numAg()
{
	try 
	{
		$FlagProvvigioni=isset($_REQUEST['provvigioni'])?$_REQUEST['provvigioni']:false;
		if($FlagProvvigioni)
		{
			$sql="SELECT distinct idreparto,agenzia FROM v_provvigione order by agenzia asc ";
		}else{
			 $sql = "select * FROM v_tabs_agenzie ORDER BY NomeAgenzia";
		}
		$arr = getFetchArray($sql);
		$counter = count($arr);
		$data = json_encode_plus($arr);
		//trace($sql,FALSE);
		//trace("rows: ".count($arr),FALSE);
		echo "{total:'".$counter."', results:'".$data."'}";
	}
	catch (Exception $e)
	{
		echo "{total:'".$counter."', results:'".$e->message."'}"; // restituisce una stringa per l'eval
	}
}

//**********************************************************************************************************
// registraAzioneSpeciale
// Registra un'azione speciale (inserimento o cancellazione) provvedendo anche alla scadenza e all'history
//**********************************************************************************************************
function registraAzioneSpeciale() {
	global $parameters,$descrEvento,$pratica,$nota,$idContratto,$IdUser,$esitoAzione,$idAzione;
    
	$parameters['TESTOSCADENZA'] = "Verifica $descrEvento cliente ".$pratica["NomeCliente"]." per pratica n. ".$pratica["CodContratto"];
	$addEsito = "Richiesta di $descrEvento effettuata.";
	$parameters['DATASCADENZA'] = ISODate($_REQUEST["dataScadenza"],true);

	if ($_REQUEST["dataScadenza"]) {
		// Cancella eventuale scadenza preesistente per la stessa azione e data a venire
		$sql = "DELETE FROM nota where IdContratto=$idContratto AND TipoNota='S' AND DataScadenza >= curdate()"
			  ." AND TestoNota=".quote_smart($nota);
		execute($sql);
	
		// Genera la nuova scadenza
		GeneraScadenza($parameters,$IdUser,"",$idContratto); // inserimento della scadenza in calendario
	    $esitoAzione = "$addEsito La data indicata &egrave; stata segnata nel tuo calendario";
		$dataScadenza = ISODate($_REQUEST["dataScadenza"],true);
	} else {
		$dataScadenza = "NULL";
	}
	// le azioni speciali vengono registrate nella relativa tabella
	$idAzioneSpeciale = azioneSpeciale($idAzione,$idContratto,$nota,$dataScadenza);
	if ($idAzioneSpeciale == false) 
		Throw new Exception(getLastError());
	else {
		writeHistory($idAzione,$esitoAzione,$idContratto,$nota,"NULL",$idAzioneSpeciale);
		return true;
	}
}

//--------------------------------------------------------------------
// Gestisce l'inserimento o aggiornamento di un azione speciale
// Se infatti esiste una azione dello stesso tipo in stato W, viene
// sovrascritta quella
//--------------------------------------------------------------------
function azioneSpeciale($idAzione,$idContratto,$nota,$dataScadenza="NULL")
{
	try
	{
		global $context;
		$nota  = quote_smart($nota);
		$IdUser = $context["IdUtente"];
		
		if ($dataScadenza!="NULL" && $dataScadenza>'') 
			$dataScadenza = "'$dataScadenza'";
		
		beginTrans();
		$idAzioneSpeciale = getScalar("SELECT IdAzioneSpeciale FROM v_azioni_da_convalidare WHERE IdContratto=$idContratto AND IdAzione=$idAzione");

		if ($idAzioneSpeciale>0) { // esiste gi� la stessa azione in attesa di convalida
			$sql = "UPDATE azionespeciale SET DataEvento=NOW(),IdUtente=$IdUser,Nota=$nota,DataScadenza=$dataScadenza"
			      .",LastUpd=NOW(),LastUser=".quote_smart($context["Userid"])." WHERE IdAzioneSpeciale=$idAzioneSpeciale";
			$resp = execute($sql);
		} else {					                	
			$sql = "INSERT INTO azionespeciale (IdContratto,IdAzione,DataEvento,IdUtente,Nota,Stato,DataScadenza,LastUpd,LastUser) "
				."VALUES($idContratto,$idAzione,NOW(),$IdUser,$nota,'W',$dataScadenza,now(),".quote_smart($context["Userid"]).")";
			$resp = execute($sql);
			$idAzioneSpeciale =  getInsertId();
		}
		if ($resp)  {
			// se sono previsti allegati, effettua le operazioni necessarie
			$allegatiInseriti = json_decode($_REQUEST["allegatiInseriti"],true);
			$allegatiCancellati = json_decode($_REQUEST["allegatiCancellati"],true);
			
			// Gli allegati inseriti devono essere solo collegati tramite la tabella allegatozionespeciale
			$sql = "INSERT INTO allegatoazionespeciale (IdAllegato,IdAzioneSpeciale)";
			foreach ($allegatiInseriti as $IdAllegato) {
				if (!execute($sql . " VALUES ($IdAllegato,$idAzioneSpeciale)")) {
					rollback();
	    			return false;
				}
			}
			// Gli allegati cancellati, invece, devono essere fisicamente cancellati e scollegati
			foreach ($allegatiCancellati as $IdAllegato) {
				if (!deleteAllegato($IdAllegato)) {
					rollback();
	    			return false;
				}
			}
			commit();
			return $idAzioneSpeciale;  	
		}
	    else {
	    	rollback();
	    	return false;
	    }
	}	
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//--------------------------------------------------------------------
// gestisce l'inserimento di un azione speciale con allegato
//--------------------------------------------------------------------
function azioneSpecialeAllegato($idAzione,$idContratto,$nota,$idallegati)
{
	try
	{
		global $context;
		$nota  = quote_smart($nota);
		$IdUser = $context["IdUtente"];
		if($idAzione > 0 && $idContratto>0)
		{
			$sql = "INSERT INTO azionespeciale (IdContratto,IdAzione,DataEvento,"
				  ."IdUtente,Nota,Stato,LastUpd,LastUser) "
				  ."VALUES($idContratto,$idAzione,NOW(),$IdUser,$nota,'W',now(),".quote_smart($context["Userid"]).")";
			
		    if(execute($sql))
		    {
		    	//trace("1-".$idAzioneSpeciale);
		    	$idAzioneSpeciale =  getInsertId();  
		    	//trace("2-".$idAzioneSpeciale);
		    	$arrayIdAllegati=explode(',', $idallegati);
		    	for($i = 0; $i < count($arrayIdAllegati); $i++) {
		    	  $id = $arrayIdAllegati[$i];
		    	  $sqlallegato = "INSERT INTO allegatoazionespeciale (IdAllegato,IdAzioneSpeciale) "
		    	  	            ."VALUES ($id,$idAzioneSpeciale)"; 
		    	  if (!execute($sqlallegato)) 
		    	  	return false;	            
		    	}
		    	return  $idAzioneSpeciale;  	
		    }
		    return false;
		}	
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//--------------------------------------------------------------------
// gestisce la cancellazione di un azione speciale con allegato
//--------------------------------------------------------------------
function deleteAzioneSpeciale($idAzioneSpeciale, $storiaRecuperoAllegati)
{
try
	{
				
		$arrayIdAllegati = getFetchArray("SELECT IdAllegato FROM allegatoazionespeciale where IdAzioneSpeciale=$idAzioneSpeciale");
		$sql = "DELETE FROM allegatoazionespeciale WHERE IdAzioneSpeciale=$idAzioneSpeciale";
		
		if(!execute($sql)) {
		  return false;	
		}
		
	    for($i = 0; $i < count($arrayIdAllegati); $i++) {
		  $idallegato = $arrayIdAllegati[$i];
		  $sqlAllegati = "DELETE FROM allegato WHERE IdAllegato = ".$idallegato['IdAllegato'];
	      if(!execute($sqlAllegati)) {
		    return false;	
		  }
		}
		
     	$arrayIdRecuperoAllegati=explode(',', $storiaRecuperoAllegati);
		for($j = 0; $j < count($arrayIdRecuperoAllegati); $j++) {
		  $idstoriarecupero = $arrayIdRecuperoAllegati[$j];
	      $sqlStoriaRecuperoAllegato = "DELETE FROM storiarecupero WHERE IdStoriaRecupero = $idstoriarecupero";
		  if(!execute($sqlStoriaRecuperoAllegato)) {
		    return false;	
		  }
		}
		
	    $sqlAzioneSpeciale = "DELETE FROM azionespeciale WHERE IdAzioneSpeciale=$idAzioneSpeciale";
	    if(!execute($sqlAzioneSpeciale)) {
		  return false;	
		}
				
		return true;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

?>
