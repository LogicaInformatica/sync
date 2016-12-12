CREATE OR REPLACE VIEW v_ultimo_rid_insoluto (IdContratto,NumRata,DataScadenza,Importo)
AS
select IdContratto,NumRata,DataScadenza,Importo
from movimento m
where importo>90 and datascadenza<CURDATE() and idtipomovimento=165
and not exists (select 1 from movimento b where b.idcontratto=m.idcontratto and b.idmovimento>m.idmovimento and idtipomovimento=165 and importo>90)
