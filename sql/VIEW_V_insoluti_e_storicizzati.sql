#
# Usata nel calcolo del dettaglio insoluto (e in aggiorna campi derivati) per tener conto correttamente anche delle rate
# che sono andate in positivo durante il periodo (in particolare, nel calcolo delle spese di recupero).
#
CREATE OR REPLACE VIEW v_insoluti_e_storicizzati
AS
select IdContratto,ImpAltriAddebiti,ImpCapitaleAffidato,NumRata,ImpPagato,ImpCapitale,ImpDebitoIniziale,ImpSpeseRecupero,
ImpInsoluto,DataInsoluto,ImpInteressi,ImpIncassoImproprio
FROM insoluto
UNION ALL
select si.IdContratto,si.ImpAltriAddebiti,ImpCapitaleDaPagare,si.NumRata,si.ImpPagato,si.ImpCapitale,si.ImpInsoluto,si.ImpSpeseRecupero,
0,DataScadenza,ImpInteressi,ImpIncassoImproprio
FROM storiainsoluto si
#considera solo le positivizzazioni per vero incasso su rate non viaggianti, perché solo da quelle interessano le spese di recupero
# e solo quelle non duplicate di rige in insoluto (caso di passaggio neg-pos-neg)
WHERE si.CodAzione='POS' AND si.ImpIncassoImproprio=0 AND IdAffidamento>0 AND (si.ImpSpeseRecupero>0 OR ImpInteressi>0)
AND DataFineAffido>=CURDATE()
AND NOT EXISTS (SELECT 1 FROM insoluto i WHERE si.idContratto=i.IdContratto AND si.NumRata=i.NumRata);