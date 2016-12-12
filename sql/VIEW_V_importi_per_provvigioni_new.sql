create or replace view v_importi_per_provvigioni_new
as
select si.IdContratto,si.NumRata,IdAgenzia,
	    IF(IdAffidamento IS NOT NULL,ImpCapitaleDaPagare,0) as ImpCapitaleAffidato,
	    IF(IdAffidamento IS NOT NULL AND ImpInsoluto>0,ImpInsoluto,0) AS ImpTotaleAffidato,
		IF(IdAffidamento IS NOT NULL,ImpPagato,0) AS ImpPagato, # totale pagato su rate in affido
		ImpPagato AS ImpPagatoTotale, #totale pagato incluse rate viaggianti
    IF(IdAffidamento IS NOT NULL,GREATEST(LEAST(ImpPagato,ImpCapitaleDaPagare),0),0) AS ImpCapitaleAffidatoPagato,
    GREATEST(LEAST(ImpPagato,ImpCapitaleDaPagare),0) AS ImpCapitaleTotalePagato,
		IF(IdAffidamento IS NULL AND NumRata!=0 AND ImpCapitaleDaPagare>0 AND ImpPagato>=ImpCapitaleDaPagare,1,0) AS RataViaggianteIncassata, #aggiunta 27/12/2011
        DataFineAffido,DataInizioAffido
from storiainsoluto si
WHERE IdAgenzia IS NOT NULL AND (DataFineAffido>CURDATE()
#non serve più escludere quelli che stanno su Insoluto, ma bisogna fare il rovescio, considerare quelle di Insoluto
#solo se non stanno su storiainsoluto
#AND Not Exists (select 1 from insoluto where idcontratto=si.idcontratto and numrata=si.numrata)
OR DataFineAffido<=CURDATE() AND CodAzione!='REV')
UNION ALL
select i.IdContratto,i.NumRata,IdAgenzia,
  IF(IdAffidamento IS NOT NULL,ImpCapitaleAffidato,0) as ImpCapitaleAffidato,
  IF(IdAffidamento IS NOT NULL,i.ImpDebitoIniziale,0) AS ImpTotaleAffidato,
  IF(IdAffidamento IS NOT NULL AND ImpDebitoIniziale>i.ImpInsoluto,ImpDebitoIniziale-i.ImpInsoluto,0) as ImpPagato,
  IF(i.ImpDebitoIniziale>i.ImpInsoluto,i.ImpDebitoIniziale-i.ImpInsoluto,0) as ImpPagatoTotale,
  IF(IdAffidamento IS NOT NULL AND ImpDebitoIniziale>i.ImpInsoluto,GREATEST(LEAST(ImpDebitoIniziale-i.ImpInsoluto,ImpCapitaleAffidato),0),0) as ImpCapitaleAffidatoPagato,
  IF(i.ImpDebitoIniziale>i.ImpInsoluto,GREATEST(LEAST(ImpDebitoIniziale-i.ImpInsoluto,ImpCapitaleAffidato),0),0) AS ImpCapitaleTotalePagato,
  IF(IdAffidamento IS NULL AND i.NumRata!=0 AND ImpCapitaleAffidato>0 AND i.ImpCapitale<=i.ImpPagato,1,0) AS RataViaggianteIncassata, #aggiunta 27/12/2011
  DataFineAffido,DataInizioAffido
from insoluto i JOIN contratto c ON i.idcontratto=c.idcontratto
where IdAgenzia IS NOT NULL
and Not Exists (select 1 from storiainsoluto s where s.idcontratto=i.idcontratto and s.numrata=i.numrata and s.datafineaffido=c.datafineaffido)