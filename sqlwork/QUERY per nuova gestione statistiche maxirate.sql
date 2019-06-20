ALTER TABLE `db_cnc`.`statistichemaxirate` 
DROP FOREIGN KEY `categoriamaxirata_fk`;
ALTER TABLE `db_cnc`.`statistichemaxirate` 
CHANGE COLUMN `IdCategoriaMaxirata` `IdCategoriaMaxirata` INT(11) NULL DEFAULT NULL ;
ALTER TABLE `db_cnc`.`statistichemaxirate` 
ADD CONSTRAINT `categoriamaxirata_fk`
  FOREIGN KEY (`IdCategoriaMaxirata`)
  REFERENCES `db_cnc`.`categoriamaxirata` (`IdCategoriaMaxirata`);


INSERT INTO `db_cnc`.`statistichemaxirate` (`IdContratto`,`IdCategoriaMaxirata`,`ImpInsoluto`,`datamese`) 
SELECT c.IdContratto, c.IdCategoriaMaxirata, c.ImpInsoluto, DATE(sr.DataEvento) as datamese
FROM db_cnc.storiarecupero  sr
JOIN contratto c ON c.IdContratto=sr.IdContratto
where sr.DescrEvento like '%MAX Maxirata non pagata%'
order by DATE(sr.DataEvento);