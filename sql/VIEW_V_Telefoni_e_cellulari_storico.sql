# ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_telefoni_e_cellulari
AS
select IdCliente,Telefono
from db_cnc_storico.v_telefono
UNION
select IdCliente,Cellulare
from db_cnc_storico.v_cellulare
