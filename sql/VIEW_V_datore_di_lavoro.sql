create or replace view v_datore_di_lavoro as
select c.IdContratto, r.Nome,r.Indirizzo,r.Cap,r.Localita,r.SiglaProvincia,r.Telefono, r.Cellulare
from contratto c
JOIN recapito r ON c.IdCliente=r.IdCliente AND r.IdTipoRecapito=4 AND (nome>'' or telefono>'' or cellulare>'' or indirizzo>'')
AND NOT EXISTS (SELECT 1 FROM recapito x WHERE c.IdCliente=x.IdCliente AND x.IdTipoRecapito=4 AND (nome>'' or telefono>'' or cellulare>'' or indirizzo>'')
                AND x.IdRecapito>r.IdRecapito);