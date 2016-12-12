#
# Vista usata per il test delle "condizioni" contenute in RegolaAffidamento
#
CREATE OR REPLACE VIEW v_cond_affidamento
AS
select a.IdAgenzia AS IdAgenziaPrecedente,rp.codRegolaProvvigione as CodProvvigionePrecedente,
c.*,cl.RagioneSociale,DATEDIFF(CURDATE(), DataRata) AS GiorniRitardo
from contratto c join cliente cl on c.IdCliente = cl.IdCliente
LEFT JOIN assegnazione a ON c.IdContratto=a.IdContratto AND a.datafin<=CURDATE() AND a.IdAgenzia>0 AND NOT EXISTS
 (select 1 from assegnazione x where x.datafin>a.datafin and x.idcontratto=a.idcontratto and x.datafin<=CURDATE())
LEFT JOIN regolaprovvigione rp ON rp.IdRegolaProvvigione=a.IdRegolaProvvigione;