CREATE OR REPLACE VIEW v_importi_aggregati_lotto
AS
select IdContratto,IdAgenzia,sum(case when ImpInsoluto < 5 then 0 else 1 end) AS NumInsoluti,MIN(NumRata) AS NumRata,MIN(DataScadenza) AS DataScadenza,
       SUM(ImpRate) AS ImpRate,SUM(ImpInsoluto) AS ImpInsoluto,SUM(ImpPagato) AS ImpPagato,SUM(ImpInteressi) AS ImpInteressi,
       CONCAT('Fino al ',DATE_FORMAT(DataFineAffido,'%d/%m')) AS Lotto, DataFineAffido
from v_importi_contratti_all
GROUP BY IdContratto,IdAgenzia,DataFineAffido;



