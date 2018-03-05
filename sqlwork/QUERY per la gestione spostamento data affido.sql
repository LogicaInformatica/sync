/*Gestione Modifica Data Affidi*/
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2071', 'MENU_MOD_DATA_AFF', 'Modifica data affidi', 'system', '194', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2071', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2071', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('9', '2071', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2071', '2001-01-01', '9999-12-31', 'system');

CREATE TABLE `db_cnc`.`dataaffido` (
  `IdDataAffido` INT NOT NULL AUTO_INCREMENT,
  `DataAffidoStandard` DATE NULL DEFAULT NULL,
  `DataAffidoVariata` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`IdDataAffido`));
  
ALTER TABLE `db_cnc`.`dataaffido` 
ADD COLUMN `LastUpd` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `DataAffidoVariata`,
ADD COLUMN `LastUser` VARCHAR(20) NULL DEFAULT NULL AFTER `LastUpd`;
  