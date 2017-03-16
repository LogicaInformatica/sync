<?php
require_once("commonbatch.php");
//----------------------------------------------------------------------------------------------------------------------
// confermaEsito
// Scopo: 		Imposta = C (confermato) lo status su ImportLog per le righe corrispondenti al batch indicato
//                
// Argomenti:		from:	sigla del committente (cio� del sistema legacy che ha inviato i dati; valori attuali TFIS e TKGI)
//                  type:   tipofile (uguale a quello passato alla import.php)
//
// Risposta: nessuna
// 			U\t messaggio		OK
//			K\t messaggio		KO
//
// La risposta termina con T \t numrighe
//----------------------------------------------------------------------------------------------------------------------
$from = strtoupper($_REQUEST["from"].$_REQUEST["FROM"]);
$type = $_REQUEST["type"].$_REQUEST["TYPE"];
trace("entrata confermaeEsito.php con from=$from,type=$type",false);

if ($from=="")
	die ("K\tParametro 'from' assente\nT\t1");

if ($type=="")
	die ("K\tParametro 'type' assente\nT\t1");
//---------------------------------------------------------------------------
// Aggiorna il DB
//---------------------------------------------------------------------------
$conn = getDbConnection();
if ($conn) {
	if (!execute("UPDATE importlog SET Status='C' WHERE Status='P' AND FileType='$type' AND ImportResult>' '"
		."  AND FileType='$type' AND IdCompagnia = (SELECT IdCompagnia FROM Compagnia C WHERE CodCompagnia='$from'"
		." AND NOW() BETWEEN C.DataIni AND C.DataFin)"))
		die ("K\t".getLastError()."\nT\t1");
	
	// Risposta con record tappo
	$n = getAffectedRows();
	echo "U\tAggiornate $n righe\t1";
	trace("Richiesta confermaEsito per filetype $type; risposta: U Aggiornate $n righe 1",FALSE);
	closeDbConnection();
}
?>