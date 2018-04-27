#
# Usata per le formule provvigionali che richiedono il calcolo su ogni contratto (rinegoziazioni e legali)
#
CREATE OR REPLACE VIEW v_contratto_per_provvigione
AS
SELECT dp.IdProvvigione,dp.ImpPagatoTotale,c.*,cd.PercTasso AS NuovoTasso,
 IFNULL(sl.PercProvvigione,10) AS PercProvvigioneLegale
FROM contratto c
JOIN dettaglioprovvigione dp ON dp.IdContratto=c.IdContratto
LEFT JOIN statolegale sl ON sl.IdStatoLegale=c.IdStatoLegale
LEFT JOIN contratto cd ON c.IdContrattoDerivato=cd.IdContratto;