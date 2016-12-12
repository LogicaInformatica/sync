CREATE OR REPLACE VIEW `v_processi_automatici`AS
SELECT IdEvento, CodEvento, TitoloEvento AS Processo,
CASE WHEN FlagSospeso='N' THEN 'Attivo' WHEN FlagSospeso='Y' THEN 'Sospeso' WHEN FlagSospeso='U' THEN 'Una tantum' END AS Stato,
Date_Format(OraInizio, '%H:%i') AS OraIni, Date_Format(OraFine,'%H:%i') AS OraFin, 
(select count(*) from automatismoevento ae where ae.IdEvento=e.IdEvento) AS numAuto 
FROM eventosistema e;