CREATE OR REPLACE VIEW v_profili_utenti
AS
select p.*,(SELECT COUNT(DISTINCT IdUtente) FROM profiloutente pu WHERE pu.IdProfilo=p.IdProfilo)
 AS NumeroUtenti
from profilo p
ORDER BY TitoloProfilo;