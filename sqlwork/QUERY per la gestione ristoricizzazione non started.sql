/*Inserimento campo IdCategoriaMaxirata in tabella contratto
 *e gestione foreign key sulla tabella categoriamaxirata */
ALTER TABLE `db_cnc_storico`.`contratto` ADD COLUMN `IdCategoriaMaxirata` INT(11) NULL DEFAULT NULL  AFTER `IdStatoStragiudiziale`;

/*Inserimento campo IdCategoriaRiscattoLeasing in tabella contratto
 *e gestione foreign key sulla tabella categoriariscattoleasing */
ALTER TABLE `db_cnc_storico`.`contratto` ADD COLUMN `IdCategoriaRiscattoLeasing` INT(11) NULL DEFAULT NULL  AFTER `IdCategoriaMaxirata`;

/*Gestione visualizzazione delle visure ACI*/
ALTER TABLE `db_cnc_storico`.`contratto` 
ADD COLUMN `FlagVisuraAci` CHAR(1) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL DEFAULT NULL AFTER `IdCategoriaRiscattoLeasing`;

/*Inserimento campo "CategoriaMaxirata" in _opt_isoluti
 *e gestione index*/
ALTER TABLE `db_cnc_storico`.`_opt_insoluti` ADD COLUMN `CategoriaMaxirata` VARCHAR(100) NULL DEFAULT NULL  AFTER `CodCliente`;

/*Inserimento campo "CategoriaRiscattoLeasing" in _opt_isoluti
 *e gestione index*/
ALTER TABLE `db_cnc_storico`.`_opt_insoluti` ADD COLUMN `CategoriaRiscattoLeasing` VARCHAR(100) NULL DEFAULT NULL  AFTER `CategoriaMaxirata`;