CREATE OR REPLACE VIEW  v_insoluti_scadenza
 AS select i.*,cast(n.DataScadenza as date) AS scadenza,n.TestoNota AS nota,IFNULL(FlagRiservato,'N') AS Riservato,
 n.IdReparto as IdRepartoDest,n.IdUtenteDest,n.IdUtente AS IdUtenteCreatore,IdNota,TipoNota
 from nota n join v_insoluti_opt i on n.IdContratto = i.IdContratto
 where n.DataScadenza IS NOT NULL;   