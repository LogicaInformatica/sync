#CREATE OR REPLACE ALGORITHM=MERGE VIEW  v_insoluti_positivi
#AS
#select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
#u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
#ifnull(c.Nominativo,c.RagioneSociale) AS cliente,co.NumRata AS rata,co.NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS Giorni,
#co.ImpInsoluto,co.ImpCapitale AS importo,co.ImpPagato,co.DataRata AS DataScadenza,sc.CodStatoRecupero AS stato,
#sc.AbbrStatoRecupero,co.CodRegolaProvvigione,
#cl.CodClasse AS classif,
#CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
#     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
#     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
#     ELSE cl.AbbrClasse END AS AbbrClasse,r.TitoloUfficio AS agenzia,
#co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
#cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,NULL AS DataScadenzaAzione,co.DataInizioAffido,co.DataFineAffido,
#u.IdReparto, c.Telefono,  'N' as CiSonoAzioniOggi,DataUltimoPagamento,co.IdStatoRecupero,co.idClasse,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale
#,co.ImpCapitale,  
#      CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 2 
#             WHEN FasciaRecupero = 'LEGALE' then 3
#             WHEN FasciaRecupero = 'RINE' then 4
#             ELSE 1 END AS TipoRecupero,td.FormDettaglio
#from contratto co
#join prodotto p on co.IdProdotto = p.IdProdotto
#join cliente c on c.IdCliente = co.IdCliente
#left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
#left join classificazione cl on cl.IdClasse = co.IdClasse
#left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
#left join utente u on u.IdUtente = co.IdOperatore
#left join utente ag on co.IdAgente = ag.IdUtente
#left join reparto r on r.IdReparto = co.IdAgenzia
#left join regolaprovvigione rp ON co.idregolaprovvigione=rp.idregolaprovvigione
#left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
#WHERE CodClasse='POS' OR co.ImpInsoluto<26;

CREATE OR REPLACE ALGORITHM=MERGE VIEW  v_insoluti_positivi
AS
select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,i.NumRate AS insoluti,
i.ImpDebitoTotale AS importo,i.ImpCapitaleAffidato,i.ImpPagatoTotale AS ImpPagato,sc.CodStatoRecupero AS stato,
sc.AbbrStatoRecupero,co.CodRegolaProvvigione,
cl.CodClasse AS classif,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,r.TitoloUfficio AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,NULL AS DataScadenzaAzione,co.DataInizioAffido,co.DataFineAffido,
u.IdReparto, c.Telefono,  'N' as CiSonoAzioniOggi,DataUltimoPagamento,co.IdStatoRecupero,co.idClasse,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale
,co.ImpCapitale,  
      CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 2 
             WHEN FasciaRecupero = 'LEGALE' then 3
             WHEN FasciaRecupero = 'RINE' then 4
             ELSE 1 END AS TipoRecupero,td.FormDettaglio
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on co.IdAgente = ag.IdUtente
left join reparto r on r.IdReparto = co.IdAgenzia
left join regolaprovvigione rp ON co.idregolaprovvigione=rp.idregolaprovvigione
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
left join v_importi_per_positivita_group i ON i.IdContratto=co.IdContratto AND i.DataFineAffido=co.DataFineAffido
WHERE CodClasse='POS' OR co.ImpInsoluto<26;
