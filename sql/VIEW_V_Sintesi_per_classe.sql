CREATE OR REPLACE VIEW v_sintesi_per_classe
AS
select IFNULL(TitoloClasse,'(Non classificate)') AS Classe,IFNULL(c.IdClasse,0) AS IdClasse,
       count(0) AS NumPratiche,
       SUM(c.NumInsoluti) AS NumInsoluti,
       sum(c.ImpInsoluto) AS ImpInsoluto,
       sum(c.ImpPagato) AS ImpPagato,sum(c.ImpCapitale) AS ImpCapitale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpInsoluto)),2) AS PercTotale,
       round(((sum(c.ImpPagato) * 100) / sum(c.ImpCapitale)),2) AS PercCapitale
from contratto c
left join classificazione cl ON cl.IdClasse=c.IdClasse
where (IdStatoRecupero != 1 OR c.idclasse=18)
group by cl.Ordine,Classe;