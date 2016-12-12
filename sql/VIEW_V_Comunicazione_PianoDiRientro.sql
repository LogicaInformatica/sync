CREATE OR REPLACE VIEW v_comunicazione_pianodirientro AS
select DATE_FORMAT(CURDATE(),'%d %M %Y') AS Oggi, 
c.IdContratto, c.CodContratto,
IFNULL(cli.Nominativo,cli.RagioneSociale) AS Intestatario,
cli.Nominativo AS NomePersonaFisica,
r.Indirizzo,
r.Cap,r.Localita,
r.SiglaProvincia,
CASE WHEN c.ImpSaldoStralcio IS NULL then replace(replace(replace(format((IFNULL(c.ImpDebitoResiduo,0)+IFNULL(c.ImpInsoluto,0)),2),'.',';'),',','.'),';',',') 
else replace(replace(replace(format(IFNULL(c.ImpSaldoStralcio,0),2),'.',';'),',','.'),';',',') END as Capitale, 
SUBSTRING(c.CodContratto, 3, LENGTH(c.CodContratto)) AS numFinanziamento,
replace(replace(replace(format(IFNULL(pr.ImpInizialeCapitale,0)+IFNULL(pr.ImpInizialeSpeseRec,0)
+IFNULL(pr.ImpInizialeSpeseLeg,0)+IFNULL(pr.ImpInizialeAltriAdd,0)+IFNULL(pr.ImpInizialeInteressi,0),2),'.',';'),',','.'),';',',') AS ImpInsolutoIT,
replace(replace(replace(format(IFNULL(pr.PrimoImporto,0),2),'.',';'),',','.'),';',',') AS PrimoImportoIT,
DATE_FORMAT(pr.DataPagPrimoImporto,'%d/%m/%Y') AS DataPagamentoPrimoImporto,
pr.NumeroRate,
replace(replace(replace(format(IFNULL(pr.ImportoRata,0),2),'.',';'),',','.'),';',',') AS importoRata,
DATE_FORMAT(pr.DecorrenzaRate,'%d/%m/%Y') AS DecorrenzaRata,
DATE_FORMAT(pr.DecorrenzaRate + INTERVAL (pr.NumeroRate-1) MONTH,'%d/%m/%Y') AS DataRataFinale

from contratto c
join cliente cli ON c.IdCliente=cli.IdCliente
JOIN recapito r ON c.IdCliente=r.IdCliente AND r.IdTipoRecapito=1
join pianorientro pr on c.IdContratto = pr.IdContratto
AND (indirizzo>'')
AND NOT EXISTS (SELECT 1 FROM recapito x WHERE c.IdCliente=x.IdCliente
                AND x.IdTipoRecapito=1 
                AND (indirizzo>'')
                AND x.IdRecapito>r.IdRecapito);