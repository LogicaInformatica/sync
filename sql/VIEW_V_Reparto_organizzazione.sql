CREATE OR REPLACE VIEW v_reparto_organizzazione
AS
select r.IdReparto,r.IdTipoReparto,r.IdCompagnia,r.CodUfficio,r.TitoloUfficio,r.NomeReferente,
r.Telefono as TelefonoRep,r.Fax as FaxRep,r.EmailReferente,r.EmailFatturazione,
case 
when r.FlagDelega is null then 'N' 
else r.FlagDelega end as FlagDelega,r.NomeBanca,
r.IBAN,r.Nota,r.TelefonoPerClienti,r.MaxSMSContratto,c.TitoloCompagnia,tr.CodTipoReparto,tr.TitoloTipoReparto
from reparto r 
left join compagnia c on(r.IdCompagnia=c.IdCompagnia)
left join tiporeparto tr on(r.IdTipoReparto=tr.IdTipoReparto);