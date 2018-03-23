<?php
require_once('commonbatch.php');
require_once('funzioniStorico.php');

set_time_limit(0);	// aumenta il tempo max di cpu

	echo "INIZIO recupero non started da storicizzazione<br>\n";
	flush();

	beginTrans();
	
	execute("SET group_concat_max_len = 1000000");
	$idContratti = getScalar("SELECT group_concat(IdContratto) FROM db_cnc_storico.contratto WHERE DataDBT < DataDecorrenza + INTERVAL 12 MONTH AND IdStatoRecupero in (79,84)"); 
	
	if (!recuperoStorico("",$idContratti,"","")) {
		rollback();
	} else {
		commit();
	}
		
	echo 'FINE recupero';

?>