CREATE OR REPLACE VIEW v_contratto_precrimine
AS
select c.*,DATE_FORMAT(ip.DataInsoluto,'%e/%m') AS ScadenzaPrecrimine,CAST(ip.DataInsoluto as char) as Riferimento
FROM v_contratto_lettera c
LEFT JOIN insolutoprecrimine ip ON ip.idcontratto=c.idcontratto
AND ip.DataInsoluto >= CURDATE() AND NOT EXISTS (SELECT 1 FROM insolutoprecrimine x where ip.idcontratto=x.idcontratto
and x.idinsoluto<ip.idinsoluto);