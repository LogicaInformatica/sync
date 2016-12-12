CREATE OR REPLACE VIEW v_totale_allegati
AS
SELECT IdContratto,IdUtente,count(*) as tot
FROM v_allegati_per_utente
GROUP BY IdContratto,IdUtente