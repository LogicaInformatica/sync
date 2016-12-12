CREATE OR REPLACE VIEW v_cellulare
AS
select IdCliente, GROUP_CONCAT(DISTINCT trim(cellulare)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS Cellulare
from recapito
where length(trim(cellulare))>3 AND (trim(cellulare) LIKE '3%' OR trim(cellulare) LIKE '+%')
GROUP BY idcliente