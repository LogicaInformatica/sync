# Attribuisce la Lombardia anche a Nicol-Seripa (e' per ora solo su Starcredit)
INSERT INTO regolaassegnazione VALUES(null, '30', NULL, NULL, NULL, '31', NULL,CURDATE(), '9999-12-31', NOW(), 'system', '3', '20', NULL, 
NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse IN (105,106)  and IdStatoRecupero=2', NULL);

# crea le regole provvigioni per 91-120 per City e CSS (rimane nominato come III Home)
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  6003, IdReparto, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','44', FormulaFascia, 
AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, '(IdClasse=109 OR IdProdotto IN (165,236))', durata, FlagNoRientro, FlagMensile, 
FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  6025, 25, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','45', FormulaFascia, 
AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, '(IdClasse=109 OR IdProdotto IN (165,236))', durata, FlagNoRientro, FlagMensile, 
FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

# disattiva la regola 2114 e la regolaassegnazione corrispondente (id=2165)
UPDATE regolaprovvigione SET DataFin=CURDATE()+INTERVAL 1 MONTH-INTERVAL 1 DAY WHERE IdRegolaProvvigione=2114; 
UPDATE regolaassegnazione SET DataFin=CURDATE()+INTERVAL 1 MONTH-INTERVAL 1 DAY WHERE IdRegolaProvvigione=2114; 
UPDATE regolaripartizione SET DataFin=CURDATE()+INTERVAL 1 MONTH-INTERVAL 1 DAY WHERE IdRegolaProvvigione=2114; 

# crea le regole provvigioni per 120-150 per City e CSS (III Home>IV Home)
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  7003, IdReparto, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','44', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, 
FasciaRecupero=REPLACE(FasciaRecupero,'3','4'), Ordine, 'IdClasse=111', durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  7025, 25, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','45', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, 
FasciaRecupero=REPLACE(FasciaRecupero,'3','4'), Ordine, 'IdClasse=111', durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;
   
# copia le fasceprovvigioni 2114 sulle nuove regole (provvisoriamente, finché non ricevo notizie da Claudio sul calcolo provvigioni per le nuyove regole)
INSERT INTO fasciaprovvigione (IdRegolaProvvigione, ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, lastupd, DataIni, DataFin) 
SELECT 6003,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2114 AND DataFin>CURDATE()
UNION ALL
SELECT 6025,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2114 AND DataFin>CURDATE()
UNION ALL
SELECT 7003,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2114 AND DataFin>CURDATE()
UNION ALL
SELECT 7025,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2114 AND DataFin>CURDATE()
;

# copia la regolaripartizione della 2114
INSERT INTO regolaripartizione (IdRegolaRipartizione, IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, DataIni, DataFin, lastupd, LastUser, IdFamiglia, IdReparto, IdRegolaProvvigione) 
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 3, 6003
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 25, 6025
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 3, 7003
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 3, 7025
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
;

# copia la regola di assegnazione 2114
INSERT INTO regolaassegnazione (IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni,
 DataFin, lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, 
 Condizione, IdRegolaProvvigione) 
VALUES 
	(6003,30,null,null,null,3,null,CURDATE(),'9999-12-31',NOW(),'system',null,10,0.00,0.00,'I','2','5,15,25','4,14,24','(IdClasse=109 OR IdProdotto IN (165,236)) AND IdStatoRecupero=2',6003)
	,(7003,30,null,null,null,3,null,CURDATE(),'9999-12-31',NOW(),'system',null,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=111 AND IdStatoRecupero=2',7003)
	,(6025,30,null,null,null,3,null,CURDATE(),'9999-12-31',NOW(),'system',null,10,0.00,0.00,'I','2','5,15,25','4,14,24','(IdClasse=109 OR IdProdotto IN (165,236)) AND IdStatoRecupero=2',6025)
	,(7025,30,null,null,null,3,null,CURDATE(),'9999-12-31',NOW(),'system',null,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=111 AND IdStatoRecupero=2',7025)
;

# crea la regolaprovvigione per FIRE analoga a quella esistente per Osirc 2A (2115: Loan>150gg)
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  2127, 27, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','2B', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2115;

# cambia 4a fascia in 5a fascia
UPDATE regolaprovvigione set FasciaRecupero=REPLACE(FasciaRecupero,'4','5') WHERE DataFin>CURDATE() AND FasciaRecupero LIKE '4%';

# copia le fasceprovvigioni 2115 sulla nuove regola 2B
INSERT INTO fasciaprovvigione (IdRegolaProvvigione, ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, lastupd, DataIni, DataFin) 
SELECT 2127,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2115 AND DataFin>CURDATE();

# copia la regolaripartizione della 2115
INSERT INTO regolaripartizione (IdRegolaRipartizione, IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, DataIni, DataFin, lastupd, LastUser, IdFamiglia, IdReparto, IdRegolaProvvigione) 
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 
27, 2127
FROM regolaripartizione WHERE IdRegolaProvvigione=2115 
;

# Crea le regole di assegnazione Fire/Osirc distinte per regione
# IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni, DataFin, lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, Condizione, IdRegolaProvvigione
INSERT INTO regolaassegnazione ( IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni, DataFin, 
lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, Condizione, 
IdRegolaProvvigione) VALUES
(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 1, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 2, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 3, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 4, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 5, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 6, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 7, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 8, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 9, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 21, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 10, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2115)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 11, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 12, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 13, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 14, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 15, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 16, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 17, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 18, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 19, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
,(null, 60, NULL, 1, 112, 27, NULL, CURDATE(), '9999-12-31',NOW(), 'system', 20, 20, NULL, NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse=112 AND IdStatoRecupero=2', 2127)
;

# Disattiva la regolaassegnazione attuale di OSIRC
UPDATE regolaassegnazione SET DataFin=CURDATE()-INTERVAL 1 DAY WHERE IdReparto=21 AND DataFin>CURDATE() AND IdArea IS NULL;

# crea righe della tabella target
INSERT INTO target
SELECT REPLACE(FasciaRecupero,'4','5'),2019,valore,ordine,curdate(),'9999-12-31',9999,Gruppo
FROM target WHERE FasciaRecupero LIKE '4%loan';


## aggiornati:
## tabs_Chart.js => ricordarsi di fare tag GIT e far fare deploy su test
## v_geography_pivot_fy.sql, v_geography_pivot.sql




