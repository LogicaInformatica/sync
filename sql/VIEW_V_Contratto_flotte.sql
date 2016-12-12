CREATE OR REPLACE VIEW v_contratto_flotte
AS
SELECT ct.IdContratto,
CASE WHEN cl.RagioneSociale != 'null' AND (ct.ImpCapitale + ct.ImpAltriAddebiti)>26 
	 THEN (SELECT SUM(ifnull(ImpCapitale,0)) + SUM(ifnull(ImpAltriAddebiti,0)) AS somma FROM contratto where IdCliente=ct.IdCliente) 
	 ELSE NULL END AS TotFlotta
FROM contratto ct
JOIN cliente cl ON ct.IdCliente=cl.IdCliente;


