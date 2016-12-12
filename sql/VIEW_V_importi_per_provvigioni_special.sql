#
# Variante di v_importi_per_provvigioni_full, che calcola i dati per gli affidi STR/LEG su base vero lotto
# invece che sul mese di assegnazione con chiusura mensile
#
CREATE OR REPLACE VIEW v_importi_per_provvigioni_special
AS
select IdContratto,IdAgenzia,IdAgente,DataInizioAffidoContratto,DataFineAffidoContratto,
        ImpCapitaleAffidato,ImpTotaleAffidato,ImpPagato,ImpPagatoTotale,ImpInteressi,ImpSpese,
        LAST_DAY(DataFineAffidoContratto) AS DataFineAffido,DataInizioAffidoContratto AS DataInizioAffido,
        IdClasse,NumRate,RateViaggiantiIncassate,IdRegolaProvvigione
from  v_importi_per_provvigioni_group;
