CREATE OR REPLACE VIEW v_proponente_writeoff
AS
select wo.IdContratto,IFNULL(u.IdUtente,uw.IdUtente) AS IdUtente,IFNULL(u.NomeUtente,uw.NomeUtente) AS Proponente
FROM writeoff wo
LEFT JOIN storiarecupero sr ON wo.IdContratto=sr.IdContratto AND sr.IdAzione=385
  AND NOT EXISTS (SELECT 1 FROM storiarecupero x WHERE sr.IdContratto=IdContratto AND IdAzione=385 AND x.IdStoriaRecupero>sr.IdStoriarecupero)
LEFT JOIN utente u ON u.IdUtente=sr.IdUtente
LEFT JOIN utente uw ON convert(uw.Userid using utf8)=convert(wo.lastuser using utf8);