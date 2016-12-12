#
# Vista per determinare i tabs della pagina "Incasso valori"
#
CREATE OR REPLACE VIEW v_incassi_tabs AS
SELECT  DISTINCT r.IdReparto as IdRepartoInc,
   r.TitoloUfficio as RepartoInc
FROM
   incasso i left join utente u on u.IdUtente = i.IdUtente
             left join reparto r on r.IdReparto=u.IdReparto
 WHERE FlagModalita='V';