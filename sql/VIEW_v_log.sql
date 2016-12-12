CREATE OR REPLACE VIEW v_log AS
select
cast(l.lastupd as date) AS Data,
l.lastupd AS DataOra,
l.Sorgente AS Evento,
l.DescrEvento AS Descrizione,
IFNULL(u.Userid,IFNULL(l.UseridCancellato,'system')) AS Utente
from log l left join utente u ON l.IdUtente = u.IdUtente;