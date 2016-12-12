CREATE VIEW v_situazione_corrente
AS
SELECT c.IdContratto,  CodContratto, IdStatoContratto, c.IdClasse, c.IdAgenzia, c.IdStatoRecupero, IdCategoria, 
	DataInizioAffido,
	DataFineAffido,IdAttributo,ImpInsoluto,v.InteressiMora,ImpCapitale,ImpSpeseRecupero,
    ImpAltriAddebiti,CodRegolaProvvigione,PercSvalutazione,IdRegolaProvvigione,
    ImpDebitoResiduo
FROM contratto c
JOIN v_dettaglio_insoluto v ON v.IdContratto=c.IdContratto
WHERE c.ImpInsoluto>26
ORDER BY 2;

select * from v_situazione_corrente limit 100000