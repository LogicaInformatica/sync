#
#  Controllo applicazione affidi forzati
#
create or replace view v_check_06
as
select c.IdContratto,c.CodContratto,rp.codregolaprovvigione AS Forzatura,c.codregolaprovvigione AS Effettiva
from assegnazione a
join regolaprovvigione rp ON a.idaffidoforzato=rp.IdRegolaProvvigione
join contratto c on c.idcontratto=a.idcontratto and a.datafin=c.datafineaffido
where idaffidoforzato is not null and c.codregolaprovvigione!=rp.codregolaprovvigione
and a.datafin>=CURDATE()+INTERVAL 1 MONTH - INTERVAL 5 DAY
order by 2;

