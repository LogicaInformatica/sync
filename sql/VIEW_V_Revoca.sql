CREATE VIEW v_revoca
AS
select sr.DataEvento,NotaEvento,c.*
from v_insoluti_opt c
JOIN storiarecupero sr ON sr.IdContratto=c.IdContratto
JOIN assegnazione a ON a.IdContratto=c.IdContratto
JOIN azione az ON az.IdAzione=sr.IdAzione
WHERE c.IdAgenzia IS NULL AND CodAzione = 'REV'
AND a.IdAgenzia IS NOT NULL
AND sr.DataEvento BETWEEN a.DataIni AND a.DataFin
AND CURDATE()<a.DataFin;