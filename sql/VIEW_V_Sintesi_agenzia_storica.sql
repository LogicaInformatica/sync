CREATE OR REPLACE VIEW v_sintesi_agenzia_storica
AS
select a.IdAgenzia,a.IdAgente,IFNULL(u.NomeUtente,'[non assegnate]') as Agente,count(distinct a.idcontratto) as NumInsoluti,
COUNT(DISTINCT sr.idcontratto) as Trattati,
count(distinct a.idcontratto)-COUNT(DISTINCT sr.idcontratto) as DaTrattare,SUM(ImpTotaleAffidato) AS ImpTotale,
sum(ImpCapitaleAffidato) AS ImpCapitale,sum(ImpPagato) AS ImpPagato,SUM(ImpPagatoTotale) AS ImpPagatoTotale,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(LEAST(i.ImpPagato,i.ImpCapitaleAffidato))*100./SUM(i.ImpCapitaleAffidato),2) END AS PercCapitale,
COUNT(sr.IdStoriaRecupero) AS NumAzioni,MAX(DataEvento) AS DataUltimaAzione,
YEAR(a.DataFin) AS Anno,a.dataFin AS DataFineAffido,a.dataIni as DataInizioAffido
from assegnazione a
LEFT JOIN utente u ON a.idAgente=u.IdUtente
LEFT JOIN storiarecupero sr ON sr.IdContratto=a.IdContratto and sr.idutente=a.idagente and sr.dataevento between a.dataini and a.datafin
JOIN v_importi_per_provvigioni_full i ON i.IdContratto=a.idcontratto AND datafineaffido=a.datafin
group by Anno,idagenzia,Agente;