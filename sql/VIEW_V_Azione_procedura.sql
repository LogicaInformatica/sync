CREATE OR REPLACE ALGORITHM=MERGE VIEW v_azione_procedura
AS
select ap.IdProcedura,az.*,case when az.DataFin>=date(now()) then 'Y' else 'N' end as Attiva,
case when (sa.idstatorecupero is not null and sa.condizione is null) then
        concat('Stato: ',sr.titolostatorecupero)
     when (sa.idstatorecupero is null and sa.condizione is not null) then
        sa.condizione
     when (sa.idstatorecupero is not null and sa.condizione is not null) then
            concat('Stato: ',sr.titolostatorecupero,' e ',sa.condizione)
        else 
            '' 
end as condizione,
case az.tipoformazione 
	when 'Annulla' then 'Annullamento' 
	when 'Autorizza' then 'Approvazione' 
	when 'Base' then 'Semplice' 
	when 'Data' then 'Con data'
	when 'InoltroWF' then 'Inoltro notifica'
	when 'Rifiuta' then 'Rifiuto'
end as tipoazione
from azioneprocedura ap left join azione az on(az.idazione=ap.idazione) 
left join statoazione sa on(az.idazione=sa.idazione)
left join statorecupero sr on(sr.idstatorecupero=sa.idstatorecupero);