CREATE OR REPLACE VIEW v_incassi AS
SELECT
   i.IdIncasso as IdIncasso,
   i.IdContratto as IdContratto,
   c.CodContratto as CodContratto,
   c.IdCompagnia as IdCompagnia,
   t.TitoloTipoIncasso as TitoloTipoIncasso,
   t.IdTipoIncasso as IdTipoIncasso,
   i.NumDocumento as NumDocumento,
   i.DataRegistrazione as Data,
   i.DataDocumento as DataDocumento,
   a.UrlAllegato as UrlAllegato,
   i.IdAllegato as IdAllegato,
   i.ImpPagato as ImpPagato,
   i.ImpCapitale as IncCapitale,
   i.ImpInteressi as IncInteressi,
   i.ImpSpese as IncSpese,
   i.ImpAltriAddebiti as IncAltriAddebiti,
   d.Capitale as InsCapitale,
   d.InteressiMora as InsInteressiMora,
   d.AltriAddebiti as InsAltriAddebiti,
   d.Speseincasso as InsSpeseInscasso,
   i.Nota as Nota,
   c.ImpInsoluto as ImpInsoluto,
   ifnull(cl.Nominativo,cl.RagioneSociale) AS NomeCliente,
   i.IdUtente as IdUtenteInc,
   u.Userid as UtenteInc,
   r.IdReparto as IdRepartoInc,
   r.TitoloUfficio as RepartoInc,
   c.DataFineAffido as DataFineAffido,
   i.FlagModalita as FlagModalita,
   i.IdDistinta as IdDistinta,
   CONCAT('Fino al ',DATE_FORMAT(DataFineAffido,'%d/%m')) AS Lotto


FROM
   incasso i left join contratto c on i.IdContratto=c.IdContratto
             left join cliente cl on cl.IdCliente = c.IdCliente
             left join utente u on u.IdUtente = i.IdUtente
             left join reparto r on r.IdReparto=u.IdReparto
             left join tipoincasso t on i.IdTipoIncasso=t.IdTipoIncasso
             left join v_dettaglio_insoluto d on d.idcontratto=i.IdContratto
             left join allegato a on a.IdAllegato=i.IdAllegato;