#
# View usata nell'Export del dettaglio provvigioni
#
create or replace view v_ultima_azione_utente
AS
select s.idcontratto,a.titoloazione as UltimaAzione,s.DataEvento as DataUltimaAzione,NomeUtente AS UtenteUltimaAzione,
s.NotaEvento
from storiarecupero s
join azione a on s.idazione=a.idazione
join utente u on u.idutente=s.idutente
where s.idutente>0 and s.idazione>0 
and not exists (select 1 from storiarecupero x
                where s.idcontratto=x.idcontratto and x.idutente>0
                and x.idazione>0 and x.idstoriarecupero>s.idstoriarecupero);