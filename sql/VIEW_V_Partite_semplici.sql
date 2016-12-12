CREATE OR REPLACE VIEW v_partite_semplici
AS
select m.IdContratto,m.NumRata,TitoloTipoInsoluto,
       CASE WHEN CategoriaMovimento='C' AND DataScadenza IS NOT NULL AND Importo>0 THEN DataScadenza END AS DataScadenza,
       CASE WHEN Importo<0 AND (CategoriaMovimento='P' OR CategoriaMovimento IS NULL) THEN DataCompetenza END AS DataPagamento,
       CASE WHEN Importo<0 AND CategoriaMovimento='P' THEN TitoloTipoMovimento
            WHEN IFNULL(i.ImpInsoluto,0)<=0 AND m.IdTipoMovimento=163 THEN ' RID'
            ELSE ''
       END AS CausalePagamento,
       CASE WHEN CategoriaMovimento='C' AND DataScadenza IS NOT NULL AND Importo>0 THEN Importo END AS Rata,
       Importo AS Debito,i.IdInsoluto
FROM movimento m
JOIN tipomovimento t ON m.idtipomovimento=t.idtipomovimento
LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto
LEFT JOIN insoluto i ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata AND i.ImpInsoluto>0; # si vede solo se c'è debito