CREATE OR REPLACE ALGORITHM=MERGE VIEW v_automatismi_tipi
AS
select 1 as IdTa,_latin1'email' as TipoAutomatismo, _latin1'Email di notifica' as TipoNominativo
union all
select 2, _latin1'emailComp', _latin1'Email di richiesta';