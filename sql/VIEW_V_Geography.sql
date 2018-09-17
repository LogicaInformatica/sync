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
        # 2018-08-31: cambiata la media ponderata
	#ROUND(SUM(IPR*NumPratiche)/sum(NumPratiche),2) AS IPR,
	ROUND(SUM(IPR*ImpCapitaleAffidato)/sum(ImpCapitaleAffidato),2) AS IPR,
	SUM(ImpCapitaleAffidato) AS ImpCapitaleAffidato,
	SUM(ImpCapitalePagato) AS ImpCapitalePagato,idagenzia,fasciarecupero,
        MAX(TipoFascia) AS TipoFascia # per fascia 0,1 produce 1
from v_geography_lotto
group by idarea,IF(TipoFascia=0,1,TipoFascia),CodRegolaProvvigione,Mese;
# in pratica raggruppa i lotti di un mese, calcolando un IPR come media ponderata dei tre IPR
# (IPR di base è il rapporto tra capitale incassato e affidato)


