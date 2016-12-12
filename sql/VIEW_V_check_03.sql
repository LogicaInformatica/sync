#
#  Controllo pratiche "in attesa" senza motivo apparente 
#
create or replace view v_check_03
as
select IdContratto,CodContratto,ImpInsoluto,NumInsoluti,c.datacambioclasse,c.idclasse,concat(CodClasse,' ',TitoloClasse) AS Classe,TitoloUfficio AS Agenzia
from contratto c
left join reparto r on r.idreparto=c.idagenzia
left join classificazione cl on c.idclasse=cl.idclasse
where idstatorecupero=2 and IFNULL(cl.FlagNoAffido,'N')='N'
AND c.IdClasse NOT IN (17,29,40,41,31,37)
AND NOT (c.DataRata>CURDATE() AND c.NumInsoluti<=1)
order by codcontratto;
   



