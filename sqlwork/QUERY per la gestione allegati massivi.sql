/*Gestione Allegati massivi*/
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2073', 'MENU_GP_ALLMAS', 'Allegati massivi', 'system', '199', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2073', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2073', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2073', '2001-01-01', '9999-12-31', 'system');


ALTER TABLE `db_cnc`.`tipoallegato` 
ADD COLUMN `Pattern` VARCHAR(50) NULL DEFAULT NULL AFTER `Ordine`;

UPDATE `db_cnc`.`funzione` SET `IdGruppo`='169' WHERE `IdFunzione`='2073';