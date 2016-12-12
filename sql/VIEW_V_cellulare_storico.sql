# ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_cellulare
AS
select IdCliente, GROUP_CONCAT(DISTINCT trim(cellulare)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS Cellulare
from db_cnc_storico.recapito
where length(trim(cellulare))>3 AND (trim(cellulare) LIKE '3%' OR trim(cellulare) LIKE '+%')
GROUP BY idcliente