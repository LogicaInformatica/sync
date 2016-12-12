#
# View usata per la lista scadenzario di tipo "Azioni legali in scadenza"
#
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_azioni
AS
select
co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,ImpPagato,sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,
cl.CodClasse AS classif,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,
CASE WHEN co.CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',co.CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,DataInizioAffido,DataFineAffido,u.IdReparto, c.Telefono,
IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
ImpInteressiMora,ImpSpeseRecupero,co.CodRegolaProvvigione,co.ImpCapitale,co.PercSvalutazione,((co.PercSvalutazione/100)*co.ImpInsoluto) as Svalutazione,
IFNULL(cl.FlagRecupero,'N') AS InRecupero,NumInsoluti,NumRate,FasciaRecupero,DataScadenza,TitoloAzione AS Azione,td.FormDettaglio
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
join azionespeciale sp ON co.IdContratto=sp.idcontratto and sp.datascadenza is not null
join azione az on az.idazione=sp.idazione
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on ag.IdUtente = co.IdAgente
left join categoria cat on co.IdCategoria=cat.IdCategoria
left join regolaprovvigione rp on  rp.IdRegolaProvvigione = co.IdRegolaProvvigione
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
Where (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' OR FasciaRecupero = 'LEGALE');
