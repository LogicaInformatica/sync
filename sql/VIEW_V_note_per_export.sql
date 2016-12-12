# view usata nella funzione di export delle note
CREATE OR REPLACE VIEW v_note_per_export
AS
select DATE_FORMAT(DataCreazione,'%d/%m/%Y %H:%i:%s') AS DataOra,u.Userid as Mittente,
IF(ud.Userid>0,ud.Userid,'Tutti') AS Destinatario,TestoNota,
IF(TipoNota='N','Nota','Messaggio') AS TipoNota,IdContratto
from nota n
left join utente u ON u.IdUtente=n.IdUtente
left join utente ud ON ud.IdUtente=n.IdUtenteDest
WHERE TipoNota IN ('N','C');