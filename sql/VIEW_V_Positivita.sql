CREATE OR REPLACE VIEW v_positivita
AS
select IdContratto,IdAgenzia,IdAgente,min(NumRata) AS NumRata,count(*) AS NumInsoluti,
max(DATEDIFF(CURDATE(), DataScadenza)) AS Giorni,
sum(ImpCapitale) as ImpCapitale,sum(ImpInsoluto) as ImpInsoluto,sum(ImpPagato) as ImpPagato,
sum(ImpInteressi) AS ImpInteressi,max(DataFineAffido) as DataFineAffido,min(DataInizioAffido) AS DataInizioAffido,Min(DataScadenza) AS DataScadenza
from storiainsoluto WHERE CodAzione NOT IN ('REV','RIE') AND DataFineAffido>=CURDATE()
group by IdContratto,IdAgenzia;
