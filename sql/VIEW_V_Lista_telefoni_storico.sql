# ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_lista_telefoni
AS
SELECT IdCliente,GROUP_CONCAT(Telefono SEPARATOR ', ') AS telefoni
 FROM db_cnc_storico.v_telefoni_e_cellulari
GROUP BY IdCliente


