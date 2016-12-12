CREATE OR REPLACE VIEW v_soggetti_collegati
AS
SELECT cp.IdCliente,TitoloTipoControparte as Ruolo,c.IdContratto,c.CodContratto,c.ImpFinanziato,c.IdProdotto,c.IdStatoContratto,
             c.IdStatoRecupero,c.IdAgenzia,ImpInsoluto
      FROM  controparte cp,contratto c,tipocontroparte tc WHERE cp.IdContratto=c.IdContratto AND cp.IdTipoControparte=tc.IdTipoControparte
UNION ALL
SELECT  idCliente,'Intestatario',c.IdContratto,c.CodContratto,c.ImpFinanziato,c.IdProdotto,c.IdStatoContratto,
             c.IdStatoRecupero,c.IdAgenzia,ImpInsoluto
      FROM contratto c;