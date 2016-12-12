CREATE OR REPLACE VIEW v_societa_organizzazione
AS
Select cm.IdCompagnia, cm.CodCompagnia, cm.TitoloCompagnia, cm.IdTipoCompagnia, cm.SiglaProvincia,
pr.TitoloProvincia, cm.NomeTitolare, cm.Indirizzo, cm.Cap, cm.Localita, cm.Telefono, cm.Fax, cm.EmailTitolare,
cm.lastUser,cm.lastUpd
from compagnia cm
left join provincia pr on(pr.SiglaProvincia=cm.SiglaProvincia)
order by cm.TitoloCompagnia; 