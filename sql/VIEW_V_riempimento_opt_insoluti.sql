#
# Vista usata per riempire la tabella _opt_insoluti
# ATTENZIONE: LA VISTA NON VIENE PIU' USATA in UpdateOptInsoluti perche' sostituita per ottimizzazione dalla
# corrispondente SELECT
CREATE OR REPLACE VIEW v_riempimento_opt_insoluti
(idContratto, Prodotto, FormDettaglio, CodUtente,
Operatore, CodAgente, Cliente,  CodiceFiscale, Stato, TitoloStatoRecupero, AbbrStatoRecupero,
 Classif, TitoloClasse, AbbrClasse, Agenzia, TipoPag, IdFamiglia, OrdineStato, FlagNoAffido,
 DataScadenzaAzione, IdReparto, Telefono, Categoria,  FlagCambioAgente,
 InRecupero, StatoRinegoziazione, LastUpd,FlagStoria,StatoLegale)
AS
select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto),FormDettaglio,u.CodUtente,
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
TitoloStatoLegale
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

select * from contratto where codregolaprovvigione='20'
;