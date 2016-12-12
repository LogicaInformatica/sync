# versione per Storico: attenzione al nome schema
#
# 4/4/2010: modificata per listare le sole rate degne di nota, ma sommare invece tutte le rate a debito, totale che
# viene usato nella v_contratto_lettera
#
create or replace view db_cnc_storico.v_lista_rate
as
select idcontratto,group_concat(IF(impinsoluto>5 and impcapitale>0,numrata,null) separator ', ') as ListaRate,
count(IF(impinsoluto>5 and impcapitale>0,1,null)) as NumRate,
 MIN(IF(impinsoluto>5 and impcapitale>0,DataInsoluto,null)) AS PrimaData,
SUM(ImpInsoluto) AS TotaleSoloDebito
from db_cnc_storico.insoluto
where numrata!=0 and impinsoluto!=0
group by idcontratto;