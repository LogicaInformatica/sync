#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_prodotto
AS
SELECT IdProdotto,TitoloProdotto,true as Selected
FROM prodotto