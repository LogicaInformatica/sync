CREATE OR REPLACE VIEW v_ripartizione_per_reparto
AS
select idclasse,percspeseincasso,impspeseincasso,flaginteressimora,idfamiglia,a.idreparto
from regolaripartizione r,reparto a
where CURDATE() BETWEEN r.DataIni AND r.DataFin
AND   CURDATE() BETWEEN a.DataIni AND a.DataFin
AND (r.IdReparto=a.IdReparto OR r.IdReparto IS NULL AND NOT EXISTS
(SELECT 1 FROM regolaripartizione x where x.idReparto=a.IdReparto
AND CURDATE() BETWEEN x.DataIni AND x.DataFin
AND r.IdClasse=x.IdClasse AND IFNULL(x.IdFamiglia,0)=IFNULL(r.IdFamiglia,0))
);

create or replace view v_azioni_fatte
as
select IdContratto,IdUtente,Max(DataEvento) AS DataUltimaAzione,count(*) as NumAzioni
from storiarecupero
WHERE IdUtente>0 AND IdAzione>0
group by IdContratto,IdUtente;

CREATE OR REPLACE VIEW v_insoluti_agenti_group
AS
SELECT IdContratto,IdAgenzia,IdAgente,Agenzia,NomeAgente,SUM(ImpInsoluto) AS ImpInsoluto,SUM(ImpPagato) AS ImpPagato,
       SUM(ImpCapitale) AS ImpCapitale,sum(case when ImpCapitale>0 AND ImpInsoluto>=10 then 1 else 0 end) AS NumInsoluti,
       CONCAT('Fino al ',DATE_FORMAT(DataFineAffido,'%d/%m')) AS Lotto, DataFineAffido
FROM v_insoluti_agenti ia
GROUP BY IdContratto,IdAgenzia,IdAgente,DataFineAffido,Agenzia,NomeAgente;

CREATE OR REPLACE VIEW v_insoluti_agenti
AS
SELECT c.IdContratto,c.IdAgenzia,c.IdAgente,DATE_FORMAT(c.DataInizioAffido,'%Y%m') AS MeseAffido,TitoloUfficio as Agenzia,
       IFNULL(u.Userid,'non assegnata') AS NomeAgente,c.ImpInsoluto,c.ImpPagato,
       c.ImpCapitale,DataFineAffido
FROM  contratto c
LEFT JOIN utente u ON u.IdUtente=c.IdAgente
LEFT JOIN reparto r ON r.IdReparto=c.IdAgenzia
WHERE ImpInsoluto>0
UNION ALL
SELECT i.IdContratto,i.IdAgenzia,i.IdAgente,DATE_FORMAT(i.DataInizioAffido,'%Y%m') AS MeseAffido,TitoloUfficio as Agenzia,
       IFNULL(u.Userid,'non assegnata') AS NomeAgente,i.ImpInsoluto,i.ImpPagato,
       i.ImpCapitaleDaPagare AS ImpCapitale,i.DataFineAffido
FROM storiainsoluto i
LEFT JOIN utente u ON u.IdUtente=i.IdAgente
LEFT JOIN reparto r ON r.IdReparto=i.IdAgenzia
WHERE i.ImpInsoluto>0 AND i.IdAgenzia IS NOT NULL AND
(i.DataFineAffido>CURDATE() OR i.DataFineAffido<=CURDATE() AND CodAzione!='REV');

CREATE OR REPLACE VIEW v_stato_lavorazione (IdContratto,CodStato,TitoloStato)
AS
SELECT c.IdContratto,
       CASE WHEN IdClasse=18 THEN '05'
            WHEN ImpPagato>0 THEN '06'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN '01'
    /*        WHEN DataInizioAffido>CURDATE() - INTERVAL 3 DAY THEN '02' */
            WHEN DataFineAffido<CURDATE() + INTERVAL 3 DAY THEN '03'
            ELSE '04'
       END AS CodStato,
       CASE WHEN IdClasse=18 THEN 'Positive'
            WHEN ImpPagato>0 THEN 'Con incasso parziale'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN 'Lavorate'
    /*      WHEN DataInizioAffido>CURDATE() - INTERVAL 3 DAY THEN 'Da lavorare - nuove' */
            WHEN DataFineAffido<CURDATE() + INTERVAL 3 DAY THEN 'Da lavorare - urgenti'
            ELSE 'Da lavorare'
       END AS TitoloStato
FROM contratto c;

CREATE OR REPLACE VIEW v_sintesi_insoluti
AS
select IFNULL(c.IdAgenzia,ia.IdAgenzia) AS IdAgenzia,
	   CASE WHEN c.IdAgenzia IS NULL AND ia.IdAgenzia IS NULL THEN 'n/a'
	        WHEN c.IdAgenzia IS NULL THEN ia.agenzia ELSE rc.TitoloUfficio END AS Agenzia,
       sc.AbbrStatoRecupero,sc.CodStatoRecupero,
       fp.TitoloFamiglia AS TitoloFamiglia,
       count(0) AS NumPratiche,sc.Ordine AS OrdineStato,
       SUM(ia.NumInsoluti) AS NumInsoluti,
       sum(case when EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) then 1 else 0 end) AS Trattati,
       sum(case when NOT EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) then 1 else 0 end) AS DaTrattare,
       sum(ia.ImpInsoluto) AS ImpInsoluto,
       sum(ia.ImpPagato) AS ImpPagato,sum(ia.ImpCapitale) AS ImpCapitale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpInsoluto)),2) AS PercTotale,
       round(((sum(ia.ImpPagato) * 100) / sum(ia.ImpCapitale)),2) AS PercCapitale
from contratto c
left join prodotto p on c.IdProdotto = p.IdProdotto
left join statorecupero sc on sc.IdStatoRecupero = c.IdStatoRecupero
left join v_insoluti_agenzia_group ia ON c.IdContratto=ia.IdContratto
left join famigliaprodotto fp on fp.IdFamiglia = p.IdFamiglia
left join reparto r ON r.IdReparto=ia.IdAgenzia
left join reparto rc ON rc.IdReparto=c.IdAgenzia
where (sc.CodStatoRecupero != 'NOR' OR ia.IdContratto IS NOT NULL)
group by AbbrStatoRecupero,sc.CodStatoRecupero,c.IdAgenzia,ia.agenzia,fp.TitoloFamiglia,sc.Ordine;

CREATE OR REPLACE VIEW v_sintesi_stato (IdAgenzia,IdAgente,Lotto,DataFineAffido,NumInsoluti,CodStato,TitoloStato,
                                          ImpInsoluto,ImpPagato,ImpCapitale,
                                          PercTotale,PercCapitale,NumAzioni,DataUltimaAzione)
AS
SELECT ia.IdAgenzia,ia.IdAgente,Lotto,c.DataFineAffido,COUNT(*) AS NumInsoluti, /* in realt  il num. pratiche */
         CASE WHEN IdClasse=18 THEN '05'
            WHEN ia.ImpPagato>0 THEN '06'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN '01'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN '03'
            ELSE '04'
       END AS CodStato,
       CASE WHEN IdClasse=18 THEN 'Positive'
            WHEN ia.ImpPagato>0 THEN 'Con incasso parziale'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN 'Lavorate'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN 'Da lavorare - urgenti'
            ELSE 'Da lavorare'
       END AS TitoloStato,
       SUM(ia.ImpInsoluto),SUM(ia.ImpPagato),SUM(ia.ImpCapitale),
ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpInsoluto),2),ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpCapitale),2),
SUM(NumAzioni),MAX(DataUltimaAzione)
FROM contratto c
JOIN v_insoluti_agenti_group ia ON ia.IdContratto=c.IdContratto
LEFT JOIN v_azioni_fatte az ON az.IdContratto=ia.IdContratto AND az.IdUtente=ia.IdAgente
GROUP BY ia.IdAgenzia,ia.IdAgente,DataFineAffido,CodStato;

CREATE OR REPLACE VIEW v_sintesi_stato_group (IdAgenzia,Lotto,DataFineAffido,NumInsoluti,CodStato,TitoloStato,
                                          ImpInsoluto,ImpPagato,ImpCapitale,
                                          PercTotale,PercCapitale,NumAzioni,DataUltimaAzione)
AS
SELECT ia.IdAgenzia,Lotto,ia.DataFineAffido,COUNT(*) AS NumInsoluti, /* in realta'  il num. pratiche */
       CASE WHEN IdClasse=18 THEN '05'
            WHEN ia.ImpPagato>0 THEN '06'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN '01'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN '03'
            ELSE '04'
       END AS CodStato,
       CASE WHEN IdClasse=18 THEN 'Positive'
            WHEN ia.ImpPagato>0 THEN 'Con incasso parziale'
            WHEN EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto AND IdAzione IS NOT NULL AND IdUtente IS NOT NULL) THEN 'Lavorate'
            WHEN ia.DataFineAffido BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY THEN 'Da lavorare - urgenti'
            ELSE 'Da lavorare'
       END AS TitoloStato,
       SUM(ia.ImpInsoluto),SUM(ia.ImpPagato),SUM(ia.ImpCapitale),
ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpInsoluto),2),ROUND((SUM(ia.ImpPagato)*100)/SUM(ia.ImpCapitale),2),
SUM(NumAzioni),MAX(DataUltimaAzione)
FROM contratto c
JOIN v_insoluti_agenti_group ia ON ia.IdContratto=c.IdContratto
LEFT JOIN v_azioni_fatte az ON az.IdContratto=ia.IdContratto AND az.IdUtente=ia.IdAgente
GROUP BY ia.IdAgenzia,DataFineAffido,CodStato;

create or replace view v_pratiche
as
select titoloarea as area,concat(titolofamiglia,' - ',titoloprodotto) as Prodotto,TitoloCompagnia as Venditore,
       codtipopagamento as TipoPagamento,cli.CodCliente,ifnull(cli.Nominativo,cli.RagioneSociale) as NomeCliente,ifnull(cli.DataNascita,'Data assente') as DataNCli,
       cli.LocalitaNascita as LuogoNCli,codstatocontratto as CodStato,titolostatocontratto as Stato,CodClasse,TitoloClasse as Classificazione,
       codstatorecupero as CodStatoRecupero,titolostatorecupero as StatoRecupero,
       CodUtente,NomeUtente,TitoloUfficio As NomeAgenzia,
       NumRata as Rata,NumInsoluti as Insoluti,Datediff(curdate(),DataRata) as Giorni,ImpInsoluto as Importo,DataRata as DataScadenza,fp.IdFamiglia,cli.IdArea as AreaCliente,cli.IdTipoCliente,
       cli.IdArea AS IdAreaCliente,fp.IdFamigliaParent,TitoloTipoSpeciale as TitTipoSpec,ifnull(cliV.Nominativo,cliV.RagioneSociale) as NomeVenditore,ifnull(cliPV.Nominativo,cliPV.RagioneSociale) as NomePuntoVendita,TitoloAttributo as Attributo,
       c.*,cli.sesso,
       cat.TitoloCategoria,cl.FlagCambioAgente
       
From contratto c
left join filiale f on c.idfiliale=f.idfiliale
left join area a on a.idarea=f.idarea
left join prodotto p on p.idprodotto=c.idprodotto
left join famigliaprodotto fp on p.idfamiglia=fp.idfamiglia
left join compagnia cp on c.iddealer=cp.idcompagnia
left join tipopagamento tpag on tpag.idtipopagamento=c.idtipopagamento
left join cliente cli on cli.idcliente=c.idcliente
left join cliente cliV on cliV.IdCliente=c.IdVenditore
left join cliente cliPV on cliPV.IdCliente=c.IdPuntoVendita
left join attributo att on att.IdAttributo=c.IdAttributo
left join statocontratto sc on sc.idstatocontratto=c.idstatocontratto
left join statorecupero sr on sr.idstatorecupero=c.idstatorecupero
left join classificazione cl on cl.idclasse=c.idclasse
left join utente u on u.idutente=c.idoperatore
left join reparto r on r.idreparto=c.idagenzia
left join tipospeciale t on c.IdTipoSpeciale=t.IdTipoSpeciale
left join categoria cat on c.IdCategoria = cat.IdCategoria;

CREATE OR REPLACE VIEW v_lista_rate
AS
select idcontratto,group_concat(numrata separator ', ') as ListaRate,count(*) as NumRate, MIN(DataInsoluto) AS PrimaData
from insoluto
where numrata>0 and impinsoluto>10 and impcapitale>0
group by idcontratto;

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
IFNULL(r.TelefonoPerClienti,IFNULL(r.telefono,'____________')) as TelAgenzia, r.EmailReferente as EmailAgenzia, IFNULL(r.Fax,'____________') as FaxAgenzia,
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
LEFT JOIN v_recapiti_mandato vrm on c.IdContratto=vrm.IdContratto and vrm.Ruolo='Garante/Coobbligato' ;

CREATE OR REPLACE VIEW v_graph_provvigione
AS
SELECT r.FasciaRecupero,DATE_FORMAT(p.DataFin,'%Y%m') as Mese,p.IdReparto,r.CodRegolaProvvigione,
a.TitoloUfficio AS Agenzia,
SUM(NumAffidati) AS NumAffidati,sum(NumIncassati) as NumIncassati,
sum(ImpCapitaleAffidato) as ImpCapitaleAffidato,sum(ImpCapitaleIncassato) as ImpCapitaleIncassato,
concat('€ ',replace(format(sum(impCapitaleAffidato),0),',','.'),'\n  (',sum(NumAffidati),')') AS LabelAffidato,
concat('€ ',replace(format(sum(ImpCapitaleIncassato),0),',','.'),'\n  (',sum(NumIncassati),')') AS LabelIncassato,
CASE WHEN sum(NumAffidati)=0 THEN 0 ELSE ROUND(sum(NumIncassati)*100./sum(NumAffidati),2) END AS IPM,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPR
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
JOIN reparto a ON a.IdReparto=p.IdReparto
group by 1,2,3;

CREATE OR REPLACE VIEW v_graph_target
AS
SELECT r.FasciaRecupero,r.TitoloRegolaProvvigione,concat(a.TitoloUfficio,' (',CodRegolaProvvigione,')') AS Agenzia,
valore AS TargetIPR,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPR,
CASE WHEN sum(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(sum(ImpCapitaleRealeIncassato)*100./sum(ImpCapitaleAffidato),2) END AS IPF,
DATE_FORMAT(p.DataFin,'%Y%m') as Mese,r.idregolaprovvigione
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
JOIN reparto a ON a.IdReparto=p.IdReparto
LEFT JOIN target t ON r.FasciaRecupero=t.FasciaRecupero and DATE_FORMAT(p.datafin,'%Y%m') between (FY-1)*100+4 AND FY*100+3
group by t.ordine,CodRegolaProvvigione,mese
order by t.ordine,CodRegolaProvvigione;

CREATE OR REPLACE VIEW v_graph_target_fy
AS
SELECT r.FasciaRecupero,r.TitoloRegolaProvvigione,concat(a.TitoloUfficio,' (',CodRegolaProvvigione,')') AS Agenzia,
valore AS TargetIPR,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(ImpCapitaleIncassato)*100./SUM(ImpCapitaleAffidato),2) END AS IPR,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(ImpCapitaleRealeIncassato)*100./SUM(ImpCapitaleAffidato),2) END AS IPF,
FY
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
JOIN reparto a ON a.IdReparto=p.IdReparto
LEFT JOIN target t ON r.FasciaRecupero=t.FasciaRecupero and DATE_FORMAT(p.datafin,'%Y%m') between (FY-1)*100+4 AND FY*100+3
group by FY,t.ordine,CodRegolaProvvigione
ORDER BY FY,t.ordine,CodRegolaProvvigione;

CREATE OR REPLACE VIEW v_geography
AS
select a.idArea,IFNULL(TitoloArea,'n/a') AS Area,TitoloUfficio AS Agenzia,DATE_FORMAT(v.DataFineAffido,'%Y%m') as Mese,count(*) as NumPratiche,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100./SUM(v.ImpCapitaleAffidato),2) END AS IPR,
SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato
from v_importi_per_provvigioni_full v
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
JOIN regolaprovvigione r ON p.idregolaprovvigione=r.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
JOIN reparto re ON re.IdReparto=v.IdAgenzia
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE (fasciarecupero like '%ESA%' OR fasciarecupero='LEASING' or fasciarecupero='FLOTTE')
group by cl.idarea,v.idagenzia,Mese;

CREATE OR REPLACE VIEW v_geography_fy
AS
select a.IdArea,IFNULL(TitoloArea,'n/a') AS Area,TitoloUfficio AS Agenzia,YEAR(v.DataFineAffido + INTERVAL 8 MONTH) as Anno,count(*) as NumPratiche,
CASE WHEN SUM(v.ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato))*100./SUM(v.ImpCapitaleAffidato),2) END AS IPR
,SUM(v.ImpCapitaleAffidato) AS ImpCapitaleAffidato,
SUM(IF(v.ImpPagato>v.ImpCapitaleAffidato,v.ImpCapitaleAffidato,v.ImpPagato)) AS ImpCapitalePagato
from v_importi_per_provvigioni_full v
JOIN provvigione p ON p.IdProvvigione=v.IdProvvigione
JOIN regolaprovvigione r ON p.idregolaprovvigione=r.idregolaprovvigione
JOIN contratto c ON c.IdContratto=v.IdContratto
JOIN cliente cl ON cl.IdCliente=c.IdCliente
JOIN reparto re ON re.IdReparto=v.IdAgenzia
LEFT JOIN area a ON a.IdArea=cl.IdArea
WHERE (fasciarecupero like '%ESA%' OR fasciarecupero='LEASING' or fasciarecupero='FLOTTE')
group by cl.idarea,v.idagenzia,Anno;

CREATE OR REPLACE VIEW v_geography_pivot_fy
AS
select Area,Anno,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(ImpCapitalePagato)*100./SUM(ImpCapitaleAffidato),2) END AS Totale,
SUM(NumPratiche) AS TotaleNum,
SUM(IF(agenzia='CITY', IPR, null)) AS City,
SUM(IF(agenzia='CITY', NumPratiche, null)) AS CityNum,
SUM(IF(agenzia='CSS', IPR, null)) AS Css,
SUM(IF(agenzia='CSS', NumPratiche, null)) AS CssNum,
SUM(IF(agenzia='EUROCOLLECTION', IPR, null)) AS Eurocollection,
SUM(IF(agenzia='EUROCOLLECTION', NumPratiche, null)) AS EurocollectionNum,
SUM(IF(agenzia='EUROLEGAL', IPR, null)) AS Eurolegal,
SUM(IF(agenzia='EUROLEGAL', NumPratiche, null)) AS EurolegalNum,
SUM(IF(agenzia='FIDES', IPR, null)) AS Fides,
SUM(IF(agenzia='FIDES', NumPratiche, null)) AS FidesNum,
SUM(IF(agenzia='OSIRC', IPR, null)) AS Osirc,
SUM(IF(agenzia='OSIRC', NumPratiche, null)) AS OsircNum,
SUM(IF(agenzia='SERVICE POWER', IPR, null)) AS ServicePower,
SUM(IF(agenzia='SERVICE POWER', NumPratiche, null)) AS ServicePowerNum,
SUM(IF(agenzia='SETEL', IPR, null)) AS Setel,
SUM(IF(agenzia='SETEL', NumPratiche, null)) AS SetelNum,
SUM(IF(agenzia='SOGEC', IPR, null)) AS Sogec,
SUM(IF(agenzia='SOGEC', NumPratiche, null)) AS SogecNum,
SUM(IF(agenzia='STARCREDIT', IPR, null)) AS Starcredit,
SUM(IF(agenzia='STARCREDIT', NumPratiche, null)) AS StarcreditNum,
SUM(IF(agenzia='STING', IPR, null)) AS Sting,
SUM(IF(agenzia='STING', NumPratiche, null)) AS StingNum
from v_geography_fy
GROUP BY Area,Anno
order by idArea;

CREATE OR REPLACE VIEW v_geography_pivot
AS
select Area,Mese,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(ImpCapitalePagato)*100./SUM(ImpCapitaleAffidato),2) END AS Totale,
SUM(NumPratiche) AS TotaleNum,
SUM(IF(agenzia='CITY', IPR, null)) AS City,
SUM(IF(agenzia='CITY', NumPratiche, null)) AS CityNum,
SUM(IF(agenzia='CSS', IPR, null)) AS Css,
SUM(IF(agenzia='CSS', NumPratiche, null)) AS CssNum,
SUM(IF(agenzia='EUROCOLLECTION', IPR, null)) AS Eurocollection,
SUM(IF(agenzia='EUROCOLLECTION', NumPratiche, null)) AS EurocollectionNum,
SUM(IF(agenzia='EUROLEGAL', IPR, null)) AS Eurolegal,
SUM(IF(agenzia='EUROLEGAL', NumPratiche, null)) AS EurolegalNum,
SUM(IF(agenzia='FIDES', IPR, null)) AS Fides,
SUM(IF(agenzia='FIDES', NumPratiche, null)) AS FidesNum,
SUM(IF(agenzia='OSIRC', IPR, null)) AS Osirc,
SUM(IF(agenzia='OSIRC', NumPratiche, null)) AS OsircNum,
SUM(IF(agenzia='SERVICE POWER', IPR, null)) AS ServicePower,
SUM(IF(agenzia='SERVICE POWER', NumPratiche, null)) AS ServicePowerNum,
SUM(IF(agenzia='SETEL', IPR, null)) AS Setel,
SUM(IF(agenzia='SETEL', NumPratiche, null)) AS SetelNum,
SUM(IF(agenzia='SOGEC', IPR, null)) AS Sogec,
SUM(IF(agenzia='SOGEC', NumPratiche, null)) AS SogecNum,
SUM(IF(agenzia='STARCREDIT', IPR, null)) AS Starcredit,
SUM(IF(agenzia='STARCREDIT', NumPratiche, null)) AS StarcreditNum,
SUM(IF(agenzia='STING', IPR, null)) AS Sting,
SUM(IF(agenzia='STING', NumPratiche, null)) AS StingNum
from v_geography
GROUP BY Area,Mese
order by IdArea;

CREATE OR REPLACE VIEW v_fasce_visibili
AS
SELECT u.IdUtente,FasciaRecupero
FROM utente u
JOIN regolaassegnazione r ON u.IdUtente=r.IdUtente AND TipoAssegnazione=1
JOIN regolaprovvigione p  ON p.IdReparto=r.IdReparto
UNION ALL
SELECT IdUtente,FasciaRecupero
FROM utente u
JOIN regolaprovvigione p ON p.IdReparto=u.IdReparto;

CREATE OR REPLACE VIEW v_sintesi_agenzia
AS
select a.idagenzia,a.idagente,IFNULL(u.NomeUtente,'[non assegnate]') as Agente,a.datafin as DataFineAffido,count(distinct a.idcontratto) as NumInsoluti,
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

CREATE OR REPLACE VIEW v_sintesi_agente
AS
select a.idagente,u.NomeUtente as Agente,count(distinct a.idcontratto) as NumInsoluti,a.datafin as DataFineAffido,
COUNT(DISTINCT sr.idcontratto) as Trattati,
count(distinct a.idcontratto)-COUNT(DISTINCT sr.idcontratto) as DaTrattare,SUM(ImpTotaleAffidato) AS ImpTotale,
sum(ImpCapitaleAffidato) AS ImpCapitale,sum(ImpPagato) AS ImpPagato,SUM(ImpPagatoTotale) AS ImpPagatoTotale,
CASE WHEN SUM(ImpCapitaleAffidato)=0 THEN 0 ELSE ROUND(SUM(LEAST(i.ImpPagato,i.ImpCapitaleAffidato))*100./SUM(i.ImpCapitaleAffidato),2) END AS PercCapitale,
COUNT(sr.IdStoriaRecupero) AS NumAzioni,MAX(DataEvento) AS DataUltimaAzione,
CONCAT('Fino al ',DATE_FORMAT(a.DataFin,'%d/%m')) AS Lotto,YEAR(a.DataFin) AS Anno
from assegnazione a
LEFT JOIN utente u ON a.idAgente=u.IdUtente
LEFT JOIN storiarecupero sr ON sr.IdContratto=a.IdContratto and sr.idutente=a.idagente and sr.dataevento between a.dataini and a.datafin
JOIN v_importi_per_provvigioni_full i ON i.IdContratto=a.idcontratto AND datafineaffido=a.datafin
group by Anno,a.DataFin,idagente;

CREATE OR REPLACE VIEW v_sintesi_agenzia_storica
AS
select a.idagenzia,a.idagente,IFNULL(u.NomeUtente,'[non assegnate]') as Agente,count(distinct a.idcontratto) as NumInsoluti,
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

CREATE OR REPLACE VIEW v_partite_semplici
AS
select IdContratto,NumRata,MAX(TitoloTipoInsoluto) AS TitoloTipoInsoluto,
MAX(CASE WHEN DataScadenza IS NOT NULL AND Importo>0 THEN DataScadenza WHEN Importo>0 THEN DataCompetenza ELSE NULL END) AS DataScadenza,
MAX(CASE WHEN Importo<0 AND (CategoriaMovimento='P' OR CategoriaMovimento IS NULL) THEN DataCompetenza ELSE NULL END) AS DataPagamento,
IFNULL(MAX(CASE WHEN Importo<0 AND CategoriaMovimento='P' THEN TitoloTipoMovimento ELSE NULL END),
       CASE WHEN SUM(Importo)<=0 AND SUM(IF(m.IdTipoMovimento=163,1,0))>0 THEN 'RID' ELSE '' END) AS CausalePagamento,
MAX(CASE WHEN CategoriaMovimento='C' THEN Importo ELSE NULL END) AS Rata,
CASE WHEN SUM(Importo)=0 THEN NULL ELSE SUM(Importo) END AS Debito
FROM movimento m
JOIN tipomovimento t ON m.idtipomovimento=t.idtipomovimento
LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto
GROUP BY IdContratto,NumRata;

create or replace view v_recapito_di_tipo
AS
select * from recapito r where indirizzo>''
  and not exists 
  (select 1 from recapito x where x.idcliente=r.idcliente and x.idtiporecapito=r.idtiporecapito 
  and indirizzo>'' and x.idrecapito>r.idrecapito);

CREATE OR REPLACE VIEW v_storiarecupero
AS
SELECT IFNULL(a.titoloAzione,'(azione automatica)') AS titoloAzione,
a.CodAzione,
IFNULL(u.UserId,'system') as UserId,
sr.IdStoriaRecupero,
sr.IdContratto,
sr.DataEvento,
sr.DescrEvento,
IF(te.IdTipoEsito IS NULL,NotaEvento,CONCAT(TitoloTipoEsito,'. ',NotaEvento)) AS NotaEvento,
sr.IdSuper as IdSuper,
ut.USerid as UserSuper
FROM storiarecupero sr
LEFT JOIN azione a ON sr.IdAzione=a.IdAzione
LEFT JOIN utente u ON sr.IdUtente=u.IdUtente
LEFT JOIN tipoesito te ON te.idtipoesito=sr.idtipoesito
LEFT JOIN utente ut ON sr.IdSuper=ut.IdUtente;

CREATE OR REPLACE  VIEW v_nota AS select
(case when (isnull(n.IdReparto) and isnull(n.IdUtenteDest)) then _utf8'T'
	  when (c.IdTipoCompagnia = 1) then _utf8'R'
	  when (c.IdTipoCompagnia = 2) then _utf8'A' 
	  when (n.IdUtenteDest is not null) then _utf8'U'
	  else NULL end) AS TipoDestinatario,
r.TitoloUfficio AS ufficio,
m.NomeUtente AS autore,
d.NomeUtente AS destinatario,
n.IdNota AS IdNota,
n.IdUtenteDest AS IdUtenteDest,
n.IdUtente AS IdUtente,
n.IdContratto AS IdContratto,
n.TipoNota AS TipoNota,
n.IdReparto AS IdReparto,
n.TestoNota AS TestoNota,
n.DataCreazione AS DataCreazione,
n.DataScadenza AS DataScadenza,
CASE WHEN Date_Format(DataScadenza,'%H:%i')='00:00' THEN NULL ELSE Date_Format(DataScadenza,'%H:%i') END AS OraScadenza,
n.DataIni AS DataIni,
n.DataFin AS DataFin,
n.LastUpd AS LastUpd,
n.LastUser AS LastUser,
n.FlagRiservato AS FlagRiservato,
n.IdNotaPrecedente AS IdNotaPrecedente,
CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato,
n.IdSuper as IdSuper,
ut.Userid as UserSuper
from nota n 
left join reparto r on r.IdReparto = n.IdReparto
left join compagnia c on c.IdCompagnia = r.IdCompagnia
left join utente m on n.IdUtente = m.IdUtente
left join utente d on n.IdUtenteDest = d.IdUtente
LEFT JOIN utente ut ON n.IdSuper=ut.IdUtente;

CREATE OR REPLACE VIEW v_contratto_precrimine
AS
select c.*,DATE_FORMAT(ip.DataInsoluto,'%e/%m') AS ScadenzaPrecrimine,CAST(ip.DataInsoluto as char) as Riferimento
FROM v_contratto_lettera c
LEFT JOIN insolutoprecrimine ip ON ip.idcontratto=c.idcontratto
AND ip.DataInsoluto >= CURDATE() AND NOT EXISTS (SELECT 1 FROM insolutoprecrimine x where ip.idcontratto=x.idcontratto
and x.idinsoluto<ip.idinsoluto);

create or replace view v_dettaglio_insoluto
as
select c.IdContratto,c.IdOperatore,c.IdAgenzia,c.idagente,
sum(case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale) else 0 end) as Capitale,
case when flagInteressimora = 'Y' then c.impinteressimora else 0 end as InteressiMora,
sum(case when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) then i.impinsoluto else 0 end) as AltriAddebiti,
case when (rr.impspeseincasso is not null) then rr.impspeseincasso
else round(rr.percspeseincasso*sum(case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale) else 0 end)/100,2)
 end as Speseincasso,c.IdClasse
from insoluto i
right join contratto c on i.idContratto=c.idContratto
left join prodotto p ON c.IdProdotto=p.IdProdotto
left join famigliaprodotto f on f.IdFamiglia=p.IdFamiglia
left join assegnazione a ON a.idContratto=c.IdContratto AND a.IdAgenzia=c.IdAgenzia AND a.DataFin > CURDATE()
left join regolaprovvigione rp ON rp.CodRegolaProvvigione=c.CodRegolaProvvigione
left join regolaripartizione rr on rr.Idregolaprovvigione=rp.IdRegolaProvvigione
where i.idinsoluto is not null
group by c.idcontratto;

/*
  I contatori hanno i seguenti significato
  NotaUtenteNonAut: nota vista da utente non autorizzato (alle note riservate) e senza visibilità su reparto
  NotaUtenteAut:    nota vista da utente autorizzato e senza visibilità su reparto
  NotaRepartoNonAut: nota vista da utente non autorizzato ma con visibilità sul reparto
  NotaRepartoAut:   nota vista da utente autorizzato e con visibilità sul reparto
  NotaSuper:        nota vista dal supervisore (tutte)
*/
CREATE OR REPLACE VIEW v_note_utente_plus (IdNota,IdContratto,IdUtente,IdCreatore,NotaUtenteNonAut,NotaUtenteAut,NotaRepartoNonAut,NotaRepartoAut,NotaSuper)
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

create or replace view v_lotti_provvigioni
AS
select distinct a.idAgenzia,r.idregolaprovvigione as IdRegola,
CONCAT('Fino al ',DATE_FORMAT(a.DataFin,'%d/%m')) AS Lotto,a.DataFin as DataFineAffido
FROM regolaprovvigione r
join assegnazione a on a.idAgenzia=r.idReparto;

CREATE OR REPLACE VIEW v_data_ultima_azione
AS
SELECT IdContratto,DATE(MAX(DataEvento)) AS DataUltimaAzione
FROM   storiarecupero
WHERE  DATE(DataEvento)<=CURDATE() AND IdAzione>0 AND IdUtente>0
GROUP BY IdContratto;

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
CASE WHEN DataUltimaAzione=CURDATE() THEN 'Y' WHEN DataUltimaAzione between DataInizioAffido AND CURDATE() THEN 'P' WHEN DataUltimaAzione IS NULL THEN 'N' ELSE 'N' END AS CiSonoAzioniOggi,
co.IdClasse,co.IdStatoRecupero,DataUltimoPagamento,DataPrimaScadenza,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
ImpInteressiMora,ImpSpeseRecupero,CodRegolaProvvigione,co.ImpCapitale,co.PercSvalutazione,((co.PercSvalutazione/100)*co.ImpInsoluto) as Svalutazione
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

CREATE OR REPLACE ALGORITHM=MERGE VIEW  v_insoluti_positivi
AS
select co.IdContratto,concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,u.CodUtente,
u.NomeUtente AS operatore,co.IdOperatore,co.IdAgente,ag.Userid as CodAgente,
ifnull(c.Nominativo,c.RagioneSociale) AS cliente,co.NumRata AS rata,co.NumInsoluti AS insoluti,DATEDIFF(CURDATE(), DataRata) AS Giorni,
co.ImpInsoluto,co.ImpCapitale AS importo,co.ImpPagato,co.DataRata AS DataScadenza,sc.CodStatoRecupero AS stato,
sc.AbbrStatoRecupero,co.CodRegolaProvvigione,
cl.CodClasse AS classif,cl.AbbrClasse AS AbbrClasse,r.TitoloUfficio AS agenzia,
co.IdCliente,CodTipoPagamento AS tipoPag,p.IdFamiglia,sc.Ordine AS OrdineStato,sc.CodStatoRecupero,co.IdAgenzia,
cl.FlagNoAffido,co.DataCambioStato,co.DataCambioClasse,NULL AS DataScadenzaAzione,co.DataInizioAffido,co.DataFineAffido,
u.IdReparto, c.Telefono,  'N' as CiSonoAzioniOggi,DataUltimoPagamento,co.IdStatoRecupero,co.idClasse,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale
,co.ImpCapitale
from contratto co
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statorecupero sc on sc.IdStatoRecupero = co.IdStatoRecupero
left join classificazione cl on cl.IdClasse = co.IdClasse
left join tipopagamento tp on co.IdTipoPagamento=tp.IdTipoPagamento
left join utente u on u.IdUtente = co.IdOperatore
left join utente ag on co.IdAgente = ag.IdUtente
left join reparto r on r.IdReparto = co.IdAgenzia
WHERE CodClasse='POS' OR co.ImpInsoluto<26;
   
