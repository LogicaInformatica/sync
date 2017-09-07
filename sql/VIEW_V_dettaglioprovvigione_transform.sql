## definisce campi aggiuntivi a dettaglioprovvigione, omonimi dei campi usati nelle formule di calcolo delle provvigioni
CREATE OR REPLACE VIEW v_dettaglioprovvigione_transform
AS
/*
SELECT IdProvvigione,IdContratto,
  IF (NumRateViaggianti>0,1,0) AS NumViaggianti,
  IFNULL(ImpPagato,0) AS ImpCapitaleIncassato,
  IFNULL(ImpInteressi,0) AS ImpInteressiDiMora,
  IFNULL(ImpSpese,0) AS ImpSpeseRecupero,
  LEAST( ImpCapitaleAffidato, IFNULL(ImpPagato,0) ) AS ImpCapitaleRealeIncassato
 FROM dettaglioprovvigione d

*/
SELECT IdProvvigione,IdContratto,
  IF (RateViaggiantiIncassate>0,1,0) AS NumViaggianti,
  IFNULL(ImpPagato,0) AS ImpCapitaleIncassato,
  IFNULL(ImpInteressi,0) AS ImpInteressiDiMora,
  IFNULL(ImpSpese,0) AS ImpSpeseRecupero,
  LEAST( ImpCapitaleAffidato, IFNULL(ImpPagato,0) ) AS ImpCapitaleRealeIncassato
 FROM v_dettaglio_provvigioni;
