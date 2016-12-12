create or replace view v_interessi_mora
as
select c.IdContratto,
## Interessi di mora (somma il maturato, che è nel contratto, con l'eventuale addebito, preso dalle righe di insoluto)
case when ifnull(rr.flagInteressimora,rrn.flaginteressimora) = 'Y' then c.impinteressimora+SUM(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpInteressi,0))
     else 0
end as InteressiMora
from contratto c
left join insoluto i on i.idContratto=c.idContratto
left join regolaprovvigione rp ON rp.IdRegolaProvvigione=c.IdRegolaProvvigione
left join regolaripartizione rr on rr.Idregolaprovvigione=rp.IdRegolaProvvigione AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rr.DataIni AND rr.DataFin
left join regolaripartizione rrn on rrn.idclasse=c.idclasse and rrn.idreparto is null AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rrn.DataIni AND rrn.DataFin
group by c.idcontratto;