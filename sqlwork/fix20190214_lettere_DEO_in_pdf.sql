-- Nuovi modelli tipo L per generazione automatica
INSERT INTO modello VALUES (
'207', 'Lettera deontologica', 'Lettera DEO.html', 'L', NULL, CURDATE(), '9999-12-31', NOW(), 'system', '7', NULL),(
'208', 'Lettera deontologica (garante)', 'Lettera DEO garante.html', 'L', NULL, CURDATE(), '9999-12-31', NOW(), 'system', '10', NULL),(
'217', 'Lettera DEO maxirata', 'Lettera DEO maxirata.html', 'L', NULL, CURDATE(), '9999-12-31', NOW(), 'system', '7', NULL),(
'218', 'Lettera DEO maxirata (garante)', 'Lettera DEO maxirata garante.html', 'L', NULL,  CURDATE(), '9999-12-31', NOW(), 'system', '10', NULL);

-- fa scadere modelli preesistenti
update modello set datafin=CURDATE()-INTERVAL 1 DAY WHERE IdModello IN (7,8,17,18,74);

-- Modifica automatismi che fanno riferimento ai vecchi modelli
update automatismo set idmodello=idmodello+200 where idmodello in (7,8);

-- Submodello per la lista rate
insert into modello values (
'274', 'SubModDEO', 'SubModDEO.html', 'X', NULL, CURDATE(), '9999-12-31', NOW(), 'system', NULL, NULL);

update modello set titolomodello = 'SubModDEO_old' where idmodello=74;

