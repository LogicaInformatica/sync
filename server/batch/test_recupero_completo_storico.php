<?php
require_once('commonbatch.php');

set_time_limit(0);	// aumenta il tempo max di cpu

$tables = array(
	'accessorio',
	'allegato',
	'allegatoazionespeciale',
	'assegnazione',
	'attribuzioneincasso',
	'azionespeciale',
	'cliente',
	'clientecompagnia',
	'contratto',
	'controparte',
	'dettaglioprovvigione',
	'incasso',
	'incassovario',
	'insoluto',
	'insolutodipendente',
	'insolutoprecrimine',
	'listagaranti',
	'messaggiodifferito',
	'modificaprovvigione',
	'movimento',
	'movimentoprecrimine',
	'nota',
	'notautente',
	'pianorientro',
	'ratapiano',
	'recapito',
	'storiainsoluto',
	'storiarecupero',
	'storicosvalutazione',
	'writeoff',
	'_opt_note_lette',
	'_opt_insoluti'
);

	echo "INIZIO recupero completo da storicizzazione<br>\n";
	flush();

	execute("SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0") or die(getLastError());
	execute("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0") or die(getLastError());

	beginTrans();
	$it = 1;
	$nt = count($tables);
	foreach ($tables as $table) {
		echo "$table - (".$it++." di $nt)<br>\n";
		flush();
		
		execute("INSERT IGNORE INTO db_cnc.$table SELECT * FROM db_cnc_storico.$table") or die(getLastError());
		execute("DELETE FROM db_cnc_storico.$table") or die(getLastError());
	}

	// Fine transazione
	commit();

	execute("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS") or die(getLastError());
	execute("SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS") or die(getLastError());

	echo 'FINE recupero';

?>