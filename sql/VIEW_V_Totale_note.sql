CREATE OR REPLACE VIEW v_totale_note
AS
SELECT IdContratto,IdUtente,count(*) as tot
FROM v_note_per_utente
GROUP BY IdContratto,IdUtente