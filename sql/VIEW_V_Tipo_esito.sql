# VIEW usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_esito
AS
SELECT IdTipoEsito,TitoloTipoEsito,false as Selected
FROM tipoesito
order by ordine;


select * from v_tipo_esito;
