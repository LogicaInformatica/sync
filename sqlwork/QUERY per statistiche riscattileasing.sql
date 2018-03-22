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
