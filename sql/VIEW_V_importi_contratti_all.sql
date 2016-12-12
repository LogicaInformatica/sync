CREATE OR REPLACE VIEW v_importi_contratti_all
AS
select IdContratto,Idagenzia,IdAgente,NumRata,DataScadenza,ImpCapitaleDaPagare as ImpRate,ImpInsoluto,ImpPagato,ImpInteressi,DataFineAffido,DataInizioAffido
from storiainsoluto si
WHERE IdAgenzia IS NOT NULL AND (DataFineAffido>CURDATE() AND Not Exists (select 1 from insoluto where idcontratto=si.idcontratto and numrata=si.numrata)
OR DataFineAffido<=CURDATE() AND CodAzione!='REV') 
UNION ALL
select i.IdContratto,IdAgenzia,IdAgente,i.NumRata,i.DataInsoluto,i.ImpCapitaleAffidato as ImpRate,i.ImpDebitoIniziale,ImpDebitoIniziale-i.ImpInsoluto as ImpPagato,i.ImpInteressi,DataFineAffido,DataInizioAffido
from insoluto i,contratto c where i.idcontratto=c.idcontratto AND IdAgenzia IS NOT NULL;