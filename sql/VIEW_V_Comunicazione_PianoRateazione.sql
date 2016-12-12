CREATE OR REPLACE VIEW v_comunicazione_pianodrateazione AS
select DATE_FORMAT(CURDATE(),'%d %M %Y') AS Oggi, 
c.IdContratto, 
IFNULL(cli.Nominativo,cli.RagioneSociale) AS Intestatario, 
r.Indirizzo,
r.Cap,r.Localita,
r.SiglaProvincia
from contratto c
join cliente cli ON c.IdCliente=cli.IdCliente
JOIN recapito r ON c.IdCliente=r.IdCliente AND r.IdTipoRecapito=1
join pianorientro pr on c.IdContratto = pr.IdContratto
AND (indirizzo>'')
AND NOT EXISTS (SELECT 1 FROM recapito x WHERE c.IdCliente=x.IdCliente 
                AND x.IdTipoRecapito=1 
                AND (indirizzo>'')
                AND x.IdRecapito>r.IdRecapito)
