CREATE OR REPLACE VIEW v_data_ultima_azione
AS
SELECT IdContratto,DATE(MAX(DataEvento)) AS DataUltimaAzione
FROM   storiarecupero
WHERE  DATE(DataEvento)<=CURDATE() AND IdAzione>0 AND IdUtente>0
GROUP BY IdContratto;