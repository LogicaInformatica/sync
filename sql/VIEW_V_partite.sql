Create or replace view v_partite
as
select m.IdContratto,m.NumRata,i.IdInsoluto,IdMovimento,DataRegistrazione,DataCompetenza,DataScadenza,DataValuta,DataDocumento, TitoloTipoMovimento, TitoloTipoInsoluto,
CASE WHEN Importo>0 THEN Importo ELSE NULL END AS Debito,CASE WHEN Importo<0 THEN -Importo ELSE NULL END AS Credito,
i.ImpInsoluto AS Saldo,NumDocumento
FROM movimento m 
LEFT JOIN tipomovimento t ON m.idtipomovimento=t.idtipomovimento
LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto 
LEFT JOIN insoluto i ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata  AND i.ImpInsoluto>0 # si vede solo se c'è debito
ORDER BY IdMovimento;