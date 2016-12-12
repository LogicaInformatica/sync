#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_area
AS
SELECT IdArea,TitoloArea,true as Selected
FROM area WHERE TipoArea='R' AND IdAreaParent IS NULL
UNION ALL
SELECT -1," [vuota]",true
