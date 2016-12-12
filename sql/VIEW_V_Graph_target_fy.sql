CREATE OR REPLACE VIEW v_graph_target_fy
AS
#SELECT FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,ROUND(SUM(IPR)/COUNT(*),2) AS IPR,ROUND(SUM(IPF)/COUNT(*),2) AS IPF,
#FY
# dal 25/1/2012
SELECT replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,
ROUND(SUM(IPR*NumAffidati)/SUM(NumAffidati),2) AS IPR,
ROUND(SUM(IPF*NumAffidati)/SUM(NumAffidati),2) AS IPF,
FY, gruppo
FROM v_graph_target_lotto
group by FY,Ordine,CodRegolaProvvigione
order by FY,Ordine,CodRegolaProvvigione;


