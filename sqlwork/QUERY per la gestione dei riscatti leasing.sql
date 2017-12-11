/*Creazione nuova tabella per le classificazioni 
 * personalizzabili dei riscatto leasing*/
CREATE TABLE IF NOT EXISTS`categoriariscattoleasing` (
  `IdCategoriaRiscattoLeasing` int(11) NOT NULL AUTO_INCREMENT,
  `CodRiscattoLeasing` varchar(20) DEFAULT NULL,
  `CategoriaRiscattoLeasing` varchar(100) DEFAULT NULL,
  `Ordine` int(11) DEFAULT NULL,
  `LastUpd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LastUser` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`IdCategoriaRiscattoLeasing`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


/*Inserimento categorizzazioni perviste*/
INSERT INTO `db_cnc`.`categoriariscattoleasing` (`IdCategoriaRiscattoLeasing`, `CodRiscattoLeasing`, `CategoriaRiscattoLeasing`, `Ordine`, `LastUser`) VALUES ('1', 'RFI', 'Riscatto finale', '1', 'system');
INSERT INTO `db_cnc`.`categoriariscattoleasing` (`IdCategoriaRiscattoLeasing`, `CodRiscattoLeasing`, `CategoriaRiscattoLeasing`, `Ordine`, `LastUser`) VALUES ('2', 'RIN', 'Rinnovo', '3', 'system');
INSERT INTO `db_cnc`.`categoriariscattoleasing` (`IdCategoriaRiscattoLeasing`, `CodRiscattoLeasing`, `CategoriaRiscattoLeasing`, `Ordine`, `LastUser`) VALUES ('3', 'RES', 'Restituzione', '4', 'system');
INSERT INTO `db_cnc`.`categoriariscattoleasing` (`IdCategoriaRiscattoLeasing`, `CodRiscattoLeasing`, `CategoriaRiscattoLeasing`, `Ordine`, `LastUser`) VALUES ('4', 'ALT', 'Altro', '5', 'system');


/*Inserimento campo IdCategoriaRiscattoLeasing in tabella contratto
 *e gestione foreign key sulla tabella categoriariscattoleasing */
ALTER TABLE `db_cnc`.`contratto` ADD COLUMN `IdCategoriaRiscattoLeasing` INT(11) NULL DEFAULT NULL  AFTER `IdCategoriaMaxirata` , 
  ADD CONSTRAINT `CategoriaRiscattoLeasing`
  FOREIGN KEY (`IdCategoriaRiscattoLeasing` )
  REFERENCES `db_cnc`.`categoriariscattoleasing` (`IdCategoriaRiscattoLeasing` )
  ON DELETE RESTRICT
  ON UPDATE RESTRICT
, ADD INDEX `CategoriaRiscattoLeasing_idx` (`IdCategoriaRiscattoLeasing` ASC) ;


/*Inserimento campo "CategoriaRiscattoLeasing" in _opt_isoluti
 *e gestione index*/
ALTER TABLE `db_cnc`.`_opt_insoluti` ADD COLUMN `CategoriaRiscattoLeasing` VARCHAR(100) NULL DEFAULT NULL  AFTER `CategoriaMaxirata` 
, ADD INDEX `_opt_insoluti_categoriariscattoleasing` (`CategoriaRiscattoLeasing` ASC) ;


/*Gestione Azione Cambia categoria maxirata*/
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('467', 'MENU_CONF_RL', 'Configurazione categoria riscatto leasing', 'system', '173', 'Menu');
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2066', 'AZIONE_RL', 'Cambio categoria riscatto leasing', 'system', '199', 'Azioni');
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2067', 'MENU_GP_RL', 'Gestione pratica in riscatto leasing', 'system', '169', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '467', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '467', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '467', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2066', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2', '2066', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2066', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2066', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2067', '2001-01-01', '9999-12-31', 'system');

INSERT INTO `db_cnc`.`azione` (`IdAzione`, `IdFunzione`, `CodAzione`, `TitoloAzione`, `DataIni`, `DataFin`, `LastUser`, `TipoFormAzione`, `FlagMultipla`) VALUES ('2066', '2066', 'CCM', 'Cambio categoria riscatto leasing', '2001-01-01', '9999-12-31', 'system', 'CambioCatRiscLeasing', 'Y');

INSERT INTO `db_cnc`.`azionetipoazione` (`IdAzione`, `IdTipoAzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2066', '5', '2001-01-01', '9999-12-31', 'system');

INSERT INTO `db_cnc`.`statoazione` (`IdStatoAzione`, `IdAzione`, `DataIni`, `DataFin`, `LastUser`, `IdStatoRecupero`) VALUES ('2066', '2066', '2001-01-01', '9999-12-31', 'system', '13');