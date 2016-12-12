CREATE OR REPLACE VIEW v_sintesi_per_agenzia_e_lotto
AS
select TitoloUfficio AS Agenzia,ia.IdAgenzia,Lotto,ia.DataFineAffido,
       count(0) AS NumPratiche,
       SUM(ia.NumInsoluti) AS NumInsoluti,
       sum(ia.ImpInsoluto) AS ImpInsoluto,
       sum(ia.ImpPagato) AS ImpPagato,sum(ia.ImpRate) AS ImpCapitale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpInsoluto)),2) AS PercTotale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpRate)),2) AS PercCapitale
from contratto c
left join v_importi_aggregati_lotto ia ON c.IdContratto=ia.IdContratto
left join reparto r on r.IdReparto = ia.IdAgenzia
where (IdStatoRecupero != 1 OR idclasse=18) AND ia.idAgenzia IS NOT NULL
group by Ia.IdAgenzia,DataFineAffido
