CREATE OR REPLACE VIEW v_note_per_utente
AS
/* Note create dall'utente */
select IdNota,IdContratto,n.IdUtente
FROM nota n
WHERE TipoNota='N'
UNION
/* Note create da altri utenti del reparto se l'utente e' autorizzato */
select IdNota,IdContratto,a.IdUtente
FROM nota n,utente u,utente a
WHERE TipoNota='N'
AND n.IdUtente=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente
AND EXISTS (SELECT 1 FROM profiloutente pu,profilofunzione pf,funzione f
 WHERE pu.IdUtente=a.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_REPARTO')
AND (IFNULL(FlagRiservato,'N')='N' OR EXISTS
 (SELECT 1 FROM profiloutente pu,v_profili_funzioni_attivi pf,funzione f
 WHERE pu.IdUtente=a.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_RISERVATO'))
/* Note dirette all'utente */
UNION
SELECT IdNota,IdContratto,n.IdUtenteDest
FROM nota n
WHERE TipoNota='N' AND IdUtente != IFNULL(IdUtenteDest,0) AND IdUtenteDest IS NOT NULL
/* Note dirette ad altri utenti del reparto se l'utente è autorizzato */
UNION
select IdNota,IdContratto,a.IdUtente
FROM nota n,utente u,utente a
WHERE TipoNota='N' AND n.IdUtente != IFNULL(n.IdUtenteDest,0) AND n.IdUtenteDest IS NOT NULL
AND n.IdUtenteDest=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente
AND EXISTS (SELECT 1 FROM profiloutente pu,profilofunzione pf,funzione f
 WHERE pu.IdUtente=a.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_REPARTO')
AND (IFNULL(FlagRiservato,'N')='N' OR EXISTS
 (SELECT 1 FROM profiloutente pu,v_profili_funzioni_attivi pf,funzione f
 WHERE pu.IdUtente=u.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_RISERVATO'))
/* Note dirette al reparto dell'utente */
UNION
SELECT IdNota,IdContratto,u.IdUtente
FROM nota n,utente u
WHERE TipoNota='N' AND n.IdUtente != u.IdUtente AND IdUtenteDest IS NULL AND u.IdReparto = IFNULL(n.IdReparto,0)
AND (IFNULL(FlagRiservato,'N')='N' OR EXISTS
 (SELECT 1 FROM profiloutente pu,v_profili_funzioni_attivi pf,funzione f
 WHERE pu.IdUtente=u.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_RISERVATO'))
/* Note dirette a tutti */
UNION
SELECT IdNota,IdContratto,u.IdUtente
FROM nota n,utente u
WHERE TipoNota='N' AND IdUtenteDest IS NULL AND n.IdReparto IS NULL
AND (IFNULL(FlagRiservato,'N')='N' OR EXISTS
 (SELECT 1 FROM profiloutente pu,v_profili_funzioni_attivi pf,funzione f
 WHERE pu.IdUtente=u.IdUtente and pu.idprofilo=pf.idprofilo and pf.idfunzione=f.idfunzione and codfunzione='READ_RISERVATO'))
