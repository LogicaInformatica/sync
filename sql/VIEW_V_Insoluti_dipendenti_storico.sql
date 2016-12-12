## Versione storico di v_insoluti_dipendenti
CREATE OR REPLACE VIEW db_cnc_storico.v_insoluti_dipendenti
AS
select c.IdCliente,c.IdContratto,SUBSTR(CodCliente,3) AS CodAna,SUBSTR(CodContratto,3) AS numPratica,Nominativo,
       SUM(IF(i.DataChiusura IS NULL,1,0)) AS NumInsoluti,
       SUM(i.ImpCapitale+i.ImpInteressi+i.ImpInteressiMora+i.ImpCommissioni-i.ImpPagato) AS ImpDebito,
       MIN(DataScadenza) As DataRata,MAX(DATEDIFF(CURDATE(), DataScadenza)) AS GiorniRitardo,td.FormDettaglio,
       GREATEST(i.LastUpd,c.LastUpd) AS LastUpd
from db_cnc_storico.insolutodipendente i
LEFT JOIN db_cnc_storico.contratto c ON i.IdContratto=c.IdContratto
LEFT JOIN prodotto p on c.IdProdotto = p.IdProdotto
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
LEFT JOIN db_cnc_storico.cliente cl  ON c.IdCliente=cl.IdCliente
GROUP BY CodCliente,CodContratto;

