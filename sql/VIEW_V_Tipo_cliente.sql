# VIEW usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_allegato
AS
SELECT IdTipoCliente,TitoloTipoCliente,true as Selected
FROM tipocliente
WHERE CodTipoCliente!='D';
