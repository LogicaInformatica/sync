#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_stato_contratto
AS
SELECT IdStatoContratto,TitoloStatoContratto,true as Selected
FROM statocontratto;

