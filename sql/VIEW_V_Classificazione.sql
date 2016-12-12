CREATE OR REPLACE VIEW `v_classificazione`
 AS select `cl`.`IdClasse` AS `IdClasse`,`cl`.`CodClasse` AS `CodClasse`,`cl`.`TitoloClasse` AS `TitoloClasse`,
 `cl`.`AbbrClasse` AS `AbbrClasse`,`cl`.`CodClasseLegacy` AS `CodClasseLegacy`
 from `classificazione` `cl` order by `cl`.`Ordine`,`cl`.`CodClasse`;