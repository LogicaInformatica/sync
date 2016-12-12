CREATE OR REPLACE VIEW v_importi_contratti
AS
select IdContratto,NumRata,DataScadenza,ImpCapitaleDaPagare as ImpRate,ImpInsoluto,ImpPagato,ImpInteressi,DataFineAffido,DataInizioAffido
from storiainsoluto si WHERE Not Exists (select 1 from insoluto where idcontratto=si.idcontratto and numrata=si.numrata)
UNION ALL
select i.IdContratto,i.NumRata,i.DataInsoluto,i.ImpCapitaleAffidato as ImpRate,i.ImpDebitoIniziale,ImpDebitoIniziale-i.ImpInsoluto as ImpPagato,i.ImpInteressi,DataFineAffido,DataInizioAffido
from insoluto i,contratto c where i.idcontratto=c.idcontratto