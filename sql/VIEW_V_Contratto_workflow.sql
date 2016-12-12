CREATE OR REPLACE VIEW v_contratto_workflow
AS
SELECT ct.*,po.IdFamiglia,fp.IdFamigliaParent,cl.IdTipoCliente,
(YEAR(CURDATE())-YEAR(DataPrimaScadenza))*12+MONTH(CURDATE())-MONTH(DataPrimaScadenza)-NumInsoluti AS RatePagate,
impPap
FROM contratto ct
JOIN cliente cl          ON ct.IdCliente=cl.IdCliente
JOIN prodotto po         ON ct.IdProdotto=po.IdProdotto
JOIN famigliaprodotto fp ON po.IdFamiglia=fp.IdFamiglia 
LEFT JOIN writeoff wo    ON wo.idContratto=ct.idContratto
;