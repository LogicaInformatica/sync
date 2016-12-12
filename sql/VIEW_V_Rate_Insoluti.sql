/**** 
	13-10-2011: modificato l'importo della rata, perché questa vista si usa nelle lettere 
                e nei mandati, in cui dovrebbe comparire il residuo da pagare per ogni
                rata, non tanto l'importo del capitale 
****/
create or replace view v_rate_insolute
AS
select IdContratto,
NumRata,
replace(replace(replace(format(IFNULL(ImpInsoluto,0),2),'.',';'),',','.'),';',',') AS ImpCapitaleDaPagare,
ImpDebitoIniziale,
IF(ImpDebitoIniziale>ImpInsoluto,ImpDebitoIniziale-ImpInsoluto,0) AS ImpPagato,
DATE_FORMAT(DataInsoluto,'%d/%m/%Y') AS DataScadenza
FROM insoluto
WHERE NumRata!=0 AND ImpInsoluto>0;
 