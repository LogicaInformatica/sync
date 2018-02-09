CREATE OR REPLACE VIEW v_graph_maxirata
AS
SELECT sm.IdCategoriaMaxirata, cm.CategoriaMaxirata,
COUNT(sm.IdCategoriaMaxirata) NumCategoriaMaxirata,
sum(sm.ImpInsoluto) as TotaleImportoInsoluto,
DATE_FORMAT(datamese,'%Y%m') as Mese
FROM statistichemaxirate sm
JOIN categoriamaxirata cm ON cm.IdCategoriaMaxirata = sm.IdCategoriaMaxirata
group by sm.IdCategoriaMaxirata, sm.datamese
order by sm.datamese;