CREATE OR REPLACE VIEW v_sintesi_lavorazioni_interne
AS
SELECT m.id,m.mese,m.FineMese AS DataFineAffido,InizioMese,FineMese,replace(m.mese,' ','/') AS Lotto,SUM(IF(a.DataInizioAffido<=m.InizioMese,1,0)) AS numPraticheIn,
  SUM(IF(a.DataInizioAffido<=m.InizioMese,IFNULL(Debito,0)+IFNULL(Debito,0),0)) AS debTotale,
  SUM(IF(a.DataInizioAffido<=m.InizioMese,IFNULL(Pagato,0)+IFNULL(Pagato,0),0)) AS totRecuperato,
  IF(SUM(IF(a.DataInizioAffido<=m.InizioMese,IFNULL(Debito,0)+IFNULL(Debito,0),0))>0,
     ROUND(SUM(IF(a.DataInizioAffido<=m.InizioMese,IFNULL(Pagato,0)+IFNULL(Pagato,0),0))*100
          /SUM(IF(a.DataInizioAffido<=m.InizioMese,IFNULL(Debito,0)+IFNULL(Debito,0),0)),2)
     ,NULL) AS ipr,
  SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,1,0)) AS numRatViaggio,
  SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,IFNULL(Debito,0)+IFNULL(Debito,0),0)) AS debTotaleViaggio,
  SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,IFNULL(Pagato,0)+IFNULL(Pagato,0),0)) AS totRecuperatoViaggio,
  IF(SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,IFNULL(Debito,0)+IFNULL(Debito,0),0))>0,
     ROUND(SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,IFNULL(Pagato,0)+IFNULL(Pagato,0),0))*100
          /SUM(IF(a.DataInizioAffido>m.InizioMese AND DataInizioAffido<=DataFineMese,IFNULL(Debito,0)+IFNULL(Debito,0),0)),2)
     ,NULL) AS iprViaggio
FROM v_mesi_affido m
LEFT JOIN v_assegnazioni_lav_interna_group a ON a.DataInizioAffido<=m.FineMese AND a.DataFineAffido>=m.InizioMese
GROUP BY m.Id;
