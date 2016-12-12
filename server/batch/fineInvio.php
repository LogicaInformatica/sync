<?php
require_once("commonbatch.php");
//----------------------------------------------------------------------------------------------------------------------
// fineInvio
// Scopo: 		Riceve comunicazione dal servizio Windows della fine dell'invio file, insieme alla data di riferimento
//              per i dati acquisiti (cioè a quale giorno di estrazione si riferiscono)
//              Ex.: http://cnctest.tfsi.it/server/batch/fineInvio.php?from=tfsi&date=2015-04-25
// Argomenti:	from:	sigla del committente (cioè del sistema legacy che ha inviato i dati; valori attuali TFIS e TKGI)
//              date:   data di riferimento
//
// Risposta:	U 				Ricezione OK
//	     		K\t messaggio	KO: errore applicativo
//----------------------------------------------------------------------------------------------------------------------
$from = strtoupper($_REQUEST["from"].$_REQUEST["FROM"]);
$date = strtoupper($_REQUEST["date"].$_REQUEST["DATE"]);

if ($from=="")
	die ("K\tParametro 'from' assente");

if ($date=="")
	die ("K\tParametro 'date' assente");

$d = dateFromString($date);
if ($d) {
	if (Date("Y-m-d",$d)!=$date)
		die ("K\tParametro 'date' in formato non valido");
} else {
	die ("K\tParametro 'date' in formato non valido");
}

//---------------------------------------------------------------------------
// Registra l'evento nell'entrata id=1 di ImportLog (il flag ImportResult
// viene messo = P al termine del batch
//---------------------------------------------------------------------------
if (!execute("UPDATE importlog SET ImportTime='$date',ImportResult='U',Status=NULL WHERE IdImportLog=1"))
	die ("K\t".getLastError());
else
    die("U");
?>