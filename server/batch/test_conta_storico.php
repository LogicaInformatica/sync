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

$nt = count($tables);
		
echo "cnctest - aruba | db_cnc | db_cnc_storico<br>\n";
	$it = 1;
	foreach ($tables as $table) {
		echo "$table (".$it++." di $nt) | ".getScalar("SELECT count(*) FROM db_cnc.$table")." | ".getScalar("SELECT count(*) FROM db_cnc_storico.$table")."<br>\n";
		flush();
	}
echo 'FINE';

?>