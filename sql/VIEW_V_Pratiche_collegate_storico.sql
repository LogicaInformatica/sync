### ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_pratiche_collegate
AS
SELECT x.IdAgenzia,x.IdCliente, Ruolo,ifnull(c.nominativo,c.ragionesociale)as cliente, x.IdContratto, x.CodContratto as numPratica, concat(titolofamiglia,' - ',titoloprodotto) as Prodotto,
       TitoloStatoContratto as Stato, TitoloStatoRecupero as StatoRecupero,
       TitoloUfficio AS Agenzia,ImpInsoluto AS Importo,ImpFinanziato
FROM db_cnc_storico.v_soggetti_collegati x
LEFT JOIN prodotto p          ON x.IdProdotto = p.IdProdotto
LEFT JOIN famigliaprodotto fp ON p.idfamiglia=fp.idfamiglia
LEFT JOIN statocontratto sc   ON sc.IdStatoContratto=x.IdStatoContratto
LEFT JOIN statorecupero sr    ON sr.IdStatoRecupero=x.IdStatoRecupero
LEFT JOIN reparto r           ON r.IdReparto=x.IdAgenzia
LEFT JOIN db_cnc_storico.cliente c 		  ON x.idcliente=c.idcliente;