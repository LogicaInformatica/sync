ALTER TABLE statolegale 
ADD COLUMN PercProvvigione DECIMAL(10,2) NULL DEFAULT NULL AFTER TitoloStatoLegale;

set session foreign_key_checks=0;
REPLACE INTO statolegale VALUES (
'1', 'AMP', 'Ammissione al passivo', '5.00', '2018-04-26 15:46:21', 'difalco'),(
'2', 'CAU', 'Causa passiva', '5.00', '2018-04-26 15:46:21', NULL),(
'3', 'CHI', 'Chiusa (Fase 1)', '10.00', '2018-04-26 15:46:21', NULL),(
'4', 'NEG', 'Chiusa/negativa', '0.00', '2018-04-26 15:46:21', NULL),(
'5', 'REC', 'Chiusa/Rec.Veicolo (Fase 1)', '10.00', '2018-04-26 15:46:21', NULL),(
'6', 'TRA', 'Chiusa/Transazione (Fase 1)', '10.00', '2018-04-26 15:46:21', NULL),(
'7', 'ING', 'Decreto ingiuntivo', '10.00', '2018-04-26 15:46:21', NULL),(
'8', 'DIF', 'Diffida', '10.00', '2018-04-26 15:46:21', NULL),(
'9', 'ESE', 'Esecuzione consegna (Fase 1)', '10.00', '2018-04-26 15:46:21', NULL),(
'10', 'FAL', 'Istanza di fallimento', '5.00', '2018-04-26 15:46:21', NULL),(
'11', 'RIV', 'Istanza di rivendica', '5.00', '2018-04-26 15:46:21', NULL),(
'12', 'OPP', 'Opposizione decreto ingiuntivo', '5.00', '2018-04-26 15:46:21', NULL),(
'13', 'ORD', 'Ordinanza assegnazione in ordine al pignoramento', '5.00', '2018-04-26 15:46:21', NULL),(
'14', 'PIG', 'Pignoramento immobilare', '5.00', '2018-04-26 15:46:21', NULL),(
'15', 'PPT', 'Pignoramento presso terzi', '5.00', '2018-04-26 15:46:21', NULL),(
'16', 'FRO', 'Pignoramento prezzo terzi frozen', '5.00', '2018-04-26 15:46:21', NULL),(
'17', 'PRE', 'Precetto', '10.00', '2018-04-26 15:46:21', NULL),(
'18', 'QUE', 'Querela', '10.00', '2018-04-26 15:46:21', NULL),(
'19', 'TIC', 'Transazione in corso (Fase1)', '10.00', '2018-04-26 15:46:21', NULL),(
'20', 'REQ', 'Remissione di querela', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'21', 'PIM', 'Pignoramento mobiliare', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'22', 'RIN', 'Rintraccio (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'23', 'NAF', 'Nuovo affidamento', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'24', 'CCH', 'Credito chirografario', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'25', 'RVE', 'Ripossesso veicolo (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'26', 'CPR', 'Concordato preventivo', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'27', 'INP', 'Insinuazione al passivo', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'28', 'DEC', 'Decesso (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'29', 'RIS', 'Ricorso per sequestro (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'30', 'GAP', 'Giudizio di appello', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'31', 'FAC', 'Fallimento chiuso', '5.00', '2018-04-26 15:46:21', 'c.desantis'),(
'32', 'IDI', 'Istanza di dissequestro', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'33', 'PPO', 'Perdita di possesso (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'34', 'TRU', 'Truffa', NULL, '2018-01-17 16:03:12', 'c.desantis'),(
'35', 'PCE', 'Prossima cessione', NULL, '2018-01-17 16:03:25', 'c.desantis'),(
'36', 'PPP', 'Prossimo passaggio a perdita (Fase 1)', '10.00', '2018-04-26 15:46:21', 'c.desantis'),(
'37', 'CHI2', 'Chiusa (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'38', 'REC2', 'Chiusa/Rec.Veicolo (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'39', 'TRA2', 'Chiusa/Transazione (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'40', 'ESE2', 'Esecuzione consegna (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'41', 'RIN2', 'Rintraccio (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'42', 'RVE2', 'Ripossesso veicolo (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'43', 'DEC2', 'Decesso (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'44', 'RIS2', 'Ricorso per sequestro (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'45', 'PPO2', 'Perdita di possesso (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL),(
'46', 'PPP2', 'Prossimo passaggio a perdita (Fase 2)', '5.00', '2018-04-26 15:48:54', NULL);

update regolaprovvigione 
set Formula='ImpPagatoTotale*IFNULL(PercProvvigioneLegale/100,0.1)',
AbbrRegolaProvvigione='10% (f1), 5% (f2)', FlagPerPratica='Y',
Condizione='IdStatoRecupero IN (7,25)'
where datafin>now() and fasciarecupero='LEGALE'
;
UPDATE regolaprovvigione SET Formula=NULL WHERE IdRegolaProvvigione='4114';


INSERT INTO target (FasciaRecupero, FY, Valore, Ordine, DataIni, DataFin, ENDFY, Gruppo) VALUES ('LEGALE', '2018', '0', '300', '2015-01-01', '9999-12-31', '9999', '4');

UPDATE funzione SET IdGruppo='501' WHERE IdFunzione='116';
UPDATE funzione SET IdGruppo='501' WHERE IdFunzione='293';
UPDATE funzione SET IdGruppo='501' WHERE IdFunzione='409';
UPDATE funzione SET IdGruppo='501' WHERE IdFunzione='207';
UPDATE funzione SET IdGruppo='501' WHERE IdFunzione='258';


INSERT INTO funzione values(
'2070', 'MENU_GP_GRAF_MAXRAT', 'Grafici statistiche maxirate', '2018-03-23 16:17:59', 'system', '501', 'Menu', NULL),(
'2072', 'MENU_GP_GRAF_RISLEAS', 'Grafici riscatti scaduti', '2018-03-23 16:41:28', 'system', '501', 'Menu', NULL
);
insert into profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin)
select IdProfilo,2070,DataIni,DataFin from profilofunzione 
where idfunzione=409;
insert into profilofunzione (IdProfilo,IdFunzione,DataIni,DataFin)
select IdProfilo,2072,DataIni,DataFin from profilofunzione 
where idfunzione=409;

UPDATE reparto SET CodUfficio='L99,M1,M2,RS', TitoloUfficio='Toyota FS recupero' WHERE IdReparto='1040';

INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, DataIni, DataFin, CodRegolaProvvigione, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved) VALUES ('5000', '1040', '2018-05-01', '9999-12-31', 'M1', '-', 'maxirata fase1', 'MAXIRATA', 'IdClasse=38', '60', 'N', 'N', 'N');
UPDATE regolaprovvigione SET AbbrRegolaProvvigione='-' WHERE IdRegolaProvvigione='4114';
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, DataIni, DataFin, CodRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved) VALUES ('5001', '1040', '2018-05-01', '9999-12-31', 'M2', 'maxirata fase2', 'MAXIRATA', 'IdClasse=38', '30', 'N', 'N', 'N');
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, DataIni, DataFin, CodRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved) VALUES ('5100', '1040', '2018-05-01', '9999-12-31', 'RS', 'riscatto', 'RISCATTO', 'IdAttributo=\'RS\'', '60', 'N', 'N', 'N');
UPDATE regolaprovvigione SET durata='30' WHERE IdRegolaProvvigione='5100';

INSERT INTO regolaassegnazione VALUES(
'4123', '60', NULL, NULL, NULL, '1040', NULL, '2018-05-01', '9999-12-31', '2018-05-10 09:00:03', NULL, NULL, '20', NULL, NULL, 'I', '2', NULL, NULL, 'IdClasse=38 AND IdStatoRecupero=2', '5000'),
('4124', '30', NULL, NULL, NULL, '1040', NULL, '2018-05-01', '9999-12-31', '2018-05-14 15:38:32', NULL, NULL, '21', NULL, NULL, 'I', '2', NULL, NULL, 'IdClasse=38 AND IdStatoRecupero=2 AND CodProvvigionePrecedente=\'M1\'', '5001'),
('4125', '30', NULL, NULL, NULL, '1040', NULL, '2018-05-01', '9999-12-31', '2018-05-14 15:38:32', NULL, NULL, '20', NULL, NULL, 'I', '2', NULL, NULL, 'IdAttributo=\'RS\' AND IdStatoRecupero=2', '5100');


UPDATE classificazione SET FlagManuale='S', FlagNoAffido='N' WHERE IdClasse='36';
UPDATE classificazione SET FlagNoAffido='N' WHERE IdClasse='38';

insert into modello values(
'6', 'Lettera per insoluto maxirata', 'Lettera INS.txt', 'L', NULL, '2001-01-01', '9999-12-31', NOW(), 'system', '6', NULL
);

insert into automatismo values(
'318', 'lettera', 'Stampa lettera INS per Maxirata', NULL, 'CodRegolaProvvigione=\'M1\' AND IdStatoRecupero IN (4,5) AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)', NULL, 'system',NOW(), '6', 'N')

UPDATE automatismo SET Condizione='CodClasse IN (\'R90\',\'B90\',\'M2\',\'RS\')  AND IdStatoContratto=1  AND IdStatoRecupero IN (4,5) AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and idclasse>106 AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r\n' 
WHERE IdAutomatismo='19';
UPDATE automatismo SET Condizione='CodClasse IN (\'R90\',\'B90\',\'M2\',\'RS\') AND IdStatoContratto=1  AND IdStatoRecupero IN (4,5) AND EXISTS (SELECT 1 FROM v_recapiti_mandato v WHERE v.IdContratto=c.IdContratto and FlagGarante=\'Y\') AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and idclasse>106 AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r\n' 
WHERE IdAutomatismo='20';
UPDATE automatismo SET Condizione='c.IdRegolaProvvigione IN (2115,2212,5000,5001,5100) AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and IdRegolaProvvigione IN (2115,2212,5000,5001,5100) AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r\n' 
WHERE IdAutomatismo='113';
UPDATE automatismo SET Condizione='c.IdRegolaProvvigione IN (2115,2212,5000,5001,5100) AND  EXISTS (SELECT 1 FROM v_recapiti_mandato v WHERE v.IdContratto=c.IdContratto and FlagGarante=\'Y\') AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and IdRegolaProvvigione IN (2115,2212,5000,5001,5100) AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r ' 
WHERE IdAutomatismo='115';

INSERT INTO azioneautomatica (IdAzione, IdAutomatismo, DataIni, DataFin, LastUser) 
VALUES ('5', '318', '2018-05-05', '9999-12-31', 'system');

-- serve a far considerare le classi MAX e RIS come candidate all'affido in rielaboraNegative
UPDATE classificazione SET NumGiorniA=9999999 WHERE IdClasse IN (36,38);
UPDATE classificazione SET Ordine='96' WHERE IdClasse='38';

UPDATE `db_cnc`.`regolaprovvigione` SET `Formula`='0' WHERE `IdRegolaProvvigione`='4114';

UPDATE `db_cnc`.`regolaprovvigione` SET `Formula`='0' WHERE `IdRegolaProvvigione`>= 5000;

UPDATE `db_cnc`.`regolaassegnazione` SET `GiorniFissiFine`='4,14,24' WHERE `IdRegolaAssegnazione`='4123';
UPDATE `db_cnc`.`regolaassegnazione` SET `GiorniFissiFine`='4,14,24' WHERE `IdRegolaAssegnazione`='4124';
UPDATE `db_cnc`.`regolaassegnazione` SET `GiorniFissiFine`='4,14,24' WHERE `IdRegolaAssegnazione`='4125';

## rende possibile le azioni di affido sulle pratiche RS/MX
UPDATE `db_cnc`.`statoazione` SET `Condizione`='IdStatoRecupero IN (2,3,4,13) OR IdClasse IN (36,38) OR IdAttributo=86' WHERE `IdStatoAzione`='2';
UPDATE `db_cnc`.`statoazione` SET `Condizione`='IdStatoRecupero IN (2,3,4,5,6,13,25,26)  OR IdClasse IN (36,38) OR IdAttributo=86' WHERE `IdStatoAzione`='7';



### AGGIORNARE VIEWS MODIFICATE