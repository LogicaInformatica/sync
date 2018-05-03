create or replace view v_tabs_provvigioni
as
select DISTINCT rp.idreparto,titoloufficio as agenzia,
       CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 2 
             WHEN FasciaRecupero = 'LEGALE' then 3
             WHEN FasciaRecupero = 'RINE' then 4
              ELSE 1 END AS tipo
from regolaprovvigione rp 
join reparto r on r.idreparto=rp.idreparto
where ordine is not null
AND EXISTS (SELECT 1 FROM provvigione p WHERE p.IdRegolaProvvigione=rp.IdRegolaProvvigione)
order by rp.ordine,titoloufficio;