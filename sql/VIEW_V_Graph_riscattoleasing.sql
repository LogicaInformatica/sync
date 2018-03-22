CREATE OR REPLACE VIEW v_graph_riscattoleasing
AS
SELECT srl.IdCategoriaRiscattoLeasing, crl.CategoriaRiscattoLeasing,
COUNT(srl.IdCategoriaRiscattoLeasing) NumCategoriaRiscattoLeasing,
sum(srl.ImpInsoluto) as TotaleImportoInsoluto,
srl.Lotto,
DATE_FORMAT(datamese,'%Y%m') as Mese
FROM statisticheriscattileasing srl
JOIN categoriariscattoleasing crl ON crl.IdCategoriaRiscattoLeasing = srl.IdCategoriaRiscattoLeasing
group by srl.IdCategoriaRiscattoLeasing, srl.datamese, srl.Lotto
order by srl.datamese;