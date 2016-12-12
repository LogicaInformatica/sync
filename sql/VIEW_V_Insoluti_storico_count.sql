CREATE OR REPLACE VIEW v_insoluti_storico_count
AS
select co.*,CodContratto AS NumPratica
from db_cnc_storico.contratto co
left join db_cnc_storico._opt_insoluti i ON i.IdContratto=co.IdContratto
WHERE (IFNULL(FlagStoria,'N')='Y' OR CodContratto LIKE 'KG%')
;