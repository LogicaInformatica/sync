CREATE OR REPLACE  VIEW v_log_storiarecupero AS
select
cast(s.DataEvento as date) AS Data,
s.DataEvento AS DataOra,
IFNULL(a.TitoloAzione,'Altra azione') AS Evento,
concat(s.DescrEvento,' - Contratto: ',c.CodContratto) AS Descrizione,
IFNULL(s.UseridCancellato,IFNULL(u.Userid,'system')) AS Utente
from (((storiarecupero s left join azione a on((s.IdAzione = a.IdAzione)))
left join contratto c on((s.IdContratto = c.IdContratto)))
left join utente u on((s.IdUtente = u.IdUtente)))
where (s.IdUtente is not null)
union all
select * from v_log;