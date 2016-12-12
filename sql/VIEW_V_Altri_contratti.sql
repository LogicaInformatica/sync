CREATE OR REPLACE VIEW v_altri_contratti
AS
select co.IdContratto,p.CodProdotto AS prodotto,co.CodContratto AS numPratica,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,co.numrata AS rata,co.NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
co.impinsoluto AS importo,co.DataRata as DataScadenza,sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,
cl.CodClasse AS classif,
CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
     WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
     WHEN co.DataDbt IS NOT NULL THEN 'DBT'
     ELSE cl.AbbrClasse END AS AbbrClasse,r.TitoloUfficio AS agenzia
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c  on c.IdCliente = co.IdCliente
join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia;