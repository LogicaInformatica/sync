create or replace view v_pratiche
as
select a.titoloarea as area,concat(titolofamiglia,' - ',Prodotto) as Prodotto,TitoloCompagnia as Venditore,
       TipoPag as TipoPagamento,cli.CodCliente,ifnull(cli.Nominativo,cli.RagioneSociale) as NomeCliente,ifnull(cli.DataNascita,'Data assente') as DataNCli,
       cli.LocalitaNascita as LuogoNCli,codstatocontratto as CodStato,
       titolostatocontratto as Stato, #Attenzione questo è lo stato del contratto, non del recupero
       Classif AS CodClasse,TitoloClasse as Classificazione,
       Stato as CodStatoRecupero,
       CASE 
       WHEN (StatoLegale >'' AND v.Stato='LEG')
			THEN CONCAT(titolostatorecupero,' (',StatoLegale,')')
	   WHEN StatoStragiudiziale >'' AND (v.Stato='STR1' OR v.Stato='STR2')
			THEN CONCAT(titolostatorecupero,' (',StatoStragiudiziale,')')
	   ELSE titolostatorecupero
	   END AS StatoRecupero ,
       CodUtente,Operatore AS NomeUtente,TitoloUfficio As NomeAgenzia,
       c.NumRata as Rata,NumInsoluti as Insoluti,Datediff(curdate(),DataRata) as Giorni,
       IFNULL(c.ImpInsoluto,IF(IdAgenzia>0,ImpSpeseRecupero,0)+IF(rp.FlagInteressiMora='Y',ImpInteressiMora,0))+IF(c.IdAttributo=86,c.ImpRiscatto,0) as Importo,
       DataRata as DataScadenza,fp.IdFamiglia,cli.IdArea as AreaCliente,cli.IdTipoCliente,
       cli.IdArea AS IdAreaCliente,fp.IdFamigliaParent,TitoloTipoSpeciale as TitTipoSpec,ifnull(cliV.Nominativo,cliV.RagioneSociale) as NomeVenditore,ifnull(cliPV.Nominativo,cliPV.RagioneSociale) as NomePuntoVendita,TitoloAttributo as Attributo,
       c.*,cli.sesso,cli.CodiceFiscale,cli.PartitaIVA,
       Categoria AS TitoloCategoria,FlagCambioAgente,ar.titoloarea as AreaIntest,
       CAST(c.DataRata as char) as Riferimento, # per uso nella eseguiAutomatismi
       cli.FlagIrreperibile
From contratto c
JOIN _opt_insoluti v ON v.IdContratto=c.IdContratto
left join filiale f on c.idfiliale=f.idfiliale
left join area a on a.idarea=f.idarea
left join famigliaprodotto fp on v.idfamiglia=fp.idfamiglia
left join compagnia cp on c.iddealer=cp.idcompagnia
left join cliente cli on cli.idcliente=c.idcliente
left join cliente cliV on cliV.IdCliente=c.IdVenditore
left join cliente cliPV on cliPV.IdCliente=c.IdPuntoVendita
left join attributo att on att.IdAttributo=c.IdAttributo
left join statocontratto sc on sc.idstatocontratto=c.idstatocontratto
left join reparto r on r.idreparto=c.idagenzia
left join tipospeciale t on c.IdTipoSpeciale=t.IdTipoSpeciale
left join area ar on ar.idarea=cli.idarea
left join regolaripartizione rp ON rp.IdRegolaProvvigione=c.IdRegolaProvvigione;