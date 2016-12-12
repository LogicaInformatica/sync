# VIEW usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_stato_recupero
AS
SELECT IdStatoRecupero,TitoloStatoRecupero,true as Selected
FROM statorecupero where IdStatoRecupero>0
UNION ALL
SELECT -1," [vuoto]",true;
