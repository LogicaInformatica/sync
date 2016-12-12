CREATE OR REPLACE VIEW v_storiarecupero_storico
AS
SELECT IFNULL(a.titoloAzione,'(azione automatica)') AS titoloAzione,
a.CodAzione,
IFNULL(u.UserId,'system') as UserId,
sr.IdStoriaRecupero,
sr.IdContratto,
sr.DataEvento,
# Soluzione provvisoria per uno dei dati non UTF8
replace(CONVERT(sr.DescrEvento USING utf8),'à','a\'') AS DescrEvento,
replace(convert(IF(te.IdTipoEsito IS NULL,NotaEvento,CONCAT(TitoloTipoEsito,'. ',NotaEvento))
 using utf8),'€','&euro;') AS NotaEvento,
sr.IdSuper as IdSuper,
ut.USerid as UserSuper,
sr.IdAzioneSpeciale,
sr.HtmlAzioneEseguita,
sr.ValoriAzioneEseguita,
a.FormWidth,
a.FormHeight,a.FlagSpeciale
FROM db_cnc_storico.storiarecupero sr
LEFT JOIN azione a ON sr.IdAzione=a.IdAzione
LEFT JOIN utente u ON sr.IdUtente=u.IdUtente
LEFT JOIN tipoesito te ON te.idtipoesito=sr.idtipoesito
LEFT JOIN utente ut ON sr.IdSuper=ut.IdUtente