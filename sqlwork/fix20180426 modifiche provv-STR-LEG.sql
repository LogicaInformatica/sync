ALTER TABLE `db_cnc`.`statolegale` 
ADD COLUMN `PercProvvigione` DECIMAL(10,2) NULL DEFAULT NULL AFTER `TitoloStatoLegale`;

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
set Formula='ImpPagatoTotale*IFNULL(PercProvvigioneLegale,0.1)',
AbbrRegolaProvvigione='10% (f1), 5% (f2)', FlagPerPratica='Y',
Condizione='IdStatoRecupero IN (7,25)'
where datafin>now() and fasciarecupero='LEGALE'
;
UPDATE `regolaprovvigione` SET `Formula`=NULL WHERE `IdRegolaProvvigione`='4114';
