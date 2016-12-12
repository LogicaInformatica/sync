CREATE OR REPLACE VIEW v_modificaprovvigione
AS
SELECT dp.IdProvvigione,i.IdContratto,i.DataFineAffido,i.NumRata,
  replace(replace(replace(format(i.ImpCapitaleAffidato,2),'.',';'),',','.'),';',',') AS ImpCapitaleAffidato,
  replace(replace(replace(format(i.ImpTotaleAffidato,2),'.',';'),',','.'),';',',') AS ImpTotaleAffidato,
  replace(replace(replace(format(i.ImpPagato,2),'.',';'),',','.'),';',',') AS ImpPagato,
  replace(replace(replace(format(i.ImpPagatoTotale,2),'.',';'),',','.'),';',',') AS ImpPagatoTotale,
  replace(replace(replace(format(IFNULL(dp.ImpInteressi,0),2),'.',';'),',','.'),';',',') AS ImpInteressi,
  replace(replace(replace(format(IFNULL(dp.ImpSpese,0),2),'.',';'),',','.'),';',',') AS ImpSpese,
  DATE_FORMAT(i.DataFineAffido,'%d/%m/%Y') AS DataLotto, mp.DiffCapitaleAffidato, mp.DiffTotaleAffidato,
  mp.DiffPagato,mp.DiffPagatoTotale,mp.DiffInteressi,mp.DiffSpeseRecupero,mp.TipoCorrezione,mp.Nota,mp.LastUpd,mp.LastUser,
  i.ImpCapitaleAffidato+IFNULL(DiffCapitaleAffidato,0) AS CapAffidatoMod,
  i.ImpTotaleAffidato+IFNULL(DiffTotaleAffidato,0) AS TotAffidatoMod,
  i.ImpPagato+IFNULL(DiffPagato,0) AS PagatoMod,
  i.ImpPagatoTotale+IFNULL(DiffPagatoTotale,0) AS PagatoTotaleMod,
  IFNULL(dp.ImpInteressi,0)+IFNULL(DiffInteressi,0) AS InteressiMod,
  IFNULL(dp.ImpSpese,0)+IFNULL(DiffSpeseRecupero,0) AS SpeseRecuperoMod,
  IF(i.RataViaggianteIncassata=1,'Y','N') AS FlagRataViaggiante,
  IF(i.RataViaggianteIncassata+IFNULL(mp.DiffRataViaggiante,0)=1,'Y','N') AS FlagRataViaggianteMod
FROM dettaglioprovvigione dp
JOIN v_importi_per_provvigioni i ON i.IdContratto=dp.IdContratto AND i.DataFineAffido=dp.DataFineAffido
LEFT JOIN modificaprovvigione mp ON i.IdContratto=mp.IdContratto AND i.DataFineAffido=mp.DataFineAffido
	AND i.NumRata=mp.NumRata;