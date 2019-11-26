## query per determinare quali pratiche sono soggette a lettera DEO
select CodContratto,c.IdContratto,TitoloClasse,TitoloStatoRecupero,DataInizioAffido,c.NumInsoluti,MIN(i.DataInsoluto) AS DataInsoluto
 from contratto c 
join insoluto i ON i.IdContratto=c.IdContratto AND i.numrata!=0 and date_format(i.DataInsoluto,'%Y%m')=date_format(NOW(),'%Y%m')-1 and i.impinsoluto>5
and not exists (select 1 from insoluto x where x.idcontratto=i.IdContratto and x.DataInsoluto<i.DataInsoluto and x.impinsoluto>5)
join classificazione cl on cl.IdClasse=c.IdClasse
JOIN statorecupero sr ON sr.IdStatoRecupero=c.IdStatoRecupero
where c.impinsoluto > 26
group by c.IdContratto;