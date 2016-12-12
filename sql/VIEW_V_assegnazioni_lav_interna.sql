create or replace view v_assegnazioni_lav_interna
as
select a.IdContratto,a.DataIni as DataInizioAffido,a.DataFin AS DataFineAffido,si.ImpInsoluto AS Debito,si.ImpPagato AS pagato
FROM assegnazione a
LEFT JOIN storiainsoluto si ON a.IdContratto=si.IdContratto AND si.DataInizioAffido=a.DataIni
WHERE a.IdAgenzia IS NULL
UNION ALL
select a.IdContratto,a.DataIni,a.DataFin,ImpDebitoIniziale,ImpDebitoIniziale-ImpInsoluto
FROM assegnazione a
LEFT JOIN insoluto i ON a.IdContratto=i.IdContratto AND a.DataFin>=CURDATE() AND i.NumRata NOT IN (
  SELECT NumRata FROM storiainsoluto s where s.idcontratto=i.idcontratto and s.numrata=i.numrata and s.DataInizioAffido=a.DataIni)
WHERE a.IdAgenzia IS NULL
;