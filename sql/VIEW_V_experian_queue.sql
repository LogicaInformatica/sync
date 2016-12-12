CREATE OR REPLACE VIEW v_experian_queue
AS
SELECT q.IdCliente,CodCliente,IFNULL(RagioneSociale,Nominativo) AS Nominativo,
GROUP_CONCAT(DISTINCT CodContratto SEPARATOR ',') AS ListaPratiche,
GROUP_CONCAT(DISTINCT AbbrClasse SEPARATOR ',') AS AbbrClasse,
GROUP_CONCAT(
	DISTINCT CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END
    SEPARATOR ',') AS Agenzie,
SUM(ImpInsoluto) AS TotaleImpScadutoNonPagato,
MAX(c.DataFineAffido) as DataFineAffido,
COUNT(*) AS NumPratiche    
FROM experianqueue q
JOIN cliente cl ON cl.IdCliente=q.IdCliente
left join contratto c on c.IdCliente=cl.IdCliente 
left join classificazione cz ON cz.IdClasse=c.IdClasse ##AND cz.FlagRecupero='Y'
left join reparto r on r.IdReparto = c.IdAgenzia

group by IdCliente;

select * from v_experian_queue;
