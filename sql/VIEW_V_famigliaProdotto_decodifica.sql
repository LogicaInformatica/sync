CREATE OR REPLACE VIEW v_famigliaprodotto_decodifica
AS
Select fp.*,com.CodCompagnia,com.TitoloCompagnia, fparent.TitoloFamiglia as famigliaParent,
case 
when(fp.IdFamigliaParent is null)then 'Macrofamiglia' else 'Sottofamiglia' END as gruppo
from famigliaprodotto fp 
left join compagnia com on(fp.IdCompagnia=com.IdCompagnia)
left join famigliaprodotto fparent on(fp.IdFamigliaParent=fparent.IdFamiglia)
order by gruppo,TitoloFamiglia;