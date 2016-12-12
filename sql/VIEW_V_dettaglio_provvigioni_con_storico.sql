CREATE OR REPLACE VIEW v_dettaglio_provvigioni_con_storico
AS
SELECT * FROM v_dettaglio_provvigioni
UNION ALL
SELECT * FROM v_dettaglio_provvigioni_storico
;
  