<?php
require_once("commonbatch.php");
//----------------------------------------------------------------------------------------------------------------------
// leggiEsito
// Scopo: 		restituisce al chiamante le informazioni sull'esito di un batch di importazione gia' eseguito
// Argomenti:		from:	sigla del committente (cioe' del sistema legacy che ha inviato i dati; valori attuali TFIS e TKGI)
//                  type:   tipofile (uguale a quello passato alla import.php)
//
// Risposta:
//          W						non tutti i files sono stati ancora elaborati		
// oppure, per ogni file importato
// 			U\t id \t messaggio		file con id=id elaborazione OK
//			K\t id \t messaggio		fiel con id=id KO: nessun aggiornamento effettuato
//		oppure una lista di righe  (per file)nel formato:
//			E\t id \t chiave \t messaggio	errore non recuperabile sul record con chiave data
//			R\t id \t chiave \t messaggio	errore recuperabile (puo' fare retry) sul record con chiave data
//
// La risposta termina con T \t numrighe
//----------------------------------------------------------------------------------------------------------------------
$from = strtoupper($_REQUEST["from"].$_REQUEST["FROM"]);
$type = $_REQUEST["type"].$_REQUEST["TYPE"];
trace("entrata leggiEsito.php con from=$from,type=$type",false);

if ($from=="")
	die ("K\t\tParametro 'from' assente\nT\t1");

if ($type=="")
	die ("K\t\tParametro 'type' assente\nT\t1");

//---------------------------------------------------------------------------
// Prepara la risposta
//---------------------------------------------------------------------------

// Determina se rispondere W (wait) perche' ci sono file ancora da processare
if (rowExistsInTable("importlog I,compagnia C","I.IdCompagnia=C.IdCompagnia AND CodCompagnia='$from'"
					." AND NOW() BETWEEN C.DataIni AND C.DataFin AND (Status IN ('N','R') OR ImportResult IS NULL)"
					." AND FileType='$type'"))
{
	closeDbConnection();
    trace("risposta leggiEsito.php W\nT\t1",false);
	die ("W\nT\t1");
}

$conn = getDbConnection();
if ($conn) {
	// Emette tutte le righe di ImportLog non ancora "inviate e confermate"
	$res = mysqli_query($conn,"SELECT I.* FROM importlog I,compagnia C WHERE I.IdCompagnia=C.IdCompagnia AND CodCompagnia='$from'"
		." AND NOW() BETWEEN C.DataIni AND C.DataFin AND FileType='$type' AND Status='P' AND ImportResult>' '"
		." ORDER BY IdImportLog"); // con questa ORDER BY, il record di tipo U/K � l'ultimo, in quanto l'ultimo ad essere
	                       // aggiornato in porocessImportedFiles
	
	$nrows = 0;
	while ($row = mysqli_fetch_assoc($res)) 
	{
		$resm = mysqli_query($conn,"SELECT * FROM importmessage WHERE IdImportLog=".$row["IdImportLog"]." AND RecordKey>' ' ORDER BY LastUpd");
		while ($rowm = mysqli_fetch_assoc($resm)) 
		{
			$x = $rowm["ErrorType"]."\t".$rowm["RecordKey"]."\t".$rowm["Message"];
            echo $x."\n";
            trace($x,false);
			$nrows++;
		}
		mysqli_free_result($resm);
		$x = $row["ImportResult"]."\t".$row["FileId"]."\t".$row["Message"];
		echo $x."\n";
        trace($x,false);
		$nrows++;
	}
	mysqli_free_result($res);

	// Record finale 
	echo "T\t$nrows";
    trace("T\t$nrows",false);
	closeDbConnection();
}
?>