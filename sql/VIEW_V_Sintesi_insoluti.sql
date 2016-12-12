CREATE OR REPLACE VIEW v_sintesi_insoluti
AS
select IFNULL(c.IdAgenzia,ia.IdAgenzia) AS IdAgenzia,
	   CASE WHEN c.IdAgenzia IS NULL AND ia.IdAgenzia IS NULL THEN 'n/a'
	        WHEN c.IdAgenzia IS NULL THEN ia.agenzia ELSE rc.TitoloUfficio END AS Agenzia,
       sc.AbbrStatoRecupero,sc.CodStatoRecupero,
       fp.TitoloFamiglia AS TitoloFamiglia,
       count(0) AS NumPratiche,sc.Ordine AS OrdineStato,
       SUM(ia.NumInsoluti) AS NumInsoluti,
       sum(case when EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) then 1 else 0 end) AS Trattati,
       sum(case when NOT EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) then 1 else 0 end) AS DaTrattare,
       sum(ia.ImpInsoluto) AS ImpInsoluto,
       sum(ia.ImpPagato) AS ImpPagato,sum(ia.ImpCapitale) AS ImpCapitale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpInsoluto)),2) AS PercTotale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpCapitale)),2) AS PercCapitale
from contratto c
left join prodotto p on c.IdProdotto = p.IdProdotto
left join statorecupero sc on sc.IdStatoRecupero = c.IdStatoRecupero
left join v_insoluti_agenzia_group ia ON c.IdContratto=ia.IdContratto
left join famigliaprodotto fp on fp.IdFamiglia = p.IdFamiglia
left join reparto r ON r.IdReparto=ia.IdAgenzia
left join reparto rc ON rc.IdReparto=c.IdAgenzia
where (sc.CodStatoRecupero != 'NOR' OR ia.IdContratto IS NOT NULL)
group by AbbrStatoRecupero,sc.CodStatoRecupero,c.IdAgenzia,ia.agenzia,fp.TitoloFamiglia,sc.Ordine;