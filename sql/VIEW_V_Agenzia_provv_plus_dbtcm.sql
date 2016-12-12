CREATE OR REPLACE VIEW v_agenzia_provv_plus_dbtcm
AS
SELECT -1 AS IdRegolaProvvigione,"" AS TitoloAgenzia
UNION ALL
SELECT IdRegolaProvvigione,
CONCAT(TitoloUfficio,
       CASE WHEN TitoloRegolaProvvigione IS NOT NULL THEN CONCAT(' [Codice ',CodRegolaProvvigione,' - ',TitoloRegolaProvvigione,']') ELSE '' END)
        AS TitoloAgenzia
FROM reparto r LEFT JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto
WHERE CURDATE() BETWEEN r.DataIni AND r.DataFin
AND CURDATE() BETWEEN rp.DataIni AND rp.DataFin
AND IdCompagnia IN (SELECT IdCompagnia FROM compagnia WHERE IdTipoCompagnia=2)
AND rp.IdClasse = 17
order by 2;