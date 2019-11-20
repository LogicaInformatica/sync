# Attribuisce la Lombardia anche a Nicol-Seripa (e' per ora solo su Starcredit)
INSERT INTO regolaassegnazione VALUES(null, '30', NULL, NULL, NULL, '31', NULL,CURDATE(), '9999-12-31', NOW(), 'system', '3', '20', NULL, 
NULL, 'I', '2', '5,15,25', '4,14,24', 'IdClasse IN (105,106)  and IdStatoRecupero=2', NULL);

# crea le regole provvigioni per 91-120 per City e CSS (rimane nominato come III Home)
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  6003, IdReparto, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','44', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, 'IdClasse=109', durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  6025, 25, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','45', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, 'IdClasse=109', durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2114;

# cambia la regola 2114 (potrebbe ricevere solo i prodotti PDR e non più le classi 109,110, oppure sparire): attendo risposta da Claudio
# idem in regolaassegnazione (id=2165)
############

# crea le regole provvigioni per 120-150 per City e CSS (III Home>IV Home?)
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
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), DataFin, NOW(), LastUser, IdFamiglia, 3, 6003
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), DataFin, NOW(), LastUser, IdFamiglia, 25, 6025
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), DataFin, NOW(), LastUser, IdFamiglia, 3, 7003
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
UNION ALL
SELECT null,IdClasse, PercSpeseIncasso, ImpSpeseIncasso, FlagInteressiMora, CURDATE(), DataFin, NOW(), LastUser, IdFamiglia, 3, 7025
FROM regolaripartizione WHERE IdRegolaProvvigione=2114 
;

# copia la regola di assegnazione 2114
INSERT INTO regolaassegnazione (IdRegolaAssegnazione, DurataAssegnazione, IdTipoCliente, IdFamiglia, IdClasse, IdReparto, IdUtente, DataIni, DataFin, lastupd, LastUser, IdArea, Ordine, ImportoDa, ImportoA, TipoDistribuzione, TipoAssegnazione, GiorniFissiInizio, GiorniFissiFine, Condizione, IdRegolaProvvigione) 
VALUES 
	(6003,30,0,0,0,3,0,CURDATE(),'9999-12-31',NOW(),'system',0,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=109 AND IdStatoRecupero=2',6003)
	,(7003,30,0,0,0,3,0,CURDATE(),'9999-12-31',NOW(),'system',0,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=111 AND IdStatoRecupero=2',7003)
	,(6025,30,0,0,0,3,0,CURDATE(),'9999-12-31',NOW(),'system',0,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=109 AND IdStatoRecupero=2',6025)
	,(7025,30,0,0,0,3,0,CURDATE(),'9999-12-31',NOW(),'system',0,10,0.00,0.00,'I','2','5,15,25','4,14,24','IdClasse=111 AND IdStatoRecupero=2',7025)
;


# crea la regolaprovvigione per OSIRC analoga a quella esistente per Fire 2A (2115: Loan>150gg)
INSERT INTO regolaprovvigione (IdRegolaProvvigione, IdReparto, IdClasse, IdFamiglia, Formula, DataIni, DataFin, lastupd, LastUser, CodRegolaProvvigione, FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica)  
SELECT  2121, 21, IdClasse, IdFamiglia, Formula, CURDATE(), '9999-12-31',NOW(),'system','2B', FormulaFascia, AbbrRegolaProvvigione, TitoloRegolaProvvigione, FasciaRecupero, Ordine, Condizione, durata, FlagNoRientro, FlagMensile, FlagCerved, FlagPerPratica
FROM regolaprovvigione 
WHERE IdRegolaProvvigione=2115;

# se confermato da Claudio, cambia 4a fascia in 5a fascia
UPDATE regolaprovvigione set FasciaRecupero=REPLACE(FasciaRecupero,'4','5') WHERE DataFin>CURDATE() AND FasciaRecupero LIKE '4%';

# copia le fasceprovvigioni 2115 sulla nuove regola 2B
INSERT INTO fasciaprovvigione (IdRegolaProvvigione, ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, lastupd, DataIni, DataFin) 
SELECT 6021,ValoreSoglia, Formula, AbbrFasciaProvvigione, LastUser, NOW(), CURDATE(), '9999-12-31'
FROM fasciaprovvigione WHERE IdRegolaProvvigione=2115 AND DataFin>CURDATE();

# Crea le regole di assegnazione Fire/Osirc distinte per regione






