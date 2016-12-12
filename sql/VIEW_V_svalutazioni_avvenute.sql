#
# Lista delle azioni (convalidate o da-non-convalidare) che hanno prodotto svalutazione
#
CREATE OR REPLACE VIEW v_svalutazioni_avvenute
AS
SELECT sr.IdContratto,sr.IdStoriaRecupero,sr.IdAzione,a.PercSvalutazione
FROM storiarecupero sr
JOIN azione a ON a.IdAzione=sr.IdAzione And a.PercSvalutazione>0
LEFT JOIN azionespeciale az ON az.IdAzioneSpeciale=sr.IdAzioneSpeciale
WHERE (IFNULL(FlagSpeciale,'N')='N' OR Stato='A');