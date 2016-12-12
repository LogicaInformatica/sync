# Azioni ancora da convalidare
CREATE OR REPLACE VIEW v_azioni_da_convalidare
AS
select azs.IdAzioneSpeciale,azs.IdContratto,u.NomeUtente,azs.Nota,azs.IdAzione,azs.DataScadenza,azs.DataEvento
from azionespeciale azs
join storiarecupero sr on azs.idazionespeciale=sr.idazionespeciale
join utente u on u.IdUtente=azs.IdUtente
where azs.stato='W';