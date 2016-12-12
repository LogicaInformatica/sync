#
# View usata da Overall per controllare che il numero contratto dato esista e sia a recupero
#
CREATE OR REPLACE VIEW v_controllo_contratto AS
select count(0) AS QuantiContratti,
substr(CodContratto,3,(locate('-',concat(CodContratto,'-'))-3)) AS NumContratto,contratto.*
from contratto
where ImpCapitale>=26 and CodContratto LIKE 'L%'
group by substr(CodContratto,3)