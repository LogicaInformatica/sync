CREATE OR REPLACE VIEW v_totale_stornato_provvigione
AS
SELECT IdProvvigione, SUM(ImpStornato) as TotaleStornato FROM dettaglioprovvigione group by IdProvvigione;