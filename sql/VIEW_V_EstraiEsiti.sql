CREATE OR REPLACE VIEW v_estrai_esiti
AS
SELECT co.CodContratto, sr.DataEvento, ifnull(cl.Nominativo,cl.RagioneSociale) AS cliente,
sr.UserId,sr.CodAzione,sr.titoloAzione,sr.DescrEvento,sr.NotaEvento
FROM v_storiarecupero sr
LEFT JOIN contratto co ON co.IdContratto=sr.IdContratto
LEFT JOIN cliente cl ON cl.IdCliente=co.IdCliente
order by 1, 2 DESC