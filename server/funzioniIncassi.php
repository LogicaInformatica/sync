<?php
// Funzioni varie relative agli incassi (chiamate da gestioneIncassi.php e da edit_azione.php)
require_once("processInsoluti.php");

//---------------------------------------------------------------------------------------
// insertIncasso
// Crea una riga di incasso e aggiorna le tabelle collegate (chiamata in edit_azione.php)
//---------------------------------------------------------------------------------------
function insertIncasso($idContratto,$idCliente,$IdAllegato) {

	global $context;

	beginTrans();
	
	$esito = "";
	$valList = "";
	$colList = "";

	addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
	addInsClause($colList,$valList,"IdAllegato",$IdAllegato,"N");
	addInsClause($colList,$valList,"IdTipoIncasso",$_POST['IdTipoIncasso'],"N");
	if ($_POST['flag_modalita']!='V') {
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
	addInsClause($colList,$valList,"ImpSpeseLegali",$_POST['speseLegaliI'],"I");
	addInsClause($colList,$valList,"ImpSpese",$_POST['speseIncassoI'],"I");
	addInsClause($colList,$valList,"FlagModalita",$_POST['flag_modalita'],"S");
	addInsClause($colList,$valList,"Nota",$_POST['nota'],"S");
	addInsClause($colList,$valList,"FlagSaldoFinale",$_POST['chkSaldoFinale']=='on'?'Y':'N',"S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	addInsClause($colList,$valList,"LastUpd","NOW()","G");
	addInsClause($colList,$valList,"IdUtente",$context['IdUtente'],"S");

	$sql =  "INSERT INTO incasso ($colList) VALUES ($valList)";
	//trace($sql);
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore)
	if (!execute($sql)) {
		$esito = getLastError();
	}else{
		switch($_POST['flag_modalita']){
			case 'E' : // e' un incasso salvo buon fine: non ci sono conseguenze contabili
				$upd = "UPDATE contratto SET ImpPagatoSBF =". importo($_POST['importo']). "+ ifnull(ImpPagatoSBF,0)
				where IdContratto = $idContratto";
				if(!execute($upd)){
					$esito = getLastError();
				};
				break;
			case 'P': // e' un pagamento confermato crea movimento di incasso. 
				if (rowExistsInTable("pianorientro","IdContratto=$idContratto AND IdStatoPiano=4")) { // PDR / SS in corso
					$ImpTotalePagato = str_replace(',','.',str_replace('.','',$_POST['capitaleI']))
									 + str_replace(',','.',str_replace('.','',$_POST['interessiMoraI']))
									 + str_replace(',','.',str_replace('.','',$_POST['speseIncassoI']))
									 + str_replace(',','.',str_replace('.','',$_POST['altriAddebitiI']))
									 + str_replace(',','.',str_replace('.','',$_POST['speseLegaliI']));
					$esito = creaMovimentoIncassoPDR($idContratto,$_POST['dataDoc'],$_POST['nrDoc'],$ImpTotalePagato);
				} else {
					$esito = creaMovimentoIncasso($idContratto,$_POST['dataDoc'],$_POST['nrDoc'],
						$_POST['capitaleI'],$_POST['interessiMoraI'],$_POST['speseIncassoI'],$_POST['altriAddebitiI'], $_POST['speseLegaliI']);
				}
				if ($_POST['chkSaldoFinale']=='on') { // e' indicato come saldo finale: genera l'abbuono finale
					creaAbbuonoFinaleSS($idContratto,$_POST['dataDoc']);
				}
				break;
			case 'V': // OBSOLETO
//				$esito = creaMovimentoIncasso($idContratto,$_POST['dataOp'],$_POST['nrDoc'],
//					$_POST['capitaleI'],$_POST['interessiMoraI'],$_POST['speseIncassoI'],$_POST['altriAddebitiI'],  $_POST['speseLegaliI']);
				break;
		}
	}
	return $esito;
}

/**
 * creaAbbuonoFinaleSS Crea un movimento contabile per portare a zero il saldo dovuto, in seguito alla registrazione di un pagamento con flag
 *   di "saldo e stralcio completato"
 */
function creaAbbuonoFinaleSS($IdContratto,$DataDoc) {
	// Determina l'importo da abbuonare
	$importo = getScalar("SELECT ImpInsoluto FROM contratto WHERE IdContratto=$IdContratto");
	if ($importo>0) {
		$IdTipoMovimento = getScalar("SELECT IdTipoMovimento FROM tipomovimento WHERE CodTipoMovimento='ABB'");
		creaMovimentoAddebito($IdContratto,$DataDoc,"",-$importo,0,0,0,0,null,null,0,$useless,true,$IdTipoMovimento);
		
	}
}

/**
 * eliminaAbbuonoFinaleSS Elimina il movimento contabile di abbuono creato al momento della convalida di un incasso
 * NOTA: si presume che l'aggiornamento dei saldi (updateInsoluti) venga chiamato altrove, dopo questa cancellazione
 */
function eliminaAbbuonoFinaleSS($IdContratto,$DataReg) {
	$IdTipoMovimento = getScalar("SELECT IdTipoMovimento FROM tipomovimento WHERE CodTipoMovimento='ABB'");
	execute("DELETE FROM movimento WHERE IdContratto=$IdContratto AND DataRegistrazione='$DataReg' AND IdTipoMovimento=$IdTipoMovimento");
}

/**
 * creaMovimentoIncasso
 * Crea uno o più movimenti contabili corrispondenti al tipo di incasso che si sta registrando
 */
function creaMovimentoIncasso($IdContratto,$DataDoc,$NumDoc,$ImpCapitale,$ImpInteressi,$ImpSpese,$AltriAddebiti,$SpLegali,
		$DataScadenza=null,$DataRegistrazione=null,$NumRata=0,&$insertedIds,$updateInsoluti=true) {
	global $context;
	trace("Creazione movimento contabile per incasso su IdContratto=$IdContratto, data=$DataDoc,Importi=$ImpCapitale,$ImpInteressi,$ImpSpese,$AltriAddebiti,$SpLegali",false);

	$insertedIds = array();
	$DataRegistrazione 	= $DataRegistrazione>'' ? ("'".ISODate($DataRegistrazione)."'"):'CURDATE()';
	$DataScadenza 		= $DataScadenza>'' ? ("'".ISODate($DataScadenza)."'"):'CURDATE()';
	$DataCompetenza		= $DataDoc>'' ? ("'".ISODate($DataDoc)."'"):'CURDATE()';
	$NumRata            = $NumRata==''?'0':$NumRata;
	
	beginTrans();	
	if (trim($NumDoc)=='') $NumDoc=' '; 
	$some = false;
	if ($ImpCapitale!=0) {  // pagamento o storno pagamento quota capitale
		$valList = "";
		$colList = "";
		$TIPO_INCASSO = 2;  		// incasso capitale 
		$TIPO_STORNO_INCASSO = 5;   // storno incasso capitale 
		
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",1,"N");
		addInsClause($colList,$valList,"Importo","-$ImpCapitale","I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",1,"N");   // partita di tipo capitale
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpCapitale>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo Pagamento
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno Pagamento
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($ImpInteressi!=0) {  // pagamento o storno pagamento interessi
		$valList = "";
		$colList = "";
		$TIPO_INCASSO = 4;  		// incasso interessi 
		$TIPO_STORNO_INCASSO = 6;   // storno incasso interessi 
		
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",2,"N");
		addInsClause($colList,$valList,"Importo","-$ImpInteressi","I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",2,"N");   // partita di tipo interessi
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpInteressi>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo Pagamento interessi
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno Pagamento interessi
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($ImpSpese!=0) {  // pagamento o storno pagamento spese
		$valList = "";
		$colList = "";
		$TIPO_INCASSO = 8;  		// incasso spese recupero
		$TIPO_STORNO_INCASSO = 9;   // storno incasso recupero 
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",3,"N");
		addInsClause($colList,$valList,"Importo","-$ImpSpese","I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",3,"N");   // partita di tipo spese
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpSpese>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo Pagamento spese
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno Pagamento spese
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($AltriAddebiti!=0) {  // pagamento o storno pagamento altri addebiti
		$valList = "";
		$colList = "";
		$TIPO_INCASSO = 11;  		 // incasso altri addebiti
		$TIPO_STORNO_INCASSO = 12;   // storno incasso altri addebiti 
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",4,"N");
		addInsClause($colList,$valList,"Importo","-$AltriAddebiti","I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",4,"N");   // partita di tipo altri addebiti
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($AltriAddebiti>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo Pagamento altri addebiti
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno Pagamento altri addebiti
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($SpLegali!=0) {  // pagamento o storno pagamento spese legali
		$valList = "";
		$colList = "";
		$TIPO_INCASSO = 19;  		 // incasso altri spese legali
		$TIPO_STORNO_INCASSO = 21;   // storno incasso spese legali
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",5,"N");
		addInsClause($colList,$valList,"Importo","-$SpLegali","I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",5,"N");   // partita di tipo spese legali
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($SpLegali>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo Incasso Spese Legali
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno Incasso Spese Legali
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	commit();
	
	$insertedIds = implode(',',$insertedIds); // restituisce la lista di IdMovimento creati
	if ($some && $updateInsoluti) {
		if (!processInsolutiSimple($IdContratto)) {
			return "Fallito ricalcolo delle partite aperte";
		}
	}
	return "";
}

/**
 * creaMovimentoIncassoPDR
 * Crea uno o più movimenti contabili di incasso/storno dei pagamenti relativi ad un piano di rientro / saldo & stralcio
 * Infatti, nel caso in cui la pratica abbia un PDR/SS in corso, ogni pagamento viene attribuito alle rate nell'ordine
 * in cui sono scadenzate, coprendo gli importi dal più vecchio al più nuovo. Nel caso di storno di incasso, viene fatto
 * lo stesso, ma all'indietro, dall'ultima rata pagata. Si generano comunque solo movimenti di tipo "capitale", senza
 * far distinzione di interessi/spese ecc.
 */
function creaMovimentoIncassoPDR($IdContratto,$DataDoc,$NumDoc,$ImpTotalePagato) {
	global $context;
	trace("Creazione movimento contabile per incasso su IdContratto=$IdContratto, data=$DataDoc,Importo totale=$ImpTotalePagato",false);

	if (trim($NumDoc)=='') $NumDoc=' ';
	
	beginTrans();
	$some = false;

	if ($ImpTotalePagato>0) { // pagamento normale
		// Legge le rate non totalmente pagate
		$rate = getRows("SELECT r.* FROM pianorientro p JOIN ratapiano r ON p.IdPianoRientro=r.IdPianoRientro"
				." WHERE IdContratto=$IdContratto AND IdStatoPiano=4 AND ImpPagato<Importo ORDER BY NumRata");
		if (!$rate) {
			rollback();
			return "Incongruenza nei dati, non esiste un piano di rientro o un saldo e stralcio da saldare relativo questa pratica";	
		}	
		foreach ($rate as $rata) {
			extract($rata);
			if ($ImpTotalePagato<=0) break;
			$pagato = importo(min($Importo-$ImpPagato,$ImpTotalePagato));
			if (!execute("UPDATE ratapiano SET ImpPagato=$pagato WHERE  IdPianoRientro=$IdPianoRientro AND NumRata=$NumRata")) {
				rollback();
				return getLastError();
			}
			trace("Contratto=$IdContratto, Accreditati euro $pagato a pagamento della rata n. $NumRata");

			$valList = "";
			$colList = "";
			$TIPO_INCASSO = 23;  		// incasso rata
			$TIPO_STORNO_INCASSO = 25;  // storno incasso rata

			addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
			addInsClause($colList,$valList,"NumRata",$NumRata,"N");
			addInsClause($colList,$valList,"DataRegistrazione", "curdate()","G");
			addInsClause($colList,$valList,"DataCompetenza", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
			addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
			addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
			addInsClause($colList,$valList,"NumRiga",2,"N");
			addInsClause($colList,$valList,"Importo","-$pagato","N"); // le entrate si mettono con importo negativo
			addInsClause($colList,$valList,"IdTipoPartita",1,"N");   // partita di tipo capitale
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_INCASSO,"N"); // Movimento di tipo incasso rata
			$ins = "INSERT INTO movimento($colList) VALUES($valList);";
			if(!execute($ins)){
				rollback();
				return getLastError();
			}
			$ImpTotalePagato -= $pagato;
			$some = true;
		}
	} else { // importo negativo: � uno storno di incasso
		// Legge le rate non totalmente impagate, in ordine decrescente
		$rate = getRows("SELECT r.* FROM pianorientro p JOIN ratapiano r ON p.IdPianoRientro=r.IdPianoRientro"
				." WHERE IdContratto=$IdContratto AND IdStatoPiano=4 AND ImpPagato>0 ORDER BY NumRata DESC");
		if (!$rate) {
			rollback();
			return "Incongruenza nei dati, non esiste un piano di rientro o un saldo e stralcio relativo con pagamenti relativi questa pratica";	
		}	
		foreach ($rate as $rata) {
			extract($rata);
			if ($ImpTotalePagato>=0) break;
			$storno = min($ImpPagato,-$ImpTotalePagato);
			if (!execute("UPDATE ratapiano SET ImpPagato=$ImpPagata-$storno WHERE IdPianoRientro=$IdPianoRientro AND NumRata=$NumRata")) {
				rollback();
				return getLastError();
			}
			trace("Contratto=$IdContratto, riaddenitati euro $storno sulla rata n. $NumRata");
		
			$valList = "";
			$colList = "";
			$TIPO_STORNO_INCASSO = 25;  // storno incasso rata
		
			addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
			addInsClause($colList,$valList,"NumRata",$NumRata,"N");
			addInsClause($colList,$valList,"DataRegistrazione", "curdate()","G");
			addInsClause($colList,$valList,"DataCompetenza", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
			addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
			addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
			addInsClause($colList,$valList,"NumRiga",2,"N");
			addInsClause($colList,$valList,"Importo","$storno","N"); 
			addInsClause($colList,$valList,"IdTipoPartita",1,"N");   // partita di tipo capitale
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdTipoMovimento",$TIPO_STORNO_INCASSO,"N"); // Movimento di tipo storno incasso rata
			$ins = "INSERT INTO movimento($colList) VALUES($valList);";
			if(!execute($ins)){
				rollback();
				return getLastError();
			}
			$ImpTotalePagato += $storno;
			$some = true;
		}	
	}
	commit();
	
	if ($some) {
		if (!processInsolutiSimple($IdContratto)) {
			return "Fallito ricalcolo delle partite aperte";
		}
	}
	return "";
}

/**
 * creaMovimentoAddebito
 * Crea uno o più movimenti contabili corrispondenti al tipo di addebito che si sta registrando
 * NOTA: grazie al parametro "forzaTipoMovimento" questa funzione si può usare per registrare anche importi a credito (o in genere, qualunque movimento)
 */
function creaMovimentoAddebito($IdContratto,$DataDoc,$NumDoc,$ImpCapitale,$ImpInteressi,$ImpSpese,$AltriAddebiti,$SpLegali,
		$DataScadenza=null,$DataRegistrazione=null,$NumRata=0,&$insertedIds,$updateInsoluti=true,$forzaTipoMovimento=0) {
	global $context;
	trace("Creazione movimento contabile per addebito su IdContratto=$IdContratto, data=$DataDoc,Importi=$ImpCapitale,$ImpInteressi,$ImpSpese,$AltriAddebiti,$SpLegali",false);
	$insertedIds = array();
	$DataRegistrazione 	= $DataRegistrazione>'' ? ("'".ISODate($DataRegistrazione)."'"):'CURDATE()';
	$DataScadenza 		= $DataScadenza>'' ? ("'".ISODate($DataScadenza)."'"):'CURDATE()';
	$DataCompetenza		= $DataDoc>'' ? ("'".ISODate($DataDoc)."'"):'CURDATE()';
	$NumRata            = $NumRata==''?'0':$NumRata;
	
	beginTrans();
	if (trim($NumDoc)=='') $NumDoc=' ';
	$some = false;
	if ($ImpCapitale!=0) {  // addebito o storno 
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",1,"N");
		addInsClause($colList,$valList,"Importo",$ImpCapitale,"I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",1,"N");   // partita di tipo capitale
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpCapitale>0) { // addebito positivo
			addInsClause($colList,$valList,"IdTipoMovimento",$forzaTipoMovimento>0?$forzaTipoMovimento:13,"N"); // Movimento di tipo Addebito capitale
		} else { // addebito negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",$forzaTipoMovimento>0?$forzaTipoMovimento:14,"N"); // Movimento di tipo storno Addebito capitale
		}

		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($ImpInteressi!=0) {  // addebito o storno addebito interessi
		$valList = "";
		$colList = ""; 
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",2,"N");
		addInsClause($colList,$valList,"Importo",$ImpInteressi,"I"); //importo positivo
		addInsClause($colList,$valList,"IdTipoPartita",2,"N");   // partita di tipo interessi
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpInteressi>0) { // addebito positivo
			addInsClause($colList,$valList,"IdTipoMovimento",3,"N"); // Movimento di tipo Addebito interessi
		} else { // addebito negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",15,"N"); // Movimento di tipo storno Addebito interessi
		}

		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($ImpSpese!=0) {  // addebito o storno addebito spese
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",3,"N");
		addInsClause($colList,$valList,"Importo",$ImpSpese,"I"); // importo positivo
		addInsClause($colList,$valList,"IdTipoPartita",3,"N");   // partita di tipo spese
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($ImpSpese>0) { // addebito positivo
			addInsClause($colList,$valList,"IdTipoMovimento",7,"N"); // Movimento di tipo Addebito spese
		} else { // addebito negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",16,"N"); // Movimento di tipo storno Addebito spese
		}

		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($AltriAddebiti!=0) {  // pagamento o storno pagamento altri addebiti
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"NumDocumento",$NumDoc,"S");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",4,"N");
		addInsClause($colList,$valList,"Importo",$AltriAddebiti,"I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",4,"N");   // partita di tipo altri addebiti
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($AltriAddebiti>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",10,"N"); // Movimento di tipo Pagamento altri addebiti
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",17,"N"); // Movimento di tipo storno Pagamento altri addebiti
		}

		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	if ($SpLegali!=0) {  // pagamento o storno pagamento altri addebiti
		$valList = "";
		$colList = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataRegistrazione", $DataRegistrazione,"G");
		addInsClause($colList,$valList,"DataCompetenza", $DataCompetenza,"G");
		addInsClause($colList,$valList,"DataDocumento", $DataDoc,"D");
		addInsClause($colList,$valList,"DataScadenza", $DataScadenza,"G");
		addInsClause($colList,$valList,"DataValuta", $DataDoc,"D");
		addInsClause($colList,$valList,"NumRiga",4,"N");
		addInsClause($colList,$valList,"Importo",$SpLegali,"I"); // le entrate si mettono con importo negativo
		addInsClause($colList,$valList,"IdTipoPartita",5,"N");   // partita di tipo altri addebiti
		addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		if ($SpLegali>0) { // incasso positivo
			addInsClause($colList,$valList,"IdTipoMovimento",18,"N"); // Movimento di tipo Spese Legali
		} else { // incasso negativo (storno)
			addInsClause($colList,$valList,"IdTipoMovimento",20,"N"); // Movimento di tipo storno Spese Legali
		}
	
		$ins = "INSERT INTO movimento($colList) VALUES($valList);";
		if(!execute($ins)){
			rollback();
			return getLastError()." SQL=$ins";
		}
		$insertedIds[] = getInsertId();
		$some = true;
	}
	commit();
	$insertedIds = implode(',',$insertedIds); // restituisce la lista di IdMovimento creati
	
	if ($some && $updateInsoluti) {
		if (!processInsolutiSimple($IdContratto)) {
			return "Fallito ricalcolo delle partite aperte";
		}
	}
	return "";
}


/**
 * insertAddebito
 * Crea una riga di addebito e aggiorna le tabelle collegate (chiamata in edit_azione.php)
 */

function insertAddebito($idContratto){

	$esito = "";
	$esito = creaMovimentoAddebito($idContratto,date('Y-m-d'),'',
			0,$_POST['interessiMoraI'],$_POST['speseIncassoI'],$_POST['altriAddebitiI'],
			$_POST['speseLegaliI']);
	return $esito;
	
}

/**
 * updateIncasso Aggiorna un incasso (per operazione di Save, Conferma, Annulla, dal pannello di modifica dell'incasso)
 * Nota: presuppone che i dati del form siano in $_POST
 */
function updateIncasso($IdAllegato,$idContratto,$IdIncasso,$oper)
{
	try
	{
		global $context;
		$oldrow = getRow("SELECT * FROM incasso WHERE IdIncasso=$IdIncasso");
		
		switch ($oper) {
			case 'C': // conferma pagamento
				$FlagModalita = 'P';
				$testo = "Confermato";
				break;
			case 'A': // annulla pagamento
				$FlagModalita = 'A';
				$testo = "Annullato";
				break;
			case 'U': // update semplice
				$FlagModalita = $oldrow['FlagModalita'];
				$testo = "Modificato";
				break;
		}
		$setClause = "";
		addSetClause($setClause,"IdContratto",$idContratto,"N");
		addSetClause($setClause,"IdAllegato",$IdAllegato,"N");
		addSetClause($setClause,"IdTipoIncasso",$_POST['IdTipoIncasso'],"N");
		addSetClause($setClause,"DataRegistrazione","NOW()","G");
		addSetClause($setClause,"DataDocumento",$_POST['DataDocumento'],"D");
		addSetClause($setClause,"NumDocumento",$_POST['NumDocumento'],"S");
		addSetClause($setClause,"ImpPagato",$_POST['ImpPagato'],"N");
		addSetClause($setClause,"ImpCapitale",$_POST['IncCapitale'],"N");
		addSetClause($setClause,"ImpInteressi",$_POST['IncInteressi'],"N");
		addSetClause($setClause,"ImpSpese",$_POST['IncSpese'],"N");
		addSetClause($setClause,"ImpAltriAddebiti",$_POST['IncAltriAddebiti'],"N");
		addSetClause($setClause, "ImpSpeseLegali",$_POST['IncSpeseLegali'], "N");
		addSetClause($setClause,"Nota",$_POST['Nota'],"S");
		addSetClause($setClause,"FlagSaldoFinale",$_POST['chkSaldoFinale']=='on'?'Y':'N',"S");
		addSetClause($setClause,"FlagModalita",$FlagModalita,"S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"LastUpd","NOW()","G");
		addSetClause($setClause,"IdUtente",$context['IdUtente'],"S");
		
		if (!execute("UPDATE incasso $setClause WHERE IdIncasso=$IdIncasso")) {
			return false;
		}
		writeHistory("NULL","$testo incasso di euro {$_POST['ImpPagato']} del {$_POST['DataDocumento']}",$idContratto,$_POST['Nota']);
		
		// Aggiorna la parte contabile (movimento e insoluto)
		$oldFlag = $oldrow['FlagModalita'];
		$diffPagato = $_POST['ImpPagato']-$oldrow['ImpPagato']; 
		$diffCapitale = $_POST['IncCapitale']-$oldrow['ImpCapitale']; 
		$diffInteressi = $_POST['IncInteressi']-$oldrow['ImpInteressi'];
		$diffSpese = $_POST['IncSpese']-$oldrow['ImpSpese'];
		$diffAltri = $_POST['IncAltriAddebiti']-$oldrow['ImpAltriAddebiti'];
		$diffLegali = $_POST['IncSpeseLegali'] -$oldrow['ImpSpeseLegali'];
		
		if ($oldFlag=='E') { // era un pagamento non confermato
			if ($FlagModalita=='P') { // diventa confermato
				// detrae l'importo dal campo cumulativo salvo buon fine
				$upd = "UPDATE contratto SET ImpPagatoSBF = ImpPagatoSBF-{$oldrow['ImpPagato']} where IdContratto = $idContratto";
				if (!execute($upd)) {
					return false;
				}
				$esito = creaMovimentoIncasso($idContratto,$_POST['Data'],$_POST['NumDocumento'],$_POST['IncCapitale'],
						$_POST['IncInteressi'],$_POST['IncSpese'],$_POST['IncAltriAddebiti'], $_POST['IncSpeseLegali']);
				if ($esito>'') {
					setLastError($esito);
					return false;	
				}
				// Se è indicato "Saldo finale S/S" genera il movimento di abbuono
				if ($_POST['chkSaldoFinale']=='on') {
					creaAbbuonoFinaleSS($idContratto,$_POST['Data']);
				}  
			} else if ($FlagModalita=='A') { // diventa annullato, serve solo aggiornare il cumulativo
				$upd = "UPDATE contratto SET ImpPagatoSBF = ImpPagatoSBF-{$oldrow['ImpPagato']} where IdContratto = $idContratto";
				if (!execute($upd)) {
					return false;
				}
			} else { // altrimenti bisogna solo aggiornare il cumulativo, con la differenza eventuale
				$upd = "UPDATE contratto SET ImpPagatoSBF = ImpPagatoSBF+$diffPagato where IdContratto = $idContratto";
				if (!execute($upd)) {
					return false;
				}
			}
		} else if ($oldFlag=='P') { // era un pagamento confermato
			if ($FlagModalita=='A') { // diventa annullato: crea uno storno
				// se era specificato flagSaldoFinale, elimina il movimento di abbuono che era stato creato
				if ($oldrow['FlagSaldoFinale']=='Y') {
					eliminaAbbuonoFinaleSS($idContratto,$oldrow['DataRegistrazione']); // NOTA:il ricalcolo insoluti viene chiamato da creaMovimentoIncasso
				}
				$esito = creaMovimentoIncasso($idContratto,$_POST['Data'],$_POST['NumDocumento'],-$oldrow['ImpCapitale'],
						-$oldrow['ImpInteressi'],-$oldrow['ImpSpese'],-$oldrow['ImpAltriAddebiti'], -$oldrow['ImpSpeseLegali']);
				if ($esito>'') {
					setLastError($esito);
					return false;	
				}
			} else { // ALTRIMENTI GLI IMPORTI NON SONO MODIFICABILI (CAMPI READONLY), MA la checkbox FlagSaldoFinale sì
				if ($oldrow['FlagSaldoFinale']=='Y' && $_POST['chkSaldoFinale']!='on') { // il flagSaldoFinale è stato spento
					eliminaAbbuonoFinaleSS($idContratto,$oldrow['DataRegistrazione']); // NOTA:il ricalcolo insoluti viene chiamato da creaMovimentoIncasso
				} else if ($oldrow['FlagSaldoFinale']!='Y' && $_POST['chkSaldoFinale']=='on') { // il flagSaldoFinale è stato acceso
					creaAbbuonoFinaleSS($idContratto,$_POST['Data']);
				}
			}
		} else { // era un pagamento annullato
			if ($FlagModalita=='P') { // diventa confermato
				$esito = creaMovimentoIncasso($idContratto,$_POST['Data'],$_POST['NumDocumento'],$oldrow['ImpCapitale'],
						$oldrow['ImpInteressi'],$oldrow['ImpSpese'],$oldrow['ImpAltriAddebiti'], $oldrow['ImpSpeseLegali']);
				if ($esito>'') {
					setLastError($esito);
					return false;
				}
			} // ALTRIMENTI NON E' MODIFICABILE (CAMPI READONLY)
		}
		return true;
	}    
	catch (Exception $e)
	{
		trace($e->getMessage());
		return false;
    }
}
/**
 * generaMovimentiPianoRientro Genera i movimenti contabili che rispecchiano l'attivazione o la chiusura di un piano
 * di rientro
 * @param {String} $tipo "Attiva" o "Annulla"
 * @param {Number} $idContratto
 */
function generaMovimentiPianoRientro($tipo,$idContratto) {
	global $context;
	beginTrans();
	
	// Determina l' IdTipoPartita da utilizzare per le rate (movimento su capitale)
	$IdPartitaCapitale = getScalar("SELECT IdTipoPartita FROM tipopartita WHERE CategoriaPartita='C'");
	if (getLastError()>'') {rollback(); return false;}
	
	$piano = getRow("SELECT IdPianoRientro,IdStatoPiano FROM pianorientro WHERE IdContratto=$idContratto");
	if ($tipo=='Attiva') {
		if (!$piano or $piano['IdStatoPiano']!=4) {
			setLastError("Il piano di rientro non esiste oppure non &egrave; attivo");
			rollback();
			return false;
		} 
		// Determina il tipo movimento da usare per le rate
		$IdMovimentoRata   = getScalar("SELECT IdTipoMovimento FROM tipomovimento WHERE CodTipoMovimento='PDR'");
		if (getLastError()>'') { rollback(); return false;}
		// Somma i movimenti su capitale, interessi, spese recupero, altri addebiti e spese legali 
		$debitoCap = 0+getScalar("SELECT SUM(Importo) FROM movimento WHERE IdContratto=$idContratto"
				." AND IdTipoPartita=$IdPartitaCapitale");		
		if (getLastError()>'') { rollback(); return false;}
		$debitoInt = 0+getScalar("SELECT SUM(Importo) FROM movimento WHERE IdContratto=$idContratto"
				." AND IdTipoPartita IN (SELECT IdTipoPartita FROM tipopartita WHERE CategoriaPartita='I')");
		if (getLastError()>'') { rollback(); return false;}
		$debitoSpe = 0+getScalar("SELECT SUM(Importo) FROM movimento WHERE IdContratto=$idContratto"
				." AND IdTipoPartita IN (SELECT IdTipoPartita FROM tipopartita WHERE CategoriaPartita='R')");
		if (getLastError()>'') { rollback(); return false;}
		$debitoAlt = 0+getScalar("SELECT SUM(Importo) FROM movimento WHERE IdContratto=$idContratto"
				." AND IdTipoPartita IN (SELECT IdTipoPartita FROM tipopartita WHERE CategoriaPartita='A')");
		if (getLastError()>'') { rollback(); return false;}
		$debitoLeg = 0+getScalar("SELECT SUM(Importo) FROM movimento WHERE IdContratto=$idContratto"
				." AND IdTipoPartita IN (SELECT IdTipoPartita FROM tipopartita WHERE CategoriaPartita='L')");
		if (getLastError()>'') { rollback(); return false;}
		// Crea movimenti di storno (formatta i numeri perch� la creaMovimentiAddebito se li aspetta formattati
		$esito = creaMovimentoAddebito($idContratto,date('Y-m-d'),'',
				number_format(-$debitoCap,2,',','.'),
				number_format(-$debitoInt,2,',','.'),
				number_format(-$debitoSpe,2,',','.'),
				number_format(-$debitoAlt,2,',','.'),
				number_format(-$debitoLeg,2,',','.'));
		if ($esito>'') {
			setLastError($esito);
			rollback();
			return false;
		}
		// Crea movimenti di addebito di ciascuna rata
		$rate = getRows("SELECT * FROM ratapiano WHERE IdPianoRientro={$piano['IdPianoRientro']} ORDER BY NumRata");
		if (getLastError()>'') { rollback(); return false;}
		foreach ($rate as $rata) {
			extract($rata);
			$valList = "";
			$colList = "";
			addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
			addInsClause($colList,$valList,"NumRata",$NumRata,"N");
			addInsClause($colList,$valList,"DataRegistrazione", "curdate()","G");
			addInsClause($colList,$valList,"DataCompetenza", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataDocumento", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataValuta", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataScadenza", $DataPrevista,"D");
			addInsClause($colList,$valList,"NumRiga",1,"N");
			addInsClause($colList,$valList,"Importo",$Importo,"N");
			addInsClause($colList,$valList,"IdTipoPartita",$IdPartitaCapitale,"N");   // partita di tipo capitale
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdTipoMovimento",$IdMovimentoRata,"N"); // Movimento di tipo Pagamento
			$ins = "INSERT INTO movimento($colList) VALUES($valList);";
			if(!execute($ins)){
				rollback();
				return false;
			}
			if (!processInsolutiSimple($idContratto)) { // aggiorna situazione
				rollback();
				return "Fallito ricalcolo delle partite aperte";
			}				
		}
	} else if ($tipo=='Annulla') {
		if (!$piano or $piano['IdStatoPiano']!=3) {
			setLastError("Il piano di rientro non esiste oppure non &egrave; annullato");
			return false;
		}
		// Determina il tipo movimento da usare per lo storno rate
		$IdMovimentoRata   = getScalar("SELECT IdTipoMovimento FROM tipomovimento WHERE CodTipoMovimento='SPDR'");
		if (getLastError()>'') { rollback(); return false;}
		
		// Crea movimenti di storno dell'addebito di ciascuna rata
		$rate = getRows("SELECT * FROM ratapiano WHERE IdPianoRientro={$piano['IdPianoRientro']} AND ImpPagato!=Importo ORDER BY NumRata");
		if (getLastError()>'') {
			rollback(); return false;
		}
		$daPagare = 0;
		foreach ($rate as $rata) {
			extract($rata);
			$daPagare += $Importo-$ImpPagato;
			$valList = "";
			$colList = "";
			addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
			addInsClause($colList,$valList,"NumRata",$NumRata,"N");
			addInsClause($colList,$valList,"DataRegistrazione", "curdate()","G");
			addInsClause($colList,$valList,"DataCompetenza", $DataPrevista,"D");
			addInsClause($colList,$valList,"DataDocumento", $DataPrevista,"D");
			addInsClause($colList,$valList,"NumDocumento",'RATA'.str_pad($NumRata,4,'0',STR_PAD_LEFT),"S");
			addInsClause($colList,$valList,"DataValuta", $DataPrevista,"D");
			addInsClause($colList,$valList,"NumRiga",2,"N");
			addInsClause($colList,$valList,"Importo",$ImpPagato-$Importo,"N");
			addInsClause($colList,$valList,"IdTipoPartita",$IdPartitaCapitale,"N");   // partita di tipo capitale
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdTipoMovimento",$IdMovimentoRata,"N"); // Movimento di tipo Pagamento
			$ins = "INSERT INTO movimento($colList) VALUES($valList);";
			if(!execute($ins)){
				rollback();
				return false;
			}
		}
		// Genera un movimento di addebito sul capitale	(include anche l'aggiornamento degli insoluti)
		if ($daPagare!=0) {
			$esito = creaMovimentoAddebito($idContratto,date('Y-m-d'),'',number_format($daPagare,2,',','.'),"0","0","0","0",
					null,null,0,$insids,true,28); // forza il tipo movimento = storno pdr/ss ($insids non serve ma devo passarlo)
			if ($esito>'') {
				setLastError($esito);
				rollback();
				return false;
			}
		}		
	} else {
		setLastError("Il parametro Tipo ha un valore non previsto (generaMovimentiPianoRientro)");
		return false;
	}
	commit();
	return true;
}

/**
 * generaScadenzePerRatePiano
 * Genera una Scadenza per ogni rata di un piano di rientro
 */
function generaScadenzePerRatePiano($IdPianoRientro) {
	$rate = getRows("
		select CodContratto,IFNULL(Nominativo,RagioneSociale) AS Nome,p.IdContratto,r.* 
		from ratapiano r
		JOIN pianorientro p ON r.IdPianoRientro=p.IdPianoRientro
		JOIN contratto c ON c.IdContratto=p.IdContratto
		JOIN cliente cl ON c.IdCliente=cl.IdCliente 
		WHERE r.IdPianoRientro=$IdPianoRientro ORDER BY NumRata");
	if (getLastError()>'')
		return false;
	foreach ($rate as $rata) {
		extract($rata);
		$testo = "Scadenza pagamento ".($TipoRata=='C'?'cambiale':'rata')." n. ".$NumRata." della pratica $CodContratto- $Nome";
		if (!GeneraScadenza(array("TESTOSCADENZA"=>$testo, "DATASCADENZA"=>$DataPrevista),'','',$IdContratto)) {
			return false;
		}
	}
	return true;
}
/**
 * cancellaScadenzePerRatePiano
 * Cancella le Scadenze create per ogni rata di un piano di rientro
 */
function cancellaScadenzePerRatePiano($IdPianoRientro) {
	$IdContratto = getScalar("SELECT IdContratto from pianorientro WHERE IdPianoRientro=$IdPianoRientro");
	if (getLastError()>'')	return false;
	$ids = getColumn("SELECT IdNota FROM nota WHERE IdContratto=$IdContratto && TipoNota='S' AND TestoNota LIKE 'Scadenza pagamento%'");
	if (getLastError()>'')	return false;
	if (count($ids)>0) {
		$ids = join(',',$ids);
		if (execute("DELETE FROM notautente WHERE IdNota IN ($ids)")) {
			if (execute("DELETE FROM nota WHERE IdNota IN ($ids)")) {
				return true;
			}
		}
	} else {
		return true;
	}
	return false;
}
?>
