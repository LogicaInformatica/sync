CREATE OR REPLACE VIEW v_insoluti_agenti_group
AS
SELECT IdContratto,IdAgenzia,IdAgente,Agenzia,NomeAgente,SUM(ImpInsoluto) AS ImpInsoluto,SUM(ImpPagato) AS ImpPagato,
       SUM(ImpCapitale) AS ImpCapitale,sum(case when ImpCapitale>0 AND ImpInsoluto>=10 then 1 else 0 end) AS NumInsoluti,
       CONCAT('Fino al ',DATE_FORMAT(DataFineAffido,'%d/%m')) AS Lotto, DataFineAffido
FROM v_insoluti_agenti ia
GROUP BY IdContratto,IdAgenzia,IdAgente,DataFineAffido,Agenzia,NomeAgente
