# OBSOLETO
# Lista rate pagate per contratto (individua le rate che hanno la data scadenza massima di un movimento a debito
# scaduta o in prossima scadenza (in questo modo dovrebbe beccare le sole rate per così dire già emesse)
#
CREATE OR REPLACE VIEW v_rate_pagate AS
SELECT m.IdContratto,NumRata
FROM movimento m
WHERE IdTipoMovimento NOT IN (121,340) AND NumRata!=0
GROUP BY m.IdContratto,NumRata
HAVING SUM(Importo)<26 AND MAX(IF(Importo>0,DataScadenza,null))<=CURDATE()+ INTERVAL 20 DAY
;