#
# Vista usata all'interno di v_dettaglio_provvigioni e nella funzione aggiornaProvvigioni
#
CREATE OR REPLACE VIEW v_sintesi_modificaprovvigione_storico
AS
SELECT IdContratto,DataFineAffido,
	   SUM(IF(TipoCorrezione='D',1,0)) AS NumRateCancellate,
	   SUM(DiffCapitaleAffidato) AS DiffCapitaleAffidato,
	   SUM(DiffTotaleAffidato) AS DiffTotaleAffidato,
	   SUM(DiffPagato) AS DiffPagato,
	   SUM(DiffPagatoTotale) AS DiffPagatoTotale,
	   MAX(DiffInteressi) AS DiffInteressi, 		# non sono suddivisi per rata (uguali su ciascuna rata)
	   MAX(DiffSpeseRecupero) AS DiffSpeseRecupero, # non sono suddivisi per rata (uguali su ciascuna rata)
	   SUM(DiffRataViaggiante) AS DiffRataViaggiante,
	   COUNT(*) AS NumModifiche
FROM db_cnc_storico.modificaprovvigione
GROUP BY IdContratto,DataFineAffido;