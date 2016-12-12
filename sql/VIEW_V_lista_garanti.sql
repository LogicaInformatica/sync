#
# Vista usata per creare la tabella di comodo listaGaranti (usata nell'export Excel) 
#
CREATE OR REPLACE VIEW v_lista_garanti
AS
SELECT co.IdContratto,GROUP_CONCAT(
CONCAT(cl.Nominativo,' (',TitoloTipoControparte
    ,IF(CodiceFiscale>'',CONCAT(', CF: ',CodiceFiscale),'')
    ,IF(cl.Telefono>'',CONCAT(', tel: ',cl.Telefono),'')
    ,IF(Indirizzo>'',CONCAT(', ',Indirizzo,' - ',Cap,' ',Localita,' ',i.SiglaProvincia),'')
    ,')')
SEPARATOR '\n') AS ListaGaranti
from controparte co 
join tipocontroparte tc on co.idtipocontroparte=tc.idtipocontroparte AND FlagGarante='Y'
join cliente cl ON cl.idcliente=co.idcliente
LEFT JOIN v_indirizzo_principale i ON i.IdCliente=cl.IdCliente
GROUP BY co.IdContratto
;