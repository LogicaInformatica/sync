CREATE TABLE `db_cnc`.`statistichemaxirate` (
  `IdStatisticheMaxirate` INT(11) NOT NULL AUTO_INCREMENT,
  `IdContratto` INT(11) NOT NULL,
  `IdCategoriaMaxirata` INT(11) NOT NULL,
  `ImpInsoluto` DECIMAL(10,2) NULL DEFAULT NULL,
  `datamese` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`IdStatisticheMaxirate`),
  INDEX `CategoriaMaxirata_idx` (`IdCategoriaMaxirata` ASC),
  INDEX `Contratto_idx` (`IdContratto` ASC),
  CONSTRAINT `categoriamaxirata_fk`
    FOREIGN KEY (`IdCategoriaMaxirata`)
    REFERENCES `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `contratto_fk`
    FOREIGN KEY (`IdContratto`)
    REFERENCES `db_cnc`.`contratto` (`IdContratto`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
AUTO_INCREMENT = 1;

INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2070', 'MENU_GP_GRAF_MAXRAT', 'Grafici statistiche maxirate', 'system', '440', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2070', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2', '2070', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2070', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('9', '2070', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2070', '2001-01-01', '9999-12-31', 'system');

/*Gestione automatismo batch per i grafici maxirate*/
INSERT INTO `db_cnc`.`automatismo` (`IdAutomatismo`, `TipoAutomatismo`, `TitoloAutomatismo`, `Comando`, `Condizione`, `Destinatari`, `LastUser`, `IdModello`, `FlagCumulativo`)
VALUES ('319', 'php', 'Gestione statistiche maxirate', 'processStatisticheMaxirate();', 'DAY(curdate()) IN (4,5,6)', NULL, 'system', NULL, NULL);

INSERT INTO `db_cnc`.`eventosistema` (`IdEvento`, `CodEvento`, `TitoloEvento`, `LastUser`, `FlagSospeso`, `OraInizio`, `OraFine`) 
VALUES ('244', 'STAT MAXIRATE', 'Gestione statistiche maxirate', 'system', 'Y', '08:29:00', '08:59:00');

INSERT INTO `db_cnc`.`automatismoevento` (`IdEvento`, `IdAutomatismo`, `LastUser`) VALUES ('244', '319', 'system');