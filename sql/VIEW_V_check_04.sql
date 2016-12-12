#
# Contratti con totale insoluto non corrispondente al dettaglio insoluto
#
create or replace view v_check_04
as
select c.idcontratto,codcontratto,c.impinsoluto,datafineaffido,c.lastupd,c.idclasse,
 v.capitale+v.interessimora+v.altriaddebiti+v.speseincasso as SommaDettaglio
 from contratto c join v_dettaglio_insoluto v on c.idcontratto=v.idcontratto
	 where c.impinsoluto!=v.capitale+v.interessimora+v.altriaddebiti+v.speseincasso
 order by codcontratto;