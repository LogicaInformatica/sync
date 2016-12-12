#
# Controllo IPR<=100% capitale
#
create or replace view v_check_07
as
select idcontratto,CodContratto,DataFineAffido AS CapitaleAffidato,impcapitaleaffidato,ImpRiconosciuto AS IPR
from v_dettaglio_provvigioni
where datafineaffido>=CURDATE()-INTERVAL 4 DAY
AND ImpRiconosciuto>impcapitaleaffidato
order by 2;


