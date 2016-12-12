#
# Controllo variazioni capitale affidato nelle provvigioni pre e post affido
#
create or replace view v_check_09
as
select p.datafin,p.idregolaprovvigione,codregolaprovvigione,p.idreparto as IdAgenzia,b.ImpCapitaleAffidato CapitaleAffidatoPrima,p.ImpCapitaleAffidato CapitaleAffidatoDopo,
       b.NumAffidati AS NumAffidatiPrima,p.NumAffidati AS NumAffidatiDopo,b.idprovvigione as IdProvvigionePrima,p.IdProvvigione as IdProvvigioneDopo
from provvigione p
join _bkp_provvigione b on p.idregolaprovvigione=b.idregolaprovvigione and p.datafin=b.datafin
join regolaprovvigione rp on p.idregolaprovvigione=rp.idregolaprovvigione
where (p.numaffidati!=b.numaffidati or p.impcapitaleaffidato!=b.impcapitaleaffidato);


#
# Controllo variazioni sul dettaglio: query troppo pesante, limitata alla parte senza union (che va bene se le prastiche conteggiate
# pre e post sono le stesse)
#
create or replace view v_check_09d
as
select v.idContratto,v.CodContratto,v.datafineaffido as DataFin,p.idregolaprovvigione,
b.impcapitaleAffidato as CapitaleAffidatoPrima,v.impcapitaleAffidato as CapitaleAffidatoDopo
from v_dettaglio_provvigioni v
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
left join _bkp_dettaglio_provvigioni b ON v.datafineaffido=b.datafineaffido and v.idcontratto=b.idcontratto
where IFNULL(b.impcapitaleAffidato,0)!=v.impcapitaleAffidato AND v.datafineAffido>=CURDATE()-INTERVAL 4 DAY;
#UNION ALL
#select b.idContratto,b.CodContratto,b.datafineaffido as DataFin,b.impcapitaleAffidato as CapitaleAffidatoPrima,NULL
#from _bkp_dettaglio_provvigioni b
#JOIN provvigione p ON p.IdProvvigione=b.IdProvvigione
#LEFT JOIN v_dettaglio_provvigioni v ON v.datafineaffido=b.datafineaffido and v.idcontratto=b.idcontratto
#where v.idcontratto IS NULL AND b.datafineAffido>=CURDATE()-INTERVAL 4 DAY
#order by 2;



select * from v_check_09;

select * from v_check_09d where datafin='2012-01-11' and idregolaprovvigione=10;