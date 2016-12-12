CREATE OR REPLACE VIEW v_tipospeciale_decodifica
AS
Select ts.IdTipoSpeciale, ts.TitoloTipoSpeciale, ts.CodTipoSpeciale, ts.CodTipoSpecialeLegacy, 
ts.FlagForzatura,
case 
when(ts.FlagForzatura='Y')then 'Si'
else 'NO' end as forzato,
ts.lastUser,ts.lastUpd
from tipospeciale ts
order by 1;