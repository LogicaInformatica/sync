# VIEW usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_allegato
AS
SELECT IdTipoAllegato,TitoloTipoAllegato,false as Selected
FROM tipoallegato;


select * from v_tipo_allegato;
