CREATE OR REPLACE VIEW v_sintesi_per_prodotto
AS
select fp.TitoloFamiglia AS Famiglia,
       f.TitoloFamiglia AS Prodotto,f.IdFamiglia,
       count(0) AS NumPratiche,
       SUM(c.NumInsoluti) AS NumInsoluti,
       sum(c.ImpInsoluto) AS ImpInsoluto,
       sum(c.ImpPagato) AS ImpPagato,sum(c.ImpCapitale) AS ImpCapitale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpInsoluto)),2) AS PercTotale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpCapitale)),2) AS PercCapitale
from contratto c
left join prodotto p ON p.IdProdotto=c.IdProdotto
left join famigliaprodotto f ON f.IdFamiglia=p.IdFamiglia
left join famigliaprodotto fp ON f.IdFamigliaParent=fp.IdFamiglia
where (IdStatoRecupero != 1 OR idclasse=18)
group by fp.TitoloFamiglia,f.TitoloFamiglia