<?php
require_once("common.php");
//==============================================================
//  FUNZIONE PER L'AGGIORNAMENTO DELLA TABELLA _opt_insoluti
//  usata per l'pottimizzazione delle query su views come la
//  v_insoluti
//==============================================================

//-------------------------------------------------------------- 
// updateOptInsoluti
// Aggiorna un insieme di righe della tabella optInsoluti
// Argomenti: $condizione	condizione WHERE da applicare alla
//                          tabella Contratto
//--------------------------------------------------------------
function updateOptInsoluti($condizione)
{
	// Determina i contratti da aggiornare
	$ids = fetchValuesArray("SELECT IdContratto FROM contratto WHERE $condizione");
	if (!is_array($ids))
		return false;
	$ids = join(",",$ids);
	if ($ids=='')
		return false;	
	// Cancella le righe da aggiornare
	beginTrans();
	if (!execute("DELETE FROM _opt_insoluti WHERE IdContratto IN ($ids)")) {
		rollback();
		return false;		
	}
	// Riscrive le righe da aggiornare
	//$startTime = microtime(true);
	// 28/4/2016: ottimizzato eliminando la view, che rallenta di ordini di grandezza rispetto alla query nuda e cruda
	//if (!execute("INSERT INTO _opt_insoluti SELECT * FROM v_riempimento_opt_insoluti WHERE IdContratto IN ($ids)")) {
	$sql = "INSERT INTO _opt_insoluti select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto),FormDettaglio,u.CodUtente,
u.NomeUtente,ag.Userid,ifnull(c.Nominativo,c.RagioneSociale),IFNULL(CodiceFiscale,PartitaIVA),sc.CodStatoRecupero,
TitoloStatoRecupero,sc.AbbrStatoRecupero,
cl.CodClasse,TitoloClasse,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN cl.IdClasse=19 THEN cl.AbbrClasse
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,
CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS Agenzia,
CodTipoPagamento AS TipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,cl.FlagNoAffido,
DataScadenzaAzione,u.IdReparto, c.Telefono,IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,FlagCambioAgente,
IFNULL(cl.FlagRecupero,'N') AS InRecupero,TitoloStatoRinegoziazione AS StatoRinegoziazione,NOW() AS LastUpd,
IF(EXISTS(SELECT 1 FROM storiarecupero sr WHERE sr.idContratto=co.IdContratto),'Y','N') AS FlagStoria,
leg.TitoloStatoLegale, stg.TitoloStatoStragiudiziale, c.CodCliente, mr.CategoriaMaxirata, rs.CategoriaRiscattoLeasing, co.FlagVisuraAci
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on ag.IdUtente = co.IdAgente
left join v_prossima_scadenza ps on ps.IdContratto=co.IdContratto
left join categoria cat on co.IdCategoria=cat.IdCategoria
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
left join statorinegoziazione rin ON rin.IdStatoRinegoziazione=co.IdStatoRinegoziazione
left join statolegale leg ON leg.IdStatoLegale=co.IdStatoLegale
left join statostragiudiziale stg ON stg.IdStatoStragiudiziale=co.IdStatoStragiudiziale
left join categoriamaxirata mr ON mr.IdCategoriaMaxirata=co.IdCategoriaMaxirata
left join categoriariscattoleasing rs ON rs.IdCategoriaRiscattoLeasing=co.IdCategoriaRiscattoLeasing
	WHERE co.IdContratto IN ($ids)"; 
	if (!execute($sql)) {
		rollback();
		return false;		
	}
	//trace("Timing updateOptInsoluti: ".(microtime(true)-$startTime),FALSE);
	
	commit();
	return TRUE;
}
?>