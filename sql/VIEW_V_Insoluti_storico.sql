CREATE OR REPLACE VIEW v_insoluti_storico
AS
select
co.IdContratto,v.prodotto,co.CodContratto AS numPratica,co.CodContratto,v.CodUtente,co.LastUpd,
v.operatore,co.IdOperatore,co.IdAgente,v.CodAgente,co.ImpSaldoStralcio,co.DataSaldoStralcio,
v.cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto AS importo,ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,DataRata AS DataScadenza,
v.agenzia,co.IdCliente,v.IdFamiglia,co.IdAgenzia,
co.DataCambioStato,co.DataCambioClasse,DataInizioAffido,DataFineAffido,v.IdReparto, v.Telefono,
att.TitoloAttributo,
CASE WHEN DATE(DataUltimaAzione)=CURDATE() THEN 'Y' WHEN DATE(DataUltimaAzione)<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,v.CodiceFiscale,
ImpInteressiMora+ImpInteressiMoraAddebitati AS ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,(co.PercSvalutazione*100) as PercSvalutazione,(co.PercSvalutazione*co.ImpInsoluto) as Svalutazione,
NumInsoluti,NumRate,DATE_FORMAT(DataCambioStato,"%M %Y") AS MeseCambioStato
,co.IdFiliale,co.IdTipoPagamento,co.IdStatoContratto,co.IdRegolaProvvigione
,co.ImpFinanziato,co.IdDealer,co.IdProdotto,co.IdAttributo,co.IdCategoria,
IF (DataUltimaScadenza<=CURDATE(),0,
Numrate - (period_diff(EXTRACT(YEAR_MONTH FROM curdate()),EXTRACT(YEAR_MONTH FROM dataprimascadenza))
+ if (day(curdate())>=day(dataultimascadenza),1,0))) AS RateFuture,
co.IdStatoRinegoziazione AS FlagRinegoziazione,v.StatoRinegoziazione,
DescrBene,CodBene,v.FormDettaglio,sx.AbbrStatoContratto,co.DataChiusura
from db_cnc_storico.contratto co
join db_cnc_storico._opt_insoluti v ON v.IdContratto=co.IdContratto
left join statocontratto sx on sx.IdStatoContratto = co.IdStatoContratto
left join attributo att on co.IdAttributo=att.IdAttributo
WHERE (IFNULL(FlagStoria,'N')='Y' OR CodContratto LIKE 'KG%')
;