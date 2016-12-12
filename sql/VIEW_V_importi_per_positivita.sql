#
# Variante semplificata delle view v_importi_per_provvigioni, usata nella v_importi_per_positivita_group
#
create or replace view v_importi_per_positivita
as
select si.IdContratto,IdAgenzia,NumRata,
	    IF(IdAffidamento IS NOT NULL,ImpCapitaleDaPagare,0) as ImpCapitaleAffidato,
	    IF(ImpInsoluto>0,ImpInsoluto,0) AS ImpDebitoTotale,
    	GREATEST(ImpPagato-ImpIncassoImproprio,0) ImpPagatoTotale,
        DataFineAffido,DataInizioAffido
from storiainsoluto si
WHERE IdAgenzia IS NOT NULL AND (DataFineAffido>CURDATE()
OR DataFineAffido<=CURDATE() AND CodAzione!='REV')
UNION ALL
select i.IdContratto,IdAgenzia,i.NumRata,
  IF(IdAffidamento IS NOT NULL,ImpCapitaleAffidato,0) as ImpCapitaleAffidato,
  IF(i.ImpDebitoIniziale>0,i.ImpDebitoIniziale,0) AS ImpDebitoTotale,
  IF(i.ImpDebitoIniziale>i.ImpInsoluto,GREATEST(ImpDebitoIniziale-i.ImpInsoluto-ImpIncassoImproprio,0),0) AS ImpPagatoTotale,
  DataFineAffido,DataInizioAffido
from insoluto i JOIN contratto c ON i.idcontratto=c.idcontratto
where IdAgenzia IS NOT NULL and Not Exists (select 1 from storiainsoluto s where s.idcontratto=i.idcontratto
and s.numrata=i.numrata and s.datafineaffido=c.datafineaffido);