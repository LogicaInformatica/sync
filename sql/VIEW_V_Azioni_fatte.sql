/* OBSOLETA (E INEFFICIENTE PERCHE' TEMPTABLE) */
create or replace view v_azioni_fatte
as
select IdContratto,IdUtente,Max(DataEvento) AS DataUltimaAzione,count(*) as NumAzioni
from storiarecupero
WHERE IdUtente>0 AND IdAzione>0
group by IdContratto,IdUtente