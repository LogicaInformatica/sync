CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_str_opt
AS
select
co.IdContratto,v.prodotto,co.CodContratto AS numPratica,v.CodUtente,
v.operatore,co.IdOperatore,co.IdAgente,v.CodAgente,
v.cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,v.stato,v.AbbrStatoRecupero,
v.classif,v.AbbrClasse,v.agenzia,co.IdCliente,v.tipoPag,v.IdFamiglia,v.OrdineStato,v.stato AS CodStatoRecupero,co.IdAgenzia,
v.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,DataInizioAffido,DataFineAffido,v.IdReparto, v.Telefono,
v.Categoria,co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,v.CodiceFiscale,
ImpInteressiMora,ImpSpeseRecupero,co.CodRegolaProvvigione,co.ImpCapitale,co.PercSvalutazione,((co.PercSvalutazione/100)*co.ImpInsoluto) as Svalutazione,
v.InRecupero,NumInsoluti,NumRate,FasciaRecupero,v.FormDettaglio,StatoLegale,co.IdStatoLegale,StatoStragiudiziale, co.IdStatoStragiudiziale, Garanzie
from contratto co
JOIN _opt_insoluti v ON v.IdContratto=co.IdContratto
left join regolaprovvigione rp on  rp.IdRegolaProvvigione = co.IdRegolaProvvigione
Where (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' OR FasciaRecupero = 'LEGALE');
