<?php
require_once("common.php");
//==============================================================
//  F U N Z I O N I    P R O F I L A Z I O N E   U T E N T E 
//--------------------------------------------------------------
// 			       
//==============================================================

//-------------------------------------------------------------- 
// createContext
// Crea la variabile di sessione userContext per l'utente collegato 
// La variabile di sessione contiene le informazioni relative alle
// tabelle Utente - StatoUtente - Reparto - Compagnia - Profilo - Funzioni
//--------------------------------------------------------------
function createContext($user,$master)
{
	unset($_SESSION['uistate']);		// stato interfaccia utente
	unset($_SESSION['userContext']);
	$user = quote_smart($user);
	$sql = "SELECT * FROM v_dati_utente WHERE Userid=$user";
	$userContext = getRow($sql);
	if ($userContext===NULL)
		return;
	$userContext["master"]=$master;
	
	if($master!=''){
		$interval='';
	}else{
		$interval=" AND CURDATE() BETWEEN pu.DataIni AND pu.DataFin ";
	}
	$sql = "SELECT p.IdProfilo, p.TitoloProfilo"
		." FROM utente u, profiloutente pu, profilo p"
		." WHERE u.Userid=$user" 
		." AND u.idUtente=pu.idUtente"
		." AND pu.idProfilo=p.idProfilo"
		."$interval AND CURDATE() BETWEEN p.DataIni AND p.DataFin ";
		$profileContext = getFetchKeyValue($sql,"IdProfilo","TitoloProfilo");
	$userContext["profiles"] = $profileContext;

	$sql = "SELECT DISTINCT f.IdFunzione, f.CodFunzione"
		." FROM utente u, profiloutente pu, profilo p, profilofunzione pf, funzione f"
		." WHERE u.Userid=$user" 
		." AND u.idUtente=pu.idUtente"
		." AND pu.idProfilo=p.idProfilo"
		." AND CURDATE() BETWEEN p.DataIni AND p.DataFin"
		." AND CURDATE() BETWEEN pf.DataIni AND pf.DataFin"
		."$interval AND p.idProfilo=pf.idProfilo"
		." AND pf.idFunzione=f.idFunzione";
	$functionContext = getFetchKeyValue($sql,"IdFunzione","CodFunzione");
	
	$userContext["functions"] = $functionContext;
	
	/* conserva nel contesto anche i valori dei parametri di sistema */
	$userContext['sysparms'] = getFetchKeyValue("SELECT * FROM parametrosistema","CodParametro","ValoreParametro");
	
	$_SESSION['userContext'] = $userContext;
	
	/* Legge lo stato dell'interfaccia utente */
	$sql = "SELECT StateId as name, IFNULL(Value,'') as value FROM uistate WHERE IdUtente=".$userContext['IdUtente']
		.  " AND StateId NOT LIKE '*%'"; // quelli col nome che comincia con asterisco sono da non leggere
	$_SESSION['uistate'] = json_encode_plus(getFetchArray($sql));
	
	return;
}

//-------------------------------------------------------------- 
// userCanDo
// Verifica nella variabile di sessione 'userContext' se l'utente 
// loggato � abilitato alla funzione richiesta 
// Ritorna un valore booleano 
//--------------------------------------------------------------
function userCanDo($key)
{
	$context = $_SESSION['userContext'];
	$functions = $context["functions"];
	return in_array($key, $functions);
}

//----------------------------------------------------------------
// userCondition
// Crea la condizione da mettere nella WHERE su tabella "nota" 
// (o su vista equivalente) per selezionare le note che l'utente 
// ha diritto di vedere
// Argomento: type=1, serve alla SELECT su nota e v_nota
//            type=2, serve alla select su v_insoluti_scadenza
//--------------------------------------------------------------
function userCondition($type=1)
{
	if (userCanDo("READ_ALL")) // utente tipo supervisore
		return "true"; // condizione where sempre vera
	
	$context = $_SESSION['userContext'];
	$idUtente = "0".$context['IdUtente']; // concatena zero per evitare errore SQL in caso di contesto perduto
	$idReparto = "0".$context['IdReparto'];
	if (userCanDo("READ_REPARTO") or userCanDo("READ_AGENZIA") ) // se utente puo' vedere tutte le note del reparto
	{
		$altri = fetchValuesArray("SELECT IdUtente FROM utente WHERE IdUtente!=".$idUtente." AND IdReparto=$idReparto");
		if (is_array($altri) && count($altri)>0)
			$ids = join(",",$altri);
		else
			$ids = "0";
	}
	else
		$ids = "0";
	
	// clausola per escludere dalle cose dirette a tutti le scadenze emesse da altre agenzie
	if ($context['InternoEsterno']=='E') // utente esterno (di agenzia)
	{  // non pu� vedere le scadenze di altri reparti
		if ($type==1) // Select su nota o v_nota
			$perTutti = " AND (TipoNota!='S' OR IdUtente";
		else // select su v_insoluti_scadenze
			$perTutti = " AND (v.TipoNota!='S' OR  IdUtenteCreatore"; 
		
		if (userCanDo('READ_AGENZIA') or userCanDo('READ_REPARTO')) {
			$perTutti .= " IN (SELECT IdUtente FROM utente WHERE IdReparto=$idReparto))";
		} else { // pu� vedere solo le proprie
			$perTutti .= " = $idUtente)";
		}
	}
	else
		$perTutti = "";
		
		
	// L'utente vede sempre le proprie note e quelle dirette esplicitamente a lui; inoltre vede, se non sono riservate e non gli e' vietato,
	// le note dirette a tutti e al proprio reparto; infine, vede quelle dirette ad altre persone del proprio reparto, se gli e' permesso
	// dall'autorizzazione READ_REPARTO.
	if ($type==1) // Select su nota o v_nota
	{
		$riservate = userCanDo("READ_RISERVATO")?"":" AND IFNULL(FlagRiservato,'N')='N'"; // se utente puo' vedere le note riservate
		return	"((IFNULL(IdUtenteDest,0) IN ($ids) OR IFNULL(IdReparto,0)=$idReparto OR IdUtente IN ($ids)"
			   ." OR IdReparto IS NULL AND IdUtenteDest IS NULL $perTutti) $riservate OR IdUtente=$idUtente OR IdUtenteDest=$idUtente)";
	}
	else // Select su v_insoluti_scadenza
	{
		$riservate = userCanDo("READ_RISERVATO")?"":" AND Riservato='N'"; 
		return	"((IFNULL(IdUtenteDest,0) IN ($ids) OR IFNULL(IdRepartoDest,0)=$idReparto OR IdUtenteCreatore IN ($ids)"
			   ." OR IdRepartoDest IS NULL AND IdUtenteDest IS NULL $perTutti) $riservate OR IdUtenteCreatore=$idUtente OR IdUtenteDest=$idUtente)";
	}
}

//-------------------------------------------------------------- 
// displayPopupWarning
// Costruisce l'istruzione js per far comparire il popup con 
// l'avviso urgente 
//--------------------------------------------------------------
function displayPopupWarning($redisplay=false)
{
	if (userCanDo('READ_AVVISO'))
	{
		$fileAge = getScalar("SELECT FileName FROM modello where TipoModello='P' AND CURDATE() BETWEEN DataIni AND DataFin");
		if($fileAge!="")
		{
			$context = $_SESSION['userContext'];
			$testoFile = file_get_contents(TEMPLATE_PATH."/".$fileAge);
			if (strlen($testoFile)>0 && ($testoFile!=$context['lastPopupWarning'] || $redisplay))
			{
				$context['lastPopupWarning'] = $testoFile; // conserva per evitare di rinviarlo di nuovo
				$_SESSION['userContext'] = $context; // aggiorna la session
				return "Ext.example.msg('Attenzione','".addslashes($testoFile)."','t-t');CONTEXT.redisplayMsg=false;";
			}
		}
	} 
	return "";
}

//-------------------------------------------------------------- 
// creaSubselectNoteAllegati
// Costruisce la subselect per la determinazione delle note
// lette/non-lette e quella per gli allegati
// in praticheCorrenti.php e ricercaCorrenti.php 
//--------------------------------------------------------------
function creaSubselectNoteAllegati(&$note,&$allegati,$isStorico=false)
{
	$context = $_SESSION['userContext'];
	$idUtente = $context["IdUtente"];
	if ($idUtente=="") // evita errori SQL per sessione persa
		$idUtente=0;
	$idReparto = $context["IdReparto"];
	if ($idReparto=="") // evita errori SQL per sessione persa
		$idReparto=0;
	
	if ($isStorico) {
		$suffix = "_storico";
		$schema = "db_cnc_storico";
	} else {
		$suffix = "";
		$schema = "db_cnc";
	}
		
	$allegati = "if (exists(select 1 from v_allegati_per_utente$suffix where IdUtente=$idUtente and IdContratto=v.IdContratto),1,0)";
	if (userCanDo("READ_ALL")) // pu� vedere tutto (supervisore)
	{
		$allegati = "if (exists(select 1 from $schema.allegato where IdContratto=v.IdContratto),1,0)";
		// supervisore: tutte le note (considera lette quelle lette dal reparto 1)
		$note = "(select n.idcontratto,
					CASE WHEN n.IdUtenteDest!=n.IdUtente AND SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1))>0 THEN SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1)) ELSE -count(*) END NumNote
					FROM $schema.nota n LEFT JOIN $schema._opt_note_lette nl ON n.IdNota=nl.IdNota AND nl.IdReparto=1
					WHERE TipoNota in ('N','C')
					GROUP BY IdContratto)";
	}
	else if (userCanDo("READ_REPARTO")) 	// utente autorizzato a vedere tutto il reparto
	{
		$utentiReparto = fetchValuesArray("SELECT IdUtente FROM utente WHERE IdReparto=$idReparto");
		$utentiReparto = join(",",$utentiReparto);
		// note create o destinate al reparto, alle sue persone o a tutti
		$note = "(select n.idcontratto,
				CASE WHEN n.IdUtenteDest!=n.IdUtente AND SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1))>0 THEN SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1)) ELSE -count(*) END NumNote
				FROM $schema.nota n 
				LEFT JOIN $schema._opt_note_lette nl ON n.IdNota=nl.IdNota AND nl.IdReparto=$idReparto
				WHERE TipoNota in ('N','C') AND	(
					IdUtente = $idUtente OR IdUtenteDest = 0$idUtente OR (IdUtenteDest IS NULL AND n.IdReparto IS NULL)
					OR n.IdReparto=$idReparto OR n.IdUtente IN ($utentiReparto) OR IdUtenteDest IN ($utentiReparto)
				)";
		if (!userCanDo("READ_RISERVATO"))	// se non pu�  vedere quelle con flag riservato
			$note .= " AND IFNULL(FlagRiservato,'N')!='Y'";	
		$note .= " GROUP BY IdContratto)";
	}
	else // utente semplice, pu� vedere solo quelle dirette o create da lui e quelle dirette a tutti
	{
		$note = "(select n.idcontratto,
				CASE WHEN n.IdUtenteDest!=n.IdUtente AND SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1))>0 THEN SUM(IF(nl.idNota IS NOT NULL or n.idutente=$idUtente,0,1)) ELSE -count(*) END NumNote
				FROM $schema.nota n 
				LEFT JOIN $schema._opt_note_lette nl ON n.IdNota=nl.IdNota AND nl.IdReparto=$idReparto
				WHERE TipoNota in ('N','C') AND	(
					IdUtente = $idUtente OR IdUtenteDest = 0$idUtente OR (IdUtenteDest IS NULL AND n.IdReparto IS NULL)
				)";
		if (!userCanDo("READ_RISERVATO"))	// se non pu�  vedere quelle con flag riservato
			$note .= " AND IFNULL(FlagRiservato,'N')!='Y'";	
		$note .= " GROUP BY IdContratto)";
	}
}
//------------------------------------------------------------------------------------ 
// condNoteNonLette
// Costruisce la clausola LEFT JOIN e WHERE da attaccare ad una SELECT FROM Nota n
// per determinare le sole note/comunicazioni non lette
//------------------------------------------------------------------------------------ 
function condNoteNonLette()
{
	$context = $_SESSION['userContext'];
	$idUtente = $context["IdUtente"];
	if ($idUtente=="") // evita errori SQL per sessione persa
		$idUtente=0;
	$idReparto = $context["IdReparto"];
	if ($idReparto=="") // evita errori SQL per sessione persa
		$idReparto=0;
	// Cambiato il 14/6 con IPI Finance: tutti vedono come non lette le sole comunicazioni dirette
	/*
	if (userCanDo("READ_ALL")) // pu� vedere tutto (supervisore)
	{
		// supervisore: tutte le note (considera lette quelle lette dal reparto 1)
		$cond = "WHERE TipoNota='C' AND NOT EXISTS (SELECT 1 FROM _opt_note_lette WHERE IdReparto=1 AND IdNota=n.IdNota)";
	}
	else 
	 
	if (userCanDo("READ_REPARTO")) 	// utente autorizzato a vedere tutto il reparto
	{
		$utentiReparto = fetchValuesArray("SELECT IdUtente FROM utente WHERE IdReparto=$idReparto");
		$utentiReparto = join(",",$utentiReparto);
		// note create o destinate al reparto, alle sue persone o a tutti
		$cond = "WHERE TipoNota='C' AND	(
					IdUtente = $idUtente OR IdUtenteDest = $idUtente OR (IdUtenteDest IS NULL AND IdReparto IS NULL)
					OR IdReparto=$idReparto OR IdUtente IN ($utentiReparto) OR IdUtenteDest IN ($utentiReparto)
				) AND NOT EXISTS (SELECT 1 FROM _opt_note_lette WHERE IdReparto=$idReparto AND IdNota=n.IdNota)";
		if (!userCanDo("READ_RISERVATO"))	// se non pu�  vedere quelle con flag riservato
			$cond .= " AND IFNULL(FlagRiservato,'N')!='Y'";	
	}
	else // utente semplice, pu� vedere solo quelle dirette o create da lui e quelle dirette a tutti
	{*/
		$cond = "WHERE TipoNota='C' AND	(
					IdUtente = $idUtente OR IdUtenteDest = 0$idUtente OR (IdUtenteDest IS NULL AND IdReparto IS NULL)
				) AND NOT EXISTS (SELECT 1 FROM _opt_note_lette WHERE IdReparto=$idReparto AND IdNota=n.IdNota)";
		if (!userCanDo("READ_RISERVATO"))	// se non pu�  vedere quelle con flag riservato
			$cond .= " AND IFNULL(FlagRiservato,'N')!='Y'";	
	/*}*/
	return $cond;	
}
?>