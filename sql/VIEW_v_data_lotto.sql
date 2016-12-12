#
# Lista le date di lotti usabili nella funzione di modifica provvigione
# (quelle prossime alla provvigione presa in esame)
#
create or replace view v_data_lotto
AS
SELECT DISTINCT p.IdProvvigione,p.DataFin,DATE_FORMAT(x.DataFin,'%Y-%m-%d') AS idDataLotto,DATE_FORMAT(x.DataFin,'%d/%m/%Y') AS dataLotto
FROM provvigione p
JOIN provvigione x ON x.DataFin BETWEEN p.DataFin-INTERVAL 1 MONTH AND p.DataFin+INTERVAL 1 MONTH;