CREATE OR REPLACE ALGORITHM=MERGE VIEW  v_insoluti_positivi_opt
AS
select co.IdContratto,v.prodotto,co.CodContratto AS numPratica,v.CodUtente,
v.operatore,co.IdOperatore,co.IdAgente,v.CodAgente,
v.cliente,i.NumRate AS insoluti,
i.ImpDebitoTotale AS importo,i.ImpCapitaleAffidato,i.ImpPagatoTotale AS ImpPagato,v.stato,
v.AbbrStatoRecupero,co.CodRegolaProvvigione,
ImpInsoluto+ImpDebitoResiduo AS ImpDebitoResiduo,
v.classif,v.AbbrClasse,v.agenzia,
co.IdCliente,v.tipoPag,v.IdFamiglia,v.OrdineStato,v.stato AS CodStatoRecupero,co.IdAgenzia,
v.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,NULL AS DataScadenzaAzione,co.DataInizioAffido,co.DataFineAffido,
v.IdReparto, v.Telefono,  'N' as CiSonoAzioniOggi,DataUltimoPagamento,co.IdStatoRecupero,co.idClasse,v.CodiceFiscale
,co.ImpCapitale,
      CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 2
             WHEN FasciaRecupero = 'LEGALE' then 3
             WHEN FasciaRecupero = 'RINE' then 4
             ELSE 1 END AS TipoRecupero,v.FormDettaglio
from contratto co
left join regolaprovvigione rp ON co.idregolaprovvigione=rp.idregolaprovvigione
left join _opt_insoluti v ON co.IdContratto=v.IdContratto
left join v_importi_per_positivita_group i ON i.IdContratto=co.IdContratto AND i.DataFineAffido=co.DataFineAffido
WHERE Classif='POS' OR co.ImpInsoluto<26;