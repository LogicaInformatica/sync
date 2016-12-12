CREATE OR REPLACE VIEW v_stato_lavorazione (IdContratto,CodStato,TitoloStato)
AS
SELECT c.IdContratto,
       CASE WHEN IdClasse=18 THEN '05'
            WHEN ImpPagato>0 THEN '06'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN '01'
    /*        WHEN DataInizioAffido>CURDATE() - INTERVAL 3 DAY THEN '02' */
            WHEN DataFineAffido<CURDATE() + INTERVAL 3 DAY THEN '03'
            ELSE '04'
       END AS CodStato,
       CASE WHEN IdClasse=18 THEN 'Positive'
            WHEN ImpPagato>0 THEN 'Con incasso parziale'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN 'Lavorate'
    /*      WHEN DataInizioAffido>CURDATE() - INTERVAL 3 DAY THEN 'Da lavorare - nuove' */
            WHEN DataFineAffido<CURDATE() + INTERVAL 3 DAY THEN 'Da lavorare - urgenti'
            ELSE 'Da lavorare'
       END AS TitoloStato
FROM contratto c;