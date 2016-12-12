CREATE OR REPLACE VIEW v_mesi_affido
AS
select distinct YEAR(DataIni)*100+MONTH(DataIni) AS Id,DATE_FORMAT(DataIni,'%m %Y') AS Mese,
CONVERT(DATE_FORMAT(DataIni,'%Y-%m-01'),DATE) AS InizioMese,CONVERT(DATE_FORMAT(DataIni,'%Y-%m-01'),DATE)+INTERVAL 1 MONTH-INTERVAL 1 DAY AS FineMese
from assegnazione;