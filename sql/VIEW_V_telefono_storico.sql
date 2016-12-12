# ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_telefono
AS
select idcliente, GROUP_CONCAT(DISTINCT trim(telefono)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS telefono
from db_cnc_storico.recapito
where length(trim(telefono))>3
GROUP BY idcliente