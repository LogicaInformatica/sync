CREATE OR REPLACE  VIEW v_nota AS select
(case when (isnull(n.IdReparto) and isnull(n.IdUtenteDest)) then _utf8'T'
	  when (c.IdTipoCompagnia = 1) then _utf8'R'
	  when (c.IdTipoCompagnia = 2) then _utf8'A' 
	  when (n.IdUtenteDest is not null) then _utf8'U'
	  else NULL end) AS TipoDestinatario,
r.TitoloUfficio AS ufficio,
m.NomeUtente AS autore,
d.NomeUtente AS destinatario,
n.IdNota AS IdNota,
n.IdUtenteDest AS IdUtenteDest,
n.IdUtente AS IdUtente,
n.IdContratto AS IdContratto,
n.TipoNota AS TipoNota,
n.IdReparto AS IdReparto,
n.TestoNota AS TestoNota,
n.DataCreazione AS DataCreazione,
n.DataScadenza AS DataScadenza,
CASE WHEN Date_Format(DataScadenza,'%H:%i')='00:00' THEN NULL ELSE Date_Format(DataScadenza,'%H:%i') END AS OraScadenza,
n.DataIni AS DataIni,
n.DataFin AS DataFin,
n.LastUpd AS LastUpd,
n.LastUser AS LastUser,
n.FlagRiservato AS FlagRiservato,
n.IdNotaPrecedente AS IdNotaPrecedente,
CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato,
n.IdSuper as IdSuper,
ut.Userid as UserSuper
from nota n 
left join reparto r on r.IdReparto = n.IdReparto
left join compagnia c on c.IdCompagnia = r.IdCompagnia
left join utente m on n.IdUtente = m.IdUtente
left join utente d on n.IdUtenteDest = d.IdUtente
LEFT JOIN utente ut ON n.IdSuper=ut.IdUtente