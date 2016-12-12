
create or replace view v_profili_funzioni
as
select p.*,codfunzione,titolofunzione
from profilo p
join profilofunzione pf on pf.idprofilo=p.idprofilo
join funzione f on pf.idfunzione=f.idfunzione
left join azione a on a.idfunzione=f.idfunzione and now() between a.dataini and a.datafin
where now() between pf.dataini and pf.datafin