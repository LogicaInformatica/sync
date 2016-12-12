create or replace view v_insoluti_count_opt
as
select co.*,v.stato,
v.classif,v.FlagNoAffido,v.IdReparto,v.Categoria,
v.InRecupero,DATEDIFF(CURDATE(), DataRata) AS giorni,v.AbbrClasse,v.tipoPag,
v.agenzia,co.CodContratto AS numPratica,co.IdStatoRinegoziazione AS FlagRinegoziazione,v.IdFamiglia
from contratto co
JOIN _opt_insoluti v ON v.IdContratto=co.IdContratto;

