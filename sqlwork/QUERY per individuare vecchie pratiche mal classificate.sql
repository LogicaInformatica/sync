select CodContratto
 from contratto c where idclasse=18 and idstatorecupero!=13
and (select sum(importo) from movimento m where m.idcontratto=c.idcontratto)>26
and CURDATE()-INTERVAL 2 MONTH>(SELECT MAX(LastUpd) FROM movimento x WHERE x.idcontratto=c.idcontratto)
order by 1
limit 999999  ;
