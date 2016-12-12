CREATE OR REPLACE VIEW v_area_geo_organizzazione
AS
Select p.IdArea,p.CodArea,p.TitoloArea,p.TipoArea,p.Cap,p.SiglaProvincia,pr.TitoloProvincia,p.IdAreaParent,
case 
when r.TitoloArea is not null then r.TitoloArea
else 'Macroarea' end as TitoloAreaParent,
case 
when r.TitoloArea is not null then 1
else 0 end as ordinatore
from area p
left join area r on(p.IdAreaParent=r.IdArea)
left join provincia pr  on(p.SiglaProvincia=pr.SiglaProvincia)
order by p.TipoArea desc,p.IdAreaParent asc,p.TitoloArea;