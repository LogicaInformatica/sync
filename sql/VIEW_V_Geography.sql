CREATE OR REPLACE VIEW v_geography
AS
select idArea,Area,Agenzia,
# modificato 18/4/2013, per passare STR/LEG da per-mese a per-lotto
#         DATE_FORMAT(IF (TipoFascia=1,DataFineAffido,
# 		  LEAST(DataFineAffido, LAST_DAY(CURDATE()))
#        ) ,'%Y%m') as Mese,
 	DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,
	SUM(NumPratiche) AS NumPratiche,
	YEAR(DataFineAffido+INTERVAL 9 MONTH) as Anno,
	ROUND(SUM(IPR*NumPratiche)/sum(NumPratiche),2) AS IPR,
	SUM(ImpCapitaleAffidato) AS ImpCapitaleAffidato,
	SUM(ImpCapitalePagato) AS ImpCapitalePagato,idagenzia,fasciarecupero,TipoFascia
from v_geography_lotto
group by idarea,TipoFascia,CodRegolaProvvigione,Mese;


