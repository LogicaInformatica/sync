CREATE OR REPLACE VIEW v_insoluti_agenzia_group
AS
SELECT IdContratto,IdAgenzia,Agenzia,SUM(ImpInsoluto) AS ImpInsoluto,SUM(ImpPagato) AS ImpPagato,
       SUM(ImpCapitale) AS ImpCapitale,COUNT(*) AS NumInsoluti
FROM v_insoluti_agenzia
GROUP BY IdContratto,IdAgenzia,Agenzia
