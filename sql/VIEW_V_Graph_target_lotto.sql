CREATE OR REPLACE VIEW v_graph_target_lotto
AS
SELECT IF(p.DataFin<'2012-03-01' OR r.FasciaRecupero NOT LIKE 'I ESA%',r.FasciaRecupero,'I ESA') AS FasciaRecupero,r.TitoloRegolaProvvigione,
CASE WHEN r.DataFin='9999-12-31' OR rNew.IdRegolaProvvigione IS NULL OR p.TipoCalcolo='M' THEN concat(a.TitoloUfficio,' (',r.CodRegolaProvvigione,')')
     ELSE CONCAT(a.TitoloUfficio,' (',r.CodRegolaProvvigione,' old)') END AS Agenzia,t.gruppo,
valore AS TargetIPR,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPF,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleRealeIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPR,
CASE WHEN sum(NumAffidati)=0 THEN 0 ELSE ROUND(sum(NumIncassati)*100./sum(NumAffidati),2) END AS IPM,
SUM(NumAffidati) AS NumAffidati,sum(NumIncassati) as NumIncassati,
sum(ImpCapitaleAffidato) as ImpCapitaleAffidato,sum(ImpCapitaleIncassato) as ImpCapitaleIncassato,
p.DataFin as DataFineAffido,r.idregolaprovvigione,
CASE WHEN r.DataFin='9999-12-31' OR rNew.IdRegolaProvvigione IS NULL or p.TipoCalcolo='M' THEN r.CodRegolaProvvigione ELSE CONCAT(r.CodRegolaProvvigione,' old') END AS CodRegolaProvvigione,
t.Ordine,a.IdReparto,FY,t.DataFin AS DataFineFascia
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
JOIN reparto a ON a.IdReparto=p.IdReparto
JOIN target t ON r.FasciaRecupero=t.FasciaRecupero and DATE_FORMAT(p.datafin,'%Y%m') between (FY-1)*100+4 AND ENDFY*100+3 AND p.DataFin BETWEEN t.DataIni AND t.DataFin
LEFT JOIN regolaprovvigione rNew ON rNew.CodRegolaProvvigione=r.CodRegolaProvvigione AND rNew.DataFin>r.DataFin AND p.DataFin>=rNew.DataIni
group by t.ordine,CASE WHEN r.DataFin='9999-12-31' OR rNew.IdRegolaProvvigione IS NULL THEN r.CodRegolaProvvigione ELSE CONCAT(r.CodRegolaProvvigione,' old') END,DataFineAffido;
