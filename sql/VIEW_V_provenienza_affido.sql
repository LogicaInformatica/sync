#
# Vista usata per riempire la colonna "provenienza" della lista "dettaglio provvigioni" di rinegoziazione
#
CREATE OR REPLACE VIEW v_provenienza_affido
AS
select a.idcontratto,a.idprovvigione,
CASE WHEN aprev.idaffidoforzato=a.idregolaprovvigione then 'Forzatura affido'
     WHEN s2.idstoriarecupero>0 then 'Affido manuale'
     WHEN s1.idstoriarecupero>0 then 'Proposta rinegoziazione'
     ELSE 'Affido automatico'
END Provenienza
from assegnazione a
left join assegnazione aprev ON a.idcontratto=aprev.idcontratto and a.datafineaffidocontratto>aprev.datafineaffidocontratto and a.idprovvigione!=aprev.idprovvigione
   and aprev.idagenzia>0 and not exists (select 1 from assegnazione x
       where a.idcontratto=x.idcontratto and a.datafineaffidocontratto>x.datafineaffidocontratto and a.idprovvigione!=x.idprovvigione
       and x.idagenzia>0 and x.idassegnazione>aprev.idassegnazione)
left join storiarecupero s1 ON s1.idcontratto=a.idcontratto and s1.idazione=408
                            and s1.dataevento BETWEEN a.datainizioaffidocontratto - INTERVAL 1 MONTH AND a.datainizioaffidocontratto
left join storiarecupero s2 ON s2.idcontratto=a.idcontratto and s2.idazione in (5,9,10,510)
                            and DATE(s2.dataevento) BETWEEN a.datainizioaffidocontratto - INTERVAL 3 DAY AND a.datainizioaffidocontratto
where a.idregolaprovvigione=1200 and not exists
  (select 1 from assegnazione y where y.idcontratto=a.idcontratto and y.idprovvigione=a.idprovvigione and y.idassegnazione<a.idassegnazione)
;