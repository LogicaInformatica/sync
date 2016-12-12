Create Or Replace View v_comunicazioni
AS
select DataCreazione as data, u.NomeUtente AS mittente,
CASE WHEN d.NomeUtente IS NOT NULL THEN d.NomeUtente
	 WHEN d.NomeUtente IS NULL AND TitoloUfficio IS NOT NULL THEN TitoloUfficio
     WHEN TitoloUfficio IS NULL THEN 'Tutti'
     ELSE TitoloUfficio
     END AS destinatario,
CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS riservato,
CodContratto,ifnull(Nominativo,RagioneSociale) as NomeCliente,c.IdCliente,
`n`.`IdNota` AS `IdNota`,`n`.`IdUtenteDest` AS `IdUtenteDest`,`n`.`IdUtente` AS `IdUtente`,`n`.`IdContratto` AS `IdContratto`,
`n`.`TipoNota` AS `TipoNota`,`n`.`IdReparto` AS `IdReparto`,`n`.`TestoNota` AS `TestoNota`,`n`.`DataCreazione` AS `DataCreazione`,
cast(`n`.`DataScadenza` as date) AS `DataScadenza`,
CASE WHEN DATE_FORMAT(n.DataScadenza,'%H:%i') != '00:00' THEN DATE_FORMAT(n.DataScadenza,'%H:%i') ELSE NULL END  AS OraScadenza,`n`.`DataIni` AS `DataIni`,`n`.`DataFin` AS `DataFin`,`n`.`lastupd` AS `lastupd`,
`n`.`LastUser` AS `LastUser`,`n`.`FlagRiservato` AS `FlagRiservato`,p.IdFamiglia
from nota n
LEFT JOIN utente u ON n.IdUtente=u.IdUtente
LEFT JOIN utente d ON n.IdUtenteDest=d.IdUtente
LEFT JOIN reparto r ON n.IdReparto=r.IdReparto
LEFT JOIN contratto c ON n.IdContratto=c.IdContratto
LEFT JOIN prodotto p ON p.IdProdotto=c.IdProdotto
LEFT JOIN cliente cl ON c.IdCliente=cl.IdCliente