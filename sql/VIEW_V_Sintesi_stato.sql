/* OBSOLETA (E INEFFICIENTE PERCHE' TEMPTABLE) */

CREATE OR REPLACE VIEW v_sintesi_stato (IdAgenzia,IdAgente,Lotto,DataFineAffido,NumInsoluti,CodStato,TitoloStato,
                                          ImpInsoluto,ImpPagato,ImpCapitale,
                                          PercTotale,PercCapitale,NumAzioni,DataUltimaAzione)
AS
SELECT ia.IdAgenzia,ia.IdAgente,Lotto,c.DataFineAffido,COUNT(*) AS NumInsoluti, /* in realt  il num. pratiche */
         CASE WHEN IdClasse=18 THEN '05'
            WHEN ia.ImpPagato>0 THEN '06'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN '01'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN '03'
            ELSE '04'
       END AS CodStato,
       CASE WHEN IdClasse=18 THEN 'Positive'
            WHEN ia.ImpPagato>0 THEN 'Con incasso parziale'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN 'Lavorate'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN 'Da lavorare - urgenti'
            ELSE 'Da lavorare'
       END AS TitoloStato,
       SUM(ia.ImpInsoluto),SUM(ia.ImpPagato),SUM(ia.ImpCapitale),
ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpInsoluto),2),ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpCapitale),2),
SUM(NumAzioni),MAX(DataUltimaAzione)
FROM contratto c
JOIN v_insoluti_agenti_group ia ON ia.IdContratto=c.IdContratto
LEFT JOIN v_azioni_fatte az ON az.IdContratto=ia.IdContratto AND az.IdUtente=ia.IdAgente
GROUP BY ia.IdAgenzia,ia.IdAgente,DataFineAffido,CodStato