CREATE OR REPLACE VIEW v_dettaglio_provvigioni_storico
AS
SELECT d.IdProvvigione,d.IdContratto,d.IdAgenzia,d.IdAgente,
	   d.ImpCapitaleAffidato + IFNULL(mp.DiffCapitaleAffidato,0) 	AS ImpCapitaleAffidato,
	   d.ImpTotaleAffidato   + IFNULL(mp.DiffTotaleAffidato,0) 		AS ImpTotaleAffidato,
	   d.ImpPagato           + IFNULL(mp.DiffPagato,0) 				AS ImpPagato,
	   d.ImpPagatoTotale     + IFNULL(mp.DiffPagatoTotale,0) 		AS ImpPagatoTotale,
	   IFNULL(d.ImpInteressi,0) + IFNULL(mp.DiffInteressi,0) 			AS ImpInteressi, 
	   IFNULL(d.ImpSpese,0)  + IFNULL(mp.DiffSpeseRecupero,0)       AS ImpSpese,
       NumRateAffidate       - IFNULL(NumRateCancellate,0) 			AS NumRate,
       NumRateViaggianti     + IFNULL(mp.DiffRataViaggiante,0) 		AS RateViaggiantiIncassate,
	   CASE WHEN d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0)=0 THEN 0 
		    ELSE LEAST(100.0,ROUND((d.ImpPagato+IFNULL(mp.DiffPagato,0))*100.0/(d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0)),2)) 
	   END AS PercCapitale,
       CASE WHEN d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0)=0 THEN 0 
            ELSE ROUND((d.ImpPagato+IFNULL(mp.DiffPagato,0))*100.0/(d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0)),2)
	   END AS PercCapitaleReale,
	   DATEDIFF(CURDATE(), DataRata) AS GiorniRitardo,CodContratto,CodContratto AS numPratica,c.IdCliente,
	   TitoloUfficio AS Agenzia,IFNULL(Nominativo,RagioneSociale) AS cliente,
       Userid as Operatore,DataUltimoPagamento,st.CodStatoRecupero,st.CodStatoRecupero AS stato,
       CASE WHEN d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0)=0 THEN 0 
			ELSE LEAST((d.ImpPagato+IFNULL(mp.DiffPagato,0)),(d.ImpCapitaleAffidato+IFNULL(mp.DiffCapitaleAffidato,0))) 
       END AS ImpRiconosciuto,
       rin.TitoloStatoRinegoziazione AS StatoRinegoziazione,'DCS_dettagliopratica' AS FormDettaglio,
       IFNULL(mp.NumModifiche,0) AS NumModifiche,d.DataFineAffido,d.DataFineAffidoContratto,d.DataInizioAffido,d.DataInizioAffidoContratto
       ,ImpProvvigione,c.IdAgenzia AS IdAgenziaCorrente,
       IFNULL(c.ImpDebitoResiduo,0)+IFNULL(c.ImpCapitale,0) AS CapitaleResiduo # aggiunto 9/12/2015
FROM db_cnc_storico.dettaglioprovvigione d
JOIN reparto r ON r.IdReparto=d.IdAgenzia
JOIN db_cnc_storico.contratto c ON c.IdContratto=d.IdContratto
JOIN statorecupero st ON st.IdStatoRecupero=c.IdStatoRecupero
JOIN db_cnc_storico.cliente x ON x.IdCliente=c.IdCliente
LEFT JOIN utente u ON u.IdUtente=d.IdAgente
LEFT JOIN statorinegoziazione rin ON rin.IdStatoRinegoziazione=c.IdStatoRinegoziazione
LEFT JOIN v_sintesi_modificaprovvigione_storico mp ON mp.IdContratto=d.IdContratto AND mp.DataFineAffido=d.DataFineAffido
;
  