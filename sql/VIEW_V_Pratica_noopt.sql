# versione non ottimizzata di v_pratiche, serve in engine_func per non dover dipendere, durante il batch, dal grado di aggiornamento
# della tabella opt_insoluti
create or replace view v_pratica_noopt
as
select a.titoloarea as area,concat(titolofamiglia,' - ',titoloprodotto) as Prodotto,TitoloCompagnia as Venditore,
       codtipopagamento as TipoPagamento,cli.CodCliente,ifnull(cli.Nominativo,cli.RagioneSociale) as NomeCliente,ifnull(cli.DataNascita,'Data assente') as DataNCli,
       cli.LocalitaNascita as LuogoNCli,codstatocontratto as CodStato,titolostatocontratto as Stato,CodClasse,TitoloClasse as Classificazione,
       codstatorecupero as CodStatoRecupero,titolostatorecupero as StatoRecupero,
       CodUtente,NomeUtente,TitoloUfficio As NomeAgenzia,
       c.NumRata as Rata,NumInsoluti as Insoluti,Datediff(curdate(),DataRata) as Giorni,c.ImpInsoluto as Importo,DataRata as DataScadenza,fp.IdFamiglia,cli.IdArea as AreaCliente,cli.IdTipoCliente,
       cli.IdArea AS IdAreaCliente,fp.IdFamigliaParent,TitoloTipoSpeciale as TitTipoSpec,ifnull(cliV.Nominativo,cliV.RagioneSociale) as NomeVenditore,ifnull(cliPV.Nominativo,cliPV.RagioneSociale) as NomePuntoVendita,TitoloAttributo as Attributo,
       c.*,cli.sesso,cli.CodiceFiscale,cli.PartitaIVA,
       cat.TitoloCategoria,cl.FlagCambioAgente,ar.titoloarea as AreaIntest,
       CAST(c.DataRata as char) as Riferimento, # per uso nella eseguiAutomatismi
       cli.FlagIrreperibile 
From contratto c
left join filiale f on c.idfiliale=f.idfiliale
left join area a on a.idarea=f.idarea
left join prodotto p on p.idprodotto=c.idprodotto
left join famigliaprodotto fp on p.idfamiglia=fp.idfamiglia
left join compagnia cp on c.iddealer=cp.idcompagnia
left join tipopagamento tpag on tpag.idtipopagamento=c.idtipopagamento
left join cliente cli on cli.idcliente=c.idcliente
left join cliente cliV on cliV.IdCliente=c.IdVenditore
left join cliente cliPV on cliPV.IdCliente=c.IdPuntoVendita
left join attributo att on att.IdAttributo=c.IdAttributo
left join statocontratto sc on sc.idstatocontratto=c.idstatocontratto
left join statorecupero sr on sr.idstatorecupero=c.idstatorecupero
left join classificazione cl on cl.idclasse=c.idclasse
left join utente u on u.idutente=c.idoperatore
left join reparto r on r.idreparto=c.idagenzia
left join tipospeciale t on c.IdTipoSpeciale=t.IdTipoSpeciale
left join categoria cat on c.IdCategoria = cat.IdCategoria
left join area ar on ar.idarea=cli.idarea; 