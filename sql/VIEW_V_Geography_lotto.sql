CREATE OR REPLACE VIEW v_geography_lotto
AS
select a.idArea,IFNULL(TitoloArea,'n/a') AS Area,CONCAT(TitoloUfficio,' (',r.CodRegolaProvvigione,')') AS Agenzia,count(*) as NumPratiche,v.datafineaffido,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100.0/SUM(v.ImpCapitaleAffidato),2) END AS IPR,
SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato,v.idagenzia,fasciarecupero,r.CodRegolaProvvigione,
CASE WHEN (fasciarecupero like '%ESA%' OR fasciarecupero like '%HOME%' OR fasciarecupero like '%LEASING%' or fasciarecupero='FLOTTE'  or fasciarecupero LIKE 'SALDO%') THEN 1
     ELSE 0
END AS TipoFascia
from v_importi_per_provvigioni_full v
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
JOIN regolaprovvigione r ON p.idregolaprovvigione=r.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
JOIN reparto re ON re.IdReparto=v.IdAgenzia
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE FasciaRecupero NOT LIKE 'DBT%' AND FasciaRecupero NOT LIKE '%REPO%' 
AND FasciaRecupero NOT LIKE 'LEGA%' AND FasciaRecupero NOT LIKE 'MAXI%'
AND FasciaRecupero NOT LIKE 'RINE%'
AND FasciaRecupero NOT LIKE 'RISCA%'
group by cl.idarea,r.CodRegolaProvvigione,v.dataFineAffido

UNION ALL 
select a.idArea,IFNULL(TitoloArea,'n/a') AS Area,CONCAT(TitoloUfficio,' (',r.CodRegolaProvvigione,')') AS Agenzia,count(*) as NumPratiche,v.datafineaffido,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100.0/SUM(v.ImpCapitaleAffidato),2) END AS IPR,
SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato,v.idagenzia,fasciarecupero,
r.CodRegolaProvvigione,2 AS TipoFascia
from dettaglioprovvigione v join provvigione p on v.IdProvvigione=p.IdProvvigione
JOIN regolaprovvigione r ON r.idregolaprovvigione=p.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
JOIN reparto re ON re.IdReparto=v.IdAgenzia
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero = '%REPO%') AND v.TipoCalcolo='C'
group by cl.idarea,r.CodRegolaProvvigione,v.dataFineAffido

UNION ALL 

select a.idArea,IFNULL(TitoloArea,'n/a') AS Area,'Totale' AS Agenzia,count(*) as NumPratiche,v.datafineaffido,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100.0/SUM(v.ImpCapitaleAffidato),2) END AS IPR,
SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato,0,
'Totale' as fasciarecupero,'Totale' as CodRegolaProvvigione,
CASE WHEN (fasciarecupero like '%ESA%' OR fasciarecupero like '%HOME%' OR fasciarecupero like '%LEASING%' or fasciarecupero='FLOTTE' or fasciarecupero LIKE 'SALDO%') THEN 1
     ELSE 0
END AS TipoFascia
from v_importi_per_provvigioni_full v
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
JOIN regolaprovvigione r ON p.idregolaprovvigione=r.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE FasciaRecupero NOT LIKE 'DBT%' AND FasciaRecupero NOT LIKE '%REPO%' 
AND FasciaRecupero NOT LIKE 'LEGA%' AND FasciaRecupero NOT LIKE 'MAXI%'
AND FasciaRecupero NOT LIKE 'RINE%'
AND FasciaRecupero NOT LIKE 'RISCA%'
group by cl.idarea,v.dataFineAffido,CASE WHEN (fasciarecupero like '%ESA%' OR fasciarecupero like '%HOME%' OR fasciarecupero like '%LEASING%' or fasciarecupero='FLOTTE' or fasciarecupero LIKE 'SALDO%') THEN 1
     ELSE 0
END

UNION ALL
select a.idArea,IFNULL(TitoloArea,'n/a') AS Area,'Totale' AS Agenzia,count(*) as NumPratiche,v.datafineaffido,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100.0/SUM(v.ImpCapitaleAffidato),2) END AS IPR,
SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato,0,
'Totale' as fasciarecupero,'Totale' as CodRegolaProvvigione,2 AS TipoFascia
from v_importi_per_provvigioni_special v
JOIN regolaprovvigione r ON v.idregolaprovvigione=r.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE FasciaRecupero LIKE 'DBT%' OR FasciaRecupero = '%REPO%'
group by cl.idarea,v.dataFineAffido;