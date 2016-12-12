#
# Variante semplificata delle view v_importi_per_provvigioni_full, usata nella v_insoluti_positivi
#
CREATE OR REPLACE VIEW v_importi_per_positivita_group
AS
select v.IdContratto,v.DataFineAffido,
        GREATEST(SUM(ImpCapitaleAffidato),0) as ImpCapitaleAffidato,
        GREATEST(SUM(ImpDebitoTotale),0) AS ImpDebitoTotale,
        GREATEST(SUM(ImpPagatoTotale), 0) AS ImpPagatoTotale,
        MIN(v.DataInizioAffido) AS DataInizioAffido,
        SUM(IF(ImpCapitaleAffidato>5 AND NumRata!=0,1,0)) AS NumRate
from v_importi_per_positivita v
GROUP BY IdContratto,IdAgenzia,DataFineAffido;