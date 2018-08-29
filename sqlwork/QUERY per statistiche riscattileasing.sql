CREATE TABLE `statisticheriscattileasing` (
  `IdStatisticheRiscattoLeasing` int(11) NOT NULL AUTO_INCREMENT,
  `IdContratto` int(11) NOT NULL,
  `IdCategoriaRiscattoLeasing` int(11) NOT NULL,
  `Lotto` int(11) NOT NULL,
  `ImpInsoluto` decimal(10,2) DEFAULT NULL,
  `datamese` date DEFAULT NULL,
  PRIMARY KEY (`IdStatisticheRiscattoLeasing`),
  KEY `CategoriaRiscattoLeasing_idx` (`IdCategoriaRiscattoLeasing`),
  KEY `Contratto_idx` (`IdContratto`),
  CONSTRAINT `statistichecategoriariscattoleasing_fk` FOREIGN KEY (`IdCategoriaRiscattoLeasing`) REFERENCES `categoriariscattoleasing` (`IdCategoriaRiscattoLeasing`),
  CONSTRAINT `statistichecontratto_fk` FOREIGN KEY (`IdContratto`) REFERENCES `contratto` (`IdContratto`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2072', 'MENU_GP_GRAF_RISLEAS', 'Grafici riscatti scaduti', 'system', '440', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2072', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2', '2072', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2072', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('9', '2072', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2072', '2001-01-01', '9999-12-31', 'system');

/*Gestione automatismo batch per i grafici dei riscatti leasing*/
INTO `db_cnc`.`automatismo` (`IdAutomatismo`, `TipoAutomatismo`, `TitoloAutomatismo`, `Comando`, `Condizione`, `Destinatari`, `LastUser`, `IdModello`, `FlagCumulativo`)
VALUES ('320', 'php', 'Gestione statistiche riscatto leasing', 'processStatisticheRiscattiLeasing();', 'DAY(curdate()) IN (4,5,6)', NULL, 'system', NULL, NULL);

INSERT INTO `db_cnc`.`eventosistema` (`IdEvento`, `CodEvento`, `TitoloEvento`, `LastUser`, `FlagSospeso`, `OraInizio`, `OraFine`) 
VALUES ('245', 'STAT_RISCATTILEASING', 'Gestione statistiche riscattiLeasing', 'system', 'Y', '08:29:00', '08:59:00');

INSERT INTO `db_cnc`.`automatismoevento` (`IdEvento`, `IdAutomatismo`, `LastUser`) VALUES ('245', '320', 'system');