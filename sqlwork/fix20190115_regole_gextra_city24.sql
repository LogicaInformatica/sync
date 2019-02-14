SET @DATAINIZIO='2019-01-15';
SET @PRIMAFINEAFFIDO = @DATAINIZIO+INTERVAL 1 MONTH-INTERVAL 1 DAY;
SELECT  @PRIMAFINEAFFIDO;

-- fa scadere provvigione 24
UPDATE regolaprovvigione SET datafin=@PRIMAFINEAFFIDO-INTERVAL 1 DAY WHERE IdRegolaProvvigione=2114;
-- corregge condizione in regola 29
UPDATE regolaprovvigione SET condizione='(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2' WHERE IdRegolaprovvigione=1041;
-- crea nuova regola provvigione per 24
INSERT INTO regolaprovvigione VALUES(
3114, 3, NULL, NULL, 'NumViaggianti*10', @PRIMAFINEAFFIDO, '9999-12-31', NOW(), 'system', '24', 'IPR', NULL, 'Loan 91-120gg',
 '3° HOME LOAN', '6', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', '30', 'N', 'N', NULL, NULL);
-- correzione fascia errata
UPDATE fasciaprovvigione SET AbbrFasciaProvvigione='20% Cap.reale+22%(idm+sp.)' WHERE IdRegolaProvvigione='1041' and ValoreSoglia='25.00' and DataIni='2019-01-01';
-- Copia fasce 1041 in 3114
INSERT INTO fasciaprovvigione
(IdRegolaProvvigione,ValoreSoglia,Formula,AbbrFasciaProvvigione,LastUser,lastupd,DataIni,DataFin)
SELECT 3114,ValoreSoglia,Formula,AbbrFasciaProvvigione,LastUser,lastupd,DataIni,DataFin
FROM fasciaprovvigione WHERE IdRegolaProvvigione=1041;
-- crea regola ripartizione
INSERT INTO regolaripartizione VALUES(
3114, NULL, '15.00', NULL, 'Y',  @PRIMAFINEAFFIDO, '9999-12-31', NOW(), 'system', NULL, NULL, 3114);

-- Termina la regola di assegnazione per 2114 e per 1041 (quella provvisoria creata nei giorni scorsi)
update regolaassegnazione set datafin=@DATAINIZIO-interval 1 day where idregolaassegnazione in (2165,1041);

-- crea regole assegnazione gextra
INSERT INTO regolaassegnazione (DurataAssegnazione,IdReparto,DataIni,DataFin,IdArea,Ordine,TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio,
 GiorniFissiFine, Condizione, IdRegolaProvvigione) VALUES
(30,1041,@DATAINIZIO, '9999-12-31', 15, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 2, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 5, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 4, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 16, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 19, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 1, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 8, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,1041,@DATAINIZIO, '9999-12-31', 18, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 1041),
(30,3,@DATAINIZIO, '9999-12-31', 3, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 6, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 7, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 9, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 10, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 11, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 12, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 13, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 14, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 17, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114),
(30,3,@DATAINIZIO, '9999-12-31', 20, 100, 'I', '2', '5,15,25', '4,14,24', '(IdClasse=109 or IdProdotto IN (165,236)) AND IdStatoRecupero=2', 3114);

-- Spegne regola provvigione '21' loan 150 gg
UPDATE regolaprovvigione SET datafin=@PRIMAFINEAFFIDO WHERE idregolaprovvigione=2115;

-- Crea nuova regola provvigione '21' loan 120 gg
INSERT INTO regolaprovvigione (IdRegolaProvvigione,IdReparto,DataIni,DataFin,CodRegolaProvvigione, FormulaFascia, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved) VALUES
(3115, 21, @PRIMAFINEAFFIDO, '9999-12-31','2A', 'IPR','Loan >120gg', '4° HOME LOAN', 7, 'CodContratto NOT LIKE ''LE%'' AND IdClasse IN (111,112)', 60, 'N', 'N', '3'); 


-- Spegne regola assegnazione 21 loan 150 gg
UPDATE regolaassegnazione SET datafin=@DATAINIZIO-INTERVAL 1 DAY WHERE idregolaassegnazione=2166;

-- Crea nuova regola assegnazione 21 loan 120 gg
INSERT INTO regolaassegnazione (DurataAssegnazione, IdReparto,DataIni, DataFin, Ordine,  TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, Condizione, IdRegolaProvvigione) VALUES
(60, 21,@DATAINIZIO, '9999-12-31',20,'I', '2', '5,15,25', '4,14,24', 'IdClasse IN (111,112) AND IdStatoRecupero=2', 3115);

-- copia regole calcolo provvigioni
INSERT INTO fasciaprovvigione
(IdRegolaProvvigione,ValoreSoglia,Formula,AbbrFasciaProvvigione,LastUser,lastupd,DataIni,DataFin)
SELECT 3115,ValoreSoglia,Formula,AbbrFasciaProvvigione,LastUser,NOW(),DataIni,'9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2115 AND DataFin>CURDATE();

-- crea regola ripartizione
INSERT INTO regolaripartizione VALUES(
3115, NULL, '15.00', NULL, 'Y',  @PRIMAFINEAFFIDO, '9999-12-31', NOW(), 'system', NULL, NULL, 3115);

