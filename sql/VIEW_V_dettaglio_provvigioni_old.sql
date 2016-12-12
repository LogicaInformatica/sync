CREATE OR REPLACE VIEW v_dettaglio_provvigioni_old
AS
SELECT v.*,DATEDIFF(CURDATE(), DataRata) AS GiorniRitardo,CodContratto,CodContratto AS numPratica,c.IdCliente,TitoloUfficio AS Agenzia,IFNULL(Nominativo,RagioneSociale) AS cliente,
     CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE LEAST(100.,ROUND(v.ImpPagato*100./ImpCapitaleAffidato,2)) END AS PercCapitale,
     CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE ROUND(v.ImpPagato*100./ImpCapitaleAffidato,2) END AS PercCapitaleReale,
      DataUltimoPagamento,
      CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE LEAST(v.ImpPagato,ImpCapitaleAffidato) END AS ImpRiconosciuto,
      Userid as Operatore,IF(c.DataInizioAffido<=v.DataInizioAffido,c.DataFineAffido,v.DataFineAffido) AS DataFineAffidoContratto,
      IF(c.DataInizioAffido<=v.DataInizioAffido,c.DataInizioAffido,v.DataInizioAffido) AS DataInizioAffidoContratto
	  ,st.CodStatoRecupero,st.CodStatoRecupero AS stato
FROM v_importi_per_provvigioni_full v
JOIN reparto r ON r.IdReparto=v.IdAgenzia
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN statorecupero st ON st.IdStatoRecupero=c.IdStatoRecupero
JOIN cliente x ON x.IdCliente=c.IdCliente
LEFT JOIN utente u ON u.IdUtente=v.IdAgente;