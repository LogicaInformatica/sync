CREATE OR REPLACE VIEW v_dati_utente
AS
SELECT u.IdUtente, u.CodUtente, u.NomeUtente, u.Userid, u.Password, u.IdReparto, u.Cellulare, u.Email, 
		  su.codStatoUtente, su.TitoloStatoUtente,
		  r.idTipoReparto, r.idCompagnia,
		  c.codCompagnia, c.idTipocompagnia,tc.MainFile,(case when c.idtipocompagnia!=1 then 'E' else 'I' end) as InternoEsterno
		  FROM utente u
          LEFT JOIN statoutente su ON u.IdStatoUtente=su.IdStatoUtente
          LEFT JOIN reparto r ON u.idreparto=r.idreparto
          LEFT JOIN compagnia c ON r.idCompagnia=c.idcompagnia
          LEFT JOIN tipocompagnia tc ON c.IdTipoCompagnia=tc.IdTipoCompagnia