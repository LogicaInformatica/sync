<?php
require_once("workflowFunc.php");
require_once("customFunc.php");

//==============================================================
//   MOTORI DI CLASSIFICAZIONE, ASSEGNAMENTO, AFFIDAMENTO,
//   RIPARTIZIONE, PROVVIGIONE
//==============================================================
//----------------------------------------------------------------
// assign
// Assegna un operatore ad una pratica in base alle
// regole contenute in RegolaAssegnazione
// Argomenti: 1) id della pratica
// Ritorna: FALSE se qualcosa va male
//          0 se tutto va bene ma non ha potuto assegnare ad alcuno
//          IdUtente   se assegnato
//----------------------------------------------------------------
function assign($IdContratto)
{
	try
	{
		trace("assign Contratto=$IdContratto",FALSE);
		//----------------------------------------------------------
		// Se fuori recupero forzato, non assegna
		//----------------------------------------------------------
		if (fuoriRecupero($IdContratto))
		{
			trace("Assegnazione non effettuata perche' contratto fuori recupero",FALSE);
			return 0;
		}
			
		//----------------------------------------------------------
		// Dapprima verifica funzione custom
		//----------------------------------------------------------
		$IdUtente = Custom_Assignment($IdContratto);	
		if ($IdUtente!==FALSE)
		{
			if ($IdUtente>0)
				return assegnaOperatore($IdContratto,$IdUtente);
			trace("Assegnazione non effettuata perche' Custom_Assignment ha restituito 0",FALSE);
			return $IdUtente;
		}
		
		//----------------------------------------------------------
		// Trattamento standard, verifica le condizioni di ciascuna
		// assegnazione applicabile
		//----------------------------------------------------------
		$pratica = getRow("SELECT v.* FROM v_pratica_noopt v WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallita assegnazione della pratica n. $IdContratto"); 
		}
		// Seleziona tutte le regole di assegnazione che riguardano gli operatori interni
		$arrayIds = Array();
		$forceAgenzia = FALSE; // TRUE quando l'agenzia deve corrispondere (cio� non considera buona l'entry 
		                       // con idReparto=NULL se ce n'� una con IdReparto non NULL)
		$regole = getFetchArray("SELECT * FROM regolaassegnazione WHERE TipoAssegnazione='1'"
			." AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY Ordine");
		foreach ($regole as $regola)
		{
			if ($regola["IdTipoCliente"]) // condizione sul tipo di cliente
				if ($regola["IdTipoCliente"]!=$pratica["IdTipoCliente"])
					continue;
			if ($regola["IdFamiglia"]) // condizione sulla famiglia di prodotto
				if ($regola["IdFamiglia"]!=$pratica["IdFamiglia"]
				&&  $regola["IdFamiglia"]!=$pratica["IdFamigliaParent"])
					continue;
			if ($regola["IdClasse"]) // condizione sulla classificazione
				if ($regola["IdClasse"]!=$pratica["IdClasse"])
					continue;
			if ($regola["IdRegolaProvvigione"]) // condizione sulla regola provvigionale
			{
				if ($regola["IdRegolaProvvigione"]!=$pratica["IdRegolaProvvigione"])
					continue;
			}		
			if ($regola["IdAreaCliente"]) // condizione sull'area di recupero
				if ($regola["IdAreaCliente"]!=$pratica["IdAreaCliente"])
					continue;
			if ($regola["ImportoDa"]>0) // condizione sull'importo minimo
				if ($regola["ImportoDa"]>$pratica["Importo"])
					continue;
			if ($regola["ImportoA"]>0) // condizione sull'importo massimo
				if ($regola["ImportoA"]<$pratica["Importo"])
					continue;
			if ($regola["IdReparto"]!==NULL && $pratica["IdAgenzia"]!=NULL) // condizione sull'agenzia a cui � affidata la pratica
			{
				if ($regola["IdReparto"]!=$pratica["IdAgenzia"])
					continue;
				else
					$forceAgenzia = TRUE; // non accetta pi� le entry con IdReparto NULL
			}	
			else // IdReparto NULL oppure idAgenzia NULL: non dipende dall'agenzia affidataria
				if ($pratica["IdAgenzia"]>0 && $forceAgenzia) // serve match con agenzia
					continue;	
					
			// Trovato
			$IdUtente = $regola["IdUtente"];
			
			// Mette in un array gli ID e tipoDistribuzione delle agenzie individuate
			if (!array_key_exists($IdUtente,$arrayIds))
				$arrayIds[$IdUtente] = $regola["TipoDistribuzione"];
		}
		//----------------------------------------------------------------------------------
		// Se arrayIds non � vuoto, assegna la pratica all'operatore che ne ha di meno
		// (in totale, se TipoDistribuzione='C', oppure nel giorno se TipoDistribuzione='I')
		//----------------------------------------------------------------------------------
		if (count($arrayIds)>0)
		{
			// Individua l'operatore con meno pratiche assegnate
			$minimo = 9999999;
			$IdUtente = 0;
			foreach ($arrayIds as $key=>$tipo)
			{
				$numAssegnate = getScalar("SELECT COUNT(*) FROM contratto WHERE IdOperatore=$key"
										 . ($tipo=='I'?" AND DataCambioClasse=CURDATE()":"")
										 );
				if ($numAssegnate<$minimo)
				{
					$IdUtente = $key;
					$minimo = $numAssegnate;
				}
			}
			// Adesso $IdUtente contiene il valore cercato
			if ($pratica["IdOperatore"]!=$IdUtente)
				if (!assegnaOperatore($IdContratto,$IdUtente,true))
					return FALSE;
			return $IdUtente;
		}
		trace("Assegnazione non effettuata perche' nessuna regola di assegnazione e' soddisfatta",FALSE);
		return 0; // torna 0 ma non FALSE, per indicare tutto bene ma non assegnata
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-------------------------------------------------------------------
// assignAgent
// Assegna una pratica ad un operatore esterno (agente dell'agenzia)
// secondo una regola di distribuzione equa
// Argomenti: 1) id della pratica
// Ritorna: FALSE se qualcosa va male
//          0 se tutto va bene ma non ha assegnato ad alcuno
//          IdUtente   se assegnato
// NOTA BENE: per ora la funzione implementa l'algoritmo semplice
//          richiesto, ma per uniformit� con il resto dovrebbe
//          usare la tabella delle regole di assegnazione e la
//          procedura custom_AgentAssignment       
//----------------------------------------------------------------
function assignAgent($IdContratto)
{
	try
	{
		trace("assignAgent Contratto=$IdContratto",FALSE);
		//----------------------------------------------------------
		// Se fuori recupero forzato, non assegna
		//----------------------------------------------------------
		if (fuoriRecupero($IdContratto))
			return 0;

		//----------------------------------------------------------
		// Se in lavorazione interna, non assegna
		//----------------------------------------------------------
		if (lavorazioneInterna($IdContratto))
			return 0;
			
		//----------------------------------------------------------
		// Legge la pratica
		//----------------------------------------------------------
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallita assegnazione della pratica n. $IdContratto"); 
		}
		$IdAgenzia = $pratica["IdAgenzia"];
		//----------------------------------------------------------
		// Se non affidata oppure gi� assegnata, non assegna
		//----------------------------------------------------------
		if (!($IdAgenzia>0) || $pratica["IdAgente"]>0)
			return 0;
		
		//----------------------------------------------------------
		// Dapprima verifica funzione custom
		//----------------------------------------------------------
		$IdUtente = Custom_AgentAssignment($IdContratto);	
		if ($IdUtente!==FALSE)
		{
			if ($IdUtente>0)
				return assegnaAgente($IdContratto,$IdUtente,true);
			return $IdUtente;
		}

		//----------------------------------------------------------
		// Se un'altra pratica dello stesso cliente � in affido
		// presso questa agenzia, assegna lo stesso operatore
		//----------------------------------------------------------
		$IdCliente = $pratica["IdCliente"];
		$IdAgente = getScalar("SELECT IdAgente FROM contratto"
			 ." WHERE IdCliente=$IdCliente AND IdAgenzia=$IdAgenzia AND IdContratto!=$IdContratto"
			 ." AND IdAgente>0");
		if ($IdAgente)
		{
			if ($IdAgente>0)
				return assegnaAgente($IdContratto,$IdAgente,true);
			return $IdAgente;
		}
		
		//----------------------------------------------------------
		// Verifica le regole di assegnazione agli agenti
		//----------------------------------------------------------
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallita assegnazione della pratica n. $IdContratto ad operatore di agenzia"); 
		}
		// Seleziona tutte le regole di assegnazione che riguardano gli operatori esterni
		$arrayIds = Array();
		$regole = getFetchArray("SELECT * FROM regolaassegnazione WHERE TipoAssegnazione='3'"
			." AND IdReparto=$IdAgenzia"
			." AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY Ordine");
		foreach ($regole as $regola)
		{
			if ($regola["IdTipoCliente"]) // condizione sul tipo di cliente
				if ($regola["IdTipoCliente"]!=$pratica["IdTipoCliente"])
					continue;
			if ($regola["IdFamiglia"]) // condizione sulla famiglia di prodotto
				if ($regola["IdFamiglia"]!=$pratica["IdFamiglia"]
				&&  $regola["IdFamiglia"]!=$pratica["IdFamigliaParent"])
					continue;
			if ($regola["IdClasse"]) // condizione sulla classificazione
				if ($regola["IdClasse"]!=$pratica["IdClasse"])
					continue;
			if ($regola["IdAreaCliente"]) // condizione sull'area di recupero
				if ($regola["IdAreaCliente"]!=$pratica["IdAreaCliente"])
					continue;
					if ($regola["ImportoDa"]>0) // condizione sull'importo minimo
				if ($regola["ImportoDa"]>$pratica["Importo"])
					continue;
			if ($regola["ImportoA"]>0) // condizione sull'importo massimo
				if ($regola["ImportoA"]<$pratica["Importo"])
					continue;

			// Trovato
			$IdUtente = $regola["IdUtente"];
			
			// Mette in un array gli ID e tipoDistribuzione delle agenzie individuate
			if (!array_key_exists($IdUtente,$arrayIds))
				$arrayIds[$IdUtente] = $regola["TipoDistribuzione"];
		}
		//----------------------------------------------------------------------------------
		// Se arrayIds � vuoto, presume una distribuzione equa tra tutti gli utenti del
		// reparto aventi profilo 5 
		//
		// SOPPRESSO IL 27/12/2011 PER EVITARE ASSEGNAZIONI NON VOLUTE
		//----------------------------------------------------------------------------------
		if (count($arrayIds)==0)
		{
			return 0;
//			$arrayIds = getFetchKeyValue("SELECT DISTINCT u.IdUtente,'C' AS Distr"
//				." FROM utente u JOIN profiloutente pu ON u.IdUtente=pu.IdUtente"
//				." WHERE CURDATE() BETWEEN u.DataIni AND u.DataFin AND IdStatoUtente=1"
//  	        ." AND u.IdReparto=$IdAgenzia AND IdProfilo=5","IdUtente","Distr");
		}
		
		//----------------------------------------------------------------------------------
		// Se arrayIds non � vuoto, assegna la pratica all'operatore che ne ha di meno
		// (in totale, se TipoDistribuzione='C', oppure nel giorno se TipoDistribuzione='I')
		//----------------------------------------------------------------------------------
		if (count($arrayIds)>0)
		{
			// Individua l'agenzia con meno pratiche assegnate
			$minimo = 9999999;
			$IdUtente = 0;
			foreach ($arrayIds as $key=>$tipo)
			{
				$numAssegnate = getScalar("SELECT COUNT(*) FROM contratto WHERE IdAgente=$key"
										 . ($tipo=='I'?" AND DataInizioAffido>=CURDATE()":"")
										 );
				if ($numAssegnate<$minimo)
				{
					$IdUtente = $key;
					$minimo = $numAssegnate;
				}
			}
			// Adesso $IdUtente contiene il valore cercato
			if (!assegnaAgente($IdContratto,$IdUtente,true))
				return FALSE;
			return $IdUtente;
		}
		return 0; // torna 0 ma non FALSE, per indicare tutto bene ma non assegnata
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}

//----------------------------------------------------------------
// delegate (affida ad agenzia)
// Assegna un'agenzia di affidamento ad una pratica in base alle
// regole contenute in RegolaAssegnazione
// Argomenti: 1) id della pratica
// Ritorna: FALSE se qualcosa va male
//          0 se tutto va bene ma non ha potuto affidare ad alcuno
//          IdReparto   se affidato
//----------------------------------------------------------------
function delegate($IdContratto)
{
	try
	{
		$subtrace = FALSE; // mettere a true per traccia dettagliata
		trace("delegate Contratto=$IdContratto",FALSE);
		//----------------------------------------------------------
		// Se fuori recupero forzato, non affida
		//----------------------------------------------------------
		if (fuoriRecupero($IdContratto))
			return 0;
			
		//----------------------------------------------------------
		// Se in lavorazione interna o in workflow, non assegna
		//----------------------------------------------------------
		if (lavorazioneInterna($IdContratto))
			return 0;

		// 2014-07-10: se il contratto ha il flag di blocco affido, anzich� affidarlo lo mette in lav interna
		// con l'apposita categoria ("Blocco affido per anomalia", id=10)
		if (rowExistsInTable("contratto","IdContratto=$IdContratto AND IF(FlagBloccoAffido>'',FlagBloccoAffido,'N') NOT IN ('N','U')")) {
			impostaStato("INT",$IdContratto,10,   // mette in stato di lavorazione interna con categoria 10 (blocco)
				"Pratica messa in 'Lavorazione interna' a causa di un blocco automatico dell'affido per anomalia nei dati");
			return 0;
		}
			
		$IdRepartoScelto = NULL;
		//--------------------------------------------------------------------------------------------------
		// Determina quale agenzia aveva in affido la volta prima e legge l'eventuale affido forzato
		// 22/12/2011: aggiunta condizione per escludere le eventuali righe di Assegnazione per lav.interna
		//--------------------------------------------------------------------------------------------------
		$dati = getRow("SELECT a.IdAgenzia,c.FlagForzaSeDBT,c.IdStatoRecupero,IFNULL(a.IdAffidoForzato,-1) AS IdAffidoForzato,r.IdReparto AS IdNuovaAgenzia,r.durata"
		   ." from assegnazione a LEFT JOIN regolaprovvigione r ON r.IdRegolaProvvigione=a.IdAffidoForzato"
		   ." JOIN contratto c ON c.IdContratto=a.IdContratto"
		   ." where a.IdContratto=$IdContratto AND a.datafin BETWEEN CURDATE()-INTERVAL 10 DAY AND CURDATE()  AND a.IdAgenzia>0 AND NOT EXISTS "
	       ." (select 1 from assegnazione x where x.datafin>a.datafin and x.idcontratto=a.idcontratto and x.datafin<=CURDATE())");

	    if (is_array($dati))
	    {
	    	$IdAgenziaPrec   = $dati["IdAgenzia"];
			trace("Agenzia di precedente affido=$IdAgenziaPrec",FALSE);
			$IdAffidoForzato = $dati["IdAffidoForzato"]; // eventuale IdRegolaProvvigione scelto da utente
			if ($IdAffidoForzato==0) // forzatura del tipo: no affido
			{
				// Non assegnabile ad alcuna agenzia: la revoca in automatico
				if (revocaAgenzia($IdContratto,true)===FALSE) // toglie affidamento, in modo automatico
					return FALSE;
				trace("Affido non effettuato perche' rilevata una precedente richiesta di forzatura fuori affido",FALSE);					
				impostaStato("INT",$IdContratto); // imposta stato Lavorazione interna
				return 0;
			}
			else if ($IdAffidoForzato==-2) // generica assegnazione a legale
			{
				trace("Affido non effettuato perche' rilevata una richiesta di forzatura con indicazione generica di affido ad un legale",FALSE);
				return 0;
			}
    		// se FlagForzaSeDBT=Y e lo stato recupero non � ATS (in attesa di affido STR/LEG) non applica la forzatura
    		if ($IdAffidoForzato>0 && $dati["FlagForzaSeDBT"]=='Y' && $dati["IdStatoRecupero"]!=25)
    		{
				trace("Forzatura affido (id=$IdAffidoForzato) non effettuata perche' la pratica non e' ancora in attesa di affido STR/LEG",FALSE);					
    			$IdAffidoForzato = -1;
    			$IdAgenziaPrec   = null;
    		}
	    }
	    else // record di assegnazione non letto
	    {
	    	$IdAffidoForzato = -1;
	    	$IdAgenziaPrec   = null;
	    }
	    
		if ($IdAffidoForzato>0) // forzatura esplicita richiesta durante affido precedente
		{
			$IdRepartoScelto 	 = $dati["IdNuovaAgenzia"];
			$IdRegolaProvvigione = $IdAffidoForzato;
			$durataProvv         = $dati["durata"]; // durata specificata nella regola provvigione
			trace ("Affido forzato (da tabella Assegnazione) con IdRegolaProvvigione=$IdRegolaProvvigione",FALSE);
		}
		else // no forzature durante affido precedente: verifica se esiste forzatura a livello di contratto
		     // (solo se contratto non affidato attualmente, altrimenti il campo CodRegolaProvvigione si riferisce
		     //  all'affido attuale, non � la forzatura)
		{
			$dati = getRow("SELECT r.IdRegolaProvvigione,r.IdReparto AS IdNuovaAgenzia,r.durata,c.CodRegolaProvvigione,FlagForzaSeDBT,IdStatoRecupero"
			              ." FROM contratto c LEFT JOIN regolaprovvigione r ON r.CodRegolaProvvigione=c.CodRegolaProvvigione"
			              ." AND CURDATE()+INTERVAL 1 MONTH BETWEEN r.DataIni AND r.DataFin"
			              ." WHERE IdContratto=$IdContratto AND IdAgenzia IS NULL");
			if (count($dati)>0)
			{
				if ($dati["CodRegolaProvvigione"]=="-2") // forzatura affido a legale generico
				{
					trace("Affido non effettuato perche' rilevata una richiesta di forzatura con indicazione generica di affido ad un legale",false);
				}
				else
				{
					// se FlagForzaSeDBT=Y e lo stato recupero non � ATS (in attesa di affido STR/LEG) non applica la forzatura
		    		if ($dati["FlagForzaSeDBT"]=='Y' && $dati["IdStatoRecupero"]!=25)
						trace("Forzatura affido (id=".$dati["IdRegolaProvvigione"].") non effettuata perche' la pratica non e' ancora in attesa di affido STR/LEG",FALSE);					
		    		else
		    		{
						$IdRepartoScelto 	 = $dati["IdNuovaAgenzia"];
						$IdRegolaProvvigione = $dati["IdRegolaProvvigione"];
						$IdAffidoForzato     = $IdRegolaProvvigione;
						$durataProvv         = $dati["durata"]; // durata specificata nella regola provvigione
						if ($IdRegolaProvvigione>0)
							trace ("Affido forzato (da tabella Contratto) con IdRegolaProvvigione=$IdRegolaProvvigione",FALSE);
		    		}
				}
			}
		}

		//--------------------------------------------------------------------------------------
		// Dapprima verifica se c'� una forzatura data con l'azione "Forza prossimo affido"
		// Poi chiama le logiche custom
		//--------------------------------------------------------------------------------------
		if ($IdRepartoScelto>0)
		{
			$msgForHistory = "Applicata una precedente richiesta di forzatura";
//			writeHistory("NULL","Applicata una precedente richiesta di forzatura",$IdContratto,"");
		}
		else
		{
			$IdRepartoScelto = Custom_Delegation($IdContratto,$msgForHistory,$IdRegolaProvvigione,$IdAgenziaPrec,$durataProvv);	
			trace("Custom_Delegation=$IdRepartoScelto",FALSE);
			if ($IdRepartoScelto!==FALSE)
			{
				if ($IdRepartoScelto>0)
				{
					// forzatura per seguire il flusso standard
				}	
				else
				{
					// Non assegnabile ad alcuna agenzia: la revoca in automatico
					if (revocaAgenzia($IdContratto,true)===FALSE) // toglie affidamento, in modo automatico
						return FALSE;
					if ($msgForHistory>"")
						writeHistory("NULL",$msgForHistory,$IdContratto,"");
					return 0;
				}
			}
		}
		//----------------------------------------------------------
		// Trattamento standard, verifica le condizioni di ciascuna
		// assegnazione applicabile e mette in un'array gli Id
		// delle agenzie che soddisfano il criterio
		//----------------------------------------------------------
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallito affidamento della pratica n. $IdContratto"); 
		}
		if ($pratica["IdAreaCliente"]==NULL)
		{
			trace("Cliente ".$pratica["IdCliente"]." privo di IdArea",FALSE);
		}
		
		// Se gia' affidata, non affida (la pratica resta per il momento all'agenzia che ce l'ha)
		if ($pratica["IdAgenzia"]>0)
		{
			trace("Contratto gia' affidato, l'affido non viene modificato",FALSE);
			return 0;
		}

		// Usa MySQL per sapere che giorno di inizio considerare, tra OGGI e DOMANI
		// dipende dal fatto se � superata o meno l'ORA_FINE_GIORNO
		$oraFineGiorno = getSysParm("ORA_FINE_GIORNO","24");
		$dataInizioAffido = getScalar("SELECT DATE(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR)");
		$dataInizioAffido = dateFromString($dataInizioAffido);
		
		// Seleziona tutte le regole di assegnazione che riguardano le agenzie
		$arrayIds = Array();
		$forceCond = FALSE; // TRUE quando c'� una condizione (cio� non considera buona l'entry 
		                    // con Condizione=NULL se ce n'� una con Condizione non NULL)
		$preferred = FALSE; // TRUE quando una regola ha tipoDistribuzione=P (prioritaria)
		
		if ($IdRepartoScelto>0) // forzatura: determina direttamente la riga applicabile (att.ne a quelle legate ad uno specifico IdRegolaProvvigione)
			$regole = getFetchArray("SELECT * FROM regolaassegnazione WHERE TipoAssegnazione='2'"
				." AND CURDATE() BETWEEN DataIni AND DataFin AND IdReparto=$IdRepartoScelto AND (IdRegolaProvvigione IS NULL OR IdRegolaProvvigione=$IdRegolaProvvigione)");
		else
			$regole = getFetchArray("SELECT * FROM regolaassegnazione WHERE TipoAssegnazione='2'"
				." AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY Ordine,Condizione DESC,TipoDistribuzione DESC");	
				
		foreach ($regole as $regola)
		{
			if ($IdRepartoScelto!=$regola["IdReparto"]) // non forzato dalla custom_delegation
			{
				if ($subtrace) trace("IdRegola=".$regola["IdRegolaAssegnazione"],FALSE);
				if ($regola["IdTipoCliente"]) // condizione sul tipo di cliente
					if ($regola["IdTipoCliente"]!=$pratica["IdTipoCliente"]) {
						if ($subtrace) trace("Scartata per check su IdTipoCliente",FALSE);
						continue;
					}
				if ($regola["IdFamiglia"]) // condizione sulla famiglia di prodotto
					if ($regola["IdFamiglia"]!=$pratica["IdFamiglia"]
					&&  $regola["IdFamiglia"]!=$pratica["IdFamigliaParent"]){
						if ($subtrace) trace("Scartata per check su IdFamiglia",FALSE);
						continue;
					}
				if ($regola["IdClasse"]) // condizione sulla classificazione
					if ($regola["IdClasse"]!=$pratica["IdClasse"]) {
						if ($subtrace) trace("Scartata per check su IdClasse",FALSE);
						continue;
					}
				if ($regola["IdArea"]) // condizione sull'area di recupero
					if ($regola["IdArea"]!=$pratica["IdAreaCliente"]) {
						if ($subtrace) trace("Scartata per check su IdAreaCliente",FALSE);
						continue;
					}
				if ($regola["ImportoDa"]>0) // condizione sull'importo minimo
					if ($regola["ImportoDa"]>$pratica["Importo"]){
						if ($subtrace) trace("Scartata per check su ImportoDa",FALSE);
						continue;
					}
				if ($regola["ImportoA"]>0) // condizione sull'importo massimo
					if ($regola["ImportoA"]<$pratica["Importo"]){
						if ($subtrace) trace("Scartata per check su ImportoA",FALSE);
						continue;
					}
	
				if ($regola["Condizione"]>'') // condizione speciale
				{
					$bool = getScalar("SELECT 1 FROM v_cond_affidamento c WHERE IdContratto=$IdContratto AND ".$regola["Condizione"]);
					if ($bool==1)
					{
						$forceCond = TRUE; // non accetta piu' le entry con Condizione NULL
						trace("Verificata condizione affido: ".$regola["Condizione"],FALSE);
					}
					else {
						if ($subtrace) trace("Scartata per check su Condizione",FALSE);
						continue;
					}
				}	
				else // Condizione NULL
					if ($forceCond) {
						if ($subtrace) trace("Scartata perche' non soddisfa condizione su regola precedente",FALSE);
						continue;
					}
				$durata = $regola["DurataAssegnazione"];
			}
			else // reparto prestabilito da forzatura: legge da RegolaProvvigione la vera durata
			{
				if ($durataProvv>0)
					$durata = $durataProvv;
			}
			
			// Eventuale variazione durata provvigione 
			if ($IdRepartoScelto>0)// regola provvigione gia' determinata dalle forzature
			{
				$durataNew = Custom_Duration($IdContratto,$IdRegolaProvvigione,$dataInizioAffido);
				if ($durataNew>0)
					$durata = $durataNew;
			}
			
			$IdReparto =  $regola["IdReparto"]; // Agenzia selezionata
			if ($regola["IdArea"]>0 && !($IdRepartoScelto>0)) // soddisfatta condizione sull'area di recupero
				trace("Area id=".$regola["IdArea"]." assegnata ad agenzia $IdReparto",FALSE);
			$dataInizioReale = $dataInizioAffido;						
			if ($regola["GiorniFissiInizio"]>"") // affido possibile solo in giorni prefissati (cioe' "per lotti")
			{
				// Se sono stabiliti dei giorni fissi, puo' affidare la pratica se oggi e' un giorno fisso modificato,
				// se oggi è il giorno fisso e non è tra quelli modificati, 
			    // ma anche se la pratica e' in attesa da prima del precedente giorni fisso o giorno fisso modificato
				// (perche' significa che quel giorno il motore non ha girato o non l'ha individuata oppure era un 
				// giorno fisso ma modificato)	
				$boolVariazione = getScalar("SELECT COUNT(*) FROM dataaffido WHERE DATE_FORMAT(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR,'%Y-%m-%d') = DataAffidoVariata");	
				if ($boolVariazione!="1") //oggi non è uno dei giorni variati 
				{
				    $bool = getScalar("SELECT DAY(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR) IN (".$regola["GiorniFissiInizio"].")");
					if ($bool!="1") // oggi non e' uno dei giorni stabiliti
					{
						//if (!inAttesaDaPrima($IdContratto,$regola["GiorniFissiInizio"],$dataInizioAffido))
						if (!giornoFissoRinviato($regola["GiorniFissiInizio"],$dataInizioAffido,$dataInizioReale,$dataInizioAffidoStandard))
						{
							trace("Non affidata ad agenzia n. $IdReparto perche' non e' uno dei giorni fissi ".$regola["GiorniFissiInizio"],FALSE);
							continue;
						}
						else {
							trace("Pratica ".$pratica["IdContratto"]." affidabile all\'agenzia $IdReparto dal giorno fisso precedente ".ISODate($dataInizioAffidoStandard),FALSE);
							$dataInizioAffido = $dataInizioAffidoStandard;
						}	
					} else {
						//controllo se è uno dei giorni stabiliti variati 
						$boolFissoVariato = getScalar("SELECT COUNT(*) FROM dataaffido WHERE DATE_FORMAT(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR,'%Y-%m-%d') = DataAffidoStandard");
						if ($boolFissoVariato==1) //oggi è uno dei giorni stabiliti e variati
						{
							//sostituisco il giorno stabilito con il giorno variato e controllo il rinvio	
							$arrGiorni = getRow("SELECT DAY(DataAffidoVariata) as giornoVariato, DAY(DataAffidoStandard) as giornoFisso FROM dataaffido WHERE DATE_FORMAT(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR,'%Y-%m-%d') = DataAffidoStandard");
							extract($arrGiorni);
							$giorniFissi = $regola["GiorniFissiInizio"];
							$array = split(",",$giorniFissi);
							/* modifico il giorno fisso 
							  col il giorno variato usando array_search */
							$array[array_search($giornoFisso,$array)]=$giornoVariato; 
							$giorniFissiVariati = implode(",", $array);
						  	if (!giornoFissoRinviato($giorniFissiVariati,$dataInizioAffido,$dataInizioReale,$dataInizioAffidoStandard))
							{
								trace("Non affidata ad agenzia n. $IdReparto perche' non e' uno dei giorni fissi ".$regola["GiorniFissiInizio"],FALSE);
								continue;
							}
							else {
								trace("Pratica ".$pratica["IdContratto"]." affidabile all\'agenzia $IdReparto dal giorno fisso precedente ".ISODate($dataInizioAffido),FALSE);
								$dataInizioAffido = $dataInizioAffidoStandard;
							}		
						}
					  }	
				} else {
					//oggi è uno dei giorni inizio affido variati assegno alla data che mi serve
					//per calcolare il fine affido la DataInizioAffido standard
					$dataInizioAffidoStandard = getScalar("SELECT DataAffidoStandard FROM dataaffido WHERE DATE_FORMAT(NOW()+INTERVAL ".(24-$oraFineGiorno)." HOUR,'%Y-%m-%d') = DataAffidoVariata"); 
					$dataInizioAffido = dateFromString($dataInizioAffidoStandard);
				}
			}
					
			// Gestisce le regole con tipoDistribuzione=P (assegnazione prevalente su quelle senza P)
			if ($regola["TipoDistribuzione"]=="P")
			{
				$preferred = TRUE;
				$arrayIds = Array(); // toglie le altre regole trovate
			}
			else if ($preferred==TRUE) // regola senza P, ma e' stata incontrata una P: ha la precedenza
				continue;	
			
			// Mette in un array gli ID e tipoDistribuzione+giorniFissiFine delle agenzie individuate
			if (!array_key_exists($IdReparto,$arrayIds))
				$arrayIds[$IdReparto] = $regola["TipoDistribuzione"].";".$regola["GiorniFissiFine"].";$durata;".$regola["IdRegolaProvvigione"];
		}
		
		//----------------------------------------------------------------------------------
		// Se arrayIds contiene almeno 2 agenzie, ed e' previsto il cambio di agenzia,
		// elimina quella corrente dall'array
		//----------------------------------------------------------------------------------
//		if ($pratica["FlagCambioAgente"]=="Y" && $IdAgenziaPrec>0 && !($IdAffidoForzato>0)) // la classe attuale prevede il cambio di agenzia da un affido al successivo
// dal 24/3/2012: non solo nel caso di affidoForzato, ma anche nel caso di decisione di customFunc (flotte)
//                il cambio agenzia non viene forzato
		if ($pratica["FlagCambioAgente"]=="Y" && $IdAgenziaPrec>0 && !($IdRepartoScelto>0)) // la classe attuale prevede il cambio di agenzia da un affido al successivo
		{
			if (count($arrayIds)>1) // c'e' piu' di una scelta
			{
				if (array_key_exists($IdAgenziaPrec,$arrayIds)) // l'agenzia precedente � una di quelle selezionabili
				{
					unset($arrayIds[$IdAgenziaPrec]); // toglie elemento dall'array
					trace("Non affidata ad agenzia n.$IdAgenziaPrec perche' e' obbligatorio il cambio di agenzia",FALSE);
				}
			}
		}
		
		//----------------------------------------------------------------------------------
		// Se una delle agenzie selezionate ha tipoDistribuzione=P, prevale sulle altre
		//----------------------------------------------------------------------------------
		foreach ($arrayIds as $key=>$value)
		{
			$values = split(";",$value); // separa tipo distribuzione da giorni fissi fine
			if ($values[0]=="P")
			{
				unset($arrayIds); // toglie tutto
				$arrayIds[$key] = $value; // rimette solo questo elemento
				break;		
			}
		}			
		
		//----------------------------------------------------------------------------------
		// Se arrayIds non e' vuoto, distribuisce la pratica all'agenzia che ne ha di meno
		// (in totale, se TipoDistribuzione='C', oppure nel lotto se TipoDistribuzione='I')
		//----------------------------------------------------------------------------------
		if (count($arrayIds)>0)
		{
			trace("Affidi possibili alle agenzie: ".join(", ",array_keys($arrayIds)),FALSE);
			// Individua l'agenzia+provvigione con meno pratiche assegnate
			$minimo = 9999999;
			$IdReparto = 0;
			foreach ($arrayIds as $key=>$value)
			{
				$value = split(";",$value); // separa tipo distribuzione da giorni fissi fine
				$tipo = $value[0];
				$durataTemp 	 = $value[2];
				$IdRegolaProvvigioneTemp = $value[3]; // se la regola assegnazione determina una specifica regola provv.
				$giorniFissiTemp = $value[1];
				
				if (!($IdRegolaProvvigioneTemp>0))
				{ 
					if ($IdRepartoScelto>0)// regola provvigione gia' determinata dalle forzature
						$IdRegolaProvvigioneTemp = $IdRegolaProvvigione;
					else
					{
						// data riferimento (fine lotto standard)
						$data = mktime(0,0,0,date("n",$dataInizioAffido)+1,date("j",$dataInizioAffido)-1,date("Y",$dataInizioAffido)); 
						$IdRegolaProvvigioneTemp = trovaProvvigioneApplicabile($IdContratto,$key,$CodProvv,$data,$durataProvv);
						$durataNew = Custom_Duration($IdContratto,$IdRegolaProvvigioneTemp,$dataInizioAffido);
						if ($durataNew>0)
							$durataTemp = $durataNew;
						else if ($durataProvv>0)
							$durataTemp = $durataProvv;
					}
				}
				if ($IdRegolaProvvigioneTemp>0) // regola trovata
				{
					// Calcola la data di fine affido
					if (!$durataTemp) $durataTemp = 30;
					// se multiplo di 30, si intende un numero intero di mesi
					if ($durataTemp%30==0)
						$data = mktime(0,0,0,date("n",$dataInizioAffido)+$durataTemp/30,
								date("j",$dataInizioAffido)-1,date("Y",$dataInizioAffido)); // data fine affido (cio� ultimo giorno di affido, incluso)
					else
						$data = mktime(0,0,0,date("n",$dataInizioAffido),date("j",$dataInizioAffido)+$durataTemp-1,date("Y",$dataInizioAffido)); // data fine affido (cio� ultimo giorno di affido, incluso)
					if ($giorniFissiTemp>"") // se deve terminare solo in giorni prefissati (causa inizio fase successiva "per lotti")
					{
						$fine   = date("j",$data); // giorno calcolato di fine affido
						$giorni = split(",",$giorniFissiTemp); // array dei numeri di giorno
						$found  = FALSE;
						foreach ($giorni as $giorno)
						{
							if ($fine <= $giorno || $giorno==0) // e' entro questo giorno della lista (giorno=0 significa ultimo del mese)
							{
								$found = TRUE; 
								if ($giorno==0) // mette la fine del mese
									$data  = mktime(0,0,0,date("n",$data)+1,0,date("Y",$data)); // ultimo del mese detto
								else // normale: impone la fine nel giorno fisso
									$data  = mktime(0,0,0,date("n",$data),$giorno,date("Y",$data)); // sposta al giorno detto
								break;            // trovato
							}
						}
						if (!$found) // non trovato (e' un giorno oltre l'ultimo dei giorni fissi)
							$data  = mktime(0,0,0,date("n",$data)+1,$giorni[0],date("Y",$data)); // sposta al giorno fisso del mese successivo
					}
				
					// Calcola quanti contratti sono gia' affidati a questo cod. provvigione e lotto
					$numAssegnate = getScalar("SELECT COUNT(*) FROM contratto c"
				                         ." WHERE IdRegolaProvvigione=0".$IdRegolaProvvigioneTemp
								         . ($tipo=='I'?" AND DataFineAffido='".ISODate($data)."'":""));
					trace("Agenzia $key NumAssegnate=$numAssegnate",FALSE);
					if ($numAssegnate<$minimo)
					{
						$IdReparto = $key;
						$minimo = $numAssegnate;
						$dataFineAffido = $data;
						$durata = $durataTemp;
						$IdRegolaProvvigione = $IdRegolaProvvigioneTemp;
					}
				}
			}

			// Adesso $IdReparto contiene il valore cercato, a meno che qualcosa manchi (da regolaProvvigione)
			if ($IdReparto>0)
			{
				//Modifica del 09/02/2018: Controllo spostamento in avanti o indietro della DataFineAffido
				//in base ad una eventuale modifica della DataInizioAffido del Lotto successivo	
				if (!controlloDataFineAffido($dataFineAffido))
				    return FALSE;	
				if (!affidaAgenzia($IdContratto,$IdReparto,$dataFineAffido,true,$dataInizioReale,$IdRegolaProvvigione))
					return FALSE;
				if ($msgForHistory>"")
					writeHistory("NULL",$msgForHistory,$IdContratto,"");
			}
			return $IdReparto;
		}
		
		// Non assegnabile ad alcuna agenzia: la revoca in automatico
		trace ("affido per contratto $IdContratto: nessuna regola di assegnazione applicabile",FALSE);
		if (revocaAgenzia($IdContratto,true)===FALSE) // toglie affidamento, in modo automatico
			return FALSE;
		return 0; // 0 indica OK ma non ho affidato
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}
//----------------------------------------------------------------------------------------------------------
// giornoFissoRinviato
// Determina se il giorno fisso precedente � passato senza affido (di solito perch� festivo) e se oggi
// � un giorno vaildo per effettuare l'affido non fatto in quel giorno.
//----------------------------------------------------------------------------------------------------------
function giornoFissoRinviato($giorniFissi,&$dataFissa,&$dataInizioReale,&$dataFissaStandard)
{
	//----------------------------------------------------------------
	// Trova quale giorno fisso precede oggi
	//----------------------------------------------------------------
	$giorni = split(",",$giorniFissi); // array dei numeri di giorno
	$oggi   = date("j");
	for ($i=count($giorni)-1; $i>=0; $i--) // trova il giorno fisso che precede oggi
	{
		$giorno = $giorni[$i];
		if ($oggi >= $giorno) // giorno fisso precedente
		{
			$data  = mktime(0,0,0,date("n"),$giorno,date("Y")); // data del giorno fisso
			break;            // trovato
		}
	}
	if ($i<0) // non trovato (oggi � un giorno che precede il primo della lista), quindi va all'ultimo del mese precedente
		$data  = mktime(0,0,0,date("n")-1,$giorni[count($giorni)-1],date("Y")); // sposta all'ultimo giorno fisso del mese precedente
    
    //salvo la data di inizio affido precedente standard per poterla
    //utilizzare in seguito per il calcolo del fine affido precedente  
    $dataFissaStandard = $data;
    //controllo se quel giorno fisso sia una data variata
    $dataModificata = getScalar("SELECT DataAffidoVariata FROM dataaffido WHERE DataAffidoStandard = '".ISODate($data)."'");
	
	//se fosse un giorno fisso modificato la sostituisco con la data modificata
	if ($dataModificata!='') {
	   //controllo se la data modificata sia maggiore di oggi
	   //continuo perchè non affidabile
	   if ($dataModificata > date('Y-m-d')) 
	     return FALSE;
	   $data = strtotime($dataModificata);	
	}
	   
	//---------------------------------------------------------------------------------------------
	// Controlla se a partire da quel giorno fino ad oggi sono avvenuti affidi automatici
	// Se s�, significa che non c'� motivo di affidare fuori dal giorno fisso, altrimenti
	// (affido non avvenuto) si pu� affidare sul lotto a patto che non siano passati troppi giorni
	//---------------------------------------------------------------------------------------------
	$affidiFatti = getScalar("SELECT MAX(LastUpd) FROM log WHERE TipoLog='AFFIDO' AND LastUpd>='".ISODate($data,true)
	                   ."' AND DATE(LastUpd)<'".ISODate(time(),true)."'");	
	
	if ($affidiFatti>0)
		return FALSE; // non c'� motivo di affidare in ritardo
		
	// Determina quanti giorni sono tollerabili come ritardo, considerando i giorni festivi intercorsi
	$giornoSettimana = getdate($data); // giorno in cui sarebbe dovuto avvenire l'affido
	$giornoSettimana = $giornoSettimana["wday"];
	
	// correzione del 18/8/2014: sopporta 3 giorni altrimenti le feste di venerd� creano problemi
	//if ($giornoSettimana==0) // era domenica
	//	$diff = 1; // un giorno di ritardo
	//else if ($giornoSettimana==6) // era sabato
	//	$diff = 2; // due giorni di ritardo
	//else 
	//	$diff = 0;
	$diff = 3;
	
	$oggi  = mktime(0,0,0,date("n"),date("j"),date("Y")); // data completa di oggi
	if ($oggi-$data>($diff+1)*24*60*60) // giorni di ritardo maggiori del tollerabile
	{	
		//trace("Passato troppo tempo ".ISODate(time()). " ".ISODate($data),FALSE);
		return FALSE; // troppo tardi, meglio non affidare
	}
	
	// Dal 31/1/2012 distingue la data di inizio affido reale e quella a giorno fisso, che serve a calcolare il fine lotto
	$dataInizioReale = $oggi;
	$dataFissa = $data;
	return TRUE;
}
/****** VERSIONE OBSOLETA
//------------------------------------------------------------------------
// inAttesaDaPrima
// Determina se il contratto era in attesa da un giorno precedente il
// pi� recente giorno fisso (il che indica che la pratica 
// avrebbe dovuto essere affidata in quel giorno fisso, ma non lo � stato
// probabilmente perch� giorno festivo)
//------------------------------------------------------------------------
function inAttesaDaPrima($IdContratto,$giorniFissi,&$dataFissa)
{
	// cerca l'ultimo evento che non sia una revoca automatica (altrimenti pu� trovare la data del rientro effettuato subito prima,
	// che ha lo stesso problema riguardante gli eventuali giorni fissi non elaborati per festivit�)
	$dataCambioStato = getScalar("SELECT DATE(MAX(DataEvento)) FROM storiarecupero WHERE IdContratto=$IdContratto AND DescrEvento NOT LIKE '%revoca auto%'");
	// Legge la massima data di registrazione degli insoluti su questo contratto
	$dataArrivoInsoluti = getScalar("SELECT MAX(DataArrivo) FROM insoluto WHERE IdContratto=$IdContratto");
trace("dataCambioStato: ".ISODate($dataCambioStato)." dataArrivoInsoluti: ".ISODate($dataArrivoInsoluti),FALSE);
	
	if ($dataCambioStato==NULL && $dataArrivoInsoluti==NULL)
		return FALSE;	
		
	if ($dataCambioStato==NULL || $dataCambioStato>$dataArrivoInsoluti && $dataArrivoInsoluti!=NULL)
		$dataCambioStato = $dataArrivoInsoluti;

	$data = dateFromString($dataCambioStato);
	$giornoSettimana = getdate($data);
	$giornoSettimana = $giornoSettimana["wday"];
	
	if ($giornoSettimana==0) // domenica
		$diff = 2; // due giorni
	else if ($giornoSettimana==1) // luned�
		$diff = 3;
	else if ($giornoSettimana==6) // sabato
		$diff = 1;
	else 
		$diff = 0;

	$dataCambioStato = mktime(0,0,0,date("n",$data),date("j",$data)-$diff,date("Y",$data)); // porta al ven precedente
		
	//trace("Controllo inAttesaDaPrima: ".ISODate($dataCambioStato)." giorno della settimana=".$giornoSettimana,false);
		
	// Trova quale giorno fisso precede oggi
	$giorni = split(",",$giorniFissi); // array dei numeri di giorno
	$oggi   = date("j");
	for ($i=count($giorni)-1; $i>=0; $i--) // trova il giorno fisso che precede oggi
	{
		$giorno = $giorni[$i];
		if ($oggi >= $giorno) // giorno fisso precedente
		{
			$data  = mktime(0,0,0,date("n"),$giorno,date("Y")); // data del giorno fisso
			break;            // trovato
		}
	}
	if ($i<0) // non trovato (oggi � un giorno che precede il primo della lista)
		$data  = mktime(0,0,0,date("n")-1,$giorni[count($giorni)-1],date("Y")); // sposta all'ultimo giorno fisso del mese precedente

	$oggi = mktime(0,0,0,date("n"),date("j"),date("Y"));

	if ($oggi-$data>($diff+3)*24*60*60) // pi� di 3 giorni dopo la data di affido
	{	
		//trace("Passato troppo tempo ".ISODate(time()). " ".ISODate($data),FALSE);
		return FALSE; // troppo tardi, meglio non affidare
	}
	if (ISODate($dataCambioStato)<=ISODate($data))  // TRUE se il cambio stato � precedente al pi� recente giorno fisso
	{	
		$dataFissa = $data;
		trace("dataInizioAffido calcolata: ".ISODate($dataFissa),FALSE);
		return TRUE;
	}
	else
		return FALSE;
}
******************/
//----------------------------------------------------------------
// classify
// Assegna una classificazione (IdClasse) ad una pratica
// Argomenti: 1) id della pratica
// Ritorna: FALSE oppure l'Id della classe attribuita
//----------------------------------------------------------------
function classify($IdContratto,&$changed=FALSE,$escludeFuoriRecupero=TRUE)
{
	try
	{
		$subtrace = FALSE; // mettere a true per traccia dettagliata
		
		trace("classify Contratto=$IdContratto",FALSE);
		//----------------------------------------------------------
		// Se fuori recupero forzato, non classifica
		//----------------------------------------------------------
		if ($escludeFuoriRecupero)
			if (fuoriRecupero($IdContratto))
				return 0;
		
		//----------------------------------------------------------
		// Legge i dati del contratto
		//----------------------------------------------------------
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallita classificazione della pratica n. $IdContratto"); 
		}	
		//----------------------------------------------------------------------------
		// Non classifica se la data di aggiornamento dei movimenti
		// � inferiore alla data di cambio classe e si tratta di pratica positiva
		// (perch� sulle positive permanenti non arrivano pi� movimenti)
		//----------------------------------------------------------------------------
		//$dataCambioClasse = ISODate($pratica["DataCambioClasse"]);
		//$dataUltimoMov    = ISODate(getScalar("SELECT MAX(LastUpd) FROM movimento WHERE IdContratto=$IdContratto"));
		//if ($dataCambioClasse>$dataUltimoMov && $pratica["CodClasse"]=='POS' && $pratica["ImpInsoluto"]<26)
		//{
		//	trace("Riclassificazione non effettuata perche' la data di cambio classe e' posteriore alla data dei movimenti",FALSE);
		//	return 0;
		//}
		
		//----------------------------------------------------------
		// Dapprima verifica funzione custom
		//----------------------------------------------------------
		$IdClasse = Custom_Classification($IdContratto);	
		trace("Custom_Classification=".(($IdClasse===FALSE)?"FALSE":(($IdClasse===NULL)?"NULL":$IdClasse)),FALSE);
		if ($IdClasse!==FALSE)
		{	
			if (!updateClass($IdContratto,$IdClasse,$changed))
				return FALSE;
			$classe = getRow("SELECT * FROM classificazione WHERE IdClasse=$IdClasse");
			if ($classe["FlagNoAffido"]=="Y" && $classe["FlagRecupero"]=="Y") // || $IdClasse==NULL) // questa classificazione non va in affido ma � in recupero: mette stato "INT" e revoca
			{
				if ($pratica["IdAgenzia"]==NULL
			 	&& ($pratica["CodStatoRecupero"]=="NOR" || $pratica["CodStatoRecupero"]=="ATT" || $pratica["CodStatoRecupero"]=="OPE" || $pratica["CodStatoRecupero"]==null))
				{
					if (!impostaStato("INT",$IdContratto)) // imposta stato Lavorazione interna
						return FALSE;
				}
			}
			trace("classify: attribuita classe $IdClasse",FALSE);
			return $IdClasse;
		}
			
		//----------------------------------------------------------
		// Trattamento standard, verifica le condizioni di ciascuna
		// classificazione applicabile
		//----------------------------------------------------------
		// Seleziona tutte le classificazioni che possono essere applicate automaticamente
		$classi = getFetchArray("SELECT * FROM classificazione WHERE FlagManuale IN ('A','B')"
			." AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY Ordine");
		$IdClasse = NULL;
		foreach ($classi as $classe)
		{	
			$TitoloClasse = $classe["TitoloClasse"];
			if ($subtrace) trace("Test classe=$TitoloClasse ",FALSE);
			if ($classe["IdCompagnia"]) // condizione sulla compagnia (committente)
				if ($classe["IdCompagnia"]!=$pratica["IdCompagnia"])
				{
					if ($subtrace) trace("Scartata per check su IdCompagnia",FALSE);
					continue;
				}
			if ($classe["IdTipoPagamento"]) // condizione sul tipo pagamento (RID, BP)
				if ($classe["IdTipoPagamento"]!=$pratica["IdTipoPagamento"])
				{
					if ($subtrace) trace("Scartata per check su IdTipoPagamento",FALSE);
					continue;
				}
			if ($classe["IdFamiglia"]) // condizione sulla famiglia di prodotto
				if ($classe["IdFamiglia"]!=$pratica["IdFamiglia"]
				&&  $classe["IdFamiglia"]!=$pratica["IdFamigliaParent"])
				{
					if ($subtrace) trace("Scartata per check su IdFamiglia",FALSE);
					continue;
				}
			if ($classe["NumInsolutiDa"]!=NULL) // condizione sul numero di insoluti minimo
				if ($classe["NumInsolutiDa"]>$pratica["Insoluti"])
				{
					if ($subtrace) trace("Scartata per check su NumInsolutiDa",FALSE);
					continue;
				}
			if ($classe["NumInsolutiA"]!=NULL) // condizione sul numero di insoluti massimo
				if ($classe["NumInsolutiA"]<$pratica["Insoluti"])
				{
					if ($subtrace) trace("Scartata per check su NumInsolutiA",FALSE);
					continue;
				}
			if ($classe["NumRataDa"]!=NULL) // condizione sul numero di rata minimo
				if ($classe["NumRataDa"]>$pratica["Rata"])
				{
					if ($subtrace) trace("Scartata per check su NumRataDa",FALSE);
					continue;
				}
			if ($classe["NumRataA"]!=NULL) // condizione sul numero di rata massimo
				if ($classe["NumRataA"]<$pratica["Rata"])
				{
					if ($subtrace) trace("Scartata per check su NumRataA",FALSE);
					continue;
				}
// dal 13/1/2012 si confronta l'importo capitale, non il totale insoluto
// dal 8/11/2012 se � richiesta solo rata 0, si applica l'importo insoluto (caso AAD)
			if ($classe["ImpInsolutoDa"]!=NULL) // condizione sull'importo insoluto minimo
			{
				$impConfronto = ($classe["NumRataA"]==="0" ? $pratica["ImpInsoluto"]:$pratica["ImpCapitale"]);
				if ($classe["ImpInsolutoDa"]>$impConfronto)
				{
					if ($subtrace) trace("Scartata per check su ImpInsolutoDa (".$classe["ImpInsolutoDa"]." confrontato con $impConfronto)",FALSE);
					continue;
				}
			}
			if ($classe["ImpInsolutoA"]!=NULL) // condizione sull'importo insoluto massimo
			{
				$impConfronto = ($classe["NumRataA"]==="0" ? $pratica["ImpInsoluto"]:$pratica["ImpCapitale"]);
				if ($classe["ImpInsolutoA"]<$impConfronto)
				{
					if ($subtrace) trace("Scartata per check su ImpInsolutoA (".$classe["ImpInsolutoA"]." confrontato con $impConfronto)",FALSE);
					continue;
				}
			}
			if ($classe["NumGiorniDa"]!==NULL) // condizione sul numero di giorni minimo
				if ($classe["NumGiorniDa"]>$pratica["Giorni"])
				{
					if ($subtrace) trace("Scartata per check su NumGiorniDa",FALSE);
					continue;
				}
			if ($classe["NumGiorniA"]!==NULL) // condizione sul numero di giorni massimo
				if ($classe["NumGiorniA"]<$pratica["Giorni"])
				{
					if ($subtrace) trace("Scartata per check su NumGiorniA",FALSE);
					continue;
				}
			// la condizione sulla recidivit� funziona solo supponendo che si sta trattando il primo insoluto
			if ($classe["FlagRecidivo"]=="Y") // condizione sulla recidivit� 
			{
				if ($pratica["FlagRecupero"]!="Y")  // il contratto deve essere stato in recupero
				{
					if ($subtrace) trace("Scartata per check su FlagRecupero!=Y",FALSE);
					continue;
				}
			}
			else if	($classe["FlagRecidivo"]=="N") // classe applicabile solo se non recidivo
			{
				if ($pratica["FlagRecupero"]=="Y")  // il contratto deve essere stato in recupero
				{
					if ($subtrace) trace("Scartata per check su FlagRecupero==Y",FALSE);
					continue;
				}
			} 	
			// Condizione generica SQL
			if ($classe["Condizione"]>"")
			{
				if (!rowExistsInTable("v_pratica_noopt c","IdContratto=$IdContratto AND (".$classe["Condizione"].")") )
				{
					trace("Classificazione ".$classe["CodClasse"]." non applicabile causa condizione ".$classe["Condizione"],FALSE);
					continue; // se la condizione non � soddisfatta, itera
				}
				else
					if ($subtrace) trace("Condizione ".$classe["Condizione"]." soddisfatta",FALSE);
			}
					
			// Trovato
			$IdClasse = $classe["IdClasse"];
			break;
		}
		if (!updateClass($IdContratto,$IdClasse,$changed))
			return FALSE;
		if ($classe["FlagNoAffido"]=="Y" && $classe["FlagRecupero"]=="Y") // || $IdClasse==NULL) // questa classificazione non va in affido ma � in recupero: mette stato "INT" e revoca
	//	if ($classe["FlagNoAffido"]=="Y" || $IdClasse==NULL) // questa classificazione non va in affido: mette stato "INT" e revoca
		{
			if ($pratica["IdAgenzia"]==NULL
			 && ($pratica["CodStatoRecupero"]=="NOR" || $pratica["CodStatoRecupero"]=="ATT" ))
			{
				if (!impostaStato("INT",$IdContratto)) // imposta stato Lavorazione interna
					return FALSE;
				
			}
		}
		if ($changed)
			trace("classify: attribuita classe: ". ($IdClasse>0?"$IdClasse - $TitoloClasse":"nessuna"),FALSE);
		else
			trace("classify: classe invariata: classe: ". ($IdClasse>0?"$IdClasse - $TitoloClasse":"nessuna"),FALSE);
		return $IdClasse;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}

//--------------------------------------------------------------
// updateClass
// Aggiorna il record Contratto con un nuovo IdClasse
//--------------------------------------------------------------
function updateClass($IdContratto,$IdClasse,&$changed)
{
	try 
	{	
		$username = getUserName();
		beginTrans();
		if ($IdClasse==NULL)
		{
			if (!execute("UPDATE contratto SET IdClasse=NULL,DataCambioClasse=NOW(),LastUpd=NOW(),"
			."LastUser='".$username."' WHERE IdContratto=$IdContratto AND IdClasse IS NOT NULL"))
			{
				rollback();
				return FALSE;		
			}
			$classe = "non classificata";
		}
		else
		{	
			$classe = getScalar("SELECT CONCAT(CodClasse,' ',TitoloClasse) FROM classificazione WHERE IdClasse=$IdClasse");
			// Aggiorna la classe del contratto e mette anche il FlagRecupero=Y se la classe lo prevede
			//$flagRecupero = getScalar("SELECT FlagRecupero FROM classificazione WHERE IdClasse=$IdClasse");
			//if ($flagRecupero=="Y")
			//	$flagRecupero = ",FlagRecupero='Y'";
			//else
			//	$flagRecupero="";
		
			if (!execute("UPDATE contratto SET IdClasse=$IdClasse,DataCambioClasse=NOW(),LastUpd=NOW(),"
			."LastUser='".$username."' WHERE IdContratto=$IdContratto AND IFNULL(IdClasse,0)!=$IdClasse"))
			{
				rollback();
				return FALSE;		
			}
		}
		
		// Scrive sullo storico recupero l'evento (se si � trattato effettivamente di un cambio)
		if (getAffectedRows()>0)
		{
			writeHistory("NULL","Pratica riclassificata come '$classe'",$IdContratto,"");
			$changed = TRUE;
		}
		else
			$changed = FALSE;
		commit();
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}

//---------------------------------------------------------------------------------------------
// chiusureMensili
// Effettua lo spezzamento delle assegnazioni legate a regole provvigionali con chiusura mensile
//---------------------------------------------------------------------------------------------
function chiusureMensili()
{
	try
	{
		//-------------------------------------------------------------------------------------
		// Individua le assegnazioni legate a regole di provvigione con chiusura mensile
		// con data di inizio nel mese passato e data di fine in un mese futuro
		//-------------------------------------------------------------------------------------
		$sql = "SELECT * FROM v_assegnazioni_da_chiudere";
		$rows = getFetchArray($sql);
		foreach ($rows as $row) 	
		{
			controllaStopForzato(); // interrompe se messo FlagSospeso='X'
			$contratto = $row["IdContratto"];
			trace("Chiusura mensile contratto $contratto",FALSE);
			beginTrans();
			//---------------------------------------------------------------------------
			// Crea la nuova riga di Assegnazione	
			//---------------------------------------------------------------------------
			$dati = getRow("SELECT IdOperatore,IdClasse,InteressiMora,SpeseIncasso FROM v_dettaglio_insoluto WHERE IdContratto=$contratto");
			$sql = "INSERT INTO assegnazione (IdContratto,IdAgenzia,IdOperatore,IdClasse,DataIni,DataFin,LastUser,"
				."IdRegolaProvvigione,ImpInteressiMora,PercSpeseRecupero,FlagParziale,IdAffidoForzato,"
				."DataInizioAffidoContratto,DataFineAffidoContratto)"
				." SELECT c.IdContratto,c.IdAgenzia,c.IdOperatore,v.IdClasse,'".$row["DataApertura"]."',a.DataFin,"
				." a.LastUser,a.IdRegolaProvvigione,v.InteressiMora,v.SpeseIncasso,'Y',IdAffidoForzato,DataInizioAffidoContratto,DataFineAffidoContratto"
				." FROM assegnazione a "
				." JOIN contratto c ON c.IdContratto=a.IdContratto"
				." JOIN v_dettaglio_insoluto v ON v.IdContratto=a.IdContratto"
				." WHERE IdAssegnazione=".$row["IdAssegnazione"];
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			$IdAssegnazione = getInsertId(); // ID generato dall'INSERT
			//-----------------------------------------------------------------------------
			// Storicizza le rate di insoluto in StoriaInsoluto, senza per� toglierle
			// da Insoluto, visto che non sono chiuse. La data di fine viene messa
			// uguale a quella della chiusura mensile (cio� fine mese passato); la data di
			// inizio � quella dell'assegnazione
			//-----------------------------------------------------------------------------
			$rate = fetchValuesArray("SELECT NumRata FROM insoluto WHERE IdContratto=$contratto");
			foreach ($rate AS $NumRata)
			{
				if (!storicizzaInsoluto($contratto,$NumRata,"RIE",$row["DataIni"],$row["DataChiusura"])) 
				{
					rollback();
					return FALSE;
				}
			}
			//-------------------------------------------------------------------------------------
			// Modifica le righe di insoluto per riflettere 
			// il valore attuale (a inizio affido) del debito da recuperare, e imposta 
			// il campo IdAffidamento
			// per collegare gli insoluti all'Assegnazione 
			//-------------------------------------------------------------------------------------
			$sql = "UPDATE insoluto SET ImpDebitoIniziale=ImpInsoluto,ImpCapitaleAffidato=IF(ImpCapitale-ImpPagato>0 AND ImpDebitoIniziale>0,LEAST(ImpCapitale-ImpPagato,ImpDebitoIniziale),0),"
			  ."IdAffidamento=$IdAssegnazione WHERE IdContratto=$contratto";
			//trace("Modifica ImpDebitoIniziale e imposta IdAffidamento nelle righe di Insoluto per riflettere il valore attuale (a inizio affido) del debito da recuperare",FALSE);
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			
			//---------------------------------------------------------------------------
			// Chiude la vecchia riga di Assegnazione
			//---------------------------------------------------------------------------
			$sql = "UPDATE assegnazione SET DataFin='".$row["DataChiusura"]."',FlagParziale='Y' WHERE IdAssegnazione=".$row["IdAssegnazione"];
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			//----------------------------------------------------------------------------------
			// Allinea le date di inizio/fine degli storicizzati precedenti, in modo che
			// siano allineate con quelle della tabella assegnazione
			// 1/10/2012: dato che le rate viaggianti hanno idaffidamento NULL, usa una query diversa
			//            per riuscire ad aggiornare anche quelle
			//----------------------------------------------------------------------------------
			/*$sql = "update storiainsoluto s join assegnazione a
					on s.idaffidamento=a.idassegnazione and s.datafineaffido!=a.datafin and s.idagenzia=a.idagenzia
					and datafineaffido>datainizioaffido and codazione in ('RIE','POS')
					set datainizioaffido=a.dataini,datafineaffido=a.datafin
					where s.idcontratto=$contratto AND a.IdAssegnazione=".$row["IdAssegnazione"];*/
			$sql = "update storiainsoluto s join assegnazione a
					on s.idcontratto=a.idcontratto and s.datafineaffido='".$row["DataFin"]."' AND s.idagenzia=a.idagenzia
					and datafineaffido>datainizioaffido and codazione in ('RIE','POS')
					set datainizioaffido=a.dataini,datafineaffido=a.datafin
					where s.idcontratto=$contratto AND a.IdAssegnazione=".$row["IdAssegnazione"];
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			commit();
		}
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace("Errore nell'elaborazione delle revoche automatiche: ".$e->getMessage());
	}	
}

//---------------------------------------------------------------------------------------------
// revocheAutomatiche
// Effettua la revoca automatica per tutte le pratiche con data fine
// affido scaduta. NB le agenzie legali non rientrano in automatico, � la funzione
// revocaAgenzia che effettua questo controllo.
//---------------------------------------------------------------------------------------------
function revocheAutomatiche(&$listaClienti)
{
	try
	{
		// Individua tutti gli affidi scaduti
		$qualcheRientroPerCerved = FALSE;
		$ore  = 24-getSysParm("ORA_FINE_GIORNO","24"); // ore di anticipo del fine giornata
		$sql  = "SELECT c.*,r.FlagCerved FROM contratto c JOIN regolaprovvigione r ON r.IdRegolaProvvigione=c.IdRegolaprovvigione"
				." WHERE DataFineAffido BETWEEN DATE(CURDATE()-INTERVAL 5 DAY) AND DATE(NOW() - INTERVAL 1 DAY + INTERVAL $ore HOUR)";
		$rows = getFetchArray($sql);
		foreach ($rows as $row) 	
		{
			controllaStopForzato(); // interrompe se messo FlagSospeso='X'
			
			$IdAgenziaPrec = $row["IdAgenzia"];
			$CodRegolaProvvigione = $row["CodRegolaProvvigione"];
			$IdContratto = $row["IdContratto"];
			$IdCliente   = $row["IdCliente"];
			$dataFineAffido = $row["DataFineAffido"];
			$dataInizioAffido = $row["DataInizioAffido"];
			trace("Contratto $IdContratto (".$row["CodContratto"].") agenzia=$IdAgenziaPrec ($CodRegolaProvvigione) - Fine affido",FALSE);
			$ret = revocaAgenzia($IdContratto,TRUE,"RIE");    // effettua la revoca
			if ($ret===FALSE) // errore grave
			{
				trace("Fallita revoca contratto $IdContratto");
				return;		
			}
			if ($row["FlagCerved"]>'') // rientrata una pratica su regola marcata Cerved
				$qualcheRientroPerCerved = TRUE;
			
			// Segna il cliente, per poi processare gli affidi (con la gestione anche delle flotte)
			$listaClienti[$IdCliente] = $IdCliente;
			
			//--------------------------------------------------------------------------
			// Esegue operazioni custom dopo il rientro della pratica
			//--------------------------------------------------------------------------
			Custom_Return($IdContratto,$IdAgenziaPrec,$CodRegolaProvvigione,$dataInizioAffido,$dataFineAffido);

			//--------------------------------------------------------------------------
			// Ricalcola campi derivati, perch� possono essere diversi dopo il rientro
			// (interesse e spese)
			//--------------------------------------------------------------------------
			if (!aggiornaCampiDerivati($IdContratto))
			{
				trace("aggiornaCampiDerivati fallita idContratto=$IdContratto");
				return FALSE;   
			}
		}
		//--------------------------------------------------------------------------
		// Cambia il flag delle provvigioni gi� chiuse ma la cui dataFineAffido
		// � uguale a quella delle pratiche rientrate, perch� i giorni festivi
		// pu� essere passata aggiornaProvvigioni, congelandole anche se il lotto
		// non � stato chiuso dal rientro (correzione 27/2/2012)
		//--------------------------------------------------------------------------
		if ($dataFineAffido>0)
		{
			execute("UPDATE provvigione SET statoprovvigione=1 WHERE statoprovvigione=2 AND DataFin>='".ISODate($dataFineAffido)."'");		
		}
		//---------------------------------------------------------------------------------------------
		// Per tutti i lotti con regolaProvvigione avente FlagCerved=Y, produce l'estratto per Cerved
		// e lo invia agli indirizzi e-mail configurati
		//---------------------------------------------------------------------------------------------
		if ($qualcheRientroPerCerved) // c'� stata qualche revoca effettivamente
			produceFileCerved($dataFineAffido);
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace("Errore nell'elaborazione delle revoche automatiche: ".$e->getMessage());
	}		
}

//---------------------------------------------------------------------------------------------
// produceFileCerved
// Produce il file per Cerved, per tutti i lotti configurati con FlagCerved='Y' e li invia
// ai destinatari configurati
//---------------------------------------------------------------------------------------------
function produceFileCerved($dataFineAffido)
{
	try
	{
		trace("Inizio produzione dei files CERVED",FALSE);
		// Individua tutte le righe di Provvigione del lotto in questione, con regola avente FlagCerved='Y'
		$sql = "SELECT IdProvvigione,CodRegolaProvvigione,DATE_FORMAT(p.DataFin,'%d-%m-%Y') AS DataLotto, FlagCerved 
				FROM provvigione p JOIN regolaprovvigione rp ON p.IdRegolaProvvigione=rp.IdRegolaProvvigione
		        WHERE FlagCerved in ('1','2','3') AND p.datafin='$dataFineAffido'";
		trace("Inizio produzione dei files CERVED $sql",FALSE);
		$rows = getFetchArray($sql);
		if (getLastError()>"")
		{
			return FALSE;
		}
		foreach ($rows as $row) 	
		{
			$IdProvvigione = $row["IdProvvigione"];
			$CodRegolaProvvigione = $row["CodRegolaProvvigione"];
			$DataLotto = $row["DataLotto"];
			//$filePath = eseguiCreazioneFileCerved($IdProvvigione,$errmsg,'',$row["FlagCerved"]);
			$filePath = eseguiCreazioneFileCerved($IdProvvigione,$errmsg,$myFile,$row["FlagCerved"]);
			if ($errmsg>"")
			{
				trace("Fallita creazione file cerved per idProvvigione=$IdPprovvigione: $errmsg");
				return FALSE;		
			}
			if ($filePath[0]>"" && $filePath[0]!="0") // n� errore n� file vuoto
			{
				$parti 				= split("/",$filePath[0]);
				$attachment["type"] = "text/plain";
				$attachment["name"] = $parti[count($parti)-1];
				$attachment["tmp_name"] = $filePath[0];	
				$destinatario = getSysParm("CERVED_MAIL","");	
				$subject   = "File per invio a Cerved - Lotto $DataLotto - Agenzia $CodRegolaProvvigione";		
				trace("$subject - path=$filePath[0]",false);
				sendMail("",$destinatario,$subject,$message,$attachment);
			}
			if (count($filePath)==2){
				if ($filePath[1]>"" && $filePath[1]!="0") // n� errore n� file vuoto
				{
					$parti 				= split("/",$filePath[1]);
					$attachment["type"] = "text/plain";
					$attachment["name"] = $parti[count($parti)-1];
					$attachment["tmp_name"] = $filePath[1];	
					$destinatario = getSysParm("CERVED_MAIL","");	
					$subject   = "File per invio a Cerved - Lotto $DataLotto - Agenzia $CodRegolaProvvigione";		
					trace("$subject - path=$filePath[1]",false);
					sendMail("",$destinatario,$subject,$message,$attachment);
				}
			}
		}
		trace("Fine produzione dei files CERVED",FALSE);
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace("Errore nella produzione dei files CERVED: ".$e->getMessage());
	}		
}

//---------------------------------------------------------------------------------------------
// affidaInAttesa OBSOLETA
// Effettua l'affido delle pratiche in attesa (ad es. quando giunge il giorno fisso)
//---------------------------------------------------------------------------------------------
/****
function affidaInAttesa()
{
	try
	{
		// Individua tutte le pratiche in attesa di affido
		$sql = "select IdContratto from contratto where idstatorecupero=2 and idagenzia IS NULL AND idclasse in "
		      ."(select idclasse from regolaassegnazione where curdate() between dataini and datafin)";
		      		
		$ids = fetchValuesArray($sql);
		foreach ($ids as $IdContratto) 	
		{
			// Non serve classificarlo, � gi� stato fatto se necessario nel processo a monte
			$ret = delegate($IdContratto); // tenta affido
			if ($ret===FALSE) // errore grave
			{
				trace("Fallito affido pratica in attesa id=$IdContratto");
				return FALSE;		
			}
			if ($ret>0) // ritornato correttamente l'IdAgenzia
			{
			
				$ret = assignAgent($IdContratto); // assegna ad operatore di agenzia
				if ($ret===FALSE) // errore grave
				{
					trace("Fallita assegnazione agente contratto in attesa id=$IdContratto");
					return;		
				}
				eseguiAutomatismiPerAzione('AFF',$IdContratto); // esegue invio SMS differito o prep. lettere
			}
			if (!aggiornaCampiDerivati($IdContratto))
			{
				trace("aggiornaCampiDerivati fallita idContratto=$IdContratto");
				return;   
			}
		}
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace("Errore nell'elaborazione degli affidamenti pratiche in attesa: ".$e->getMessage());
	}		
}
****/
//---------------------------------------------------------------------------------------------
// ricalcolaProvvigioniPerContratto
// Effettua il ricalcolo delle provvigioni 
// Argomento opzionale: condizione su v_provvigioni_ricalcolabili per decidere quali provvigioni ricalcolare
//---------------------------------------------------------------------------------------------
function ricalcolaProvvigioniPerContratto($IdContratto,$dataLotto=NULL)
{
	if ($dataLotto==NULL)
		$dataLotto = getScalar("SELECT MIN(datafin) FROM assegnazione WHERE datafin>=CURDATE()");
	trace("Ricalcolo provvigione per il contratto $IdContratto, dataLotto=$dataLotto",FALSE);
	$IdRegola = getScalar("SELECT p.IdRegolaProvvigione FROM assegnazione a"
		." JOIN provvigione p ON a.IdProvvigione=p.IdProvvigione WHERE a.IdContratto=$IdContratto"
	    ." AND a.datafin='".ISODate($dataLotto)."'");
	
	if ($IdRegola>0)
		return aggiornaProvvigioni(false,"IdRegola=$IdRegola");
	else
	{
		trace("Regola provvigione non determinata: ricalcolo non effettuato",FALSE);
		return FALSE;
	}
}

//---------------------------------------------------------------------------------------------
// aggiornaProvvigioni
// Effettua il ricalcolo delle provvigioni 
// Argomenti opzionali:
// - flag che indica se, al termine, deve cambiare lo stato delle provvigioni di periodi completati
// - condizione su v_provvigioni_ricalcolabili per decidere quali provvigioni ricalcolare
//---------------------------------------------------------------------------------------------
function aggiornaProvvigioni($changeStatus=TRUE,$condizione="TRUE")
{
	global $context;
	try
	{
		//-------------------------------------------------------------------------------------
		// Calcola incassi di idm e spese scorporati dai movimenti di incasso capitale
		// Att.ne: le righe con numrata=0 non vengono toccate 
		//-------------------------------------------------------------------------------------
		if ($condizione=="TRUE") // esegue aggiornamento preliminare solo se ricalcolo totale incondizionato
		{
			trace("Calcolo incassi di idm e spese scorporati dai movimenti di incasso capitale",FALSE);
			/*********************************************************************************************************
			 *  Correzione 1/11/2011: spese e interessi sono attribuibili per contratto, non per rata,
			 *  all'agenzia che ha in affido il contratto nella data in cui queste spese sono contabilizzate
			 *  sulla rata n.0. Quindi,  l'aggiornamento viene modificato attribuendo questi incassi 
			 *  a livello di Assegnazione (nuovi campi ImpInteressiMoraPagati e ImpSpeseRecuperoPagate)
			 *********************************************************************************************************/
			$sql = "UPDATE assegnazione SET ImpInteressiMoraPagati = (SELECT -SUM(Importo) FROM movimento m"
			      ." WHERE IdTipoMovimento IN (106,107) AND IdContratto=assegnazione.IdContratto "
			      ." AND m.DataCompetenza BETWEEN assegnazione.DataIni AND assegnazione.DataFin)";
			if (!execute($sql))
				return FALSE;

			$sql = "UPDATE assegnazione SET ImpSpeseRecuperoPagate = (SELECT -SUM(Importo) FROM movimento m"
			      ." WHERE IdTipoMovimento=111 AND IdContratto=assegnazione.IdContratto "
			      ." AND m.DataCompetenza BETWEEN assegnazione.DataIni AND assegnazione.DataFin)";
			if (!execute($sql))
				return FALSE;
		
			//---------------------------------------------------------------------------------------------------
			// Calcola il campo ImpIncassoImproprio in Insoluto e storia insoluto, per tener conto di 
			//            saldi rata che non sono dovuti a incassi veri e propri e non devono quindi essere
			//            riconosciuti come tali
			//---------------------------------------------------------------------------------------------------
			// Prima determina la lista di contratti che verranno corretti
			$ids = fetchValuesArray("SELECT IdContratto FROM v_incassi_su_insoluti_da_correggere"
			 	                   ." UNION SELECT IdContratto FROM v_incassi_su_storiainsoluti_da_correggere");
			if (is_array($ids)) { 	                   
				// Mette come incasso improprio qualsiasi incasso ingiustificato (niente pagamenti veri)
				if (!execute("UPDATE insoluto SET impIncassoImproprio=0")) // prima resetta
					return FALSE;	
				$sql = "update insoluto i
						Join v_incassi_su_insoluti_da_correggere v on i.idinsoluto=v.idinsoluto
						set i.impIncassoImproprio=Improprio";
				if (!execute($sql))
					return FALSE;	
				trace("Aggiornato il campo ImpIncassoImproprio su ".getAffectedRows()." righe di Insoluto",FALSE);			
					
				if (!execute("UPDATE storiainsoluto SET impIncassoImproprio=0")) // prima resetta
					return FALSE;	
				$sql = "UPDATE storiainsoluto i
						Join v_incassi_su_storiainsoluti_da_correggere v on i.idstoriainsoluto=v.idstoriainsoluto
						set i.impIncassoImproprio=Improprio";
				if (!execute($sql))
					return FALSE;	
				trace("Aggiornato il campo ImpIncassoImproprio su ".getAffectedRows()." righe di StoriaInsoluto",FALSE);	
				// A questo punto � necessario ricalcolare i campi derivati sui contratti toccati dalle query precedenti
				trace("Ricalcolo campi derivati per le pratiche toccate dalle operazioni precedenti",FALSE);	
				foreach ($ids as $IdContratto) {
					aggiornaCampiDerivati($IdContratto);
				}
			}
		}
		
		//-------------------------------------------------------------------------------------
		// Determina la lista di agenzie,"lotti" (=date fine affido) e regole provvigionali
		// di cui ricalcolare le provvigioni. Sono tutte le combinazioni agenzia-lotto-regola
		// escluse quelle per le quali la provvigione e' gia' "congelata" (stato=2)
		//-------------------------------------------------------------------------------------
		$casi = getFetchArray("SELECT * FROM v_provvigioni_ricalcolabili WHERE $condizione");
		if (!is_array($casi)) {
 			trace("la select non ha restituito un array",FALSE);
			return FALSE;
		}
		
		// 22/2/2013: se le provv. richieste non ci sono piu' provvede a cancellare i residui
		if (count($casi)==0) // l'istruzione e' ok ma non ci sono casi: accade solo se la condizione specificata non porta a nulla
		{
 			trace("Nessuna provvigione ricalcolabile",FALSE);
			// Provvede a cancellare le righe che non corrispondono piu' ad alcuna assegnazione
			$IdsProvvigioni = fetchValuesArray("select IdProvvigione from provvigione where
					(tipocalcolo!='M' AND datafin not in (select datafin from assegnazione a where a.idagenzia=provvigione.idreparto)
					OR tipocalcolo='M' AND datafin not in (select LAST_DAY(datafin) from assegnazione a where a.idagenzia=provvigione.idreparto))");
   			if  (cancellaProvvigioni($IdsProvvigioni))
 			{
 				trace("Lista provvigioni cancellate perche' orfane: ".join(",",$IdsProvvigioni),FALSE);
 				return TRUE;
 			}
 			else
  				return FALSE;
 		}
			
		//-------------------------------------------------------------------------------------
		// Loop sull'array dei casi distinti di agenzia, lotto e regola
		//-------------------------------------------------------------------------------------
		trace("Ricalcolo di provvigioni per ".count($casi)." terne agenzia-lotto-regola",FALSE);
		foreach ($casi as $caso)
		{	
			if ($condizione=="TRUE") controllaStopForzato(); // interrompe se messo FlagSospeso='X'
			
			$IdAgenzia = $caso["IdAgenzia"];
			$IdRegolaProvv  = $caso["IdRegola"];
			$DataLotto = ISODate($caso["DataFineAffido"]);
			$tipoCalcolo = $caso["TipoCalcolo"]; // N/C/c/M/R
			trace("Ricalcolo provvigione tipo $tipoCalcolo per agenzia $IdAgenzia, regola=$IdRegolaProvv, Lotto=".$caso["Lotto"].", tipoCalcolo=$tipoCalcolo",false);
			
			$regola = getRow("SELECT * FROM regolaprovvigione WHERE IdRegolaProvvigione=$IdRegolaProvv");
			if (!is_array($regola))
				return FALSE;
			
			beginTrans(); // il ricalcolo e' transazionale: se qualcosa va male, storna il singolo ricalcolo
			//---------------------------------------------------------------------------------------------------
			// 7/11/2012: calcola il campo ImpIncassoImproprio in StoriaInsoluto, per tener conto di 
			//            saldi rata che non sono dovuti a incassi veri e propri e non devono quindi essere
			//            riconosciuti come tali
			// 19/4/13: inclusa anche la rata 0 e considerato anche l'abbuono passivo in modo specifico
			//   (perche' puo' mischiarsi a pagamenti veri e quindi non essere rilevato dal resto della query)
			// 26/4/2013: spostato prima del loop (una sola update globale)
			//---------------------------------------------------------------------------------------------------
			/*
			$sql = "UPDATE storiainsoluto s join contratto c on c.idcontratto=s.idcontratto
					SET ImpIncassoImproprio=s.ImpPagato
					WHERE s.datafineaffido='$DataLotto' and s.idagenzia=$IdAgenzia
					AND s.imppagato>0 and codazione in ('RIE','POS') and s.impIncassoImproprio!=s.imppagato
					AND (not exists (select 1 from v_mov m where m.idcontratto=s.idcontratto and m.numrata=s.numrata
  					and (dataregistrazione BETWEEN s.dataInizioAffido-interval 3 day AND s.DataFineAffido OR dataregistrazione='2013-03-22' and s.datainizioaffido='2013-03-25')
					AND (categoriamovimento='P' and importo<0 or idtipomovimento IN (106,107,111,118)))
					OR exists (select 1 from movimento m where m.idcontratto=s.idcontratto and m.numrata=s.numrata
  					and (dataregistrazione BETWEEN s.dataInizioAffido-interval 3 day AND s.DataFineAffido OR dataregistrazione='2013-03-22' and s.datainizioaffido='2013-03-25')
					AND idtipomovimento (300,347) and importo=s.ImpPagato))";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}	
			trace("Aggiornato il campo ImpIncassoImproprio su ".getAffectedRows()." righe di StoriaInsoluto",FALSE);			
			*/
			//-------------------------------------------------------------------------------------
			// Cancella la riga di Provvigione preesistente, il legame in Assegnazione
			// e le righe di DettaglioProvvigione e ModifcaProvvigione
			//-------------------------------------------------------------------------------------
			switch ($tipoCalcolo)
			{
				case 'N': // provvigioni esattoriali normali per lotto
				case 'M': // provvigioni STR/LEG per lotto arrotondato al mese
				case 'R': // provvigioni rinegoziazioni calcolate a fine mese
					$IdsProvvigioni = fetchValuesArray("SELECT IdProvvigione FROM provvigione WHERE IdRegolaProvvigione=$IdRegolaProvv"
			    	  ." AND IdReparto=$IdAgenzia AND DataFin='$DataLotto' AND TipoCalcolo='$tipoCalcolo'");
			        break;
			    case 'X': // provvigioni STR/LEG con chiusura periodica mensile (visibilit� limitata alle agenzie)
			    case 'C': // provvigioni STR/LEG con chiusura periodica mensile
					$IdsProvvigioni = fetchValuesArray("SELECT IdProvvigione FROM provvigione WHERE IdRegolaProvvigione=$IdRegolaProvv"
			      	." AND IdReparto=$IdAgenzia AND (DataFin='$DataLotto' OR DataFin>=CURDATE() AND CURDATE()<='$DataLotto')"
			      	." AND TipoCalcolo='$tipoCalcolo'");
					break;
			}

			if (!cancellaProvvigioni($IdsProvvigioni)) // elimina riferimenti e righe delle provvigioni interessate
			{
				rollback();
				return FALSE;
			}

			//-------------------------------------------------------------------------------------
			// Costruisce la query sui contratti
			//-------------------------------------------------------------------------------------
			switch ($tipoCalcolo)
			{
				case 'N': // provvigioni esattoriali normali per lotto
//7/10/13	    	$condImporti = "DataFineAffido='$DataLotto' AND IdAgenzia=$IdAgenzia";
			    	$condImporti = "DataFineAffido='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
			    	$condVeloce  = "DataFin='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
			    	$view = "v_importi_per_provvigioni_full";
			    	break;
			    case 'M': // provvigioni STR/LEG per lotto arrotondato al mese
//7/10/13			    	$condImporti = "DataFineAffido='$DataLotto' AND IdAgenzia=$IdAgenzia";
			    	$condImporti = "DataFineAffido='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
			    	$condVeloce  = "LAST_DAY(DataFineAffidoContratto)='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
			    	$view = "v_importi_per_provvigioni_special";
			    	break;
				case 'C': // provvigioni STR/LEG con chiusura mensile
//7/10/13					$condImporti = "(DataFineAffido='$DataLotto' OR DataFineAffido>=CURDATE() AND CURDATE()<='$DataLotto') AND IdAgenzia=$IdAgenzia";
					$condImporti = "(DataFineAffido='$DataLotto' OR DataFineAffido>=CURDATE() AND CURDATE()<='$DataLotto') AND IdRegolaProvvigione=$IdRegolaProvv";
					$condVeloce  = "(DataFin='$DataLotto' OR DataFin>=CURDATE() AND CURDATE()<='$DataLotto') AND IdRegolaProvvigione=$IdRegolaProvv";
					$view = "v_importi_per_provvigioni_full";
					break;
				case 'X': // provvigioni STR/LEG con chiusura mensile e visibilit� limitata per le agenzie
					$dataMassima = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
					if ($dataMassima=="") $dataMassima = '9999-12-31';
					$condImporti = "DataInizioAffidoContratto<='$dataMassima' AND (DataFineAffido='$DataLotto' OR DataFineAffido>=CURDATE() AND CURDATE()<='$DataLotto') AND IdRegolaProvvigione=$IdRegolaProvv";
					$condVeloce  = "DataInizioAffidoContratto<='$dataMassima' AND (DataFineAffidoContratto='$DataLotto' OR DataFineAffidoContratto>=CURDATE() AND CURDATE()<='$DataLotto') AND IdRegolaProvvigione=$IdRegolaProvv";
					$view = "v_importi_per_provvigioni_full";
					break;
				case 'R': // provvigioni rinegoziazione con chiusura fine mese di competenza
//7/10/13					$condImporti = "DataFineAffido BETWEEN ('$DataLotto'+interval 1 day-interval 1 month) AND '$DataLotto' AND IdAgenzia=$IdAgenzia";
					$condImporti = "LAST_DAY(DataFineAffido)='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
					$condVeloce = "LAST_DAY(DataFin)='$DataLotto' AND IdRegolaProvvigione=$IdRegolaProvv";
					$view = "v_importi_per_provvigioni_full";
					break;
			}
			// per velocizzare (dato che la query principale � lenta), determina prima se esistono assegnazioni 
			if (!rowExistsInTable("assegnazione",$condVeloce)) {
				commit();
				trace("Nessuna assegnazione per questa regola, tipo e periodo (where: $condVeloce)",false);
				continue; // passa alla regola successiva
			}
				
			//-------------------------------------------------------------------------------------
			// Inserisce la riga di Provvigione, vuota
			//-------------------------------------------------------------------------------------
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdReparto",$IdAgenzia,"N");	 	
			addInsClause($colList,$valList,"DataFin",$DataLotto,"S");
			addInsClause($colList,$valList,"LastUser",getUserName(),"S");
			addInsClause($colList,$valList,"IdRegolaProvvigione",$IdRegolaProvv,"N");
			addInsClause($colList,$valList,"TipoCalcolo",$tipoCalcolo,"S");
			$stato = ($DataLotto>=ISODate(time()))?"0":"1"; // stato 0=incompleto, 1=completo (2=consolidato)	 	
			addInsClause($colList,$valList,"StatoProvvigione",$stato,"S");	 	
			
			//trace("INSERT INTO provvigione ($colList) VALUES ($valList)",FALSE);
			if (!execute("INSERT INTO provvigione ($colList) VALUES ($valList)"))
			{
				rollback();
				return FALSE;
			}
			$IdProvvigione = getInsertId(); // ID della nuova riga appena inserita
			trace("Inserita riga con id=$IdProvvigione",FALSE);
			//-------------------------------------------------------------------------------------
			// Azzera i contatori 
			//-------------------------------------------------------------------------------------
			$numAffidati = 0;
			$numIncassati = 0;
			$impCapitaleIncassato = 0;
			$impCapitaleRealeIncassato = 0;
			$impInteressiDiMora = 0;
			$impSpeseRecupero = 0;
			$impCapitaleAffidato = 0;
			$impAltroAffidato = 0;
			$minDataIni = '9999-12-31';
			$numViagg = 0; // numero contratti con rate viaggianti incassate
			
			//-------------------------------------------------------------------------------------
			// Loop su tutte le pratiche interessate; ogni pratica di una coppia agenzia-lotto
			// viene esaminata tante volte quante regole distinte esistono, perche' l'applicabilita'
			// della regola non si puo' testare direttamente con la query 
			//-------------------------------------------------------------------------------------
			$dati = getFetchArray($query="SELECT * FROM $view WHERE $condImporti ORDER BY IdContratto");
			if (!is_array($dati))
			{
				rollback();
				return FALSE;
			}	
			trace("Analisi di ".count($dati)." contratti con IdAgenzia=$IdAgenzia e DataFineAffido=$DataLotto, query=$query",FALSE);
			$numRiconosciuti = 0;
			$lastId = 0;
			foreach ($dati as $riga)
			{
				//-------------------------------------------------------------------------------------
				// Determina quale regola di calcolo provvigione e' applicabile a questo contratto
				//-------------------------------------------------------------------------------------				
				$IdContratto = $riga["IdContratto"];
	//7/10/13			$IdRegola = trovaProvvigioneApplicabile($IdContratto,$IdAgenzia,$CodProvv,$riga["DataFineAffido"]);
				$IdRegola=$IdRegolaProvv; //$IdRegola==$IdRegolaProvv
	//			trace("IdContratto=$IdContratto, IdRegola=$IdRegola, IdRegolaProvv=$IdRegolaProvv",FALSE);
				if ($IdRegola==$IdRegolaProvv) // e' proprio quella corrente 
				{
					if ($regola["FlagPerPratica"]=='Y') // calcolo provvigioni per pratica singola (Nicol Rinegoziazione)
					{
						// capita (in particolare su Nicol / RINE) che una pratica sia affidata due volte nello stesso mese
						// se quindi il codice contratto risulta uguale al precedente elaborato, lo ignora. Questo � necessario
						// solo in questo tipo di regole, in cui la provvigione dipende dal numero di pratiche e dallo
						// stato in cui si trovano
						if ($IdContratto==$lastId) {
							continue;
						}
						$lastId = $IdContratto;
					}
						
					$numAffidati++;
					$impCapitaleAffidato += $riga["ImpCapitaleAffidato"]; // capitale da recuperare
					$impAltroAffidato += $riga["ImpTotaleAffidato"]-$riga["ImpCapitaleAffidato"];  // altri addebiti da recuperare
					if ($riga["ImpPagato"]>0)
					{
						$numIncassati++;
						// dal 1/2/2012 invertiti i due termini capitale reale e capitale
						// il capitale reale incassato tiene conto al massimo dell'importo rata
						$impCapitaleRealeIncassato += $riga["ImpPagato"]>$riga["ImpCapitaleAffidato"]?$riga["ImpCapitaleAffidato"]:$riga["ImpPagato"];
						// il capitale  incassato tiene conto di tutto quello che e' stato incassato, tranne spese e interessi
						$impCapitaleIncassato += $riga["ImpPagato"];
					}
						
					if ($riga["ImpInteressi"]>0) // i.d.m. pagati
						$impInteressiDiMora += $riga["ImpInteressi"];
					if ($riga["ImpSpese"]) // spese pagate
						$impSpeseRecupero += $riga["ImpSpese"];
					if ($minDataIni>ISODate($riga["DataInizioAffido"]))
						$minDataIni = ISODate($riga["DataInizioAffido"]);
					if ($riga["RateViaggiantiIncassate"]>0)
						$numViagg++; // indica il numero di pratiche con rate viaggianti, non il num. totale di rate viaggianti
						
					//-------------------------------------------------------------------------------------
					// Indica quale riga di Provvigione � applicata, nella riga di Assegnazione
					// interessata
					//-------------------------------------------------------------------------------------				
					switch ($tipoCalcolo)
					{
						case 'N': // provvigioni esattoriali normali per lotto
							$ret = execute("UPDATE assegnazione SET IdProvvigione=$IdProvvigione"
		        			 ." WHERE IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND DataFin='$DataLotto'");
						  	break;
			    		case 'M': // Non indica le righe di tipo M in "assegnazione"
			    			$ret = true;
					    	break;
			    		case 'X': // Non indica le righe di tipo X in "assegnazione"
			    			$ret = true;
					    	break;
					    case 'C': // provvigioni STR/LEG con chiusura mensile
							$ret = execute("UPDATE assegnazione SET IdProvvigione=$IdProvvigione"
		        		 		." WHERE IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND (DataFin='$DataLotto' OR DataFin>=CURDATE() AND CURDATE()<='$DataLotto')");						;
							break;
						case 'R': // provvigioni rinegoziazioni calcolate a fine mese di competenza
							$ret = execute("UPDATE assegnazione SET IdProvvigione=$IdProvvigione"
		        		 		." WHERE IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND LAST_DAY(DataFin)='$DataLotto'");						;
							break;
					}			
					if (!$ret)
					{
						rollback();
						return FALSE;
					}
					
					//-------------------------------------------------------------------------------------
					// Crea oppure aggiorna la riga di DettaglioProvvigione
					//-------------------------------------------------------------------------------------		
					$colList = "";
					$valList = "";
					addInsClause($colList,$valList,"IdProvvigione",$IdProvvigione,"N");	 	
					addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
					addInsClause($colList,$valList,"IdAgenzia",$IdAgenzia,"N");
					addInsClause($colList,$valList,"IdAgente",$riga["IdAgente"],"N");
					addInsClause($colList,$valList,"ImpCapitaleAffidato",$riga["ImpCapitaleAffidato"],"N");
					addInsClause($colList,$valList,"ImpTotaleAffidato",$riga["ImpTotaleAffidato"],"N");
					addInsClause($colList,$valList,"ImpPagato",$riga["ImpPagato"],"N");
					addInsClause($colList,$valList,"ImpPagatoTotale",$riga["ImpPagatoTotale"],"N");
					addInsClause($colList,$valList,"ImpInteressi",$riga["ImpInteressi"],"N");
					addInsClause($colList,$valList,"ImpSpese",$riga["ImpSpese"],"N");
					addInsClause($colList,$valList,"NumRateAffidate",$riga["NumRate"],"N");
					addInsClause($colList,$valList,"NumRateViaggianti",$riga["RateViaggiantiIncassate"],"N");
					addInsClause($colList,$valList,"DataInizioAffido",$riga["DataInizioAffido"],"D");
					addInsClause($colList,$valList,"DataFineAffido",$DataLotto,"S");
					addInsClause($colList,$valList,"DataInizioAffidoContratto",$riga["DataInizioAffidoContratto"],"D");
					addInsClause($colList,$valList,"DataFineAffidoContratto",$riga["DataFineAffidoContratto"],"D");
					addInsClause($colList,$valList,"TipoCalcolo",$tipoCalcolo,"S");
					addInsClause($colList,$valList,"LastUpd","NOW()","G");
					addInsClause($colList,$valList,"LastUser",getUserName(),"S");
                    // 2018-04-29 Spostata a dopo la INSERT, in modo da poter utilizzare quanto scritto 
                     // sulla riga di dettaglio provvigione
					if ($regola["FlagPerPratica"]=='Y') // calcolo provvigioni per pratica singola (Nicol Rinegoziazione)
					{   
					}
					else // provvigioni non calcolate a livello di contratto singolo, ma a livello di risultato globale
					{
						if ($riga["ImpPagato"]>0) 
							$numRiconosciuti++; // conta come contratto accreditato
					}
					if (!execute("REPLACE INTO dettaglioprovvigione ($colList) VALUES ($valList)"))
					{
						rollback();
						return FALSE;
					}
					
					if ($regola["FlagPerPratica"]=='Y') // calcolo provvigioni per pratica singola (Nicol Rinegoziazione)
  						$ImpProvvigioni = calcolaProvvigionePerPratica($regola,$IdProvvigione,$DataLotto);
						if ($ImpProvvigioni===NULL || $ImpProvvigioni===FALSE)
						{
							rollback();
							return FALSE;
						}
						if ($ImpProvvigioni>0) 
							$numRiconosciuti++; // conta come contratto accreditato
						addInsClause($colList,$valList,"ImpProvvigione",$ImpProvvigioni,"N");
                    }
                    
					//-------------------------------------------------------------------------------------
					// Ricollega le modifiche provvigioni che erano state definite per il lotto
					//-------------------------------------------------------------------------------------
					$sql = "UPDATE modificaprovvigione SET IdProvvigione=$IdProvvigione WHERE IdContratto=$IdContratto AND DataFineAffido='$DataLotto'";
					if (!execute($sql))
					{
						rollback();
						return FALSE;
					}					
					//-------------------------------------------------------------------------------------
					// Corregge i dati accumulati con le modifiche registrate in ModificaProvvigione 
					//-------------------------------------------------------------------------------------
					$rowMod = getRow("SELECT * FROM v_sintesi_modificaprovvigione WHERE IdContratto=$IdContratto AND DataFineAffido='$DataLotto'");
					if (is_array($rowMod))
					{
						if ($riga["NumRate"]>0 && $riga["NumRate"]<=$rowMod["NumRateCancellate"])
							$numAffidati--;
						if ($riga["ImpPagato"]>0 && $riga["ImpPagato"] <= -$rowMod["DiffPagato"]) // imp.pagato completamente annullato
							$numIncassati--;
						if ($riga["RateViaggiantiIncassate"]>0 && $riga["RateViaggiantiIncassate"]<=-$rowMod["DiffRataViaggiante"])
							$numViagg--;
						else if ($riga["RateViaggiantiIncassate"]==0 && $rowMod["DiffRataViaggiante"]>0)
							$numViagg++;
						
						$impCapitaleAffidato  += $rowMod["DiffCapitaleAffidato"];
						$impAltroAffidato 	  += $rowMod["DiffTotaleAffidato"]-$rowMod["DiffCapitaleAffidato"];
						$impCapitaleIncassato += $rowMod["DiffPagato"];
						if ($riga["ImpPagato"]>$riga["ImpCapitaleAffidato"])
							$impCapitaleRealeIncassato += $rowMod["DiffCapitaleAffidato"];
						else
							$impCapitaleRealeIncassato += $rowMod["DiffPagato"];
						$impInteressiDiMora += $rowMod["DiffInteressi"];
						$impSpeseRecupero   += $rowMod["DiffSpeseRecupero"];
					}
				}       // fine if regola match
			}			// fine foreach sui contratti

			if ($numAffidati>0)
			{
				// Causa duplicati nelle REPLACE precedenti deve ricalcolare il numero esatto di pratiche affidate
				$numAffidati = getScalar("SELECT COUNT(*) FROM dettaglioprovvigione WHERE IdProvvigione=$IdProvvigione");
			
				//-------------------------------------------------------------------------------------
				// Esegue i calcoli determinati dalla regola di provvigione 
				// applicata e aggiorna la riga di Provvigione
				//-------------------------------------------------------------------------------------
				$setClause = "";
				addSetClause($setClause,"NumAffidati",$numAffidati,"N");
				addSetClause($setClause,"NumIncassati",$numIncassati,"N");
				addSetClause($setClause,"NumRiconosciuti",$numRiconosciuti,"N");
				addSetClause($setClause,"NumViaggianti",$numViagg,"N");
				addSetClause($setClause,"ImpCapitaleIncassato",$impCapitaleIncassato,"N");
				addSetClause($setClause,"ImpCapitaleRealeIncassato",$impCapitaleRealeIncassato,"N");
				addSetClause($setClause,"ImpInteressiDiMora",$impInteressiDiMora,"N");
				addSetClause($setClause,"ImpSpeseRecupero",$impSpeseRecupero,"N");
				addSetClause($setClause,"ImpCapitaleAffidato",$impCapitaleAffidato,"N");
				addSetClause($setClause,"ImpAltroAffidato",$impAltroAffidato,"N");
				addSetClause($setClause,"DataIni",$numAffidati>0?"'$minDataIni'":"NULL","G");
		    
				trace("UPDATE provvigione $setClause WHERE IdProvvigione=$IdProvvigione",FALSE);
				if (!execute("UPDATE provvigione $setClause WHERE IdProvvigione=$IdProvvigione"))		
				{
					rollback();
					return FALSE;
				}	
				
				
				//-------------------------------------------------------------------------------------
				// Caso 0 (nuovo): provvigioni calcolate per pratica; il risultato e' dato dalla
				// somma dei campi ImpProvvigione del dettaglioProvvigione 
				//-------------------------------------------------------------------------------------
				if ($regola["FlagPerPratica"]=='Y') // calcolo provvigioni per pratica singola
				{
					$provv = getScalar("SELECT SUM(ImpProvvigione) FROM dettaglioprovvigione WHERE IdProvvigione=$IdProvvigione AND ImpProvvigione IS NOT NULL");
					trace("Calcolata somma provvigioni per pratica = $provv",FALSE);
					$soglia = NULL;
					$bonus = NULL;
                    // in questo caso, il campo ImpProvvigione (di dettaglioprovvigione) è stato già impostato su ciascuna pratica 
				}
								
				//-------------------------------------------------------------------------------------
				// Primo caso: e' indicata una formula diretta 
				//-------------------------------------------------------------------------------------
				// Legge provvigione + campi calcolati
				else if (!($regola["FormulaFascia"]>"")) // non c'e' calcolo per fasce
				{
					if (!($regola["Formula"]>""))
					{
						trace("La regola provvigione con id=$IdProvvigione non ha ne' formula ne' fascia",FALSE,TRUE);
						$provv = 0;
					}
					else
					{	
						$provv = getScalar("SELECT ".$regola["Formula"]." FROM v_provvigione WHERE IdProvvigione=$IdProvvigione");
						trace("Applica formula diretta ".$regola["Formula"]." = $provv",FALSE);
					}
					$soglia = NULL;
					$bonus = NULL;
                    // in questo caso, il campo ImpProvvigione (di dettaglioprovvigione) è per ora nullo , ma può essere calcolato applicando
                    // la stessa formula alla speciale vista v_dettaglioprovvigione_transform (che mappa i campi del dettaglio su quelli
                    // usati nelle "Formule")
                    if (!execute("UPDATE dettaglioprovvigione dp"
                        . " JOIN v_dettaglioprovvigione_transform t ON t.IdProvvigione=dp.IdProvvigione AND t.IdContratto=dp.IdContratto"
                        . " SET dp.ImpProvvigione={$regola["Formula"]} WHERE dp.IdProvvigione=$IdProvvigione"))		
                    {
                        rollback();
                        return FALSE;
                    }
				}
				//-------------------------------------------------------------------------------------
				// Secondo caso: si applicano fasce di risultato (perché FormulaFascia è valorizzato)
				//-------------------------------------------------------------------------------------
				else
				{
					$sql = "SELECT ".$regola["FormulaFascia"]." FROM v_provvigione WHERE IdProvvigione=$IdProvvigione";
					$valore = getScalar($sql);
					if ($valore===NULL || $valore===FALSE)
					{
						trace("Valore per calcolo fascia (".$regola["FormulaFascia"].") non restituito dalla query: $sql",FALSE);
						rollback();
						return FALSE;
					}
					trace("Cerca fascia per valore $valore",FALSE);
					$fascia = getRow("SELECT * FROM fasciaprovvigione WHERE IdRegolaProvvigione=$IdRegolaProvv"
					            . " AND '$DataLotto' BETWEEN DataIni AND DataFin"
								. " AND $valore<=ValoreSoglia ORDER BY ValoreSoglia LIMIT 1");
					if (!is_array($fascia))
					{
						trace("Manca la definizione delle fasce provvigionali per la regola n. $IdRegolaProvv",FALSE);
						rollback();
						return FALSE;
					}
					// Calcola l'importo con una SELECT della formula dalla view
					$provv  = getScalar("SELECT ".$fascia["Formula"]." FROM v_provvigione WHERE IdProvvigione=$IdProvvigione");
					$soglia = $fascia["ValoreSoglia"];
					
					// Eventuale Bonus: se c'e' una formula anche nella riga principale
					if ($regola["Formula"]>"")
					{
						$bonus = getScalar("SELECT ".$regola["Formula"]." FROM v_provvigione WHERE IdProvvigione=$IdProvvigione");
					}
					else
						$bonus = NULL;
					
					trace("Applica formula fascia ".$fascia["Formula"]." = $provv, bonus=$bonus",FALSE);
                    
                    // in questo caso, il campo ImpProvvigione (di dettaglioprovvigione) è per ora nullo , ma può essere calcolato applicando
                    // la stessa formula selezionata (per fascia) alla speciale vista v_dettaglioprovvigione_transform (che mappa i campi del dettaglio su quelli
                    // usati nelle "Formule"). Inoltre può essere aggiunto anche il bonus, a meno che non sia una semplice costante
                    if ($regola["Formula"]>'' && !preg_match('/^[0-9\.]+$/',$regola["Formula"])) {
                        $addition = "+".$regola["Formula"];
                    } else {
                        $addition = "";
                    }
                    if (!execute("UPDATE dettaglioprovvigione dp"
                        . " JOIN v_dettaglioprovvigione_transform t ON t.IdProvvigione=dp.IdProvvigione AND t.IdContratto=dp.IdContratto"
                        . " SET dp.ImpProvvigione={$fascia["Formula"]}$addition WHERE dp.IdProvvigione=$IdProvvigione"))		
                    {
                        rollback();
                        return FALSE;
                    }

				}

				//-------------------------------------------------------------------------------------
				// Aggiornamento finale della riga di Provvigione
				//-------------------------------------------------------------------------------------
				$setClause = "";
				addSetClause($setClause,"ImpProvvigione",$provv,"N");
				addSetClause($setClause,"ValoreSoglia",$soglia,"N");
				addSetClause($setClause,"ImpBonus",$bonus,"N");
				
				trace("UPDATE provvigione $setClause WHERE IdProvvigione=$IdProvvigione",FALSE);
				if (!execute("UPDATE provvigione $setClause WHERE IdProvvigione=$IdProvvigione"))		
				{
					rollback();
					return FALSE;
				}	
			}
			else // Nessuna pratica conteggiata: cancella la riga di provvigione
			{
				trace("DELETE FROM provvigione WHERE IdProvvigione=$IdProvvigione",FALSE);
				if (!execute("DELETE FROM provvigione WHERE IdProvvigione=$IdProvvigione"))		
				{
					rollback();
					return FALSE;
				}	
			}
			commit();
		}	// fine foreach ricalcolo caso agenzia-lotto-regola	
		//----------------------------------------------------------------------------
		// 6/9/2011: chiude le provvigioni per i lotti scaduti
		//----------------------------------------------------------------------------
		if ($changeStatus)
			execute("update provvigione set statoprovvigione=2 where datafin<CURDATE()");
		
		//----------------------------------------------------------------------------
		// 24/11/2011: elimina le provvigioni rimaste vuote (in quanto tali non sono
		//             state ricalcolate)
		//----------------------------------------------------------------------------
		execute("delete from provvigione where not exists (select 1 from dettaglioprovvigione where idprovvigione=provvigione.idprovvigione)
				 AND not exists (select 1 from assegnazione where idprovvigione=provvigione.idprovvigione)
				 AND NOT EXISTS (select 1 from ".MYSQL_SCHEMA."_storico.assegnazione where idprovvigione=provvigione.idprovvigione)");
		       
		       
		trace("Ricalcolo provvigioni completato",FALSE);
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		setLastSerror($e->getMessage());
		trace("Errore nel calcolo delle provvigioni: ".$e->getMessage());
	}		
}
//------------------------------------------------------------------------------------------------------------
// cancellaProvvigioni
// Elimina le provvigioni con ID dati nell'array passato come argomento
//------------------------------------------------------------------------------------------------------------
function cancellaProvvigioni($IdsProvvigioni)
{
	if (count($IdsProvvigioni)>0)
  	{
  		$IdsProvvigioni = join(",",$IdsProvvigioni);
		$sql = "UPDATE assegnazione SET IdProvvigione=NULL WHERE IdProvvigione IN ($IdsProvvigioni)";
		if (execute($sql))
		{
			// stacca le modifiche provvigioni, poi le riattacchera usando la data lotto
			$sql = "UPDATE modificaprovvigione SET IdProvvigione=NULL WHERE IdProvvigione IN ($IdsProvvigioni)";
			if (execute($sql))
			{   // cancella altre dipendenze
				$sql = "DELETE FROM dettaglioprovvigione WHERE IdProvvigione IN ($IdsProvvigioni)";
				if (execute($sql))
				{
					$sql = "DELETE FROM provvigione WHERE IdProvvigione IN ($IdsProvvigioni)";
					if (!execute($sql))
					{
						return FALSE;
					}
				}
			}					
		}				
  	}
  	return TRUE;
}
//----------------------------------------------------------------------------------------------------
// calcolaProvvigionePerPratica
// Nel caso di regola provvigionale da calcolare pratica per pratica applica la formula semplice o
// a fasce e restituisce l'importo calcolato
//----------------------------------------------------------------------------------------------------
function calcolaProvvigionePerPratica($regola,$IdProvvigione,$DataLotto)
{
	//-------------------------------------------------------------------------------------
	// Primo caso: indicata una formula diretta 
	//-------------------------------------------------------------------------------------
	// Legge provvigione + campi calcolati
	if (!($regola["FormulaFascia"]>"")) // non c'e' calcolo per fasce
	{
		if (!($regola["Formula"]>""))
		{
			trace("La regola provvigione con id=$IdProvvigione non ha ne' formula ne' fascia",FALSE,TRUE);
			$provv = 0;
		}
		else
		{	
			$provv = getScalar("SELECT ".$regola["Formula"]." FROM v_contratto_per_provvigione WHERE IdProvvigione=$IdProvvigione");
			trace("Applica formula diretta ".$regola["Formula"]." = $provv, contratto = $IdContratto",FALSE);
		}
	}
	//-------------------------------------------------------------------------------------
	// Secondo caso: niente formula, si applicano fasce di risultato 
	//-------------------------------------------------------------------------------------
	else
	{
		$sql = "SELECT ".$regola["FormulaFascia"]." FROM v_contratto_per_provvigione WHERE IdProvvigione=$IdProvvigione";
		$valore = getScalar($sql);
		if ($valore===FALSE)
			return FALSE;
			
		if ($valore==NULL) // non c'e' (ad es. nuovoTasso non determinabile, perche' la rinegoziazione non e' chiusa)
		{
			$provv = 0;		
		}
		else
		{
			$fascia = getRow("SELECT * FROM fasciaprovvigione WHERE IdRegolaProvvigione=".$regola["IdRegolaProvvigione"]
	     	               . " AND '$DataLotto' BETWEEN DataIni AND DataFin"
						   . " AND $valore<=ValoreSoglia ORDER BY ValoreSoglia LIMIT 1");
			if (!is_array($fascia))
			{
				trace("Manca la definizione delle fascie provvigionali per la regola n. ".$regola["IdRegolaProvvigione"],FALSE);
				return FALSE;
			}
			// Calcola l'importo con una SELECT della formula dalla view
			$provv  = getScalar("SELECT ".$fascia["Formula"]." FROM v_contratto_per_provvigione WHERE IdProvvigione=$IdProvvigione");
		}
	}
	return $provv;
}

//----------------------------------------------------------------------------------------------------
// affidaTutti
// Esegue l'affidamento dei contratti di cui � stata appena modificata la classe. 
// Viene fatto in massa dopo aver classificato tutti i contratti, per gestire
// correttamente le flotte e l'affido congiunto di pratiche in base alla classe peggiore
//----------------------------------------------------------------------------------------------------
function affidaTutti($arrayIdClienti)
{
	// se non c'� lista clienti (postprocessing non preceduto da altre fasi) la crea con i contratti in attesa di affido
	if (count($arrayIdClienti)==0)
	{
//		$sql = "select DISTINCT IdCliente from contratto where idstatorecupero=2 and idclasse in "
//		      ."(select idclasse from regolaassegnazione where curdate() between dataini and datafin)";
		// Esamina quelli in stato ATT (attesa affido pre-DBT) e ATS (attesa affido STR/LEG)
		$sql = "select DISTINCT IdCliente from contratto where idstatorecupero=25 or (idstatorecupero=2 and idclasse in "
		      ."(select idclasse from classificazione where curdate() between dataini and datafin and IFNULL(flagnoaffido,'N')='N'))";
	}
	else // altrimenti aggiunge quelli in attesa all'array gi� fornito (in cui peraltro dovrebbero essere tutti in attesa)
	{
		$ids = join(",",$arrayIdClienti);
		$sql = "select DISTINCT IdCliente from contratto where (idstatorecupero=2 and idclasse in "
		      ."(select idclasse from classificazione where curdate() between dataini and datafin and IFNULL(flagnoaffido,'N')='N'))"
		      ." OR idstatorecupero=25 OR IdCliente IN (0$ids)";
	}
	$arrayIdClienti = fetchValuesArray($sql);
	$ids = join(",",$arrayIdClienti);
	writeProcessLog(PROCESS_NAME,"L'elaborazione riguarda i contratti di ".count($arrayIdClienti)." clienti");
   	
	// Seleziona i contratti da affidare, in ordine di cliente e gravit� discendente
    // 25-11-2011: esclude le pratiche con una sola rata insoluta che deve ancora scadere
    // 1/8/2012: corregge criterio per le pratiche str/leg
   	$sql = "SELECT IdContratto FROM contratto c JOIN classificazione cl ON cl.IdClasse=c.IdClasse
	       WHERE IdAgenzia IS NULL AND IdCliente IN (0$ids)
	       AND (idstatorecupero=25 OR (IFNULL(FlagNoAffido,'N')='N' AND IFNULL(cl.FlagRecupero,'N')='Y')
		                               AND NOT (c.DataRata>CURDATE() AND c.NumInsoluti<=1)
		       )
	       ORDER BY IdCliente,cl.Ordine DESC";
	
	$contratti = fetchValuesArray($sql);
	foreach ($contratti AS $IdContratto)
	{
		controllaStopForzato(); // interrompe se messo FlagSospeso='X'
		
		$IdAgenzia = delegate($IdContratto); // affidamento ad agenzia
		trace("delegate Contratto=$IdContratto IdAgenzia=$IdAgenzia",FALSE);
		if ($IdAgenzia===FALSE) // affidamento ad agenzia
		{
			trace("Delegation fallita idContratto=$IdContratto");
			return FALSE;
		}
		if ($IdAgenzia>0) // effettivamente affidato: assegna agli operatori di agenzia
		{
			if (assignAgent($IdContratto)===FALSE) // assegnazione automatica ad operatore esterno (persona dell'agenzia)
			{
				trace("assegnaAgente fallita idContratto=$IdContratto");
				return FALSE;
			}
			// 30/9/2016: spostato nella funzione affidaAgenzia, visto che la produzione di SMS lettere (ad es.) deve
			// essere fatta anche se si affida manualmente
			//eseguiAutomatismiPerAzione('AFF',$IdContratto); // esegue invio SMS differito o prep. lettere
		}
		
		$IdUtente = assign($IdContratto); // assegnazione ad operatore (viene dopo perche' puo' dipendere dall'affido)
		if ($IdUtente===FALSE) 
		{
			trace("Assign fallita idContratto=$IdContratto");
			return FALSE;
		}
		else if ($IdUtente==0)
		{
			trace("Contratto non assegnabile IdContratto=$IdContratto",FALSE);
			//			writeRecordError($idImportLog,"E","Contratto non assegnabile IdContratto=$IdContratto",$codcli);
		}
		// Se non affidato ad agenzia, mette lo stato "in attesa di affido"
		if (!metteInAttesa($IdContratto))
			return FALSE;
		
		// Ri-aggiorna campi calcolati nel contratto, se non affidata (altrimenti li ha gi� ricalcolati l'affido)
		if (!($IdAgenzia>0)) {
			if (!aggiornaCampiDerivati($IdContratto,0))
			{
				trace("aggiornaCampiDerivati fallita idContratto=$IdContratto");
				return FALSE;   
			}
		}
	}
	// Registra su log l'avvenuta esecuzione dell'affido
	writeLog("AFFIDO","engineFunc.php","Esecuzione affidamenti automatici","affidaTutti");
	return TRUE;
}

//************************************************************************
//* aggiornaFlagBloccoAffido
//* Aggiorna il flag di bloccoAffido nel contratto
//************************************************************************
function aggiornaFlagBloccoAffido($IdContratto,$flagBloccoAffido) {
	if (!($IdContratto>0)) return; // pu� essere un contratto non registrato a causa dell'errore
	$row = getRow("SELECT CodContratto,IF(FlagBloccoAffido>'',FlagBloccoAffido,'N') AS FlagBloccoAffido FROM contratto WHERE IdContratto=$IdContratto");
	$currFlag 		= $row["FlagBloccoAffido"];
	$CodContratto   = $row["CodContratto"];
	
	$newFlag = '';
	switch ($flagBloccoAffido) { 
		// nota: se currFlag=U (pratica messa esplicitamente in attesa di affido, blocchi non operativi), 
		// nessuna delle IF seguenti viene eseguita quindi si ha l'effetto di non mettere in blocco
		case 'N': 	 // segnalazione di nessun errore su contratto
		 	if ($currFlag=='C') { 	  // aveva solo quelli
				$newFlag = 'N';
		 	} else if ($currFlag=='B') { 	// aveva errori sia sul contratto sia sui movimenti
				$newFlag = 'M'; // restano (per ora) quelli sui movimenti
		 	}
	 		break;
		case 'm': // segnalazione di nessun errore su movimenti
		 	if ($currFlag=='M') { 	// aveva solo quelli
				$newFlag = 'N';
		 	} else if ($currFlag=='B') { 	// aveva errori sia sul contratto sia sui movimenti
				$newFlag = 'C'; // restano quelli sul contratto
		 	}
			break;
		case 'C': // segnalazione di errore su contratti
		 	if ($currFlag=='N') { 	// era senza errori
				$newFlag = 'C';
		 	} else if ($currFlag=='M') { 	// aveva errori sui movimenti
				$newFlag = 'B'; 			// adesso ce li ha su entrambi
		 	}
			break;
		case 'M': // segnalazione di errore su movimenti
		 	if ($currFlag=='N') { 	// era senza errori
				$newFlag = 'M';
		 	} else if ($currFlag=='C') { 	// aveva errori sul contratto
				$newFlag = 'B'; 			// adesso ce li ha su entrambi
		 	}
			break;
	}

	if ($newFlag=='') return; // niente da cambiare
	
	if (!execute("UPDATE contratto SET FlagBloccoAffido='$newFlag' WHERE IdContratto=$IdContratto")) 
		return; //	qualcosa non va, rinuncia (la traccia conterr� il messaggio SQL)
		
	if (getAffectedRows()==0) return; // nessun cambiamento: nessuna azione

	// se il valore � effettivamente cambiato da non-bloccato a bloccato, emette opportuna segnalazione	
	if 	($newFlag!='N' && $currFlag=='N') {
		writeProcessLog(PROCESS_NAME,"Pratica {$row["CodContratto"]} marcata con 'Blocco Affido' a causa di anomalie nell'acquisizione dei dati",2);
		writeHistory("NULL","Pratica marcata con 'Blocco Affido' a causa di anomalie nell'acquisizione dei dati",$IdContratto,"");		
	} // non emette messaggi al reset, perch� potrebbe essere smentito subito dopo (flag ok su contartti, KO su movimenti) 
}

//************************************************************************
//* resetBloccoAffido
//* Aggiorna il flag di bloccoAffido nel contratto mettendolo a U (sospeso)
//* per impedire che venga ricalcolato (in caso di "messa in attesa di affido"
//* volontaria) oppure a "N" (nel momento di un affido volontario)
//* contemporaneamente, toglie la categoria 10 (Blocco) 
//************************************************************************
function resetBloccoAffido($IdContratto,$flagBloccoAffido) {
	execute("UPDATE contratto SET FlagBloccoAffido='$flagBloccoAffido',IdCategoria=IF(IdCategoria=10,NULL,IdCategoria)"
			." WHERE IdContratto=$IdContratto");
}


/**
 * Controlla se � stato messo flagSospeso='X' per provocare lo stop immediato del batch
 */
function controllaStopForzato() {
	global $idImportLog;
	/* legge l'eventuale flag di stop messo nell'evento IMPORT */
	$stop = getScalar("SELECT FlagSospeso FROM eventosistema WHERE IdEvento=1");
	if ($stop=="X")
	{
		writeResult($idImportLog,"K","Elaborazione interrotta perch� FlagSospeso=X");
		sendDeferMail(); // invia messaggi accumulati
		die();
	}
}