CREATE OR REPLACE VIEW v_prodotto_decodifica
AS
Select pro.*,fp.TitoloFamiglia,case 
when codMarca='TO' then 'Toyota'
when codMarca='LE' then 'Lexus'
when codMarca='RD' then 'Redfin'
when codMarca='DH' then 'Daihatsu'
when codMarca='SU' then 'Subaru'
else 'Nessuno' END as descrizioneMarca
from prodotto pro
left join famigliaprodotto fp on(pro.IdFamiglia=fp.IdFamiglia)
order by TitoloProdotto;