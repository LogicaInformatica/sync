### ATTENZIONE AL NOME SCHEMA
CREATE OR REPLACE VIEW db_cnc_storico.v_soggetti_collegati
AS
SELECT cp.IdCliente,TitoloTipoControparte as Ruolo,c.IdContratto,c.CodContratto,c.ImpFinanziato,c.IdProdotto,c.IdStatoContratto,
             c.IdStatoRecupero,c.IdAgenzia,ImpInsoluto
      FROM  db_cnc_storico.controparte cp,
      db_cnc_storico.contratto c,tipocontroparte tc 
      WHERE cp.IdContratto=c.IdContratto AND cp.IdTipoControparte=tc.IdTipoControparte
UNION ALL
SELECT  idCliente,'Intestatario',c.IdContratto,c.CodContratto,c.ImpFinanziato,c.IdProdotto,c.IdStatoContratto,
             c.IdStatoRecupero,c.IdAgenzia,ImpInsoluto
      FROM db_cnc_storico.contratto c;