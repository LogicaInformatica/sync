CREATE OR REPLACE VIEW v_recapito
AS
select r.*,CodTipoRecapito,TitoloTipoRecapito,
if (r.LastUser='system' or r.LastUser='import','N','S') AS modificabile
from recapito r join tiporecapito t on t.IdTipoRecapito = r.IdTipoRecapito
where CURDATE() BETWEEN r.DataIni AND r.DataFin
ORDER by IdCliente,IdContratto,ProgrRecapito;