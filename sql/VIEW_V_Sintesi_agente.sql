#
# 5/4/2014: aggiunta condizione per escludere gli affidi non ancora visibili alle agenzie
#
CREATE OR REPLACE VIEW v_sintesi_agente
AS
select a.idagente,u.NomeUtente as Agente,count(distinct a.idcontratto) as NumInsoluti,a.datafin as DataFineAffido,
COUNT(DISTINCT sr.idcontratto) as Trattati,
count(distinct a.idcontratto)-COUNT(DISTINCT sr.idcontratto) as DaTrattare,SUM(ImpTotaleAffidato) AS ImpTotale,
sum(ImpCapitaleAffidato) AS ImpCapitale,sum(i.ImpPagato) AS ImpPagato,SUM(ImpPagatoTotale) AS ImpPagatoTotale,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(LEAST(i.ImpPagato,i.ImpCapitaleAffidato))*100./SUM(i.ImpCapitaleAffidato),2) END AS PercCapitale,
COUNT(sr.IdStoriaRecupero) AS NumAzioni,MAX(DataEvento) AS DataUltimaAzione,
CONCAT('Fino al ',DATE_FORMAT(a.DataFin,'%d/%m')) AS Lotto,YEAR(a.DataFin) AS Anno,a.dataIni as DataInizioAffido
from assegnazione a
join contratto c on c.idContratto=a.IdContratto
JOIN statorecupero st ON st.IdStatoRecupero=c.IdStatoRecupero
LEFT JOIN utente u ON a.idAgente=u.IdUtente
LEFT JOIN storiarecupero sr ON sr.IdContratto=a.IdContratto and sr.idutente=a.idagente and sr.dataevento between a.dataini and a.datafin
JOIN v_importi_per_provvigioni_full i ON i.IdContratto=a.idcontratto AND i.datafineaffido=a.datafin
and  (
codstatorecupero NOT IN ('STR1','STR2','LEG') AND c.DataInizioAffido<=(SELECT CAST(ValoreParametro AS DATE) FROM parametrosistema WHERE CodParametro='DATA_ULT_VIS')
OR codstatorecupero IN ('STR1','STR2','LEG') AND c.DataInizioAffido<=(SELECT CAST(ValoreParametro AS DATE) FROM parametrosistema WHERE CodParametro='DATA_ULT_VIS_STR')
)
group by Anno,a.DataFin,idagente;