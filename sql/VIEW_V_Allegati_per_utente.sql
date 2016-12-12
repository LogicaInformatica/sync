CREATE OR REPLACE VIEW v_allegati_per_utente
AS
/* Allegati creati dall'utente e allegati non creati dall'utente (a meno che non siano riservati e l'utente non disponga della necessaria autorità) */
SELECT IdAllegato,IdContratto,u.IdUtente
FROM allegato a,utente u
WHERE a.IdUtente=u.IdUtente
OR IFNULL(FlagRiservato,'N')='N'
OR u.IdReparto in (SELECT IdReparto
FROM reparto where IdTipoReparto=1);