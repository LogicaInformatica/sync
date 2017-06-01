create or replace view v_insoluti_count
as
select co.*,sc.CodStatoRecupero AS stato,
cl.CodClasse AS classif,cl.FlagNoAffido,u.IdReparto,tr.CodTipoReparto,r.TitoloUfficio,IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,
IFNULL(cl.FlagRecupero,'N') AS InRecupero,DATEDIFF(CURDATE(), DataRata) AS giorni,cl.AbbrClasse,tp.CodTipoPagamento AS tipoPag,
CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
co.CodContratto AS numPratica,co.IdStatoRinegoziazione AS FlagRinegoziazione,pr.IdFamiglia
from contratto co
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia
left join tiporeparto tr on tr.IdTipoReparto = r.IdTipoReparto
left join utente u on u.IdUtente =co.IdOperatore
left join categoria cat on co.IdCategoria=cat.IdCategoria
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join prodotto pr on  co.IdProdotto = pr.IdProdotto;

