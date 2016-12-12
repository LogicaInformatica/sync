#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_categoria
AS
SELECT IdCategoria,TitoloCategoria,true as Selected
FROM categoria
UNION ALL
SELECT -1," [vuota]",true;
