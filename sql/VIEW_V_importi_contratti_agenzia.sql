CREATE OR REPLACE VIEW v_importi_contratti_agenzia
AS
select IdContratto,Idagenzia,NumRata,DataScadenza,ImpCapitaleDaPagare as ImpRate,ImpInsoluto,ImpPagato,ImpInteressi,DataFineAffido,DataInizioAffido
from storiainsoluto si
WHERE Not Exists (select 1 from insoluto where idcontratto=si.idcontratto and numrata=si.numrata)
AND IdAgenzia IS NOT NULL AND DataFineAffido>CURDATE()
UNION ALL
select i.IdContratto,IdAgenzia,i.NumRata,i.DataInsoluto,i.ImpCapitaleAffidato as ImpRate,i.ImpDebitoIniziale,ImpDebitoIniziale-i.ImpInsoluto as ImpPagato,i.ImpInteressi,DataFineAffido,DataInizioAffido
from insoluto i,contratto c where i.idcontratto=c.idcontratto AND IdAgenzia IS NOT NULL;