### ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_note_per_export
AS
select DATE_FORMAT(DataCreazione,'%d/%m/%Y %H:%i:%s') AS DataOra,u.Userid as Mittente,
IF(ud.Userid>0,ud.Userid,'Tutti') AS Destinatario,TestoNota,
IF(TipoNota='N','Nota','Messaggio') AS TipoNota,IdContratto
from db_cnc_storico.nota n
left join utente u ON u.IdUtente=n.IdUtente
left join utente ud ON ud.IdUtente=n.IdUtenteDest
WHERE TipoNota IN ('N','C');