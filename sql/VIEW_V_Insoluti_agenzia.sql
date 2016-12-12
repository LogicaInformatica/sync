CREATE OR REPLACE VIEW v_insoluti_agenzia
AS
SELECT i.IdContratto,r.IdReparto AS IdAgenzia,TitoloUfficio as Agenzia,
       i.ImpDebitoIniziale as ImpInsoluto,ImpDebitoIniziale-i.ImpInsoluto AS ImpPagato,
       i.ImpCapitaleAffidato AS ImpCapitale,DataFineAffido
FROM insoluto i
JOIN contratto c ON i.IdContratto=c.IdContratto
LEFT JOIN utente u ON u.IdUtente=c.IdAgente
LEFT JOIN reparto r ON r.IdReparto=u.IdReparto
WHERE ImpDebitoIniziale>0 and c.IdAgenzia IS NOT NULL
UNION ALL
SELECT i.IdContratto,i.IdAgenzia,TitoloUfficio as Agenzia,
       i.ImpInsoluto,i.ImpPagato,
       ImpCapitaleDaPagare AS ImpCapitale,i.DataFineAffido
FROM storiainsoluto i
JOIN contratto c ON i.IdContratto=c.IdContratto
LEFT JOIN utente u ON u.IdUtente=i.IdAgente
LEFT JOIN reparto r ON r.IdReparto=i.IdAgenzia
WHERE i.ImpInsoluto>0 AND i.IdAgenzia IS NOT NULL AND 
(i.DataFineAffido>CURDATE() AND Not Exists (select 1 from insoluto where idcontratto=i.idcontratto and numrata=i.numrata)
OR i.DataFineAffido<=CURDATE() AND CodAzione!='REV') 
