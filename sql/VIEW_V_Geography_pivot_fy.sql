CREATE OR REPLACE VIEW v_geography_pivot_fy
AS
select Area,Anno,
SUM(IF(agenzia='Totale', IPR, null)) AS Totale,
SUM(IF(agenzia='Totale', NumPratiche, null)) AS TotaleNum,
SUM(IF(agenzia='CITY (24)', IPR, null)) AS City1,
SUM(IF(agenzia='CITY (24)', NumPratiche, null)) AS City1Num,
SUM(IF(agenzia='CITY (P4)', IPR, null)) AS City2,
SUM(IF(agenzia='CITY (P4)', NumPratiche, null)) AS City2Num,
SUM(IF(agenzia='CSS (I8)', IPR, null)) AS CSSI8, /* dal 2020-06-08 */
SUM(IF(agenzia='CSS (I8)', NumPratiche, null)) AS CSSI8Num, /* dal 2020-06-08 */
SUM(IF(agenzia='CSS (45)', IPR, null)) AS Css,
SUM(IF(agenzia='CSS (45)', NumPratiche, null)) AS CssNum,
SUM(IF(agenzia='EUROCOLLECTION (20)', IPR, null)) AS Eurocollection,
SUM(IF(agenzia='EUROCOLLECTION (20)', NumPratiche, null)) AS EurocollectionNum,
SUM(IF(agenzia='FIDES (L2)', IPR, null)) AS Fides,
SUM(IF(agenzia='FIDES (L2)', NumPratiche, null)) AS FidesNum,
SUM(IF(agenzia='GEA Services (I2)', IPR, null)) AS GeaServices, /* non serve piu' dal 2020-06-08 */
SUM(IF(agenzia='GEA Services (I2)', NumPratiche, null)) AS GeaServicesNum, /* non serve piu' dal 2020-06-08 */
SUM(IF(agenzia='KREOS (I7)', IPR, null)) AS Kreos,
SUM(IF(agenzia='KREOS (I7)', NumPratiche, null)) AS KreosNum,
SUM(IF(agenzia LIKE '%(31)', IPR, null)) AS Ncp31, -- da 2018-10-04 NCP accorpato in Studio Luzzi
SUM(IF(agenzia LIKE '%(31)', NumPratiche, null)) AS Ncp31Num,
SUM(IF(agenzia='NICOL (35)', IPR, null)) AS Nicol35,
SUM(IF(agenzia='NICOL (35)', NumPratiche, null)) AS Nicol35Num,
SUM(IF(agenzia='OSIRC (2A)', IPR, null)) AS Osirc,
SUM(IF(agenzia='OSIRC (2A)', NumPratiche, null)) AS OsircNum,
SUM(IF(agenzia='FIRE (2B)', IPR, null)) AS FIRE,
SUM(IF(agenzia='FIRE (2B)', NumPratiche, null)) AS FireNum,
SUM(IF(agenzia='SETEL (27)', IPR, null)) AS Setel1,
SUM(IF(agenzia='SETEL (27)', NumPratiche, null)) AS Setel1Num,
SUM(IF(agenzia='SETEL (29)', IPR, null)) AS Setel2,
SUM(IF(agenzia='SETEL (29)', NumPratiche, null)) AS Setel2Num,
SUM(IF(agenzia='SOGEC (S2)', IPR, null)) AS Sogec1,
SUM(IF(agenzia='SOGEC (S2)', NumPratiche, null)) AS Sogec1Num,
SUM(IF(agenzia='SOGEC (P2)', IPR, null)) AS Sogec2,
SUM(IF(agenzia='SOGEC (P2)', NumPratiche, null)) AS Sogec2Num,
SUM(IF(agenzia='STARCREDIT (06)', IPR, null)) AS Starcredit,
SUM(IF(agenzia='STARCREDIT (06)', NumPratiche, null)) AS StarcreditNum,
SUM(IF(agenzia='STING (F2)', IPR, null)) AS Sting,
SUM(IF(agenzia='STING (F2)', NumPratiche, null)) AS StingNum
from v_geography_fy
WHERE TipoFascia IN (0,1)
GROUP BY Area,Anno
order by idArea;