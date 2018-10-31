/*
CREATE OR REPLACE VIEW v_lista_telefoni
AS
SELECT IdCliente,GROUP_CONCAT(Telefono SEPARATOR ', ') AS telefoni
 FROM v_telefoni_e_cellulari
GROUP BY IdCliente
;
*/
/** Ottimizzata 2018-10-31 */
CREATE OR REPLACE VIEW v_lista_telefoni
AS
select IdCliente, GROUP_CONCAT(DISTINCT trim(telefono)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS telefoni
from recapito
where length(trim(telefono))>3
GROUP BY idcliente;