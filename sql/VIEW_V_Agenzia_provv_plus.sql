CREATE OR REPLACE VIEW v_agenzia_provv_plus
AS
SELECT -1 AS IdRegolaProvvigione,' Elimina forzatura precedente' AS TitoloAgenzia,'' AS TipoAgenzia
UNION ALL
SELECT 0,' Al rientro, forza in lavorazione interna','' AS TipoAgenzia
UNION ALL
SELECT -2,' Affida a un legale (da decidere)','GENLEG' AS TipoAgenzia
UNION ALL
SELECT IdRegolaProvvigione,
CONCAT(TitoloUfficio,
       CASE WHEN TitoloRegolaProvvigione IS NOT NULL THEN CONCAT(' [Codice ',CodRegolaProvvigione,IF(rp.DataFin<'9999-12-31',' (OLD)',''),' - ',TitoloRegolaProvvigione,']') ELSE '' END)
        AS TitoloAgenzia,
       CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 'STR'
            WHEN FasciaRecupero = 'LEGALE' then 'LEG'
            WHEN FasciaRecupero = 'RINE' then 'RIN'
            ELSE 'AGE' END AS TipoAgenzia
FROM reparto r JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto
WHERE CURDATE() BETWEEN r.DataIni AND r.DataFin
AND CURDATE()+INTERVAL 1 MONTH BETWEEN rp.DataIni AND rp.DataFin
order by 2;