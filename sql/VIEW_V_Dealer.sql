#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_dealer
AS
SELECT IdCompagnia,TitoloCompagnia,true as Selected
FROM compagnia WHERE IdTipoCompagnia=3
UNION ALL
SELECT -1," [vuoto]",true;
