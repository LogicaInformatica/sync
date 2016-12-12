create or replace view v_recapiti_mandato as
select sm.Ruolo, sm.IdContratto,sm.FlagGarante,
CASE WHEN cl.Nominativo IS NOT NULL THEN cl.Nominativo
     WHEN rl.Nome IS NOT NULL THEN rl.Nome
     ELSE cl.RagioneSociale END AS Soggetto,
CASE WHEN cl.Nominativo IS NOT NULL THEN rp.Indirizzo
     WHEN rl.Indirizzo >'' THEN rl.Indirizzo
     ELSE rp.Indirizzo END AS Indirizzo,
LPAD(CASE WHEN cl.Nominativo IS NOT NULL THEN rp.CAP
     WHEN rl.Indirizzo >''  THEN rl.CAP
     ELSE rp.CAP END,5,'0') AS Cap,
CASE WHEN cl.Nominativo IS NOT NULL THEN rp.Localita
     WHEN rl.Indirizzo >''  THEN rl.Localita
     ELSE rp.Localita END AS Localita,
IFNULL(CONCAT('(',CASE WHEN cl.Nominativo IS NOT NULL THEN rp.SiglaProvincia
     WHEN rl.Indirizzo >''  THEN rl.SiglaProvincia
     ELSE rp.SiglaProvincia END,')'),'') AS SiglaProvincia,
DATE_FORMAT(cl.DataNascita,'%d/%m/%Y') AS DataNascitaIT, cl.LocalitaNascita, cl.CodiceFiscale, rp.Telefono, rp.Cellulare, tc.AltriNumeri
from v_soggetti_mandato sm
LEFT JOIN cliente cl on cl.IdCliente=sm.IdCliente
LEFT JOIN v_recapito_di_tipo rl ON sm.IdCliente=rl.IdCliente AND rl.IdTipoRecapito=5	-- 'LEG'
LEFT JOIN v_recapito_di_tipo rp ON sm.IdCliente=rp.IdCliente AND rp.IdTipoRecapito=1	-- 'BASE'
LEFT JOIN v_altri_telefoni tc ON tc.IdCliente=sm.IdCliente

order by 1;