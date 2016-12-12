CREATE OR REPLACE VIEW v_insoluti_override
AS
select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto AS importo,ImpPagato,DataRata AS DataScadenza,sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,
cl.CodClasse AS classif,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,r.TitoloUfficio AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,DataScadenzaAzione,DataInizioAffido,DataFineAffido,u.IdReparto, c.Telefono,
IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,co.CodRegolaProvvigione,
CASE WHEN DATE(DataUltimaAzione)=CURDATE() THEN 'Y' WHEN DATE(DataUltimaAzione)<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
 co.IdFiliale AS IdFiliale,co.IdTipoSpeciale as IdTipoSpeciale,f.TitoloFiliale as TitoloFiliale,ts.TitoloTipoSpeciale as TitoloTipoSpeciale,
 ts.FlagForzatura as FlagForzatura,fl.TitoloFiliale as Responsabile, DataDecorrenza, TitoloCompagnia AS Dealer,
 co.ImpCapitale,td.FormDettaglio
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
left join filiale f on f.IdFiliale=co.IdFiliale
left join tipospeciale ts on ts.IdTipoSpeciale=co.IdTipoSpeciale
left join filiale fl on fl.IdFiliale=co.IdResponsabile
left join compagnia cg on cg.idcompagnia=co.iddealer
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
WHERE co.IdFiliale is not null
and FlagForzatura = 'Y'
and co.IdStatoRecupero not in (1,7) and co.idclasse!=18 and co.impinsoluto>=26;
