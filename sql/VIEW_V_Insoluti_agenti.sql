CREATE OR REPLACE VIEW v_insoluti_agenti
AS
SELECT c.IdContratto,c.IdAgenzia,c.IdAgente,DATE_FORMAT(c.DataInizioAffido,'%Y%m') AS MeseAffido,TitoloUfficio as Agenzia,
       IFNULL(u.Userid,'non assegnata') AS NomeAgente,c.ImpInsoluto,c.ImpPagato,
       c.ImpCapitale,DataFineAffido
FROM  contratto c
LEFT JOIN utente u ON u.IdUtente=c.IdAgente
LEFT JOIN reparto r ON r.IdReparto=c.IdAgenzia
WHERE ImpInsoluto>0
UNION ALL
SELECT i.IdContratto,i.IdAgenzia,i.IdAgente,DATE_FORMAT(i.DataInizioAffido,'%Y%m') AS MeseAffido,TitoloUfficio as Agenzia,
       IFNULL(u.Userid,'non assegnata') AS NomeAgente,i.ImpInsoluto,i.ImpPagato,
       i.ImpCapitaleDaPagare AS ImpCapitale,i.DataFineAffido
FROM storiainsoluto i
LEFT JOIN utente u ON u.IdUtente=i.IdAgente
LEFT JOIN reparto r ON r.IdReparto=i.IdAgenzia
WHERE i.ImpInsoluto>0 AND i.IdAgenzia IS NOT NULL AND
(i.DataFineAffido>CURDATE() OR i.DataFineAffido<=CURDATE() AND CodAzione!='REV');