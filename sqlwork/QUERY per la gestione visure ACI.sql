/*Gestione Import Visure ACI*/
INSERT INTO `db_cnc`.`funzione` (`IdFunzione`, `CodFunzione`, `TitoloFunzione`, `LastUser`, `IdGruppo`, `MacroGruppo`) VALUES ('2069', 'MENU_GP_VA', 'Importa visure ACI', 'system', '199', 'Menu');

INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('1', '2069', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('3', '2069', '2001-01-01', '9999-12-31', 'system');
INSERT INTO `db_cnc`.`profilofunzione` (`IdProfilo`, `IdFunzione`, `DataIni`, `DataFin`, `LastUser`) VALUES ('10', '2069', '2001-01-01', '9999-12-31', 'system');


INSERT INTO `db_cnc`.`tipoallegato` (`IdTipoAllegato`, `CodTipoAllegato`, `TitoloTipoAllegato`, `DataIni`, `DataFin`, `LastUser`) VALUES ('13', 'VISACI', 'Visura ACI', '2001-01-01', '9999-12-31', 'system');