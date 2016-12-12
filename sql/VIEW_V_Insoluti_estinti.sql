CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_estinti
AS
select
co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,co.CodContratto,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,co.ImpSaldoStralcio,co.DataSaldoStralcio,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto AS importo,ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,DataRata AS DataScadenza,
CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
co.IdCliente,p.IdFamiglia,co.IdAgenzia,
co.DataCambioStato,co.DataCambioClasse,DataInizioAffido,DataFineAffido,u.IdReparto, c.Telefono,
TitoloAttributo,
CASE WHEN DataUltimaAzione=CURDATE() THEN 'Y' WHEN DataUltimaAzione<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
ImpInteressiMora+ImpInteressiMoraAddebitati AS ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,(co.PercSvalutazione*100) as PercSvalutazione,(co.PercSvalutazione*co.ImpInsoluto) as Svalutazione,
NumInsoluti,NumRate,DATE_FORMAT(DataCambioStato,"%M %Y") AS MeseCambioStato
,co.IdFiliale,co.IdTipoPagamento,co.IdStatoContratto,co.IdRegolaProvvigione
,co.ImpFinanziato,co.IdDealer,co.IdProdotto,co.IdAttributo,co.IdCategoria,
IF (DataUltimaScadenza<=CURDATE(),0,
Numrate - (period_diff(EXTRACT(YEAR_MONTH FROM curdate()),EXTRACT(YEAR_MONTH FROM dataprimascadenza))
+ if (day(curdate())>=day(dataultimascadenza),1,0))) AS RateFuture,
co.IdStatoRinegoziazione AS FlagRinegoziazione,TitoloStatoRinegoziazione AS StatoRinegoziazione,
DescrBene,CodBene,td.FormDettaglio,sx.AbbrStatoContratto,co.DataChiusura
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statocontratto sx on sx.IdStatoContratto = co.IdStatoContratto
left join reparto r on r.IdReparto = co.IdAgenzia
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on ag.IdUtente = co.IdAgente
left join attributo att on co.IdAttributo=att.IdAttributo
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
left join statorinegoziazione rin ON rin.IdStatoRinegoziazione=co.IdStatoRinegoziazione
left join v_data_ultima_azione az ON az.IdContratto=co.IdContratto

where co.impinsoluto>26 AND co.idstatocontratto in (2, 3, 5, 14, 17, 22, 24)
AND (LEFT(CodContratto,2)='LO' OR co.IdAttributo IN (63,68,71,80,82,84,88)) ## vedi mail Federica Cerrato del 27/9/13 
AND idcontrattoderivato is null AND DataChiusura<=CURDATE()
and co.IdClasse!=19 #esclude quelli messi in EXIT
;
