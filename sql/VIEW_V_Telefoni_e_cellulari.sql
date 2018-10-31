/*
CREATE OR REPLACE VIEW v_telefoni_e_cellulari
AS
select IdCliente,Telefono
from v_telefono
UNION
select IdCliente,Cellulare
from v_cellulare
;
*/
CREATE OR REPLACE VIEW v_telefoni_e_cellulari
AS
select IdCliente, GROUP_CONCAT(DISTINCT trim(telefono)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS Telefono
from recapito
where length(trim(telefono))>3
GROUP BY idcliente;