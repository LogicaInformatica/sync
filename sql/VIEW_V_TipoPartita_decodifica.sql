CREATE OR REPLACE VIEW v_tipoPartita_decodifica
AS
Select tp.IdTipoPartita, tp.TitoloTipoPartita, tp.CodTipoPartitaLegacy, tp.CodTipoPartita, tp.CategoriaPartita,
case 
when(tp.CategoriaPartita='C')then 'Capitale'
when(tp.CategoriaPartita='A')then 'Altro'
when(tp.CategoriaPartita='R')then 'Spese recupero'
when(tp.CategoriaPartita='I')then 'Interessi di mora' end as descrizioneCategoriaPartita,
tp.lastUser,tp.lastUpd
from tipopartita tp
order by 1;