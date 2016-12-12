CREATE OR REPLACE VIEW v_tabs_agenzie
AS
select distinct concat(r.idReparto,',',IFNULL(CodRegolaProvvigione,'')) AS ChiaveAgenzia,
        concat(r.titoloufficio,CASE WHEN CodRegolaProvvigione IS NULL THEN '' ELSE concat(' (',CodRegolaProvvigione,')') END ) as NomeAgenzia,
        CASE WHEN FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' THEN 2 
             WHEN FasciaRecupero = 'LEGALE' then 3
             WHEN FasciaRecupero = 'RINE' then 4
             ELSE 1 END AS tipo
        from reparto r 
        JOIN regolaprovvigione rp ON rp.IdReparto=r.IdReparto
        WHERE (CURDATE() BETWEEN r.DataIni AND r.DataFin)
        order by rp.ordine,rp.codRegolaProvvigione;