
##
# ATTENZIONE: APPLICARE IN PRODUZIONE SOLO GLI UPDATE, E GLI INSERT su regolaassegnazione
# il resto è gia' stato applicato il 6/12
##
 
# Attribuisce la Lombardia anche a Nicol-Seripa (e' per ora solo su Starcredit)
select * from regolaassegnazione where idreparto=31 and idarea=3;
INSERT INTO regolaassegnazione VALUES(null, '30', NULL, NULL, NULL, '31', NULL,CURDATE(), '9999-12-31', NOW(), 'system', '3', '20', NULL, 
NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse IN (105,106)  and IdStatoRecupero=2', NULL);

# crea la regole provvigioni per 91-150 per CSS (44)
SELECT * FROM regolaprovvigione WHERE IdRegolaProvvigione=6025;
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, 
LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine,
 Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  6025, 25, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','45', FormulaFascia, 
AbbrRegolaProvvigione, 'Loan 91-150gg', FasciaRecupero, Ordine, Condizione, 30, FlagNoRientro, FlagMensile, 
FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

   
# copia le fasceprovvigioni 2114 sulle nuove regole (provvisoriamente, finché non ricevo notizie da Claudio sul calcolo provvigioni per le nuyove regole)
SELECT * FROM fasciaprovvigione WHERE IdRegolaProvvigione=6025;
INSERT INTO fasciaprovvigione (IdRegolaProvvigione, ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, lastupd, DataIni, DataFin) 
SELECT 6025,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2114 AND DataFin>CURDATE()
;

# copia la regolaripartizione della 2114
SELECT * FROM regolaripartizione WHERE IdRegolaProvvigione=6025;
INSERT INTO regolaripartizione (IdRegolaRipartizione, IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, DataIni, DataFin, lastupd, LastUser, IdFamiglia, IdReparto, IdRegolaProvvigione) 
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 25, 6025
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
;
# copia la regola di assegnazione 2114
select * from regolaassegnazione where idregolaprovvigione=6025;
INSERT INTO regolaassegnazione (IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni,
 DataFin, lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, 
 Condizione, IdRegolaProvvigione) 
VALUES 
	(6025,30,null,null,null,25,null,CURDATE(),'9999-12-31',NOW(),'system',null,10,0.00,0.00,'I','2','5,15,25','4,14,24',
	'(IdClasse IN (109,111) OR IdProdotto IN (165,236)) AND IdStatoRecupero=2',6025)
;
## Corregge data scadenza
UPDATE regolaassegnazione SET DataFin='9999-12-31' WHERE IdRegolaprovvigione=2114;

# crea la regolaprovvigione per FIRE analoga a quella esistente per Osirc 2A (2115: Loan>150gg)
SELECT * FROM regolaprovvigione WHERE IdRegolaProvvigione=2127;
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  2127, 27, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','2B', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2115;

# copia le fasceprovvigioni 2115 sulla nuove regola 2B
SELECT * FROM fasciaprovvigione WHERE IdRegolaProvvigione=2127;
INSERT INTO fasciaprovvigione (IdRegolaProvvigione, ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, lastupd, DataIni, DataFin) 
SELECT 2127,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2115 AND DataFin>CURDATE();

# copia la regolaripartizione della 2115 in 2127
SELECT * FROM regolaripartizione WHERE IdRegolaProvvigione=2127;
INSERT INTO regolaripartizione (IdRegolaRipartizione, IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, DataIni, DataFin, lastupd, LastUser, IdFamiglia, IdReparto, IdRegolaProvvigione) 
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), '9999-12-31', NOW(), LastUser, IdFamiglia, 
27, 2127
FROM regolaripartizione WHERE IdRegolaProvvigione=2115 
;
#aggiorna lettera CR per nuova regola provv. 2127 (fatto in prod)
select * from automatismo  where condizione like '%2115%';
update automatismo 
set condizione = replace(condizione,'2115', '2115,2127') where condizione like '%2115%';

# Crea le regole di assegnazione Fire/Osirc distinte per regione
# IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni, DataFin, lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, Condizione, IdRegolaProvvigione
select * from regolaassegnazione where idregolaprovvigione in (2115,2127);
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

## aggiornati:
## tabs_Chart.js => ricordarsi di fare tag GIT e far fare deploy su test
## v_geography_pivot_fy.sql, v_geography_pivot.sql




