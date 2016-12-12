CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti
AS
select
co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,co.CodContratto,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,co.ImpSaldoStralcio,co.DataSaldoStralcio,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto AS importo,ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,DataRata AS DataScadenza,sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,
cl.CodClasse AS classif,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN cl.IdClasse=19 THEN cl.AbbrClasse
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,
CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,DataScadenzaAzione,DataInizioAffido,DataFineAffido,u.IdReparto, c.Telefono,
IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,
CASE WHEN DataUltimaAzione=CURDATE() THEN 'Y' WHEN DataUltimaAzione<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
ImpInteressiMora+ImpInteressiMoraAddebitati AS ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,(co.PercSvalutazione*100) as PercSvalutazione,(co.PercSvalutazione*co.ImpInsoluto) as Svalutazione,
IFNULL(cl.FlagRecupero,'N') AS InRecupero,NumInsoluti,NumRate,DATE_FORMAT(DataCambioStato,"%M %Y") AS MeseCambioStato
,co.IdFiliale,co.IdTipoPagamento,co.IdStatoContratto,co.IdRegolaProvvigione
,co.ImpFinanziato,co.IdDealer,co.IdProdotto,co.IdAttributo,co.IdCategoria,
IF (DataUltimaScadenza<=CURDATE(),0,
Numrate - (period_diff(EXTRACT(YEAR_MONTH FROM curdate()),EXTRACT(YEAR_MONTH FROM dataprimascadenza))
+ if (day(curdate())>=day(dataultimascadenza),1,0))) AS RateFuture,
co.IdStatoRinegoziazione AS FlagRinegoziazione,TitoloStatoRinegoziazione AS StatoRinegoziazione,
DescrBene,CodBene,td.FormDettaglio
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
left join v_data_ultima_azione az ON az.IdContratto=co.IdContratto;
