#
# Crea la lista di casi di cui si deve ricalcolare le provvigioni
#
CREATE OR REPLACE VIEW v_provvigioni_ricalcolabili
AS
SELECT * FROM v_lotti_provvigioni v
#non deve esistere la stessa riga in stato "consolidato"
WHERE NOT EXISTS (SELECT 1 FROM provvigione p WHERE p.StatoProvvigione='2' AND v.IdRegola=p.IdRegolaProvvigione AND v.DataFineAffido=p.DataFin)
#e deve essere un periodo recente (meno di 2 mesi fa) oppure deve esistere la riga in stato "non consolidato"
  AND (datafineaffido>CURDATE()-INTERVAL 2 MONTH
       OR EXISTS (SELECT 1 FROM provvigione p WHERE p.StatoProvvigione!='2'
                  AND v.IdRegola=p.IdRegolaProvvigione AND v.DataFineAffido=p.DataFin))
order by 1,4,2;


select * from v_provvigioni_ricalcolabili;


 