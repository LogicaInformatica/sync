CREATE OR REPLACE VIEW v_comunicazione_saldostralcio AS
select DATE_FORMAT(CURDATE(),'%d %M %Y') AS Oggi, 
c.IdContratto, c.CodContratto,
IFNULL(cli.Nominativo,cli.RagioneSociale) AS Intestatario, 
r.Indirizzo,
r.Cap,r.Localita,
r.SiglaProvincia,
SUBSTRING(c.CodContratto, 3, LENGTH(c.CodContratto)) AS numFinanziamento,
replace(replace(replace(format((IFNULL(c.ImpDebitoResiduo,0)+IFNULL(c.ImpInsoluto,0)),2),'.',';'),',','.'),';',',') AS impCapitaleSalStr,
DATE_FORMAT(c.DataSaldoStralcio,'%d/%m/%Y') AS dataPagamentoSalStr,
replace(replace(replace(format(IFNULL(c.ImpSaldoStralcio,0),2),'.',';'),',','.'),';',',') AS importoSalStr
from contratto c
join cliente cli ON c.IdCliente=cli.IdCliente
JOIN recapito r ON c.IdCliente=r.IdCliente AND r.IdTipoRecapito=1
AND (indirizzo>'')
AND NOT EXISTS (SELECT 1 FROM recapito x WHERE c.IdCliente=x.IdCliente 
                AND x.IdTipoRecapito=1 
                AND (indirizzo>'')
                AND x.IdRecapito>r.IdRecapito);