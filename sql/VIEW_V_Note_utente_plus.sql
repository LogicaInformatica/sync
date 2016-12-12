/*
  I contatori hanno i seguenti significato
  NotaUtenteNonAut: nota vista da utente non autorizzato (alle note riservate) e senza visibilità su reparto
  NotaUtenteAut:    nota vista da utente autorizzato e senza visibilità su reparto
  NotaRepartoNonAut: nota vista da utente non autorizzato ma con visibilità sul reparto
  NotaRepartoAut:   nota vista da utente autorizzato e con visibilità sul reparto
  NotaSuper:        nota vista dal supervisore (tutte)
*/
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_note_utente_plus (IdNota,IdContratto,IdUtente,IdCreatore,
NotaUtenteNonAut,NotaUtenteAut,NotaRepartoNonAut,NotaRepartoAut,NotaSuper)
AS
/* Note create dall'utente */
select IdNota,IdContratto,n.IdUtente,n.IdUtente as IdCreatore,
1 as NotaUtenteNonAut,1 as NotaUtenteAut,1 as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper 
FROM nota n WHERE TipoNota in ('N','C')
UNION ALL
/* Note dirette all'utente */
SELECT IdNota,IdContratto,n.IdUtenteDest,n.IdUtente,
1 as NotaUtenteNonAut,1 as NotaUtenteAut,1 as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper  
FROM nota n
WHERE TipoNota in ('N','C') AND IdUtente != IFNULL(IdUtenteDest,0) AND IdUtenteDest IS NOT NULL
/* Note dirette al reparto dell'utente */
UNION ALL
SELECT IdNota,IdContratto,u.IdUtente,n.IdUtente as IdCreatore,
IF(FlagRiservato='Y',0,1) as NotaUtenteNonAut,1 as NotaUtenteAut,IF(FlagRiservato='Y',0,1) as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper 
FROM nota n,utente u
WHERE TipoNota in ('N','C') AND n.IdUtente != u.IdUtente AND IdUtenteDest IS NULL AND u.IdReparto = IFNULL(n.IdReparto,0)
/* Note dirette a tutti */
UNION ALL
SELECT IdNota,IdContratto,u.IdUtente,n.IdUtente as IdCreatore,
IF(FlagRiservato='Y',0,1) as NotaUtenteNonAut,1 as NotaUtenteAut,IF(FlagRiservato='Y',0,1) as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper 
FROM nota n,utente u
WHERE TipoNota in ('N','C') AND IdUtenteDest IS NULL AND n.IdReparto IS NULL and n.idutente!=u.idutente
/* Note create da altri utenti del reparto e dirette non ad utenti del reparto */
UNION ALL
select IdNota,IdContratto,a.IdUtente,n.IdUtente as IdCreatore,
0 as NotaUtenteNonAut,0 as NotaUtenteAut,IF(FlagRiservato='Y',0,1) as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper 
FROM nota n,utente u,utente a
WHERE TipoNota in ('N','C')
AND n.IdUtente=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente AND IdUtenteDest!=a.IdUtente
and IFNULL(n.idUtenteDest,0) NOT IN (SELECT IdUtente FROM utente WHERE IdReparto=a.IdReparto)
/* Note dirette ad altri utenti del reparto */
UNION ALL
select IdNota,IdContratto,a.IdUtente,n.IdUtente as IdCreatore,
0 as NotaUtenteNonAut,0 as NotaUtenteAut,IF(FlagRiservato='Y',0,1) as NotaRepartoNonAut,1 as NotaRepartoAut,1 as NotaSuper 
FROM nota n,utente u,utente a
WHERE TipoNota in ('N','C') AND n.IdUtente != IFNULL(n.IdUtenteDest,0) AND n.IdUtenteDest IS NOT NULL
AND n.IdUtenteDest=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente AND IdUtenteDest!=a.IdUtente
AND n.IdUtente != a.idutente;

select * from v_note_utente_plus where idutente=1