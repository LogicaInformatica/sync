#
# Controllo affidi per meno di 26 euro
#
create view v_check_01
as
select IdContratto,CodContratto,ImpInsoluto,NumInsoluti,concat(CodClasse,' ',TitoloClasse) AS Classe,TitoloUfficio AS Agenzia
from contratto c
left join reparto r on r.idreparto=c.idagenzia
left join classificazione cl on c.idclasse=cl.idclasse
where datainizioaffido=CURDATE() AND impinsoluto<26;
