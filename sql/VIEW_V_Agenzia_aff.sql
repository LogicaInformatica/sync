#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_agenzia_aff
AS
SELECT IdRegolaProvvigione,
	   CONCAT(TitoloUfficio,' (',CodRegolaProvvigione,')') AS TitoloAgenzia,true as Selected
FROM reparto r JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto
     AND r.DataFin>=CURDATE() AND rp.DataFin>=CURDATE() 
UNION ALL
SELECT -1," [nessuna]",true;
