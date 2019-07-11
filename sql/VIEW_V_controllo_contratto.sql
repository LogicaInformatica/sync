#
# View usata da Overall per controllare che il numero contratto dato esista e sia a recupero
# 2019-07-11 left join aggiunta da Claudio De Santis in produzione
CREATE OR REPLACE VIEW v_controllo_contratto AS
select count(0) AS QuantiContratti,
substr(CodContratto,3,(locate('-',concat(CodContratto,'-'))-3)) AS NumContratto,
contratto.*,
`reparto`.`EmailReferente`  AS `EmailReferenteAgenzia`
from contratto
LEFT OUTER JOIN `reparto` on `reparto`.`IdReparto` = `contratto`.`IdAgenzia`
where ImpCapitale>=26 and CodContratto LIKE 'L%'
group by substr(CodContratto,3);


`reparto`.`EmailReferente`  AS `EmailReferenteAgenzia`

from `contratto` LEFT OUTER JOIN `reparto`

on `reparto`.`IdReparto` = `contratto`.`IdAgenzia`