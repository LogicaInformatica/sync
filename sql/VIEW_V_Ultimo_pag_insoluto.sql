CREATE OR REPLACE VIEW v_ultimo_pag_insoluto (IdContratto,NumRata,DataScadenza,Importo,IdTipoInsoluto)
AS
select i.IdContratto,i.NumRata,i.DataScadenza,i.Importo,i.idTipoInsoluto
from v_ultimo_insoluto i
LEFT JOIN movimento m ON i.idcontratto=m.idcontratto and i.numrata=m.numrata and -m.importo>=i.importo
WHERE NOT Exists (SELECT 1 FROM movimento x WHERE x.idcontratto=m.idcontratto AND x.numrata=m.numrata and x.importo=m.importo and x.idmovimento>m.idmovimento)

