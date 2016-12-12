CREATE OR REPLACE VIEW v_ultimo_insoluto (IdContratto,NumRata,DataScadenza,Importo,IdTipoInsoluto)
AS
select r.IdContratto,r.NumRata,r.DataScadenza,r.Importo,m.idTipoInsoluto
from v_ultimo_rid_insoluto r
LEFT JOIN movimento m ON r.idcontratto=m.idcontratto and r.numrata=m.numrata and m.idtipomovimento=163
