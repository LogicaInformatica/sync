CREATE OR REPLACE VIEW v_cessioni_con_garante
AS
select c.IdContratto,CodContratto AS numPratica,cl.IdCliente,CASE SUBSTR(CodContratto,1,2) WHEN 'LO' THEN 'CO' ELSE 'LE' END AS Modulo,
SUBSTR(CodContratto,3) AS Pratica,NumRate,NumRatePagate,DataDBT,ImpDBT,ImpCapitale,
IFNULL(Nominativo,RagioneSociale) AS Cliente,DataNascita,IFNULL(CodiceFiscale,PartitaIva) AS CodiceFiscale,
r1.Indirizzo,r1.Localita,r1.Cap,r1.Telefono,IFNULL(r2.telefono,r2.cellulare) as Telefono2,r1.Cellulare,IFNULL(r3.telefono,r3.cellulare) as TelefonoSede,
CodCompagnia AS CodConvenzionato,TitoloCompagnia AS Convenzionato,TitoloTipoControparte,IdStatoRecupero,
DataContratto AS DataLiquidazione,ImpFinanziato AS Finanziato
FROM contratto c
join controparte cnt ON c.IdContratto=cnt.IdContratto
join cliente cl ON cnt.IdCliente=cl.IdCliente
JOIN tipocontroparte tc ON tc.idtipocontroparte=cnt.idtipocontroparte and flaggarante='Y'
LEFT JOIN compagnia co ON c.IdDealer=co.IdCompagnia
LEFT JOIN v_num_rate_pagate nrp ON c.IdContratto=nrp.IdContratto
LEFT JOIN v_recapito_di_tipo r1 ON r1.IdCliente=cl.IdCliente AND r1.IdTipoRecapito=1
LEFT JOIN v_telefono_di_tipo r2 ON r2.IdCliente=cl.IdCliente AND r2.IdTipoRecapito in (2,99)
LEFT JOIN v_telefono_di_tipo r3 ON r3.IdCliente=cl.IdCliente AND r3.IdTipoRecapito in (2,5);