CREATE OR REPLACE VIEW v_email
AS
select IdCliente, GROUP_CONCAT(DISTINCT trim(email)
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR '; ')
                  AS Email
from recapito
where length(trim(email))>4 AND email LIKE '%@%'
GROUP BY idcliente