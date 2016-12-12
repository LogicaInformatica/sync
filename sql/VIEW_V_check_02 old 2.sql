#
# Controlli affidi non corrispondenti al numero giorni di ritardo
#
create or replace view v_check_02
as
select DISTINCT c.IdContratto,CodContratto,sr.descrevento AS Motivo,ImpInsoluto,NumInsoluti,concat(CodClasse,' ',TitoloClasse) AS Classe,TitoloUfficio AS Agenzia,
  CodRegolaProvvigione,c.idclasse,DATEDIFF(CURDATE(), DataRata) as GiorniRitardo
from contratto c
join assegnazione a ON a.IdContratto=c.IdContratto and a.datafin=c.datafineaffido
join reparto r on r.idreparto=c.idagenzia
join classificazione cl on a.idclasse=cl.idclasse
left join storiarecupero sr ON sr.idcontratto=c.idcontratto AND sr.dataevento>curdate() and 
	(sr.descrevento like '%forzato%stessa%agenzia%' OR sr.descrevento like 'Applicata%forzatura%')
where datainizioaffido=CURDATE()
and DATEDIFF(CURDATE(), DataRata) NOT BETWEEN CASE WHEN CodRegolaProvvigione IN ('I1','I7','1C','1S') THEN 0
         WHEN CodRegolaProvvigione IN ('P2','P4') THEN 31
         WHEN CodRegolaProvvigione IN ('20','06','29') THEN 61
         WHEN CodRegolaProvvigione IN ('S2','28') THEN 91
         WHEN CodRegolaProvvigione = '24' THEN 121
         WHEN CodRegolaProvvigione IN ('L2','L3') THEN 91
         WHEN CodRegolaProvvigione = '27' THEN 151
         WHEN CodRegolaProvvigione = 'F1' THEN 31
         WHEN CodRegolaProvvigione = 'F2' THEN 91
         ELSE 0 END
AND
CASE WHEN CodRegolaProvvigione IN ('I1','I7','1C','1S') THEN 30
         WHEN CodRegolaProvvigione IN ('P2','P4') THEN 60
         WHEN CodRegolaProvvigione IN ('20','06','29') THEN 90
         WHEN CodRegolaProvvigione IN ('S2','28') THEN 120
         WHEN CodRegolaProvvigione = '24' THEN 150
         WHEN CodRegolaProvvigione IN ('L2','L3') THEN 99999
         WHEN CodRegolaProvvigione = '27' THEN 99999
         WHEN CodRegolaProvvigione = 'F1' THEN 90
         WHEN CodRegolaProvvigione = 'F2' THEN 99999
         ELSE 999 END
order by codcontratto;

            