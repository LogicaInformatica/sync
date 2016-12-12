CREATE OR REPLACE ALGORITHM=MERGE VIEW v_azioni_semplici_forms
AS
select 'Base' as IdFormA,'Semplice' as TipoFormAzione
union all
select 'Data','Con data'
union all
select 'Esito','Con esito' 
union all
select 'EsitoNegativo','Con esito negativo'
union all
select 'InviatoSMS','Invio di sms' 
union all
select 'InvioEmail','Invio di e-mail' 
order by TipoFormAzione Asc