### ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_recapito
AS
select r.*,CodTipoRecapito,TitoloTipoRecapito,
if (r.LastUser='system' or r.LastUser='import','N','S') AS modificabile
from db_cnc_storico.recapito r 
join tiporecapito t on t.IdTipoRecapito = r.IdTipoRecapito
where CURDATE() BETWEEN r.DataIni AND r.DataFin
ORDER by IdCliente,IdContratto,ProgrRecapito;