#
# Assegnazioni, legate a  regole di provvigione con chiusura mensile, da spezzare per la chiusura mensile
#
CREATE OR REPLACE VIEW v_assegnazioni_da_chiudere
AS
select a.*,LAST_DAY(CURDATE()-INTERVAL 1 MONTH) AS DataChiusura,LAST_DAY(CURDATE()-INTERVAL 1 MONTH)+INTERVAL 1 DAY AS DataApertura
from assegnazione a JOIN regolaprovvigione r ON a.IdRegolaProvvigione=r.IdRegolaProvvigione AND r.FlagMensile='Y'
WHERE a.DataFin>CURDATE() AND LAST_DAY(a.DataIni)<CURDATE() AND MONTH(a.DataIni)!=MONTH(a.DataFin);