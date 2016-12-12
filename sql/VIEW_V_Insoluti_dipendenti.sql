CREATE OR REPLACE VIEW v_insoluti_dipendenti
AS
select c.IdCliente,c.IdContratto,SUBSTR(CodCliente,3) AS CodAna,SUBSTR(CodContratto,3) AS numPratica,Nominativo,
       SUM(IF(i.DataChiusura IS NULL,1,0)) AS NumInsoluti,
       SUM(i.ImpCapitale+i.ImpInteressi+i.ImpInteressiMora+i.ImpCommissioni-i.ImpPagato) AS ImpDebito,
       MIN(DataScadenza) As DataRata,MAX(DATEDIFF(CURDATE(), DataScadenza)) AS GiorniRitardo,td.FormDettaglio
from insolutodipendente i
LEFT JOIN contratto c ON i.IdContratto=c.IdContratto
LEFT JOIN prodotto p on c.IdProdotto = p.IdProdotto
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
LEFT JOIN cliente cl  ON c.IdCliente=cl.IdCliente
WHERE i.DataChiusura IS NULL
GROUP BY CodCliente,CodContratto; 
