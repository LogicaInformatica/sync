# OBSOLETO (troppo pesante)
# Numero di rate pagate per contratto
#
CREATE OR REPLACE VIEW v_num_rate_pagate AS
select IdContratto,COUNT(*) AS NumRatePagate
FROM v_rate_pagate
GROUP BY IdContratto;