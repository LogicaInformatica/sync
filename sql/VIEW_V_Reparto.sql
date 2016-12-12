CREATE OR REPLACE VIEW `v_reparto`
AS select TitoloCompagnia,r.*,CASE WHEN IdTipoCompagnia=1 THEN 'I' ELSE 'E' END AS TipoReparto
from compagnia c  join  reparto r on c.IdCompagnia = r.IdCompagnia
ORDER BY TitoloCompagnia,TitoloUfficio,CodUfficio