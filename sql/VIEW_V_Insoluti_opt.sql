## modificata il 23/12/14 per aggiungere il calcolo dell'importo su pratiche con impinsoluto NULL (positive)
## modificata il 9/5/2016 per aggiungere il campo calcolato ImpDebitoIniziale = importo+ImpPagato
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_opt
AS
select
co.IdContratto,v.Prodotto AS prodotto,co.CodContratto AS numPratica,co.CodContratto,v.CodUtente,
v.operatore,co.IdOperatore,co.IdAgente,v.CodAgente,co.ImpSaldoStralcio,co.DataSaldoStralcio,
v.cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,
IFNULL(co.ImpInsoluto,IF(IdAgenzia>0,ImpSpeseRecupero,0)+IF(rp.FlagInteressiMora='Y',ImpInteressiMora,0)) as importo,
ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,DataRata AS DataScadenza,
v.stato,v.AbbrStatoRecupero,v.classif,v.AbbrClasse,v.agenzia,
co.IdCliente,v.tipoPag,v.IdFamiglia,v.OrdineStato,v.stato AS CodStatoRecupero,co.IdAgenzia,
v.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,v.DataScadenzaAzione,DataInizioAffido,DataFineAffido,v.IdReparto, v.Telefono,
v.Categoria,
CASE WHEN DATE(DataUltimaAzione)=CURDATE() THEN 'Y' WHEN DATE(DataUltimaAzione)<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza, co.DataDBT,v.CodiceFiscale,
ImpInteressiMora+ImpInteressiMoraAddebitati AS ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,(co.PercSvalutazione*100) as PercSvalutazione,(co.PercSvalutazione*co.ImpInsoluto) as Svalutazione,
v.InRecupero,NumInsoluti,NumRate,LAST_DAY(DataCambioStato) AS MeseCambioStato
,co.IdFiliale,co.IdTipoPagamento,co.IdStatoContratto,co.IdRegolaProvvigione
,co.ImpFinanziato,co.IdDealer,co.IdProdotto,co.IdAttributo,co.IdCategoria,
IF (DataUltimaScadenza<=CURDATE(),0,
Numrate - (period_diff(EXTRACT(YEAR_MONTH FROM curdate()),EXTRACT(YEAR_MONTH FROM dataprimascadenza))
+ if (day(curdate())>=day(dataultimascadenza),1,0))) AS RateFuture,
co.IdStatoRinegoziazione AS FlagRinegoziazione,v.StatoRinegoziazione,
DescrBene,CodBene,FormDettaglio,IdCOntrattoDerivato,DataChiusura,StatoLegale,co.IdStatoLegale,StatoStragiudiziale,co.IdStatoStragiudiziale,Garanzie,
IFNULL(co.ImpInsoluto,IF(IdAgenzia>0,ImpSpeseRecupero,0)+IF(rp.FlagInteressiMora='Y',ImpInteressiMora,0))
+ImpPagato AS ImpDebitoIniziale, v.CodCliente, co.IdCategoriaMaxirata, v.CategoriaMaxirata, co.IdCategoriaRiscattoLeasing, v.CategoriaRiscattoLeasing, co.FlagVisuraAci
from contratto co
join _opt_insoluti v ON v.IdContratto=co.IdContratto
left join regolaripartizione rp ON rp.IdRegolaProvvigione=co.IdRegolaProvvigione;