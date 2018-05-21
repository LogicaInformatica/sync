/*Creazione nuova tabella per le classificazioni 
 * personalizzabili delle maxi-rate*/
CREATE TABLE IF NOT EXISTS`categoriamaxirata` (
  `IdCategoriaMaxirata` int(11) NOT NULL AUTO_INCREMENT,
  `CodMaxirata` varchar(20) DEFAULT NULL,
  `CategoriaMaxirata` varchar(100) DEFAULT NULL,
  `Ordine` int(11) DEFAULT NULL,
  `LastUpd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LastUser` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`IdCategoriaMaxirata`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


/*Inserimento categorizzazioni perviste*/
INSERT INTO `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`, `CodMaxirata`, `CategoriaMaxirata`, `Ordine`, `LastUser`) VALUES ('1', 'IR', 'Insolvenza reale', '1', 'system');
INSERT INTO `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`, `CodMaxirata`, `CategoriaMaxirata`, `Ordine`, `LastUser`) VALUES ('2', 'RT', 'Rifinanziamento tardivo', '2', 'system');
INSERT INTO `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`, `CodMaxirata`, `CategoriaMaxirata`, `Ordine`, `LastUser`) VALUES ('3', 'RI', 'Rinnovo', '3', 'system');
INSERT INTO `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`, `CodMaxirata`, `CategoriaMaxirata`, `Ordine`, `LastUser`) VALUES ('4', 'FS', 'Furto/Sinistro', '4', 'system');
INSERT INTO `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`, `CodMaxirata`, `CategoriaMaxirata`, `Ordine`, `LastUser`) VALUES ('5', 'AL', 'Altro', '5', 'system');


/*Inserimento campo IdCategoriaMaxirata in tabella contratto
 *e gestione foreign key sulla tabella categoriamaxirata */
ALTER TABLE `db_cnc`.`contratto` ADD COLUMN `IdCategoriaMaxirata` INT(11) NULL DEFAULT NULL  AFTER `IdStatoStragiudiziale` , 
  ADD CONSTRAINT `CategoriaMaxirata`
  FOREIGN KEY (`IdCategoriaMaxirata` )
  REFERENCES `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata` )
  ON DELETE RESTRICT
  ON UPDATE RESTRICT
, ADD INDEX `CategoriaMaxirata_idx` (`IdCategoriaMaxirata` ASC) ;


/*Inserimento campo "CategoriaMaxirata" in _opt_isoluti
 *e gestione index*/
ALTER TABLE `db_cnc`.`_opt_insoluti` ADD COLUMN `CategoriaMaxirata` VARCHAR(100) NULL DEFAULT NULL  AFTER `CodCliente` 
, ADD INDEX `_opt_insoluti_categoriamaxirata` (`CategoriaMaxirata` ASC) ;


/*Gestione Azione Cambia categoria maxirata*/
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('466', 'MENU_CONF_MR', 'Configurazione categoria maxirata', 'system', '173', 'Menu');
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2064', 'AZIONE_MAXRAT', 'Cambio categoria maxirata', 'system', '199', 'Azioni');
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2065', 'MENU_GP_MR', 'Gestione pratica in maxirata', 'system', '169', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '466', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '466', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '466', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2064', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2', '2064', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2064', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2064', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2065', '2001-01-01', '9999-12-31', 'system');

INSERT INTO `db_cnc`.`azione` (`IdAzione`, `IdFunzione`, `CodAzione`, `TitoloAzione`, `DataIni`, `DataFin`, `LastUser`, `TipoFormAzione`, `FlagMultipla`) VALUES ('2064', '2064', 'CCM', 'Cambio categoria maxirata', '2001-01-01', '9999-12-31', 'system', 'CambioCatMaxirata', 'Y');

INSERT INTO `db_cnc`.`azionetipoazione` (`IdAzione`, `IdTipoAzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('2064', '5', '2001-01-01', '9999-12-31', 'system');

INSERT INTO `db_cnc`.`statoazione` (`IdStatoAzione`, `IdAzione`, `Condizione`, `DataIni`, `DataFin`, `LastUser`, `IdStatoRecupero`) VALUES ('2064', '2064', 'IdCategoria=1006', '2001-01-01', '9999-12-31', 'system', '13');
UPDATE `db_cnc`.`statoazione` SET `Condizione`='IdClasse=38', `IdStatoRecupero`=NULL WHERE `IdStatoAzione`='2064';