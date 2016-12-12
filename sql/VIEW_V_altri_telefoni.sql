/* Att.ne: VIEW INEFFICIENTE (TEMPTABLE): usata solo in stampa mandato, dove non crea problemi di prestazioni */

create or replace view v_altri_telefoni as
select idcliente,
GROUP_CONCAT(DISTINCT IFNULL(trim(telefono),''), trim(IFNULL(CONCAT(' ',cellulare),''))
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS AltriNumeri
from recapito
where IdTipoRecapito!=1 AND (length(trim(telefono))>3 OR length(trim(cellulare))>3)
GROUP BY idcliente