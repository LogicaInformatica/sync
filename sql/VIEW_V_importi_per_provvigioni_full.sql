CREATE OR REPLACE VIEW v_importi_per_provvigioni_full
AS
select a.IdContratto,a.IdAgenzia,a.IdAgente,a.DataInizioAffidoContratto,a.DataFineAffidoContratto,
        CASE WHEN SUM(ImpCapitaleAffidato)>0 THEN SUM(ImpCapitaleAffidato) ELSE 0 END as ImpCapitaleAffidato,
        CASE WHEN SUM(ImpTotaleAffidato)>0 THEN SUM(ImpTotaleAffidato) ELSE 0 END AS ImpTotaleAffidato,
        
    	/* anche se pagamenti su altre rate, il pagato va a coprire fino all'affidato */
        IF ( SUM(ImpPagatoTotale)>0 , LEAST(SUM(ImpCapitaleAffidato),SUM(ImpPagatoTotale)) , 0) AS ImpPagato,
    	/* la colonna ImpPagatoTotale tiene conto anche delle rate viaggianti, ma non di IDM e spese */
        IF ( SUM(ImpPagatoTotale)>0 , SUM(ImpPagatoTotale), 0) AS ImpPagatoTotale,
        IFNULL(a.ImpInteressiMoraPagati,0) AS ImpInteressi,IFNULL(a.ImpSpeseRecuperoPagate,0) as ImpSpese,
        a.DataFin AS DataFineAffido,MIN(v.DataInizioAffido) AS DataInizioAffido,a.IdClasse,a.IdProvvigione,
       SUM(IF(ImpCapitaleAffidato>5 AND NumRata!=0,1,0)) AS NumRate,
       SUM(RataViaggianteIncassata) AS RateViaggiantiIncassate,a.idRegolaProvvigione
from assegnazione a
JOIN v_importi_per_provvigioni v ON v.IdContratto=a.IdContratto and v.IdAgenzia=a.IdAgenzia and v.DataFineAffido=a.DataFin
GROUP BY IdContratto,IdAgenzia,IdAgente,a.DataFin; 