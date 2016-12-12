#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_operatore
AS
SELECT IdUtente,NomeUtente,true as Selected
FROM utente u JOIN assegnazione a ON a.IdAgente=u.IdUtente
UNION ALL
SELECT -1," [nessuno]",true
