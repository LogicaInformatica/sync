<?php
require_once("workflowFunc.php");
//==============================================================
//   FUNZIONI CUSTOMIZZABILI PER I MOTORI DI WORKFLOW
//   CLASSIFICAZIONE, AFFIDAMENTO ECC.
//==============================================================
//----------------------------------------------------------------
// Custom_Assignment
// Assegna un operatore ad una pratica secondo criteri custom
// Ritorna: FALSE se non pu� stabilire a chi assegnare e demanda
//               la cosa alle regole standard
//          0    se decide di non assegnare ad alcuno
//          IdUtente   se assegnato
//----------------------------------------------------------------
function Custom_Assignment($IdContratto)
{
	try
	{
		return FALSE; // demanda alla procedura standard
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}
//----------------------------------------------------------------
// Custom_AgentAssignment
// Assegna un operatore esterno ad una pratica secondo criteri custom
// Ritorna: FALSE se non pu� stabilire a chi assegnare e demanda
//               la cosa alle regole standard
//          0    se decide di non assegnare ad alcuno
//          IdUtente   se assegnato
//----------------------------------------------------------------
function Custom_AgentAssignment($IdContratto)
{
	try
	{
		return FALSE; // demanda alla procedura standard
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}

//----------------------------------------------------------------
// Custom_Delegation
// Affida una pratica ad un'agenzia secondo criteri custom
// Ritorna: FALSE se non pu� stabilire a chi assegnare e demanda
//               la cosa alle regole standard
//          0    se decide di non assegnare ad alcuno
//          IdAgenzia   se affidato
//----------------------------------------------------------------
function Custom_Delegation($IdContratto,&$msgForHistory,&$idRegolaProvvigione,$IdAgenziaPrec,&$durataProvv)
{
	try
	{
		$idRegolaProvvigione = "";
		$msgForHistory = "";
		// Nota i dati experian presi in considerazione per questa forzatura sono solo quelli del piu recente invio
		$dati = getRow("SELECT CodContratto,IdAgenzia,IdStatoRecupero,IdStatoContratto,IdProdotto,D4CScoreIndex"
				." FROM contratto c "
				." LEFT JOIN experian e ON e.IdCliente=c.IdCliente AND e.IdExperian=(SELECT MAX(IdExperian) FROM experian)"
				." WHERE c.IdContratto=$IdContratto");
		if (!$dati) return false;
		$codContratto = $dati["CodContratto"];
		$IdAgenzia    = $dati["IdAgenzia"];
		$IdStatoRecupero = $dati["IdStatoRecupero"];
		$IdStatoContratto = $dati["IdStatoContratto"];
		$IdProdotto      = $dati["IdProdotto"];
		
		// 6/6/2011: provvisorio; mette certi contratti in stato INT
		$subcode 	  = 0+substr($codContratto,2);
		$lista 		= file_get_contents(TMP_PATH."/forzature.txt");
		$lista 		= split("\n",$lista);
		if (in_array($subcode,$lista) && $IdAgenzia==NULL && $IdStatoRecupero!=13) // da escludere
		{
			trace("Contratto $codContratto non affidato a causa di forzatura una-tantum",FALSE);
			impostaStato("INT",$IdContratto); // imposta stato Lavorazione interna
			cambioCategoria($IdContratto,999); // categoria "da affidare a mano"
			$msgForHistory = "Contratto $codContratto non affidato a causa di forzatura una-tantum";		
			return 0; // indica no affido
		}		

		// 29/12/2015: se il contratto soddisfa il criterio indicato nel parametro CONDIZIONE_FLOTTA
		// mette in lavorazione interna con categoria "Flotte SOFT"
		$condizione = getSysParm("CONDIZIONE_FLOTTA","");
		if ($condizione>'' && $IdAgenzia==NULL) {
			if (rowExistsInTable("contratto c","IdContratto=$IdContratto AND $condizione")) {
				trace("Contratto $codContratto messo automaticamente in lavorazione interna Flotte SOFT",FALSE);
				impostaStato("INT",$IdContratto); // imposta stato Lavorazione interna
				cambioCategoria($IdContratto,1050); // categoria "flotte SOFT"
				$msgForHistory = "Contratto $codContratto messo automaticamente in lavorazione interna Flotte SOFT";
				return 0; // indica no affido
			}
		}
		
		//-----------------------------------------------------------------------------------
		// Non affidare i contratti che non sono in stato Attivo, a meno che non sia stata
		// fatta una forzatura in attesa di affido, recentemente
		// New: anche quelli in attesa di affido STR/LEG e di PAP/CES sono esclusi
		// dal 1/12/14 esclude anche la classe 90 (estinti con debito)
		//-----------------------------------------------------------------------------------
		if ($IdStatoContratto!=1 && $IdAgenzia==NULL && $IdStatoRecupero!=13 
		&& $IdStatoRecupero!=25 && $IdStatoRecupero!=27 && $IdClasse!=90)
		{
			if (!rowExistsInTable("storiarecupero","IdContratto=$IdContratto AND IdAzione=191 AND DataEvento>=CURDATE()-INTERVAL 10 DAY"))
			{		
				trace("Contratto $codContratto non affidato e passato in lavorazione interna perche' non e' in stato Attivo",FALSE);
				impostaStato("INT",$IdContratto); // imposta stato Lavorazione interna
				$msgForHistory = "Contratto $codContratto non affidato e passato in lavorazione interna perche' non e' in stato Attivo";		
				return 0; // indica no affido
			}
		}		

		//-----------------------------------------------------------------------------------
		// Se si tratta di un contratto di rifinanziamento e va a recupero, passa direttamente
		// a City 24
		//-----------------------------------------------------------------------------------
		/* portato nell regole su DB
		if ($IdProdotto==236 || $IdProdotto==165)
		{
			$sql = "SELECT * FROM regolaprovvigione WHERE CodRegolaProvvigione='24' AND CURDATE() BETWEEN DataIni AND DataFin";
			$dati = getRow($sql);
			if (is_array($dati))
			{
				$IdAgenzia = $dati["IdReparto"]; 
				$idRegolaProvvigione = $dati["IdRegolaProvvigione"];	 
				$durataProvv = $dati["durata"];
				trace("Affidamento forzato all'agenzia $IdAgenzia (cod=$idRegolaProvvigione) perche' recupero su rifinanziamento",FALSE);
				$msgForHistory = "Affidamento forzato a City (24) perch� contratto di rifinanziamento (prodotto PO o PR) andato a recupero";		
				return $IdAgenzia;
			}
		}
		*/

		//---------------------------------------------------------------------------------------
		// 15/7/2016: Se si tratta di un contratto classificato con Experian con Score Index 6-7
		// forza l'affidamento a City 24; se ha score index 8-10 forza a OSIRC 2A 
		//---------------------------------------------------------------------------------------
		$score = $dati["D4CScoreIndex"];
		$forzaExp = '';
		if (substr($codContratto,0,2)=='LO') {
			if ($score>=6 and $score<=7) {
				$forzaExp = '24';			
				$ag = "City 24";
			} else if ($score>=8 and $score<=10) {
				$forzaExp = '2A';			
				$ag = "OSIRC 2A";
			}
		} else if (substr($codContratto,0,2)=='LE') {
			if ($score>=6 and $score<=10) {
				$forzaExp = 'L2';
				$ag = "FIDES L2";
			}	
		}
		if ($forzaExp>'') {
			$rprow = getRow("SELECT IdRegolaProvvigione,durata,IdReparto"
					." FROM regolaprovvigione WHERE CodRegolaProvvigione='$forzaExp'"
					." AND CURDATE()+INTERVAL 1 MONTH -INTERVAL 1 DAY<DataFin");
			
			$idRegolaProvvigione = $rprow["IdRegolaProvvigione"];
			$durataProvv = $rprow["durata"];
			$IdAgenzia = $rprow["IdReparto"];
			$msgForHistory = "Affidamento forzato all'agenzia $ag a causa dello Score Index Experian = $score";
			trace($msgForHistory,FALSE);
			return $IdAgenzia;
		}
		
		//-----------------------------------------------------------------------------------
		// Gestione flotte (accorpamenti): se il cliente ha altri contratti gi� affidati, 
		// affida alla stessa
		// agenzia. Questo sfrutta il fatto che, nella "affidaTutti" le classificazioni 
		// vengono affidate in ordine di gravit� decrescente
		//-----------------------------------------------------------------------------------
		
		// 28/8/2012: prima controlla se il contratto � destinato alla gestione flotte vera e propria:
		// in tal caso, nnon fa l'accorpamento, che potrebbe portarlo su agenzia non appropriata
		$flotte = getScalar("SELECT COUNT(*) FROM v_cond_affidamento c WHERE IdContratto=$IdContratto AND ragionesociale>''  
			and 1<(select count(*) from contratto where ifnull(idclasse,1) not in (1,18) and idcliente=c.idcliente)
			and giorniritardo>30");
		
		if ($flotte>0) 		// ha gli estremi per la gestione flotte F1/F2, 
			return FALSE;	// trattamento ordinario
			
			
		$dati = getRow("SELECT c.IdClasse,c.IdCliente,c.IdAgenzia,c.ImpInsoluto,cl.Ordine,CodContratto
		                FROM contratto c LEFT JOIN classificazione cl ON cl.IdClasse=c.IdClasse 
		                WHERE c.IdContratto = $IdContratto");
		$IdAgenzia = $dati["IdAgenzia"];
		$IdClasse  = $dati["IdClasse"];
		$IdCliente = $dati["IdCliente"];
		$gravita   = $dati["Ordine"];
		if ($IdAgenzia>0)
		{
			trace("Contratto gi� affidato, l'affido non viene modificato",FALSE);
			return $IdAgenzia;
		}
		if (fuoriRecupero($IdContratto))
			return 0;		// non affidare

		if ($IdClasse<=2) // regola del 24/6/11: INS e TEK non vanno in gestione flotta
			return FALSE;	// segue regole normali
		if ($IdClasse==101 || $IdClasse==102) // nuovi INS e TEK non vanno in gestione flotta
			return FALSE;	// segue regole normali
			
		if ($IdClasse==18 || $dati["ImpInsoluto"]<26) // non vale per i contratti positivi o quasi
			return FALSE;   // segue regole normali

		if ($IdStatoRecupero==25 || $IdStatoRecupero==27) // attesa STR / PAP
			return FALSE; 	
		// Determina se ci sono altri contratti dello stesso cliente, gi� affidati, non positivi e in classi > 2
		// e peggiori o della stessa gravit� alla classe attuale 
		// Inoltre devono essere affidati ad un codice provvigione compatibile con il tipo prodotto
		// (leasing o loan)
		$condFam = substr($dati["CodContratto"],0,2)=="LO"?1:2;
		
/*		$sql = "SELECT IdAgenzia,IdAgente,CodRegolaProvvigione FROM contratto c"
		    ." LEFT JOIN classificazione cl ON cl.IdClasse=c.IdClasse"
		    ." WHERE IdCliente=$IdCliente AND IdAgenzia>0 AND IdContratto!=$IdContratto"
			." AND DataFineAffido>CURDATE() AND ImpInsoluto>=26"
			." AND c.IdClasse!=18 AND c.IdClasse>2 AND cl.Ordine>=$gravita";
*/			
		// Correzione 16/10/2011: confronta la classe del momento dell' affido, non quella corrente	
		$sql = "SELECT c.IdAgenzia,c.IdAgente,c.IdRegolaProvvigione,rp.durata FROM contratto c
                JOIN assegnazione a ON a.IdContratto=c.IdContratto AND a.DataFin=c.DataFineAffido and a.idAgenzia=c.IdAgenzia
	            LEFT JOIN classificazione cl ON cl.IdClasse=a.IdClasse
	            LEFT JOIN regolaProvvigione rp ON rp.IdRegolaProvvigione=c.IdRegolaProvvigione
                WHERE c.IdCliente=$IdCliente AND c.IdAgenzia>0 AND c.IdContratto!=$IdContratto
				AND c.DataFineAffido>CURDATE() AND c.ImpInsoluto>=26
				AND c.IdClasse!=18 AND c.IdClasse>2 AND cl.Ordine>=$gravita";

		// Per l'ultima condizione, verifica sia la regola provvigionale sia la regola d'affidamento,
		// perch� quella provvigionale pu� non aver specificata la restrizione sulla fam. prodotto)
		// NB: le regole provv. devono essere ancora valide alla data del nuovo affido, che viene qui calcolata
		//     per default come 30 gg dalla data di oggi (quindi in pratica si verificano le regole valide per lotti
		//     che finiscono da qui a un mese).
	    $sql .= " AND c.IdRegolaProvvigione in (select IdRegolaProvvigione FROM regolaprovvigione 
	              WHERE (IdFamiglia IS NULL OR IdFamiglia=$condFam) AND FasciaRecupero NOT LIKE 'DBT%' AND FasciaRecupero != 'LEGALE' AND FasciaRecupero NOT LIKE '%REPO%'
	              AND CURDATE()+INTERVAL 1 MONTH-INTERVAL 1 DAY BETWEEN DataIni AND DataFin)";
		// include le pratiche assegnate ad agenzia secondo regola di assegnazione in vigore e della giusta famiglia
	    $sql .= " AND c.IdAgenzia in (select IdReparto FROM regolaassegnazione
	           		WHERE tipoassegnazione=2 AND (IdFamiglia IS NULL OR IdFamiglia=$condFam)
	           		AND CURDATE() BETWEEN DataIni AND DataFin)";
	         
	    $dati = getRow($sql);
		if (count($dati)>0)
		{
			$IdAgenzia = $dati["IdAgenzia"];
			
			// 25-9-2011: se stessa agenzia del precedente affido, mette in attesa per aspettare decisioni
//			if ($IdAgenziaPrec==$IdAgenzia) 
//			{
//				$msgForHistory = "Affidamento sospeso alla stessa agenzia con altre pratiche dello stesso cliente, in conflitto con la regola di cambio agenzia";		
//				return 0;
//			}
			
			//$IdAgente  = $dati["IdAgente"];
			//$idRegolaProvvigione = getScalar("SELECT IdRegolaProvvigione FROM regolaprovvigione WHERE CodRegolaProvvigione='".$dati["CodRegolaProvvigione"]."'");
			$idRegolaProvvigione = $dati["IdRegolaProvvigione"];	 
			$durataProvv = $dati["durata"];
			trace("Affidamento forzato all'agenzia $IdAgenzia (cod=$idRegolaProvvigione) come altro contratto dello stesso cliente",FALSE);
			$msgForHistory = "Affidamento forzato alla stessa agenzia delle altre pratiche dello stesso cliente";		
/* eliminato perch� risolto in generale nella assignAgent
			// Aggiorna il campo IdAgente, in modo che l'assegnazione all'agente sia bypassata e lasci lo stesso
			// Se poi l'affido non viene fatto, il campo viene resettato
		//	if ($IdAgente>0) // potrebbe essere una pratica non affidata ad agente
		//		if (!assegnaAgente($IdContratto,$IdAgente,TRUE))
		//			return FALSE;
*/			
			return $IdAgenzia;
		}
				
		return FALSE; // demanda alla procedura standard
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}
//----------------------------------------------------------------
// Custom_Classification
// Classifica una pratica secondo criteri custom
// Ritorna: FALSE se non pu� stabilire la classificazione e demanda
//               la cosa alle regole standard
//          0    se decide di non classificare
//          IdClasse   se assegnata alla classe ritornata
//----------------------------------------------------------------
function Custom_Classification($IdContratto)
{
	try
	{
		$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$IdContratto");
		
		//-----------------------------------------------------------------------------------------------------------
		// Imposta classificazione per maxirata non pagata
		//-----------------------------------------------------------------------------------------------------------
		if ($pratica["ImpRataFinale"]>0) // prevede una rata finale
		{
			// Controlla se la rata finale � insoluta
			if (rowExistsInTable("insoluto","IdContratto=$IdContratto AND NumRata>" . 
			       ($pratica["NumRate"]-1) . " AND ImpInsoluto>10"))
			{
				trace("Custom_Classification: maxirata",FALSE);
				return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='MAX'");	             
			}
		}

		//-----------------------------------------------------------------------------------------------------------
		// Imposta classificazione per primo affido su piano di rientro
		//-----------------------------------------------------------------------------------------------------------
		$IdProdotto = $pratica["IdProdotto"];
		if ($IdProdotto == 165) // insoluto su Piano di Rientro
		{
		 	if ($pratica["Insoluti"]==1) // un solo insoluto su Piano di Rientro
			{
				if ($pratica["FlagRecupero"]!="Y") // non recidiva
				{
					trace("Pratica PDR1 non recidiva",FALSE);
					return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='PDR1'"); 
				} // se invece e' recidiva, segue la strada normale
			}
//			else // pi� di un insoluto: segue la strada normale (di solito � in lav.interna)
		}
		
		//-----------------------------------------------------------------------------------------------------------
		// Imposta classificazione per comodato d'uso e altri contratti per dipendenti Toyota
		//-----------------------------------------------------------------------------------------------------------
		if (in_array($IdProdotto,array(83,84,166,84,100,219,221,408)))  // comodato d'uso e altri prodotti per i dipendenti Toyota
		{
			trace("Custom_Classification: comodato",FALSE);
			return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='COM'"); 
		}
		
		//-----------------------------------------------------------------------------------------------------------
		// Imposta classificazione per insoluto tecnico (spostata per ultimo il 20/1/2012)
		//-----------------------------------------------------------------------------------------------------------
		if ($pratica["Insoluti"]>0 && $pratica["Importo"]>5)
		{
			$row = getRow("SELECT sum(if(IdTipoInsoluto=12,1,0)) AS tecnici,sum(if(IFNULL(IdTipoInsoluto,0)!=12,1,0)) AS nontecnici" 
		    	         ." FROM insoluto WHERE IdContratto=$IdContratto");
			if ($row["tecnici"]==1 && $row["nontecnici"]==0)  // solo 1 insoluto tecnico
			{
                if ($pratica["Giorni"]<=30 && $pratica["FlagRecupero"]!='Y') // fino a 30 gg non recidivo
                //if ($pratica["IdClasse"]==NULL && $pratica["FlagRecupero"]!='Y') // � la prima volta in assoluto
                {
					//trace("Custom_Classification: insoluto tecnico T00",FALSE);
					//return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='T00'"); /* torna il codice di classificazione "insoluto tecnico" */	             
					trace("Custom_Classification: insoluto tecnico T30",FALSE);
					return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='T30'"); /* torna il codice di classificazione "insoluto tecnico" */	             
				}
				// Passa da T00 a T01 se � T00 da almeno 5 giorni
//				else if ($pratica["CodClasse"]=="T00")
//				{
//	//			 	if (ISODate($pratica["DataCambioClasse"])<=ISODate(mktime(0,0,0,date("n"),date("j")-5,date("Y"))))
//					{
//						trace("Custom_Classification: insoluto tecnico T01",FALSE);
//						return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='T01'"); /* torna il codice di classificazione "insoluto tecnico" */	             
//					}
//					else // T00 da poco: rimane com'�
//					{
//						return $pratica["IdClasse"];			
//					}
//				}
			}
		}
		
		//-----------------------------------------------------------------------------------------------------------
		// Imposta classificazione per riscatto scaduto
		//-----------------------------------------------------------------------------------------------------------
		if ($pratica["IdAttributo"]==86 && $pratica["ImpInsoluto"]<=0) 
		{
			trace("Custom_Classification: riscatto scaduto",FALSE);
		    return getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='RIS'");	             
		}
		
		trace("Custom_Classification: nessuna classificazione speciale",FALSE);
		return FALSE; /* non e' un caso speciale, chiede di applicare classificazione standard */
		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}
//------------------------------------------------------------------------------
// Custom_List
// Crea i dati per una lista custom (all'interno di praticheCorrenti.php)
// Ritorna: FALSE se quella data non e' una lista riconosciuta
// Argomenti: $task		task=nome in codice della lista
//            $join     join da inserire nella SELECT
//            (byRef) $query  pezzo della query da eseguire
//            (byRef) $queryForCount pezzo query da eseguire per SELECT COUNT(*)
//            (byRef) $fields   lista colonne delle SELECT
//            (byRef) $ordine   campi per la ORDER BY
//------------------------------------------------------------------------------
function Custom_List($task,$join,&$query,&$queryForCount,&$fields,&$ordine)
{
	switch ($task)
	{
		case "nonstarted": 
            // fino al 2017-10-06 pratiche "non-started": 6 insoluti nei primi 9 mesi
			//$query =   "v_insoluti_opt v $join WHERE DataDBT <= DataPrimaScadenza + INTERVAL 9 MONTH AND insoluti>5";
            // dopo il 2017-10-06 : 3 rate insolute consecutive nei primi 12 mesi a partire da DataDecorrenza
            $subselect = "select distinct c.IdContratto from contratto c 
join insoluto i1 on i1.IdContratto=c.IdContratto AND i1.numRata>0 and i1.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i1.ImpPagato=0
join insoluto i2 on i2.IdContratto=c.IdContratto AND i2.numRata=i1.NumRata+1 and i2.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i2.ImpPagato=0
join insoluto i3 on i3.IdContratto=c.IdContratto AND i3.numRata=i2.NumRata+1 and i3.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i3.ImpPagato=0
where (c.ImpInsoluto > 0 or c.IdStatoRecupero in (79,84)) and c.IdStatoContratto !=29
UNION 
select distinct c.IdContratto from contratto c 
where c.DataDBT < c.DataDecorrenza + INTERVAL 12 MONTH AND (c.ImpInsoluto > 0 or c.IdStatoRecupero in (79,84)) and c.IdStatoContratto !=29";
            $query =   "v_insoluti_opt v $join WHERE v.IdContratto IN ($subselect)";
			$queryForCount = $query;
			break;
		default:
			return FALSE;
	}
	return TRUE;
}
//------------------------------------------------------------------------------
// Custom_Return
// Esegue eventuali operazioni custom al rientro delle pratiche dall'affido
// Argomenti: $IdContratto	contratto in rientro
//            $IdAgenzia    agenzia a cui era affidato
//            $dataFineAffido data di fine dell'affido appena rientrato
//------------------------------------------------------------------------------
function Custom_Return($IdContratto,$IdAgenzia,$CodRegolaProvvigione,$dataInizioAffido,$dataFineAffido)
{
	//-----------------------------------------------------------------------------------
	// Dopo il rientro da SETEL va in stato "Proposta passaggio DBT", a meno che non ci 
	// sia una forzatura di affido oppure non sia arrivato un incasso nel periodo di
	// affido (oppure non sia in positivo)
	// 27/7/2013: la stessa cosa avviene al rientro da OSIRC e FIDES
	// 15/6/2015: aggiunta OSIRC 2A (che sostituisce SETEL 27)
	//-----------------------------------------------------------------------------------
	if ($CodRegolaProvvigione=="27" || $CodRegolaProvvigione=="L2" || $CodRegolaProvvigione=="L3" || $CodRegolaProvvigione=="2A") 
	{
		$IdAffidoForzato = getScalar("SELECT IdAffidoForzato FROM assegnazione WHERE IdContratto=$IdContratto"
			." AND IdAgenzia=$IdAgenzia AND DataFin='".ISODate($dataFineAffido)."'");
		trace("ritorno da SETEL 27/FIDES L2/OSIRC L3/OSIRC 2A: IdAffidoForzato=$IdAffidoForzato",FALSE);
		if (!($IdAffidoForzato>0))
		{
			$pagato = getScalar("SELECT sum(ImpPagato-ImpIncassoImproprio) FROM storiainsoluto WHERE IdContratto=$IdContratto"
		                   ." AND CodAzione!='REV' AND IdAgenzia=$IdAgenzia AND DataFineAffido='".ISODate($dataFineAffido)."'"
		                   ." AND ImpCapitaleDaPagare>0");
			trace("ritorno da SETEL 27/FIDES L2/OSIRC L3/OSIRC 2A: pagato=$pagato",FALSE);
		    if (!($pagato>0))
 			{
 				// Imposta il campo necessario alle uscite dal ciclo di workflow
 				if (!execute("UPDATE Contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero WHERE IdContratto=$IdContratto"))
					trace("Custom_Return: fallito passaggio in stato Proposta DBT",FALSE);
 				else if (!impostaStato("WRKPROPDBT",$IdContratto))
					trace("Custom_Return: fallito passaggio in stato Proposta DBT",FALSE);
 			}
			else 
				trace("Custom_Return: rientrata da SETEL 27/FIDES L2/OSIRC L3/OSIRC 2A ma non messa in stato DBT causa incasso di euro $pagato",FALSE);
		}
		else 
			trace("Custom_Return: rientrata da SETEL 27/FIDES L2/OSIRC L3/OSIRC 2A ma non messa in stato DBT causa affido forzato: ".print_r($IdAffidoForzato,TRUE),FALSE);
	}
	//--------------------------------------------------------------------------------------
	// Dopo il rientro da stragiudiziale, se � stata registrata una PDP perdita di possesso
	// e non ci sono richieste di riaffido, passa la pratica in write off
	//--------------------------------------------------------------------------------------
	if (rowExistsInTable("storiarecupero s JOIN azione a ON s.IdAzione=a.IdAzione AND CodAzione='PDP'
						  JOIN azionespeciale azs ON azs.IdAzione=a.IdAzione AND s.IdContratto=azs.IdContratto",
	                     "s.IdContratto=$IdContratto AND azs.stato!='R'"))
	{ //esiste una registrazione valida di perdita di possesso
		$prossimoAffido = getScalar("SELECT IdAffidoForzato FROM assegnazione WHERE IdContratto=$IdContratto AND DataFin='$dataFineAffido'");
		if (!($prossimoAffido>'')) // non c'� forzatura di riaffido
		{
			if (impostaStato("WRKPROPWO",$IdContratto))
 				trace("Passaggio automatico in WriteOff per presenza di un'azione PDP su idContratto=$IdContratto",FALSE);
			else
 				trace("Fallito passaggio automatico in WriteOff per presenza di un'azione PDP su idContratto=$IdContratto",FALSE);
		}
	}
	else
	{
		//--------------------------------------------------------------------------------------
		// Dopo il rientro da stragiudiziale step 1, se c'� stato un incasso forza il riaffido
		// alla stessa agenzia, a meno che non ci sia gi� una richiesta di riaffido pendente
		//--------------------------------------------------------------------------------------
		$row 	= getRow("SELECT FasciaRecupero,IdRegolaProvvigione FROM regolaprovvigione WHERE CodRegolaProvvigione='$CodRegolaProvvigione' AND DataFin>CURDATE()");
		$fascia = $row["FasciaRecupero"];
		$IdRegola = $row["IdRegolaProvvigione"];
		if ($fascia=="DBT SOFT" || $fascia=="DBT HARD" ) // sono i codici 05 07 16 e 25
		{
			$IdAffidoForzato = getScalar("SELECT IdAffidoForzato FROM assegnazione WHERE IdContratto=$IdContratto"
				." AND IdAgenzia=$IdAgenzia AND DataFin='".ISODate($dataFineAffido)."'");
			if (!($IdAffidoForzato>0)) // non c'� stata gi� una forzatura
			{
				// Calcola il pagato nell'intero periodo di affido STR
				$pagato = getScalar("SELECT sum(ImpPagato-ImpIncassoImproprio) FROM storiainsoluto WHERE IdContratto=$IdContratto"
		                   ." AND CodAzione!='REV' AND IdAgenzia=$IdAgenzia AND DataInizioAffido BETWEEN '".ISODate($dataInizioAffido)."' AND '".ISODate($dataFineAffido)."'"
		                   ." AND ImpCapitaleDaPagare>0");
		    	if ($pagato>0)
 				{ 
 					// inserisce la forzatura nella riga di assegnazione, come se fosse stata fatta durante l'affido
					$sql =  "UPDATE assegnazione SET IdAffidoForzato=$IdRegola 
					 WHERE  IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND DataFin='".ISODate($dataFineAffido)."'";
					if (execute($sql))
					{
						writeHistory("NULL","Forzato riaffido ad agenzia $CodRegolaProvvigione perche' avvenuto incasso di euro $pagato",$IdContratto,"");
					}
 				}
			}
		}
	}
}

//--------------------------------------------------------------------------------------------------
// Custom_Search
// Esegue una ricerca custom nella ricerca delle pratiche
// Argomenti: $sField		campo di ricerca inserito
//			  $where_search campo passato per riferimento sulla ricerca
//            $schema 		nome dello schema di DB (si usa per distinguere le ricerche sullo storico
//--------------------------------------------------------------------------------------------------
function Custom_Search($sField,&$where_search,$schema=MYSQL_SCHEMA)
{
//	trace ("Custom_Search $sField",FALSE);
	$sField = strtoupper($sField);
	$where_search = "";
	if (substr($sField,0,3)=="ID=") // escamotage per specificare un IdContratto
	{
		$where_search = "v.IdContratto=".substr($sField,3);
		trace ("Custom_Search where=$where_search",FALSE);
		return TRUE;
	}
	if (is_numeric($sField))
	{
		// cerca con una query semplice sulla sola tabella Contratto
		$ids = fetchValuesArray("SELECT IdContratto FROM $schema.contratto WHERE CodContratto IN ('LE$sField','LO$sField') OR CodContratto LIKE 'LE$sField%'");
		if (count($ids)>0)
			$where_search = "v.IdContratto IN (".join(",",$ids).")";
		else
			$where_search = "v.IdContratto=0"; // non esiste un contratto con questo numero
		trace ("Custom_Search where=$where_search",FALSE);
		return TRUE;
	}

	if (is_numeric(substr($sField,2,3)))		// se seguito da cifre numeriche
	{//codcontratto o numero di codcontratto
		$prefix = substr($sField,0,2);
		switch ($prefix)
		{
			case 'LO': 
				$where_search = "v.numPratica = '".$sField."'";
				break;
			case 'LE': 
				$where_search = "v.numPratica LIKE '".$sField."%'"; // perch� pu� esserci il trattino finale "-2"
				break;
		}
	}
	
	if ($where_search=="") // nessuno dei casi precedenti
	{
		// tolta ricerca su num pratica, perch� gestita dai casi precedenti
		//$where_search = "numPratica LIKE '%".$sField."%'"; 
		// Determina la lista di IdCliente che soddisfano i criteri (nome o rag. sociale)
		$clienti = fetchValuesArray("SELECT IdCliente FROM $schema.cliente WHERE IFNULL(Nominativo,RagioneSociale) LIKE '%".$sField."%'");
		if (is_array($clienti) && count($clienti)>0)
			if (count($clienti)>500) // clausola IN lunga, meglio evitare			
				//$where_search = "($where_search OR v.IdCliente IN (SELECT IdCliente FROM cliente WHERE IFNULL(Nominativo,RagioneSociale) LIKE '%".$sField."%'))";
				$where_search = "v.IdCliente IN (SELECT IdCliente FROM $schema.cliente WHERE IFNULL(Nominativo,RagioneSociale) LIKE '%".$sField."%')";
			else
				//$where_search = "($where_search OR v.IdCliente IN (".join($clienti,",")."))";
				$where_search = "v.IdCliente IN (".join($clienti,",").")";
		else
			$where_search = "v.numPratica LIKE '%".$sField."%'"; 
	}
	$where_search .= " AND v.numPratica NOT LIKE 'KG%'"; // esclude pratiche dipendenti Toyota
	//trace ("Custom_Search where=$where_search",FALSE);
	return TRUE;
}
//------------------------------------------------------------------------------
// Custom_Duration
// Applica una regola custom per la durata dell'affidamento
// Ritorna: FALSE se vale la regola standard, altrimenti il numero giorni
// Argomenti:   $IdContratto			id contratto
//				$IdRegolaProvvigione	regola provvigionale applicata
//				$dataInizioAffido		data di inizio affidamento
//------------------------------------------------------------------------------
function Custom_Duration($IdContratto,$IdRegolaProvvigione,$dataInizioAffido)
{
	// non vale pi� dal 2015
	/*
	$CodRegolaProvvigione = getScalar("SELECT CodRegolaProvvigione FROM regolaprovvigione WHERE IdRegolaProvvigione=0$IdRegolaProvvigione");
	if ($CodRegolaProvvigione=='26' // IRC stragiudiziale step 2, 180 giorni se maggiore di 10K o irreperibile
	|| $CodRegolaProvvigione=='30') // NCP Srl
	{
		$row = getRow("SELECT ImpInsoluto,FlagIrreperibile FROM contratto c JOIN cliente cl ON c.IdCliente=cl.IdCliente WHERE c.IdContratto=$IdContratto");
		if ($row["ImpInsoluto"]>10000 || $row["FlagIrreperibile"]=='Y')
		{
			trace("Custom duration per affido DBT STRONG = 180gg",FALSE);
			return 180;
		}
		else
		{
			trace("Custom duration per agenzia DBT STRONG = 120gg",FALSE);
			return 120;
		}
	}
	//else
	//	trace("Custom duration nulla CodRegolaProvvigione=$CodRegolaProvvigione",FALSE);
	 * 
	 */
	return FALSE; // applica le regole standard
}

//------------------------------------------------------------------------------
// Custom_Import_Check
// Applica una regola custom di controllo ad un campo di input all'import
// Ritorna: FALSE se il valore � errato
//          il campo $reason in tal caso deve contenere un testo con il motivo dell'errore
//------------------------------------------------------------------------------
function Custom_Import_Check($table,$column,$value,&$reason) {
	global $context;
	trace(print_r($context,true),false);
	if($table == "contratto" && $column == "CodContratto"){
		$idReparto = $context["IdReparto"];
		$idAgenzia = getScalar("SELECT IdAgenzia FROM contratto WHERE codContratto =".quote_smart($value));
		if($idReparto == $idAgenzia){
			return true;
		}else{
			$reason = "la pratica $value non appartiene all'agenzia";
			return false;
		}
	}else{
		return true;
	}
	
	
}
