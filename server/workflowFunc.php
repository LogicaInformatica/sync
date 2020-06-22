<?php
require_once("common.php");
require_once('tcpdf/tcpdf.php');

//==============================================================
//   F U N Z I O N I   P E R   I L   W O R K F L O W
//==============================================================
//---------------------------------------------------------------------------------
// allowedActions
// Crea una lista (separata da virgole) degli IdAzione permessi all'utente corrente
// Opzionalmente, ritorna il numero di azioni consentite nella
// variabile passata come argomento
//----------------------------------------------------------------------------------
function allowedActions(&$count=0,&$added='',$isEmpG=false)
{
	global $context;
	if($isEmpG==='true')
	{
		$functions=getFetchKeyValue("Select * from v_azioni_pratiche_dipendenti","IdFunzione","CodFunzione"); 
	}else{
		$functions = $context["functions"];
	}
	if($added!=''){
		$join = ' left join azionetipoazione ata on a.IdAzione=ata.IdAzione ';
	}

	if (is_array($functions))
	{
		$keys = array_keys($functions);
		$ids = fetchValuesArray("SELECT DISTINCT a.IdAzione FROM azione a $join WHERE IdFunzione IN (".join(",",$keys).")$added");
		if (count($ids)>0)
		{
			
			$count=count($ids);
			return join(",",$ids);
		}
		else
			return "0";
	}
	else
		return "0";
}
//-------------------------------------------------------------------------------------
// getActions
// Crea un array con tutte le azioni possibili su una pratica o un insieme di pratiche
// input:  1. array con i numeri pratiche(idContratto)
//         2. flag che indica se considerare lo stato della pratica (statoRecupero)
//         3. flag che indica se considerare il profilo utente
//         4. flag che indica se si stanno richiedendo le sole azioni del pulsante "note"
// output: array azioni (key=idAzione, value=idStatoAzione)
//-------------------------------------------------------------------------------------
function getActions($Contracts,$consideraStatoRecupero=true,$consideraProfilo=true,$isNote=false)
{
	$sql = "SELECT sa.*, a.TitoloAzione "
	." FROM statoazione sa, azione a"
	." WHERE CURDATE() BETWEEN a.DataIni AND a.DataFin "
	." AND sa.IdAzione = a.IdAzione";
	if ($consideraProfilo)
		$sql .= " AND a.IdAzione IN (".allowedActions().")";
	
	if ($isNote) {
		$sql .= " AND a.IdAzione IN (SELECT IdAzione FROM azionetipoazione WHERE IdTipoAzione IN (12,13))";
	} else {
	//	if (count($Contracts)>1) // pi� di un contratto selezionato?
	//		$sql .= " AND FlagMultipla='Y'"; // filtra solo le azioni che possono essere eseguite su pi� contratti
	}

	if (count($Contracts)>1) // pi� di un contratto selezionato?
		$sql .= " AND FlagMultipla='Y'"; // filtra solo le azioni che possono essere eseguite su pi� contratti
	
	$sql .= " ORDER BY sa.IdAzione,sa.Ordine,sa.IdStatoAzione";
	$actionAll = getFetchArray($sql);
	
	//trace("actionall ".print_r($actionAll,true));
	$actions = array();
	foreach ($Contracts as $contract) // controlla ogni pratica
	{	
		//trace("Controllo azioni contratto $contract",FALSE);
		$actAllowed = actionsAllowedOnContract($actionAll,$contract,$consideraStatoRecupero); // array azioni ammesse su contratto
		//trace(print_r($actAllowed,TRUE),FALSE);
		if ($contract == $Contracts[0])
			$actions = $actAllowed;
		else
			$actions    = array_intersect_key($actions,$actAllowed);      // merge delle azioni consentite 
	}
	return $actions;
}
//-------------------------------------------------------------------------------------
// actionsAllowedOnContract
// Crea un array con tutte le azioni possibili su una data pratica
// input:  1. array con le righe di stato azione da considerare
//         2. Id del contratto
//         3. flag che indica se considerare lo stato della pratica (statoRecupero)
// output: array azioni (key=idAzione, value=idStatoAzione)
// Legge le informazioni su Contratto - Cliente - Prodotto - Famiglia -
//                          Statoazioni - Azioni
//-------------------------------------------------------------------------------------
function actionsAllowedOnContract($actionAll,$contract,$consideraStatoRecupero=true)
{
	global $context; // pu� servire nella Condizione registrata sul DB
	$actions = array();
	$sql = "SELECT 1 FROM v_contratto_workflow v WHERE IdContratto=$contract";
	$lastAddition = "";
	foreach($actionAll as $element) // loop sulle righe di statoAzione
	{
		$IdAzione = $element['IdAzione'];
		if (array_key_exists($IdAzione,$actions)) // azione gi� segnata come consentita
			continue; // passa alla successiva riga di StatoAzione
		
		$addition = "";
		// Test sulla famiglia prodotto: da cambiare se si cicla pi� di due livelli
		if ($element['IdFamiglia']!=null)
			$addition .= " AND (IdFamiglia=".$element['IdFamiglia']." OR IdFamigliaParent=".$element['IdFamiglia'].")";
		if ($element['IdStatoContratto']!=null)
			$addition .= " AND IdStatocontratto=".$element['IdStatoContratto'];
		if ($consideraStatoRecupero && $element['IdStatoRecupero']!=null)
			$addition .= " AND IdStatoRecupero=".$element['IdStatoRecupero'];
		if ($element['IdClasse']!=null)
			$addition .= " AND IdClasse=".$element['IdClasse'];
		if ($element['IdTipoCliente']!=null)
			$addition .= " AND IdTipoCliente=".$element['IdTipoCliente'];
		if ($element['IdCompagnia']!=null)
				$addition .= " AND IdCompagnia=".$element['IdCompagnia'];

		// Se la riga di stato azione prevede una condizione, la aggiunge alla query	
		if ($element['Condizione']!=null && $consideraStatoRecupero==TRUE)
		{
			eval('$condizione = "'.$element['Condizione'].'";'); // usa eval in modo che traduca eventuali var. php interne
			$addition .= " AND ($condizione)"; 
		}
		// Esegue la SELECT sulla pratica condizionata a tutte le condizioni definite per la specifica Azione
		// Se il risultato � non vuoto, include l'IdStatoAzione e il titolo azione nel risultato
		// Se esistono pi� righe per azione in statoAzione, considera solo la prima soddisfatta
		// 2013-12-06: ottimizza non ripetendo la query se � uguale alla precedente OK
		if ($addition=="" || $addition==$lastAddition || getScalar($sql.$addition)==1)  // il contratto soddisfa tutte le condizioni
		{  // azione consentita
			if (!array_key_exists($IdAzione,$actions))
				$actions[$IdAzione] = $element['IdStatoAzione']; // registra il primo IdStatoAzione che soddisfa le condizioni
			if ($addition!=$lastAddition) $lastAddition = $addition; // ricorda ultima condizione soddisfatta
		}
	//	else
	//		trace("Azione $IdAzione non ammessa sul contratto $contract (query: $sql)",FALSE);
	}
	return $actions;
}

//---------------------------------------------------------------------------------------
// readAllActions
// Legge tutte le azioni possibili all'utente, ordinandolo sul tipo azione oppure solo
// sull'azione (se sono meno del valore predefinito MAX_FLAT_MENU)
// Riconosce se si tratta di una nota o meno, nel primo caso carica solo le azioni
// possibili sulla nota altrimenti tutte le altre.
// Riconosce che si tratti anche della griglia sulle pratiche degli operatori in tal caso
// carica solo un certo set di azioni altrimenti tutte le altre.
// 27/6/2012: aggiunte condizioni per escludere le azioni STR e/o LEG
//---------------------------------------------------------------------------------------
function readAllActions(&$flat,$isNote,$isEmGrid,$includiSTR,$includiLEG,$isStorico)
{
	$added = '';
	if ($isStorico) {
		$added = ' AND ata.idTipoAzione=16';
	} else if($isNote){
		$added = ' AND ata.idTipoAzione in (12,13)';
	} else{
		$added = ' AND IFNULL(ata.idTipoAzione,0) not in (12,13,16)';
	}
	
	$ids = allowedActions($count,$added,$isEmGrid);
	$flat = ($count<=MAX_FLAT_MENU);
	if ($flat)
		$sql = "SELECT a.TitoloAzione, a.IdAzione, a.CodAzione"
		." FROM azione a"
		." WHERE a.idAzione IN ($ids)"
		." AND NOW() BETWEEN a.DataIni AND a.DataFin"
		." ORDER BY a.ordine, a.titoloazione";
	else
	{
		if (!$includiSTR) $escludiSTR = " AND ta.IdTipoAzione!=14";
		if (!$includiLEG) $escludiLEG = " AND ta.IdTipoAzione!=15";
		$sql = "SELECT ta.TitoloTipoAzione, ta.idTipoAzione, a.TitoloAzione, a.IdAzione, a.CodAzione"
		." FROM tipoazione ta, azione a, azionetipoazione ata"
		." WHERE ta.idTipoazione=ata.idTipoazione"
		." AND a.idAzione=ata.idAzione"
		.$escludiSTR.$escludiLEG
		." AND a.idAzione IN ($ids)"
		." AND NOW() BETWEEN a.DataIni AND a.DataFin"
		." AND NOW() BETWEEN ata.DataIni AND ata.DataFin"
		." ORDER BY ta.ordine, ata.ordine, a.ordine, a.titoloazione";
	}
	//trace($sql);
	return getFetchArray($sql);
}

//---------------------------------------------------------------------------------------
// getApprovers
// Restituisce un array di utenti che possono approvare (o pi� precisamente, eseguire
// l'azione indicata su) il contratto dato in base alle caratteristiche del contratto 
// e dei profili utente 
// Argomenti: 1) id del contratto  2) codice dell'azione di approvazione
//---------------------------------------------------------------------------------------
function getApprovers($idcontratto,$nextAzione)
{
	$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$idcontratto");
	$utenti  = array();
	if ($pratica)
	{
		$profili = getFetchArray("SELECT pu.*,u.NomeUtente FROM azione a"
					            ." JOIN profilofunzione pf ON a.IdFunzione=pf.IdFunzione"
								." JOIN profiloutente pu ON pu.IdProfilo=pf.IdProfilo"
								." JOIN utente u ON pu.IdUtente=u.IdUtente"
								." WHERE CodAzione='$nextAzione'");
		// per ogni profilo utente controlla che il contratto rientri nei parametri del profiloUtente
		// Se s�, aggiunge all'array restituito
		foreach ($profili as $profutente)
		{
			if ($profutente["IdFamiglia"]) // condizione sulla famiglia di prodotto
				if ($profutente["IdFamiglia"]!=$pratica["IdFamiglia"])
					continue;
			if ($profutente["IdProdotto"]) // condizione sul prodotto
				if ($profutente["IdProdotto"]!=$pratica["IdProdotto"])
					continue;
			if ($profutente["IdCompagnia"]) // condizione sulla compagnia (committente)
				if ($profutente["IdCompagnia"]!=$pratica["IdCompagnia"])
					continue;
			if ($profutente["IdArea"]) // condizione sull'area recupero (= area cliente)
				if ($profutente["IdArea"]!=$pratica["AreaCliente"])
					continue;
			if ($profutente["IdTipoCliente"]) // condizione sul tipo cliente
				if ($profutente["IdTipoCliente"]!=$pratica["IdTipoCliente"])
					continue;
			if ($profutente["IdStatoRecupero"]) // condizione sullo stato del recupero
				if ($profutente["IdStatoRecupero"]!=$pratica["IdStatoRecupero"])
					continue;
			if ($profutente["ImportoMin"])  // condizione su importo minimo
				if ($profutente["ImportoMin"]>$pratica["Importo"])
					continue;
			if ($profutente["ImportoMax"])  // condizione su importo massimo
				if ($profutente["ImportoMax"]<$pratica["Importo"])
					continue;
	
			$utenti[] = array("IdUtente"=>$profutente["IdUtente"],"NomeUtente"=>$profutente["NomeUtente"]);
		}
	}
	if (count($utenti)==0)
		trace("Nessun utente abilitato all'azione di workflow $nextAzione",FALSE);
	return $utenti;
}

//---------------------------------------------------------------------------------------
// getApproversAtStep
// Simile alla getApprovers precedente per determinare gli approvatori sullo stato dato. 
// Restituisce un array di utenti che possono eseguire il passo di iter
// successivo sul contratto dato in base alle caratteristiche del contratto 
// e dei profili utente 
// Argomenti: 1) array degli id di contratto  
//            2) idStato in cui il contratto viene messo (dall'azione
//            che ha chiamato questa funzione per determinare la lista degli approv.)
//---------------------------------------------------------------------------------------
function getApproversAtStep($Contracts,$fromState)
{
	//trace("getApproversAtStep: ".print_r($Contracts,true)." fromState=$fromState",FALSE);
	// legge la lista delle azioni che partono dallo stato dato e portano ad un nuovo stato
	$actionAll = getFetchArray("SELECT * FROM statoazione WHERE IdStatoRecupero=$fromState AND IdStatoRecuperoSuccessivo IS NOT NULL");
//trace("SELECT * FROM statoazione WHERE IdStatoRecupero=$fromState AND IdStatoRecuperoSuccessivo IS NOT NULL");	
	// determina quali azioni sono eseguibili sui contratti dati
	$actions = array();
	foreach ($Contracts as $contract) // controlla ogni pratica
	{	
		$actAllowed = actionsAllowedOnContract($actionAll,$contract,false); // array azioni ammesse su contratto
		if ($contract == $Contracts[0])
			$actions  = $actAllowed;
		else
			$actions  = array_intersect_key($actions,$actAllowed);      // merge delle azioni consentite 
	}
	$IdAzioni = join(",",array_keys($actions)); // lista id azioni per la clausola IN

	$utenti  = array();
	foreach ($Contracts as $idcontratto) // controlla ogni pratica
	{	
		$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$idcontratto");
		if ($pratica)
		{
			$profili = getFetchArray("SELECT pu.*,u.NomeUtente FROM azione a"
					            ." JOIN profilofunzione pf ON a.IdFunzione=pf.IdFunzione"
								." JOIN profiloutente pu ON pu.IdProfilo=pf.IdProfilo"
								." JOIN utente u ON pu.IdUtente=u.IdUtente"
								." WHERE IdAzione IN ($IdAzioni)");
			// per ogni profilo utente/utente abilitato ad una delle azioni ammesse
			// controlla che il contratto rientri nei parametri del profiloUtente
			// Se s�, aggiunge l'utente all'array restituito
			foreach ($profili as $profutente)
			{
				if ($profutente["IdFamiglia"]) // condizione sulla famiglia di prodotto
					if ($profutente["IdFamiglia"]!=$pratica["IdFamiglia"])
						continue;
				if ($profutente["IdProdotto"]) // condizione sul prodotto
					if ($profutente["IdProdotto"]!=$pratica["IdProdotto"])
						continue;
				if ($profutente["IdCompagnia"]) // condizione sulla compagnia (committente)
					if ($profutente["IdCompagnia"]!=$pratica["IdCompagnia"])
						continue;
				if ($profutente["IdArea"]) // condizione sull'area recupero (= area cliente)
					if ($profutente["IdArea"]!=$pratica["AreaCliente"])
						continue;
				if ($profutente["IdTipoCliente"]) // condizione sul tipo cliente
					if ($profutente["IdTipoCliente"]!=$pratica["IdTipoCliente"])
						continue;
				if ($profutente["IdStatoRecupero"]) // condizione sullo stato del recupero
					if ($profutente["IdStatoRecupero"]!=$pratica["IdStatoRecupero"])
						continue;
				if ($profutente["ImportoMin"])  // condizione su importo minimo
					if ($profutente["ImportoMin"]>$pratica["Importo"])
						continue;
				if ($profutente["ImportoMax"])  // condizione su importo massimo
					if ($profutente["ImportoMax"]<$pratica["Importo"])
						continue;
				$utenti[] = array("IdUtente"=>$profutente["IdUtente"],"NomeUtente"=>$profutente["NomeUtente"]);
			}
		}
	}
	if (count($utenti)==0)
	{
		trace("Nessun utente abilitato all'azione di workflow con IdStatoAzione=$fromState",FALSE);
		$utenti[] = array("IdUtente"=>0,"NomeUtente"=>"Nessun utente abilitato all'approvazione");
	}
	return $utenti;
}

//---------------------------------------------------------------------------------------
// readActionsInProc
// Legge le azioni permesse all'utente che fanno parte di una procedura di workflow data
//---------------------------------------------------------------------------------------
function readActionsInProc($codice)
{
	$sql = "SELECT a.TitoloAzione, a.IdAzione, a.CodAzione, p.UrlDocProcedura"
	." FROM azione a, azioneprocedura ap, procedura p"
	." WHERE a.idazione=ap.idazione AND ap.idprocedura=p.idprocedura AND p.codprocedura='$codice'"
	." AND a.idAzione IN (".allowedActions().")"
	." ORDER BY ap.ordine, a.ordine, a.titoloazione";
	//trace("sql: $sql");
	return getFetchArray($sql);
}
//-----------------------------------------------------------------------
// writeHistory
// Inserimento nuovo record storiaRecupero
//-----------------------------------------------------------------------
function writeHistory($idAzione,$descrEvento,$idContratto,$nota,$esito="NULL",$idAzioneSpeciale="NULL",$htmlAzione="NULL",$valoriHtmlAzione="NULL")
{
	try
	{
//		if ($idAzione==28)
//			trace("writehistory",true);
		global $context;
		getUserName($IdUser);
		// Non commentare la riga seguente: serve alle verifiche sull'esecuzione del batch
		trace("IdContratto=$idContratto: $descrEvento",FALSE);
		$descrEvento = quote_smart($descrEvento);
		$nota  = quote_smart($nota);
		if ($esito=="")
			$esito = "NULL";
		if ($idAzioneSpeciale=='') // correzione 10/9/2015
			$idAzioneSpeciale = "NULL";
		$master=$context["master"];
		//trace("master ".$master);
		$varNameS='';
		$IdM='';
		if($master!=''){
			$varNameS=',IdSuper';
			$sqlIdMaster="SELECT IdUtente FROM utente where userid='$master'";
			$IdM = getScalar($sqlIdMaster);
			$IdM=",$IdM";
		}
		
		$sql = "INSERT INTO storiarecupero (IdAzioneSpeciale,IdContratto,IdAzione,DataEvento,"
			  ."IdUtente,DescrEvento,NotaEvento,HtmlAzioneEseguita,ValoriAzioneEseguita,IdTipoEsito$varNameS) "
			  ."VALUES($idAzioneSpeciale,$idContratto,$idAzione,NOW(),$IdUser,$descrEvento,$nota,$htmlAzione,$valoriHtmlAzione,$esito$IdM)";
		return execute($sql);
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//------------------------------------------------------------------------
// getDefaultDate
// Legge l'eventuale data di default dalla tabella Automatismo
// $idazione: id della tabella azione
// $default: una espressione MySql come ad es. "NOW()+INTERVAL 2 DAY", oppure omesso
// &$numGiorni: restituisce il numero di giorni calcolato
//------------------------------------------------------------------------
function getDefaultDate($idazione,$default,&$numGiorni)
{	
	$defaultFromDb = getScalar("SELECT Comando FROM automatismo a, azioneautomatica aa"
	. " WHERE a.idAutomatismo=aa.idAutomatismo AND NOW() BETWEEN aa.DataIni AND aa.DataFin"
	. " AND aa.IdAzione=$idazione AND TipoAutomatismo='scadenzadefault'");

	if ($defaultFromDb) { // trovata una regola sul DB
		$default = $defaultFromDb;
	}
	if ($default=="") { // usa un default generale (sconsigliato) 
		$gSett = date('N');
		$GG = $gSett+3;     // default 3 giorni da adesso, ma il chiamante pu� specificarlo
		switch($GG){
			case 6: $gSett=$GG+2;
				break;
			case 7: $gSett=$GG+1;
				break;
			default: $gSett=3;
		}
		$default = "CURDATE() + INTERVAL $gSett DAY";
	}else{ // c'e' e' una espressione MySql come ad es. "NOW()+INTERVAL 2 DAY" oppure una data come 9999-12-31
		if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/',$default)) { // c'� una data costante, non deve aggiustare nulla (NOTA, la data deve essere tra apici)
			// prosegue
		} else {
			//controllo della caduta del giorno ed aggiustamento
			$arr = explode(' ',$default);
			for($i=0;$i<count($arr);$i++){
				if(is_numeric($arr[$i])){
					$gSett=date('N');
					$GG=$gSett+$arr[$i];
					switch($GG){
						case 6: $arr[$i]=$arr[$i]+2;
							break;
						case 7: $arr[$i]=$arr[$i]+1;
							break;
					}
				}
			}
			$default=implode(' ',$arr);
		}
	}
	
	$row = getRow("SELECT $default AS newdate,DATEDIFF($default,CURDATE()) AS numdays");
	$numGiorni = $row['numdays'];
	return $row['newdate'];
}

//------------------------------------------------------------------------
// getForzaturaAffidoCorrente
// Legge l'eventuale forzatura di affido registrata
//------------------------------------------------------------------------
function getForzaturaAffidoCorrente($IdContratto)
{	
	$row = getRow("SELECT CodRegolaProvvigione,IdAgenzia FROM contratto WHERE IdContratto=$IdContratto");
	if ($row["IdAgenzia"]>0) // correntemente in affido
	{
		$IdForzatura = getScalar("SELECT IdAffidoForzato FROM assegnazione "
					." WHERE IdContratto=$IdContratto AND DataFin>CURDATE()");
	}
	else // non in affido
	{
		$IdForzatura = getScalar("SELECT IdRegolaProvvigione FROM regolaprovvigione "
		  ." WHERE CodRegolaProvvigione='".$row["CodRegolaProvvigione"]."'"
		  ." AND DataFin>CURDATE()");
	}
	return $IdForzatura;
}
	
//-----------------------------------------------------------------------
// assegnaOperatore
// Assegna un contratto ad un nuovo operatore
//-----------------------------------------------------------------------
function assegnaOperatore($contratto,$newop,$writeHist=false)
{
	try
	{
		$userid = getUserName();
			
		$dati = getRow("SELECT CodStatoRecupero,IdOperatore FROM contratto c,statorecupero s WHERE IdContratto=$contratto AND c.IdStatoRecupero=s.IdStatoRecupero");
		// Modifica l'assegnazione corrente su Contratto
		if ($dati["IdOperatore"]!=$newop)
		{
			beginTrans();
			$sql = "UPDATE contratto SET IdOperatore=$newop,LastUser='$userid' WHERE IdContratto=$contratto";
			//trace("assegnaOperatore - Imposta IdOperatore = $newop",FALSE);
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			// Se il contratto non era gi� assegnato e lo stato lo consente, mette in stato OPE
			if ($dati["CodStatoRecupero"]==null || $dati["CodStatoRecupero"]=='NOR')
			{
				$statoOPE = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='OPE'");
				//trace("assegnaOperatore - Imposta stato = $statoOPE",FALSE);
				$sql = "UPDATE contratto SET IdStatoRecupero=$statoOPE,DataCambioStato=CURDATE() WHERE IdContratto=$contratto";
				if (!execute($sql))
				{
					rollback();
					return FALSE;
				}
			}
			commit();
			// se deve, scrive la history
			if ($writeHist)
			{
				$nome = getScalar("SELECT nomeutente FROM utente WHERE IdUtente=$newop");
				writeHistory("NULL","Assegnazione all'operatore $nome",$contratto,"");		
			}
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// assegnaTeam
// Assegna un contratto ad un team (=reparto interno)
//-----------------------------------------------------------------------
function assegnaTeam($contratto,$newop,$writeHist=false)
{
	try
	{
		$userid = getUserName();
			
		$dati = getRow("SELECT CodStatoRecupero,IdTeam FROM contratto c,statorecupero s WHERE IdContratto=$contratto AND c.IdStatoRecupero=s.IdStatoRecupero");
		// Modifica l'assegnazione corrente su Contratto
		if ($dati["IdTeam"]!=$newop)
		{
			beginTrans();
			$sql = "UPDATE contratto SET IdTeam=$newop,LastUser='$userid' WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			// Se il contratto non era gi� assegnato e lo stato lo consente, mette in stato OPE
			if ($dati["CodStatoRecupero"]==null || $dati["CodStatoRecupero"]=='NOR')
			{
				$statoOPE = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='OPE'");
				$sql = "UPDATE contratto SET IdStatoRecupero=$statoOPE,DataCambioStato=CURDATE() WHERE IdContratto=$contratto";
				if (!execute($sql))
				{
					rollback();
					return FALSE;
				}
			}
			commit();
			// se deve, scrive la history
			if ($writeHist)
			{
				$nome = getScalar("SELECT nomeutente FROM utente WHERE IdUtente=$newop");
				writeHistory("NULL","Assegnazione al team $nome",$contratto,"");
			}
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// assegnaAgente
// Assegna un contratto ad un operatore esterno (agente)
//-----------------------------------------------------------------------
function assegnaAgente($contratto,$newop,$writeHist=false)
{
	try
	{
		$userid = getUserName();
			
		$dati = getRow("SELECT IdAgente,DataFineAffido FROM contratto c,statorecupero s WHERE IdContratto=$contratto AND c.IdStatoRecupero=s.IdStatoRecupero");
		// Modifica l'assegnazione corrente su Contratto
		if ( $dati["IdAgente"]!=$newop)
		{
			beginTrans();
			$sql = "UPDATE contratto SET IdAgente=$newop,LastUser='$userid' WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			// Modifica l'agente anche nell'assegnazione
			$sql = "UPDATE assegnazione SET IdAgente=$newop,LastUser='$userid' WHERE IdContratto=$contratto"
			      ." AND DataFin='".ISODate($dati["DataFineAffido"])."'";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			commit();
		}
		// se deve, scrive la history
		if ($writeHist)
		{
			$nome = getScalar("SELECT nomeutente FROM utente WHERE IdUtente=$newop");
			writeHistory("NULL","Assegnazione all'operatore di agenzia $nome",$contratto,"");		
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// affidaAgenzia
// Affida manualmente un contratto ad una agenzia pre-DBT (affido 30 gg)
// Restituisce il nome dell'agenzia affidata
//-----------------------------------------------------------------------
function affidaAgenzia($contratto,$newag,$dataFineAffido,$writeHist=false,$dataInizioAffido=NULL,&$IdProvv=NULL)
{
	try
	{
		if (!$newag)
			$newag = "NULL";
		
		// Se la pratica � in affido su agenzia diversa, la revoca; se � la stessa non fa nulla
		beginTrans();
		$dati = getRow("SELECT IdAgenzia,IdRegolaProvvigione,DataInizioAffido FROM contratto c"
		 ." WHERE IdContratto=$contratto");
		$IdAgenzia = $dati["IdAgenzia"];
		$IdRegolaProvvigione = $dati["IdRegolaProvvigione"];

		$agenzia = getRow("SELECT * FROM v_agenzia WHERE IdAgenzia=$newag"); // dati nuova agenzia

		// agenzia non cambiata e nessuna forzatura cod.provv oppure stesso cod.provv
		$cambiaProvv = FALSE;		
		if ($IdAgenzia==$newag)
		{
			trace("Stessa agenzia, IdProvv='$IdProvv' prima era '$IdRegolaProvvigione'",FALSE);
			if ($IdProvv==$IdRegolaProvvigione)
			{
				trace("Riaffido non effettuato perche' identico all'attuale affido",FALSE);
				commit();
				return $agenzia["TitoloAgenzia"];
			}
			else // stessa agenzia ma forzatura diversa
			{
				trace("Cambio codice provvigione",FALSE);
				$cambiaProvv = TRUE;		
			}
		}
		else if ($IdAgenzia) // attualmente � affidato: revoca, prima di riassegnare
		{
			if (!revocaAgenzia($contratto,TRUE,"REV"))
			{
				rollback();
				return FALSE;
			}
		}
		else // non affidata: aggiorna la riga di assegnazione per lavorazione interna
		{
			if (!execute("UPDATE assegnazione SET DataFin=CURDATE() WHERE IdContratto=$contratto AND IdAgenzia IS NULL AND DataFin='9999-12-31'"))
			{
				rollback();
				return FALSE;
			}
		}
		// Calcola la data finale di affidamento	
		if (!$dataFineAffido) // se data non specificata prende la durata minima di affido da RegolaAssegnazione
		{					  
			$dataFineAffido = getScalar("SELECT CURDATE() + INTERVAL MIN(DurataAssegnazione) DAY FROM regolaassegnazione"
				." WHERE IdReparto=$newag AND DurataAssegnazione>0 AND TipoAssegnazione='2'");
			if (!$dataFineAffido) // default +1 mese
				$dataFineAffido = mktime(0,0,0,date("n")+1,date("j")-1,date("Y"));
		}
		$dataFineAffido = ISODate($dataFineAffido);
		if ($dataInizioAffido==NULL)
			if ($cambiaProvv) // stessa agenzia, cambia solo il cod. provv
			{
				$dataInizioAffido = $dati["DataInizioAffido"];
				trace("Mantiene data inizio affido ".ISODate($dati["DataInizioAffido"]),FALSE);
			}
			else
				$dataInizioAffido = time();
		$dataInizioAffido = ISODate($dataInizioAffido);
		$userid = getUserName();

		// Modifica l'assegnazione corrente su Contratto
		if ($IdProvv>"0") // specificato l'ID di una regola provvigionale
		{
			$CodProvv = getScalar("SELECT CodRegolaProvvigione FROM regolaprovvigione WHERE IdRegolaProvvigione=$IdProvv");
		}
		else // L'utente ha chiesto al motore di determinare il cod. provvigione
		{
			// Determina quale regola provvigionale si applica
			$IdProvv = trovaProvvigioneApplicabile($contratto,$newag,$CodProvv,$dataFineAffido);
			if (!($IdProvv>0))
			{
				setLastError("Non e' definita alcuna regola provvigionale applicabile per l'agenzia ".$agenzia["TitoloAgenzia"]);
				return FALSE;
			}
		}
		trace("affidaAgenzia - Imposta Contratto=$contratto IdAgenzia = $newag,DataInizioAffido=$dataInizioAffido,"
		      ."DataFineAffido='$dataFineAffido' codiceProvv=$CodProvv, IdProvvigione=$IdProvv",FALSE);
		
		// 11/8/2016: se si sta affidando ad una agenzia non per Rinegoziazione, annulla lo StatoRinegoziazione
		$statoRine = $CodProvv=='PR'? '' : ',IdStatoRinegoziazione=NULL';
		
		$sql = "UPDATE contratto SET IdAgenzia=$newag,LastUser='$userid',DataInizioAffido='$dataInizioAffido',FlagForzaSeDBT='N',"
	    	  ."DataFineAffido='$dataFineAffido',IdRegolaProvvigione=$IdProvv,CodRegolaProvvigione='$CodProvv' $statoRine"
	    	  ." WHERE IdContratto=$contratto";
		if (!execute($sql))
		{
			rollback();
			return FALSE;
		}

		// Lo stato AGE, STR1, STR2, LEG viene dedotto dalla regola provv. applicata
		$cstato = getScalar("SELECT CASE WHEN FasciaRecupero IN ('DBT SOFT','DBT HARD','REPO') THEN 'STR1'
		                                WHEN FasciaRecupero = 'DBT STRONG'               THEN 'STR2'
		                                WHEN FasciaRecupero = 'LEGALE'                   THEN 'LEG'
		                                WHEN FasciaRecupero = 'RINE'                     THEN 'AFR'
		                                ELSE 'AGE' 
		                           END FROM regolaprovvigione WHERE IdRegolaProvvigione=$IdProvv");
		$stato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='$cstato'");
		trace("affidaAgenzia - Imposta stato = $cstato",FALSE);
		$sql = "UPDATE contratto SET IdStatoRecupero=$stato,DataCambioStato=CURDATE() WHERE IdContratto=$contratto AND IFNULL(IdStatoRecupero,0)!=$stato";
		if (!execute($sql))
		{
			rollback();
			return FALSE;
		}

		//-----------------------------------------------------------------------------
		// Se � presente una riga di assegnazione per lav.interna la "chiude"
		//-----------------------------------------------------------------------------
		if (!chiudeAffidamentoInterno($contratto))
		{
			rollback();
			return FALSE;
	    }
		
		if ($IdAgenzia!=$newag) // non � solo un cambio cod. provv
		{
			//-----------------------------------------------------------------------------
			// Registra l'affido nella tabella Assegnazione
			//-----------------------------------------------------------------------------
			// Nota dal 13/3/14: SpeseIncasso in v_dettaglio_insoluto non tiene conto delle eventuali spese
			// calcolate nelle rate positivizzate in storiainsoluto, come fa invece la aggiornaCampiDerivati.
			// Ma questo non � un problema qui, perch� si sta iniziano l'affido e non ci sono ancora quindi
			// rate portate in storiainsoluto
			$dati = getRow("SELECT IdOperatore,IdClasse,InteressiMora,SpeseIncasso FROM v_dettaglio_insoluto WHERE IdContratto=$contratto");
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$contratto,"N");	 	
			addInsClause($colList,$valList,"IdAgenzia",$newag,"N");	 	
			addInsClause($colList,$valList,"IdOperatore",$dati["IdOperatore"],"N");	 	
			addInsClause($colList,$valList,"IdClasse",$dati["IdClasse"],"N");	 	
			addInsClause($colList,$valList,"DataIni",$dataInizioAffido,"S");		
			addInsClause($colList,$valList,"DataFin",$dataFineAffido,"S"); 		
			addInsClause($colList,$valList,"LastUser",$userid,"S");
			addInsClause($colList,$valList,"IdRegolaProvvigione",$IdProvv,"N"); 
			addInsClause($colList,$valList,"ImpInteressiMora",$dati["InteressiMora"],"N");
			addInsClause($colList,$valList,"DataInizioAffidoContratto",$dataInizioAffido,"S");		
			addInsClause($colList,$valList,"DataFineAffidoContratto",$dataFineAffido,"S"); 		
			// nonostante il nome, PercSpeseRecupero contiene l'importo delle spese
			addInsClause($colList,$valList,"PercSpeseRecupero",$dati["SpeseIncasso"],"N");
			if (!execute("INSERT INTO assegnazione ($colList) VALUES ($valList)"))
			{
				rollback();
				return FALSE;
			}
			$IdAffidamento = getInsertId(); // ID generato dall'INSERT
			
			//-------------------------------------------------------------------------------------
			// Modifica i campi ImpDebitoIniziale e ImpCapitaleAffidato in "insoluto" per riflettere 
			// il valore attuale (a inizio affido) del debito da recuperare; imposta anche il campo IdAffidamento
			// per collegare gli insoluti all'Assegnazione 
			//-------------------------------------------------------------------------------------
			$sql = "UPDATE insoluto SET ImpDebitoIniziale=ImpInsoluto,ImpCapitaleAffidato=IF(ImpCapitale-ImpPagato>0 AND ImpDebitoIniziale>0,LEAST(ImpCapitale-ImpPagato,ImpDebitoIniziale),0),"
			  ."IdAffidamento=$IdAffidamento WHERE IdContratto=$contratto";
			//trace("Modifica ImpDebitoIniziale e imposta IdAffidamento nelle righe di Insoluto per riflettere il valore attuale (a inizio affido) del debito da recuperare",FALSE);
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			// 30/9/2016: esegue autiomatismi legati all'affido
			eseguiAutomatismiPerAzione('AFF',$contratto); // esegue invio SMS differito o prep. lettere
		}
		else
		{ 
			//-----------------------------------------------------------------------------
			// Cambia solo il codice provvigionale ed (eventualm.) la data fine affido
			//-----------------------------------------------------------------------------
			$setClause = "";
			addSetClause($setClause,"DataFin",$dataFineAffido,"S");
			addSetClause($setClause,"LastUser",$userid,"S");
			addSetClause($setClause,"IdRegolaProvvigione",$IdProvv,"N");
			//echo($sql."<br><br>".$sql2."<br><be>"."UPDATE contratto $setClause WHERE IdContratto=$IdContratto");
			if (!execute("UPDATE assegnazione $setClause WHERE IdContratto=$contratto AND IdAgenzia=$newag AND DataFin>=CURDATE()"))
			{
				rollback();
				return FALSE;
			}
		}
		// se deve, scrive la history
		if ($writeHist)
		{
			writeHistory("NULL","Effettuato affidamento automatico all'agenzia ".$agenzia["TitoloAgenzia"]." ($CodProvv)"
			            ." dal ".italianDate($dataInizioAffido)." al ".italianDate($dataFineAffido),$contratto,"");	
			trace("Regola provvigionale applicata = $IdProvv",FALSE);	
		}
		
		// Se la pratica era in classe EXIT, ricalcola la classificazione corretta
		toglieClasseExit($contratto); 
		
		// Aggiorna i campi derivati, perch� possono essere cambiate i flags per spese e idm
	    if (!aggiornaCampiDerivati($contratto))
		{
			rollback();
			return FALSE;
		}
		
		commit();
		return $agenzia["TitoloAgenzia"];
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// prorogaAgenzia
// Proroga affidamento
// Restituisce il nome dell'agenzia affidata
//-----------------------------------------------------------------------
function prorogaAgenzia($contratto,$data)
{
	try
	{
		$userid = getUserName();
		beginTrans();
		$dati = getRow("SELECT IdAgenzia,TitoloUfficio,DataFineAffido FROM contratto c LEFT JOIN reparto r"
		    ." ON c.IdAgenzia=r.IdReparto WHERE c.IdContratto=$contratto");
		if (!is_array($dati))
			return FALSE; 
		
		$IdAgenzia = $dati["IdAgenzia"];
		if ($IdAgenzia>0)
		{
			beginTrans();
			// Aggiorna la riga di Assegnazione
			$sql = "UPDATE assegnazione SET DataFin='".ISODate($data)."',DataFineAffidoContratto='".ISODate($data)."'"
			      ." WHERE IdContratto=$contratto"
			      ." AND IdAgenzia=$IdAgenzia AND DataFin='".ISODate($dati["DataFineAffido"])."'";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}		
			
			// Aggiorna le righe eventuali positive di StoriaInsoluto
			$sql = "UPDATE storiainsoluto SET DataFineAffido='".ISODate($data)."' WHERE IdContratto=$contratto"
			      ." AND CodAzione='POS' AND IdAgenzia=$IdAgenzia AND DataFineAffido='".ISODate($dati["DataFineAffido"])."'";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}		
					
			// Aggiorna la data fine affido nel contratto
			$sql = "UPDATE contratto SET LastUser='$userid',DataFineAffido='".ISODate($data)."' WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			else
			{
				commit();
				return $dati["TitoloUfficio"];
			}
		}	
		else
			return FALSE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// revocaAgenzia
// Revoca l'affidamento all'agenzia decidendo inoltre se la pratica
// va in stato OPE, ATT, ATS o NOR
// Restituisce il nome dell'agenzia revocata
//-----------------------------------------------------------------------
function revocaAgenzia($contratto,$writeHist=false,$tipoAzione="REV",&$error)
{
	try
	{
		$userid = getUserName();

		$dati = getRow("SELECT TitoloAgenzia,IdOperatore,c.IdClasse,IFNULL(FlagNoAffido,'N') AS FlagNoAffido,ImpInsoluto AS Debito,TitoloAgenzia,"
		              ." IFNULL(cl.FlagRecupero,'N') AS FlagRecupero,"
					  ." c.IdAgenzia,c.IdAgente,DataInizioAffido,DataFineAffido,CodStatoRecupero,c.CodRegolaProvvigione,"
					  ." c.IdStatoRecupero,FlagNoRientro,DataDBT"
					  ." FROM contratto c"
		              ." LEFT JOIN v_agenzia a ON a.IdAgenzia=c.IdAgenzia"
		              ." LEFT JOIN classificazione cl ON cl.IdClasse=c.IdClasse"
		              ." LEFT JOIN regolaprovvigione rp ON c.IdRegolaProvvigione=rp.IdRegolaProvvigione"
		              ." JOIN statorecupero sr ON sr.IdStatoRecupero=c.IdStatoRecupero"
		              ." WHERE c.IdContratto=$contratto");
		$IdAgenzia = $dati["IdAgenzia"];
		if (!($IdAgenzia>0))
		{
			if ($dati["IdAgente"]>0) // potrebbe esserci IdAgente a causa di vecchia gestione flotte in Custom_Delegation
				if (!execute("UPDATE contratto SET IdAgente=NULL,IdRegolaProvvigione=NULL,CodRegolaProvvigione=NULL WHERE IdContratto=$contratto"))
					return FALSE;
			trace($error="Revoca richiesta su pratica non affidata: nessuna operazione eseguita",FALSE);
			return ""; // revoca nulla, non c'� affido
		}
		// Se � un affido senza rientro (gestione legale) non ha effetto
		if ($tipoAzione=="RIE" && $dati["FlagNoRientro"]=='Y')
		{
			trace("Fine affido che non prevede il rientro: pratica lasciata assegnata a ".$dati["TitoloAgenzia"],FALSE);
			return $IdAgenzia;
		}
		
		$CodRegolaProvvigione = $dati["CodRegolaProvvigione"];
		$CodStatoRecupero = $dati["CodStatoRecupero"];
		beginTrans();
		
		//-----------------------------------------------------------------------------
		// Storicizza le rate di insoluto in StoriaInsoluto, senza per� toglierle
		// da Insoluto, visto che non sono chiuse
		//-----------------------------------------------------------------------------
		$rate = fetchValuesArray("SELECT NumRata FROM insoluto WHERE IdContratto=$contratto");
		foreach ($rate AS $NumRata)
		{
			if (!storicizzaInsoluto($contratto,$NumRata,$tipoAzione)) 
			{
				$error = getLastError();
				rollback();
				return FALSE;
			}
		}

		//-------------------------------------------------------------------------------------------------
		// Se � una revoca esplicita, elimina le eventuali positivit� gi� registrate in storia insoluto,
		// trasformandole in righe di revoca (altrimenti l'agenzia continua a vederle come positivit�)
		//-------------------------------------------------------------------------------------------------
		if ($tipoAzione=="REV")
		{
			if (!execute("UPDATE storiainsoluto SET CodAzione='REV',IdAffidamento=NULL WHERE IdContratto=$contratto AND IdAgenzia=".$dati["IdAgenzia"]
			             ." AND DataFineAffido>=CURDATE() AND CodAzione='POS'"))
			{
				$error = getLastError();
				rollback();
				return FALSE;
			}
		}
		
		//-------------------------------------------------------------------------------------------------
		// Se lo stato attuale non � "affidata" (perch� � in uno stato di workflow), la revoca non provoca
		// un cambio di stato, ma cambia il contenuto del campo IdStatoRecuperoPrecedente in modo che rifletta
		// il fatto che la pratica non � pi� in affido
		//-------------------------------------------------------------------------------------------------
		if ($CodStatoRecupero!='AGE' && $CodStatoRecupero!='STR1' && $CodStatoRecupero!='STR2' 
		&& $CodStatoRecupero!='LEG' && $CodStatoRecupero!='AFR') // non in stato semplicemente "affidato"
			$nuovostato = "*";
			
		// Determina la riga di Assegnazione che si sta revocando
		$rowass = getRow("SELECT IdAssegnazione,IdAffidoForzato FROM assegnazione a WHERE IdContratto=$contratto AND IdAgenzia=".$dati["IdAgenzia"]
			." and not exists (select 1 from assegnazione x where x.idcontratto=a.idcontratto and x.idassegnazione>a.idassegnazione)");
		$IdAffidamento = $rowass["IdAssegnazione"];
		$IdAffidoForzato = $rowass["IdAffidoForzato"];
		//-------------------------------------------------------------------------------------------------
		// Se � una revoca esplicita, lo mette in stato di lavorazione interna, in modo che il motore
		// non effettui automaticamente un nuovo affido. In questo caso, viene anche cancellata la riga
		// in assegnazione, che non deve pi� contribuire alla visibilit� n� alle provvigioni
		//-------------------------------------------------------------------------------------------------
		if ($tipoAzione=="REV")
		{
			if ($nuovostato != "*") 	// era in stato semplicemente "affidato"
			{
				if ($IdAffidoForzato!=null && $IdAffidoForzato!=0 && $IdAffidoForzato!=-1) // c'� un forzatura di affido
					$nuovostato = $CodStatoRecupero=='AGE'?'ATT':'ATS'; // mette in attesa
				else // nessuna forzatura di affido					
					$nuovostato = "INT";	// va in lavorazione interna
			}
			else						// era in qualche stato particolare (WF)
				$nuovoStatoPrecedente = "INT"; // registra come stato di ritorno la lav. interna
			if ($IdAffidamento>0)
			{
				// Disassocia le righe di insoluto/StoriaInsoluto da assegnazione
				if (!execute("UPDATE insoluto SET IdAffidamento=NULL WHERE IdAffidamento=$IdAffidamento"))
				{
					$error = getLastError();
					rollback();
					return FALSE;
				}
				if (!execute("UPDATE storiainsoluto SET IdAffidamento=NULL WHERE IdAffidamento=$IdAffidamento"))
				{
					$error = getLastError();
					rollback();
					return FALSE;
				}			
				// Cancella la riga di assegnazione	solo se deve ancora scadere (altrimenti si tratta di revoca su studio legale
				// con data scaduta ma affido ancora in corso, nel qual caso non si deve cancellare l'assegnazione.
				if (!execute("DELETE FROM assegnazione WHERE IdContratto=$contratto AND IdAgenzia=".$dati["IdAgenzia"]
			             ." AND DataFin>=CURDATE()"))
				{
					$error = getLastError();
					rollback();
					return FALSE;
				}
				// Se c'era un affido forzato, deve rimetterlo nel contratto, altrimenti si perde
				if ($IdAffidoForzato!=null && $IdAffidoForzato!=0 && $IdAffidoForzato!=-1)
					forzaAffidoAgenzia($contratto,$IdAffidoForzato,"","NULL",true,false);
			}
		}
		//------------------------------------------------------------
		// Rientro automatico
		//------------------------------------------------------------
		else
		{
			// 1/10/2012: cancella il link tra Insoluto e Assegnazione
			if (!execute("UPDATE insoluto SET IdAffidamento=NULL WHERE IdAffidamento=$IdAffidamento"))
			{
				$error = getLastError();
				rollback();
				return FALSE;
			}
						
			// 1/12/2011: Se richiesto affido forzato, va necessariamente in stato "in attesa di affido"
			if ($IdAffidoForzato>0) // forzatura standard
			{
				trace("Pratica messa in attesa di affido perch� esiste una forzatura",FALSE);
				if ($CodStatoRecupero=="AGE")
					$nuovostato = "ATT"; // messo in attesa perch� sar� forzato
				else
					$nuovostato = "ATS"; // in attesa affido STR/LEG
			}
			else // rientro senza forzature		
			{
				// in stato stragiudiziale o legale
				$strleg = ($CodStatoRecupero=='STR1' || $CodStatoRecupero=='LEG');
				// classe a recupero e con affido: deve essere messa in attesa
				// (basterebbe il secondo test, ma e' successo che qualche riga con flagrecupero=N avesse il NoAffido=Y, invece di NULL
				$inattesa = $dati["FlagRecupero"]=='Y' && $dati["FlagNoAffido"]!='Y'; 
				// in recupero se il debito � maggiore di 26
				$inrecupero = ($dati["Debito"]>=26);
				// classe che prevede il recupero (non ad es. BP <7 giorni o <20gg)
				$classeRecupero = $dati["FlagRecupero"]=='Y';
				
				// indica se � in stato DBT su OCS
				$inDBT = $dati["DataDbt"]>"";
				//$stato = ($CodStatoRecupero=='STR2')?'ATP':($strleg?'ATS':($inrecupero?($inattesa?"ATT":"OPE"):"NOR"));

				// correzione 18/1/13: le pratiche a recupero ma in classe senza affido vanno in stato lavorazione interna
				$stato = $inrecupero?($CodStatoRecupero=='STR2' ? 'ATP'
							                                    :($strleg ? 'ATS'
							                                              : ($inDBT?"ATS"
							                                                       :($inattesa?"ATT"
	    	  						                                                       	  :($classeRecupero?"INT":"OPE")
							                                                       	)
							                                                )
							                                     )
							          )
									:"NOR";
				if ($nuovostato != "*") // in stato semplicemente "affidato" oppure 
					$nuovostato = $stato;
				else // era in qualche stato particolare (WF)
					if ($inDBT && strpos($CodStatoRecupero,"DBT")!==FALSE) // workflow di dbt: pu� uscirne
						$nuovostato = $stato;
					else // altro workflow: ricorda dove andare a fine workflow
						$nuovoStatoPrecedente = $stato;
			}
		}

		if ($nuovostato=="*") // era in qualche stato particolare (WF)
		{
			$nuovostato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='$nuovoStatoPrecedente'");
			//-----------------------------------------------------------------------------
			// Aggiorna il contratto, senza cambiarne lo stato, ma cambiando lo stato
			// per il rientro da workflow
			//-----------------------------------------------------------------------------
			$sql = "UPDATE contratto SET IdAgenzia=NULL,IdAgente=NULL,LastUser='$userid',"
			       ."DataInizioAffido=NULL,DataFineAffido=NULL,IdRegolaProvvigione=NULL,CodRegolaProvvigione=NULL,"
			       ."IdStatoRecuperoPrecedente=$nuovostato WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				$error = getLastError();
				rollback();
				return FALSE;
			}
			trace("Stato della pratica invariato, per revoca di tipo $tipoAzione in stato $CodStatoRecupero",FALSE);
		}
		else
		{
			//-------------------------------------------------------------------------------
			// Se passaggio in stato INT (lavorazione interna), crea il necessario per la
			// gestione (sintesi) 
			//-------------------------------------------------------------------------------
			if ($nuovostato == "INT")
				inizioLavorazioneInterna($contratto);
						
			//-----------------------------------------------------------------------------
			// Determina l' id del nuovo stato 
			//-----------------------------------------------------------------------------
			trace("Modificato stato della pratica, per revoca di tipo $tipoAzione in $nuovostato",FALSE);
			$nuovostato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='$nuovostato'");
			
			//-----------------------------------------------------------------------------
			// Aggiorna il contratto
			//-----------------------------------------------------------------------------
			$sql = "UPDATE contratto SET IdAgenzia=NULL,IdAgente=NULL,IdStatoRecupero=$nuovostato,LastUser='$userid',"
			       ."DataInizioAffido=NULL,DataCambioStato=CURDATE(),DataFineAffido=NULL,IdRegolaProvvigione=NULL,CodRegolaProvvigione=NULL WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				$error = getLastError();
				rollback();
				return FALSE;
			}
			if ($tipoAzione=="REV") // nella revoca manuale provvede a ricalcolare i campi derivati
			{                       // nei rientri invece lo fa il chiamante			
				// Aggiorna i campi derivati, perch� possono essere cambiate i flags per spese e idm
	    		if (!aggiornaCampiDerivati($contratto))
				{
					$error = getLastError();
					rollback();
					return FALSE;
				}
			}		
		}
		// se deve, scrive la history
		if ($writeHist)
		{
			writeHistory("NULL","Revoca automatica affidamento all'agenzia ".$dati["TitoloAgenzia"]." (".$CodRegolaProvvigione.")",$contratto,"");		
			if ($nuovostato == "INT")
				writeHistory("NULL","Contratto portato in lavorazione interna in conseguenza della classificazione attuale",$contratto,"");
		}
		
		// 30/9/2016: esegue eventuali azioni automatiche legate alla revoca (ancellazione msg differiti non inviati)
		eseguiAutomatismiPerAzione('REV',$contratto); 
		
		commit();
		return $dati["TitoloAgenzia"];
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//---------------------------------------------------------------------------------
// rendePositivo
// Mette in stato di positivit� un contratto (per incassi arrivati)
// NB: il parametro $totale indice se � una positivit� totale, che produce
//     la storicizzazione di tutti gli insoluti 
// NB: il beginTran/commit � nelle funzioni chiamanti
//---------------------------------------------------------------------------------
function rendePositivo($contratto,$totale)
{
	try
	{
		$giaPositivo = FALSE;
		$userid = getUserName();
		$stato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='NOR'");
		$classe = getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='POS'");
		$IdStatoCeduto = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='CES'");
		$IdStatoWriteoff = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='WOF'");
		
		$dati = getRow("Select IdAgenzia,IdAgente,DataInizioAffido,DataFineAffido,IdClasse,IdStatoRecupero from contratto where IdContratto = $contratto");

		// Modifica del 18/12/2011: se il contratto � gi� positivo, procede comunque, in modo che la storicizzaInsoluto provveda
		// ad inserire/aggiornare le righe (su un contratto positivo possono arrivare, lasciandolo positivo, nuovi movimenti
		// a debito (<5) o a credito.
		// Modificato il 20/9/16 per evitare infinite registrazioni sui fuori recupero
		if ($classe==$dati["IdClasse"]  // gi�  classe=POS, oppure ceduto/stornato oppure fuori recupero
		or $dati["IdClasse"]=19
		or $IdStatoCeduto==$dati["IdStatoRecupero"] or $IdStatoWriteoff==$dati["IdStatoRecupero"]) 
		{
			trace("Contratto gia' classificato come Positivo. Aggiorna eventuali righe dalla tabella StoriaInsoluto.",FALSE);
			$giaPositivo = TRUE;
		}
		
		//---------------------------------------------------------------------------------
		// Se si tratta di un contratto in lavorazione interna, modifica la data di fine
		// affido nella riga di assegnazione (� 9999-12-31) con la data di oggi
		//---------------------------------------------------------------------------------
		if (!execute("UPDATE assegnazione SET DataFin=CURDATE() WHERE IdContratto=$contratto AND DataFin='9999-12-31'"))
			return FALSE;
		if (getAffectedRows()>0)
			trace("Chiuso record assegnazione per lav.interna in data odierna",FALSE);
		
		//---------------------------------------------------------------------------------
		// Se si tratta di positivit� totale storicizza tutto l'insoluto in StoriaInsoluto
		// se totale=false, significa che � stata chiamata solo per cambiare lo stato del
		// contratto, non per storicizzare
		//---------------------------------------------------------------------------------
		if ($totale)
		{
			$rate = fetchValuesArray("SELECT NumRata FROM insoluto WHERE IdContratto=$contratto");
			foreach ($rate AS $NumRata)
			{
				if (!storicizzaInsoluto($contratto,$NumRata,"POS")) 
				{
					return FALSE;
				}
			}
		}
		//-------------------------------------------------------------------------------------
		// Annulla gli eventuali affidi forzati 
		//-------------------------------------------------------------------------------------
		if (!annullaForzaturaAffido($contratto))
			return FALSE;
		
		//------------------------------------------------------------------------------------------
		// Aggiorna i dati di classificazione,stato e affido ecc. sul contratto
		// NB: la positivit� non altera l'assegnazione all'oper. interno  n� l'affido
		//     (dalla versione 0.9.9)
		// NB2: (24-6-2011) lo stato recupero di affido deve rimanere lo stesso, visto che l'affido
		//      non viene tolto
		// NB3: (05/11/14) viene annullata la Categoria (di Lav.Interna)
		//------------------------------------------------------------------------------------------
		if (!$giaPositivo)
		{
			if ($dati["IdClasse"]==19)  // 6-11-2012: se forzato fuori recupero, non cambia la classe
										//            altrimenti al successivo insoluto diventa di nuovo affidabile
				$classe = 19;	
			if ($dati["IdAgenzia"]>0)
				$sql = "UPDATE contratto SET LastUser='$userid',"
			       ."IdClasse=$classe,DataCambioClasse=CURDATE(),IdCategoria=NULL"
		    	   ." WHERE IdContratto=$contratto";
			else
				$sql = "UPDATE contratto SET IdStatoRecupero=$stato,LastUser='$userid',DataCambioStato=CURDATE(),"
		       	."IdClasse=$classe,DataCambioClasse=CURDATE(),IdCategoria=NULL"
		       	." WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				return FALSE;
			}
			if ($totale)
				$msg = "Passaggio pratica in 'positivo'";
			else
				$msg = "Passaggio pratica in 'positivo' (restano altri addebiti)";
		
			writeHistory("NULL",$msg,$contratto,"");		
		}

		//-----------------------------------------------------------------------------
		// Aggiorna i dati derivati da insoluti sul contratto
		//-----------------------------------------------------------------------------
	    if (!aggiornaCampiDerivati($contratto))
		{
			return FALSE;
		}

		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//---------------------------------------------------------------------------------
// metteInAttesa
// Mette in stato di ATT un contratto se non � affidato ma si trova in una
// classe che prevede l'affido.
//---------------------------------------------------------------------------------
function metteInAttesa($contratto,$forceINT=false)
{
	try
	{
		$userid = getUserName();
		$stato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='ATT'");
		if ($forceINT) {
// correzione del 2/12/2013: se chiamato con force, gli eventuali controlli di ammissibilit� sono gi� inclusi nella
// regola che ha scatenato la chiamata, quindi li semplifica, per non elencare i vari stati in cui l'op � possibile
			$statoOld  = getScalar("Select c.IdStatoRecupero"
		      ." FROM contratto c LEFT JOIN statorecupero sr ON c.IdStatoRecupero=sr.IdStatoRecupero"
		      ." LEFT JOIN classificazione cl ON c.IdClasse=cl.IdClasse "
		      ." WHERE IdContratto = $contratto "
		      ." AND IFNULL(FlagNoAffido,'N')!='Y' AND IFNULL(cl.FlagRecupero,'N')='Y' AND c.IdAgenzia IS NULL");
		} else {
			$statoOld  = getScalar("Select c.IdStatoRecupero"
		      ." FROM contratto c LEFT JOIN statorecupero sr ON c.IdStatoRecupero=sr.IdStatoRecupero"
		      ." LEFT JOIN classificazione cl ON c.IdClasse=cl.IdClasse "
		      ." WHERE IdContratto = $contratto "
		      ." AND IFNULL(CodStatoRecupero,'NOR') IN ('NOR','OPE') AND IFNULL(FlagNoAffido,'N')!='Y' AND IFNULL(cl.FlagRecupero,'N')='Y'"
		      ." AND c.IdAgenzia IS NULL");
		}
		if ($statoOld>0)
		{			
			beginTrans();
			$sql = "UPDATE contratto SET IdStatoRecupero=$stato,LastUser='$userid',DataCambioStato=CURDATE()"
		      	 ." WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			// toglie IdAffidamento dalle eventuali righe di insoluto (� IdAffidamento della lav.Interna)
			$sql = "UPDATE insoluto SET IdAffidamento=NULL WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return FALSE;
			}
			
			// Chiude eventuale affidamento aperto (� affidamento della lav.Interna)
			if (!chiudeAffidamentoInterno($contratto))
			{
				rollback();
				return FALSE;
			}
			// se la classe era EXIT, la rimette a posto
			toglieClasseExit($contratto);
			
			writeHistory("NULL","Pratica messa in stato 'in attesa di affidamento'",$contratto,"");		
			commit();
			return TRUE;
		}
		else 
		{
			return TRUE; // niente da fare, va bene cos�
		}
	} 
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		rollback();
		return FALSE;
	}
}

//-----------------------------------------------------------------------------------------------------------------------
// storicizzaInsoluto
// Scrive una nuova riga in StoriaInsoluto, copiando i dati dal contratto e da insoluto, poi elimina la riga corrispondente
// da "Insoluto"
// Argomenti: 1) Id contratto
//            2) Numero rata
//            3) codice azione convenzionale per indicare il motivo della storicizzazione (RIEntro, REVoca, POSitivit�)
//            4) data inizio affido (opzionale)
//            5) data fine affido (opzionale)
//-----------------------------------------------------------------------------------------------------------------------
function storicizzaInsoluto($contratto,$NumRata,$tipoAzione,$dataApertura=0,$dataChiusura=0)
{
	try
	{
		// Modifica 18/12/2011: non si limita ad inserire, ma aggiorna la riga se gi� esiste, con il principio che 
		// in StoriaInsoluto ci deve essere al massimo una sola riga a parit� di contratto/rata/lotto con l'unica
		// eccezione che una rata positiva pu� rientrare negativa ed essere quindi registrata prima come POS e al
		// rientro come RIE
		//----------------------------------------------------------------------------------------------------------
		// Casi possibili: anzitutto la riga esistente pu� essere solo di tipo POS,
		// quella che arriva pu� essere POS, REV o RIE e pu� trovarsi su insoluto perch� �
		// un addebito arrivato (o tornato) dopo la positivit�, oppure perch� � una riga a saldo zero o
		// a credito, anche questa arrivata (o tornata) dopo la positivit�. ["tornata"] nel senso che tutte
		// le rate non a zero sono scritte su Insoluto durante la ProcessInsoluti se c'� motivo di scriverle
		// Quindi, se � POS si aggiornano i dati; se � REV si aggiorna anche il tipo; se � RIE
		// (rientro automatico di fine affido) ha importanza solo se a debito (altrimenti la
		// riga POS gia' e' stata aggiornata con il credito corrispondente). Se � a debito, e
		// impInsoluto, ImpCapitale e ImpPagato sono identici a quelli della riga POS, non registra nulla;
		// se ImpPagato e' aumentato, lo aggiorna nella riga POS, altrimenti inserisce la riga RIE 
		// ma senza IdAffidamento (in modo che eventuali ulteriori importi a debito siano considerati "viaggianti")
		//----------------------------------------------------------------------------------------------------------
		
		$userid = getUserName();
		
		// legge i dati necessari da insoluto e contratto
		// Non usa v_pratiche, perche' i campi di sintesi nel contratto possono non essere stati ancora aggiornati
		// 29/4/13: aggiunta assegnazione.DataIni che ha la precedenza sulla data inizio affido contratto nel
		// caso di positivita' STR (le chiusre mensili ridefiniscono il periodo di affido ogni mese)
		$dati = getRow("SELECT c.IdOperatore,c.IdAgenzia,c.IdAgente,i.IdTipoInsoluto,i.DataInsoluto AS DataScadenza,i.ImpDebitoIniziale,i.ImpCapitale,i.ImpInteressi,"
						 ."i.ImpAltriAddebiti,i.ImpSpeseRecupero,i.ImpCapitaleAffidato,i.ImpIncassoImproprio,"
						 ."i.ImpPagato,i.ImpInsoluto,IFNULL(a.DataIni,c.DataInizioAffido) AS DataInizioAffido,c.DataFineAffido,i.DataArrivo,i.IdAffidamento,c.IdStatoRecupero"
					     ." FROM contratto c "
					     ." left JOIN assegnazione a on a.IdContratto=c.IdContratto AND a.DataFin=c.DataFineAffido"
					     ." JOIN insoluto i ON c.IdContratto=i.IdContratto WHERE c.IdContratto=$contratto AND i.NumRata=$NumRata");
		if (!is_array($dati)) {
			trace("La query in storicizzaInsoluto per la lettura dei dati dell'insoluto non ha restituito alcuna riga (rata n.$NumRata)",false);
			return true; // evita di mandare in errore il batch
		}
					
		// 22/12/2011: storicizza anche quelli non affidati ma in lavorazione interna)
		if (!($dati["IdAgenzia"]>0 || $dati["IdStatoRecupero"]==13)) // Non storicizzare le positivit� di quelli non affidati n� in lav. interna
			return TRUE;
		
		// Legge riga preesistente
		if ($dati["IdAgenzia"]>0)
		{
			$data = ISODate($dati["DataFineAffido"]);
			$storia = getRow("SELECT * FROM storiainsoluto WHERE IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' AND CodAzione='POS'");
		}
		else // si tratta di storicizzazione pratica in lav. interna, la data fine non c'�
		{
			$storia = getRow("SELECT * FROM storiainsoluto WHERE IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='9999-12-31' AND CodAzione='POS'");
		}
		// Totale pagato, tutto incluso (mentre il campo ImpPagato in Insoluto contiene solo la parte di capitale pagata)
		$impPagato = ($dati["ImpDebitoIniziale"]>$dati["ImpInsoluto"])?round($dati["ImpDebitoIniziale"]-$dati["ImpInsoluto"],2):0;
		if (!is_array($storia)) // non ancora storicizzata
		{
			if ($dati["ImpInsoluto"]==0 && $dati["ImpDebitoIniziale"]==0 && $dati["ImpCapitale"]==0 && $dati["ImpPagato"]==0 && $dati["ImpInteressi"]==0) // riga inutile
				$oper = "";
			else
				$oper = "INS";
		}
		else if ($tipoAzione=="RIE" && $dati["ImpInsoluto"]>0) // esiste, ma questo � un rientro con debito
		{
			if ($dati["ImpDebitoIniziale"]==$storia["ImpInsoluto"] && $dati["ImpCapitale"]==$storia["ImpCapitale"]
			&& $impPagato==$storia["ImpPagato"]) // RIE identico a POS, non serve registrarlo
				$oper = "";
			else if (round($impPagato-$storia["ImpPagato"],2)>0) // importo pagato � aumentato
				$oper = "UPD";
			else // nuovo addebito, diverso da quanto scritto in POS: inserisce ma slegato dall'affido
			{
				// 27/2/2012: invece di fare solo la creazione della riga RIE slegata dall'affido, come faceva finora,
				// elimina la riga POS e scrive una riga RIE regolare
				//$dati["IdAffidamento"] = "";
				if (!execute("DELETE FROM storiainsoluto WHERE IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' AND CodAzione='POS'"))
					return FALSE;
				$oper = "INS";
			}
		}
		else if ($tipoAzione=="RIE" && $dati["ImpInsoluto"]<=0) // esiste, ma questo � un rientro con credito
			$oper =""; // nessuna operazione
		else
			$oper ="UPD";
		
		if ($oper=="INS")
		{			
			trace("Inserimento riga di StoriaInsoluto per IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' - CodAzione=$tipoAzione",FALSE);
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
	
			addInsClause($colList,$valList,"CodAzione",$tipoAzione,"S");	 	
			addInsClause($colList,$valList,"IdOperatore",$dati["IdOperatore"],"N");	 	
			addInsClause($colList,$valList,"IdAgenzia",$dati["IdAgenzia"],"N");	 	
			addInsClause($colList,$valList,"IdAgente",$dati["IdAgente"],"N");	 	
			addInsClause($colList,$valList,"IdContratto",$contratto,"N");	 	
			addInsClause($colList,$valList,"IdTipoInsoluto",$dati["IdTipoInsoluto"],"N");	 	
			addInsClause($colList,$valList,"DataScadenza",$dati["DataScadenza"],"D");
			addInsClause($colList,$valList,"DataArrivo",$dati["DataArrivo"],"D");	 	
			addInsClause($colList,$valList,"IdAffidamento",$dati["IdAffidamento"],"N");
			addInsClause($colList,$valList,"NumRata",$NumRata,"N");	 	
			addInsClause($colList,$valList,"ImpInsoluto",$dati["ImpDebitoIniziale"],"N"); // NB: viene registrato il debito all' inizio affido
// spostato	sopra		$impPagato = ($dati["ImpDebitoIniziale"]>$dati["ImpInsoluto"])?$dati["ImpDebitoIniziale"]-$dati["ImpInsoluto"]:0;
			addInsClause($colList,$valList,"ImpPagato",$impPagato,"N"); // pagato effettivo	tutto incluso
			addInsClause($colList,$valList,"ImpCapitale",$dati["ImpCapitale"],"N");	 	
			addInsClause($colList,$valList,"ImpInteressi",$dati["ImpInteressi"],"N");	 	
			addInsClause($colList,$valList,"ImpSpeseRecupero",$dati["ImpSpeseRecupero"],"N");	 	
			addInsClause($colList,$valList,"ImpAltriAddebiti",$dati["ImpAltriAddebiti"],"N");	 	// dal 26/1/12			
			addInsClause($colList,$valList,"ImpCapitaleDaPagare",$dati["ImpCapitaleAffidato"],"N");	 		
			addInsClause($colList,$valList,"ImpIncassoImproprio",$dati["ImpIncassoImproprio"],"N");	// dal 24/4/2013			
			// Se si tratta di revoca, mette come data di fine affido la data corrente, altrimenti
			// lascia quella originaria	
			if ($tipoAzione=='REV')
			{
				addInsClause($colList,$valList,"DataInizioAffido",$dati["DataInizioAffido"],"D");	 
				addInsClause($colList,$valList,"DataFineAffido","CURDATE()","G");
			}
			else if ($dataApertura!=0) // chiamato da chiusure mensili: deve alterare le date di inizio e fine
			{
				addInsClause($colList,$valList,"DataInizioAffido",$dataApertura,"D");	 
				addInsClause($colList,$valList,"DataFineAffido",$dataChiusura,"D");	
			}	 	
			else if ($dati["DataFineAffido"]>'')
			{
				addInsClause($colList,$valList,"DataInizioAffido",$dati["DataInizioAffido"],"D");	 
				addInsClause($colList,$valList,"DataFineAffido",$dati["DataFineAffido"],"D");	
			} 	
			else // storicizzazione lav. interna: data fine=infinita
			{
				addInsClause($colList,$valList,"DataFineAffido","9999-12-31","S");	
				$dataInizio = getScalar("SELECT DataIni FROM assegnazione WHERE IdContratto=$contratto AND IdAgenzia IS NULL AND DataFin='9999-12-31'");
				addInsClause($colList,$valList,"DataInizioAffido",$dataInizio,"D");	
			} 	

			addInsClause($colList,$valList,"DataStorico","CURDATE()","G");	 	
			addInsClause($colList,$valList,"LastUser",$userid,"S");
			if (!execute("INSERT INTO storiainsoluto ($colList) VALUES ($valList)")) {
				trace("Errore in INSERT",false);
				return FALSE;
			}
		}
		//----------------------------------------------------------------------------
		// Riga gi� storicizzata, aggiorna
		//----------------------------------------------------------------------------
		else if ($oper=="UPD")
		{
			$setClause ="";
			addSetClause($setClause,"CodAzione",$tipoAzione,"S"); // POS su POS oppure REV su POS
			addSetClause($setClause,"IdTipoInsoluto",$dati["IdTipoInsoluto"],"N");	 	
			addSetClause($setClause,"DataScadenza",$dati["DataScadenza"],"D");
			//IdAffidamento non deve cambiare
			//addSetClause($setClause,"IdAffidamento",$dati["IdAffidamento"],"N");
			// ImpInsoluto non cambia perch� contiene il debito all'inizio dell'affido			
			// ImpCapitaleDaPagare non cambia perch� contiene il capitale da pagare all'inizio dell'affido			
			addSetClause($setClause,"ImpCapitale",$dati["ImpCapitale"],"N");	 // questo pu� cambiare per successivi addebiti	
			addSetClause($setClause,"ImpInteressi",$dati["ImpInteressi"],"N");	 	
			addSetClause($setClause,"ImpSpeseRecupero",$dati["ImpSpeseRecupero"],"N");	 	
			// nella nuova gestione la riga di insoluto contiene sempre il debitoiniziale corretto anche quando � una
			// nuova riga successiva a storicizzazione, ma comunque usando il debitoinsoluto di storiainsoluto (che si chiama ImpInsoluto)
			// si � sicuri di avere il debito iniziale originario
			$impPagato = ($storia["ImpInsoluto"]>$dati["ImpInsoluto"])?$storia["ImpInsoluto"]-$dati["ImpInsoluto"]:0;
			trace("Importo pagato=$impPagato calcolato come differenza tra debito all'affido (".$storia["ImpInsoluto"]
					.") e insoluto attuale (".$dati["ImpInsoluto"].")",FALSE);
			addSetClause($setClause,"ImpPagato",$impPagato,"N"); // pagato effettivo	
			addSetClause($setClause,"ImpIncassoImproprio",$dati["ImpIncassoImproprio"],"N");	// dal 24/4/2013			
			
			// Se si tratta di revoca, mette come data di fine affido la data corrente, altrimenti
			// lascia quella originaria	
			if ($tipoAzione=='REV')
				addSetClause($setClause,"DataFineAffido","CURDATE()","G");	 	

			addSetClause($setClause,"LastUser",$userid,"S");
			if (!execute("UPDATE storiainsoluto $setClause WHERE IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' AND CodAzione='POS'"))
			{
				return FALSE;
			}
			trace("Aggiornamento riga di StoriaInsoluto per IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' - CodAzione=$tipoAzione",FALSE);
		}
		else // rientro con credito, nessuna operazione, perch� aggiornamento deve gi� essere avvenuto per codazione=POS
			trace("Aggiornamento ignorato riga di StoriaInsoluto per IdContratto=$contratto AND NumRata=$NumRata AND DataFineAffido='$data' - CodAzione=$tipoAzione",FALSE);
		
		//----------------------------------------------------------------------------
		// Se la storicizzazione � dovuta al passaggio in positivit�, 
		// cancella la riga da Insoluto. Se la riga ha un credito, verr� poi ricreata
		// in Insoluto dalla processAndClassify, quando necessario, ma fino ad allora
		// non serve mantenerla anche l�
		//----------------------------------------------------------------------------
		if ($tipoAzione=="POS")
		{
			if (!execute("DELETE FROM insoluto WHERE IdContratto=$contratto AND NumRata=$NumRata")) {
				trace("Errore in INSERT",false);
				return FALSE;
			}
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// aggiornaCampiDerivati
// Aggiorna sul contratto i campi derivati da insoluti
//-----------------------------------------------------------------------
function aggiornaCampiDerivati($IdContratto)
{
	try
	{
		$impGiaPagato = getScalar("SELECT SUM(GREATEST(LEAST(ImpPagato-ImpIncassoImproprio,ImpCapitaleDaPagare),0))
			                     FROM storiainsoluto
			                     WHERE CodAzione='POS' AND IdContratto=$IdContratto AND DataFineAffido>CURDATE()");
		if (!($impGiaPagato>0))
			$impGiaPagato = 0;

			
		// Ottimizzazione luglio 2014: precalcola i campi delle regole di ripartizione
		$sql = "SELECT IFNULL(rr.flagInteressimora,rrn.flaginteressimora) AS FlagInteressiMora,
		               CASE WHEN rr.IdRegolaRipartizione IS NOT NULL THEN rr.impspeseincasso
		                    WHEN rrn.IdRegolaRipartizione IS NOT NULL and c.IdAgenzia IS NULL THEN rrn.impspeseincasso
		                    ELSE 0 END AS ImpSpeseIncasso,
		               CASE WHEN rr.IdRegolaRipartizione IS NOT NULL THEN rr.percspeseincasso
		                    WHEN rrn.IdRegolaRipartizione IS NOT NULL and c.IdAgenzia IS NULL THEN rrn.percspeseincasso
		                    ELSE 0 END AS PercSpeseIncasso
		        FROM contratto c
		        LEFT JOIN regolaprovvigione rp ON rp.IdRegolaProvvigione=c.IdRegolaProvvigione
				LEFT JOIN regolaripartizione rr on rr.Idregolaprovvigione=rp.IdRegolaProvvigione AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rr.DataIni AND rr.DataFin
				LEFT join regolaripartizione rrn on rrn.idclasse=c.idclasse and rrn.idreparto is null AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rrn.DataIni AND rrn.DataFin
				WHERE c.IdContratto=$IdContratto";
		$row = getRow($sql);
		$FlagInteressiMora  = $row["FlagInteressiMora"];
		$ImpSpeseIncasso 	= $row["ImpSpeseIncasso"];
		$PercSpeseIncasso   = $row["PercSpeseIncasso"];
		
		$exprIntMora =  ($FlagInteressiMora=='Y')?"c.impinteressimora+SUM(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpInteressi,0))":"0";
		
		
		$sql = "select c.IdContratto,c.IdOperatore,c.IdAgenzia,c.idagente,
			sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpCapitale-i.impPagato,
			       case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale)
			            when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) THEN 0
			            else i.ImpInsoluto
			       end
			      )
			    ) as Capitale, #questo � il capitale da pagare ancora
			$exprIntMora as InteressiMora,
			SUM(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpInteressi,0)) AS InteressiMoraAddebitati,
			sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpAltriAddebiti,
			       case when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) then i.impinsoluto
 			when i.NumRata!=0 and i.ImpCapitale>=i.ImpPagato and i.ImpCapitale-i.ImpPagato<i.ImpInsoluto then i.ImpInsoluto-(i.ImpCapitale-i.ImpPagato)
			       else 0
			       end
			      )
			   ) as AltriAddebiti,
			c.ImpSpeseRecupero AS Speseincasso, # le copia dall'esistente per il primo update di ImpInsoluto
			c.IdClasse,
			MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>5,i.NumRata,NULL)) AS NumRata,
			sum(case when i.ImpCapitale>0 AND i.ImpInsoluto>5 then 1 else 0 end) AS NumInsoluti,
			
			### modificato 19/4/13:  sum(i.ImpDebitoIniziale)-sum(i.ImpInsoluto) as ImpPagato,
			SUM(IF(i.ImpDebitoIniziale>i.ImpInsoluto,GREATEST(LEAST(i.ImpDebitoIniziale-i.ImpInsoluto-i.ImpIncassoImproprio,i.ImpCapitaleAffidato),0),0))  
			+$impGiaPagato as ImpPagato,
			
			MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>5,i.DataInsoluto,NULL)) as DataRata,
			IF(i.IdContratto IS NOT NULL,'Y','N') AS EsisteInsoluto
			from contratto c
			left join assegnazione a on c.idcontratto=a.idcontratto and c.datafineaffido=a.datafin
			left join insoluto i on i.idContratto=c.idContratto
			where c.idcontratto=$IdContratto";
	    $row = getRow($sql);
		if (!is_array($row))
		{
			trace("Non riuscita lettura dati dalla view v_dettaglio_insoluto per aggiornamento campi derivati",FALSE);
			return FALSE;   
		}
		// 7/1/13: cambiata query con una  pi� veloce
		/*
	    $sql2 = "select max(datacompetenza)"
 				." from"
     			." (SELECT idcontratto,numrata FROM insoluto"
      			." UNION"
      			." SELECT idcontratto,numrata FROM storiainsoluto) i"
 				." join movimento m on i.idcontratto=m.idcontratto and i.numrata=m.numrata"
 				." join tipomovimento tm on m.idtipomovimento=tm.idtipomovimento"
				." where m.idcontratto=$IdContratto and categoriaMovimento = 'P'"; 
	    */
		$sql2 = "select datacompetenza from movimento m
				 join tipomovimento tm on m.idtipomovimento=tm.idtipomovimento and categoriaMovimento = 'P'
				 left join storiainsoluto s on s.idcontratto=m.idcontratto and s.numrata=m.numrata
				 left join insoluto i on i.idcontratto=m.idcontratto and i.numrata=m.numrata
				 where importo<0 and (s.idcontratto is not null or i.idcontratto is not null)
				 and m.idcontratto=$IdContratto order by 1 desc limit 1";
	    $DataUltimoPagamento = getscalar($sql2);

	    $ImpInsoluto = $row["Capitale"]+$row["InteressiMora"]+$row["AltriAddebiti"]+$row["Speseincasso"];
	   // echo("interessiMora:".$row["InteressiMora"]);

		$setClause ="";
		addSetClause($setClause,"NumRata",$row["NumRata"],"N");
		addSetClause($setClause,"NumInsoluti",$row["NumInsoluti"],"N");
		addSetClause($setClause,"ImpInsoluto",$ImpInsoluto,"N");
		addSetClause($setClause,"ImpPagato",$row["ImpPagato"],"N");
		addSetClause($setClause,"DataRata",$row["DataRata"],"D");
		addSetClause($setClause,"ImpCapitale",$row["Capitale"],"N");
		addSetClause($setClause,"ImpAltriAddebiti",$row["AltriAddebiti"],"N");
		//vedi commento nella query soprastante
		//addSetClause($setClause,"ImpSpeseRecupero",$row["Speseincasso"],"N");
		addSetClause($setClause,"ImpInteressiMoraAddebitati",$row["InteressiMoraAddebitati"],"N");
		addSetClause($setClause,"DataUltimoPagamento",$DataUltimoPagamento,"D");
		//echo($sql."<br><br>".$sql2."<br><be>"."UPDATE contratto $setClause WHERE IdContratto=$IdContratto");
		
		if (execute("UPDATE contratto $setClause WHERE IdContratto=$IdContratto"))	{
			//if (getAffectedRows()>0) {
				if (!aggiornaSpeseRecupero($IdContratto,$row["EsisteInsoluto"],$FlagInteressiMora,$ImpSpeseIncasso,$PercSpeseIncasso)) 
					return false;	
				trace("Aggiornati campi calcolati nel contratto id=$IdContratto",FALSE);
			//}
			return TRUE;
		} else
			return FALSE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------------------------------------
// aggiornaSpeseRecupero
// Aggiorna il campo ImpSpeseRecupero nel contratto, tenendo conto anche degli insoluti eventualmente
// gi� storicizzati perch� diventati positivi nel periodo di affido
//-----------------------------------------------------------------------------------------------------
function aggiornaSpeseRecupero($IdContratto,$esisteInsoluto,$FlagInteressiMora,$ImpSpeseIncasso,$PercSpeseIncasso)
{
	if ($esisteInsoluto=='Y') {
		$innerJoin = "(
				SELECT IdContratto,ImpAltriAddebiti,ImpCapitaleAffidato,NumRata,ImpPagato,ImpCapitale,ImpDebitoIniziale,ImpSpeseRecupero,
                       ImpInsoluto,DataInsoluto,ImpInteressi,ImpIncassoImproprio
                FROM insoluto WHERE IdContratto=$IdContratto
                UNION ALL
                select si.IdContratto,si.ImpAltriAddebiti,ImpCapitaleDaPagare,si.NumRata,si.ImpPagato,si.ImpCapitale,si.ImpInsoluto,si.ImpSpeseRecupero,
                       0,DataScadenza,ImpInteressi,ImpIncassoImproprio
                FROM storiainsoluto si
                WHERE si.CodAzione='POS' AND si.ImpIncassoImproprio=0 AND IdAffidamento>0 AND (si.ImpCapitaleDaPagare>0 OR ImpInteressi>0)
                AND DataFineAffido>=CURDATE() AND IdAgenzia>0
                AND NOT EXISTS (SELECT 1 FROM insoluto x WHERE si.idContratto=x.IdContratto AND si.NumRata=x.NumRata)
            )";
	} else {
		$innerJoin = "(select si.IdContratto,si.ImpAltriAddebiti,ImpCapitaleDaPagare AS ImpCapitaleAffidato,si.NumRata,si.ImpPagato,
					  si.ImpCapitale,si.ImpInsoluto AS ImpDebitoIniziale,si.ImpSpeseRecupero,
                      0 AS ImpInsoluto,DataScadenza AS DataInsoluto,ImpInteressi,ImpIncassoImproprio
                FROM storiainsoluto si
                WHERE IdContratto=$IdContratto AND si.CodAzione='POS' AND si.ImpIncassoImproprio=0 AND IdAffidamento>0 AND (si.ImpCapitaleDaPagare>0 OR ImpInteressi>0)
                AND DataFineAffido>=CURDATE() AND IdAgenzia>0)";
	}

	$exprIntMora =  ($FlagInteressiMora=='Y')?"c.impinteressimora+SUM(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpInteressi,0))":"0";
	if ($ImpSpeseIncasso>="0") {
		$exprSpese = $ImpSpeseIncasso;
	} else if ($PercSpeseIncasso>"0") {
		$exprSpese = "round($PercSpeseIncasso*sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpCapitaleAffidato,
				      		IF(i.numrata!=0 AND i.impPagato<=i.impCapitale,impDebitoIniziale,0)))/100,2)";
	} else
		$exprSpese = "0";
	$sql = "SELECT GREATEST(0,SUM(IFNULL(i.ImpSpeseRecupero,0))) + $exprSpese - ifnull(a.impspeserecuperopagate,0) as SpeseRecupero,
			$exprIntMora as InteressiMora
			from contratto c
			left join assegnazione a on c.idcontratto=a.idcontratto and c.datafineaffido=a.datafin
			left join $innerJoin i on i.idContratto=c.idContratto
			WHERE c.IdContratto=$IdContratto";
	$row = getRow($sql);
	$spese = $row["SpeseRecupero"];
	if (!$spese)
		$spese = 0; 
	$int   = $row["InteressiMora"];
	if (!$int)
		$int = 0;
	return execute("UPDATE contratto SET ImpInsoluto=$int+$spese+ImpAltriAddebiti+ImpCapitale,ImpSpeseRecupero=$spese WHERE IdContratto=$IdContratto");	
}

//-----------------------------------------------------------------------
// segnaRecidivo
// Imposta  FlagRecupero=Y nel contratto se esiste pi� di un movimento
// di tipo "Insoluto" (il flag viene anche impostato da updateClass,
// la prima volta che entra in una classe di recupero, ma questa funzione
// � necessaria per individuare i contratti con una storia di insoluti
// alle spalle).
//-----------------------------------------------------------------------
function segnaRecidivo($IdContratto)
{
	try
	{
		$flag = getScalar("SELECT FlagRecupero FROM contratto WHERE IdContratto=$IdContratto");
		
		// conta gli insoluti nell'intero partitario (movimenti di tipo X espliciti)
		$numInsoluti1 = getScalar("SELECT COUNT(DISTINCT NumRata) FROM movimento m,tipomovimento tm"
				." WHERE m.IdTipoMovimento=tm.IdTipoMovimento and CategoriaMovimento='X' AND IdContratto=$IdContratto");

		// conta gli insoluti dalle tabelle insoluto e StoriaInsoluto, per tener conto dei BP, che non hanno movimenti "X" espliciti
		$numInsoluti2 = getScalar("SELECT COUNT(*) FROM (SELECT DISTINCT NumRata FROM storiainsoluto WHERE IdContratto=$IdContratto AND ImpCapitaleDaPagare>0 AND NumRata!=0"
								 ." UNION SELECT DISTINCT NumRata FROM insoluto WHERE IdContratto=$IdContratto and ImpCapitale>0 AND ImpInsoluto>5 AND numrata!=0) X");
		if ($numInsoluti1>1 || $numInsoluti2>1) // pi� di un insoluto
		{
		 	if ($flag!="Y") // non ha gi� il flag che indica recidivo
			{
				trace("Contratto=$IdContratto - imposta FlagRecupero=Y (recidivo) [movimenti di insoluto=$numInsoluti1, rate insolute trattate in cnc=$numInsoluti2]",FALSE);
				return execute("UPDATE contratto SET FlagRecupero='Y' WHERE IdContratto=$IdContratto");
			}
		}
		else // solo un insoluto
		{ 
			if ($flag=="Y")
			{
				trace("Contratto=$IdContratto - imposta FlagRecupero=N (non recidivo) [movimenti di insoluto=$numInsoluti1, rate insolute trattate in cnc=$numInsoluti2]",FALSE);
				return execute("UPDATE contratto SET FlagRecupero='N' WHERE IdContratto=$IdContratto");
			}
		}
		trace("Contratto=$IdContratto - FlagRecupero invariato [movimenti di insoluto=$numInsoluti1, rate insolute trattate in cnc=$numInsoluti2]",FALSE);
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// chiudiLavorazione
// Chiude la lavorazione della pratica
//-----------------------------------------------------------------------
function chiudiLavorazione($contratto,$esito)
{
	try
	{
		$userid = getUserName();
		$stato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='CLO'");
		//if ($esito=='P') // esito positivo
		//{
		// 	$classe = getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='POS'");
		//	$sql = "UPDATE contratto SET IdStatoRecupero=$stato,IdClasse=$classe,LastUser='$userid',"
		//	." DataCambioStato=CURDATE(),DataCambioClasse=CURDATE() WHERE IdContratto=$contratto";
		//}
		//else
		//{
			$sql = "UPDATE contratto SET IdStatoRecupero=$stato,LastUser='$userid',DataCambioStato=CURDATE() WHERE IdContratto=$contratto";
		//}
		if (!execute($sql))
			return FALSE;
		else
			return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//----------------------------------------------------------------------------------------------
// allegaDocumento
// Allega un documento 
// Argomenti: 1) riga di v_pratiche oppure IdContratto
//            2) tipo allegato
//            3) titolo allegato
//            4) flag riservato
// NB: il campo di upload deve chiamarsi "docPath"
// Ritorna:   IdAllegato della nuova riga oppure false
//----------------------------------------------------------------------------------------------
function allegaDocumento($pratica,$idtipo,$titolo,$riservato,$fileName='docPath',$idImportLog="NULL")
{
	
	try
	{
		global $context;
		
		if (!is_array($pratica))
			$pratica = getRow("SELECT * from v_pratiche WHERE IdContratto=$pratica");
		$tmpName  = $_FILES[$fileName]['tmp_name'];
		$fileName = $_FILES[$fileName]['name'];
		$fileSize = $_FILES[$fileName]['size'];
		$fileType = $_FILES[$fileName]['type'];
		
		$fileName=urldecode($fileName);
				
		if(!get_magic_quotes_gpc())
			$fileName = addslashes($fileName);
		
		// 14/8/2011: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
		// del processo corrente
		if (function_exists('posix_getpwuid')) {
		$processUser = posix_getpwuid(posix_geteuid());
		$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$processUser['name']."/".$pratica['CodContratto'];
		} else {	
			$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$pratica['CodContratto'];
		}
		if (!file_exists($localDir)) { // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
				setLastError("Impossibile creare la cartella $localDir");
				trace("Impossibile creare la cartella $localDir");
				return FALSE;
			}		
		}
        		
        // 2018-08-31 Se un allegato con lo stesso file name esiste già nella tabella "allegato", fa in modo di generare un nuovo
        // filename univoco
		$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fileName);			
		$IdContratto = $pratica['IdContratto'];
        if (rowExistsInTable("allegato","IdContratto=$IdContratto AND UrlAllegato=$url")) {
            $fileName = pathinfo($fileName,PATHINFO_FILENAME).'_'.date('YmdHis')."_".pathinfo($fileName,PATHINFO_EXTENSION);
    		$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fileName);			
        }   
		       
		if (move_uploaded_file ($tmpName, $localDir."/".$fileName))
		{
			chmod($localDir."/".$fileName,0777);
			$titolo = quote_smart($titolo);
			$IdContratto = $pratica['IdContratto'];
			$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fileName);			
			$userid = getUserName($IdUtente);
			$userid = quote_smart($userid);
			$riservato = quote_smart($riservato);
			
			$master=$context["master"];
			//trace("master ".$master);
			$varNameS='';
			$Master='';
			if($master!=''){
				$varNameS=',lastSuper';
				$Master=",'$master'";
			}
			
			$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,"
								." IdUtente,LastUser, IdTipoAllegato, FlagRiservato,IdImportLog$varNameS) "
								."VALUES($IdContratto,$titolo,$url,$IdUtente,$userid,$idtipo,$riservato,$idImportLog$Master)"; 
//			trace("sql $sql");
			$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
			writeHistory($idAzione,"Allegato documento",$pratica['IdContratto'],"Documento: $titolo Contratto:".$pratica['CodContratto']);				
			if (execute($sql)) {
				return getInsertId();
			} else {
				return false;
			}
				
		}
		else
		{
			setLastError("Impossibile copiare il file nel repository");
			trace("Impossibile copiare il file nel repository");
			return FALSE;
		}
		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}	
}


//----------------------------------------------------------------------------------------------
// creaDocumentoAllegato
// Crea ed allega un documento di tipo allegato 
// Argomenti: 1) id contratto
//            2) testo dell'allegato
//            3) formato es. ".html"
//            4) flag riservato
//			  5) il nome del file	
//----------------------------------------------------------------------------------------------
function creaDocumentoAllegato($IdContratto,$testo,$formato,$riservato,$fileName,$idTipoAllegato)
{
	$arr = getRow("SELECT IdCompagnia,CodContratto from contratto where IdContratto=$IdContratto");
	
	try
	{
		global $context;
		$localDir=ATT_PATH."/".$arr[IdCompagnia]."/".$arr[CodContratto];
		if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
				Throw new Exception("Impossibile creare la cartella dei documenti");				
		
		$file = $fileName."_".date('Y_m_j-H_i_s').".$formato";
		
		if (file_put_contents(ATT_PATH."/".$arr[IdCompagnia]."/".$arr[CodContratto]."/".$file,$testo))
		{
			$titolo = quote_smart($fileName);
			$url = quote_smart(REL_PATH."/".$arr[IdCompagnia]."/".$arr[CodContratto]."/".$file);
			$userid = getUserName($IdUtente);
			$userid = quote_smart($userid);
			$riservato = quote_smart($riservato);
			
			$master=$context["master"];
			//trace("master ".$master);
			$varNameS='';
			$Master='';
			if($master!=''){
				$varNameS=',lastSuper';
				$Master=",$master";
			}
			
			$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,"
								." IdUtente,LastUser, IdTipoAllegato, FlagRiservato$varNameS) "
								."VALUES($IdContratto,$titolo,$url,$IdUtente,$userid,$idTipoAllegato,$riservato$Master)"; 
							
			return execute($sql);
		}
		else
		{
			setLastError("Impossibile allegare il documento");
			trace("Impossibile allegare il documento");
			return FALSE;
		}
		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}	
}

//----------------------------------------------------------------------------------------------
// eseguiAzione
// Esegue tutte le azioni correlate all'azione selezionata, inoltre cambia
// lo stato del contratto in funzione dei valori idClasseSuccessiva, idStatoRecuperoSuccessivo
// presenti sulla tabella StatoAzione
//----------------------------------------------------------------------------------------------
function eseguiAzione($idStatoAzione,$contratto,$parameters)
{
	try
	{
		$userid = getUserName();
		beginTrans(); // inizio transazione
		// Modifica lo stato del contratto
		if (!cambiaStato($idStatoAzione,$contratto))
		{
			trace("Errore nella cambiaStato",FALSE);
			rollback();
			return FALSE;
		}
		$idAzione = getScalar("SELECT IdAzione FROM statoazione WHERE IdStatoAzione=$idStatoAzione");
		if (!eseguiAutomatismi($idAzione,$contratto,$parameters))
		{
			trace("Errore nella eseguiAutomatismi",FALSE);
			rollback();
			return FALSE;
		}
		commit();
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		rollback();
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// forzaFuoriRecupero
// Mette il contratto in stato CLO e classificazione=EXIT per evitare
// che il contratto venga mai riprocessato
//-----------------------------------------------------------------------
function forzaFuoriRecupero($contratto)
{
	try
	{
		$userid = getUserName();
		// dal 10/7/2012: non cambia stato, ma solo classe
//		$IdStato  = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='CLO'");
		$IdClasse = getScalar("SELECT IdClasse FROM classificazione WHERE CodClasse='EXIT'");
	
		revocaAgenzia($contratto,TRUE);
		
		//$sql = "UPDATE contratto SET IdStatoRecupero=$IdStato,IdClasse=$IdClasse,DataCambioStato=CURDATE(),"
		//	.  "DataCambioClasse=NOW(),LastUser='$userid' WHERE IdContratto=$contratto";
		$sql = "UPDATE contratto SET IdClasse=$IdClasse,DataCambioClasse=NOW(),LastUser='$userid' WHERE IdContratto=$contratto";
		if (!execute($sql))
			return FALSE;
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// toglieClasseExit
// Ricalcola la classe giusta per una pratica fuori recupero, essendo
// avvenuta un'azione che la rimette in gioco (ad es. affido)
//-----------------------------------------------------------------------
function toglieClasseExit($contratto)
{
	try
	{
		$CodClasse = getScalar("SELECT CodClasse FROM contratto c JOIN classificazione cl ON cl.IdClasse=c.IdClasse
		                       WHERE IdContratto=$contratto");
	
		if ($CodClasse=="EXIT") 
		{
			trace("Ricalcolo classificazione per uscita dal Fuori recupero",FALSE);
			classify($contratto,$changed,false);
		}
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
	}
}
//-----------------------------------------------------------------------
// impostaStato
// Cambia lo stato del contratto in un dato stato
//-----------------------------------------------------------------------
function impostaStato($codStato,$contratto,$IdCategoria=0,$msg="")
{
	$dati = getRow("SELECT IdStatoRecupero,TitoloStatoRecupero FROM statorecupero WHERE CodStatoRecupero='$codStato'");
	$sql = "UPDATE contratto SET IdStatoRecupero=".$dati["IdStatoRecupero"].",DataCambioStato=CURDATE()";
	if ($IdCategoria>0) {
		$sql .= ",IdCategoria=$IdCategoria";
	}
	$sql .= " WHERE IdContratto=$contratto AND IFNULL(IdStatoRecupero,0)!=".$dati["IdStatoRecupero"];
	if (getAffectedRows()==1) {
		if ($msg=="") $msg="Stato del contratto modificato in '".$dati["TitoloStatoRecupero"]."'"; 
		writeHistory("NULL",$msg,$contratto,"");
	}
	if ($codStato == "INT")
		inizioLavorazioneInterna($contratto);
		
	return execute($sql);
}

//-----------------------------------------------------------------------
// cambiaStato
// Cambia lo stato del contratto in funzione dei valori idClasseSuccessiva, idStatoRecuperoSuccessivo
// presenti sulla tabella StatoAzione associati all'azione
//-----------------------------------------------------------------------
function cambiaStato($idStatoAzione,$contratto)
{
	try
	{
		$userid = getUserName();
		$sql = "SELECT idClasseSuccessiva, sa.idStatoRecuperoSuccessivo, CodStatoRecupero FROM statoazione sa "
		      ." LEFT JOIN statorecupero s ON sa.idStatoRecuperoSuccessivo=s.IdStatoRecupero"
		      ." WHERE sa.idStatoAzione=$idStatoAzione";
		$datiCnt = getRow($sql); // se lo statoRecuperoSuccessivo � nullo
		if ($datiCnt==null)
			return TRUE;
		if ($datiCnt['idClasseSuccessiva']!=null)
		{
			$sql = "UPDATE contratto SET IdClasse=".$datiCnt['idClasseSuccessiva'].","
			. "LastUser='$userid',DataCambioClasse=NOW() WHERE IdContratto=$contratto";
			if (!execute($sql))
				return FALSE;
		}
		if ($datiCnt['idStatoRecuperoSuccessivo']==null)
			return true;
		if ($datiCnt['idStatoRecuperoSuccessivo']>0) // l'azione provoca un cambio di stato
		{
			$sqlCont = "SELECT count(*) FROM contratto WHERE IdContratto=$contratto"
			          ." and idstatorecupero in "
			          ."(select idstatorecupero from statorecupero where codstatorecupero like 'WRK%')";
			$inwork = getScalar($sqlCont);
			if($inwork==0) // se non � un workflow lo stato attuale del contratto, salvalo nello stato precedente
			{	
				$sqlrecP = "UPDATE contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero WHERE IdContratto=$contratto";
				if (!execute($sqlrecP))
					return FALSE;
			}
			if ($datiCnt['CodStatoRecupero'] == "ATT") // se chiesto passaggio in attesa di affido, esegue particolari operazioni
			{
				if (!metteInAttesa($contratto,true)) // forza anche se � in stato INT
					return FALSE;
			}
			else
			{
				//aggiorna statorecupero attuale
				$sql = "UPDATE contratto SET IdStatoRecupero=".$datiCnt['idStatoRecuperoSuccessivo'].","
				. "LastUser='$userid',DataCambioStato=CURDATE() WHERE IdContratto=$contratto";
				if (!execute($sql))
					return FALSE;
				
				// Se � andato in lavorazione interna, provvede ai settaggi necessari
				if ($datiCnt['CodStatoRecupero'] == "INT")
					inizioLavorazioneInterna($contratto);
			}
		}
		else if ($datiCnt['idStatoRecuperoSuccessivo']==0) // significa "ripristino stato pre-workflow"
		{
			return ripristinaStato($contratto);
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// RipristinaStato
// Cambia lo stato dei contratti in funzione del valore di IdStatoRecuperoPrecedente
// presenti sul contratto stesso. Segno di un uscita da un workflow.
//-----------------------------------------------------------------------
function ripristinaStato($contratto)
{
	try
	{
		// recupera statorecuperoprec e aggiorna lo stato attuale
		$userid = getUserName();
		$sqlrecupero="UPDATE contratto SET idstatorecupero=IdStatoRecuperoPrecedente,"
					. "LastUser='$userid',DataCambioStato=CURDATE() WHERE IdContratto=$contratto AND IdStatoRecuperoPrecedente>0;";
		if (!execute($sqlrecupero))
		{
			return FALSE;
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// emailListaUtentiContratti
// Crea un array associativo di idUtenti approvatori precedenti ognuno dei
// quali ha una sottolista dei contratti su cui ha operato.
//-----------------------------------------------------------------------
function emailListaUtentiContratti($contratto,$CodiceAzione,&$arrayM)
{
	try
	{
		//recupera user attuatore
		$userid = getUserName();
		//recupera la prima delle azioni di quel ciclo di workflow di cui fa parte questa cancellazione/revoca
		$sqlAzioniWrkF="SELECT idazione FROM `azioneprocedura` "
							."where idprocedura=(select idprocedura from `azioneprocedura` ".
												"where idazione=(select idazione from `azione` ".
																			"where codazione='$CodiceAzione'))";
		$sqlPrimaAzioneWrkF="$sqlAzioniWrkF limit 1";
		if (!$rec = getRow($sqlPrimaAzioneWrkF)){return FALSE;}
		$PrimaAzioneWrkF=$rec['idazione'];	
		//recupera la data della prima delle azioni sulla pratica specificata
		$sqlDataUltimadellePrimeAzioni="SELECT dataevento FROM `storiarecupero` WHERE IdContratto=$contratto and idAzione=$PrimaAzioneWrkF order by dataevento desc limit 1";
		if (!$rec = getRow($sqlDataUltimadellePrimeAzioni)){return FALSE;}
		$DataUltimadellePrimeAzioni=$rec['dataevento'];
		//recupera gli id degli approvatori precedenti
		$sqlIdAttuatoriPrecedenti="SELECT distinct idutente FROM `storiarecupero` "
								."WHERE IdContratto=$contratto " 
								."and (dataevento>'$DataUltimadellePrimeAzioni' or dataevento='$DataUltimadellePrimeAzioni') "
								."and idAzione in ($sqlAzioniWrkF) order by dataevento desc ";
								//trace(">>>>>>>sql $sqlIdAttuatoriPrecedenti");
		if (!$idAttuatoriPrecedenti = getFetchArray($sqlIdAttuatoriPrecedenti)){return FALSE;}
		
		foreach($idAttuatoriPrecedenti as $attuatore)
		{	
			//se non esiste tale chiave di quell'utente od � la prima visita
			if(!array_key_exists($attuatore['idutente'], $arrayM))
			{
				$arrayM[$attuatore['idutente']]=array();
			}
			array_push($arrayM[$attuatore['idutente']],$contratto);
		}
		return $arrayM;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// eseguiAutomatismiPerAzione
// Esegue gli automatismi legati ad una azione di codice dato
//-----------------------------------------------------------------------
function eseguiAutomatismiPerAzione($CodAzione,$IdContratto,$viewName="v_pratica_noopt")
{
	try
	{
		// prendo idazione dalla tabella azione
		$sql="SELECT IdAzione FROM azione A WHERE CodAzione ='".$CodAzione."'";
		$IdAzione = getscalar($sql);
		
		if($IdAzione=="")
		{
			trace("Impossibile trovare IdAzione per il codice azione $CodAzione nella tabella azione");
			return FALSE;
		}
		
		// prendo i dati dalla vista v_pratica
		$sql="SELECT * FROM $viewName WHERE IdContratto=$IdContratto";
		$parameters = getRow($sql);
		if (empty($parameters))
		{
			trace("Nessun dato ricevuto dalla vista $viewName per il contratto con Id = $IdContratto");
			return FALSE;
		} 

		//chiamata func eseguiAutomatismi
		if(!eseguiAutomatismi($IdAzione,$IdContratto,$parameters))
		{
			trace("Errore nella chiamata della funzione eseguiAutomatismi");
			return FALSE;	
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// eseguiAutomatismi
// legge tutti gli automatismi associati all'azione
//-----------------------------------------------------------------------
function eseguiAutomatismi($idAzione,$IdContratto,$parameters)
{
	try
	{
		$userid = getUserName();
		// Legge solo gli automatismi con FlagCUmulativo='N', perch� gli altri devono essere eseguiti una
		// sola volta sull'intero insieme di pratiche selezionate
		$sql = "select a.*,m.FileName"
			  ." from azioneautomatica az JOIN automatismo a ON az.idautomatismo=a.idautomatismo and curdate() between az.dataini and az.datafin AND IFNULL(FlagCumulativo,'N')='N'"
              ." LEFT JOIN modello m ON a.IdModello=m.IdModello"
			  ." where az.idazione=$idAzione ORDER BY az.Ordine";
		$datiCnt = getFetchArray($sql);
		foreach ($datiCnt as $value) 
		{
				//trace(print_r($value,true));
				if (!verificaCondizione($value['Condizione'],$IdContratto)) {
					trace("Id=$IdContratto - non soddisfatta condizione per automatismo: ".$value['Condizione'],FALSE);
				} else	{
					$tipoautomatismo = strtoupper($value['TipoAutomatismo']);
					trace("Esecuzione automatismo '".$value['TitoloAutomatismo']."' sulla pratica $IdContratto",FALSE);
					switch (strtoupper($tipoautomatismo)) {
	    				case "PHP": // esegue istruzione php
							trace("Esecuzione php '".$value['Comando'].";'",FALSE);
	    					eval($value['Comando'].";");
	    					break;
						case "EMAIL":
	    					InvioEmail($value['FileName'],$value['Destinatari'],$parameters);
				        	break;
	    				case "SCADENZA":
	    					GeneraScadenze($value['Destinatari'],$parameters,$IdContratto);
	    					break;
	    				case "SMS":
	    					GeneraSMS($value['FileName'],$value['Destinatari'],$parameters,$IdContratto);
	    					break;
	    				case "LETTERA": // lettera rotomail
	    					// Crea la riga su messaggidifferiti, solo se per lo stesso contratto
	    					// e modello non c'� gi� una riga in stato "creata" o "sospesa" (il che significa che �
	    					// ancora valida per l'invio)
	    					
	    					// Se viene passato un "riferimento", significa che il messaggio � legato ad un particolare oggetto
	    					// (ad es. � l'avviso per una rata): se � stato gi� inviato un messaggio per lo stesso oggetto,
	    					// non viene creata la riga.
	    					$where = "IdContratto=$IdContratto AND Tipo='L' AND IdModello=".$value['IdModello'];
	    					if ($parameters["Riferimento"]>"")
	    						$where .= " AND (Stato IN ('C','S') OR Stato IN ('E','N') AND IFNULL(Riferimento,'')='".$parameters["Riferimento"]."')";
	    					else
	    					 	$where .= " AND Stato IN ('C','S')";
	    					
	    					if (!rowExistsInTable("messaggiodifferito",$where)) // messaggio non "duplicato"
	    					{
	    						// Crea la riga di insermento su messaggidifferiti
								$colList = ""; // inizializza lista colonne
								$valList = ""; // inizializza lista valori
								addInsClause($colList,$valList,"IdModello",$value['IdModello'],"N");	 	
								addInsClause($colList,$valList,"IdContratto", $IdContratto,"N");	
								addInsClause($colList,$valList,"Stato", "C","S");
								addInsClause($colList,$valList,"Tipo", "L","S");
								addInsClause($colList,$valList,"DataCreazione","NOW()","G");
								addInsClause($colList,$valList,"Riferimento",$parameters["Riferimento"],"S");
								//echo("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)");
								if (!execute("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)"))
								{
				   					trace("Impossibile inserire il record sulla tabella messaggiodifferito.");
									return FALSE;
								}
	    					}
	    					else
	    					{
	    						trace("Lettera sul contratto $IdContratto non creata, perche' gia' presente in stato C o S con lo stesso riferimento",FALSE);
	    					}
							break;
	    				case "EMAILD":
	    					// Crea la riga di insermento su messaggidifferiti
							$colList = ""; // inizializza lista colonne
							$valList = ""; // inizializza lista valori
							addInsClause($colList,$valList,"IdModello",$value['IdModello'],"N");	 	
							addInsClause($colList,$valList,"IdContratto", $IdContratto,"N");	
							addInsClause($colList,$valList,"Stato", "C","S");
							addInsClause($colList,$valList,"Tipo", "E","S");
							addInsClause($colList,$valList,"DataCreazione","NOW()","G");
							//echo("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)");
							if (!execute("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)"))
							{
				   				trace("Impossibile inserire il record sulla tabella messaggiodifferito.");
								return FALSE;
							}
	    					break;
	    				case "SMSD":
	    					// Crea la riga su messaggidifferiti, solo se per lo stesso contratto
	    					// e modello non c'� gi� una riga in stato "creata" o "sospesa" (il che significa che �
	    					// ancora valida per l'invio)
	    					
	    					// Se viene passato un "riferimento", significa che il messaggio � legato ad un particolare oggetto
	    					// (ad es. � l'avviso per una rata): se � stato gi� inviato un messaggio per lo stesso oggetto,
	    					// non viene creata la riga.
	    					$where = "IdContratto=$IdContratto AND Tipo='S' AND IdModello=".$value['IdModello'];
	    					if ($parameters["Riferimento"]>"")
	    						$where .= " AND (Stato IN ('C','S') OR Stato IN ('E','N') AND Riferimento='".$parameters["Riferimento"]."')";
	    					else
	    					 	$where .= " AND Stato IN ('C','S')";
	    					
	    					if (!rowExistsInTable("messaggiodifferito",$where)) // messaggio non "duplicato"
	    					{
								$colList = ""; // inizializza lista colonne
								$valList = ""; // inizializza lista valori
								addInsClause($colList,$valList,"IdModello",$value['IdModello'],"N");	 	
								addInsClause($colList,$valList,"IdContratto", $IdContratto,"N");	
								addInsClause($colList,$valList,"Stato", "C","S");
								addInsClause($colList,$valList,"Tipo", "S","S");
								addInsClause($colList,$valList,"DataCreazione","NOW()","G");
								addInsClause($colList,$valList,"Riferimento",$parameters["Riferimento"],"S");
								//echo("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)");
								if (!execute("INSERT INTO messaggiodifferito ($colList)  VALUES ($valList)"))
								{
				   					trace("Impossibile inserire il record sulla tabella messaggiodifferito.");
									return FALSE;
								}
	    					}
	    					else
	    						trace("Messaggio differito sul contratto $IdContratto non creato, perche' gia' presente in stato C o S",FALSE);
	    					break;
	    				case "SQL": // esegue istruzione SQL
	    					$sql = $value['Comando'];
	    					$sql = preg_replace('/\$IdContratto/i',$IdContratto,$sql); // sostituisce l'eventuale variabile
	    					trace("Esecuzione comando SQL automatico: $sql",FALSE);
	    					if (!execute($sql))
								return FALSE;
					}//end switch
				}//end if
		}//end foreach
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// eseguiAutomatismiCumulativi
// legge tutti gli automatismi cumulativi associati all'azione
//-----------------------------------------------------------------------
function eseguiAutomatismiCumulativi($idStatoAzione,$idContratti,$parameters)
{
	try
	{
		$idAzione = getScalar("SELECT IdAzione FROM statoazione WHERE IdStatoAzione=$idStatoAzione");
		$userid = getUserName();
		$sql = "select a.*,m.FileName" 
		." from azioneautomatica az, automatismo a, modello m where az.idazione=$idAzione and curdate() between az.dataini and az.datafin"
		." and az.idautomatismo=a.idautomatismo and a.IdModello=m.IdModello AND IFNULL(FlagCumulativo,'N')='Y' ORDER BY az.Ordine";
		$datiCnt = getFetchArray($sql);
		/*trace("datiCnt: ".print_r($datiCnt,true));
		trace("idazione $idAzione");
		trace("idcontratti: ".print_r($idContratti,true));
		trace("parametri: ".print_r($parameters,true));*/
		foreach ($datiCnt as $value) 
		{
				//crea l'array di contratti che possono fare quell'automatismo tra quelli passati
				$contrattiParam=array();
				foreach($idContratti as $IdContratto)
				{
					if (verificaCondizione($value['Condizione'],$IdContratto))
					{
						array_push($contrattiParam,$IdContratto);
					}
				}
				//trace("contrattiParam: ".print_r($contrattiParam,true));
				//trace("parametri: ".print_r($parameters,true));
				//identifica l'automatismo ed esegue i contratti filtrati
				$tipoautomatismo = strtoupper($value['TipoAutomatismo']);
				switch ($tipoautomatismo) {
    				case "EMAIL":
    					//invio di email aggregative con una lista fissa e di contratti e di destinatari 
    					InvioMEmail($contrattiParam,$datiCnt[0]['FileName'],$parameters);
			        	break;
			        case "EMAILCOMP":
			        	//invio di email specifiche per ogni utente, composte da diversi gruppi di contratti
			        	InvioMEmailComposte($datiCnt[0]['FileName'],$parameters);
			        	break;
    			}//end switch
		}//end try
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// verificaCondizione
// Verifica se un'azione deve essere eseguita o meno
// Ritorna true se l'azione deve essere eseguita
//-----------------------------------------------------------------------
function verificaCondizione($cond,$IdContratto)
{
	try
	{
		if (($cond==null) || ($cond==''))
			return TRUE;	
		$cond = "SELECT * FROM v_pratica_noopt c WHERE ($cond) AND IdContratto=$IdContratto";
		$row = getRow($cond);
		return is_array($row);
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//-----------------------------------------------------------------------
// InvioEmail
// Riceve il comando (testo dell'email) ed i destinatari dell'email ed
// effettua l'invio
//-----------------------------------------------------------------------
function InvioEmail($comando,$destinatari,$parameters,&$txt="")
{
	try
	{
		//echo 'invio mail-'.$destinatari;
		$dest=strDestinatari($destinatari,$parameters);
		$arr=preparaEmail($comando,$parameters);
		$subject=$arr[0];
		$body=$arr[1];
		$txt=$subject."<br><br>".$body;   // usato per far tornare il testo della mail usato su cronprocess
		$ret = sendMail("",$dest,$subject,$body,'');
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//-----------------------------------------------------------------------
// InvioMEmail
// Riceve il comando (testo dell'email, i destinatari dell'email, ed i 
// contratti su cui fare l'elenco della mail e ne effettua l'invio
//-----------------------------------------------------------------------
function InvioMEmail($idContratti,$comando,$parameters,&$txt="")
{
	global $context;
	try
	{
		//echo 'invio mail-'.$destinatari;
		$destinatari=$parameters['*APPROVER'];
		//trace(">destinatari: ".print_r($destinatari,true));
		$dest=strMDestinatari($destinatari,$parameters);
		//trace(">dest ".$dest);
		$arr=preparaEmail($comando,$parameters,'M',$idContratti);
		$subject=$arr[0];
		$body=$arr[1];
		$txt=$subject."<br><br>".$body;   // usato per far tornare il testo della mail usato su cronprocess
		//trace("txt: $txt");
		$mailMitt = $context["Email"];
		if($mailMitt!='') 
			$mailMitt = $parameters['NOMEAUTORE']."<$mailMitt>";
		$ret = sendMail($mailMitt,$dest,$subject,$body,'');
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//-----------------------------------------------------------------------
// InvioMEmailComposte
// Riceve il comando (testo dell'email), i destinatari dell'email, ed i 
// contratti su cui fare l'elenco della mail (dentro a *DESTINATARIRIF) e ne effettua l'invio
//-----------------------------------------------------------------------
function InvioMEmailComposte($comando,$parameters,&$txt="")
{
	global $context;
	$IdUser = $context['IdUtente'];
	try
	{
		//echo 'invio mail-'.$destinatari;
		$destinatari=$parameters['*DESTINATARIRIF'];
		//trace(">albero destinatarii: ".print_r($destinatari,true));
		//per ogni destinatario crea la mail specifica, 
		//composta da N contratti elencati ne proprio sottoalbero e le associa al modello inviandola. 
		$i=0;
		$idUtentiDest=array_keys($destinatari);
		foreach($destinatari as $destinatario)
		{
			//trace(">> contratti destinatario: ".print_r($destinatario,true));
			//trace("destinatario ".$idUtentiDest[$i]);
			/*foreach($destinatario as $contratto){
				trace("contratto di ut:".$idUtentiDest[$i]." - $contratto");
			}*/
			$strDest = getScalar("SELECT Email FROM utente WHERE IdUtente=".$idUtentiDest[$i]);
			//trace("MAIL DESTINATARIO ".$strDest);
			if($strDest!='')
			{
				//se � pieno continua e manda la mail altrimenti non mandarla e vai avanti
				$arr=preparaEmail($comando,$parameters,'M',$destinatario);
				$subject=$arr[0];
				$body=$arr[1];
				$txt=$subject."<br><br>".$body;   // usato per far tornare il testo della mail usato su cronprocess
				//trace("txt: $txt");
				$mailMitt = getScalar("SELECT Email FROM utente WHERE IdUtente=".$IdUser);
				if($mailMitt!='')
				{
					$mitt=$parameters['NOMEAUTORE']."<$mailMitt>";
				}				
				//trace("MITTENTE $mitt");
				$ret = sendMail($mitt,$strDest,$subject,$body,'');
			}
			$i++;
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//---------------------------------------------------------------------------------------------
// preparaEmail
// Compone il testo e il sibject di un messaggio di mail, dato il file e i parametri
//---------------------------------------------------------------------------------------------
function preparaEmail($fileModello,$parameters,$flag="",$idContratti="")
{
		
//      echo 'dest-'.$dest;
		$content = file_get_contents(TEMPLATE_PATH.'/'.$fileModello);
		$c = json_decode($content,true);
		$subject = replaceVariables($c['subject'],$parameters);
		if($flag=='M'){
			//trace(">>contratti: ".print_r($idContratti,true));
			//trace(">>parametri: ".print_r($parameters,true));
			$body    = replaceModelVariable($idContratti,$c['body'],$parameters);
		}else{
			$body    = replaceVariables($c['body'],$parameters); //singolo			
		}
		$arr= Array($subject,$body);
		return $arr;
}
//-----------------------------------------------------------------------
// strDestinatari
// Compone la stringa dei destinatari della mail
//-----------------------------------------------------------------------
function strDestinatari($destinatari,$parameters)
{
	try
	{
		$strDest='';
		$dest = explode(";", $destinatari);
		foreach ($dest as $value) 
		{
			if ($value!='')
			{
				if (substr($value,0,1)=='*')   // il valore deve essere prelevato da parameters
					if(is_array($parameters[$value]))
					{
						foreach ($parameters[$value] as $dest) 
						{
								$test = getScalar("SELECT Email FROM utente WHERE IdUtente=".$dest);
								if ($test!='')
								{
									$strDest .= $test.';';
								}
						}
					}else{
    					$strDest .= getScalar("SELECT Email FROM utente WHERE IdUtente=".$parameters[$value]) . ';';
					}
    			else
					$strDest .= $value.";";
			}	
 		}
		return $strDest;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return '';
	}
}
//-----------------------------------------------------------------------
// strMDestinatari
// Compone la stringa dei destinatari della mail multipla
//-----------------------------------------------------------------------
function strMDestinatari($destinatari,$parameters)
{
	try
	{
		$strDest='';
		//$dest = explode(";", $destinatari);
		foreach ($destinatari as $value) 
		{
			if ($value!='')
			{
				$test = getScalar("SELECT Email FROM utente WHERE IdUtente=".$value);
				if ($test!='')
				{
					$strDest .= $test.';';
					//trace("stre dest ".$strDest);
				}
			}	
 		}
		return $strDest;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return '';
	}
}
//-----------------------------------------------------------------------
// replaceModel
// body			corpo del messagge contenente il placeholder del sottomodello
// modelName	TitoloModello del submodel (tipoModello 'X')
// query		query da eseguire per ottenere le righe con i valori da sostituire nel sottomodello
//-----------------------------------------------------------------------
function replaceModel($body,$modelName,$query,$parametersDefault="")
{
	global $context;
	try {
		$modPlaceholder = "%MODELLO.$modelName%";
		if (stripos($body,$modPlaceholder)===FALSE) {
			$modPlaceholder = "DCSys_model_$modelName"; // nuovo placeholder
		}
		if (stripos($body,$modPlaceholder)!==FALSE) {
			$row = getRow("SELECT FileName FROM modello WHERE TitoloModello='$modelName' AND TipoModello='X'");
			if ($row) {
				$modelText = file_get_contents(TEMPLATE_PATH.'/'.$row['FileName']);
				
				$rows = htmlentities_deep(getFetchArray($query));
				$text = "";
				foreach($rows as $row) {
					$text .= replaceVariables($modelText,$row,$parametersDefault);
				}

				return str_replace($modPlaceholder,$text,$body);
			} else {
				trace("Non trovato il modello '$modelName'");
			}
		}
	}
	catch (Exception $e) {
		trace($e->getMessage());
		setLastError($e->getMessage());
	}
	return $body;
}

//-----------------------------------------------------------------------
// replaceVariables
// riceve una stringa la quale contiene del testo che deve essere sostituito
// i testo da sostituire � racchiuso tra %
// restiruisce la stringa aggiornata con il testo sostituito
//-----------------------------------------------------------------------
function replaceVariables($strTxt,$parameters,$parametersDefault="")
{
	try
	{
		$pos = strpos($strTxt, '%');
   		while ($pos!==FALSE)
   		{
    		$pos1 = strpos($strTxt, '%',$pos+1);
    		if ($pos1>$pos+1)
    		{
    			$keySearch = substr($strTxt,$pos+1,$pos1-$pos-1);
    			//trace("Key $keySearch",false);
    			if (array_key_exists($keySearch,$parameters)) {
    				$newVal = $parameters[$keySearch];
    			}
    			else {
    				trace("funzione replaceVariables: non trovato valore da sostituire per la variabile $keySearch",false);
    				$newVal = $parametersDefault; //"";
    			}
				// per l'uso nel template Word, bisogna eliminare le entities HTML, che
				// non sono tollerate (ad es. &deg;)
    			$newVal = html_entity_decode($newVal,ENT_COMPAT,"ISO-8859-1");	
    			$newVal = str_replace("&","&amp;",$newVal);
				// con il passaggio alla nuova versione di php sono diventate problematiche anche le lettere accentate
				$newVal = str_replace("è","e'",$newVal); 
				$newVal = str_replace("é","e'",$newVal); 
				$newVal = str_replace("ì","i'",$newVal); 
				$newVal = str_replace("à","a'",$newVal); 
				$newVal = str_replace("ò","o'",$newVal); 
				$newVal = str_replace("ù","u'",$newVal); 
				
    			$strTxt = substr_replace($strTxt,$newVal,$pos,$pos1-$pos+1);
    			$pos = strpos($strTxt, '%',$pos+1+strlen($newVal)); // prossima occorrenza
    		}
    		else
    			break; 
		}
		/*20/05/2016 riconoscimento dei valori legati alle varibili del tipo DCSys_XXX per i form delle lettere*/
		$pattern = '/DCSys_(\w+)/';
		$found = preg_match_all($pattern, $strTxt,$matches);
		$vars = array_unique($matches[1]);
		foreach($vars as $strVal){
		    if (array_key_exists($strVal,$parameters)) {
					$newStr = $parameters[$strVal];
//					trace("funzione replaceVariables: valore da sostituire per la variabile $strVal: $newStr",false);
		    }else {
					trace("funzione replaceVariables: non trovato valore da sostituire per la variabile $strVal",false);
					$newStr = $parametersDefault;
			}
		    $newStr  = html_entity_decode($newStr ,ENT_COMPAT,"UTF-8");	
			$newStr  = str_replace("&","&amp;",$newStr);
			
			$strTxt = str_replace("DCSys_".$strVal,$newStr, $strTxt,$count);
//			trace("Replace fatti=$count",false);
		}
		return $strTxt;	
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return '';
	}
}
//-----------------------------------------------------------------------
// replaceModelVariable
// riceve una stringa la quale contiene del testo, controlla che sia un 
// modello se si, deve essere sostituito iterando per ogni contratto
// i testo da sostituire � racchiuso tra %
// restiruisce la stringa aggiornata con il testo sostituito
//-----------------------------------------------------------------------
function replaceModelVariable($idContratti,$strTxt,$parameters,$parametersDefault="")
{
	try
	{
		$pos = strpos($strTxt, '%');
		//trace("POSIZIONE POS $pos");
   		while ($pos!==FALSE)
   		{
    		$pos1 = strpos($strTxt, '%',$pos+1);
    		//trace("POSIZIONE POS1 $pos1 = '%',pos+1");
    		if ($pos1>$pos+1)
    		{
    			$keySearch = substr($strTxt,$pos+1,$pos1-$pos-1);
    			$keycomp = substr($strTxt,$pos+1,8);
    			$modello = strtolower($keycomp);
    			if(strcmp($modello,'modello.')==0){
					//per ogni contratto itera la sostituzione del modello
					$keymod = substr($strTxt,($pos1-(($pos1-$pos-1)-8)),(($pos1-$pos-1)-8));
					$fileModel = getScalar("SELECT filename FROM modello where TitoloModello = '$keymod'");  
					if (!$fileModel)   
						Throw new Exception("Non trovata definizione del sottomodello '$keymod'");
					$newVal='';
					foreach ($idContratti as $contratto) 
					{
						$pratica = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=$contratto");  
						if (!$pratica)   
							Throw new Exception("Impossibile leggere la pratica con id $contratto");
						
						$parameters = array_merge($parameters,$pratica);
						
						//apre il modello e lo sostituisce
    					$content = file_get_contents(TEMPLATE_PATH.'/'.$fileModel);
    					$newVal .= replaceVariables($content,$parameters);
					}
    			}else{
    				//avanza normalmente
    				if (array_key_exists($keySearch,$parameters))
    					$newVal = $parameters[$keySearch];
	    			else
	    				$newVal = $parametersDefault; //"";
    			}
    			//sostituisce
    			$strTxt = substr_replace($strTxt,$newVal,$pos,$pos1-$pos+1);
    			//trace(">>>strtxt $strTxt | newval $newVal");
    			$pos = strpos($strTxt, '%',$pos+1); // prossima occorrenza
    		}
    		else
    			break; 
		}
		return $strTxt;	
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return '';
	}
}
//-----------------------------------------------------------------------
// GeneraScadenze
// Genera tante scadenze quanti sono i destinatari
// I dati della scadenza sono gestiti nella tabella Nota
//-----------------------------------------------------------------------
function GeneraScadenze($destinatari,$parameters,$contratto)
{
	try
	{
		$userid = getUserName();
		$dest = explode(",", $destinatari);
		foreach ($dest as $value) 
		{
			$idDestinatario=null;
			$idReparto=null;
			if (substr($value,0,1)=='*')   // il valore deve essere prelevato da parameters
			{
				$tipoDest=substr($parameters[$value],0,1);
				switch ($tipoDest) {
    				case "U":  //utente
						$idDestinatario=substr($parameters[$value],1);
						break;
    				case "R":  //reparto
						$idReparto=substr($parameters[$value],1);
						break;
				}				
			}else{  // ho una stringa con la quale devo prendere l'id dell'utente se non trovato cerco il reparto
 				$idDestinatario = getScalar("SELECT IdUtente FROM utente WHERE CodUtente='".$value."'");
 				if ($idDestinatario==null)
 					$idReparto = getScalar("SELECT IdReparto FROM reparto WHERE CodUfficio='".$value."'");
 			}
			if (!GeneraScadenza($parameters,$idDestinatario,$idReparto,$contratto))
    			return FALSE; 			
 		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// GeneraScadenza
// Genera una singola scadenza nello scadenzario
//-----------------------------------------------------------------------
function GeneraScadenza($parameters,$idDestinatario,$idReparto,$contratto)
{
	try
	{
		global $context;
		$userid = getUserName($IdUser);
		
		if ($idDestinatario=='')
			$idDestinatario='null';
		if ($idReparto=='')
			$idReparto='null';
		$testoNota=$parameters['TESTOSCADENZA'];
		if ($testoNota=='')
			$testoNota='null';
		$dataScad=$parameters['DATASCADENZA'];
		if ($dataScad=='')
			$dataScad='null';	
		$testoNota=quote_smart($testoNota);	
		$master=$context["master"];
		//trace("master ".$master);
		$varNameS='';
		$IdM='';
		if($master!=''){
			$varNameS=',IdSuper';
			$sqlIdMaster="SELECT IdUtente FROM utente where userid='$master'";
			$IdM = getScalar($sqlIdMaster);
			$IdM=",$IdM";
		}
		$sql = "INSERT INTO nota (IdUtenteDest,IdUtente,IdContratto,TipoNota,IdReparto,TestoNota,DataCreazione,"
			."DataScadenza,DataIni,DataFin,LastUpd,LastUser$varNameS) values ("
			.$idDestinatario.",".$IdUser.",".$contratto.",'S',".$idReparto.",".$testoNota.",NOW(),'"
			.$dataScad."',NOW(),'9999-12-31',NOW(),'$userid'$IdM)";
		return execute($sql);
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//-----------------------------------------------------------------------
// GeneraSMS
// Effettua l'invio degli sms 
//-----------------------------------------------------------------------
function GeneraSMS($comando,$destinatari,$parameters,$contratto)
{
	try
	{
		$userid = getUserName($IdUser);
		$dest = explode(",", $destinatari);
		foreach ($dest as $value) 
		{
			$destinatario=null;
			if (substr($value,0,1)=='*')   // il valore deve essere prelevato da parameters
			{
				$tipoDest=substr($parameters[$value],0,1);
				switch ($tipoDest) {
    				case "U":  //utente
						$idUtente=substr($parameters[$value],1);
   						$destinatario = getScalar("SELECT Cellulare FROM utente WHERE idUtente=".$idUtente);
						break;
    				case "C":  //cliente
    					$idCliente=substr($parameters[$value],1);
    					$destinatario = getScalar("SELECT Cellulare FROM recapito WHERE idCliente=".$idCliente." and idTipoRecapito=1");
						break;
				}				
			}	
			else{  // ho una stringa con la quale devo prendere l'id dell'utente se non trovato cerco il reparto
 				$destinatario = getScalar("SELECT Cellulare FROM utente WHERE CodUtente='".$value."'");
 			}
			// prelevo il testo dell'sms
 			$content = file_get_contents(TEMPLATE_PATH.'/'.$comando);
			$c = json_decode($content,true);
			$testoSMS=replaceVariables($c['testoSMS'],$parameters);
			if (!inviaSMS($destinatario,$testoSMS))
    			return FALSE; 			
 		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//-------------------------------------------------------------------------------------
// inviaSMS
// Effettua l'invio dell'sms se non riesce ad effettuare l'invio scrive sul file di log
//-------------------------------------------------------------------------------------
function inviaSMS($destinatario,$testoSMS,&$ErrMsg)
{

	$invio=FALSE;
	$arrayDestinatario=explode(',', $destinatario);
	$destinatario='';
	$arrNewDest = array();

	for ($i = 0; $i < count($arrayDestinatario); $i++)
	{
		$resp=ctrlNumeroCellulare($arrayDestinatario[$i]);
		if($resp!=''){
			$arrNewDest[]=$resp;
		}
	}
	$destinatario=join(',',$arrNewDest);
	//trace("dest $destinatario");
	
	if (SMS_TEST_NR=='dummy')
	{
		trace("Invio SMS non effettuato (dummy) - destinatario - $destinatario - Testo - $testoSMS",FALSE);
		$ErrMsg.="Invio SMS non effettuato (dummy) - destinatario - $destinatario - Testo - $testoSMS";
		return FALSE;
	}
	
	for ($i = 0; $i < count($arrayDestinatario); $i++)
	{
	    $destinatario = $arrayDestinatario[$i];
		try
		{
			if (SMS_TEST_NR>'')
				$destinatario = SMS_TEST_NR;
			else
				$destinatario = ctrlNumeroCellulare($destinatario);
				
			if ($destinatario!=''){
				// Preparazione parametri per API di SMSHosting	
                $curl = curl_init();
                curl_setopt($curl,CURLOPT_URL,SMS_URL);
                curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
                //	curl_setopt($curl,CURLOPT_SSLVERSION,3);

                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                );
                curl_setopt($curl,CURLOPT_POST,true);
                curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
                curl_setopt($curl,CURLOPT_POSTFIELDS, "to=$destinatario&from=".SMS_SENDER."&text=".urlencode($testoSMS));

                $resp = curl_exec($curl);
                $json = json_decode($resp);
                $info = curl_getinfo($curl);
                curl_close($ch);

                switch ($info['http_code']) {
                    case 200:
                    case 204:
                        $invio=TRUE;
                        break;
                    default:
    					trace("Invio SMS non riuscito; codice di ritorno='{$json->errorMsg}' - destinatario - $destinatario - Testo - $testoSMS",FALSE);
    					$ErrMsg .= "Invio SMS al numero $destinatario non riuscito. {$json->errorMsg}";
                        break;
                }
			}
		}
		catch (Exception $e)
		{
			$ret = $e->getMessage();
			$ErrMsg.="Invio SMS al numero $destinatario non effettuato per il seguente motivo: $ret";
			trace($ret);
			setLastError($ret);
			return FALSE;
		}
	}
	return $invio;
}

//-------------------------------------------------------------------------------------
// ctrlNumeroCellulare
// Controlla e normalizza il numero di telefono destinatario di un SMS
//-------------------------------------------------------------------------------------
function ctrlNumeroCellulare($destinatario)
{
	try
	{
		$destinatario = trim($destinatario);
		$retVal='';
		// prendo solo i valori numerici
		for ($i = 0; $i < strlen($destinatario); $i++) {
    		if (substr($destinatario,$i,1)>='0' && substr($destinatario,$i,1)<='9')
    	    			$retVal.=substr($destinatario,$i,1);
    	}
		// verifico la lunghezza se diversa da 10 e 12 non � valido
		// il primo carattere deve essere 3
		if (strlen($retVal)<9 || strlen($retVal)>13 || substr($retVal,0,1)!='3')
		{
			trace("Numero telefonico per invio SMS non valido -$destinatario-",FALSE);
			return '';
		}
		if (strlen($retVal)<'12')
			$retVal = '39'.$retVal;
		return $retVal;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return '';
	}
}

//================================================================================
// inviaSingolaEmailDiff     (by Aldo)
// Invia singola email differita
// Argomenti:
//   1) $IdModello	     Id del modello da utilizzare 
//   2) IdContratto      Id del contratto 
//   3) $IdMessaggioDifferito Id del messaggio differito
// Restituisce:
//      true :	tutto OK
//      false:  errore nell'invio email
//================================================================================
function  inviaSingolaEmailDiff($IdModello,$IdContratto,$IdMessaggioDifferito)
{
  try
  {	
		//prendo il nome del file modello
	  	$nomeModelloEmail = getScalar("SELECT FileName FROM modello WHERE IdModello=".$IdModello);
	  	if(!($nomeModelloEmail>""))
	  	{
	      UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione dell'email","");
	      trace("Modello email" .$IdModello." non presente nella tabella Modello.",false);
	      return false;	
	  	}
	  	
	  	// prendo dalla vista i dati 
		$ins = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=".$IdContratto);
	  	if(empty($ins))
	    {
	      UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione dell'email","");	      
	      trace("Non sono stati ricevuti dati dalla vista v_contratto_lettera per il contratto". $IdContratto,false);
	      return false;
	    }
		
	    // prendo dalla vista gli indirizzi email del destinatario
		$destinatario = getScalar("SELECT Email FROM v_email WHERE idCliente=".$ins["IdCliente"]);
		
		$testo="";
		
		if($destinatario=="")
	  	{
	    	$retArr=preparaEmail($nomeModelloEmail,$ins);
	    	$testo=$retArr[0]."$".$retArr[1];
	    	UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Indirizzo email cliente non presente",$testo,"");	
	    	trace("Indirizzo email cliente ".$ins["IdCliente"]." non presente.",false);
	    	return false;          
	  	}

		// invio l'email 
	  	if(!InvioEmail($nomeModelloEmail,$destinatario,$ins,$testo))
        {
          UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore durante l'invio delle email all'indirizzo $destinatario",$testo,"");
          trace("Errore durante l'invio delle email all'indirizzo $destinatario",false);
	      return false;
        }
	
	  	//trace("testo:$testo");
	  	UpdateStatoMsgDiff($IdMessaggioDifferito,"E","Email inviata",$testo);
	  	$sql="select IdAzione from azione where TitoloAzione='Invio e-mail'";
		writeHistory(getscalar($sql),"Inviato email all'indirizzo $destinatario",$IdContratto,$testo);
		return true;	  	
  }
		
  catch (Exception $e)
  {
	  trace("Errore nell'invio email con IdMessaggioDifferito: $IdMessaggioDifferito:".$e->getMessage());
	  setLastError($e->getMessage());
	  return false;
  } // end catch  	
}

//================================================================================
// inviaSingoloSmsDiff      (by Aldo)
// Invia singolo sms differito
// Argomenti:
//   1) $IdModello	     Id del modello da utilizzare 
//   2) IdContratto      Id del contratto 
//   3) $IdMessaggioDifferito Id del messaggio differito
// Restituisce:
//      true :	tutto OK
//      false:  errore nell'invio sms
//================================================================================
function  inviaSingoloSmsDiff($IdModello,$IdContratto,$IdMessaggioDifferito)
{
  try
  {	
	   	// prendo il nome del modello
	  	$nomeModelloSms = getScalar("SELECT FileName FROM modello WHERE IdModello=".$IdModello);
	  	if(!($nomeModelloSms>""))
	  	{
	      UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione del messaggio","");
	      trace("Modello email" .$IdModello." non presente nella tabella Modello.",false);
	      return false;	
	  	}
	  	
	  	//apro il file json e lo decodifico
	  	$modelloSms = json_decode(file_get_contents(TEMPLATE_PATH.'/'.$nomeModelloSms));
        
	  	// prendo i dati dalla vista
		$ins = getRow("SELECT * FROM v_contratto_precrimine WHERE IdContratto=".$IdContratto);
        if(empty($ins))
        {
    	  UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione del messaggio","");
          trace("Nessun dato ricevuto dalla vista  v_contratto_precrimine per il contratto.".$IdContratto,false);
          return false;           // esco dalla funzione	
        }
        
        // sostituzione dei parametri nel testo 
        $testoSms = replaceVariables($modelloSms->testoSMS,$ins);
        
        // prendo i cellulari del destinatario (cliente) dalla vista
        $dest= getScalar("SELECT Cellulare FROM v_cellulare WHERE idCliente=".$ins["IdCliente"]);
        if($dest=="")
	  	{
	    	UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Non sono presenti numeri di cellulare per il cliente",$testoSms,"");
	    	trace("Non sono presenti numeri di cellulare per il cliente",false);
	    	return false;
	  	}

	  	if(!inviaSMS($dest,$testoSms,$errmsg))
	  	{
	  		UpdateStatoMsgDiff($IdMessaggioDifferito,"N",$errmsg,$testoSms,"");
	  		return false;           // esco dalla funzione
	  	}
  			
	  	$sql="select IdAzione from azione where TitoloAzione='Invio SMS'";
		writeHistory(getscalar($sql),"Inviato sms al numero $dest",$IdContratto,$testoSms);
		UpdateStatoMsgDiff($IdMessaggioDifferito,"E","Sms inviato",$testoSms,""); 
		return true;	
  }
		
	  catch (Exception $e)
  {
	  trace("Errore nell'invio degli sms differiti:".$e->getMessage());
	  setLastError($e->getMessage());
	  return false;
  } // end catch  	
}

//-----------------------------------------------------------------------
// cambioCategoria
// cambia la categoria del contratto
// Restituisce il nome della categoria assegnata
//-----------------------------------------------------------------------
function cambioCategoria($contratto,$IdCategoria)
{
	try
	{
		if (!$IdCategoria)
			$IdCategoria = "NULL";
		//-----------------------------------------------------------------------------
		// Registra il cambio di categoria
		//-----------------------------------------------------------------------------
		if (!execute("UPDATE contratto set IdCategoria=$IdCategoria where IdContratto = $contratto"))
		{
			trace("Errore nell'assegnazione della categoria con id $IdCategoria al contratto con id $contratto");
			return FALSE;
		}
		return getScalar("Select TitoloCategoria from categoria where IdCategoria=$IdCategoria");
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// cambioCategoriaMaxirata
// cambia la categoria maxirata del contratto
// Restituisce il nome della categoria assegnata
//-----------------------------------------------------------------------
function cambioCategoriaMaxirata($contratto,$IdCategoriaMaxirata)
{
	try
	{
		if (!$IdCategoriaMaxirata)
			$IdCategoriaMaxirata = "NULL";
		//-----------------------------------------------------------------------------
		// Registra il cambio di categoria
		//-----------------------------------------------------------------------------
		if (!execute("UPDATE contratto set IdCategoriaMaxirata=$IdCategoriaMaxirata where IdContratto = $contratto"))
		{
			trace("Errore nell'assegnazione della categoria maxirata con id $IdCategoriaMaxirata al contratto con id $contratto");
			return FALSE;
		}
		if (!execute("UPDATE statistichemaxirate set IdCategoriaMaxirata=$IdCategoriaMaxirata where IdContratto = $contratto"))
		{
			trace("Errore nell'assegnazione della categoria maxirata con id $IdCategoriaMaxirata allle statistiche maxirata con id $contratto");
			return FALSE;
		}
		return getScalar("Select CategoriaMaxirata from categoriamaxirata where IdCategoriaMaxirata=$IdCategoriaMaxirata");
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// cambioCategoriaRiscattoLeasing
// cambia la categoria riscatti scaduti del contratto
// Restituisce il nome della categoria assegnata
//-----------------------------------------------------------------------
function cambioCategoriaRiscattoLeasing($contratto,$IdCategoriaRiscattoLeasing)
{
	try
	{
		if (!$IdCategoriaRiscattoLeasing)
			$IdCategoriaRiscattoLeasing = "NULL";
		//-----------------------------------------------------------------------------
		// Registra il cambio di categoria
		//-----------------------------------------------------------------------------
		if (!execute("UPDATE contratto set IdCategoriaRiscattoLeasing=$IdCategoriaRiscattoLeasing where IdContratto = $contratto"))
		{
			trace("Errore nell'assegnazione della categoria riscatti scaduti con id $IdCategoriaRiscattoLeasing al contratto con id $contratto");
			return FALSE;
		}
		return getScalar("Select CategoriaRiscattoLeasing from categoriariscattoleasing where IdCategoriaRiscattoLeasing=$IdCategoriaRiscattoLeasing");
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// cambioDataRiscattoScaduto
// modifica la data di chiusura per riaffidare la pratiche ad uno dei tre periodi 
// di affido del “Riscatto leasing”
// Restituisce true o false
//-----------------------------------------------------------------------
function cambioDataRiscattoScaduto($contratto,$dataChiusura)
{
	try
	{
		//-----------------------------------------------------------------------------
		// modifica della data di chiusura per riaffidare la pratiche ad uno dei tre periodi di affido del “Riscatto leasing”
		//-----------------------------------------------------------------------------
		if (!execute("UPDATE contratto set DataChiusura='$dataChiusura' where IdContratto = $contratto"))
		{
			trace("Errore nella modifica data chiusura per riaffidare pratiche Riscatto leasing al contratto con id $contratto");
			return FALSE;
		}
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}


//-----------------------------------------------------------------------
// cambioStatoLegale
// Cambia lo stato legale del contratto
// Restituisce il nome della stato legale assegnato
//-----------------------------------------------------------------------
function cambioStatoLegale($contratto,$IdStatoLegale)
{
	try
	{
		if (!$IdStatoLegale)
			$IdStatoLegale = "NULL";
		//-----------------------------------------------------------------------------
		// Registra il cambio di stato legale
		//-----------------------------------------------------------------------------
		if (!execute("UPDATE contratto set IdStatoLegale=$IdStatoLegale where IdContratto = $contratto"))
		{
			trace("Errore nell'assegnazione dello stato legale con id $IdStatoLegale al contratto con id $contratto");
			return FALSE;
		}
		return getScalar("Select TitoloStatoLegale from statolegale where IdStatoLegale=$IdStatoLegale");
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//-----------------------------------------------------------------------
// cambioStatoStragiud
// Cambia lo stato stragiudiziale del contratto
// Restituisce il nome della stato legale assegnato
//-----------------------------------------------------------------------
function cambioStatoStragiud($contratto,$IdStatoStragiudiziale)
{
	try
	{
		if (!$IdStatoStragiudiziale)
			$IdStatoStragiudiziale = "NULL";
			//-----------------------------------------------------------------------------
			// Registra il cambio di stato legale
			//-----------------------------------------------------------------------------
			if (!execute("UPDATE contratto set IdStatoStragiudiziale=$IdStatoStragiudiziale where IdContratto = $contratto"))
			{
				trace("Errore nell'assegnazione dello stato legale con id $IdStatoStragiudiziale al contratto con id $contratto");
				return FALSE;
			}
			return getScalar("Select TitoloStatoStragiudiziale from statostragiudiziale where IdStatoStragiudiziale=$IdStatoStragiudiziale");
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//--------------------------------------------------------------
// fuoriRecupero
// Determina se il contratto dato � stato forzato fuori recupero
// o � in DBT. (viene chiamata per l'affido)
//--------------------------------------------------------------
function fuoriRecupero($IdContratto)
{
	$row = getRow("SELECT CodClasse,CodStatoRecupero FROM classificazione cl,contratto c,statorecupero sr"
		   ." WHERE IdContratto=$IdContratto"
	       ." AND cl.IdClasse=c.IdClasse AND sr.IdStatoRecupero=c.IdStatoRecupero");
	$classe = $row["CodClasse"];
	$stato  = $row["CodStatoRecupero"];
	// NB la classe DBT si usa come stato finale del vecchio workflow DBT. Nel nuovo, la pratica va in
	// stato di attesa affidamento oppure STR1/STR2/LEG ecc. quindi subisce le regole di affido
	return ($classe == "DBT" || $classe == "EXIT" || $stato == "CLO" || $stato == "CES");
}

//--------------------------------------------------------------
// lavorazioneInterna
// Determina se il contratto dato � in lav. interna oppure in
// workflow. In questo caso funziona la classificazione ma non
// l'affido e l'assegnazione
//--------------------------------------------------------------
function lavorazioneInterna($IdContratto)
{
	$codStato = getScalar("SELECT CodStatoRecupero FROM statorecupero sr,contratto c WHERE IdContratto=$IdContratto"
	       ." AND sr.IdStatoRecupero=c.IdStatoRecupero");
	return ($codStato=="INT" || substr($codStato,0,3)=="WRK");
}
//----------------------------------------------------------------------------------------------
// trovaProvvigioneApplicabile
// Per un dato contratto, esamina le regole provvigionali disponibili e trova quella applicabile
// Il parametro $dataRiferimento serve solo a selezionare le regole valide, cio� quelle in cui
// l'intervallo DataIni-DataFin include la data di riferimento considerata (che � posta uguale 
// al fine lotto standard di 30 gg)
//----------------------------------------------------------------------------------------------
function trovaProvvigioneApplicabile($IdContratto,$IdAgenzia,&$CodProvv="",$dataRiferimento=NULL,&$durata=NULL)
{
	try
	{
		// Legge dati contratto		
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica))
		{
			Throw new Exception("Fallita trovaProvvigioneApplicabile per la pratica n. $IdContratto"); 
		}
		// Data di riferimento: quella passata, oppure la fine affido corrente oppure oggi+1 mese
		if ($dataRiferimento==NULL)
			if ($pratica["$dataRiferimento"]!=NULL)
				$dataRiferimento = ISODate($pratica["DataFineAffido"]);
			else
				$dataRiferimento = ISODate(mktime(0,0,0,date("n")+1,date("j")-1,date("Y")));
		else		
			$dataRiferimento = ISODate($dataRiferimento);

		$row = getRow("SELECT r.IdRegolaProvvigione,CodRegolaProvvigione,Durata"
		             ." FROM assegnazione a LEFT JOIN regolaprovvigione r ON a.IdRegolaProvvigione=r.IdRegolaProvvigione"
					 ." WHERE IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND a.DataFin='$dataRiferimento'");
		if (count($row)>0)
		{
			if ($row["IdRegolaProvvigione"]>0)
			{
				$CodProvv = $row["CodRegolaProvvigione"];
				$durata   = $row["Durata"];
				return $row["IdRegolaProvvigione"];
			}
		}

		//--------------------------------------------------------------------------------------		
		// Legge la classe originaria di assegnazione, se c'�
		//--------------------------------------------------------------------------------------		
		$IdClasse = getScalar("SELECT IdClasse FROM assegnazione WHERE IdContratto=$IdContratto AND IdAgenzia=$IdAgenzia AND DataFin='$dataRiferimento'");
		if (!$IdClasse)
			$IdClasse = $pratica["IdClasse"];
			
		// Seleziona tutte le regole di provvigione della specifica agenzia, in modo che quelle con classe
		// generica siano le ultime e tra quelle siano ultime quelle senza condizione
		$regole = getFetchArray("SELECT r.*,IFNULL(IdClasse,0) AS classe,IFNULL(Condizione,'') AS cond FROM regolaprovvigione r"
		       ." WHERE IdReparto=$IdAgenzia"
			   ." AND '$dataRiferimento' BETWEEN DataIni AND DataFin ORDER BY classe DESC, cond DESC");
		$IdRegola = NULL;
		foreach ($regole as $regola)
		{
			if ($regola["IdFamiglia"]>0) // condizione sulla famiglia di prodotto
				if ($regola["IdFamiglia"]!=$pratica["IdFamiglia"]
				&&  $regola["IdFamiglia"]!=$pratica["IdFamigliaParent"])
					continue;
			if ($regola["classe"]>0) // condizione sulla classificazione
			{
				if ($regola["IdClasse"]!=$IdClasse)
					continue;
			}
			
			if ($regola["Condizione"]>"") // condizione SQL esplicita
			{
				//NB: se � arrivato fin qui, significa che la pratica non � ancora affidata: la condizione
				//    deve perci� essere testata su Contratto
				if (!rowExistsInTable("v_cond_affidamento","IdContratto=$IdContratto AND (".$regola["Condizione"].")") )
				{
					//trace("Condizione '".$regola["Condizione"]."' NON soddisfatta",FALSE);
					continue; // se la condizione non � soddisfatta, itera
				}
				trace("Condizione '".$regola["Condizione"]."' soddisfatta",FALSE);
			}
			
			// Trovata
			$IdRegola = $regola["IdRegolaProvvigione"];
			$CodProvv = $regola["CodRegolaProvvigione"];
			$durata = $regola["durata"]; // att.ne alla minuscola
			break;
		}
		
		if ($IdRegola==NULL)
			trace("Nessuna regola provvigione applicabile al contratto $IdContratto per l'agenzia $IdAgenzia",FALSE);
			
		return $IdRegola;
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace($e->getMessage());
		return NULL;
	}		
}
//---------------------------------------------------------------------------------------------
// aggiornaAreaCliente
// Ricalcola il campo idArea (su Cliente) dopo l'aggiornamento di un recapito 
// (Chiamato dal batch e dalla funzione di editing dei recapiti) 
//---------------------------------------------------------------------------------------------
function aggiornaAreaCliente($IdCliente)
{
	try
	{
// Parte dal presupposto che area sia = regione
		$sql =  "update cliente c LEFT JOIN recapito r ON r.idcliente=c.idcliente and idtiporecapito=1 and r.siglaprovincia>''"
		       ." and not exists (select 1 from recapito x where x.idcliente=c.idcliente and x.idrecapito>r.idrecapito"
		       ." and x.idtiporecapito=1 and x.siglaprovincia>'')"
               ." left join area a on a.tipoarea='R' and a.siglaprovincia=r.siglaprovincia"	
			   ." set c.idarea=a.idareaparent "
			   ." where c.idCliente=$IdCliente";
		return execute($sql);
	}
	catch (Exception $e)
	{
		setLastSerror($e->getMessage());
		trace($e->getMessage());
		return NULL;
	}		
}
//-----------------------------------------------------------------------
// annullaForzaturaAffido
// Elimina le informazioni create con la forzatura affido (chiamata
// da automatismo sull'annullamento/rifiuto della proposta DBT)
//-----------------------------------------------------------------------
function annullaForzaturaAffido($contratto)
{
	$row = getRow("SELECT FlagForzaSeDBT,IdAgenzia,CodRegolaProvvigione FROM contratto WHERE IdContratto=$contratto");	
	if ($row["IdAgenzia"]>0) // in affido
	{
		beginTrans();
		$sql = "UPDATE assegnazione SET IdAffidoForzato=NULL WHERE IdContratto=$contratto AND DataFin>CurDate()";
		if (!execute($sql))
		{
			rollback();
			return FALSE;
		}
		// aggiorna il flag sul contratto
		$sql = "UPDATE contratto SET FlagForzaSeDBT=NULL WHERE IdContratto=$contratto";		
	}	
	else // non in affido
		$sql = "UPDATE contratto SET FlagForzaSeDBT=NULL,CodRegolaProvvigione=NULL WHERE IdContratto=$contratto";		

	if (!execute($sql))
	{
		rollback();
		return FALSE;
	}
	commit();
	return TRUE;
}

//-----------------------------------------------------------------------
// forzaAffidoAgenzia
// Segna nella riga della tabella "assegnazione" l'IdRegolaProvvigione
// scelto dall'utente (con l'azione "Forza prossimo affido")
// Input: 1) IdContratto
// 		  2) IdRegolaProvvigione, oppure 0 (forza lav. interna), oppure -1 (toglie forzatura), o -2 (affida a legale da decidere)
//        3) eventuale nota
//        4) IdAzione (non "NULL" se si vuole che nella storia compaia il codice azione)
//        5) inRevoca (true se chiamata dalla revoca (per ripristino forzatura prec.)
//        6) soloQuandoDBT (true se la forzatura deve avvenire non prima che la pratica vada in DBT e quindi in ATS)
// Restituisce: nome agenzia, oppure 0 se la forzatura � "non affidare"
// oppure false, se la forzatura � "elimina forzatura precedente"
//-----------------------------------------------------------------------
function forzaAffidoAgenzia($contratto,$IdRegola,$nota="",$idAzione="NULL",$inRevoca=false,$soloQuandoDBT=false)
{
	try
	{
		switch ($IdRegola)
		{
			case -2:  // la riga di combo con valore -2 significa "affida a legale (da decidere)"
				break;
			case -1:  // la riga di combo con valore -1 significa "elimina forzatura precedente"
				$IdRegola = "NULL";
				break;
			case 0:   // significa forza lav. interna al rientro (lo 0 viene registrato nel campo, che non
				      // � foreign key per questo motivo
				break;
			default: // altri valori
				$dati = getRow("SELECT TitoloUfficio,CodRegolaProvvigione,
					IF (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' OR FasciaRecupero = 'LEGALE',25,2) AS Stato FROM reparto r,regolaprovvigione rp
				    WHERE r.IdReparto=rp.IdReparto AND rp.IdRegolaProvvigione=$IdRegola");
				$nome  = $dati["TitoloUfficio"]." (".$dati["CodRegolaProvvigione"].")";
				$stato = $dati["Stato"];
				break;
		}

		// Legge il contratto: se � in affido, viene segnato il prossimo affido sulla riga di assegnazione
		// altrimenti viene segnato il codice provvigione (futuro) sul contratto, come si fa nel caso di affido con
		// codice provvigione; se la richieste � no-affido, viene messo il contratto in lavorazione interna, in modo che
		// non venga affidato.
		$row = getRow("SELECT IdAgenzia,IdStatoRecupero FROM contratto WHERE IdContratto=$contratto");
		$IdAgenzia = $row["IdAgenzia"];
		$IdStatoRecupero = $row["IdStatoRecupero"];
		
		beginTrans();
		if ($IdAgenzia>0) // contratto attualmente in affido
		{
			// Aggiorna la riga di assegnazione relativa all'affido attuale (anche con i valori NULL e 0)
			$sql =  "UPDATE assegnazione a JOIN contratto c ON c.IdContratto=a.IdContratto"
				.	" SET IdAffidoForzato=$IdRegola "
				.   " WHERE c.IdContratto=$contratto AND a.DataFin=c.DataFineAffido AND a.IdAgenzia=c.IdAgenzia";
			if (!execute($sql))
			{
				rollback();
				return false;
			}
			// imposta il flag per rinviare la forzatura al DBT
			$sql = "UPDATE contratto SET FlagForzaSeDBT='".($soloQuandoDBT?"Y":"N")."'"
					 . " WHERE IdContratto=$contratto";
			if (!execute($sql))
			{
				rollback();
				return false;
			}
		}
		else // contratto non in affido
		{
			if ($IdRegola=="NULL")	// reset forzatura precedente: pulisce il campo CodRegolaProvvigione
				$sql = "UPDATE contratto SET CodRegolaProvvigione=NULL WHERE IdContratto=$contratto";
			else if ($IdRegola==0)  // richiesto di non affidare al prossimo giro, mette in stato lav. interna, se
									// la pratica era in attesa di affido
			{
				if ($IdStatoRecupero==2 || $IdStatoRecupero==25) //
				{
					$sql = "UPDATE contratto SET IdStatoRecupero=13,DataCambioStato=CURDATE(),CodRegolaProvvigione=NULL WHERE IdContratto=$contratto";
					inizioLavorazioneInterna($contratto); // prepara riga di Assegnazione
				}
			}
			else if ($IdRegola==-2)  // richiesto affido generico a legale
			{
				$sql = "UPDATE contratto SET CodRegolaProvvigione='-2' WHERE IdContratto=$contratto";
			}
			else	// forzata una particolare regola e messo in attesa di affido (normale o strag.)	
			{
				if ($soloQuandoDBT) // forzatura senza cambio di stato (chiamata da wf dbt)
				{						
					$sql = "UPDATE contratto SET CodRegolaProvvigione='".$dati["CodRegolaProvvigione"]."'"
					 . ",FlagForzaSeDBT='Y' WHERE IdContratto=$contratto";
				}
				else // forza anche lo stato di attesa giusto
				{
					$sql = "UPDATE contratto SET IdStatoRecupero=$stato,DataCambioStato=CURDATE(),CodRegolaProvvigione='".$dati["CodRegolaProvvigione"]."'"
					 . ",FlagForzaSeDBT='N' WHERE IdContratto=$contratto";
				}
			}
			if (!execute($sql))
			{
				rollback();
				return false;
			}
		}
		
		if ($IdRegola==NULL)
			$esitoAzione = "Eliminata forzatura del prossimo affidamento automatico";
		else if ($IdRegola==0)
			$esitoAzione = "Al rientro dall' affidamento la pratica sar� messa in lavorazione interna ";
		else if ($inRevoca) // forzatura chiamata durante revoca per riprodurre la forzatura precedente
			$esitoAzione = "Mantenuta forzatura del prossimo affidamento automatico all'agenzia ".$nome;
		else
			$esitoAzione = "Registrata forzatura del prossimo affidamento automatico all'agenzia ".$nome;
		writeHistory($idAzione,$esitoAzione,$contratto,$nota);	
		
		// se la classificazione era EXIT, la toglie
		if ($IdRegola!="NULL")
			toglieClasseExit($contratto);
			
		commit();
		return $nome;		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}

//---------------------------------------------------------------------------------
// inizioLavorazioneInterna
// Crea una riga di assegnazione per un contratto messo in lavorazione interna
// e aggiorna corrispondentemente IdAffidamento in Insoluto. Quando viene 
// chiamata, si presume che il contratto sia stato appena messo in lav.interna; 
// se esiste gi� una vecchia assegnazione alla lav.interna aperta la riusa
//---------------------------------------------------------------------------------
function inizioLavorazioneInterna($contratto)
{
	try
	{
		$userid = getUserName();
		//-------------------------------------------------------------------------------------
		// Annulla gli eventuali affidi forzati 
		//-------------------------------------------------------------------------------------
		if (!annullaForzaturaAffido($contratto))
			return FALSE;
		
		beginTrans();
		//-------------------------------------------------------------------------------------
		// Aggiorna Assegnazione
		//-------------------------------------------------------------------------------------
		$row = getRow("SELECT IdAssegnazione,IdAgenzia FROM assegnazione WHERE IdContratto=$contratto"
		             ." AND DataFin>=CURDATE()");
		if (is_array($row))
		{
			if ($row["IdAgenzia"]>0)
			{
				trace("Chiamata funzione inizioLavorazioneInterna per contratto $contratto ancora in affido ad agenzia n. ".$row["IdAgenzia"],FALSE);
				return FALSE;
			}
			$IdAffidamento = $row["IdAssegnazione"]; // chiave riga da riusare
		}
		else // riga da inserire
		{
			$dati = getRow("SELECT IdOperatore,IdClasse,InteressiMora,SpeseIncasso FROM v_dettaglio_insoluto WHERE IdContratto=$contratto");
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$contratto,"N");	 	
			addInsClause($colList,$valList,"IdOperatore",$dati["IdOperatore"],"N");	 	
			addInsClause($colList,$valList,"IdClasse",$dati["IdClasse"],"N");	 	
			addInsClause($colList,$valList,"DataIni","CURDATE()","G");		
			addInsClause($colList,$valList,"DataFin","9999-12-31","S"); 		
			addInsClause($colList,$valList,"LastUser",$userid,"S");
			addInsClause($colList,$valList,"ImpInteressiMora",$dati["InteressiMora"],"N");
	// nonostante il nome, PercSpeseRecupero contiene l'importo delle spese
			addInsClause($colList,$valList,"PercSpeseRecupero",$dati["SpeseIncasso"],"N");
			if (!execute("INSERT INTO assegnazione ($colList) VALUES ($valList)"))
			{
				rollback();
				return FALSE;
			}
			$IdAffidamento = getInsertId(); // ID generato dall'INSERT
			
		}
				
		//-------------------------------------------------------------------------------------
		// Modifica i campi ImpDebitoIniziale e ImpCapitaleAffidato in "insoluto" per riflettere 
		// il valore attuale (a inizio affido) del debito da recuperare; imposta anche il campo IdAffidamento
		// per collegare gli insoluti all'Assegnazione 
		//-------------------------------------------------------------------------------------
		$sql = "UPDATE insoluto SET ImpDebitoIniziale=ImpInsoluto,ImpCapitaleAffidato=IF(ImpCapitale-ImpPagato>0 AND ImpDebitoIniziale>0,LEAST(ImpCapitale-ImpPagato,ImpDebitoIniziale),0),"
			  ."IdAffidamento=$IdAffidamento WHERE IdContratto=$contratto";
		if (!execute($sql))
		{
			rollback();
			return FALSE;
		}
		
		commit();
		return TRUE;
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}
}
//--------------------------------------------------------------
// insertPianoRientro
// Registra il piano di rientro 
//--------------------------------------------------------------
function insertPianoRientro($IdContratto,$importo,$numRate,$dataPrimaRata)
{
	global $context;
	try
	{
		beginTrans();
		$sql = "INSERT INTO pianorientro (IdContratto, IdStatoPiano,DataAccordo,DataIni,DataFin,lastupd,LastUser)"
			  ." VALUES ($IdContratto, null, now(), now(), '9999-12-31', now(),'".getUserName()."') ";
			  
		if(execute($sql))
		{

			$IdPianoRientro = getInsertId();
		
			$importoRata = $importo / $numRate ;// importo della rata
			
			$dataRata = $dataPrimaRata; // datarata

			for($i=0; $i < $numRate; $i++)
			{
				if(!inserisciRate($IdContratto,$importoRata,$dataRata,$i+1,$IdPianoRientro))
				{
					rollback();
					return false;
				}
				$dataRata =date("Y-m-d",mktime(0,0,0,substr($dataRata, 2, 2) + 1,substr($dataRata, 0, 2),substr($dataRata, 6, 4)));
			}
			
			commit();
			return true;		
		} 		  
		else
		{
			return false;
		}
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		return false;
	}
}

//--------------------------------------------------------------
// inserisciRate
// Inserisce le rate del piano di rientro
//--------------------------------------------------------------
function inserisciRate($IdContratto,$importo,$dataRata,$numRata,$IdPianoRientro)
{
	global $context;
	try
	{
		$sql = "INSERT INTO ratapiano (IdPianoRientro,NumRata, DataPrevista,Importo,lastupd,LastUser)"
			  ." VALUES ($IdPianoRientro, $numRata, '$dataRata', $importo,now(),'".getUserName()."') ";
			  
		if(execute($sql))
				return true;
		else
			return false;
	}
	catch (Exception $e)
	{
			trace($e->getMessage());
			return false;
	}
}
//--------------------------------------------------------------------
// chiudeAffidamentoInterno
// Chiude una riga di affidamento e la cancella se risulta essere di
// durata nulla
//--------------------------------------------------------------------
function chiudeAffidamentoInterno($contratto)
{
	$row = getRow("SELECT DataIni,IdAssegnazione FROM assegnazione WHERE IdContratto=$contratto AND DataFin>=CURDATE() AND IdAgenzia IS NULL");
	if (count($row)>0)
	{	// elimina riferimenti a questa assegnazione
		$ida = $row["IdAssegnazione"];
		if (!execute("UPDATE insoluto SET IdAffidamento=NULL WHERE IdAffidamento=$ida"))
			return FALSE;
		if (!execute("UPDATE storiainsoluto SET IdAffidamento=NULL WHERE IdAffidamento=$ida"))
			return FALSE;
		
		$dataIni = ISODate($row["DataIni"]);
		$dataFin = ISODate(mktime(0,0,0,date("n"),date("j")-1,date("Y")));
		if ($dataFin<$dataIni) // periodo nullo: cancella la riga di assegnazione
			$sql = "DELETE FROM assegnazione WHERE IdContratto=$contratto AND DataFin>=CURDATE() AND IdAgenzia IS NULL";
		else
			$sql = "UPDATE assegnazione SET DataFin=CURDATE()-INTERVAL 1 DAY WHERE IdContratto=$contratto AND DataFin>=CURDATE() AND IdAgenzia IS NULL";
		return execute($sql);
	}
	else
		return TRUE; // no-op
}

//--------------------------------------------------------------------
// annullaForzaturePrecedenti
// Elimina le richieste di forzatura affido esistenti (quando si apre
// un workflow o si passa in DBT)
//--------------------------------------------------------------------
function annullaForzaturePrecedenti($contratto)
{
	// annulla eventuale forzatura su contratto
	if (!execute("UPDATE contratto SET CodRegolaProvvigione=NULL WHERE IdContratto=$contratto AND IdAgenzia IS NULL"))
		return FALSE;
	// annulla eventuale forzatura su assegnazione corrente
	return execute("UPDATE assegnazione SET IdAffidoForzato=NULL WHERE IdContratto=$contratto AND DataFin>=CURDATE()");
}


//----------------------------------------------------------------//
// gestisce l'inserimento della dataVendita e dei flag di input   //
// all'azione passaggio in DBT nella tabella contratto e cliente  //
//----------------------------------------------------------------//
function udpdateCampiPropostaDBT($flagIrreperibile,$flagIpoteca,$flagConcorsuale,$idContratto,$idCliente,$dataVendita="NULL")
{
	try
	{
		beginTrans();
				        
		if(!execute("UPDATE contratto SET DataVendita=".$dataVendita." WHERE IdContratto = $idContratto")) {
		  rollback();
		  return false;	
		}
				  
		if (!execute("UPDATE cliente SET FlagIrreperibile='$flagIrreperibile' WHERE IdCliente = $idCliente")){
		  rollback();
		  return false;	
		}
		
		if (!execute("UPDATE contratto SET FlagIpoteca='$flagIpoteca' WHERE IdContratto = $idContratto")){
		  rollback();
		  return false;	
		}
				
		if (!execute("UPDATE contratto SET FlagConcorsuale='$flagConcorsuale' WHERE IdContratto = $idContratto")){
		  rollback();
		  return false;	
		}
		commit();
		return true;
	}	
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}		
}
//----------------------------------------------------------------//
// gestisce l'inserimento dei dati di saldo e stralcio            //
//----------------------------------------------------------------//
function udpdateCampiPropostaSS($idContratto,$dataSS,$impSS)
{
	try
	{
		if ($impSS==NULL) // si tratta di un annullamento/rifiuto
			$sql = "UPDATE contratto SET DataSaldoStralcio=NULL,ImpSaldoStralcio=NULL WHERE IdContratto = $idContratto";
		else if ($dataSS != NULL) // saldo e stralcio semplice 
		  	$sql = "UPDATE contratto SET DataSaldoStralcio='".$dataSS."',ImpSaldoStralcio=".$impSS." WHERE IdContratto = $idContratto";
		else  // saldo e stralcio dilazionato, la data � nel piano di rientro
			$sql = "UPDATE contratto SET DataSaldoStralcio=NULL,ImpSaldoStralcio=".$impSS." WHERE IdContratto = $idContratto";
			
		return execute($sql);
	}	
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}		
}
//--------------------------------------------------------------
// insertCampiPianoRientro
// Registra il piano di rientro 
//--------------------------------------------------------------
function insertCampiPianoRientro($IdContratto,$primoImporto,$dataPrimoImporto,$numRate,$decorrenzaRata,$importoRata)
{
	global $context;
	try
	{
		beginTrans();
		$impRata = str_replace(',','.',str_replace('.','',$importoRata));
		$impPrimoImp = str_replace(',','.',str_replace('.','',$primoImporto));
		$sql = "INSERT INTO pianorientro (IdContratto, IdStatoPiano,DataAccordo,DataIni,DataFin,lastupd,LastUser,PrimoImporto,DataPagPrimoImporto,NumeroRate,DecorrenzaRate,ImportoRata)"
			  ." VALUES ($IdContratto, 1, null, now(), '9999-12-31', now(),'".getUserName()."',$impPrimoImp,'$dataPrimoImporto',$numRate,'$decorrenzaRata',$impRata) ";
		if(execute($sql))
		{

			$IdPianoRientro = getInsertId();
		
			//$importoRata = $importo / $numRate ;// importo della rata
			
			$dataRata = $decorrenzaRata; // datarata
			
			//Inserimento tra le rate il primo importo da pagare
			$sqlPrimaRata = "INSERT INTO ratapiano (IdPianoRientro,NumRata, DataPrevista,Importo,lastupd,LastUser)"
			      ." VALUES ($IdPianoRientro, 1, '$dataPrimoImporto', $impPrimoImp,now(),'".getUserName()."') ";
			      
			if(!execute($sqlPrimaRata)) {
			  rollback();
			  return false;	
			}      

			for($i=0; $i < $numRate; $i++)
			{
				if(!inserisciRate($IdContratto,$impRata,$dataRata,$i+2,$IdPianoRientro))
				{
					rollback();
					return false;
				}
				$dataRata =date("Y-m-d",mktime(0,0,0,substr($dataRata, 5, 2) + 1,substr($dataRata, 8, 2),substr($dataRata, 0, 4)));
			}
			
			commit();
			return true;		
		} 		  
		else
		{
			return false;
		}
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		return false;
	}
}

//--------------------------------------------------------------
// updateCampiPianoRientro
// Modifica i capmi del piano di rientro dilazionato
//--------------------------------------------------------------
function updateCampiPianoRientro($IdContratto,$primoImporto,$dataPrimoImporto,$numRate,$decorrenzaRata,$importoRata)
{
	global $context;
	try
	{
		$impRata = str_replace(',','.',$importoRata);
	    if(!execute("UPDATE pianorientro SET PrimoImporto='".$primoImporto."',DataPagPrimoImporto='".$dataPrimoImporto."', "
	                ."NumeroRate='".$numRate."',DecorrenzaRate='".$decorrenzaRata."', ImportoRata='".$importoRata."' "
	                ."WHERE IdContratto = $IdContratto")) 
		  return false;	
		
		$IdPianoRientro = getScalar("SELECT IdPianoRientro FROM pianorientro where IdContratto = $IdContratto");
		
		//$importoRata = $importo / $numRate ;// importo della rata
			
		beginTrans();
		//prima di aggiornare la tabella ratapiano elimino le vecchie rate 
		//per evitare l'errore nel caso in cui cambiasse il numero di rate 
		if ($IdPianoRientro>0) {
			if (!execute("delete from ratapiano where IdPianorientro=$IdPianoRientro")) {
				rollback();
				return false;	
			}
		}
		  	
	    $dataRata = $decorrenzaRata; // datarata  
			
		//Inserimento tra le rate il primo importo da pagare
		$sqlPrimaRata = "INSERT INTO ratapiano (IdPianoRientro,NumRata, DataPrevista,Importo,lastupd,LastUser)"
		      ." VALUES ($IdPianoRientro, 1, '$dataPrimoImporto', $primoImporto,now(),'".getUserName()."') ";
			      
		if(!execute($sqlPrimaRata)) {
			rollback();
			return false;	
		}      

		for($i=0; $i < $numRate; $i++)
		{
			if(!inserisciRate($IdContratto,$impRata,$dataRata,$i+2,$IdPianoRientro))
			{
				rollback();
				return false;
			}
			$dataRata =date("Y-m-d",mktime(0,0,0,substr($dataRata, 5, 2) + 1,substr($dataRata, 8, 2),substr($dataRata, 0, 4)));
		}
		commit();		
		return true;		
	}
	catch (Exception $e)
	{
		rollback();
		trace($e->getMessage());
		return false;
	}
}

//----------------------------------------------------------------------------------------------
// inserisciAllegatiCessione
// inserisce nella cartella allegati gli allegati del contratto in cessione 
// Argomenti: 1) riga di v_pratiche
//            2) titolo allegato
//            3) url allegato
//            4) nome allegato
//----------------------------------------------------------------------------------------------
function inserisciAllegatiCessione($pratica,$titolo,$urlFile,$fileName)
{
	
	try
	{
		global $context;
		$file = dirname(__FILE__).'/../'.$urlFile;
		
		if(!get_magic_quotes_gpc())
			$fileName = addslashes($fileName);
		
		// 14/8/2011: per evitare problemi di permissione, genera il file in un subfolder che si chiama come lo userid
		// del processo corrente
		$processUser = posix_getpwuid(posix_geteuid());
		$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/Allegati";
		if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
				Throw new Exception("Impossibile creare la cartella dei documenti");				
		
	    if (copy($file, $localDir."/".$fileName)) {
          $idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
		  writeHistory($idAzione,"Copiato allegato nella cartella cessioni",$pratica['IdContratto'],"Documento: $titolo Contratto:".$pratica['CodContratto']);				
	      return TRUE;
        } else {
        	setLastError("Impossibile copiare il file nel repository");
			trace("Impossibile copiare il file nel repository");
			return FALSE;
        }
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}	
}

//----------------------------------------------------------------------------------------------
// creaEXLDatiCessione
// crea foglio exel dell'estratto conto nella cartella Dati del contratto in cessione 
// Argomenti: 1) riga di v_pratiche
//            2) nome colonne foglio exel
//            3) dati foglio exel
//            4) titolo foglio exel 
//----------------------------------------------------------------------------------------------
function creaEXLDatiCessione($pratica,$colonne,$dati,$titolo) {
	
	//$titolo="Storico recupero";	
	$nomeFile=$titolo.".xls";

	$columns=array();
	$id=0;
	//trace("dati ".print_r($dati['results'][0],true));
	$chiavi=array_keys($colonne['results'][0]);
	//trace("chiavi ".print_r($colonne['results'][0],true),false);
	foreach($chiavi as $elemento)
	{
		$columns[$id]['dataIndex']=$elemento;
		//$columns[$id]['header']=$elemento;
		switch($elemento)
		{
			case "NumRata":
				$columns[$id]['width']=40;
				$columns[$id]['header']="Rata";
				break;
			case ($elemento=="DataCreazione" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']="Data creazione";
				break;
			case ($elemento=="DataScadenza" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']="Data scadenza";
				break;
			case ($elemento=="DataRegistrazione" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']="Data registrazione";
				break;
			case ($elemento=="DataCompetenza" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']="Data competenza";
				break;	
			case ($elemento=="DataValuta" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']="Data valuta";
				break;			
			case ($elemento=="TitoloTipoMovimento" ): 
				$columns[$id]['width']=180;
				$columns[$id]['header']="Tipo movimento";
				break;
			case ($elemento=="TitoloTipoInsoluto" ): 
				$columns[$id]['width']=180;
				$columns[$id]['header']="Causale insoluto";
				break;	
			case ($elemento=="Debito" || $elemento=="Credito"): 
				$columns[$id]['width']=70;
				$columns[$id]['header']=$elemento;
				break;
			case ($elemento=="Data" ): 
				$columns[$id]['width']=100;
				$columns[$id]['header']=$elemento;
				break;
			case ($elemento=="Utente" ): 
				$columns[$id]['width']=60;
				$columns[$id]['header']=$elemento;
				break;
			case ($elemento=="DescrEvento" ): 
				$columns[$id]['width']=250;
				$columns[$id]['header']="Descrizione evento";
				break;
			case ($elemento=="Nota" ): 
				$columns[$id]['width']=400;
				$columns[$id]['header']=$elemento;
				break;
			case ($elemento=="TestoNota" ): 
				$columns[$id]['width']=400;
				$columns[$id]['header']="Nota";
				break;	
			case ($elemento=="Controparte" ): 
				$columns[$id]['width']=125;
				$columns[$id]['header']=$elemento;
				break;
			case ($elemento=="TitoloTipoRecapito" ): 
				$columns[$id]['width']=125;
				$columns[$id]['header']="Tipo recapito";
				break;
			case ($elemento=="Indirizzo" ): 
				$columns[$id]['width']=200;
				$columns[$id]['header']=$elemento;
				break;			
			case ($elemento=="Recapiti" ): 
				$columns[$id]['width']=200;
				$columns[$id]['header']="Telefoni e indirizzo di posta";
				break;
			case ($elemento=="numPratica" ): 
				$columns[$id]['width']=60;
				$columns[$id]['header']="N.Pratica";
				break;
			case ($elemento=="Prodotto" ): 
				$columns[$id]['width']=110;
				$columns[$id]['header']=$elemento;
				break;			
			case ($elemento=="Stato" ): 
				$columns[$id]['width']=90;
				$columns[$id]['header']="Stato contratto";
				break;	
			case ($elemento=="StatoRecupero" ): 
				$columns[$id]['width']=90;
				$columns[$id]['header']="Stato recupero";
				break;	
			case ($elemento=='Ruolo' || $elemento=='Agenzia' || $elemento=="Finanziato" || $elemento=="Impagato" ): 
				$columns[$id]['width']=90;
				$columns[$id]['header']=$elemento;
				break;	
			/*default:
				$columns[$id]['header']=$elemento; 
				$columns[$id]['width']=100;
				break;*/
			
		}
		$id++;
	}
	//conversione in standard object
	$columns = json_encode_plus($columns);
	$columns = json_decode($columns);
	
	// 14/8/2011: per evitare problemi di permissione, genera il file in un subfolder che si chiama come lo userid
	// del processo corrente
	$processUser = posix_getpwuid(posix_geteuid());
	$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/Dati";
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
		if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
			Throw new Exception("Impossibile creare la cartella dei documenti");
		
	$momW = '<?xml version="1.0" encoding="utf-8"?><?mso-application progid="Excel.Sheet"?>';
	$number=file_put_contents($localDir."/$nomeFile",$momW);
	
	$dataCreazione=date("Y-m-d\TH:i\Z");
	$momW = <<<EOT
		<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"  
					xmlns:x="urn:schemas-microsoft-com:office:excel" 
					xmlns:o="urn:schemas-microsoft-com:office:office" 
					xmlns:html="http://www.w3.org/TR/REC-html40">
			<o:DocumentProperties>
				<o:Title>$titolo</o:Title>
				<o:Created>$dataCreazione</o:Created>
			</o:DocumentProperties>
			<ss:ExcelWorkbook>
				<ss:WindowHeight>9240</ss:WindowHeight>
				<ss:WindowWidth>50000</ss:WindowWidth>
				<ss:ProtectStructure>false</ss:ProtectStructure>
				<ss:ProtectWindows>false</ss:ProtectWindows>
			</ss:ExcelWorkbook>
			<ss:Styles>
				<ss:Style ss:ID="Default" ss:Name="Normal">
					<ss:Alignment ss:Vertical="Top" ss:WrapText="0" />
					<ss:Font ss:FontName="arial" ss:Size="10" />
					<ss:Interior />
					<ss:NumberFormat />
					<ss:Protection />
					<ss:Borders />
				</ss:Style>
				<ss:Style ss:ID="headercell">
					<ss:Font ss:Bold="1" ss:Size="10" />
					<ss:Interior ss:Pattern="Solid" ss:Color="#C0C0C0" />
					<ss:Alignment ss:WrapText="0" ss:Horizontal="Center" />
				</ss:Style>
				<ss:Style ss:ID="dec">
					<ss:NumberFormat ss:Format="[$-410]#,##0.00"/>
				</ss:Style>
			</ss:Styles>
			<ss:Worksheet ss:Name="$titolo">
				<ss:Names>
					<ss:NamedRange ss:Name="Print_Titles" ss:RefersTo="='$titolo'!R1:R1" />
				</ss:Names>
EOT;
	$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	
	$momW = '<ss:Table x:FullRows="1" x:FullColumns="1" ss:ExpandedColumnCount="'.
				count($columns).'" ss:ExpandedRowCount="'.(1+$dati->total).'">';
	$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($columns as $col) {
		$momW = '<ss:Column ss:AutoFitWidth="1" ss:Width="'.$col->width.'"/>';
		$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	}
	$momW = '<ss:Row ss:AutoFitHeight="1">';
	$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($columns as $col) {
		$momW = '<ss:Cell ss:StyleID="headercell"><ss:Data ss:Type="String">'.$col->header.'</ss:Data><ss:NamedCell ss:Name="Print_Titles" /></ss:Cell>';
		$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	}
	$momW =  "</ss:Row>\n";
	$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($dati->results as $row) {
		$momW = '<ss:Row>';
		$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
		foreach ($columns as $col) {
			$fld = $col->dataIndex;
			$v = $row->$fld;
			if ($col->align=='right') {
				if (preg_match("/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/", $v)) {	// numero decimale con separatori italiani
					$momW = '<ss:Cell ss:StyleID="dec">';
					$v = str_replace(',','.',str_replace('.','',$v));
				} else {
					$momW = '<ss:Cell>';
				}
				$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
				
				$momW = '<ss:Data ss:Type="Number">'.$v.'</ss:Data></ss:Cell>';
				$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
			} else {
				$momW = '<ss:Cell><ss:Data ss:Type="String"><![CDATA['.$v.']]></ss:Data></ss:Cell>';
				$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
			}
		}
		$momW = "</ss:Row>\n";
		$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND);
	}

	$momW = <<<EOT
				</ss:Table>
					  
				<x:WorksheetOptions>
					<x:PageSetup>
						<x:Layout x:CenterHorizontal="1" x:Orientation="Landscape" />
						<x:Footer x:Data="Page &amp;P of &amp;N" x:Margin="0.5" />
						<x:PageMargins x:Top="0.5" x:Right="0.5" x:Left="0.5" x:Bottom="0.8" />
					</x:PageSetup>
					<x:Print>
						<x:PrintErrors>Blank</x:PrintErrors>
						<x:FitWidth>1</x:FitWidth>
						<x:FitHeight>32767</x:FitHeight>
						<x:ValidPrinterInfo />
						<x:VerticalResolution>600</x:VerticalResolution>
					</x:Print>
					<x:FitToPage /><x:Selected />
					<x:ProtectObjects>False</x:ProtectObjects>
					<x:ProtectScenarios>False</x:ProtectScenarios>
				</x:WorksheetOptions>
			</ss:Worksheet>
		</ss:Workbook>
EOT;
	$number=file_put_contents($localDir."/$nomeFile",$momW,FILE_APPEND); 

	$File=array();
	$File['tmp_name'] = $localDir."/$nomeFile";
	$File['name'] = $nomeFile;
	$File['type'] = filetype($localDir."/$nomeFile");
	
}

//----------------------------------------------------------------------------------------------
// creaDOCDatiCessione
// crea file doc dei dati contratto nella cartella Dati del contratto in cessione 
// Argomenti: 1) riga di v_pratiche
//            2) nome del file doc
//            3) titolo del file xml preso a modello
//----------------------------------------------------------------------------------------------
function creaDOCDatiCessione($pratica,$FileName,$TitoloModello){
	
	try {
		$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/Dati";
		if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
				Throw new Exception("Impossibile creare la cartella dei documenti");
		$modelText = file_get_contents(TEMPLATE_PATH.'/'.$FileName);
		$p1 = strpos($modelText, '<w:body>')+8;
		$p2 = strpos($modelText, '<w:sectPr'); // a volte ha attributi, percio' non cerca <w:sectPr> con  ">" di chiusura
	//	$p2 = strpos($modelText, '<w:sectPr>');
		$p3 = strpos($modelText, '</w:body>');
		$header  = substr($modelText,0,$p1);
		$body 	 = substr($modelText,$p1,$p2-$p1);
		$section = substr($modelText,$p2,$p3-$p2);
		$footer  = substr($modelText,$p3);
		
		$textToPrint .= $header;
		
		$praticaC = htmlentities_deep(getRow("SELECT v.CodCliente, v.NomeCliente, v.Prodotto, v.DataNCli, v.sesso, v.LuogoNCli, v.NomePuntoVendita,".
	                                         " v.NomeVenditore, v.AutoreOverride,v.TitTipoSpec, v.area, v.Venditore, v.Giorni, v.StatoRecupero, v.Classificazione,".
			  							     " v.NomeUtente, v.NomeAgenzia, v.NumRate, v.NumMesiDilazione, v.CodBene, v.DescrBene, v.Garanzie, v.TipoPagamento, v.Rata,".
											 " replace(replace(replace(format(IFNULL(v.ImpInsoluto,0),2),'.',';'),',','.'),';',',') AS Insoluto, replace(replace(replace(format(IFNULL(v.ImpValoreBene,0),2),'.',';'),',','.'),';',',') AS ImpValoreBene,".
											 " replace(replace(replace(format(IFNULL(v.ImpFinanziato,0),2),'.',';'),',','.'),';',',') AS ImpFinanziato, replace(replace(replace(format(IFNULL(v.ImpAnticipo,0),2),'.',';'),',','.'),';',',') AS ImpAnticipo,".
											 " replace(replace(replace(format(IFNULL(v.ImpErogato,0),2),'.',';'),',','.'),';',',') AS ImpErogato, replace(replace(replace(format(IFNULL(v.ImpRataFinale,0),2),'.',';'),',','.'),';',',') AS ImpRataFinale,".
											 " replace(replace(replace(format(IFNULL(v.ImpRiscatto,0),2),'.',';'),',','.'),';',',') AS ImpRiscatto, replace(replace(replace(format(IFNULL(v.ImpInteressi,0),2),'.',';'),',','.'),';',',') AS ImpInt,".
											 " replace(replace(replace(format(IFNULL(v.ImpSpeseIncasso,0),2),'.',';'),',','.'),';',',') AS ImpSpInc, replace(replace(replace(format(IFNULL(v.ImpRata,0),2),'.',';'),',','.'),';',',') AS ImportoRata,".
											 " replace(replace(replace(format(IFNULL(v.ImpInteressiDilazione,0),2),'.',';'),',','.'),';',',') AS ImpInteressiDilazione,".
											 " replace(format(IFNULL(v.PercTasso,0),2),'.',',') AS PercTasso, replace(format(IFNULL(v.PercTaeg,0),2),'.',',') AS PercTaeg, replace(format(IFNULL(v.PercTassoReale,0),2),'.',',') AS PercTassoReale,".
											 " DATE_FORMAT(v.DataScadenza,'%d/%m/%Y') AS DataScadenza, DATE_FORMAT(v.DataInizioAffido,'%d/%m/%Y') AS DataInizioAffido, DATE_FORMAT(v.DataFineAffido,'%d/%m/%Y') AS DataFineAffido,".
											 " DATE_FORMAT(v.DataNCli,'%d/%m/%Y') AS DataNCli, DATE_FORMAT(v.DataContratto,'%d/%m/%Y') AS DataContratto, DATE_FORMAT(v.DataUltimaScadenza,'%d/%m/%Y') AS DataUltimaScadenza,".
											 " DATE_FORMAT(v.DataPrimaScadenza,'%d/%m/%Y') AS DataPrimaScadenza, DATE_FORMAT(v.DataChiusura,'%d/%m/%Y') AS DataChiusura, DATE_FORMAT(v.DataDecorrenza,'%d/%m/%Y') AS DataDecorrenza,".
											 " CONCAT(if(v.TitoloCategoria!='null',CONCAT('(Categoria: ', v.TitoloCategoria,') '),''),".
	                                         " if(a.TitoloAzione!='null',CONCAT('(Ultima azione: ', a.TitoloAzione,')'),'') ) AS Azione,". 
											 " b.TitoloBanca as Banca, b.Telefono as TelBan, v.IBAN, v.CAB, v.ABI,".
											 " if(v.IBAN is null AND v.CAB is null AND v.ABI is null,'',ifnull(os.Soggetto,v.NomeCliente)) AS Intestatario".
											 " FROM v_pratiche as v". 
											 " left join banca b on v.abi=b.abi and v.cab=b.cab". 
											 " left join v_altri_soggetti os on v.IdContratto=os.IdContratto AND os.CodTipoControparte='ICC'". 
											 " left join pianorientro pr on pr.IdContratto=v.IdContratto". 
											 " left join azione a on a.TitoloAzione = (select a.TitoloAzione". 
											 " from storiarecupero sr". 
											 " left join azionetipoazione ata on ata.IdAzione = sr.IdAzione and ata.IdTipoAzione in (9,14,15)". 
											 " left join azioneprocedura ap on sr.IdAzione = ap.IdAzione". 
											 " left join azione a on a.IdAzione=ata.IdAzione or a.IdAzione = ap.IdAzione".
											 " where sr.IdContratto=".$pratica['IdContratto'] ." having a.TitoloAzione IS NOT NULL order by sr.IdStoriaRecupero desc limit 1)". 
											 " WHERE v.IdContratto=".$pratica['IdContratto'] ." limit 1"));
		$textToPrint .= replaceVariables($body,$praticaC,'');
			
		$textToPrint .= $section;
		$textToPrint.=$footer;
		
		//$suff = count($arrayContratti)==1?$pratica['dataPagamento']:date("Ymd_Hi");
		header("Content-type: application/vnd.ms-word");
		//header("Content-Disposition: attachment; filename=$localDir\"".$TitoloModello.".doc\"");
		$number=file_put_contents($localDir."/$TitoloModello.doc",$textToPrint);
	}	
	catch (Exception $e)
	{
		echo $e->getMessage();
	}
	
}

function creaDocumentazione($idContratto, $pratica) {
	
	//copio gli allegati nella cartella cessioni dell'utente 
	$allegati = getFetchArray("SELECT TitoloAllegato,UrlAllegato From allegato where IdContratto=".$idContratto);
	foreach ($allegati as $allegato)
	{
	 	$titolo=$allegato['TitoloAllegato'];
		$urlFile=$allegato['UrlAllegato'];
		$estensione = explode(".", $urlFile);
		$fileName=$titolo.'.'.$estensione[1];
		inserisciAllegatiCessione($pratica,$titolo,$urlFile,$fileName);
	}
		          
	//creazione foglio exel dell'estratto conto nella cartella cessioni dell'utente 
	$sql = "SELECT NumRata,". 
		   " DATE_FORMAT(DataScadenza,'%d/%m/%Y') AS DataScadenza,". 
		   " DATE_FORMAT(DataRegistrazione,'%d/%m/%Y') AS DataRegistrazione,". 
		   " DATE_FORMAT(DataCompetenza,'%d/%m/%Y')AS DataCompetenza,".
		   " DATE_FORMAT(DataValuta,'%d/%m/%Y') AS DataValuta,". 
		   " TitoloTipoMovimento, TitoloTipoInsoluto,".
		   " If(Debito!='null',replace(replace(replace(format(Debito,2),'.',';'),',','.'),';',','),NULL) AS Debito,". 
		   " If(Credito!='null',replace(replace(replace(format(Credito,2),'.',';'),',','.'),';',','),NULL) AS Credito". 
		   " FROM v_partite where IdContratto=$idContratto ORDER BY NumRata";
	//preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);
	$count = getScalar("SELECT count(*) FROM v_partite where IdContratto=$idContratto ORDER BY NumRata");
	$resp='({"total":"' . $count . '","results":' . $data . '})';
    $ContrattiEstratto = json_decode(trim($resp,'()'));
	//preparazione array colonne
	$colonneEstratto['total'] = $count;
	$colonneEstratto['results'] = $arr;
	creaEXLDatiCessione($pratica, $colonneEstratto, $ContrattiEstratto, 'Estratto conto');
					
	//creazione foglio exel della storia recupero nella cartella cessioni dell'utente 
	$sql = "SELECT DataEvento AS Data, UserId AS Utente,".
		   " DescrEvento, NotaEvento as Nota ".
	       " FROM v_storiarecupero where IdContratto=$idContratto ORDER BY DataEvento DESC";
	//preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);
	$count = getScalar("SELECT count(*) FROM v_storiarecupero where IdContratto=$idContratto ORDER BY DataEvento DESC");
	$resp='({"total":"' . $count . '","results":' . $data . '})';
	$datiStorico = json_decode(trim($resp,'()'));
	//preparazione array colonne
	$colonneStorico['total'] = $count;
	$colonneStorico['results'] = $arr;
	creaEXLDatiCessione($pratica, $colonneStorico, $datiStorico, 'Storico recupero');
					
	//creazione foglio exel degli altri soggetti nella cartella cessioni dell'utente 
	$sql = "SELECT Controparte, TitoloTipoRecapito,".
		   " CONCAT(CASE WHEN Nome IS NOT NULL THEN Nome ELSE '' END,' ',".
		   " CASE WHEN Indirizzo IS NOT NULL THEN Indirizzo ELSE '' END) AS Indirizzo,".
           " CONCAT(CASE WHEN Telefono IS NOT NULL THEN CONCAT('tel: ',Telefono,' ') ELSE '' END,".
           " CASE WHEN Cellulare IS NOT NULL THEN CONCAT('cell: ',Cellulare,' ') ELSE '' END,".
           " CASE WHEN Fax IS NOT NULL THEN CONCAT('fax: ',Fax,' ') ELSE '' END,".
           " CASE WHEN Email IS NOT NULL THEN CONCAT('e-mail: ',Email,' ') ELSE '' END) AS Recapiti".
           " FROM v_altri_soggetti where IdContratto=$idContratto ORDER BY IdCliente, IdTipoRecapito ASC";
    //preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);
	$count = getScalar("SELECT count(*) FROM v_altri_soggetti where IdContratto=$idContratto ORDER BY IdCliente, IdTipoRecapito ASC");
	if($count>0) {
      $resp='({"total":"' . $count . '","results":' . $data . '})';
	  $datiAltrSog = json_decode(trim($resp,'()'));
	  //preparazione array colonne
	  $colonneAltrSog['total'] = $count;
	  $colonneAltrSog['results'] = $arr;
	  creaEXLDatiCessione($pratica, $colonneAltrSog, $datiAltrSog, 'Altri soggetti');	
	}
					
	//creazione foglio exel recapito nella cartella cessioni dell'utente 
	$sql = "SELECT TitoloTipoRecapito,".
		   " CONCAT(CASE WHEN Nome IS NOT NULL THEN Nome ELSE '' END,' ',".
		   " CASE WHEN Indirizzo IS NOT NULL THEN Indirizzo ELSE '' END,' ',".
		   " CASE WHEN CAP IS NOT NULL THEN CAP ELSE '' END,' ',".
		   " CASE WHEN Localita IS NOT NULL THEN Localita ELSE '' END,' ',".
		   " CASE WHEN SiglaProvincia IS NOT NULL THEN CONCAT('(',SiglaProvincia,')') ELSE '' END) AS Indirizzo,".
		   " CONCAT(CASE WHEN Telefono IS NOT NULL THEN CONCAT('tel: ',Telefono,' ') ELSE '' END,".
		   " CASE WHEN Cellulare IS NOT NULL THEN CONCAT('cell: ',Cellulare,' ') ELSE '' END,".
		   " CASE WHEN Fax IS NOT NULL THEN CONCAT('fax: ',Fax,' ') ELSE '' END,".
		   " CASE WHEN Email IS NOT NULL THEN CONCAT('e-mail: ',Email,' ') ELSE '' END) AS Recapiti".
		   " FROM v_recapito WHERE FlagAnnullato='N' AND IdCliente=".$pratica["IdCliente"];
    //preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);
	$count = getScalar("SELECT count(*) FROM v_recapito WHERE FlagAnnullato='N' AND IdCliente=".$pratica["IdCliente"]);
	if($count>0) {
	  $resp='({"total":"' . $count . '","results":' . $data . '})';
	  $datiRec = json_decode(trim($resp,'()'));
	  //preparazione array colonne
	  $colonneRec['total'] = $count;
	  $colonneRec['results'] = $arr;
	  creaEXLDatiCessione($pratica, $colonneRec, $datiRec, 'Recapiti cliente');	
	} 
					
	//creazione foglio exel degli altri contratti nella cartella cessioni dell'utente 
	$sql = "SELECT Ruolo,numPratica,Prodotto,Stato,StatoRecupero,Agenzia, ".
		   " If(ImpFinanziato!='null',replace(replace(replace(format(ImpFinanziato,2),'.',';'),',','.'),';',','),NULL) AS Finanziato,".
		   " If(Importo!='null',replace(replace(replace(format(Importo,2),'.',';'),',','.'),';',','),NULL) AS Impagato".  
		   " FROM v_pratiche_collegate WHERE IdCliente=".$pratica["IdCliente"].
           " AND IdContratto!=$idContratto ORDER BY numPratica";
    //preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode($arr);
	$count = getScalar("SELECT count(*) FROM v_pratiche_collegate WHERE IdCliente=".$pratica["IdCliente"]." AND IdContratto!=$idContratto ORDER BY numPratica");
	if($count>0) {
	  $resp='({"total":"' . $count . '","results":' . $data . '})';
	  $datiAltrCon = json_decode(trim($resp,'()'));
	  //preparazione array colonne
	  $colonneAltrCon['total'] = $count;
	  $colonneAltrCon['results'] = $arr;
	  creaEXLDatiCessione($pratica, $colonneAltrCon, $datiAltrCon, 'Altri contratti');	
	} 
					
	//creazione foglio exel delle note nella cartella cessioni dell'utente 
	$sql = "SELECT DataCreazione, DataScadenza, TestoNota". 
		   " FROM nota".
		   " where tipoNota in ('N','C') AND IdContratto=$idContratto ORDER BY DataCreazione DESC";
    //preparazione dati con conversione in standard object
	$arr = getFetchArray($sql);
	$data = json_encode_plus($arr);
	$count = getScalar("SELECT count(*) FROM nota where tipoNota in ('N','C') AND IdContratto=$idContratto");
	if($count>0) {
	  $resp='({"total":"' . $count . '","results":' . $data . '})';
	  $datiNota = json_decode(trim($resp,'()'));
	  //preparazione array colonne
	  $colonneNota['total'] = $count;
	  $colonneNota['results'] = $arr;
	  creaEXLDatiCessione($pratica, $colonneNota, $datiNota, 'Annotazioni');	
	}
				  
	//creazione file doc dati generali
	creaDOCDatiCessione($pratica,'Dati Contratto.xml','Dati Contratto');
}

/**
 * creaZIPDatiCessione
 * Crea uno zip con tutti i file di una pratica, creati da formAzioneDocumentazioneCES.php
 */
function creaZIPDatiCessione($IdContratto,&$messaggio,&$link){
	
	try {
		$pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$IdContratto");
		
		$localDirZip = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto'];
		$localDirDati = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/Dati";
		$localDirAllegati = ATT_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/Allegati";
								
		$zipfilename = "DettagliPratica".$pratica['CodContratto'].".zip"; // nome del file zip
		$archive_folder_Dati = $localDirDati; // cartella da archiviare
		$archive_folder_Allegati = $localDirAllegati; // cartella da archiviare
		//trace("archive_folder_Allegati- ".$archive_folder_Allegati,fals);
				
		$zip = new ZipArchive; 
	    if ($zip -> open("$localDirZip/$zipfilename", ZIPARCHIVE::CREATE)!==TRUE) {
			Throw new Exception("Impossibile creare il file $localDirZip/$zipfilename");
		}
		 
		if (!file_exists($localDirDati)) { // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDirDati,0777,true)) { // true --> crea le directory ricorsivamente
				Throw new Exception("Impossibile creare la cartella dei documenti");
			}
		}
		
		$dirDati = preg_replace('/[\/]{2,}/', '/', $archive_folder_Dati."/");
			    
		$dirs = array($dirDati); 
		while (count($dirs)) 
		{ 
		   $dir = current($dirs); 
		   		        
		   $dh = opendir($dir);
		   $cartelle = explode('/',$dir);
		   $subdir=$cartelle[count($cartelle)-2]."/"; //sottocartella
		   while($file = readdir($dh)) 
		   { 
		      if ($file != '.' && $file != '..') 
		      { 
		         if (is_file($dir.$file)){
		           $zip -> addFile($dir.$file, $subdir.$file);
		         }     
		         elseif (is_dir($dir.$file)){ 
		          $dirs[] = $dir.$file."/";
		         }     
		      } 
		   } 
		   closedir($dh); 
		   array_shift($dirs); 
		}
		
		if (!file_exists($localDirAllegati)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDirAllegati,0777,true)) // true --> crea le directory ricorsivamente
				Throw new Exception("Impossibile creare la cartella dei documenti");
		
		$dirAllegati = preg_replace('/[\/]{2,}/', '/', $archive_folder_Allegati."/");
		
			    
		$dirs = array($dirAllegati); 
		//trace("dirs- ".$dirs,false);
		while (count($dirs)) 
		{ 
		   $dir = current($dirs); 
		   		        
		   $dh = opendir($dir);
		   $cartelle = explode('/',$dir);
		   $subdir=$cartelle[count($cartelle)-2]."/"; //sottocartella
		   while($file = readdir($dh)) 
		   { 
		      if ($file != '.' && $file != '..') 
		      { 
		         if (is_file($dir.$file)){
		           $zip -> addFile($dir.$file, $subdir.$file);
		         }     
		         elseif (is_dir($dir.$file)){ 
		          	$dirs[] = $dir.$file."/";
		         }     
		      } 
		   } 
		   closedir($dh); 
		   array_shift($dirs); 
		}
		    
		$zip -> close(); 
		// Ritorna, link, esito e filpath
		$link = REL_PATH."/".$pratica['IdCompagnia']."/cessioni/".$pratica['CodContratto']."/".$zipfilename;
		$messaggio = "Prodotto ZIP file con tutta la documentazione della pratica ". $pratica['CodContratto']." al link: <a href='$link' target='_blank'>$link</a>";
		return "$localDirZip/$zipfilename";
	}	
	catch (Exception $e)
	{
		$message = $e->getMessage();
		return null;
	}
}

/**
 * creaZIPDatiCessioneMultipla
 * Crea uno zip contenente tutti gli zip files relativi a piu' contratti ceduti, prodotti dalla funzione
 * precedente (questa la chiama formAzioneDocumentazioneCES nel caso di azione applicata a piu' contratti
 */
function creaZIPDatiCessioneMultipla($filepaths,&$messaggio,&$link){

	try {
		$localDirZip = ATT_PATH;
		$zipfilename = "cessioni_".date('Y-m-d-H-i-s').".zip";
		$zip = new ZipArchive;
		if ($zip -> open("$localDirZip/$zipfilename", ZIPARCHIVE::CREATE)!==TRUE) {
			Throw new Exception("Impossibile creare il file $localDirZip/$zipfilename");
		}

		foreach ($filepaths as $file) {
			trace("Aggiunge file $file al file zip $localDirZip/$zipfilename",false);
			$zip->addFile($file,basename($file));
		}
		$zip->close();

		// Ritorna, link, esito e filpath
		$link = REL_PATH."/".$zipfilename;
		$messaggio = "Prodotto ZIP file con tutta la documentazione di ".count($filepaths)." pratiche al link: <a href='$link'>$link</a>";
		return true;
	}
	catch (Exception $e)
	{
		$message = $e->getMessage();
		return false;
	}
}

//----------------------------------------------------------------------------------------------
// updatePercSvalutazione
// Aggiorna la percentuale di svalutazione nel saldo e stralcio 
// Argomenti: 1) percentuale di abbuono
//            2) id contratto
//----------------------------------------------------------------------------------------------
function updatePercSvalutazione($percAbbuono,$idContratto){
	
	$percSval=str_replace(',','.',$percAbbuono);
	$aggiornamento=TRUE;
	switch($percSval)
	{
		case ($percSval<=20):
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,80);
		    break;
		case ($percSval>20 && $percSval<=30): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,70);
			break;
		case ($percSval>30 && $percSval<=40): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,60);
			break;
		case ($percSval>40 && $percSval<=50): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,50);
			break;
		case ($percSval>50 && $percSval<=60): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,40);
			break;
		case ($percSval>60 && $percSval<=70): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,30);
			break;
		case ($percSval>70 && $percSval<=80): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,20);
			break;
		case ($percSval>80): 
			$aggiornamento = applicaPercSvalutazione(0,$idContratto,10);
			break;
	}
	return $aggiornamento;
}
//--------------------------------------------------------------------
// applicaPercSvalutazione
// Se � non nulla, applica al contratto la percentuale di svalutazione
// data
//--------------------------------------------------------------------
function applicaPercSvalutazione($IdAzione,$IdContratto,$perc,$nota="")
{
	if ($perc!=null) 
	{
		$sqlperc = "UPDATE contratto SET PercSvalutazione=$perc/100 WHERE IdContratto=$IdContratto";
		if (!execute($sqlperc))
			return FALSE;
		else
			writeHistory($IdAzione>0?$IdAzione:"NULL","Aggiornamento percentuale di svalutazione = $perc%",$IdContratto,$nota);
	}
	return TRUE;
}
//--------------------------------------------------------------------
// eseguiCreazioneFileCerved  13-Feb-2013
// Vengono creati i file in funzione del tipo cliente ricevuto  
//--------------------------------------------------------------------

function eseguiCreazioneFileCerved($IdProvvigione,&$errorMsg,&$fileURL,$tipoCliente){
	$myFileURL=$fileURL;
	switch ($tipoCliente){
		case 1:
			$retVal[0]=creaFileCerved($IdProvvigione,$errorMsg,$myFileURL); // solo persone giuridiche
			$fileURL[0]=$myFileURL;
			break;
		case 2:
			$retVal[0]=creaFileCervedFisiche($IdProvvigione,$errorMsg,$myFileURL); // solo persone fisiche
			$fileURL[0]=$myFileURL;
			break;
		case 3:
			$retVal[0]=creaFileCerved($IdProvvigione,$errorMsg,$myFileURL); // solo persone giuridiche
			$fileURL[0]=$myFileURL;
			if ($retVal[0]=="") return "";
			$retVal[1]=creaFileCervedFisiche($IdProvvigione,$errorMsg,$myFileURL); // solo persone fisiche
			$fileURL[1]=$myFileURL;
			break;
	}
	return $retVal;
}

//--------------------------------------------------------------------
// eseguiCreazioneFileAci  25-Gen-2018
// Vengono creati i file di input ACI  
//--------------------------------------------------------------------
function eseguiCreazioneFileAci($IdProvvigione,&$errorMsg,&$fileURL){
	$myFileURL=$fileURL;
	$retVal[0]=creaFileAci($IdProvvigione,$errorMsg,$myFileURL); // solo persone giuridiche
	$fileURL[0]=$myFileURL;
	return $retVal;
}

//--------------------------------------------------------------------
// creaFileCerved
// Crea un file da inviare a Cerved per un dato IdProvvigione
//--------------------------------------------------------------------
function creaFileCerved($IdProvvigione,&$errorMsg,&$fileURL)
{
	// Individua le pratiche da includere nel file
	$lotto = getScalar("SELECT CONCAT(CONVERT(CodRegolaProvvigione USING UTF8),'-',DATE_FORMAT(p.DataFin,'%Y%m%d'))
	                    FROM provvigione p JOIN regolaprovvigione rp ON p.IdRegolaProvvigione=rp.IdRegolaProvvigione
	                    WHERE IdProvvigione=$IdProvvigione");
	
	// per ora solo le persone giuridiche (vedi ultima condizione)
	$sql = "SELECT v.IdContratto,v.CodContratto,CodiceFiscale,PartitaIVA,v.IdCliente,IdTipoCliente
				,RagioneSociale,IFNULL(RagioneSociale,Nominativo) AS NomeCliente,snc.Nome,snc.Cognome
				,LocalitaNascita,DataNascita,SiglaProvincia,v.CapitaleResiduo
				FROM v_dettaglio_provvigioni v
				JOIN cliente cl ON v.IdCliente=cl.IdCliente
				LEFT JOIN v_separa_nome_cognome snc ON snc.IdCliente=cl.IdCliente
				WHERE ImpPagatoTotale=0 AND IdProvvigione=$IdProvvigione AND IdTipoCliente=1";
	$rows = getFetchArray($sql);
	if (!is_array($rows))
	{
		$errorMsg = getLastError();
		return ""; // nessun file generato
	}	
//	trace($sql,false);
	$fileName = "File_Cerved_G_".$lotto.".txt"; 
	trace("Preparazione file estratto per Cerved $fileName (".count($rows)." righe)",FALSE);
	if (count($rows)==0)
		return "0";
	// 16/1/2013: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
	// del processo corrente (siccome la creazione pu� essere lanciata da web o da batch)
	$processUser = posix_getpwuid(posix_geteuid());
	$localDir = TMP_PATH."/".$processUser['name'];
	$fileURL = TMP_REL_PATH."/".$processUser['name']."/$fileName";
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
		mkdir($localDir,0777,true); // true --> crea le directory ricorsivamente
		
	$filePath = "$localDir/$fileName"; 
	$tipoWrite = 0;
	
	// Looop principale (un record per ogni pratica inclusa)
	foreach ($rows as $row)
	{
		$pratica = $row["CodContratto"];
		$cliente = $row["NomeCliente"];
		$codfisc = $row["CodiceFiscale"];
		$partiva = $row["PartitaIVA"];
		$fisgiu  = $row["IdTipoCliente"]==1?'1':'0';
		$nome    = $row["RagioneSociale"]==""?$row["Nome"]:$row["RagioneSociale"];
		$cognome = $row["Cognome"];
		$comune  = $row["LocalitaNascita"];
		$provincia = $row["SiglaProvincia"];
		$datanasc = date('dmY',dateFromString($row["DataNascita"])); // formato richiesto GGMMAAAA
		$capitale = $row["CapitaleResiduo"];
		/*
		$rec = "Y;N;N;$lotto;$pratica;$cliente;$codfisc;$partiva;;;GP;$fisgiu;$nome;$cognome;$codfisc;$comune;$provincia;$datanasc;";
		// 9/12/2015: aggiunto il campo capitale residuo
		*/	
		$rec = "Y;N;N;$lotto;$pratica;$cliente;$codfisc;$partiva;;;$capitale;";
		if (!file_put_contents($filePath,$rec,$tipoWrite))
		{
			$errorMsg = "Fallita scrittura file $filePath";
			trace($errorMsg,FALSE);
			return "";
		}
		$tipoWrite = FILE_APPEND;
		// Legge i garanti e li appende al record principale
		$garanti = getFetchArray("SELECT * FROM v_lista_garanti_per_cerved WHERE IdContratto=".$row["IdContratto"]);
		if (!is_array($garanti))
		{
			$errorMsg = getLastError();
			if ($errorMsg>"")
				return ""; // nessun file utilizzabile
		}	
		// Looop secondario (garanti)
		foreach ($garanti as $row)
		{
			$codfisc = $row["CodiceFiscale"];
			$fisgiu  = $row["IdTipoCliente"]==1?'1':'0';
			$nome    = $row["RagioneSociale"]==""?$row["Nome"]:$row["RagioneSociale"];
			$cognome = $row["Cognome"];
			$comune  = $row["LocalitaNascita"];
			$provincia = $row["SiglaProvincia"];
			$datanasc = date('dmY',dateFromString($row["DataNascita"])); // formato richiesto GGMMAAAA
			//  13 Feb 2013 su richiesta di De Santis il comune e la provincia sono omessi. Armando
			//			$rec = "GP;$fisgiu;$nome;$cognome;$codfisc;$comune;$provincia;$datanasc;";
			$rec = "GP;$fisgiu;$nome;$cognome;$codfisc;;;$datanasc;";
			if (!file_put_contents($filePath,$rec,$tipoWrite))
			{
				$errorMsg = "Fallita scrittura file $filePath";
				trace($errorMsg,FALSE);
				return "";
			}	
		}
		
		if (!file_put_contents($filePath,"\r\n",$tipoWrite)) // salto riga 
		{
			$errorMsg = "Fallita scrittura file $filePath";
			trace($errorMsg,FALSE);
			return "";
		}	
	}	
	return $filePath;
}
//--------------------------------------------------------------------
// creaFileCervedFisiche
// Crea un file da inviare a Cerved per un dato IdProvvigione
//--------------------------------------------------------------------
function creaFileCervedFisiche($IdProvvigione,&$errorMsg,&$fileURL)
{
	// Individua le pratiche da includere nel file
	$lotto = getScalar("SELECT CONCAT(CONVERT(CodRegolaProvvigione USING UTF8),'-',DATE_FORMAT(p.DataFin,'%Y%m%d'))
	                    FROM provvigione p JOIN regolaprovvigione rp ON p.IdRegolaProvvigione=rp.IdRegolaProvvigione
	                    WHERE IdProvvigione=$IdProvvigione");
	
	$sql="SELECT concat(CodContratto,'a') as keyOrd,v.IdContratto,CodContratto,CodiceFiscale,PartitaIVA,v.IdCliente,IdTipoCliente
				,snc.Nome,snc.Cognome,LocalitaNascita,DataNascita,vip.Indirizzo,vip.CAP,vip.Localita,v.CapitaleResiduo
				FROM v_dettaglio_provvigioni v
				JOIN cliente cl ON v.IdCliente=cl.IdCliente
				LEFT JOIN v_separa_nome_cognome snc ON snc.IdCliente=cl.IdCliente
				left join v_indirizzo_principale vip on vip.IdCliente=cl.IdCliente        
				WHERE v.ImpPagatoTotale=0 AND IdProvvigione=$IdProvvigione AND IdTipoCliente=2
				union 
				SELECT concat(CodContratto,'b') as keyOrd, v.IdContratto,CodContratto,vlc.CodiceFiscale,'' as PartitaIVA,vlc.IdCliente,2 asIdTipoCliente
       			,vlc.Nome,vlc.Cognome,vlc.LocalitaNascita,vlc.DataNascita,vip.Indirizzo,vip.CAP,vip.Localita,v.CapitaleResiduo
				FROM v_dettaglio_provvigioni v
				JOIN cliente cl ON v.IdCliente=cl.IdCliente
        		inner join v_lista_garanti_per_cerved vlc on vlc.IdContratto=v.IdContratto
        		left join v_indirizzo_principale vip on vip.IdCliente=vlc.IdCliente
                WHERE ImpPagatoTotale=0 AND IdProvvigione=$IdProvvigione AND cl.IdTipoCliente=2
				order by keyOrd";

	$rows = getFetchArray($sql);
	if (!is_array($rows))
	{
		$errorMsg = getLastError();
		return ""; // nessun file generato
	}	
//	trace($sql,false);
	$fileName = "File_Cerved_F_".$lotto.".txt"; 
	trace("Preparazione file estratto per Cerved $fileName (".count($rows)." righe)",FALSE);
	if (count($rows)==0)
		return "0";
		
	// 16/1/2013: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
	// del processo corrente (siccome la creazione pu� essere lanciata da web o da batch)
	$processUser = posix_getpwuid(posix_geteuid());
	$localDir = TMP_PATH."/".$processUser['name'];
	$fileURL = TMP_REL_PATH."/".$processUser['name']."/$fileName";
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
		mkdir($localDir,0777,true); // true --> crea le directory ricorsivamente
		
	$filePath = "$localDir/$fileName"; 
	$tipoWrite = 0;
	
	// Looop principale (un record per ogni pratica inclusa)
	foreach ($rows as $row)
	{
		$pratica   = $row["CodContratto"];
		$codfisc   = $row["CodiceFiscale"];
		$nome      = $row["Nome"];
		$cognome   = $row["Cognome"];
		$indirizzo = $row["Indirizzo"];
		$cap 	   = $row["CAP"];
		$localita  = $row["Localita"];
		$capitale   = $row["CapitaleResiduo"];
		$datanasc = date('dmY',dateFromString($row["DataNascita"])); // formato richiesto GGMMAAAA
		$rec = "$pratica;$nome;$cognome;$codfisc;$datanasc;$indirizzo;$cap;$localita;$capitale\r\n";
		if (!file_put_contents($filePath,$rec,$tipoWrite))
		{
			$errorMsg = "Fallita scrittura file $filePath";
			trace($errorMsg,FALSE);
			return "";
		}
		$tipoWrite = FILE_APPEND;
	}	
	return $filePath;
}

//--------------------------------------------------------------------
// creaFileAci
// Crea un file col tracciato d’input ad ACI per un dato IdProvvigione
//--------------------------------------------------------------------
function creaFileAci($IdProvvigione,&$errorMsg,&$fileURL)
{
	// Individua le pratiche da includere nel file
	$lotto = getScalar("SELECT CONCAT(CONVERT(CodRegolaProvvigione USING UTF8),'-',DATE_FORMAT(p.DataFin,'%Y%m%d'))
	                    FROM provvigione p JOIN regolaprovvigione rp ON p.IdRegolaProvvigione=rp.IdRegolaProvvigione
	                    WHERE IdProvvigione=$IdProvvigione");
	
	$sql ="SELECT c.CodBene 
	       FROM v_dettaglio_provvigioni v  
	       LEFT JOIN contratto c ON c.IdContratto=v.IdContratto 
	       WHERE IdProvvigione=$IdProvvigione";
	

	$rows = getFetchArray($sql);
	if (!is_array($rows))
	{
		$errorMsg = getLastError();
		return ""; // nessun file generato
	}	
//	trace($sql,false);
	$fileName = "File_ACI_".$lotto.".txt"; 
	trace("Preparazione file estratto per ACI $fileName (".count($rows)." righe)",FALSE);
	if (count($rows)==0)
		return "0";
		
	// 16/1/2013: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
	// del processo corrente (siccome la creazione pu� essere lanciata da web o da batch)
	$processUser = posix_getpwuid(posix_geteuid());
	$localDir = TMP_PATH."/".$processUser['name'];
	$fileURL = TMP_REL_PATH."/".$processUser['name']."/$fileName";
	
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
		mkdir($localDir,0777,true); // true --> crea le directory ricorsivamente
		
	$filePath = "$localDir/$fileName"; 
	$tipoWrite = 0;
		
	// Looop principale (un record per ogni pratica inclusa)
	foreach ($rows as $row)
	{
		if ($row["CodBene"]!=="" && $row["CodBene"]!==null) {
		  $dataconsultazione = date('Ymd'); // formato richiesto AAAAMMGG	
		  $tipoConsultazione = "T";
          $serieTarga        = 1;
          $targa             = $row["CodBene"];
        
          $rec = "$dataconsultazione$tipoConsultazione$serieTarga$targa\r\n";
		  if (!file_put_contents($filePath,$rec,$tipoWrite))
		  {
			 $errorMsg = "Fallita scrittura file $filePath";
			 trace($errorMsg,FALSE);
			 return "";
		  }
		  $tipoWrite = FILE_APPEND;	
		}	
		
	}
	return $filePath;
}

//--------------------------------------------------------------------
// savePianoRateazione
// Salva il piano di rateazione e le relative rate
//--------------------------------------------------------------------
function savePianoRateazione($arrayPianoRateazione)
{
	if(is_array($arrayPianoRateazione))
	{
	
	 if($arrayPianoRateazione["IdPianoRientro"]<=0)
	 {
	    beginTrans();
	 	$IdPianoRientro = insertPianoRatezione($arrayPianoRateazione);
	 	if($IdPianoRientro!=false)
	 	{
	 		$arrayPianoRateazione["IdPianoRientro"] = $IdPianoRientro;
	 	 	if(insertRate($arrayPianoRateazione)!=false)
		 	{
		 		commit();
		 		return true;
		 	}
		 	else
		 	{
		 		rollback();
		 		return false;
		 	}
	 	}
	 	else
	 	{
	 		rollback();
	 		return false;
	 	}
	 }	
	 else
	 {
	 	beginTrans();
	 	$ret = updatePianoRatezione($arrayPianoRateazione);
	 	if($ret!=false)
	 	{
	 		$ret = insertRate($arrayPianoRateazione);
	 		if($ret!=false)
		 	{
		 		commit();
		 		return true;
		 	}
		 	else
		 	{
		 		rollback();
		 		return false;
		 	}
	 	}
	 	else
	 	{
	 		rollback();
	 		return false;
	 	}
	 }
	}
	else
	{
		setLastError("Dati mancanti per l\'inserimento del piano di rateazione.");
		return false;
	}	
}


//----------------------------------------------------------------------------------------------
// updatePianoRatezione
// AGGIORNA I DATI DEL PIANO DI RATEAZIONE
//----------------------------------------------------------------------------------------------
function updatePianoRatezione($parameters) 
{
	try
	{
		$setClause = "";
		addSetClause($setClause, "Periodicita", 		  $parameters['Periodicita'], 			"N", "N");
		addSetClause($setClause, "DurataAnni", 		   	  $parameters['DurataAnni'], 			"N", "N");
		addSetClause($setClause, "Spese", 		   	  	  $parameters['Spese'], 				"N", "N");
		addSetClause($setClause, "Tasso", 		   	  	  $parameters['Tasso'], 				"N", "N");
		addSetClause($setClause, "IdContratto", 		  $parameters['IdContratto'], 			"N", "N");
		addSetClause($setClause, "IdContratto", 		  $parameters['IdContratto'], 			"N", "N");
		addSetClause($setClause, "DecorrenzaRate", 	   	  $parameters['DecorrenzaRate'],   		"D", "N");
		addSetClause($setClause, "Nota", 			  	  $parameters['Nota'], 					"S", "N");
		addSetClause($setClause, "NumeroRate", 			  $parameters['NumRate'], 				"N", "N");
		addSetClause($setClause, "ImportoRata", 		  $parameters['ImportoRata'], 			"N", "N");
		addSetClause($setClause, "RataCrescente", 		  $parameters['RataCrescente'], 		"S", "N");
		addSetClause($setClause, "LastUpd", 			  "NOW()", 					 	 		"G", "N");
		addSetClause($setClause, "LastUser", 			  getUserName(), 		     			"S", "N");


		$sql = "UPDATE pianorientro $setClause WHERE IdPianoRientro=" . $parameters['IdPianoRientro'];
		//trace($sql);	
		$conn = getDbConnection();
		
		if (execute($sql)) {
			return $parameters['IdPianoRientro'];
		} else {
			setLastError("Errore durante l\'aggiornamento del piano di rateazione.");
			trace("Errore durante l\'aggiornamento del piano di rateazione.".mysqli_error($conn));
			return false;
		}
	}
	catch (Exception $e)
	{
		trace("Errore durante l\'aggiornamento del piano di rateazione: ".$e->getMessage());
		setLastError("Errore durante l\'aggiornamento del piano di rateazione.");
		return false;
	}
}


//----------------------------------------------------------------------------------------------
// insertPianoRatezione
// AGGIORNA I DATI  DEL PIANO DI RATEAZIONE
//----------------------------------------------------------------------------------------------
function insertPianoRatezione($parameters)
{
	try
	{
		$valList = "";
		$colList = "";

		addInsClause($colList, $valList,  "Periodicita", 		  $parameters['Periodicita'], 			"N", "N");
		addInsClause($colList, $valList,  "DurataAnni", 		  $parameters['DurataAnni'], 			"N", "N");
		addInsClause($colList, $valList,  "Spese", 		   	  	  $parameters['Spese'], 				"N", "N");
		addInsClause($colList, $valList,  "Tasso", 		   	  	  $parameters['Tasso'], 				"N", "N");
		addInsClause($colList, $valList,  "IdContratto", 		  $parameters['IdContratto'], 			"N", "N");
		addInsClause($colList, $valList,  "DecorrenzaRate", 	  $parameters['DecorrenzaRate'],   		"D", "N");
		addInsClause($colList, $valList,  "Nota", 			  	  $parameters['Nota'], 					"S", "N");
		addInsClause($colList, $valList,  "NumeroRate", 		  $parameters['NumRate'], 				"N", "N");
		addInsClause($colList, $valList,  "ImportoRata", 		  $parameters['ImportoRata'], 			"N", "N");
		addInsClause($colList, $valList,  "RataCrescente", 		  $parameters['RataCrescente'], 		"S", "N");
		addInsClause($colList, $valList,  "IdStatoPiano", 		  $parameters['IdStatoPiano'], 			"N", "N");
		addInsClause($colList, $valList,  "DataAccordo", 		  "NOW()", 					 	 		"G", "N");
		addInsClause($colList, $valList,  "LastUpd", 			  "NOW()", 					 	 		"G", "N");
		addInsClause($colList, $valList,  "LastUser", 			  getUserName(), 		     			"S", "N");
		
		$sql = "INSERT INTO pianorientro ($colList)  VALUES($valList)";
		//trace($sql);
		$conn = getDbConnection();
		if (execute($sql)) 
			return getInsertId();
		else {
			trace("Errore durante l\'inserimento del piano di rateazione.".mysqli_error($conn));
			setLastError("Errore durante l\'inserimento del piano di rateazione.");
			return false;
		}
	}
	catch (Exception $e)
	{
		trace("Errore durante l\'inserimento del piano di rateazione : ".$e->getMessage());
		setLastError("Errore durante l\'inserimento del piano di rateazione.");
		return false;
	}
}

//----------------------------------------------------------------------------------------------
// insertRate
// Inserisce le rate di un piano di rateazione
//----------------------------------------------------------------------------------------------
function insertRate($parameters)
{
	try
	{
		if($parameters["IdPianoRientro"]>0)
		{
			if(!execute("delete from ratapiano where IdPianoRientro=".$parameters["IdPianoRientro"]))
			{
				trace("Errore durante l\'inserimento del piano di rateazione. Impossibile cancellare le rate gi� impostate.".mysqli_error($conn));
				setLastError("Errore durante l\'inserimento del piano di rateazione. Impossibile cancellare le rate gi&agrave; impostate.");
				return false;
			}
		}
		
		$arrayRate =$arrDatiExtra = json_decode(stripslashes($parameters["Rate"]));;
		foreach($arrayRate as $rata)
		{
			$valList = "";
			$colList = "";
			addInsClause($colList, $valList,  "DataPrevista", 		  	  $rata[2], 						"D", "N");
			addInsClause($colList, $valList,  "IdPianoRientro", 		  $parameters["IdPianoRientro"], 	"N", "N");
			addInsClause($colList, $valList,  "Importo", 		   	  	  $rata[3],	 						"N", "N");
			addInsClause($colList, $valList,  "NumRata", 		   	  	  $rata[0], 						"N", "N");
			addInsClause($colList, $valList,  "LastUpd", 			  	  "NOW()", 					 	 	"G", "N");
			addInsClause($colList, $valList,  "LastUser", 			  	  getUserName(), 		     		"S", "N");
			
			$sql = "INSERT INTO ratapiano ($colList)  VALUES($valList)";
			$conn = getDbConnection();
			if (!execute($sql)) 
			{
				trace("Errore durante l\'inserimento delle rate del piano di rateazione.".mysqli_error($conn));
				setLastError("Errore durante l\'inserimento delle rate del piano di rateazione.");
				return false;
			}
		}
		return true;
	}
	catch (Exception $e)
	{
		trace("Errore durante l\'inserimento delle rate del piano di rateazione : ".$e->getMessage());
		setLastError("Errore durante l\'inserimento delle rate del piano di rateazione.");
		return false;
	}
}

//================================================================================
// UpdateStatoMsgDiff
// MODIFICA LO STATO DEI MESSAGGI DIFFERITI
//================================================================================
function UpdateStatoMsgDiff($IdMessaggio,$esito,$TestoEsito,$TestoMessaggio,$IdAllegato="")
{
		  	
  	//aggiorno lo stato del record del messaggiodifferito come Eseguito
  	$setClause = "";
    addSetClause($setClause,"Stato",$esito,"S");	
    addSetClause($setClause,"DataEmissione","NOW()","G");
    addSetClause($setClause,"TestoEsito",$TestoEsito,"S");
    addSetClause($setClause,"TestoMessaggio",$TestoMessaggio,"S");
    addSetClause($setClause,"IdAllegato",$IdAllegato,"N");
    //trace("UPDATE messaggiodifferito $setClause WHERE IdMessaggioDifferito=".$IdMessaggio);
  		if (!execute("UPDATE messaggiodifferito $setClause WHERE IdMessaggioDifferito=".$IdMessaggio))
		{
				trace("\nErrore durante l'aggiornamento del messaggio differito con Id: $IdMessaggio - ".getlastError());
				return false;
		}
	return true;	
}
/**
 * creaStampa
 * Crea una lettera (durante una produzione massiva di lettere) in formato testo oppure PDF (da HTML)
 * @param {Number} $IdModello ID del modello di lettera
 * @param {Number} $IdContratto ID del contratto
 * @param {Number} $IdMessaggioDifferito ID della riga di tabella messaggiodifferito
 * @return {Boolean} TRUE se tutto ok (la lettera prodotta viene inserita tra gli allegati del contratto)
 */
function creaStampa($IdModello,$IdContratto,$IdMessaggioDifferito)
{
	global $context;
	try
	{
		// ricavo dal Db il Nome del file dell'allegato da utilizzare ed il suo IdTipoAllegato
		$sql = "SELECT M.FileName,M.IdTipoAllegato,M.TitoloModello FROM modello M where IdModello =". $IdModello;
		$tipoAll = getrow($sql);
		//trace(">tip all ".print_r($tipoAll,true));	
		$NomeFileAllegato = $tipoAll["FileName"];
		$idTipoAllegato = $tipoAll["IdTipoAllegato"];
		
		//  se non ha trovato nessun modello con il codice modello ricevuto
		if(!($NomeFileAllegato>"")){
		    //  invio errore per mancanza modello sul db
		    $msg = "Il modello '$IdModello' non  e' presente nella tab modelli.";
			UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione della lettera ",$msg);
			return false;			
		}
	
	    // controllo se esiste il file modello dell'allegato nella cartella templates
	    $fileTemp = TEMPLATE_PATH."/$NomeFileAllegato";
	    //trace(">ftemp $fileTemp");
	    if(!file_exists ($fileTemp)){
	       $msg = "Il file $fileTemp non e' presente nella cartella ".TEMPLATE_PATH;
	       UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
	       trace($msg,false);
	       return false;		
	    }
	    
		
	    // leggo dal db i dati per la costruzione dell'allegato
	    $sql = "SELECT * FROM v_contratto_lettera WHERE IdContratto=$IdContratto";
	    $row = getRow($sql);
	    //trace(">dati sost ".print_r($row,true));
	    if(empty($row))  // se non ricevo dati dalla vista
	    {
	      $msg = "Non sono stati ricevuti dati dalla vista v_contratto_lettera per il contratto $IdContratto."; 
	      UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
	      trace($msg,false);
	      return false;
	    }

	    if (!($row['ImpInsoluto']>=26)) {
	      $msg = "Il contratto ha un insoluto inferiore a 26 euro"; 
	      UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Lettera non prodotta",$msg);
	      trace($msg,false);
	      return -1;  // deve continuare, ma torna -1 per indicare che non si tratta di un IdAllegato
	    }
	    
		//2020-05-19 GENERAZIONE DEL QR CODE VALIDA SOLO PER IL MODELLO Lettera DEO.html e Lettera DEO garante.html (fmazzarani)
		$qrcode = false;
		//TEST REGEXP PER NOMI FILE LETTERA DEO, LETTERA DEO MAXIRATE E PREAVVISO CENTRALE RISCHI
		// E PER I MODELLI RELATIVI AI GARANTI
		//sono le uniche lettere che hanno il QRCode inglobato nel testo
		if(	preg_match("/.*?\s(DEO)\.html/ism",$NomeFileAllegato,$match) || preg_match("/.*?(DEO garante)\.html/ism",$NomeFileAllegato,$match)){
			if($match){
				$imgBase64Code = generaQRCode($row,$errConvertion);
				$qrcode = true;
				if(!$imgBase64Code){
					$qrcode = false;
					$msg = $errConvertion > '' ? $errConvertion : '';
					Throw new Exception("\Genereazione QR Code per il contratto $CodContratto non riuscita a causa del seguente errore: $msg");
					return false;
				}
			}
		}
		
	    $CodContratto = $row["CodContratto"];
	    
	    // sceglie la cartella di destinazione in base allo userid
		$processUser = posix_getpwuid(posix_geteuid());
		$folder = $processUser['name'].'_new/'.substr($CodContratto,0,4); 

		$localDir=ATT_PATH."/".$row["IdCompagnia"]."/$folder/".$CodContratto;
		trace("Scrittura allegato nel folder $localDir",FALSE);
		if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
			if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
				Throw new Exception("\nOperazione non riuscita a causa del seguente errore: Impossibile creare la cartella dei documenti $localDir");				

		// Se l'estensione del modello e' txt, produce una file di testo, se e' HTML produce un file PDF
		// .html oppure .htm
		if (preg_match('/html?$/i',$NomeFileAllegato)){
			$ext = ".pdf";
		}else{
			$ext = ".txt";
		}
		$fileName =	substr($NomeFileAllegato,0,strrpos($NomeFileAllegato,'.'))."_{$CodContratto}_Rata_{$row["Rata"]}$ext";
		$newFile  = $localDir."/".$fileName;	

		// leggo il contenuto del modello di stampa
		$strTxt = file_get_contents($fileTemp);
		
		if($strTxt=="")       // se il contenuto � vuoto o ci sono errori
		{
			$msg = "Errore nella lettura del file $fileTemp";
			UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
			trace($msg,false);
			return false;
		}
		// sostituisco i dati tra % % con i dati ricevuti dalla vista
		preg_match_all('/(%[a-z_0-9\.]+%)/i',$strTxt,$arr);
		for ($i=count($arr[1])-1;$i>=0; $i--) {
			$var  = $arr[1][$i];
			$keySearch = substr($var,1,strlen($var)-2);
			if (strtolower(substr($keySearch,0,8))=='modello.') {
				$keymod = substr($keySearch,8);
				$fileModel = getScalar("SELECT filename FROM modello where TitoloModello = '$keymod'");
				if (!$fileModel)
					Throw new Exception("Non trovata definizione del sottomodello di stampa '$keymod'");
				$newVal='';
			
				$sqlRate = "SELECT * FROM v_rate_insolute WHERE IdContratto=".$IdContratto;
				$resultRate = getFetchArray($sqlRate);
				foreach ($resultRate as $rowRate) {
					//apre il modello e lo sostituisce
					$content = file_get_contents(TEMPLATE_PATH.'/'.$fileModel);
					if ($content=="")
						Throw new Exception("Non trovato il file '$fileModel' per il sottomodello di stampa '$keymod'");
						
					$newVal .= replaceVariables($content,$rowRate);
					$newVal .= TEXT_NEWLINE;
				}
			}else{
				if (array_key_exists($keySearch,$row))
					$newVal= $row[$keySearch];
				else {
					$newVal = '';
					trace("Non trovato valore da sostituire alla variabile $var",FALSE);
				}
			}
			//2020-05-08 gestione della sostituzione della variabile relativa al QRcode nel modello Lettera DEO.txt
			if($var == '%QRCode%' && $qrcode){
				$strTxt = str_replace($var,$imgBase64Code,$strTxt);
			}else{
				$strTxt = str_replace($var,$newVal,$strTxt);
			}			
		}

		// scrivo il nuovo contenuto della lettera nel nuovo allegato
		// Se il file da produrre e' PDF, chiama l'apposita funzione, altrimenti scrive semplicemente il testo fin qui composto
		if ($ext=='.pdf') {
			$result = creaPdfDaHtml($strTxt,$newFile);
		} else {		
			$result = file_put_contents($newFile,$strTxt);
		}
		if(!$result) {
			$msg .= "\nErrore nella scrittura del file $newFile ";	
			UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
			trace($msg,false);
			return false;
		}

		$titolo = substr($NomeFileAllegato,0,strrpos($NomeFileAllegato,"."));
		$url = REL_PATH."/".$row["IdCompagnia"]."/$folder/".$CodContratto."/".$fileName;
		$IdUtente = $context["IdUtente"];
		$idtipo = $idTipoAllegato;
		
		// Controllo se l'allegato e' stato gia' inserito nella tabella allegato
		$IdAllegato = getScalar("SELECT IdAllegato FROM allegato WHERE UrlAllegato='$url' AND IdContratto=$IdContratto");
		if ($IdAllegato>0) // gia' presente: modifica solo la data
		{
			if (!execute("UPDATE allegato SET LastUpd=NOW() WHERE IdAllegato=$IdAllegato"))
			{
				$msg = "Errore nell'update dell' UrlAllegato=$url nella tabella Allegato";
				UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'update dell' UrlAllegato=$url nella tabella Allegato",$msg);
				trace($msg,false);
				return false;
			}
			else
				trace("Aggiornata data allegato $IdAllegato per il contratto ".$row["IdContratto"],FALSE);
		}
		else // necessario inserimento
		{
			// effettuo l'insert su tab allegato
			$colList = "";
			$valList  = "";
			addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
			addInsClause($colList,$valList,"TitoloAllegato", $titolo,"S");
			addInsClause($colList,$valList,"UrlAllegato",$url,"S");
			addInsClause($colList,$valList,"IdUtente",$IdUtente,"N");
			addInsClause($colList,$valList,"LastUser","system","S");
			addInsClause($colList,$valList,"IdTipoAllegato",$idtipo,"N");
			
			$master=$context["master"];
			//trace("master ".$master);
			if($master!=''){
				addInsClause($colList,$valList,"lastSuper",$master ,"S");
			}
			
			if (!execute("INSERT INTO allegato ($colList) VALUES ($valList)"))
			{
				$msg = "Errore durante l'inserimento dell'allegato nella tabella allegato :$sql ";
				UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
				trace($msg,false);
				return false;
			}
			$IdAllegato=getInsertId();  // prendo l'id dell'ultimo allegato inserito sul db
			trace("creato allegato $IdAllegato per il contratto ".$row["IdContratto"],FALSE);
		}
        UpdateStatoMsgDiff($IdMessaggioDifferito,"E","Creata lettera e inserita tra gli allegati al contratto",$tipoAll["TitoloModello"],$IdAllegato);	  
		writeHistory("NULL","Creata lettera '".$tipoAll["TitoloModello"]."'",$IdContratto,"");
		//trace("res idall $IdAllegato");
		return $IdAllegato;
	}
	catch (Exception $e)
	{
	    $msg = "Errore nell'elaborazione dell'allegato '".$tipoAll["TitoloModello"]."' per il contratto: $CodContratto -".$e->getMessage();
		trace($msg);
		UpdateStatoMsgDiff($IdMessaggioDifferito,"N","Errore nell'elaborazione ".$tipoAll["TitoloModello"],$msg);
	    return false;
	}
}

//================================================================================
// calcolaNumRatePagate     
// Calcola il numero di rate effettivamente pagate ( non sbbuonate o stornate )
//================================================================================
function calcolaNumRatePagate($IdContratto)
{
	$sql = "SELECT COUNT(*) FROM (
				SELECT NumRata FROM movimento m
				WHERE IdContratto = $IdContratto AND IdTipoMovimento NOT IN (121,340) AND NumRata!=0
				GROUP BY NumRata
				HAVING SUM(Importo)<26 AND MAX(IF(Importo>0,DataScadenza,null))<=CURDATE()+ INTERVAL 20 DAY
				) X";
	return getScalar($sql);
}

//================================================================================
// saveWriteoff  
// Salva nella tabella writeOff i dati di un form del ciclo writeoff
// Argomenti:
//   	 IdContratto      Id del contratto 
// Restituisce:
//      true :	tutto OK
//      false:  errore 
//================================================================================
function  saveWriteoff($IdContratto)
{
	$userid = getUserName();
	
	$idWriteOff = getScalar("SELECT IdWriteOff FROM writeoff WHERE IdContratto=$IdContratto");
	if ($idWriteOff>0) {
		$setClause = "";
		addSetClause($setClause,"Flag1",$_REQUEST['c1']?'Y':'N',"S");
		addSetClause($setClause,"Flag2",$_REQUEST['c2']?'Y':'N',"S");
		addSetClause($setClause,"Flag3",$_REQUEST['c3']?'Y':'N',"S");
		addSetClause($setClause,"Flag3a",$_REQUEST['c3a']?'Y':'N',"S");
		addSetClause($setClause,"Flag4",$_REQUEST['c4']?'Y':'N',"S");
		addSetClause($setClause,"Flag4a",$_REQUEST['c4a']?'Y':'N',"S");
		addSetClause($setClause,"Flag5",$_REQUEST['c5']?'Y':'N',"S");
		addSetClause($setClause,"Flag5a",$_REQUEST['c5a']?'Y':'N',"S");
		addSetClause($setClause,"Flag5b",$_REQUEST['c5b']?'Y':'N',"S");
		addSetClause($setClause,"Flag5c",$_REQUEST['c5c']?'Y':'N',"S");
		addSetClause($setClause,"Flag6",$_REQUEST['c6']?'Y':'N',"S");
		addSetClause($setClause,"Flag7",$_REQUEST['c7']?'Y':'N',"S");
		addSetClause($setClause,"Nota",$_REQUEST['nota'],"S");
		addSetClause($setClause,"Nota2",$_REQUEST['nota2'],"S");
		addSetClause($setClause,"Nota3a",$_REQUEST['nota3a'],"S");
		addSetClause($setClause,"Nota4a",$_REQUEST['nota4a'],"S");
		addSetClause($setClause,"Nota5a",$_REQUEST['nota5a'],"S");
		addSetClause($setClause,"Nota5b",$_REQUEST['nota5b'],"S");
		addSetClause($setClause,"Nota5c",$_REQUEST['nota5c'],"S");
		addSetClause($setClause,"Nota6",$_REQUEST['nota6'],"S");
		addSetClause($setClause,"Nota7",$_REQUEST['nota7'],"S");
		addSetClause($setClause,"importo3a",$_REQUEST['importo3a'],"N");
		addSetClause($setClause,"importo4a",$_REQUEST['importo4a'],"N");
		addSetClause($setClause,"importo5b",$_REQUEST['importo5b'],"N");
		addSetClause($setClause,"impDBT",$_REQUEST['impDBT'],"N");
		addSetClause($setClause,"impIntMora",$_REQUEST['impIntMora'],"N");
		addSetClause($setClause,"impSpeseLegali",$_REQUEST['impSpeseLegali'],"N");
		addSetClause($setClause,"impRiscatto",$_REQUEST['impRis'],"N");
		addSetClause($setClause,"impPdr",$_REQUEST['impPdr'],"N");
		addSetClause($setClause,"impPap",$_REQUEST['impPap'],"N");
		addSetClause($setClause,"impSval",$_REQUEST['impSval'],"N");
		addSetClause($setClause,"impSvalLE",$_REQUEST['impSvalLE'],"N");
		addSetClause($setClause,"percSval",$_REQUEST['percSval'],"N");
		addSetClause($setClause,"percSvalLE",$_REQUEST['percSvalLE'],"N");
		
		addSetClause($setClause,"LastUser",$userid,"S");
		
		$sql = "UPDATE writeoff $setClause WHERE IdWriteoff=$idWriteOff";
	} else {
		$colList = "";
		$valList  = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"Flag1",$_REQUEST['c1']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag2",$_REQUEST['c2']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag3",$_REQUEST['c3']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag3a",$_REQUEST['c3a']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag4",$_REQUEST['c4']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag4a",$_REQUEST['c4a']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag5",$_REQUEST['c5']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag5a",$_REQUEST['c5a']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag5b",$_REQUEST['c5b']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag5c",$_REQUEST['c5c']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag6",$_REQUEST['c6']?'Y':'N',"S");
		addInsClause($colList,$valList,"Flag7",$_REQUEST['c7']?'Y':'N',"S");
		addInsClause($colList,$valList,"Nota",$_REQUEST['nota'],"S");
		addInsClause($colList,$valList,"Nota2",$_REQUEST['nota2'],"S");
		addInsClause($colList,$valList,"Nota3a",$_REQUEST['nota3a'],"S");
		addInsClause($colList,$valList,"Nota4a",$_REQUEST['nota4a'],"S");
		addInsClause($colList,$valList,"Nota5a",$_REQUEST['nota5a'],"S");
		addInsClause($colList,$valList,"Nota5b",$_REQUEST['nota5b'],"S");
		addInsClause($colList,$valList,"Nota5c",$_REQUEST['nota5c'],"S");
		addInsClause($colList,$valList,"Nota6",$_REQUEST['nota6'],"S");
		addInsClause($colList,$valList,"Nota7",$_REQUEST['nota7'],"S");
		addInsClause($colList,$valList,"importo3a",$_REQUEST['importo3a'],"N");
		addInsClause($colList,$valList,"importo4a",$_REQUEST['importo4a'],"N");
		addInsClause($colList,$valList,"importo5b",$_REQUEST['importo5b'],"N");
		addInsClause($colList,$valList,"impDBT",$_REQUEST['impDBT'],"N");
		addInsClause($colList,$valList,"impIntMora",$_REQUEST['impIntMora'],"N");
		addInsClause($colList,$valList,"impSpeseLegali",$_REQUEST['impSpeseLegali'],"N");
		addInsClause($colList,$valList,"impRiscatto",$_REQUEST['impRis'],"N");
		addInsClause($colList,$valList,"impPdr",$_REQUEST['impPdr'],"N");
		addInsClause($colList,$valList,"impPap",$_REQUEST['impPap'],"N");
		addInsClause($colList,$valList,"impSval",$_REQUEST['impSval'],"N");
		addInsClause($colList,$valList,"impSvalLE",$_REQUEST['impSvalLE'],"N");
		addInsClause($colList,$valList,"percSval",$_REQUEST['percSval'],"N");
		addInsClause($colList,$valList,"percSvalLE",$_REQUEST['percSvalLE'],"N");
		addInsClause($colList,$valList,"LastUser",$userid,"S");
		
		$sql = "INSERT INTO writeoff ($colList) VALUES ($valList)";
	}
	return execute($sql);
}

//-------------------------------------------------------------------------
// deleteAllegato
// Elimina un determinato allegato
// Argomenti: 1) IdAllegato 
//--------------------------------------------------------------------------
function deleteAllegato($IdAllegato)
{
	$row = getRow("SELECT IdContratto,UrlAllegato FROM allegato where idallegato=$IdAllegato");
	if (!$row) {
		$msg = "L'allegato richiesto non esiste piu'";
		trace($msg,false);
		setLastSerror($msg);
		return false;
	}
	$url = $row["UrlAllegato"];
	$idContratto = $row["IdContratto"];
	try
	{
		if (execute("delete from allegatoazionespeciale where idallegato=$IdAllegato")) // se per caso � collegato ad az. speciale
		{
			if(execute("update incasso set idallegato = null where idallegato=$IdAllegato"))
			{
			if (execute("delete from allegato where idallegato=$IdAllegato")) 
			{
				$localDir = str_replace(REL_PATH,ATT_PATH,$url);
				unlink($localDir);
				writeHistory("NULL","Eliminato l'allegato $url",$idContratto,"");
				return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return false;
	}
}
//-----------------------------------------------------------------------------
// chiudeConvalide
// Forza la chiusura delle azioni in attesa di convalida per un dato contratto
// Viene usata quando la pratica va in cessione o writeoff
//-----------------------------------------------------------------------------
function chiudeConvalide($IdContratto) {
	$ret = execute("UPDATE azionespeciale SET stato='C' WHERE IdContratto=$IdContratto AND stato='W'");
	if ($ret && getAffectedRows()>0) {
		writeHistory("NULL","Forzato annullamento delle richieste in attesa di convalida",$IdContratto,"");
	}
}
/**
 * creaPdfDaHtml
 * Crea un file PDF a partire da un testo HTML, utilizzando la libreria tcpdf
 * @param {String} $html testo HTML
 * @param {String} $filePath path del file di output (completo di nome file)
 */
function creaPdfDaHtml($html,$filePath) {
	try {
		//2020-06-08 - MODIFICA PER GESTIONE DEL FOOTER
		//-------------------------------------------------------------
		// Estende la classe TCPDF per poter mettere footer
		//-------------------------------------------------------------
		class MYPDF extends TCPDF {
			public $footerImage;
			
			public function setFooterImage($path){
				$this->footerImage = $path;
			} 
			
			// Page footer
			public function Footer() {
				$this->SetY(-15); // stacca un po' il footer dal bordo inferiore

				// Mette l'immagine del footer aziendale
				// il parametro L serve ad allineare a sx il footer
				// vedi https://stackoverflow.com/questions/44061583/what-are-the-parameter-of-the-image-in-tcpdf
				// oppure https://w3schools.invisionzone.com/topic/55228-help-with-tcpdf-image-alignment/
				$logo = $this->Image($this->footerImage, 35, $this->GetY()-13, 0, 25,'','','',false,300,'L'); // altezza 2.5 cm
				
				// Mette il numero di pagina
				$this->Cell(0, 11, 'Pagina '.$this->getAliasNumPage().' di '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
			}
		}
		$footerImage = "footerLettere2020.png";
		trace("Creazione PDF da HTML su $filePath",false);
		//create a new PDF document
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 9 /* PDF_FONT_SIZE_MAIN */)); // era 10
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 9 /* PDF_FONT_SIZE_DATA */)); // era 10
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		trace("path del footer: ".TEMPLATE_PATH."/$footerImage");
		$pdf->setFooterImage(TEMPLATE_PATH."/$footerImage");
		$pdf->SetMargins(18,50); // millimetri
		
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		// headerLettera.jpg deve essere messa nella cartella tcpdf/examples/images  
		$pdf->setHeaderData("headerLettera.jpg","170" /* larghezza in mm */
				,'','',array(0,0,0),array(255,255,255));
		$pdf->setPrintFooter(true); // evita riga di footer
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		// ---------------------------------------------------------
		
		// set font		
		$pdf->AddFont('PdfaHelveticai', 'I',__DIR__."/tcpdf/fonts/pdfahelveticai.php" );
		//$pdf->SetFont("PdfaHelveticai", 'I', 10);
		$pdf->SetFont("PdfaHelveticai", 'I', 9); // 2019-02-14 diminuito font
        
		//add a page
		$pdf->AddPage();
		//print text
		$pdf->writeHTML($html,true, 0, true,0);
		
		//close and output pdf document
		ob_end_clean();
		$pdf->Output($filePath, 'F');
		trace("Registrato file $filePath",false);
		unset($pdf);
		return true;
	} catch (Exception $e) {
		trace($e->getMessage(),true);
		return false;
	}		
}

//-----------------------------------------------------------------------------
// controlloDataFineAffido
// Data la data di affido controllo se la DataInizioAffido del lotto successivo
// sia stata variata così da modificare la DataFineAffido al giorno precedente
// della DataInizioAffido modificata
//-----------------------------------------------------------------------------
function controlloDataFineAffido(&$dataFineAffido) {
		
	try {
	  $dataStandard = date('Y-m-d', strtotime("+1 day", $dataFineAffido));
	  $dataFineAffidoVariata = getScalar("SELECT DATE_SUB(DataAffidoVariata, INTERVAL 1 DAY) as dataFineAffidoVariata FROM dataaffido WHERE DataAffidoStandard = '$dataStandard'");
	  if ($dataFineAffidoVariata!='') {
	  	$dataFineAffido = dateFromString($dataFineAffidoVariata);
	  }
	  return true;
	} catch (Exception $e) {
		trace($e->getMessage(),true);
		return false;
	}	
}
?>
