CREATE OR REPLACE VIEW v_provincia_decodifica
AS
Select p.SiglaProvincia,p.TitoloProvincia,p.IdRegione,r.TitoloRegione
from provincia p
left join regione r on(p.IdRegione=r.IdRegione)
order by 1;