CREATE OR REPLACE VIEW v_importi_aggregati
AS
select IdContratto,sum(case when ImpInsoluto < 5 then 0 else 1 end) AS NumInsoluti,MIN(NumRata) AS NumRata,MIN(DataScadenza) AS DataScadenza,
       SUM(ImpRate) AS ImpRate,SUM(ImpInsoluto) AS ImpInsoluto,SUM(ImpPagato) AS ImpPagato,SUM(ImpInteressi) AS ImpInteressi,
       MAX(DataFineAffido) AS DataFineAffido,MIN(DataInizioAffido) AS DataInizioAffido
from v_importi_contratti
GROUP BY IdContratto