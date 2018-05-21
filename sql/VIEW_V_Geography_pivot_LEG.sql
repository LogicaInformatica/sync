CREATE OR REPLACE VIEW v_geography_pivot_LEG
AS
select Area,Mese,
SUM(IF(agenzia='Totale', IPR, null)) AS Totale,
SUM(IF(agenzia='Totale', NumPratiche, null)) AS TotaleNum,
SUM(IF(agenzia LIKE '%Luzzi%L34%', IPR, null)) AS Luzzi,
SUM(IF(agenzia LIKE '%Luzzi%', NumPratiche, null)) AS LuzziNum,
SUM(IF(agenzia LIKE '%Cube%', IPR, null)) AS LSCube,
SUM(IF(agenzia LIKE '%Cube%', NumPratiche, null)) AS LSCubeNum,
SUM(IF(agenzia LIKE '%Fides%L37%', IPR, null)) AS Fides,
SUM(IF(agenzia LIKE '%Fides%L37%', NumPratiche, null)) AS FidesNum
FROM v_geography
WHERE TipoFascia=3
GROUP BY Area,Mese
order by IdArea;