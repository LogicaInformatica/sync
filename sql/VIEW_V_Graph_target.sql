CREATE OR REPLACE VIEW v_graph_target (FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,IPR,IPF,Mese,idregolaprovvigione,gruppo)
AS
SELECT replace(CONVERT(FasciaRecupero USING utf8), _UTF8'°',  _UTF8'&deg;'),TitoloRegolaProvvigione,Agenzia,TargetIPR,
# 2018-08-31 cambio metodo calcolo media ponderata
# ROUND(SUM(IPR*NumAffidati)/SUM(NumAffidati),2) AS IPR,
# ROUND(SUM(IPF*NumAffidati)/SUM(NumAffidati),2) AS IPF,
ROUND(SUM(IPR*ImpCapitaleAffidato)/SUM(ImpCapitaleAffidato),2) AS IPR,
ROUND(SUM(IPF*ImpCapitaleAffidato)/SUM(ImpCapitaleAffidato),2) AS IPF,
DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,idregolaprovvigione,gruppo
FROM v_graph_target_lotto
group by Ordine,CodRegolaProvvigione,mese
order by Ordine,CodRegolaProvvigione;
