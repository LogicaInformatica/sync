CREATE OR REPLACE VIEW v_lista_telefoni
AS
SELECT IdCliente,GROUP_CONCAT(Telefono SEPARATOR ', ') AS telefoni
 FROM v_telefoni_e_cellulari
GROUP BY IdCliente


