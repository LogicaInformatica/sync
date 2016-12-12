create or replace view v_recapito_di_tipo
AS
select * from recapito r where indirizzo>''
  and not exists 
  (select 1 from recapito x where x.idcliente=r.idcliente and x.idtiporecapito=r.idtiporecapito 
  and indirizzo>'' and x.idrecapito>r.idrecapito);