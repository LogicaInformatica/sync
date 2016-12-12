CREATE OR REPLACE VIEW v_fasce_visibili
AS
SELECT u.IdUtente,replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero
FROM utente u
JOIN regolaassegnazione r ON u.IdUtente=r.IdUtente AND TipoAssegnazione=1
JOIN regolaprovvigione p  ON p.IdReparto=r.IdReparto
UNION
SELECT IdUtente,replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero
FROM utente u
JOIN regolaprovvigione p ON p.IdReparto=u.IdReparto
UNION
SELECT u.IdUtente,replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero
FROM utente u
JOIN regolaassegnazione r ON u.IdUtente=r.IdUtente AND TipoAssegnazione=1
JOIN regolaprovvigione p ON p.IdRegolaProvvigione=r.IdRegolaProvvigione