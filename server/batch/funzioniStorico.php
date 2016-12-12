<?php
require_once('commonbatch.php');

$schema_storico = MYSQL_SCHEMA."_storico";

global $client_tables_simple, $contr_tables_in, $contr_tables_simple, $schema_storico;
	$client_tables_simple = array(
		'clientecompagnia',
		'controparte',
		'recapito',
		'incassovario',
		'contratto',
		'cliente'
	);
	$contr_tables_in = array(
		array('tb1' => 'ratapiano',			'id1' => 'idpianorientro',	 	'tb2' => 'pianorientro',	'id2' => 'idpianorientro'),
		array('tb1' => 'notautente',		'id1' => 'idnota',			'tb2' => 'nota',		'id2' => 'idnota'),
		array('tb1' => '_opt_note_lette',		'id1' => 'idnota',			'tb2' => 'nota',		'id2' => 'idnota'),
		array('tb1' => 'allegatoazionespeciale',	'id1' => 'idazionespeciale', 	'tb2' => 'azionespeciale',	'id2' => 'idazionespeciale')
	);
	$contr_tables_simple = array(
		'insolutodipendente',
		'insolutoprecrimine',
		'accessorio',
		'movimento',
		'movimentoprecrimine',
		'storicosvalutazione',
		'pianorientro',
		'nota',
		'dettaglioprovvigione',
		'assegnazione',
		'allegato',
		'azionespeciale',
		'attribuzioneincasso',
		'modificaprovvigione',
		'insoluto',
		'storiainsoluto',
		'incasso',
		'listagaranti',
		'messaggiodifferito',
		'storiarecupero',
		'writeoff',
		'_opt_insoluti'
	);

//==============================================================================================
// Effettua il recupero dal db storico di uno o più clienti e di tutte le tabelle collegate.
//
// Argomenti in query string (uno solo dei seguenti, in ordine):
//   1) cliente		idcliente 
//   2) contratti	lista di idcontratto separati da virgola
//   3) file		path del file in formato import del tipo xxx_yyy_contratti
//	 4) dir			path della directory contenente uno o più file di import del tipo xxx_yyy_contratti
//
//	 default: dir=TMP_PATH."/import
//
// Nota: la funzione esegue anche degli echo perché richiamata come pagina a se stante, sia
// dall'azione "Ripristina da storico", sia da amministratore di sistema per motivi estemporanei
// Quando viene chiamata da cronProcess gli echo non si vedono.
//==============================================================================================
function recuperoStorico($cliente,$listacontratti,$nomefile,$nomedir) {
	global $context, $client_tables_simple, $contr_tables_in, $contr_tables_simple, $schema_storico;

	$msg = "INIZIO RECUPERO DA STORICO PER ";
	//------------------------------------------------------
	// Lettura parametro: idCliente, idContratto o filename
	//------------------------------------------------------
	$where = NULL;
	$contratti = array();
	$clienti = array();
	if ($cliente>'') {
		$where = "idcliente=".$cliente;
	} else {
		if ($listacontratti>'') {
			$where = "idcontratto IN ($listacontratti)";
		}
	}

	if ($where == NULL) {
		if ($nomefile>'') {
			$file = $nomefile;
			$msg .= "file $file";
			if (!restoreFile($file, $contratti, $clienti))
				return false;
		} else{
			$dir = $nomedir>''?$nomedir:TMP_PATH."/import";
			$msg .= "directory $dir";
			if (!restoreDirectory($dir, $contratti,$clienti))
				return false;
		}
		$inStr = implode(",", $contratti);
		if (!($inStr>'') && count($clienti)==0) {
			if ($context["process_name"]>'') {
				writeProcessLog($context["process_name"],"Nessun contratto o cliente da ripristinare");
				return true;
			} else {
				trace("Nessun contratto o cliente da ripristinare",false);
				die('<br><br>FINE RESTORE: nessun contratto da ripristinare');
			}
		}
		$where = "codcontratto IN ($inStr)";
//		if (count($clienti)>0) {
//			$where .= " OR IdCliente IN (SELECT IdCliente FROM $schema_storico.cliente WHERE CodCliente IN (".implode(',',$clienti). "))";	
//		}
	} else {
		$msg .= $where;
	}

	if ($context["process_name"]>'') {
		writeProcessLog($context["process_name"],$msg);
	} else {
		trace($msg,false);
		trace("clausola WHERE iniziale: ".$where);
		echo "$msg<br><br>\n";
		flush();
	}
	
	// drop table tmp_contratti_restore;
	if (!execute("DROP TABLE IF EXISTS $schema_storico.tmp_contratti_restore")) 
	 	include "die_batch.php";
	// Creazione tabella temporanea con i contratti non storicizzabili
	$sql_create_tmp = "CREATE TABLE $schema_storico.tmp_contratti_restore ".
			"(PRIMARY KEY (idcontratto), INDEX `IdCliente` (idcliente), INDEX `IdContrattoDerivato` (idcontrattoderivato), ".
			"INDEX `CodContratto` (codcontratto), INDEX `IdVenditore` (idvenditore), INDEX `IdPuntoVendita` (idpuntovendita)) ".
			"(SELECT idcliente, idcontratto, codcontratto, idcontrattoDerivato, idvenditore, idpuntovendita FROM $schema_storico.contratto WHERE $where)";
	if (!execute($sql_create_tmp)) include "die_batch.php";
	
	// parte della insert sulla tabella $schema_storico.tmp_contratti_restore
	$sql_insert_tmp = "INSERT IGNORE INTO $schema_storico.tmp_contratti_restore ".
			"SELECT c.idcliente, c.idcontratto, c.codcontratto, c.idcontrattoDerivato, c.idvenditore, c.idpuntovendita FROM $schema_storico.contratto c ";
		
	do {
		$cont = false;
	
		// contratti dei clienti con contratti da ripristinare
		if (!execute("$sql_insert_tmp WHERE c.idcliente IN (SELECT DISTINCT idcliente FROM $schema_storico.tmp_contratti_restore)")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "<br>a) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti da cui derivano contratti da ripristinare
		if (!execute("$sql_insert_tmp WHERE c.idcontrattoderivato IS NOT NULL AND c.idcontrattoderivato IN (SELECT idcontratto FROM $schema_storico.tmp_contratti_restore)")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "b) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti derivati da contratti da ripristinare
		if (!execute("$sql_insert_tmp JOIN $schema_storico.tmp_contratti_restore t ON c.idcontratto=t.idcontrattoderivato")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "c) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti di clienti controparti in contratti da ripristinare
		if (!execute("$sql_insert_tmp WHERE c.idcliente IN (SELECT p.idcliente FROM $schema_storico.controparte p JOIN ".
				"$schema_storico.tmp_contratti_restore t ON p.idcontratto=t.idcontratto)")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "d) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti di cui sono controparti clienti controparti o titolari di contratti da ripristinare 
		if (!execute("$sql_insert_tmp WHERE c.idcontratto IN (SELECT DISTINCT c1.idcontratto FROM $schema_storico.controparte c1 WHERE c1.idcliente IN ".
				"(SELECT c2.idcliente idc FROM $schema_storico.controparte c2 WHERE c2.IdContratto IN (SELECT idcontratto FROM $schema_storico.tmp_contratti_restore) UNION ".
				"SELECT idVenditore idc FROM  $schema_storico.tmp_contratti_restore UNION SELECT idPuntoVendita idc FROM  $schema_storico.tmp_contratti_restore UNION ".
				"SELECT idcliente idc FROM $schema_storico.tmp_contratti_restore))")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "d) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti di clienti idVenditore in contratti da ripristinare
		if (!execute("$sql_insert_tmp JOIN (SELECT idVenditore idc FROM $schema_storico.tmp_contratti_restore UNION SELECT idPuntoVendita idc FROM $schema_storico.tmp_contratti_restore) k on c.idcliente=k.idc")) include "die_batch.php";
		$n = getAffectedRows();
	//echo "d-2) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	} while ($cont);
	
	
	// drop table tmp_clienti_restore;
	if (!execute("DROP TABLE IF EXISTS $schema_storico.tmp_clienti_restore")) include "die_batch.php";
	// Creazione tabella temporanea con i clienti storicizzabili
	$sql = "SELECT idcliente FROM $schema_storico.controparte WHERE idcontratto in (SELECT idcontratto FROM $schema_storico.tmp_contratti_restore) UNION ".
		"SELECT idVenditore idcliente FROM $schema_storico.tmp_contratti_restore WHERE idVenditore>0 UNION ".
		"SELECT idPuntoVendita idcliente FROM $schema_storico.tmp_contratti_restore WHERE idPuntoVendita>0 UNION ". 
		"SELECT idcliente FROM $schema_storico.tmp_contratti_restore";
	if (!execute("CREATE TABLE $schema_storico.tmp_clienti_restore (PRIMARY KEY (idcliente)) $sql")) include "die_batch.php";
	
	//-----------------------------------------------------------------------
	// Aggiungo le righe corrispondenti ai clienti trovati nei files
	//-----------------------------------------------------------------------
	if (count($clienti)>0) {
		$sql = "SELECT IdCliente FROM $schema_storico.cliente WHERE CodCliente IN (".implode(",",$clienti). ")";
		$idclienti = fetchValuesArray($sql);
		trace("Clienti da aggiungere, se non già inclusi: $sql",false);
		foreach ($idclienti as $idcliente) {
			if (!execute("INSERT IGNORE INTO $schema_storico.tmp_clienti_restore VALUES($idcliente)")) include "die_batch.php";;
		}
	}
	//-----------------------------------------------------------------------
	// Legge per costruire una traccia dei contratti che saranno trasferiti
	//-----------------------------------------------------------------------
	$sql = "SELECT CodContratto FROM $schema_storico.contratto t JOIN $schema_storico.tmp_clienti_restore c ON t.idcliente=c.idcliente";
	$lista = fetchValuesArray($sql);
	if (is_array($lista))
		trace("Contratti da ripristinare: ".join(",",$lista),false);
	
	//-----------------------------------------------------------------------
	// Dal cliente ottiene tutti gli altri clienti legati dai suoi contratti
	// come controparti, subentranti o subentrati
	//-----------------------------------------------------------------------
	
	if (!execute("SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0")) include "die_batch.php";
	if (!execute("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0")) include "die_batch.php";
	
		beginTrans();
		$it = 1;
		$nt = count($client_tables_simple) +  count($contr_tables_simple) +  count($contr_tables_in);
		foreach ($contr_tables_in as $row) {
			$table = $row['tb1'];
			$table2 = $row['tb2'];
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			$inWhere = $row['id1']." in (SELECT t.".$row['id2']." FROM $schema_storico.$table2 t JOIN $schema_storico.tmp_contratti_restore c ON t.idcontratto=c.idcontratto)";
			if (!execute("INSERT IGNORE INTO $table SELECT * FROM $schema_storico.$table WHERE $inWhere")) include "die_batch.php";
			if (!execute("DELETE FROM $schema_storico.$table WHERE $inWhere")) include "die_batch.php";
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
		foreach ($client_tables_simple as $table) {
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			if (!execute("INSERT IGNORE INTO $table SELECT t.* FROM $schema_storico.$table t JOIN $schema_storico.tmp_clienti_restore c ON t.idcliente=c.idcliente")) include "die_batch.php";
			if (!execute("DELETE FROM $schema_storico.$table WHERE idcliente IN (SELECT idcliente FROM $schema_storico.tmp_clienti_restore)")) include "die_batch.php";
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
		foreach ($contr_tables_simple as $table) {
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			if (!execute("INSERT IGNORE INTO $table SELECT t.* FROM $schema_storico.$table t JOIN $schema_storico.tmp_contratti_restore k ON t.idcontratto=k.idcontratto")) include "die_batch.php";
			if (!execute("DELETE FROM $schema_storico.$table WHERE idcontratto IN (SELECT idcontratto FROM $schema_storico.tmp_contratti_restore)")) include "die_batch.php";
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
	
		// Fine transazione
		commit();
	/*
	foreach ($clienti as $cliente) {
		restoreClient($cliente);
	}
	*/
	// Crea una traccia sulla storia dei contratti recuperati, solo se già dotati di righe di storia
	getUserName($IdUser);
	if (!execute("INSERT INTO storiarecupero (IdContratto,IdAzione,DataEvento,IdUtente,DescrEvento,NotaEvento)"
	." SELECT IdContratto,530,NOW(),$IdUser,'Dati ripristinati dallo storico',''"
	." FROM $schema_storico.tmp_contratti_restore c"
	." WHERE EXISTS (SELECT 1 FROM storiarecupero WHERE IdContratto=c.IdContratto)")) include "die_batch.php";;
		
	if (!execute("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS")) include "die_batch.php";
	if (!execute("SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS")) include "die_batch.php";
	
	if (!execute("DROP TABLE IF EXISTS $schema_storico.tmp_clienti_restore")) include "die_batch.php";
	if (!execute("DROP TABLE IF EXISTS $schema_storico.tmp_contratti_restore")) include "die_batch.php";
	
	trace("FINE RESTORE",false);

	if ($context["process_name"]>'')  {
		writeProcessLog($context["process_name"],"Fine recupero contratti storicizzati");
		return true;
	} else {
		die('<br><br>FINE RESTORE: fare refresh della lista delle pratiche storicizzate per vedere la nuova situazione');
	}
}

//==============================================================================================
// restoreClient
// Effettua il restore di un cliente e di tutte le entità collegate
// Argomenti:
//   1) $cliente			idCliente da trattare
//
// Un qualsiasi errore interrompe l'esecuzione
//==============================================================================================
function restoreClient($cliente) {
	global $client_tables_simple, $contr_tables_in, $contr_tables_simple, $schema_storico;
	beginTrans();

	$gruppoClienti = fetchValuesArray("SELECT idcliente FROM $schema_storico.cliente WHERE idcliente=$cliente UNION ". 
		"SELECT c2.idcliente FROM $schema_storico.contratto c1 JOIN $schema_storico.controparte c2 ON c1.idcontratto=c2.idcontratto WHERE c1.idcliente=$cliente UNION ".
		"SELECT c2.idcliente FROM $schema_storico.contratto c1 JOIN $schema_storico.contratto c2 ON c1.idcontrattoderivato=c2.idcontratto WHERE c1.idcliente=$cliente UNION ".
		"SELECT c2.idcliente FROM $schema_storico.contratto c1 JOIN $schema_storico.contratto c2 ON c2.idcontrattoderivato=c1.idcontratto WHERE c1.idcliente=$cliente"
	);
	
	$msg = "$cliente - [";
	foreach ($gruppoClienti as $idCliente) {
		$msg .= "  $idCliente";
		$contratti = fetchValuesArray("SELECT idcontratto FROM $schema_storico.contratto WHERE idcliente = $idCliente");
		$rc = getLastError();
		if ($rc>"") {
			die($rc);
		}

		$where = "idcliente=$idCliente";
		for ($i=0; $i<count($client_tables_simple); $i++) {
			restoreSimple($client_tables_simple[$i], $where);
		}

		foreach ($contratti as $idContratto) {
			// Tabelle legate ai contratti
			$where = "idcontratto=$idContratto";
			for ($i=0; $i<count($contr_tables_in); $i++) {
				restoreIn($contr_tables_in[$i], $where);
			}
			for ($i=0; $i<count($contr_tables_simple); $i++) {
				restoreSimple($contr_tables_simple[$i], $where);
			}
		}
	}
	commit();
	$msg .= " ]";
	trace($msg,FALSE);
	echo "$msg<br>\n";
}

//==============================================================================================
// restoreSimple
// Execute per il trasferimento dei record di una entità direttamente figlia di Cliente o Contratto
// Argomenti:
//   1) $table			tabella da trattare
//   2) $where			clausola where da applicare alla query
//
// Un qualsiasi errore interrompe l'esecuzione
//==============================================================================================
function restoreSimple($table, $where) {
	global $schema_storico;

	execute("INSERT IGNORE INTO $table SELECT * FROM $schema_storico.$table WHERE $where") or die(getLastError());
	execute("DELETE FROM $schema_storico.$table WHERE $where") or die(getLastError());
}

//==============================================================================================
// restoreIn
// Execute per il trasferimento dei record di una entità di secondo livello rispetto al Cliente
// o al Contratto. 
// Argomenti:
//   1) $row			oggetto della tabella con info per la preparazione della query principale 
//   2) $where			clausola where da applicare alla prima query
//
// Un qualsiasi errore interrompe l'esecuzione
//==============================================================================================
function restoreIn($row, $where) {
	global $schema_storico;

	$inArray = fetchValuesArray("SELECT ".$row['id2']." FROM $schema_storico.".$row['tb2']." WHERE $where");
	$rc = getLastError();
	if ($rc>"") {
		die($rc);
	}
	if (count($inArray)>0) {
		$inWhere = $row['id1']." in (".implode(",", $inArray).")";
		$table = $row['tb1'];

		execute("INSERT IGNORE INTO $table SELECT * FROM $schema_storico.$table WHERE $inWhere") or die(getLastError());
		execute("DELETE FROM $schema_storico.$table WHERE $inWhere") or die(getLastError());
	}
}

//=======================================================================================
// restoreDirectory
// Scopo: processa un file JSON di contratti
// Argomenti:
//	1) $path			path completo della directory contenente i file di import
//  2) &$contratti		reference all'array di codcontratto al quale vengono aggiunti i codici trovati
//  3) &$clienti		reference all'array di codclienti al quale vengono aggiunti i codici trovati
//
// Ritorna:
//	TRUE o FALSE		
//=======================================================================================
function restoreDirectory($path, &$contratti, &$clienti) {
	global $schema_storico;
	$ret = TRUE;
	try {
		foreach (scandir($path) as $item) { // legge in ordine alfabetico
			$filename = "$path/$item";
			if (is_file($filename)) {
				// Separa tipo e id del file da processare (il nome file è Company_idfile_tipofile)
				$parti = explode("_",$item);
				$type  = $parti[2];

				if (preg_match("/contratt[oi]/i", $type)
				||  preg_match("/moviment[oi]/i", $type)
				||  preg_match("/precrimin/i", $type)
				) {
					trace("Inizio elaborazione file $filename",FALSE);
					if (!restoreFile($filename, $contratti, $clienti))
						return FALSE;
				}
			}
		}
		$contratti = array_unique($contratti);
	}
	catch (Exception $e) {
		$msg = "Errore in elaborazione della directory di import: ".$e->getMessage();
		if ($context["process_name"]>'') {
			writeProcessLog($context["process_name"],$msg,2);
		} else {
			trace($msg, false);
			echo "$msg<br>";
		}
		$ret = FALSE;
	}

	return $ret;
}

//=======================================================================================
// restoreFile
// Scopo: processa un file JSON di contratti
// Argomenti:
//	1) $path			path completo del file
//  2) &$contratti		reference all'array di codcontratto al quale vengono aggiunti i codici trovati
//  3) &$clienti		reference all'array di codcliente al quale vengono aggiunti i codici cliente/venditore trovati
//
// Ritorna:
//	TRUE o FALSE		
//=======================================================================================
function restoreFile($path, &$contratti, &$clienti) {
	global $schema_storico;

	$ret = TRUE;
	try {
		// Legge le righe e verifica che siano JSON ok
		$file = fopen("$path",'r');
		if (!$file) {
			throw new Exception("impossibile leggere il file '$path'");
		}

		$contrIn = array();
		$clientIn = array();
		// Processa tutti le righe del file
		for ($nrows=0; ($buffer = fgets($file)) !== false; $nrows++) {
			$json = json_decode($buffer);
			if (NULL == $json) {
				throw new Exception("la riga n. " . ($nrows+1) . " del file ha un formato invalido");
			}
			else 
				if (property_exists($json,"rows")) { // si tratta dell'ultima riga di controllo (già controllata nella import.php)
					break;
				}
			if ($json->CodContratto)
				array_push($contrIn, "'".$json->CodContratto."'");
			else if ($json->codContratto)
				array_push($contrIn, "'".$json->codContratto."'");		
			if ($json->CodCliente)
				array_push($clientIn, "'".$json->CodCliente."'");
			if ($json->CodPuntoVendita)
				array_push($clientIn, "'".$json->CodPuntoVendita."'");
			if ($json->CodVenditore)
				array_push($clientIn, "'".$json->CodVenditore."'");
		}
		if (count($contrIn)>0) {
			$contratti = array_unique(array_merge($contratti, array_unique($contrIn)));
			$clienti = array_unique(array_merge($clienti, array_unique($clientIn)));
	//		$rc = getLastError();
	//		if ($rc>"") {
	//			throw new Exception($rc);
	//		}
		} else {
			throw new Exception("nessun cliente da ripristinare");
		}
	}
	catch (Exception $e) {
		$msg = "Errore in elaborazione del file di import: ".$e->getMessage();
		if ($context["process_name"]>'') {
			writeProcessLog($context["process_name"],$msg,2);
		} else {
			trace($msg, false);
			echo "$msg<br>";
		}
		$ret = FALSE;
	}
	fclose($file);
	return $ret;
}

//==============================================================================================
// Effettua lo svecchiamento del db spostando clienti e tutte le tabelle collegate secondo 
// criteri di non negatività dei contratti, non affido e cambiamento di stato precedente un 
// dato intervallo di tempo.
//
// Argomenti in query string:
//   1) mesi	numero di mesi del periodo di tempo che definisce un cambio di stato 
//				troppo recente per permettere la storicizzazione (default: 3)
//==============================================================================================
function svecchiamento($mesi) {
	global $client_tables_simple, $contr_tables_in, $contr_tables_simple, $schema_storico;
	
	$intervallo = (isset($mesi)?$mesi:"3")." MONTH";
	
	trace("INIZIO storicizzazione - intervallo: $intervallo",false);
	echo "INIZIO storicizzazione - intervallo: $intervallo<br>\n";
	flush();
	// drop table tmp_contratti_negativi;
	execute("DROP TABLE IF EXISTS tmp_contratti_negativi") or die(getLastError());
	// Creazione tabella temporanea con i contratti non storicizzabili
	trace("Creazione tabella temporanea con i contratti non storicizzabili",false);
	echo "Creazione tabella temporanea con i contratti non storicizzabili<br>\n";
	flush();
	$sql_create_tmp = "CREATE TABLE tmp_contratti_negativi ".
			"(PRIMARY KEY (`IdContratto`), INDEX `IdCliente` (`IdCliente`), INDEX `IdContrattoDerivato` (`IdContrattoDerivato`), ".
			"INDEX `CodContratto` (`CodContratto`), INDEX `IdVenditore` (`IdVenditore` ), INDEX `IdPuntoVendita` (`IdPuntoVendita` )) (".
			"SELECT idcliente, idcontratto, codcontratto, idcontrattoDerivato, idvenditore, idpuntovendita FROM contratto c ".
			"WHERE IFNULL(impinsoluto,0)>=26 OR idtipopagamento=1 OR idagenzia IS NOT NULL OR datacambiostato>DATE_SUB(current_date, INTERVAL $intervallo)".
			" OR (COALESCE(DataUltimaScadenza,DataChiusura,'2999-12-31')>CURDATE() AND EXISTS (SELECT 1 FROM movimento m WHERE m.IdContratto=c.IdContratto)))";
	execute($sql_create_tmp) or die(getLastError());
	
	// parte della insert sulla tabella tmp_contratti_negativi
	$sql_insert_tmp = "INSERT IGNORE INTO tmp_contratti_negativi ".
			"SELECT c.idcliente, c.idcontratto, c.codcontratto, c.idcontrattoDerivato, c.idvenditore, c.idpuntovendita FROM contratto c ";
		
	do {
		$cont = false;
	
		// contratti dei clienti con contratti negativi
		trace("Riempimento tabella con i contratti dei clienti con contratti negativi",false);
		echo "Riempimento tabella con i contratti dei clienti con contratti negativi<br>\n";
		flush();
		execute("$sql_insert_tmp WHERE c.idcliente IN (SELECT DISTINCT idcliente FROM tmp_contratti_negativi)") or die (getLastError());
		$n = getAffectedRows();
	//echo "<br>a) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti da cui derivano negativi
		trace("Riempimento tabella con i contratti da cui derivano negativi",false);
		echo "Riempimento tabella con i contratti da cui derivano negativi<br>\n";
		flush();
		execute("$sql_insert_tmp WHERE c.idcontrattoderivato IS NOT NULL AND c.idcontrattoderivato IN (SELECT idcontratto FROM tmp_contratti_negativi)") or die(getLastError());
		$n = getAffectedRows();
	//echo "b) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti derivati da negativi
		trace("Riempimento tabella con i contratti derivati da negativi",false);
		echo "Riempimento tabella con i contratti derivati da negativi<br>\n";
		flush();
		execute("$sql_insert_tmp JOIN tmp_contratti_negativi t ON c.idcontratto=t.idcontrattoderivato") or die(getLastError());
		$n = getAffectedRows();
	//echo "c) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti di clienti controparti in contratti negativi
		trace("Riempimento tabella con i contratti di clienti controparti in contratti negativi",false);
		echo "Riempimento tabella con i contratti di clienti controparti in contratti negativi<br>\n";
		flush();
		execute("$sql_insert_tmp WHERE c.idcliente IN (SELECT p.idcliente FROM controparte p JOIN ".
				"tmp_contratti_negativi t ON p.idcontratto=t.idcontratto)") or die(getLastError());
		$n = getAffectedRows();
	//echo "d) $n<br>\n";
	//flush();
		$cont |= ($n>0);
	
		// contratti di cui sono controparti clienti controparti o titolari di contratti negativi 
		trace("Riempimento tabella con i contratti di cui sono controparti clienti controparti o titolari di contratti negativi",false);
		echo "Riempimento tabella con i contratti di cui sono controparti clienti controparti o titolari di contratti negativi<br>\n";
		flush();
		execute("$sql_insert_tmp WHERE c.idcontratto IN (SELECT DISTINCT c1.idcontratto FROM controparte c1 WHERE c1.idcliente IN ".
				"(SELECT c2.idcliente idc FROM controparte c2 WHERE c2.IdContratto IN (SELECT idcontratto FROM tmp_contratti_negativi) UNION ".
				"SELECT idVenditore idc FROM  tmp_contratti_negativi UNION SELECT idPuntoVendita idc FROM  tmp_contratti_negativi UNION ".
				"SELECT idcliente idc FROM tmp_contratti_negativi))") or die(getLastError());
		$n = getAffectedRows();
	//echo "d) $n<br>\n";
	//flush();
		$cont |= ($n>0);
		// contratti di clienti idVenditore in contratti negativi
		trace("Riempimento tabella con i contratti di clienti idVenditore in contratti negativi",false);
		echo "Riempimento tabella con i contratti di clienti idVenditore in contratti negativi<br>\n";
		flush();
		execute("$sql_insert_tmp JOIN (SELECT idVenditore idc FROM tmp_contratti_negativi UNION SELECT idPuntoVendita idc FROM tmp_contratti_negativi) k on c.idcliente=k.idc") or die(getLastError());
		$n = getAffectedRows();
	//echo "d-2) $n<br>\n";
	//flush();
		$cont |= ($n>0);

		// contratti non storicizzabili perché cliente referenziato su Experian (non storicizzo Experian)
		trace("Riempimento tabella con i contratti non storicizzabili perche' inviati ad Experian",false);
		echo "Riempimento tabella con i contratti non storicizzabili perche' inviati ad Experian<br>\n";
		flush();
		execute("$sql_insert_tmp WHERE c.idcliente IN (SELECT DISTINCT idcliente FROM experian)") or die(getLastError());
		$n = getAffectedRows();
		
		$cont |= ($n>0);
		
		// contratti non storicizzabili per subentro negativo
		trace("Riempimento tabella con i contratti non storicizzabili per subentro negativo",false);
		echo "Riempimento tabella con i contratti non storicizzabili per subentro negativo<br>\n";
		flush();
		execute("INSERT IGNORE INTO tmp_contratti_negativi SELECT a.idcliente, a.idcontratto, a.codcontratto, a.idcontrattoDerivato, a.idvenditore, a.idpuntovendita FROM ".
				"(SELECT DISTINCT SUBSTRING_INDEX(codcontratto, '-', 1) AS cod, idcliente, idcontratto, codcontratto, idcontrattoDerivato, idvenditore, idpuntovendita FROM ".
				"contratto) a JOIN (SELECT DISTINCT SUBSTRING_INDEX(codcontratto, '-', 1) AS cod FROM ".
				"tmp_contratti_negativi WHERE codcontratto LIKE 'LE%-%') b ON a.cod = b.cod") or die(getLastError());
		$n = getAffectedRows();
	//echo "e) $n<br>\n";
	//flush();

	} while ($cont);
	
	// drop table tmp_clienti_stor;
	execute("DROP TABLE IF EXISTS tmp_clienti_stor") or die(getLastError());
	// Creazione tabella temporanea con i clienti storicizzabili
	trace("Creazione tabella temporanea con i clienti storicizzabili",false);
	echo "Creazione tabella temporanea con i clienti storicizzabili<br>\n";
	flush();
	$sql = "SELECT c.idcliente FROM cliente c LEFT JOIN (".
		"SELECT idcliente idc FROM controparte WHERE idcontratto in (SELECT idcontratto FROM tmp_contratti_negativi) UNION ".
		"SELECT idVenditore idc FROM tmp_contratti_negativi UNION ".
		"SELECT idPuntoVendita idc FROM tmp_contratti_negativi UNION ". 
		"SELECT idcliente idc FROM tmp_contratti_negativi) ns ON c.idcliente=ns.idc WHERE ns.idc IS NULL";
	execute("CREATE TABLE tmp_clienti_stor (PRIMARY KEY (`IdCliente`)) $sql") or die(getLastError());
	
	// drop table tmp_contratti_stor;
	execute("DROP TABLE IF EXISTS tmp_contratti_stor") or die(getLastError());
	// Creazione tabella temporanea con i contratti storicizzabili
	trace("Creazione tabella temporanea con i contratti storicizzabili",false);
	echo "Creazione tabella temporanea con i contratti storicizzabili<br>\n";
	flush();
	$sql = "SELECT idcontratto FROM contratto k JOIN tmp_clienti_stor c ON k.idcliente=c.idcliente";
	execute("CREATE TABLE tmp_contratti_stor (PRIMARY KEY (`IdContratto`)) $sql") or die(getLastError());
	
	// Elimina tabella dei contratti non storicizzabili 
	execute("DROP TABLE IF EXISTS tmp_contratti_negativi") or die(getLastError());
	
	execute("SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0") or die(getLastError());
	execute("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0") or die(getLastError());
	
		beginTrans();
		$it = 1;
		$nt = count($client_tables_simple) +  count($client_tables_in) + count($contr_tables_simple) +  count($contr_tables_in);
		foreach ($contr_tables_in as $row) {
			$table = $row['tb1'];
			$table2 = $row['tb2'];
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			$inWhere = $row['id1']." in (SELECT t.".$row['id2']." FROM $table2 t JOIN tmp_contratti_stor c ON t.idcontratto=c.idcontratto)";
			execute("INSERT IGNORE INTO $schema_storico.$table SELECT * FROM $table WHERE $inWhere") or die(getLastError());
			execute("DELETE FROM $table WHERE $inWhere") or die(getLastError());
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
		foreach ($client_tables_simple as $table) {
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			execute("INSERT IGNORE INTO $schema_storico.$table SELECT t.* FROM $table t JOIN tmp_clienti_stor c ON t.idcliente=c.idcliente") or die(getLastError());
			execute("DELETE FROM $table WHERE idcliente IN (SELECT idcliente FROM tmp_clienti_stor)") or die(getLastError());
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
		foreach ($contr_tables_simple as $table) {
			echo "$table - (".$it++." di $nt)... ";
			flush();
	
			execute("INSERT IGNORE INTO $schema_storico.$table SELECT t.* FROM $table t JOIN tmp_contratti_stor k ON t.idcontratto=k.idcontratto") or die(getLastError());
			execute("DELETE FROM $table WHERE idcontratto IN (SELECT idcontratto FROM tmp_contratti_stor)") or die(getLastError());
			$n = getAffectedRows();
			trace("$table - ($it di $nt) $n records",false);
			echo "$n records<br>\n";
			flush();
		}
	
		// Fine transazione
		commit();
		
	// Crea una traccia sulla storia dei contratti svecchiati, solo se già dotati di righe di storia
	getUserName($IdUser);
	execute("INSERT INTO $schema_storico.storiarecupero (IdContratto,IdAzione,DataEvento,IdUtente,DescrEvento,NotaEvento)"
	." SELECT IdContratto,530,NOW(),$IdUser,'Effettuata archiviazione nel DB storico della pratica e di tutti i dati collegati',''"
	." FROM tmp_contratti_stor c"
	." WHERE EXISTS (SELECT 1 FROM $schema_storico.storiarecupero WHERE IdContratto=c.IdContratto)");
	
	execute("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS") or die(getLastError());
	execute("SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS") or die(getLastError());	
	
	execute("DROP TABLE IF EXISTS tmp_clienti_stor") or die(getLastError());
	execute("DROP TABLE IF EXISTS tmp_contratti_stor") or die(getLastError());
	
	trace("FINE storicizzazione",false);
	die('FINE storicizzazione');
}
?>
