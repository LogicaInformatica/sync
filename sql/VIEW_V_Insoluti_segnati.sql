#
# Variante di v_insoluti che contiene come ProssimaAgenzia l'agenzia di prossimo affido 
# (usata nelle liste delle pratiche segnate per affido STR/LEG)
#
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_segnati
AS
select v.*,CONCAT(TitoloUfficio,' (',rp.CodRegolaProvvigione,')') AS ProssimaAgenzia
FROM v_insoluti_opt v
LEFT JOIN regolaprovvigione rp ON rp.CodRegolaProvvigione=v.CodRegolaProvvigione
	AND CURDATE()+INTERVAL 1 MONTH BETWEEN DataIni AND DataFin
LEFT JOIN reparto r ON r.IdReparto=rp.IdReparto;  