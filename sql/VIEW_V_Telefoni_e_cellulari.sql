CREATE OR REPLACE VIEW v_telefoni_e_cellulari
AS
select IdCliente,Telefono
from v_telefono
UNION
select IdCliente,Cellulare
from v_cellulare
