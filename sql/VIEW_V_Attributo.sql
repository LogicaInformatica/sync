#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_attributo
AS
SELECT IdAttributo,TitoloAttributo,true as Selected
FROM attributo
UNION ALL
SELECT -1," [vuoto]",true;
