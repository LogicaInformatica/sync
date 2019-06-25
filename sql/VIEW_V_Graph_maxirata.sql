CREATE OR REPLACE VIEW v_graph_maxirata
AS
SELECT IFNULL(sm.IdCategoriaMaxirata,99) as IdCategoriaMaxirata, IFNULL(cm.CategoriaMaxirata,'Senza categoria') as CategoriaMaxirata,
COUNT(IFNULL(sm.IdCategoriaMaxirata,99)) NumCategoriaMaxirata,
sum(sm.ImpInsoluto) as TotaleImportoInsoluto,
DATE_FORMAT(datamese,'%Y%m') as Mese
FROM statistichemaxirate sm
LEFT JOIN categoriamaxirata cm ON cm.IdCategoriaMaxirata = sm.IdCategoriaMaxirata
group by sm.IdCategoriaMaxirata, DATE_FORMAT(sm.datamese, '%Y%m')
order by DATE_FORMAT(sm.datamese, '%Y%m');