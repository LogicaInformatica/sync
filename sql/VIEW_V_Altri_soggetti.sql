CREATE OR REPLACE VIEW v_altri_soggetti AS select 
c.IdCliente AS IdCliente,
cp.IdContratto AS IdContratto,
r.IdRecapito AS IdRecapito,
r.modificabile AS modificabile,
r.IdTipoRecapito AS IdTipoRecapito,
r.TitoloTipoRecapito AS TitoloTipoRecapito,
r.ProgrRecapito AS ProgrRecapito,
r.Nome AS Nome,
r.Indirizzo AS Indirizzo,
r.Localita AS Localita,
r.CAP AS CAP,
r.SiglaProvincia AS SiglaProvincia,
r.SiglaNazione AS SiglaNazione,
r.Telefono AS Telefono,
r.Cellulare AS Cellulare,
r.Fax AS Fax,
r.Email AS Email,
concat(ifnull(c.Nominativo,c.RagioneSociale),_latin1' (',t.TitoloTipoControparte,_latin1') Cod.Fiscale: ',IFNULL(c.CodiceFiscale,'n/d')) AS Controparte, 
ifnull(c.Nominativo,c.RagioneSociale) AS Soggetto,
t.CodTipoControparte AS CodTipoControparte
from (((controparte cp join cliente c on((cp.IdCliente = c.IdCliente))) 
left join tipocontroparte t on((t.IdTipoControparte = cp.IdTipoControparte))) 
left join v_recapito r on((c.IdCliente = r.IdCliente))) 
where (now() between cp.DataIni and cp.DataFin) 
order by cp.IdContratto,t.TitoloTipoControparte,Soggetto;