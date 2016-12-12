/*
 O B S O L E T A
*/


CREATE OR REPLACE VIEW v_ripartizione_per_reparto
AS
select idclasse,percspeseincasso,impspeseincasso,flaginteressimora,idfamiglia,a.idreparto
from regolaripartizione r,reparto a
where CURDATE() BETWEEN r.DataIni AND r.DataFin
AND   CURDATE() BETWEEN a.DataIni AND a.DataFin
AND (r.IdReparto=a.IdReparto OR r.IdReparto IS NULL AND NOT EXISTS
(SELECT 1 FROM regolaripartizione x where x.idReparto=a.IdReparto
AND CURDATE() BETWEEN x.DataIni AND x.DataFin
AND r.IdClasse=x.IdClasse AND IFNULL(x.IdFamiglia,0)=IFNULL(r.IdFamiglia,0))
)