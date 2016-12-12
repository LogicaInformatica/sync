#
# Vista di comodo per estrazioni
#
CREATE OR REPLACE VIEW v_ultimo_recapito
AS
select IdCliente,CONCAT(Indirizzo,' - ',CAP,' ',Localita,' (',SiglaProvincia,')') AS Indirizzo, Telefono,Cellulare,Email
from recapito r
where IFNULL(FlagAnnullato,'N')='N' AND IdTipoRecapito IN (1,2,3) AND Indirizzo>''
AND NOT EXISTS (SELECT 1 FROM recapito x
                WHERE r.IdCliente=x.IdCLiente AND IFNULL(x.FlagAnnullato,'N')='N' AND  IdTipoRecapito IN (1,2,3) AND Indirizzo>''
                AND x.lastUpd>r.lastUpd)