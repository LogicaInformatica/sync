#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_classe
AS
SELECT IdClasse,TitoloClasse,true as Selected
FROM classificazione
UNION ALL
SELECT -1," [vuota]",true;
