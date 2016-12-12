#
# view per separare nome e cognome
#
create or replace view v_separa_nome_cognome
as
select IdCliente,case when substr(nominativo,1,3) IN ('DI ','DE ','DA ','LA ','LE ','LO ','LI ','AL ','EL ','IL ','MC ','D\' ') THEN substr(nominativo,1,locate(' ',nominativo,4)-1)
     when substr(nominativo,1,4) IN ('DEL ','DAL ','DAI ') THEN substr(nominativo,1,locate(' ',nominativo,5)-1)
     when substr(nominativo,1,2) IN ('D ') THEN substr(nominativo,1,locate(' ',nominativo,3)-1)
     when substr(nominativo,1,6) IN ('DELLA ','DELLE ','DELLI ','DELLO ','DELL\' ','DALLA ','DEGLI ') THEN substr(nominativo,1,locate(' ',nominativo,7)-1)
     ELSE substr(nominativo,1,locate(' ',concat(nominativo,' '))-1)
END AS Cognome,
case when substr(nominativo,1,3) IN ('DI ','DE ','DA ','LA ','LE ','LO ','LI ','AL ','EL ','IL ','MC ','D\' ') THEN substr(nominativo,1+locate(' ',nominativo,4))
     when substr(nominativo,1,4) IN ('DEL ','DAL ','DAI ') THEN substr(nominativo,1+locate(' ',nominativo,5))
     when substr(nominativo,1,2) IN ('D ') THEN substr(nominativo,1+locate(' ',nominativo,3))
     when substr(nominativo,1,6) IN ('DELLA ','DELLE ','DELLI ','DELLO ','DELL\' ','DALLA ','DEGLI ') THEN substr(nominativo,1+locate(' ',nominativo,7))
     ELSE substr(nominativo,locate(' ',concat(nominativo,' '))+1)
END AS Nome
 from cliente where nominativo is not null;