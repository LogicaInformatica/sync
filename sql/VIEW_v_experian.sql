create or replace view v_experian
as
select cli.CodCliente,IF(CodContratto LIKE 'LE%','41','02') AS TipoCredito,
	CASE WHEN TitoloProdotto LIKE '%usato' THEN '03'
         WHEN TitoloProdotto LIKE '%nuovo%' THEN '02'
		 ELSE '15' 
	END AS MotivoFinanziamento,
    LEAST(3,SUM(c.NumInsoluti)) AS StatusPagamenti,
    MAX(Datediff(curdate(),c.DataRata)) as GiorniSconfino,
    ROUND(SUM(c.ImpInsoluto),0) AS Scaduto,
    case when substr(nominativo,1,3) IN ('DI ','DE ','DA ','LA ','LE ','LO ','LI ','AL ','EL ','IL ','MC ','D\' ') THEN substr(nominativo,1,locate(' ',nominativo,4)-1)
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
	END AS Nome,
    CodiceFiscale,PartitaIva,Sesso,DataNascita,LocalitaNascita,
    Indirizzo,Localita,ip.SiglaProvincia,Cap,
    RagioneSociale,c.*
FROM contratto c
JOIN cliente cli ON c.IdCliente=cli.IdCliente
JOIN prodotto p on c.IdProdotto=p.IdProdotto
LEFT JOIN v_indirizzo_principale ip ON ip.IdCliente=c.IdCliente
GROUP BY cli.IdCliente
HAVING SUM(c.ImpInsoluto)>26 
;
select * from v_experian limit 10;


