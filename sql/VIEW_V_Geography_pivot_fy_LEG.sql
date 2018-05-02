CREATE OR REPLACE VIEW v_geography_pivot_fy_LEG
AS
select Area,Anno,

SUM(IF(agenzia='Totale', IPR, null)) AS Totale,
SUM(IF(agenzia='Totale', NumPratiche, null)) AS TotaleNum,
SUM(IF(agenzia LIKE '%Luzzi%L34%', IPR, null)) AS Luzzi,
SUM(IF(agenzia LIKE '%Luzzi%', NumPratiche, null)) AS LuzziNum,
SUM(IF(agenzia LIKE '%Cube%', IPR, null)) AS LSCube,
SUM(IF(agenzia LIKE '%Cube%', NumPratiche, null)) AS LSCubeNum,
SUM(IF(agenzia LIKE '%Fides%L37%', IPR, null)) AS Fides,
SUM(IF(agenzia LIKE '%Fides%L37%', NumPratiche, null)) AS FidesNum
from v_geography_fy
WHERE TipoFascia=3
GROUP BY Area,Anno
order by idArea;