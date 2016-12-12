#
#  Controllo bilanciamento affidi tra agenzie corrispondenti
#
create or replace view v_check_05
as
select codregolaprovvigione,TitoloUfficio AS Agenzia,count(*)
from contratto c JOIN reparto r ON r.IdReparto=c.IdAgenzia
where codregolaprovvigione in ('I1','I7','P2','P4','1C','1S')
and datafineaffido BETWEEN CURDATE()+ INTERVAL 1 MONTH-INTERVAL 3 DAY AND CURDATE()+ INTERVAL 1 MONTH-INTERVAL 1 DAY
group by codregolaprovvigione,TitoloUfficio
order by 1;

