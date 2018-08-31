CREATE OR REPLACE VIEW v_geography_fy
AS
select idArea,Area,Agenzia,
# modificato 18/4/2013, per passare i grafici STR/LEG da mensili a per-lotto
#YEAR( IF (TipoFascia=1,
#          DataFineAffido,
#		  LEAST(DataFineAffido, LAST_DAY(CURDATE()))
#         )
#		  +INTERVAL 8 MONTH) as Anno,
YEAR(LAST_DAY(DataFineAffido)+INTERVAL 9 MONTH) as Anno,
SUM(NumPratiche) AS NumPratiche,
# 2018-08-31: cambiata la media ponderata
#ROUND(SUM(IPR*NumPratiche)/sum(NumPratiche),2) AS IPR,
ROUND(SUM(IPR*ImpCapitaleAffidato)/sum(ImpCapitaleAffidato),2) AS IPR,
SUM(ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(ImpCapitalePagato) AS ImpCapitalePagato,idagenzia,fasciarecupero,tipoFascia
from v_geography_lotto
group by idarea,tipoFascia,CodRegolaProvvigione,Anno;

select sum(NumPratiche) from v_geography_fy where agenzia='NCP (31)' and anno=2017