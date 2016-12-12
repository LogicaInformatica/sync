CREATE OR REPLACE VIEW v_dettaglio_rate_provvigione
AS
SELECT dp.IdProvvigione,v.IdContratto,v.NumRata,c.CodContratto,DATE_FORMAT(dp.datafineaffido,'%d/%m/%Y') AS Lotto,
  v.ImpCapitaleAffidato + IFNULL(m.DiffCapitaleAffidato,0) AS ImpCapitaleAffidato,
  v.ImpTotaleAffidato   + IFNULL(m.DiffTotaleAffidato,0) AS ImpTotaleAffidato,
  v.ImpPagato           + IFNULL(m.DiffPagato,0) AS ImpPagato,
  v.ImpPagatoTotale     + IFNULL(m.DiffPagatoTotale,0) AS ImpPagatoTotale,
  IF(v.RataViaggianteIncassata+IFNULL(m.DiffRataViaggiante,0)=1,'Y','N') AS FlagRataViaggiante,
CASE WHEN m.TipoCorrezione='M' THEN 'Corretta manualmente'
     WHEN m.TipoCorrezione='D' THEN 'Cancellata manualmente'
	 WHEN TipoAnomalia='1' THEN 'Incasso non attribuibile'
     WHEN TipoAnomalia='2' THEN 'Incasso eccede insoluto affidato'
     WHEN TipoAnomalia='3' THEN 'Incasso eccede capitale affidato'
     WHEN TipoAnomalia='4' THEN 'Inizialmente a credito'
END AS TipoAnomalia
from v_importi_per_provvigioni v
JOIN dettaglioprovvigione dp ON v.IdContratto=dp.IdContratto and dp.datafineaffido=v.datafineaffido
JOIN contratto c ON c.IdContratto=dp.IdContratto
LEFT JOIN modificaprovvigione m ON m.IdProvvigione=dp.IdProvvigione AND v.NumRata=m.NumRata and m.idContratto=dp.IdContratto
;
