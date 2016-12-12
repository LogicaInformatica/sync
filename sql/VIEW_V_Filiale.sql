#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_filiale
AS
SELECT IdFiliale,TitoloFiliale,true as Selected
FROM filiale
UNION ALL
SELECT -1," [vuota]",true;
