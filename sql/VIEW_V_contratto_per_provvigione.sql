#
# Usata per le formule provvigionali che richiedono il calcolo su ogni contratto (rinegoziazioni)
#
CREATE OR REPLACE VIEW v_contratto_per_provvigione
AS
SELECT c.*,cd.PercTasso AS NuovoTasso
FROM contratto c
LEFT JOIN contratto cd ON c.IdContrattoDerivato=cd.IdContratto;