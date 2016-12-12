CREATE OR REPLACE VIEW v_agenzia_provv
AS
# la riga con "codice provv. automatico" la fa comparire solo per il tipo "AGE"
SELECT DISTINCT CAST(CONCAT(r.IdReparto,",") AS CHAR)  AS IdAgenzia,
       CONCAT(TitoloUfficio, ' [Codice provv. automatico]') AS TitoloAgenzia,
	  'AGE' AS TipoAgenzia,
       r.*,'' AS CodRegolaProvvigione
FROM reparto r JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto AND NOT (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' OR FasciaRecupero = 'LEGALE')
WHERE CURDATE() BETWEEN r.DataIni AND r.DataFin
  AND CURDATE()+INTERVAL 1 MONTH BETWEEN rp.DataIni AND rp.DataFin
  AND IdCompagnia IN (SELECT IdCompagnia FROM compagnia WHERE IdTipoCompagnia=2)
UNION ALL
SELECT CONCAT(r.IdReparto,',',IFNULL(IdRegolaProvvigione,0)) AS IdAgenzia,
CONCAT(TitoloUfficio,
       CASE WHEN TitoloRegolaProvvigione IS NOT NULL THEN CONCAT(' [Codice ',CodRegolaProvvigione,IF(rp.DataFin<CURDATE()+INTERVAL 2 MONTH,' (OLD)',''),' - ',TitoloRegolaProvvigione,']') 
            ELSE ''
       END) AS TitoloAgenzia,
       CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 'STR' 
            WHEN FasciaRecupero = 'LEGALE' then 'LEG'
            WHEN FasciaRecupero = 'RINE' then 'RIN'
            ELSE 'AGE' 
       END AS TipoAgenzia,
       r.*,CodRegolaProvvigione
FROM reparto r LEFT JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto
WHERE CURDATE() BETWEEN r.DataIni AND r.DataFin
AND CURDATE()+INTERVAL 1 MONTH BETWEEN IFNULL(rp.DataIni,'2001-01-01') AND IFNULL(rp.DataFin,'9999-12-31')
AND IdCompagnia IN (SELECT IdCompagnia FROM compagnia WHERE IdTipoCompagnia=2);

SELECT * FROM v_agenzia_provv;

select * from compagnia where  idcompagnia=1036;