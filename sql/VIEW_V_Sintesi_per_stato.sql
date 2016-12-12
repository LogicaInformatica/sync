CREATE OR REPLACE VIEW v_sintesi_per_stato
AS
select CASE WHEN CodStatoRecupero='NOR' OR IdClasse=18 THEN 'Positive' ELSE sc.TitoloStatoRecupero END AS StatoRecupero,c.IdStatoRecupero,
       count(0) AS NumPratiche,sc.Ordine,
       SUM(c.NumInsoluti) AS NumInsoluti,
       sum(c.ImpInsoluto) AS ImpInsoluto,
       sum(c.ImpPagato) AS ImpPagato,sum(c.ImpCapitale) AS ImpCapitale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpInsoluto)),2) AS PercTotale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpCapitale)),2) AS PercCapitale
from contratto c
left join statorecupero sc on sc.IdStatoRecupero = c.IdStatoRecupero
where (c.IdStatoRecupero != 1 OR idclasse=18)
group by StatoRecupero
ORDER BY c.IdStatoRecupero;