#
# Aggregato di v_importi_per_provvigioni_full usata dalla v_importi_per_provvigioni_special
#
CREATE OR REPLACE VIEW v_importi_per_provvigioni_group
AS
select v.IdContratto,v.DataInizioAffidoContratto,v.DataFineAffidoContratto,rp.IdRegolaProvvigione,
           v.IdAgenzia,MAX(v.idAgente) AS IdAgente,MAX(v.idClasse) as IdClasse,
				   MAX(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
				   MAX(v.ImpTotaleAffidato) AS ImpTotaleAffidato,
				   SUM(v.ImpPagato) AS ImpPagato,
				   SUM(v.ImpPagatoTotale) AS ImpPagatoTotale,
        		   SUM(v.ImpInteressi) AS ImpInteressi,
        		   SUM(v.ImpSpese) as ImpSpese,
      			   MAX(v.NumRate) AS NumRate,
	               MAX(RateViaggiantiIncassate) AS RateViaggiantiIncassate
FROM v_importi_per_provvigioni_full v
#JOIN assegnazione a ON a.IdContratto=v.IdContratto AND v.DataFineAffido>=a.DataInizioAffidoContratto AND v.DataInizioAffido<=a.DataFineAffidoContratto
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
JOIN regolaprovvigione rp ON rp.IdRegolaProvvigione=p.IdRegolaProvvigione
WHERE FlagMensile='Y'
GROUP BY v.IdContratto,IdRegolaProvvigione,DataInizioAffidoContratto,DataFineAffidoContratto,IdAgenzia;