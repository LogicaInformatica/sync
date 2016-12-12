#
# Controlli affidi non corrispondenti al numero rate
#
create or replace view v_check_02
as
select DISTINCT c.IdContratto,CodContratto,sr.descrevento AS Motivo,ImpInsoluto,NumInsoluti,concat(CodClasse,' ',TitoloClasse) AS Classe,TitoloUfficio AS Agenzia,
  CodRegolaProvvigione,c.idclasse
from contratto c
join assegnazione a ON a.IdContratto=c.IdContratto and a.datafin=c.datafineaffido
join reparto r on r.idreparto=c.idagenzia
join classificazione cl on a.idclasse=cl.idclasse
left join storiarecupero sr ON sr.idcontratto=c.idcontratto AND sr.dataevento>curdate() and 
	(sr.descrevento like '%forzato%stessa%agenzia%' OR sr.descrevento like 'Applicata%forzatura%')
where datainizioaffido=CURDATE()
and NumInsoluti NOT BETWEEN CASE WHEN CodRegolaProvvigione IN ('I1','I7','P2','P4','LR','1C','1S','CL') THEN 1
         WHEN CodRegolaProvvigione IN ('03','06','L4','20','29','L5') THEN 2
         WHEN CodRegolaProvvigione IN ('S2','18','28') THEN 3
         WHEN CodRegolaProvvigione = '24' THEN 4
         WHEN CodRegolaProvvigione IN ('L2','L3') THEN 3
         WHEN CodRegolaProvvigione = '27' THEN 5
         ELSE 2 END
AND
CASE WHEN CodRegolaProvvigione IN ('I1','I7','P2','P4','LR','1C','1S','CL') THEN 1
         WHEN CodRegolaProvvigione IN ('03','06','L4','20','29','L5') THEN 2
         WHEN CodRegolaProvvigione IN ('S2','18','28') THEN 3
         WHEN CodRegolaProvvigione = '24' THEN 4
         WHEN CodRegolaProvvigione IN ('L2','L3') THEN 999
         WHEN CodRegolaProvvigione = '27' THEN 999
         ELSE 999 END
order by codcontratto;

            