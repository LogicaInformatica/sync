CREATE OR REPLACE VIEW v_geography_pivot_STR
AS
select Area,Mese,
SUM(IF(agenzia='Totale', IPR, null)) AS Totale,
SUM(IF(agenzia='Totale', NumPratiche, null)) AS TotaleNum,
SUM(IF(agenzia='CSS (16)', IPR, null)) AS Css,
SUM(IF(agenzia='CSS (16)', NumPratiche, null)) AS CssNum,
SUM(IF(agenzia LIKE 'ERNST%38%', IPR, null)) AS EY,
SUM(IF(agenzia LIKE 'ERNST%38%', NumPratiche, null)) AS EYNum,
SUM(IF(agenzia='FIDES (36)', IPR, null)) AS Fides,
SUM(IF(agenzia='FIDES (36)', NumPratiche, null)) AS FidesNum,
SUM(IF(agenzia='FIRE (07)', IPR, null)) AS Fire,
SUM(IF(agenzia='FIRE (07)', NumPratiche, null)) AS FireNum,
SUM(IF(agenzia='NICOL (05)', IPR, null)) AS Nicol,
SUM(IF(agenzia='NICOL (05)', NumPratiche, null)) AS NicolNum,
SUM(IF(agenzia='CITY (25)', IPR, null)) AS City,
SUM(IF(agenzia='CITY (25)', NumPratiche, null)) AS CityNum,
SUM(IF(agenzia='IRC FAST SRL (26)', IPR, null)) AS Irc,
SUM(IF(agenzia='IRC FAST SRL (26)', NumPratiche, null)) AS IrcNum,
SUM(IF(agenzia LIKE '%Luzzi%33%', IPR, null)) AS Luzzi,
SUM(IF(agenzia LIKE '%Luzzi%33%', NumPratiche, null)) AS LuzziNum

##,
##SUM(IF(agenzia='NCP (30)', IPR, null)) AS Ncp,
##SUM(IF(agenzia='NCP (30)', NumPratiche, null)) AS NcpNum
from v_geography
WHERE TipoFascia=2
GROUP BY Area,Mese
order by IdArea;