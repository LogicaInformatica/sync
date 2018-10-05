select * from modello where filename like '%.html';

insert into modello values(
'220', 'Lettera DEO/CR maxirate', 'Lettera DEO-CR maxirate.html', 'H', 'N', '2001-01-01', '9999-12-31', NOW(), NULL, '7', NULL),
('221', 'Lettera DEO/CR maxirate (garante)', 'Lettera DEO-CR maxirate GARANTE.html', 'H', 'N', '2001-01-01', '9999-12-31',  NOW(), NULL, '10', NULL);


insert into modello values(
'213', 'Lettera DEO/CR maxirate', 'Lettera DEO-CR maxirate.html', 'L', 'N', '2001-01-01', '9999-12-31', NOW(), NULL, '7', NULL),(
'215', 'Lettera DEO/CR maxirate (garante)', 'Lettera DEO-CR maxirate GARANTE.html', 'L', 'N', '2001-01-01', NOW(), '2016-11-15 17:18:10', NULL, '10', NULL);

insert into automatismo values(
'213', 'lettera', 'Stampa lettera CR-DEO maxirata', NULL, 'c.IdRegolaProvvigione IN (5001,5100) AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and IdRegolaProvvigione IN (5001,5100) AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r\n', NULL, 'system', now(), '213', 'N'),(
'215', 'lettera', 'Stampa lettera CR-DEO maxirata (garante)', NULL, 'c.IdRegolaProvvigione IN (5001,5100) AND  EXISTS (SELECT 1 FROM v_recapiti_mandato v WHERE v.IdContratto=c.IdContratto and FlagGarante=\'Y\') AND NOT exists (select 1 from assegnazione where idcontratto=c.idcontratto and IdRegolaProvvigione IN (5001,5100) AND datafin BETWEEN datainizioaffido - interval 3 day AND datainizioaffido - interval 1 day)\r ', NULL, 'system',NOW(), '215', 'N');

insert into azioneautomatica values(
'5', '213', '2016-11-01', '9999-12-31', NOW(), 'system', NULL),(
'5', '215', '2016-11-01', '9999-12-31', NOW(), 'system', NULL);


select * from automatismo where idmodello in (113,114,115,116,120,121,213,215);

select * from azioneautomatica where idautomatismo in (113,115)
;

select * from regolaprovvigione where idregolaprovvigione in (2115,2212);


