CREATE OR REPLACE VIEW v_piano_rientro
AS
SELECT p.*,c.IdOperatore,IFNULL(NomeUtente,'(non assegnate)') AS NomeOperatore
FROM pianorientro p
JOIN contratto c ON p.IdContratto=c.IdContratto
LEFT JOIN utente u ON c.IdOperatore=u.IdUtente;