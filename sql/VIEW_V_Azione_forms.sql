CREATE OR REPLACE ALGORITHM=MERGE VIEW v_azione_forms
AS
select 'Annulla' as IdFormA,'Annullamento' as TipoFormAzione
union all
select 'Autorizza','Approvazione'
union all
select 'Base','Semplice'
union all
select 'Data','Con data' 
union all
select 'InoltroWF','Inoltro notifica' 
union all
select 'Rifiuta','Rifiuto'
union all
select 'ImportoResiduo','Saldo e Stralcio'
union all
select 'SaldoStralcioDifferito','Saldo e Stralcio Diff.'
union all
select 'BaseConImporto','Semplice con Importo'
order by TipoFormAzione Asc