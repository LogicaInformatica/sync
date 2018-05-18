CREATE OR REPLACE VIEW `v_agenzia`
AS
SELECT IdReparto AS IdAgenzia,TitoloUfficio AS TitoloAgenzia,r.*,
CASE WHEN IdTipoReparto IN (2,3) THEN 'AGE' WHEN IdTipoReparto=4 THEN 'STR' WHEN IdTipoReparto=5 THEN 'LEG' ELSE 'AGE' END AS TipoAgenzia
FROM reparto r
WHERE CURDATE() BETWEEN DataIni AND DataFin
AND IdCompagnia IN (SELECT IdCompagnia FROM compagnia WHERE IdTipoCompagnia=2);