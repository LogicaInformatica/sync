#
# Vista usata per le analisi manuali sul DB (movimenti con causali)
#
create or replace view v_mov
as
select titolotipomovimento,categoriamovimento,categoriapartita,m.idcontratto,m.numrata,dataregistrazione,datascadenza,importo,m.idtipomovimento,
m.numriga,idtipoinsoluto,DataCompetenza
 from movimento m
join tipomovimento tm on tm.idtipomovimento=m.idtipomovimento
join tipopartita tp on tp.idtipopartita=m.idtipopartita;