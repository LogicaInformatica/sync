CREATE OR REPLACE VIEW v_contratto_lettera
AS
select DATE_FORMAT(CURDATE(),'%d/%m/%Y') AS Oggi,
       IFNULL(cl.Nominativo,cl.RagioneSociale) AS Intestatario,
CASE WHEN cl.Nominativo IS NOT NULL THEN
	CASE WHEN cl.Sesso IS NULL THEN if(SUBSTRING(cl.CodiceFiscale,10,2)>40,'Gentile Signora','Egregio Signore')
		 WHEN cl.Sesso='M' THEN 'Egregio Signore'
     	 ELSE 'Gentile Signora' END
     WHEN rl.Nome IS NOT NULL THEN 'Egregio Signore / Gentile Signora'
     ELSE 'Spet.le' END  AS Titolo,
CASE WHEN cl.Nominativo IS NOT NULL THEN cl.Nominativo
     WHEN rl.Nome IS NOT NULL THEN rl.Nome
     ELSE cl.RagioneSociale END AS RapprLegale,
CASE WHEN cl.Nominativo IS NOT NULL THEN rp.Indirizzo
     WHEN rl.Indirizzo >'' THEN rl.Indirizzo
     ELSE rp.Indirizzo END AS Indirizzo,
LPAD(CASE WHEN cl.Nominativo IS NOT NULL THEN rp.CAP
     WHEN rl.Indirizzo >''  THEN rl.CAP
     ELSE rp.CAP END,5,'0') AS Cap,
CASE WHEN cl.Nominativo IS NOT NULL THEN rp.Localita
     WHEN rl.Indirizzo >''  THEN rl.Localita
     ELSE rp.Localita END AS Localita,
CASE WHEN cl.Nominativo IS NOT NULL THEN rp.SiglaProvincia
     WHEN rl.Indirizzo >''  THEN rl.SiglaProvincia
     ELSE rp.SiglaProvincia END AS SiglaProvincia,
RIGHT(fp.CodFamiglia,2) AS CodProdotto,
cl.Nominativo AS NomePersonaFisica,IFNULL(cl.PartitaIVA,cl.CodiceFiscale) AS CodFiscalePartitaIVA,
replace(replace(replace(format(IFNULL(if (impDBT>0,0,c.ImpAltriAddebiti),0),2),'.',';'),',','.'),';',',') AS ImpAltriAddebitiIT,
replace(replace(replace(format(IFNULL(if(impInteressiMaturati>0,impInteressiMaturati,im.InteressiMora),0),2),'.',';'),',','.'),';',',') AS ImpInteressiMoraIT,
replace(replace(replace(format(IFNULL(c.ImpSpeseRecupero,0),2),'.',';'),',','.'),';',',') AS ImpSpeseRecuperoIT,
replace(replace(replace(format(IFNULL(if (impDBT>0,impDBT,c.ImpInsoluto),0),2),'.',';'),',','.'),';',',') AS ImpInsolutoIT,
replace(replace(replace(format(IFNULL(c.ImpFinanziato,0),2),'.',';'),',','.'),';',',') AS ImpFinanziatoIT,
replace(replace(replace(format(IFNULL(if (impDBT>0,impDBT,c.ImpCapitale),0)+IFNULL(c.ImpSpeseRecupero,0)+
IFNULL(if(impInteressiMaturati>0,impInteressiMaturati,im.InteressiMora),0)+IFNULL(if (impDBT>0,0,c.ImpAltriAddebiti),0),2),'.',';'),',','.'),';',',') AS TotaleDovuto,
# aggiunta 4/4/2012
replace(replace(replace(format(IFNULL(c.ImpSpeseRecupero,0)+
IFNULL(if(impInteressiMaturati>0,impInteressiMaturati,im.InteressiMora),0)+IFNULL(if (impDBT>0,impDBT,TotaleSoloDebito),0),2),'.',';'),',','.'),';',',') AS TotaleSoloDebito,
# aggiunta 15/5/2012
replace(replace(replace(format(IFNULL(if (impDBT>0,impDBT,TotaleSoloDebito),0),2),'.',';'),',','.'),';',',') AS CapitaleSoloDebito,
replace(convert(concat(b.TitoloBanca,' - ',b.TitoloAgenzia) USING utf8),'°','.') as DenomBanca,
replace(replace(replace(format(c.ImpCapitale,2),'.',';'),',','.'),';',',') AS Importo,
c.NumRata as Rata,
DATE_FORMAT(DataRata,'%e/%m/%Y') AS DataScadenza,
IFNULL(r.TelefonoPerClienti,IFNULL(r.telefono,IFNULL(main.telefonoPerClienti,main.telefono))) as TelAgenzia, 
IF(c.IdAgenzia IS NOT NULL,r.EmailReferente,main.EmailReferente) as EmailAgenzia, IFNULL(r.Fax,'____________') as FaxAgenzia,
r.TitoloUfficio AS Agenzia, c.*,SUBSTR(CodContratto,3) AS CodContrattoRidotto, 
CONCAT(IF(SUBSTR(CodContratto,1,2)='LO','CO00000000','LE000000000'),SUBSTR(CodContratto,3)) AS CodIndividuale,
DATE_FORMAT(c.DataContratto,'%d/%m/%Y') as DataContrattoIT,
DATE_FORMAT(c.DataInizioAffido,'%e/%m/%Y') as DataInizioAffidoIT, DATE_FORMAT(c.DataFineAffido,'%d/%m/%Y') as DataFineAffidoIT,
DATE_FORMAT(c.DataDecorrenza,'%e/%m/%Y') as DataDecorrenzaIT, DATE_FORMAT(IFNULL(c.DataChiusura,c.DataUltimaScadenza),'%d/%m/%Y') as DataChiusuraIT,
DATE_FORMAT(cl.DataNascita,'%e/%m/%Y') AS DataNascitaIT, cl.LocalitaNascita, cl.CodiceFiscale, rp.Telefono, rp.Cellulare, tp.TitoloTipoPagamento,
substring(c.CodContratto,1,2) AS SiglaProd,
concat(d.TitoloCompagnia,ifnull(concat(' - ',d.Indirizzo),''),ifnull(concat(' - ',ifnull(d.CAP,''),' ',d.Localita,ifnull(concat(' (',d.SiglaProvincia,')'),'')),''))as Dealer,
p.CodMarca as CodBrand,
vrm.Ruolo as RuoloGar,
CONCAT('e p.c.\r\n',vrm.Soggetto) as SoggettoGarPc,
vrm.Soggetto as SoggettoGar,
vrm.Indirizzo as IndirizzoGar,
vrm.CAP as CAPGar,
vrm.Localita as LocalitaGar,
vrm.SiglaProvincia as SiglaProvinciaGar,
lr.PrimaData,
lr.ListaRate,CASE WHEN lr.NumRate>1 THEN CONCAT('Rate nn. ',ListaRate,' - Decorrenza ',DATE_FORMAT(PrimaData,'%e/%m/%Y'))
                             WHEN lr.NumRate=1 THEN CONCAT('Rata n. ',ListaRate,' - Scadenza ',DATE_FORMAT(PrimaData,'%e/%m/%Y'))
                             ELSE '' END AS IndicaRate
,IFNULL(u.NomeUtente,r.NomeReferente) AS Agente, IF(u.Telefono>' ',u.Telefono,IFNULL(r.TelefonoPerClienti,r.Telefono)) AS TelAgente,
IF(RagioneSociale>'','Spett.le','Preg.') AS AppellativoCorto,
IF(RagioneSociale>'','Spett.le Societa''','Preg. Sig./Sig.ra') AS AppellativoLungo,
IF(RagioneSociale>'','Vi','Le') AS PronomeCorto,
IF(RagioneSociale>'','Voi','Lei') AS PronomeLungo,
IF(RagioneSociale>'','Vostri','Suoi') AS AggettivoPlurale,
IF(RagioneSociale>'','Vostro','Suo') AS AggettivoSingolare,
IF(RagioneSociale>'','potrete','potra''') AS VerboPotere,
# Da migliorare mettendo i cedenti nella tab Compagnia e collegando i contratti, per ora va bene solo per IPIFinance/Unicredit
SUBSTR(DescrBene,INSTR(DescrBene,'Cedente:')+9,INSTR(DescrBene,'\n')-INSTR(DescrBene,'Cedente:')-9) AS Cedente,
# 2018-10-05 campi per maxirata
replace(replace(replace(format(fin.ImpDebitoIniziale,2),'.',';'),',','.'),';',',') RataFinale, 
DATE_FORMAT(fin.DataInsoluto,'%e/%m/%Y') AS DataRataFinale

FROM contratto c
JOIN cliente cl ON cl.IdCliente=c.IdCliente
LEFT JOIN v_interessi_mora im ON c.IdContratto=im.IdContratto
LEFT JOIN v_lista_rate lr ON c.IdContratto=lr.IdContratto
LEFT JOIN v_recapito rl ON c.IdCliente=rl.IdCliente AND rl.CodTipoRecapito='LEG'
LEFT JOIN v_recapito rp ON c.IdCliente=rp.IdCliente AND  rp.CodTipoRecapito='BASE'
LEFT JOIN insoluto as fin ON fin.IdContratto=c.IdContratto AND fin.NumRata=c.Numrate
LEFT JOIN prodotto p on p.idprodotto=c.idprodotto
LEFT JOIN famigliaprodotto fp on p.idfamiglia=fp.idfamiglia
LEFT JOIN reparto r on c.IdAgenzia=r.IdReparto
LEFT JOIN tipopagamento tp on tp.IdTipoPagamento=c.IdTipoPagamento
LEFT JOIN banca b on b.ABI=c.ABI and b.CAB=c.CAB
LEFT JOIN compagnia d on d.IdCompagnia=c.IdDealer
LEFT JOIN utente u ON u.IdUtente=IFNULL(c.IdAgente,c.IdOperatore) #aggiunto il 17/12/2015
LEFT JOIN v_recapiti_mandato vrm on c.IdContratto=vrm.IdContratto and vrm.Ruolo!='Cliente' && vrm.FlagGarante='Y'
JOIN reparto main ON main.idReparto=1;


select * from v_contratto_lettera  where idcliente=28648;


select 'à', CONVERT('à' USING  UTF8), CONVERT('à' USING  LATIN1)