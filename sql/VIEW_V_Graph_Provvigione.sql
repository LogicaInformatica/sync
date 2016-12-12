CREATE OR REPLACE VIEW v_graph_provvigione
AS
SELECT replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero,DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,IdReparto,CodRegolaProvvigione,
Agenzia,
SUM(NumAffidati) AS NumAffidati,sum(NumIncassati) as NumIncassati,
sum(ImpCapitaleAffidato) as ImpCapitaleAffidato,sum(ImpCapitaleIncassato) as ImpCapitaleIncassato,
concat('€ ',replace(format(sum(impCapitaleAffidato),0),',','.'),'\n  (',sum(NumAffidati),')') AS LabelAffidato,
concat('€ ',replace(format(sum(ImpCapitaleIncassato),0),',','.'),'\n  (',sum(NumIncassati),')') AS LabelIncassato,
ROUND(SUM(IPR*NumAffidati)/SUM(NumAffidati),2) AS IPR,ROUND(SUM(IPM*NumAffidati)/SUM(NumAffidati),2) AS IPM,gruppo,
IF ( DataFineFascia >= 	 # esclude le fasce che non erano più valide alla fine del fiscal year precedente
	CAST(CONCAT(YEAR(CURDATE())-IF(MONTH(CURDATE())>3,1,0),'-03-31') AS DATE) # espressione che indica il fine fiscal year precedente
   ,'N','Y') AS FasciaVecchia
FROM v_graph_target_lotto
group by 1,2,3,4;
