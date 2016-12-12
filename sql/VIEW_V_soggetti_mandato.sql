create or replace view v_soggetti_mandato as
SELECT cp.IdCliente,TitoloTipoControparte as Ruolo,cp.IdContratto,tc.flagGarante
FROM  controparte cp,tipocontroparte tc WHERE cp.IdTipoControparte=tc.IdTipoControparte AND tc.FlagGarante='Y'
UNION ALL
SELECT  idCliente,'Cliente' as Ruolo,c.IdContratto,'N' AS FlagGarante FROM contratto c;