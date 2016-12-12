CREATE OR REPLACE VIEW v_incassi_su_storiainsoluti_da_correggere
AS
select i.idstoriainsoluto,i.idcontratto,i.NumRata,i.ImpInsoluto,i.ImpInsoluto-i.ImpPagato AS Residuo,i.ImpPagato,a.dataini,a.datafin,
SUM(IF(m.importo>0,m.importo,0)) as addebiti,
SUM(IF(m.importo<0 and categoriamovimento='P',-m.importo,0)) as incassiPropri,
SUM(IF(m.importo<0 and IFNULL(categoriamovimento,'')!='P',-m.importo,0)) as incassiImpropri,
GREATEST(0,i.ImpPagato-SUM(IF(m.importo<0 and categoriamovimento='P',-m.importo,0))) AS Improprio,ImpIncassoImproprio
from storiainsoluto i
join assegnazione a on i.idcontratto=a.idcontratto and a.datafin=i.datafineaffido and a.datafin>=curdate()-INTERVAL 3 MONTH and a.idagenzia>0
LEFT join movimento m ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata
  AND (m.DataRegistrazione BETWEEN a.dataIni AND a.DataFin OR m.DataRegistrazione='2013-03-22' AND a.DataIni='2013-03-25')
LEFT JOIN tipomovimento t ON t.IdTipoMovimento=m.IdTipoMovimento
where CodAzione!='REV'
group by i.idstoriainsoluto,i.idcontratto,i.NumRata,i.ImpInsoluto,i.ImpPagato,a.dataini,a.datafin
HAVING ImpPagato>0 and ImpPagato>incassiPropri and i.ImpIncassoImproprio!=Improprio
order by i.idcontratto;