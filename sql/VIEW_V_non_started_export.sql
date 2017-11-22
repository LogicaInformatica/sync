CREATE OR REPLACE VIEW v_non_started_export
AS
SELECT c.IdContratto,
IF(cli.DataNascita>'',cli.DataNascita,'n/d') as DataNascitaCliente,
IF(c.IdVenditore>0,ifnull(cli.Nominativo, cli.RagioneSociale),'') as Venditore,
ts.CodTipoSpeciale as CodOverride,
ac.Prodotto as ServiziAssicurativi,
IF(cli.CodFormaGiuridica>'',concat(tc.TitoloTipoCliente, ' - ', cli.CodFormaGiuridica),tc.TitoloTipoCliente) as TipoAnagrafica,
IF(cli.CodiceFiscale>'',cli.CodiceFiscale,'') AS CodFiscGarante,
IF(c.IdDealer>0, cm.SiglaProvincia, '') as ProvinciaDealer,
substr(CodContratto,3,length(CodContratto)) as Pratica,
p.TitoloProdotto as DescProdotto,
ts.TitoloTipoSpeciale as DescOverride
from contratto c
left join tipospeciale ts on c.IdTipoSpeciale = ts.IdTipoSpeciale
left join accessorio ac on ac.IdContratto = c.IdContratto
left join cliente cli on cli.IdCliente = c.IdVenditore
left join tipocliente tc on tc.IdTipoCliente = cli.IdTipoCliente
left join compagnia cm on cm.IdCompagnia = c.IdDealer
left join controparte co on co.idcliente = cli.idcliente
left join tipocontroparte tcr on co.idtipocontroparte=tcr.idtipocontroparte AND tcr.FlagGarante='Y'
left join prodotto p on p.IdProdotto = c.IdProdotto
group by c.IdContratto