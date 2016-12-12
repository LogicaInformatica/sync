CREATE OR REPLACE VIEW v_sintesi_per_agenzia
AS
select TitoloUfficio AS Agenzia,c.IdAgenzia,
       count(0) AS NumPratiche,
       SUM(c.NumInsoluti) AS NumInsoluti,
       sum(c.ImpInsoluto) AS ImpInsoluto,
       sum(c.ImpPagato) AS ImpPagato,sum(c.ImpCapitale) AS ImpCapitale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpInsoluto)),2) AS PercTotale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpCapitale)),2) AS PercCapitale
from contratto c
left join reparto r on r.IdReparto = c.IdAgenzia
where (IdStatoRecupero != 1 OR idclasse=18) AND c.idAgenzia IS NOT NULL
group by TitoloUfficio;