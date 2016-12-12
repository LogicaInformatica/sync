#
# View usata nelle liste di pratiche svalutate storicizzate
#
CREATE OR REPLACE VIEW v_storico_svalutazione
AS
select c.IdContratto,CodContratto,c.IdCliente,IFNULL(RagioneSociale,Nominativo) AS Cliente,TitoloProdotto AS Prodotto,ImpDebito,
s.PercSvalutazione,ROUND(s.PercSvalutazione*ImpDebito,2) AS Svalutazione,YEAR(DataStorico) AS Anno
from contratto c
JOIN cliente cl ON cl.IdCliente=c.IdCliente
JOIN prodotto p ON p.IdProdotto=c.IdProdotto
JOIN storicosvalutazione s ON c.IdContratto=s.IdContratto;