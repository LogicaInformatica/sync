create or replace view v_qualche_azione
as
select IdContratto
from storiarecupero
WHERE IdUtente IS NOT NULL AND IdAzione IS NOT NULL
GROUP BY IdContratto