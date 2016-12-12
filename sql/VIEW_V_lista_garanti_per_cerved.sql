#
# Vista usata per aggiungere la lista dei garanti nell'export Cerved
#
CREATE OR REPLACE VIEW v_lista_garanti_per_cerved
AS
SELECT DISTINCT cl.IdCliente as IdCliente,IdContratto,IdTipoCliente,RagioneSociale,snc.Nome,snc.Cognome,LocalitaNascita,DataNascita,SiglaProvincia,CodiceFiscale
from controparte co join tipocontroparte tc on co.idtipocontroparte=tc.idtipocontroparte AND FlagGarante='Y'
join cliente cl ON cl.idcliente=co.idcliente
LEFT JOIN v_separa_nome_cognome snc ON snc.IdCliente=cl.IdCliente;