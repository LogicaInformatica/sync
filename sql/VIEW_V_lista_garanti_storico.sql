# versione per Storico: attenzione al nome schema
# Vista usata per creare la tabella di comodo listaGaranti (usata nell'export Excel) VERSIONE SU STORICO
#
CREATE OR REPLACE VIEW db_cnc_storico.v_lista_garanti
AS
SELECT co.IdContratto,GROUP_CONCAT(
CONCAT(cl.Nominativo,' (',TitoloTipoControparte
    ,IF(CodiceFiscale>'',CONCAT(', CF: ',CodiceFiscale),'')
    ,IF(cl.Telefono>'',CONCAT(', tel: ',cl.Telefono),'')
    ,IF(Indirizzo>'',CONCAT(', ',Indirizzo,' - ',Cap,' ',Localita,' ',i.SiglaProvincia),'')
    ,')')
SEPARATOR '\n') AS ListaGaranti
from db_cnc_storico.controparte co 
join tipocontroparte tc on co.idtipocontroparte=tc.idtipocontroparte AND FlagGarante='Y'
join db_cnc_storico.cliente cl ON cl.idcliente=co.idcliente
LEFT JOIN db_cnc_storico.v_indirizzo_principale i ON i.IdCliente=cl.IdCliente
GROUP BY co.IdContratto
;