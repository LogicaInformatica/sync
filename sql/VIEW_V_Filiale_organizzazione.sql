CREATE OR REPLACE VIEW v_filiale_organizzazione
AS
SELECT f.IdFiliale,f.CodFiliale,f.TitoloFiliale,f.IdArea,f.MailPrincipale,
f.MailResponsabile,a.CodArea,a.TitoloArea,a.TipoArea
FROM filiale f
left join Area a on(f.IdArea=a.IdArea);