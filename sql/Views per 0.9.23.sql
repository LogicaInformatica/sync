CREATE OR REPLACE VIEW v_graph_target
AS
SELECT FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,ROUND(SUM(IPR)/COUNT(*),2) AS IPR,ROUND(SUM(IPF)/COUNT(*),2) AS IPF,
DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,idregolaprovvigione
FROM v_graph_target_lotto
group by Ordine,CodRegolaProvvigione,mese
order by Ordine,CodRegolaProvvigione;

CREATE OR REPLACE VIEW v_graph_target
AS
SELECT FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,ROUND(SUM(IPR)/COUNT(*),2) AS IPR,ROUND(SUM(IPF)/COUNT(*),2) AS IPF,
DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,idregolaprovvigione
FROM v_graph_target_lotto
group by Ordine,CodRegolaProvvigione,mese
order by Ordine,CodRegolaProvvigione;

CREATE OR REPLACE VIEW v_graph_target_fy
AS
SELECT FasciaRecupero,TitoloRegolaProvvigione,Agenzia,TargetIPR,ROUND(SUM(IPR)/COUNT(*),2) AS IPR,ROUND(SUM(IPF)/COUNT(*),2) AS IPF,
FY
FROM v_graph_target_lotto
group by FY,Ordine,CodRegolaProvvigione
order by FY,Ordine,CodRegolaProvvigione;

CREATE OR REPLACE VIEW v_graph_provvigione
AS
SELECT FasciaRecupero,DATE_FORMAT(DataFineAffido,'%Y%m') as Mese,IdReparto,CodRegolaProvvigione,
Agenzia,
SUM(NumAffidati) AS NumAffidati,sum(NumIncassati) as NumIncassati,
sum(ImpCapitaleAffidato) as ImpCapitaleAffidato,sum(ImpCapitaleIncassato) as ImpCapitaleIncassato,
concat('€ ',replace(format(sum(impCapitaleAffidato),0),',','.'),'\n  (',sum(NumAffidati),')') AS LabelAffidato,
concat('€ ',replace(format(sum(ImpCapitaleIncassato),0),',','.'),'\n  (',sum(NumIncassati),')') AS LabelIncassato,
ROUND(SUM(IPR)/COUNT(*),2) AS IPR,ROUND(SUM(IPM)/COUNT(*),2) AS IPM
FROM v_graph_target_lotto
group by 1,2,3;

CREATE OR REPLACE VIEW v_graph_target_lotto
AS
SELECT r.FasciaRecupero,r.TitoloRegolaProvvigione,concat(a.TitoloUfficio,' (',CodRegolaProvvigione,')') AS Agenzia,
valore AS TargetIPR,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPR,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleRealeIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPF,
CASE WHEN sum(NumAffidati)=0 THEN 0 ELSE ROUND(sum(NumIncassati)*100./sum(NumAffidati),2) END AS IPM,
SUM(NumAffidati) AS NumAffidati,sum(NumIncassati) as NumIncassati,
sum(ImpCapitaleAffidato) as ImpCapitaleAffidato,sum(ImpCapitaleIncassato) as ImpCapitaleIncassato,
p.DataFin as DataFineAffido,r.idregolaprovvigione,CodRegolaProvvigione,t.Ordine,a.IdReparto,FY
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
JOIN reparto a ON a.IdReparto=p.IdReparto
LEFT JOIN target t ON r.FasciaRecupero=t.FasciaRecupero and DATE_FORMAT(p.datafin,'%Y%m') between (FY-1)*100+4 AND FY*100+3
group by t.ordine,CodRegolaProvvigione,DataFineAffido;

CREATE OR REPLACE VIEW v_sintesi_agenzia_storica
AS
select a.IdAgenzia,a.IdAgente,IFNULL(u.NomeUtente,'[non assegnate]') as Agente,count(distinct a.idcontratto) as NumInsoluti,
COUNT(DISTINCT sr.idcontratto) as Trattati,
count(distinct a.idcontratto)-COUNT(DISTINCT sr.idcontratto) as DaTrattare,SUM(ImpTotaleAffidato) AS ImpTotale,
sum(ImpCapitaleAffidato) AS ImpCapitale,sum(ImpPagato) AS ImpPagato,SUM(ImpPagatoTotale) AS ImpPagatoTotale,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(LEAST(i.ImpPagato,i.ImpCapitaleAffidato))*100./SUM(i.ImpCapitaleAffidato),2) END AS PercCapitale,
COUNT(sr.IdStoriaRecupero) AS NumAzioni,MAX(DataEvento) AS DataUltimaAzione,
YEAR(a.DataFin) AS Anno
from assegnazione a
LEFT JOIN utente u ON a.idAgente=u.IdUtente
LEFT JOIN storiarecupero sr ON sr.IdContratto=a.IdContratto and sr.idutente=a.idagente and sr.dataevento between a.dataini and a.datafin
JOIN v_importi_per_provvigioni_full i ON i.IdContratto=a.idcontratto AND datafineaffido=a.datafin
group by Anno,idagenzia,Agente;

CREATE OR REPLACE VIEW v_sintesi_agenzia
AS
select a.IdAgenzia,a.IdAgente,IFNULL(u.NomeUtente,'[non assegnate]') as Agente,a.datafin as DataFineAffido,count(distinct a.idcontratto) as NumInsoluti,
COUNT(DISTINCT sr.idcontratto) as Trattati,
count(distinct a.idcontratto)-COUNT(DISTINCT sr.idcontratto) as DaTrattare,SUM(ImpTotaleAffidato) AS ImpTotale,
sum(ImpCapitaleAffidato) AS ImpCapitale,sum(ImpPagato) AS ImpPagato,SUM(ImpPagatoTotale) AS ImpPagatoTotale,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(LEAST(i.ImpPagato,i.ImpCapitaleAffidato))*100./SUM(i.ImpCapitaleAffidato),2) END AS PercCapitale,
COUNT(sr.IdStoriaRecupero) AS NumAzioni,MAX(DataEvento) AS DataUltimaAzione,
CONCAT('Fino al ',DATE_FORMAT(a.DataFin,'%d/%m')) AS Lotto
from assegnazione a
LEFT JOIN utente u ON a.idAgente=u.IdUtente
LEFT JOIN storiarecupero sr ON sr.IdContratto=a.IdContratto and sr.idutente=a.idagente and sr.dataevento between a.dataini and a.datafin
JOIN v_importi_per_provvigioni_full i ON i.IdContratto=a.idcontratto AND datafineaffido=a.datafin
WHERE a.datafin>=curdate()
group by Lotto,idagenzia,Agente;

CREATE OR REPLACE VIEW v_agenzia_provv_plus
AS
SELECT -1 AS IdRegolaProvvigione," Elimina forzatura precedente" AS TitoloAgenzia
UNION ALL
SELECT 0," Al rientro, forza in lavorazione interna"
UNION ALL
SELECT IdRegolaProvvigione,
CONCAT(TitoloUfficio,
       CASE WHEN TitoloRegolaProvvigione IS NOT NULL THEN CONCAT(' [Codice ',CodRegolaProvvigione,' - ',TitoloRegolaProvvigione,']') ELSE '' END)
FROM reparto r LEFT JOIN regolaprovvigione rp ON r.IdReparto=rp.IdReparto
WHERE CURDATE() BETWEEN r.DataIni AND r.DataFin
AND CURDATE() BETWEEN rp.DataIni AND rp.DataFin
AND IdCompagnia IN (SELECT IdCompagnia FROM compagnia WHERE IdTipoCompagnia=2)
order by 2;

CREATE OR REPLACE VIEW v_contratto_workflow
AS
SELECT ct.*,po.IdFamiglia,fp.IdFamigliaParent,cl.IdTipoCliente,
(YEAR(CURDATE())-YEAR(DataPrimaScadenza))*12+MONTH(CURDATE())-MONTH(DataPrimaScadenza)-NumInsoluti AS RatePagate
FROM contratto ct
JOIN cliente cl          ON ct.IdCliente=cl.IdCliente
JOIN prodotto po         ON ct.IdProdotto=po.IdProdotto
JOIN famigliaprodotto fp ON po.IdFamiglia=fp.IdFamiglia
;

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

CREATE OR REPLACE ALGORITHM=MERGE VIEW v_azione_forms
AS
select 'Annullamento' as TipoFormAzione
union all
select 'Approvazione' 
union all
select 'Semplice' 
union all
select 'Con data' 
union all
select 'Inoltro notifica' 
union all
select 'Rifiuto'
order by TipoFormAzione Asc;

CREATE OR REPLACE ALGORITHM=MERGE VIEW v_automatismi_tipi
AS
select 1 as IdTa,_latin1'email' as TipoAutomatismo, _latin1'Email di notifica' as TipoNominativo
union all
select 2, _latin1'emailComp', _latin1'Email di richiesta';

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
replace(replace(replace(format(IFNULL(c.ImpAltriAddebiti,0),2),'.',';'),',','.'),';',',') AS ImpAltriAddebitiIT,
replace(replace(replace(format(IFNULL(c.ImpInteressiMora,0),2),'.',';'),',','.'),';',',') AS ImpInteressiMoraIT,
replace(replace(replace(format(IFNULL(c.ImpSpeseRecupero,0),2),'.',';'),',','.'),';',',') AS ImpSpeseRecuperoIT,
replace(replace(replace(format(IFNULL(c.ImpInsoluto,0),2),'.',';'),',','.'),';',',') AS ImpInsolutoIT,
replace(replace(replace(format(IFNULL(c.ImpFinanziato,0),2),'.',';'),',','.'),';',',') AS ImpFinanziatoIT,
replace(replace(replace(format(IFNULL(c.ImpCapitale,0)+IFNULL(c.ImpSpeseRecupero,0)+
IFNULL(c.ImpInteressiMora,0)+IFNULL(c.ImpAltriAddebiti,0),2),'.',';'),',','.'),';',',') AS TotaleDovuto,
concat(b.TitoloBanca,' - ',b.TitoloAgenzia) as DenomBanca,
replace(replace(replace(format(c.ImpCapitale,2),'.',';'),',','.'),';',',') AS Importo,
c.NumRata as Rata,
DATE_FORMAT(DataRata,'%e/%m/%Y') AS DataScadenza,
IFNULL(r.TelefonoPerClienti,IFNULL(r.telefono,IFNULL(main.telefonoPerClienti,main.telefono))) as TelAgenzia, r.EmailReferente as EmailAgenzia, IFNULL(r.Fax,'____________') as FaxAgenzia,
r.TitoloUfficio AS Agenzia, c.*,SUBSTR(CodContratto,3) AS CodContrattoRidotto, DATE_FORMAT(c.DataContratto,'%d/%m/%Y') as DataContrattoIT,
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
lr.ListaRate,CASE WHEN lr.NumRate>1 THEN CONCAT('Rate nn. ',ListaRate,' - Decorrenza ',DATE_FORMAT(PrimaData,'%e/%m/%Y'))
                             WHEN lr.NumRate=1 THEN CONCAT('Rata n. ',ListaRate,' - Scadenza ',DATE_FORMAT(PrimaData,'%e/%m/%Y'))
                             ELSE '' END AS IndicaRate
FROM contratto c
JOIN cliente cl ON cl.IdCliente=c.IdCliente
LEFT JOIN v_lista_rate lr ON c.IdContratto=lr.IdContratto
LEFT JOIN v_recapito rl ON c.IdCliente=rl.IdCliente AND rl.CodTipoRecapito='LEG'
LEFT JOIN v_recapito rp ON c.IdCliente=rp.IdCliente AND  rp.CodTipoRecapito='BASE'
LEFT JOIN prodotto p on p.idprodotto=c.idprodotto
LEFT JOIN famigliaprodotto fp on p.idfamiglia=fp.idfamiglia
LEFT JOIN reparto r on c.IdAgenzia=r.IdReparto
LEFT JOIN tipopagamento tp on tp.IdTipoPagamento=c.IdTipoPagamento
LEFT JOIN banca b on b.ABI=c.ABI and b.CAB=c.CAB
LEFT JOIN compagnia d on d.IdCompagnia=c.IdDealer
LEFT JOIN v_recapiti_mandato vrm on c.IdContratto=vrm.IdContratto and vrm.Ruolo='Garante/Coobbligato' 
JOIN reparto main ON main.idReparto=1;

CREATE OR REPLACE ALGORITHM=MERGE VIEW v_procedure_workflow
AS
Select p.*, (select count(*) from azioneprocedura ap where ap.IdProcedura=p.IdProcedura) as numAzioni,
            (select count(distinct sap.IdStatoRecuperoSuccessivo) 
                from azioneprocedura apr 
                left join statoazione sap on(apr.IdAzione=sap.IdAzione) 
                left join statorecupero sr on(sap.IdStatoRecuperoSuccessivo=sr.IdStatoRecupero)
                where apr.IdProcedura=p.IdProcedura
                and sap.IdStatoRecuperoSuccessivo is not null
                and sr.CodStatoRecupero like 'WRK%') as numStati,
            case when p.DataFin>=date(now()) then 'Y' else 'N' end as Attiva
from procedura p;

CREATE OR REPLACE VIEW v_lista_rate
AS
select idcontratto,group_concat(numrata separator ', ') as ListaRate,count(*) as NumRate, MIN(DataInsoluto) AS PrimaData
from insoluto
where numrata>0 and impinsoluto>5 and impcapitale>0
group by idcontratto;

/*
  I contatori hanno i seguenti significato
  NotaUtenteNonAut: nota vista da utente non autorizzato (alle note riservate) e senza visibilità su reparto
  NotaUtenteAut:    nota vista da utente autorizzato e senza visibilità su reparto
  NotaRepartoNonAut: nota vista da utente non autorizzato ma con visibilità sul reparto
  NotaRepartoAut:   nota vista da utente autorizzato e con visibilità sul reparto
  NotaSuper:        nota vista dal supervisore (tutte)
*/
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_note_utente_plus (IdNota,IdContratto,IdUtente,IdCreatore,NotaUtenteNonAut,NotaUtenteAut,NotaRepartoNonAut,NotaRepartoAut,NotaSuper)
AS
/* Note create dall'utente */
select IdNota,IdContratto,n.IdUtente,n.IdUtente,1,1,1,1,1 FROM nota n WHERE TipoNota in ('N','C')
UNION ALL
/* Note dirette all'utente */
SELECT IdNota,IdContratto,n.IdUtenteDest,n.IdUtente,1,1,1,1,1 FROM nota n
WHERE TipoNota in ('N','C') AND IdUtente != IFNULL(IdUtenteDest,0) AND IdUtenteDest IS NOT NULL
/* Note dirette al reparto dell'utente */
UNION ALL
SELECT IdNota,IdContratto,u.IdUtente,n.IdUtente,IF(FlagRiservato='Y',0,1),1,IF(FlagRiservato='Y',0,1),1,1
FROM nota n,utente u
WHERE TipoNota in ('N','C') AND n.IdUtente != u.IdUtente AND IdUtenteDest IS NULL AND u.IdReparto = IFNULL(n.IdReparto,0)
/* Note dirette a tutti */
UNION ALL
SELECT IdNota,IdContratto,u.IdUtente,n.IdUtente,IF(FlagRiservato='Y',0,1),1,IF(FlagRiservato='Y',0,1),1,1
FROM nota n,utente u
WHERE TipoNota in ('N','C') AND IdUtenteDest IS NULL AND n.IdReparto IS NULL and n.idutente!=u.idutente
/* Note create da altri utenti del reparto e dirette non ad utenti del reparto */
UNION ALL
select IdNota,IdContratto,a.IdUtente,n.IdUtente,0,0,IF(FlagRiservato='Y',0,1),1,1
FROM nota n,utente u,utente a
WHERE TipoNota in ('N','C')
AND n.IdUtente=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente AND IdUtenteDest!=a.IdUtente
and IFNULL(n.idUtenteDest,0) NOT IN (SELECT IdUtente FROM utente WHERE IdReparto=a.IdReparto)
/* Note dirette ad altri utenti del reparto */
UNION ALL
select IdNota,IdContratto,a.IdUtente,n.IdUtente,0,0,IF(FlagRiservato='Y',0,1),1,1
FROM nota n,utente u,utente a
WHERE TipoNota in ('N','C') AND n.IdUtente != IFNULL(n.IdUtenteDest,0) AND n.IdUtenteDest IS NOT NULL
AND n.IdUtenteDest=u.IdUtente AND u.IdReparto=a.IdReparto AND u.IdUtente!=a.IdUtente AND IdUtenteDest!=a.IdUtente
AND n.IdUtente != a.idutente;

/* Att.ne: VIEW INEFFICIENTE (TEMPTABLE): usata solo in stampa mandato, dove non crea problemi di prestazioni */

create or replace view v_altri_telefoni as
select idcliente,
GROUP_CONCAT(DISTINCT IFNULL(trim(telefono),''), trim(IFNULL(CONCAT(' ',cellulare),''))
                   ORDER BY CASE WHEN lastuser IN ('import','system') THEN -IdTipoRecapito ELSE IdRecapito END DESC SEPARATOR ', ')
                  AS AltriNumeri
from recapito
where IdTipoRecapito!=1 AND (length(trim(telefono))>3 OR length(trim(cellulare))>3)
GROUP BY idcliente;

/****
create or replace view v_rate_insolute
AS
select IdContratto,
NumRata,
replace(replace(replace(format(IFNULL(
	IF(ImpCapitale-(ImpPagato-(ImpDebitoIniziale-ImpInsoluto))<=ImpDebitoIniziale,
		ImpCapitale-(ImpPagato-(ImpDebitoIniziale-ImpInsoluto)),
		IF(ImpDebitoIniziale<10,0,ImpDebitoIniziale))
,0),2),'.',';'),',','.'),';',',') AS ImpCapitaleDaPagare,
ImpDebitoIniziale,
IF(ImpDebitoIniziale>ImpInsoluto,ImpDebitoIniziale-ImpInsoluto,0) AS ImpPagato,
DATE_FORMAT(DataInsoluto,'%d/%m/%Y') AS DataScadenza
FROM insoluto
WHERE NumRata>0 AND ImpCapitale>0 and impdebitoiniziale>5;
****/ 
/**** 
	13-10-2011: modificato l'importo della rata, perché questa vista si usa nelle lettere 
                e nei mandati, in cui dovrebbe comparire il residuo da pagare per ogni
                rata, non tanto l'importo del capitale 
****/
create or replace view v_rate_insolute
AS
select IdContratto,(ImpPagato-(ImpDebitoIniziale-ImpInsoluto)),
NumRata,
replace(replace(replace(format(IFNULL(ImpInsoluto,0),2),'.',';'),',','.'),';',',') AS ImpCapitaleDaPagare,
ImpDebitoIniziale,
IF(ImpDebitoIniziale>ImpInsoluto,ImpDebitoIniziale-ImpInsoluto,0) AS ImpPagato,
DATE_FORMAT(DataInsoluto,'%d/%m/%Y') AS DataScadenza
FROM insoluto
WHERE NumRata>0 AND ImpInsoluto>0;

CREATE OR REPLACE VIEW v_fasce_visibili
AS
SELECT u.IdUtente,FasciaRecupero
FROM utente u
JOIN regolaassegnazione r ON u.IdUtente=r.IdUtente AND TipoAssegnazione=1
JOIN regolaprovvigione p  ON p.IdReparto=r.IdReparto
UNION
SELECT IdUtente,FasciaRecupero
FROM utente u
JOIN regolaprovvigione p ON p.IdReparto=u.IdReparto
UNION
SELECT u.IdUtente,FasciaRecupero
FROM utente u
JOIN regolaassegnazione r ON u.IdUtente=r.IdUtente AND TipoAssegnazione=1
JOIN regolaprovvigione p ON p.IdRegolaProvvigione=r.IdRegolaProvvigione;

Create or replace view v_partite
as
select m.IdContratto,m.NumRata,i.IdInsoluto,IdMovimento,DataRegistrazione,DataCompetenza,DataScadenza,DataValuta, TitoloTipoMovimento, TitoloTipoInsoluto,
CASE WHEN Importo>0 THEN Importo ELSE NULL END AS Debito,CASE WHEN Importo<0 THEN -Importo ELSE NULL END AS Credito
FROM movimento m 
LEFT JOIN tipomovimento t ON m.idtipomovimento=t.idtipomovimento
LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto
LEFT JOIN insoluto i ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata
ORDER BY IdMovimento;

CREATE OR REPLACE ALGORITHM=MERGE VIEW v_assegnazioni_workflow
AS
SELECT re.*,
(SELECT count(*) FROM regolaprovvigione rp where rp.IdReparto=re.IdReparto) as NumTipAff,
(SELECT count(*) FROM regolaassegnazione rass where rass.tipoassegnazione=2 and rass.IdReparto=re.IdReparto) as NumRegAff,
(SELECT count(*) FROM regolaassegnazione rasse where rasse.tipoassegnazione=3 and rasse.IdReparto=re.IdReparto) as NumRegAffOpe
FROM reparto re where re.IdTipoReparto>1;

CREATE OR REPLACE VIEW v_dettaglio_provvigioni
AS
SELECT v.*,CodContratto,CodContratto AS numPratica,c.IdCliente,TitoloUfficio AS Agenzia,IFNULL(Nominativo,RagioneSociale) AS cliente,
      CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE LEAST(100.,ROUND(v.ImpPagato*100./ImpCapitaleAffidato,2)) END AS PercCapitale,
      CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE ROUND(v.ImpPagato*100./ImpCapitaleAffidato,2) END AS PercCapitaleReale,
      DataUltimoPagamento,
      CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE LEAST(v.ImpPagato,ImpCapitaleAffidato) END AS ImpRiconosciuto,
      Userid as Operatore
FROM v_importi_per_provvigioni_full v
JOIN reparto r ON r.IdReparto=v.IdAgenzia
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente x ON x.IdCliente=c.IdCliente
LEFT JOIN utente u ON u.IdUtente=v.IdAgente
;

create or replace view v_soggetti_mandato as
SELECT cp.IdCliente,IF(tc.IdTipoControparte=1,'Garante/Coobbligato',TitoloTipoControparte) as Ruolo,cp.IdContratto
FROM  controparte cp,tipocontroparte tc WHERE cp.IdTipoControparte=tc.IdTipoControparte AND tc.FlagGarante='Y'
UNION ALL
SELECT  idCliente,'Cliente' as Ruolo,c.IdContratto FROM contratto c;

CREATE OR REPLACE VIEW v_partite_semplici
AS
select m.IdContratto,m.NumRata,TitoloTipoInsoluto,
       CASE WHEN CategoriaMovimento='C' AND DataScadenza IS NOT NULL AND Importo>0 THEN DataScadenza END AS DataScadenza,
       CASE WHEN Importo<0 AND (CategoriaMovimento='P' OR CategoriaMovimento IS NULL) THEN DataCompetenza END AS DataPagamento,
       CASE WHEN Importo<0 AND CategoriaMovimento='P' THEN TitoloTipoMovimento
            WHEN IFNULL(i.ImpInsoluto,0)<=0 AND m.IdTipoMovimento=163 THEN ' RID'
            ELSE ''
       END AS CausalePagamento,
       CASE WHEN CategoriaMovimento='C' AND DataScadenza IS NOT NULL AND Importo>0 THEN Importo END AS Rata,
       Importo AS Debito,i.IdInsoluto
FROM movimento m
JOIN tipomovimento t ON m.idtipomovimento=t.idtipomovimento
LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto
LEFT JOIN insoluto i ON m.IdContratto=i.IdContratto AND m.NumRata=i.NumRata;

CREATE OR REPLACE VIEW v_importi_per_provvigioni_full
AS
select a.IdContratto,a.IdAgenzia,a.IdAgente,
        CASE WHEN SUM(ImpCapitaleAffidato)>0 THEN SUM(ImpCapitaleAffidato) ELSE 0 END as ImpCapitaleAffidato,
        CASE WHEN SUM(ImpTotaleAffidato)>0 THEN SUM(ImpTotaleAffidato) ELSE 0 END AS ImpTotaleAffidato,
        IF ( SUM(ImpPagatoTotale)>0 , LEAST(SUM(ImpTotaleAffidato),SUM(ImpPagatoTotale)) , 0) AS ImpPagato,
        IF ( SUM(ImpPagatoTotale)>0 , SUM(ImpPagatoTotale), 0) AS ImpPagatoTotale,
        a.ImpInteressiMoraPagati AS ImpInteressi,a.ImpSpeseRecuperoPagate as ImpSpese,
        v.DataFineAffido,MIN(v.DataInizioAffido) AS DataInizioAffido,a.IdClasse,a.IdProvvigione,
       SUM(IF(ImpCapitaleAffidato>5 AND NumRata>0,1,0)) AS NumRate
from assegnazione a
JOIN v_importi_per_provvigioni v ON v.IdContratto=a.IdContratto and v.IdAgenzia=a.IdAgenzia and v.DataFineAffido=a.DataFin
GROUP BY IdContratto,IdAgenzia,IdAgente,DataFineAffido;

create or replace view v_insoluti_count
as
select co.*,sc.CodStatoRecupero AS stato,cl.CodClasse AS classif,cl.FlagNoAffido,u.IdReparto,tr.CodTipoReparto,r.TitoloUfficio,IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,
IFNULL(cl.FlagRecupero,'N') AS InRecupero
from contratto co
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia
left join tiporeparto tr on tr.IdTipoReparto = r.IdTipoReparto
left join utente u on u.IdUtente =co.IdOperatore
left join categoria cat on co.IdCategoria=cat.IdCategoria;

create or replace view v_importi_per_provvigioni
as
select si.IdContratto,si.NumRata,IdAgenzia,
	    IF(IdAffidamento IS NOT NULL,ImpCapitaleDaPagare,0) as ImpCapitaleAffidato,
	    IF(IdAffidamento IS NOT NULL,ImpInsoluto,0) AS ImpTotaleAffidato,
		IF(IdAffidamento IS NOT NULL,ImpPagato,0) AS ImpPagato,
		ImpPagato AS ImpPagatoTotale,
        DataFineAffido,DataInizioAffido
from storiainsoluto si
WHERE IdAgenzia IS NOT NULL AND (DataFineAffido>CURDATE() AND Not Exists (select 1 from insoluto where idcontratto=si.idcontratto and numrata=si.numrata)
OR DataFineAffido<=CURDATE() AND CodAzione!='REV')
UNION ALL
select i.IdContratto,i.NumRata,IdAgenzia,
  IF (IdAffidamento IS NULL OR ImpDebitoIniziale<0,0,
   IF (i.ImpCapitale-(i.ImpPagato-(ImpDebitoIniziale-i.ImpInsoluto))<=0,0,
     IF (i.ImpCapitale-(i.ImpPagato-(ImpDebitoIniziale-i.ImpInsoluto))>i.ImpDebitoIniziale and i.ImpCapitale-(i.ImpPagato-(ImpDebitoIniziale-i.ImpInsoluto))>0, i.ImpDebitoIniziale,
       IF (i.ImpCapitale-(i.ImpPagato-(ImpDebitoIniziale-i.ImpInsoluto))>i.ImpCapitale, i.ImpCapitale,
           i.ImpCapitale-(i.ImpPagato-(ImpDebitoIniziale-i.ImpInsoluto))
          )
        )
      )
   ) as ImpCapitaleAffidato,
  IF(IdAffidamento IS NOT NULL,i.ImpDebitoIniziale,0),
  IF(IdAffidamento IS NOT NULL AND ImpDebitoIniziale>i.ImpInsoluto,ImpDebitoIniziale-i.ImpInsoluto,0) as ImpPagato,
  IF(IdAffidamento IS NOT NULL,ImpDebitoIniziale-i.ImpInsoluto,0) as ImpPagatoTotale,
	DataFineAffido,DataInizioAffido
from insoluto i JOIN contratto c ON i.idcontratto=c.idcontratto
where IdAgenzia IS NOT NULL;

CREATE OR REPLACE VIEW v_insoluti_dipendenti
AS
select c.IdCliente,c.IdContratto,SUBSTR(CodCliente,3) AS CodAna,SUBSTR(CodContratto,3) AS numPratica,Nominativo,
       SUM(IF(i.DataChiusura IS NULL,1,0)) AS NumInsoluti,
       SUM(i.ImpCapitale+i.ImpInteressi+i.ImpInteressiMora+i.ImpCommissioni-i.ImpPagato) AS ImpDebito,
       MIN(DataScadenza) As DataRata,MAX(DATEDIFF(CURDATE(), DataScadenza)) AS GiorniRitardo,
       i.DataChiusura as DataChiusura
from insolutodipendente i
LEFT JOIN contratto c ON i.IdContratto=c.IdContratto
LEFT JOIN cliente cl  ON c.IdCliente=cl.IdCliente
GROUP BY CodCliente,CodContratto;

CREATE OR REPLACE VIEW v_azioni_pratiche_dipendenti
AS
select IdFunzione,CodFunzione from funzione where idFunzione in(22,23,27,29,158);

CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti
AS
select
co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,NumRata AS rata,NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS giorni,
ImpInsoluto,ImpInsoluto AS importo,ImpPagato,DataRata AS DataScadenza,sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,
cl.CodClasse AS classif,cl.AbbrClasse AS AbbrClasse,
CASE WHEN CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,DataScadenzaAzione,DataInizioAffido,DataFineAffido,u.IdReparto, c.Telefono,
IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,
CASE WHEN DataUltimaAzione=CURDATE() THEN 'Y' WHEN DataUltimaAzione<CURDATE() THEN 'P' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,co.PercSvalutazione,((co.PercSvalutazione/100)*co.ImpInsoluto) as Svalutazione,
IFNULL(cl.FlagRecupero,'N') AS InRecupero,NumInsoluti,NumRate
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join reparto r on r.IdReparto = co.IdAgenzia
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on ag.IdUtente = co.IdAgente
left join v_prossima_scadenza ps on ps.IdContratto=co.IdContratto
left join categoria cat on co.IdCategoria=cat.IdCategoria
left join v_data_ultima_azione az ON az.IdContratto=co.IdContratto;

create or replace view v_dettaglio_insoluto
as
select c.IdContratto,c.IdOperatore,c.IdAgenzia,c.idagente,
sum(case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale) else 0 end) as Capitale,
case when ifnull(rr.flagInteressimora,rrn.flaginteressimora) = 'Y' then c.impinteressimora else 0 end as InteressiMora,
sum(case when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) then i.impinsoluto else 0 end) as AltriAddebiti,
CASE WHEN rr.IdRegolaRipartizione IS NOT NULL
	   THEN IFNULL(rr.impspeseincasso,
	      		round(rr.percspeseincasso*sum(case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale) else 0 end)/100,2)
	      		)
     WHEN rrn.IdRegolaRipartizione IS NOT NULL and c.IdAgenzia IS NULL
     THEN IFNULL(rrn.impspeseincasso,
	      		round(rrn.percspeseincasso*sum(case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale) else 0 end)/100,2)
	      		)
     ELSE 0
END as Speseincasso,c.IdClasse,
IFNULL(MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>=5,i.NumRata,NULL)),MIN(i.NumRata)) AS NumRata,
sum(case when i.ImpCapitale>0 AND i.ImpInsoluto>=5 then 1 else 0 end) AS NumInsoluti,
sum(i.ImpDebitoIniziale)-sum(i.ImpInsoluto) as ImpPagato,
IFNULL(MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>=5,i.DataInsoluto,NULL)),MIN(i.DataInsoluto)) as DataRata
from contratto c
left join insoluto i on i.idContratto=c.idContratto
left join regolaprovvigione rp ON rp.CodRegolaProvvigione=c.CodRegolaProvvigione
left join regolaripartizione rr on rr.Idregolaprovvigione=rp.IdRegolaProvvigione
left join regolaripartizione rrn on rrn.idclasse=c.idclasse and rrn.idreparto is null
group by c.idcontratto;


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
order by TipoFormAzione Asc;

CREATE OR REPLACE VIEW v_azioni_semplici_forms
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
order by TipoFormAzione Asc;
