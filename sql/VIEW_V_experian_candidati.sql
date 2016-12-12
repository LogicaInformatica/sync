CREATE OR REPLACE VIEW v_experian_candidati
AS
SELECT cl.IdCliente,CodCliente,IFNULL(RagioneSociale,Nominativo) AS Nominativo,
GROUP_CONCAT(DISTINCT CodContratto SEPARATOR ',') AS ListaPratiche,
GROUP_CONCAT(DISTINCT AbbrClasse SEPARATOR ',') AS AbbrClasse,
GROUP_CONCAT(
	DISTINCT CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END
    SEPARATOR ',') AS Agenzie,
SUM(ImpInsoluto) AS TotaleImpScadutoNonPagato,
MAX(c.DataFineAffido) as DataFineAffido,
COUNT(*) AS NumPratiche    
FROM cliente cl 
join contratto c on c.IdCliente=cl.IdCliente 
	AND ImpInsoluto>26 AND c.CodRegolaProvvigione IN ('06','20') ## FILTRO DA CAMBIARE SE CAMBIANO LE REGOLE DI INVIO
    AND DataFineAffido<CURDATE()+10
left join classificazione cz ON cz.IdClasse=c.IdClasse AND cz.FlagRecupero='Y'
left join reparto r on r.IdReparto = c.IdAgenzia
group by IdCliente
having count(cz.IdClasse)>0
UNION 
SELECT * FROM v_experian_queue;

select * from v_experian_candidati;

SELECT cl.IdCliente,CodCliente,IFNULL(RagioneSociale,Nominativo) AS Nominativo,
GROUP_CONCAT(DISTINCT CodContratto SEPARATOR ',') AS ListaPratiche,
GROUP_CONCAT(DISTINCT AbbrClasse SEPARATOR ',') AS AbbrClasse,
GROUP_CONCAT(
	DISTINCT CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END
    SEPARATOR ',') AS Agenzie,
SUM(ImpInsoluto) AS TotaleImpScadutoNonPagato,
MAX(c.DataFineAffido) as DataFineAffido,
COUNT(*) AS NumPratiche    
FROM cliente cl 
join contratto c on c.IdCliente=cl.IdCliente 
	AND ImpInsoluto>26 AND c.CodRegolaProvvigione IN ('06','20') ## FILTRO DA CAMBIARE SE CAMBIANO LE REGOLE DI INVIO
left join classificazione cz ON cz.IdClasse=c.IdClasse AND cz.FlagRecupero='Y'
left join reparto r on r.IdReparto = c.IdAgenzia
group by IdCliente
having count(cz.IdClasse)>0
