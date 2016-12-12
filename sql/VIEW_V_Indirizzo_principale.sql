create or replace view v_indirizzo_principale
AS
select r.*,TitoloRegione
FROM recapito r 
LEFT JOIN provincia p ON p.SiglaProvincia=r.SiglaProvincia
LEFT JOIN regione x ON x.IdRegione=p.IdRegione
where idtiporecapito=1 and FlagAnnullato='N' AND indirizzo>''
  and not exists (SELECT 1 FROM recapito x 
                  WHERE x.idcliente=r.idcliente and x.idtiporecapito=1 and FlagAnnullato='N' AND indirizzo>'' 
                  AND x.idrecapito>r.idrecapito);
