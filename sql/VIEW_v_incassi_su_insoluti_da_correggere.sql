CREATE OR REPLACE VIEW v_incassi_su_insoluti_da_correggere
AS
select i.idinsoluto,i.idcontratto,i.NumRata,i.ImpDebitoIniziale,i.ImpInsoluto,i.ImpDebitoIniziale-i.ImpInsoluto as ImpPagato,a.dataini,a.datafin,
SUM(IF(m.importo>0,m.importo,0)) as addebiti,
SUM(IF(m.importo<0 and categoriamovimento='P',-m.importo,0)) as incassiPropri,
SUM(IF(m.importo<0 and IFNULL(categoriamovimento,'')!='P',-m.importo,0)) as incassiImpropri,
GREATEST(0,i.ImpDebitoIniziale-i.ImpInsoluto-SUM(IF(m.importo<0 and categoriamovimento='P',-m.importo,0))) AS Improprio,
ImpIncassoImproprio
from insoluto i
## NB: condizione su datafin serve a escludere gli affidi legali fuori tempo, per i quali verrebbero dati come
## impropri tutti gli incassi fuori periodo. La condizione è fatta però in modo che includa tutte le assegnazioni attive
## ma anche quelle appena processate per il rientro di fine periodo
join assegnazione a on i.idcontratto=a.idcontratto and a.datafin>=curdate()-INTERVAL (1+2*(weekday(a.dataini)=0)) DAY and a.idAgenzia>0
LEFT join movimento m ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata AND (m.DataRegistrazione BETWEEN a.dataIni-INTERVAL (1+2*(weekday(a.dataini)=0)) DAY AND a.DataFin OR m.DataRegistrazione='2013-03-22' AND a.DataIni='2013-03-25')
LEFT JOIN tipomovimento t ON t.IdTipoMovimento=m.IdTipoMovimento
group by i.idinsoluto,i.idcontratto,i.NumRata,i.ImpDebitoIniziale,i.ImpInsoluto,a.dataini,a.datafin
HAVING ImpPagato>0 and ImpPagato>incassiPropri and i.ImpIncassoImproprio!=Improprio
order by i.idcontratto
;
