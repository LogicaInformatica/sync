<?php
/**
 * importProcessor.php Programma generalizzato di verifica e acquisizione di file Excel e CSV
 * Riceve i parametri da riga comando ($argv), chiamato da cmdUnix in processControl.php
 * Scrive i progressi dell'elaborazione su processlog.
 * @param {Number} $idModulo Id del modulo di import interessato
 * @param {String} $filePath path del file da processare (che e' stato caricato precedentemente su /tmp/import
 * @param {String} $tipoOperazione puo' assumere i seguenti valori:
 *   v = verify: fase di verifica del formato del file e dei campi
 *   p = preview: il file viene caricato sulle tabelle di import transitorie (ad es. temp_import_cliente)
 *   l = load: i dati vengono caricati dalle tabelle transitorie a quelle definitive                                
 * @param {String} $processName Identificativo del processo che serve a distinguere le righe in processlog

	 Esempi di chiamata da browser:
	 kreos.dcsys.it/server/importProcessor.php?id=2&lotto=1&oper=v&process=test&file=%2Fvar%2Fwww%2Fvhosts%2Fdcsys.it%2Fkreos%2Ftmp%2Fimport%2Fbancario%20da%20inviare%20gestionale%20%20Anagrafica%20Harvest.xlsx
	 kreos.dcsys.it/server/importProcessor.php?id=13&lotto=2&oper=v&process=testcsv&file=%2Fvar%2Fwww%2Fvhosts%2Fdcsys.it%2Fkreos%2Ftmp%2Fimport%2Fdocumenti.csv
 
 *
 */
require_once("userFunc.php");
require_once("funzioniIncassi.php");
require_once("funzioniWizard.php");
require_once('processInsoluti.php');
require_once('riempimentoOptInsoluti.php');
require_once('customFunc.php');

define('NUM_MAX_AVVISI',100);

try {
	extract($_REQUEST);
	
	// Acquisisce i parametri di input
	if ($_REQUEST['id']) { // se chiamato per debug da browser, riceve i parametri nella query string
		$idModulo 		= $_REQUEST['id'];
		$idLotto 		= $_REQUEST['lotto'];
		$filePath 		= $_REQUEST['file'];
		$tipoOperazione = $_REQUEST['oper'];
		$processName 	= $_REQUEST['process'];
		$numFile	 	= $_REQUEST['numFile'];
		$IdUtente = $_REQUEST['userid'];
		$debug = false; // variabile globale testata dalla writeProcessLog per decidere di enetere un echo
	} else {	
		$idLotto = $argv[1];
		$idModulo = $argv[2];
		$filePath = $argv[3];
		$tipoOperazione = $argv[4];
		$processName = $argv[5];
		$numFile = $argv[6];
		$Userid = $argv[7];
	}
	// Crea il contesto utente 
	createContext($Userid);	
	
	trace($msg="importProcessor chiamato con i seguenti parametri: $idLotto $idModulo $filePath $tipoOperazione $processName $numFile $Userid",false);
	writeLog("APP","Inizio importazione lotto",$msg,"IMP_LOTTO");
	// Legge le informazioni sulla trasformazione
	if (is_numeric($idModulo)) {
		$trasf = getScalar("SELECT trasformazione FROM moduloimport WHERE IdModulo=$idModulo");
		if ($trasf) {
			$trasf = json_decode(utf8_encode($trasf),true); // trasforma in array (di arrays)
			if (!$trasf) {
				erroreProcesso("Impossibile interpretare le regole di trasformazione nel modulo di importo con IdModulo=$idModulo");
			}
		} else {
			erroreProcesso("Il modulo con IdModulo=$idModulo non esiste o non contiene regole di trasformazione");
		}
	} else {
		erroreProcesso("L'Id del modulo di import='$idModulo' non &egrave; valido");
	}
	
	// Legge le definizioni proprie del wizard
	$config = file_get_contents('../js/wizard_config.json');
	if (!$config) 
		erroreProcesso("Impossibile leggere il file di configurazione necessario (js/wizard_columns.json)");
	$config = json_decode(utf8_encode($config),true);
	if (!$config)
		erroreProcesso("Il file di configurazione (js/wizard_columns.json) ha un contenuto json non valido");
	
	// Prepara un array secondario per accelerare l'accesso alle informazioni del wizard con chiave nome di colonna del DB
	$wizardColumns = array();
	foreach($config['columns'] as $configCol) {
		$wizardColumns[$configCol['name']] = $configCol; // crea un'entry avente per chiave il nome di colonna del DB
	}
	
	// Richiama la funzione specializzata per il tipo di operazione dato
	switch ($tipoOperazione) {
		case "v": // verifica file e campi
			$ret = verificaFile();
			break;
		case "p": // preview (caricamento su tabelle di transito)
			$ret = caricaFile();
			break;
		case "l": // caricamento finale con storno del precedente
		case "u": // caricamento finale con aggiornamento
			$ret = caricaDB();
			break;
		default:
			erroreProcesso("Il parametro tipoOperazione ha un valore non previsto ($tipoOperazione)");
	}
	if ($debug) // chiamato da browser per test
		die("<br>Programma terminato con codice di ritorno ".($ret?0:1));
	else
		exit($ret?0:1); // torna codice ok o non ok
	
} catch(Exception $e) {
	erroreProcesso($e->getMessage());
}
// fine main	
	
/**
 * erroreProcesso Termina il processo registrando una riga di errore su processLog
 * @var unknown_type
 */
function erroreProcesso($error) {
	global $processName,$debug,$connection;
	
	if ($connection) {
		if ($inTransaction) rollback();
		enableForeignKeys(true);
		closeDb();
	}
	
	writeProcessLog($processName, $error, 1);
	writeProcessLog($processName, "Elaborazione interrotta a causa dell'errore indicato nel messaggio precedente", -1);
	writeLog("APP","Importazione lotto",$error,"IMP_LOTTO");
	
	if ($debug) // chiamato da browser per test
		die("<br>Programma terminato per anomalia");
	else
		exit(1); // torna un codice diverso da 0 per indicare terminazione anomala
}

/**
 * verificaFile
 * Inoltra ai due processi alternativi di verifica del file di input (Excel e CSV)
 */
function verificaFile() {
	global $trasf,$processName,$filePath;
	
	$fileName = pathinfo($filePath,PATHINFO_FILENAME);
	if (!writeProcessLog($processName, "Inizio verifica file $fileName", 0))
		return; // se torna false significa che è stata richiesta una interruzione
	if($trasf['infoFile']['fileType'] == 'Excel') { 
		verificaFileExcel();
	} else {	
		verificaFileCSV();
	}
	writeProcessLog($processName, "Fine verifica file $fileName", 0);
}

/**
 * caricaFile
 * Carica il file dato nelle tabelle transitorie di import
 */
function caricaFile() {
	global $trasf,$processName,$filePath,$idLotto,$idModulo;
	$fileName = pathinfo($filePath,PATHINFO_FILENAME);
	if (!writeProcessLog($processName, "Inizio caricamento dati dal file $fileName alle tabelle transitorie", 0))
		return; // se torna false significa che e' stata richiesta una interruzione
	
	// Ripulisce le tabelle coinvolte
	if ($trasf['infoCliente']) {
		if (!execute("DELETE FROM temp_import_cliente WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoGarante']) {
		if (!execute("DELETE FROM temp_import_garante WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoContratto']) {
		if (!execute("DELETE FROM temp_import_contratto WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoRecapito']) {
		if (!execute("DELETE FROM temp_import_recapito WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoRata']) {
		if (!execute("DELETE FROM temp_import_posizione WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoMovimento']) {
		if (!execute("DELETE FROM temp_import_movimento WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if ($trasf['infoStoriaRec']) {
		if (!execute("DELETE FROM temp_import_storiarecupero WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
			erroreProcesso(getLastError());
	}
	if (!execute("DELETE FROM temp_import_workarea WHERE IdLotto=$idLotto AND IdModulo=$idModulo"))
		erroreProcesso(getLastError());

	// Cancella anche tutte le righe piu' vecchie di un anno
	execute("DELETE FROM temp_import_cliente WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_contratto WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_recapito WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_posizione WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_movimento WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_storiarecupero WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	execute("DELETE FROM temp_import_workarea WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	
	if($trasf['infoFile']['fileType'] == 'Excel') {
		caricaFileExcel();
	} else {
		caricaFileCSV();
	}
	writeProcessLog($processName, "Fine caricamento dal file $fileName alle tabelle transitorie", 0);
}

/**
 * verificaFileExcel
 * Verifica che il file da elaborare esista e sia compatibile con quanto definito. Poi analizza tutti le celle
 * e segnala su processlog i valori non validi in base alle definizioni esistenti in wizard_config.json
 */
function verificaFileExcel() {
	global $trasf,$processName,$filePath,$wizardColumns;
	
	$info = analizzaFileExcel($filePath, $error); // verifica che esista, sia leggibile e sia un file Excel
	if (!$info) 
		erroreProcesso($error);

	// Verifica che esista il foglio scelto
	$trovato = false;
	$sheetName = $trasf['infoFile']['sheetName'];
	foreach ($info as $sheet) {
		if (strtolower(trim($sheet['sheetName'])) == strtolower(trim($sheetName))) {
			$trovato = true;
			$num_colonne = count($sheet['columns']);
			break;
		}
	}
	if (!$trovato) 
		erroreProcesso("Il foglio $sheetName non esiste nel file Excel dato");

	$info = $sheet; // mette in info l'occorrenza che descrive il foglio scelto
	
	// Controlla che il numero di colonne rilevato coincida con il numero di colonne previste nella definizione della trasformazione
	$num_colonne_prev = count($trasf['infoFile']['columns']);
	if ($num_colonne != $num_colonne_prev) 
		erroreProcesso("Il numero di colonne nel foglio $sheetName &egrave; $num_colonne e quindi non corrisponde al numero previsto ({$num_colonne_prev})"
		." nella definizione delle regole di trasformazione");
		
	if (!writeProcessLog($processName, "Inizio analisi del contenuto del foglio '$sheetName' del file Excel", 0))
		return; // se torna false significa che è stata richiesta una interruzione
	
	$fileType = PHPExcel_IOFactory::identify($filePath);
	$objReader = PHPExcel_IOFactory::createReader($fileType);
	$objReader->setReadDataOnly(true);
	$objPHPExcel = $objReader->load($filePath);//load del file

	// individua il foglio (non usa la getSheetByName per essere tollerante sulle maiuscolo/minuscole)
	foreach ($objPHPExcel->getAllSheets() as $sheet) {
		if (strtolower(trim($sheetName)) == strtolower(trim($sheet->getTitle()))) {
			break;
		}
	}

	// Analizza l'intero contenuto
	$numWarnings = 0;
	for ($row=$info['headerRow']+1; $row<$info['headerRow']+$info['numrows']; $row++) {
		if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
			return false;
		}
		// prepara un'array con i valori delle celle della riga
		$fields = array();
		for ($col=0; $col<$num_colonne; $col++) {
			$value = $sheet->getCellByColumnAndRow($col,$row)->getValue(); // legge il valore della cella
			$fields[] = $value;
		}		
		esaminaColonne($row+1,$fields,$numWarnings);
		if ($numWarnings>=NUM_MAX_AVVISI) {
			writeProcessLog($processName, "Analisi del contenuto del foglio '$sheetName' interrotta perche' raggiunto il numero massimo di avvisi: $numWarnings",1);
			break;
		}
	}
	$row -= $info['headerRow']+1; // calcola il numero di righe di dati
	writeProcessLog($processName, "Fine analisi del contenuto del foglio '$sheetName' del file Excel ($row righe); numero di avvisi: $numWarnings", $numWarnings>0?1:0);
	return true;
}

/**
 * caricaFileExcel
 * Verifica che il file da elaborare esista e sia compatibile con quanto definito. Poi analizza tutti le celle
 * e segnala su processlog i valori non validi (troncati o ignorati) in base alle definizioni esistenti in wizard_config.json
 * 
 * NOTA: i controlli sul file sono gli stessi fatti nella verificaFileExcel (dato che nell'intervallo di tempo tra la verifica e il
 * caricamento il file potrebbe essere sparito o cambiato)
 */
function caricaFileExcel() {
	global $trasf,$processName,$filePath,$wizardColumns;

	$info = analizzaFileExcel($filePath, $error); // verifica che esista, sia leggibile e sia un file Excel
	if (!$info)
		erroreProcesso($error);

	// Verifica che esista il foglio scelto
	$trovato = false;
	$sheetName = $trasf['infoFile']['sheetName'];
	foreach ($info as $sheet) {
		if (strtolower(trim($sheet['sheetName'])) == strtolower(trim($sheetName))) {
			$trovato = true;
			$num_colonne = count($sheet['columns']);
			break;
		}
	}
	if (!$trovato)
		erroreProcesso("Il foglio $sheetName non esiste nel file Excel dato");

	$info = $sheet; // mette in info l'occorrenza che descrive il foglio scelto

	// Controlla che il numero di colonne rilevato coincida con il numero di colonne previste nella definizione della trasformazione
	$num_colonne_prev = count($trasf['infoFile']['columns']);
	if ($num_colonne != $num_colonne_prev)
		erroreProcesso("Il numero di colonne nel foglio $sheetName &egrave; $num_colonne e quindi non corrisponde al numero previsto ({$num_colonne_prev})"
		." nella definizione delle regole di trasformazione");

	if (!writeProcessLog($processName, "Inizio caricamento del contenuto del foglio '$sheetName' del file Excel", 0))
		return; // se torna false significa che è stata richiesta una interruzione

	$fileType = PHPExcel_IOFactory::identify($filePath);
	$objReader = PHPExcel_IOFactory::createReader($fileType);
	$objReader->setReadDataOnly(true);
	$objPHPExcel = $objReader->load($filePath);//load del file

	// individua il foglio (non usa la getSheetByName per essere tollerante sulle maiuscolo/minuscole)
	foreach ($objPHPExcel->getAllSheets() as $sheet) {
		if (strtolower(trim($sheetName)) == strtolower(trim($sheet->getTitle()))) {
			break;
		}
	}

	// Analizza e copia l'intero contenuto
	$numWarnings = 0;
	$numRows = 0;
	for ($row=$info['headerRow']+1; $row<$info['headerRow']+$info['numrows']; $row++, $numRows++) {
		if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
			return false;
		}
		// prepara un'array con i valori delle celle della riga
		$fields = array();
		for ($col=0; $col<$num_colonne; $col++) {
			$value = $sheet->getCellByColumnAndRow($col,$row)->getCalculatedValue(); // legge il valore della cella
			$fields[] = $value;
		}
		copiaColonne($row,$fields,$numWarnings);
		
		if ($numRows%1000==999) {
			if (!writeProcessLog($processName, "Caricate ".($numRows+1). " righe...",0))
				return; // se torna false significa che e' stata richiesta una interruzione
		}
	}
	writeProcessLog($processName, "Fine caricamento del contenuto del foglio '$sheetName' del file Excel ($numRows righe); numero di avvisi: $numWarnings", $numWarnings>0?1:0);
	return true;
}

/**
 * caricaFileCSV
 * Verifica che il file da elaborare esista e sia compatibile con quanto definito. Poi analizza tutti le celle
 * e segnala su processlog i valori non validi (troncati o ignorati) in base alle definizioni esistenti in wizard_config.json
 *
 * NOTA: i controlli sul file sono gli stessi fatti nella verificaFileCSV (dato che nell'intervallo di tempo tra la verifica e il
 * caricamento il file potrebbe essere sparito o cambiato)
 */
function caricaFileCSV() {
	global $trasf,$processName,$filePath,$wizardColumns;

	$info = analizzaFileCSV($filePath, $error); // verifica che esista, sia leggibile e sia un file CSV
	if (!$info)
		erroreProcesso($error);

	$fieldSeparator = $info['fieldSeparator'];
	// Controlla che il numero di colonne rilevato coincida con il numero di colonne previste nella definizione della trasformazione
	$num_colonne = count($info['columns']);
	$num_colonne_prev = count($trasf['infoFile']['columns']);
	if ($num_colonne != $num_colonne_prev)
		erroreProcesso("Il numero di colonne nel file dato &egrave; $num_colonne e quindi non corrisponde al numero previsto ({$num_colonne_prev})"
		." nella definizione delle regole di trasformazione");

	// Legge l'intero contenuto
	$handle = fopen($filePath, 'r');
	$chunk  = "";
	$row = 0;
	$numWarnings = 0;
	$numRows = 0;
	while (!!($line = fread($handle,MAX_CSV_LINE_LENGTH))) {
		$line = $chunk . $line; // concatena al pezzo di riga rimasto dalla fread precedente
		$chunk = '';
		while (strlen($line)>0) { // loop per spezzare le righe contenute nel buffer letto
			if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
				return false;
			}
			// considera che i separatori di riga potrebbero anche variare, quindi li cerca entrambi
			$pos = strpos($line,"\n");
			if ($pos===FALSE)
				$pos = strpos($line,"\r");
			if ($pos===FALSE) { // il pezzo finale del buffer letto non contiene un salto riga
				$chunk = $line;
				break;
			}
			$riga = substr($line,0,$pos);          // riga da esaminare
			$line = substr($line,$pos+1);          // parte rimanente del buffer da analizzare ancora
			if (ord(substr($line,0,1))<=13) {      // elimina possibile carattere speciale residuo (salto riga o fine file)
				$line = substr($line,1);
			}
			$row++;
			$fields = explode($fieldSeparator,$riga);
			if ($row==1) continue; // non esaminare l'header
			copiaColonne($row,$fields,$numWarnings);
			if ($numRows%1000==999) {
				if (!writeProcessLog($processName, "Caricate ".($numRows+1). " righe...",0))
					return; // se torna false significa che è stata richiesta una interruzione
			}
			$numRows++;
		} // fine esame di un buffer letto
		if ($numWarnings>=NUM_MAX_AVVISI) break;
	}
	// Esamina l'ultima riga
	if (strlen($chunk>2)) {
		$row++;
		$numRows++;
		$fields = explode($fieldSeparator,$chunk);
		copiaColonne($row,$fields,$numWarnings);
	}
	fclose($handle);
	writeProcessLog($processName, "Fine caricamento del contenuto del file CSV ($row righe); numero di avvisi: $numWarnings", $numWarnings>0?1:0);
	return true;
}

/**
 * interpretaNumero
 * Verifica se un numero è numerico e restituisce il valore (arrotondato al max a 5 decimali)
 * @param {String} (byref) $v campo letto dal file
 * @return {Boolean} false se la stringa non è un numero valido e non è vuota
 */
function interpretaNumero(&$v) {
	$v = trim(str_replace('$','',$v));
	if ($v=='') {
		$v = null;
		return true;
	}
	
	if (preg_match('/\.\d{3}\,/',$v)  // presume formato italiano (separatore delle migliaia)
	or  preg_match('/\.\d{3}$/',$v)
	or  preg_match('/,\d*$/',$v)) {
		$v = str_replace('.','',$v);
		$v = str_replace(',','.',$v);
	} else {  // formato americano
		$v = str_replace(',','',$v);
	}
	if (is_numeric($v)) {
		$v = round($v,5);
		return true;
	} else {
		return false;
	}
}

/**
 * interpretaData
 * Verifica se una data e' in uno dei formati consentiti e la restituisce in formato standard
 * @param {String} $v stringa contenente la data da analizzare
 * @return {Number} data in formato Unix time oppure null se la stringa non rappresenta una data riconoscibile
 */
function interpretaData($v) {
	if (preg_match('/^\d{4}$/',$v)) { // numero di 4 cifre: viene considerato un anno (se compreso tra 1900 e 2100) oppure giorno+mese
		if ($v>='1900' && $v<='2100') {
			$data = "$v-01-01";
		} else {
			$data = date('Y').'-'.substr($v,2,2).'-'.substr($v,0,2);
		}
	} else if (preg_match('/^\d{5}$/',$v)) { // numero di 5 cifre: viene considerato una data numeric in formato interno Excel
		$t = mktime(0,0,0,12,30+$v,1899); // Excel conta i giorni dal 30/12/1899
		$data = date('Y-m-d',$t);
	} else if (preg_match('/^\d{6}$/',$v)) { // numero di 6 cifre: viene considerato anno+mese
		if (substr($v,0,2)>='19' && substr($v,0,2)<='21') { // YYYYMM
			$data = substr($v,0,4).'-'.substr($v,4,2).'-01';
		} else { // MMYYYY
			$data = substr($v,2,4).'-'.substr($v,0,2).'-01';
		}
	} else if (preg_match('/^\d{7,8}$/',$v)) { // numero di 8 cifre: viene considerato anno+mese+giorno o viceversa
		if (strlen($v)==8 && substr($v,0,2)>='19' && substr($v,0,2)<='21') { // YYYYMMDD
			$data = substr($v,0,4).'-'.substr($v,4,2).'-'.substr($v,6,2);
			if (!strtotime($data)) {
    			$v = str_pad($v,8,'0',STR_PAD_LEFT);
	    		$data = substr($v,4,4).'-'.substr($v,2,2).'-'.substr($v,0,2);
			}
		} else { // DDMMYYYY
			$v = str_pad($v,8,'0',STR_PAD_LEFT);
			$data = substr($v,4,4).'-'.substr($v,2,2).'-'.substr($v,0,2);
		}
	} else if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/',$v,$parts)) { // data standard
		$gg = $parts[1];
		$mm = $parts[2];
		$aa = $parts[3];
		$gg = str_pad($gg,2,'0',STR_PAD_LEFT);
		$mm = str_pad($mm,2,'0',STR_PAD_LEFT);
		if (strlen($aa)==2)
			$aa = "20$aa";
		else if (strlen($aa)==3) 
			$aa = "2$aa";
		$data = "$aa-$mm-$gg ";
	} else if (preg_match('/^(\d{1,2})[\/\-\.](\d{4})$/',$v,$parts)) { // formato mm/yyy
		$mm = $parts[1];
		$aa = $parts[2];
		$mm = str_pad($mm,2,'0',STR_PAD_LEFT);
		if (strlen($aa)==2)
			$aa = "20$aa";
		else if (strlen($aa)==3) 
			$aa = "2$aa";
		$data = "$aa-$mm-01";
	} else if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})$/',$v,$parts)) { // formato gg/mm
		$gg = $parts[1];
		$mm = $parts[2];
		$gg = str_pad($gg,2,'0',STR_PAD_LEFT);
		$mm = str_pad($mm,2,'0',STR_PAD_LEFT);
		$data = date('Y')."-$mm-$gg";
	} else { // se no, suppone che sia espressa in forma standard riconoscibile
		$data = $v;
	}
	return strtotime($data); // torna null se non valida
}

/**
 * verificaFileCSV
 * Verifica che il file da elaborare esista e sia compatibile con quanto definito. Poi analizza tutti i campi
 * e segnala su processlog i valori non validi in base alle definizioni esistenti in wizard_config.json
 */
function verificaFileCSV() {
	global $trasf,$processName,$filePath,$wizardColumns;

	$info = analizzaFileCSV($filePath, $error); // verifica che esista, sia leggibile e sia un file CSV
	if (!$info)
		erroreProcesso($error);
	
	$fieldSeparator = $info['fieldSeparator'];
	// Controlla che il numero di colonne rilevato coincida con il numero di colonne previste nella definizione della trasformazione
	$num_colonne = count($info['columns']);
	$num_colonne_prev = count($trasf['infoFile']['columns']);
	if ($num_colonne != $num_colonne_prev)
		erroreProcesso("Il numero di colonne nel file dato &egrave; $num_colonne e quindi non corrisponde al numero previsto ({$num_colonne_prev})"
		." nella definizione delle regole di trasformazione");
	
	// Legge l'intero contenuto
	$handle = fopen($filePath, 'r');
	$chunk  = "";
	$row = 0;
	$numWarnings = 0;
	
	while (!!($line = fread($handle,MAX_CSV_LINE_LENGTH))) {
		$line = $chunk . $line; // concatena al pezzo di riga rimasto dalla fread precedente
		$chunk = '';
		while (strlen($line)>0) { // loop per spezzare le righe contenute nel buffer letto
			if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
				return false;
			}
			// considera che i separatori di riga potrebbero anche variare, quindi li cerca entrambi
			$pos = strpos($line,"\n");
			if ($pos===FALSE)
				$pos = strpos($line,"\r");
			if ($pos===FALSE) { // il pezzo finale del buffer letto non contiene un salto riga
				$chunk = $line;
				break;
			}
			$riga = substr($line,0,$pos);          // riga da esaminare
			$line = substr($line,$pos+1);          // parte rimanente del buffer da analizzare ancora
			if (ord(substr($line,0,1))<=13) {      // elimina possibile carattere speciale residuo (salto riga o fine file)
				$line = substr($line,1);
			}
			$row++;
			$fields = explode($fieldSeparator,$riga);
			if ($row==1) continue; // non esaminare l'header
			esaminaColonne($row,$fields,$numWarnings);
			if ($numWarnings>=NUM_MAX_AVVISI) {
				writeProcessLog($processName, "Analisi del contenuto del file interrotta perche' raggiunto il numero massimo di avvisi: $numWarnings",1);
				break;
			}
		} // fine esame di un buffer letto
		if ($numWarnings>=NUM_MAX_AVVISI) break;
	}
	// Esamina l'ultima riga
	if (strlen($chunk>2)) {
		$row++;
		$fields = explode($fieldSeparator,$chunk);
		esaminaColonne($row,$fields,$numWarnings);
	}	
	fclose($handle);   
	writeProcessLog($processName, "Fine analisi del contenuto del file CSV ($row righe); numero di avvisi: $numWarnings", $numWarnings>0?1:0);
	return true;
}

/**
 * copiaColonne
 * Inserisce i valori (mappati) di una riga di file nelle rispettive tabelle target
 * @param {Number} $row numero della riga sotto esame (serve ai messaggi di errore)
 * @param {Array} $fields array dei valori
 * @param {Number} $numWarnings (byRef) contatore del numero di avvisi emessi
 */
function copiaColonne($row,$fields,&$numWarnings) {
	global $trasf,$processName,$wizardColumns,$idLotto,$idModulo;
		
	$colonneTrasf = $trasf['colonne'];
	//trace("trasf: ".print_r($trasf,true),false);
	 // liste colonne per le varie insert
	$colLists = array("workarea"=>"","cliente"=>"", "contratto"=>"", "recapito"=>"", "posizione"=>"", "movimento"=>"");
	 // liste valori per le varie insert
	$valLists = array("workarea"=>"","cliente"=>"", "contratto"=>"", "recapito"=>"", "posizione"=>"", "movimento"=>"");
	$valArrays = array("workarea"=>"","cliente"=>"", "contratto"=>"", "recapito"=>"", "posizione"=>"", "movimento"=>"");
	
	$insertIds = array(); // array destinato a contenere le chiavi delle tabelle temp_import_cliente e temp_import_contratto
	foreach ($fields as $col=>$value) {
		$target = $colonneTrasf[$col]['colDB']; 	// colonna di destinazione nel DB
		if (!($target>' ')) continue; 				// colonna ignorata
		
		$colName = $colonneTrasf[$col]['colFileInput']; // nome della colonna nel file di input
		// Determina se è una colonna che compare più volte (concatenazione di note)
		$multi = 0;
		foreach ($colonneTrasf as $colTrasf) {
			$multi += ($colTrasf['colDB']==$target);
		}
		$multi = ($multi>1); // indica che il campo DB target è da creare concatenando più stringhe
		// Prepara il valore
		$value = preg_replace('/(^\s+|\s+$)/','',$value); 	// toglie spazi ecc.
		if ($value=='') continue; // valore vuoto: e' sempre valido, come formato (da fare controllo campi obbligatori)
		$def   = $wizardColumns[$target];  			// definizione della colonna nel wizard_config.json
		$table =  $def['table'];
		
		// Non creare INSERT in tabelle che non sono previste (possono esserci campi chiave di altre tabelle senza
		// che le stesse siano prodotte in output). Eventualmente, determina l'id della riga corrispondente (per ora
		// tratta solo i campo CodCliente e CodContratto, mentre dovrebbe trattare anche l'identificazione del cliente
		// mediante P.IVA ecc.)
		if ($table=='cliente' && !$trasf['infoCliente']) {
			if ($target=='CodCliente') { // l'utente ha specificato quale campo contiene il codice cliente
				$insertIds['cliente'] = getScalar($sql="SELECT IdImportCliente FROM temp_import_cliente WHERE IdLotto=$idLotto AND CodCliente=".quote_smart($value));
				// Se non c'è una riga corrispondente in temp_import_cliente può darsi che il cliente sia stato creato a parte, quindi ottiene il suo Id
				// direttamente dalla tabella cliente e scrive una riga su temp_import_cliente
				if (!$insertIds['cliente']) {
					$idCliente = getScalar("SELECT IdCliente FROM cliente WHERE CodCliente=".quote_smart($value));
					execute("INSERT INTO temp_import_cliente (IdLotto,IdModulo,CodCliente,IdCliente) VALUES($idLotto,$idModulo,".quote_smart($value).",$idCliente)");
					$insertIds['cliente'] = getInsertId();
				}
			}
			continue; 
		}
		if ($table=='contratto' && !$trasf['infoContratto']) {
			if ($target=='CodContratto') { // l'utente ha specificato quale campo contiene il codice contratto (num. pratica)
				$insertIds['contratto'] = getScalar($sql="SELECT IdImportContratto FROM temp_import_contratto WHERE IdLotto=$idLotto AND CodContratto=".quote_smart($value));
				// Se non c'è una riga corrispondente in temp_import_contratto può darsi che il contratto sia stato creato a parte, quindi ottiene il suo Id
				// direttamente dalla tabella contratto e scrive una riga su temp_import_contratto
				if (!$insertIds['contratto']) {
					$idContratto = getScalar("SELECT IdContratto FROM contratto WHERE CodContratto=".quote_smart($value));
					execute("INSERT INTO temp_import_contratto (IdLotto,IdModulo,CodContratto,IdContratto) VALUES($idLotto,$idModulo,".quote_smart($value).",$idContratto)");
					$insertIds['contratto'] = getInsertId();
				}
			}
			continue;
		}
		
		//trace("$table/$target=$value",false);
		// Controllo con espressione regolare
		if ($def['check_ex']>'') {
			if (!preg_match($def['check_ex'],$value)) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna '$colName' a riga $row ($value)"
					." viene scartato  perch&eacute; non &egrave; valido per il campo di destinazione '{$def['title']}'", 1);
					$numWarnings++;
				}
				$value = null;
			} 
			addOrConcat($colLists[$table],$valLists[$table],$target,$value,$valArrays[$table],$multi,$colName);
		} else if ($def['length']>0) { // lunghezza massima
			if (strlen($value)>$def['length']) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna '$colName' a riga $row ($value)"
					." &egrave; troncato a {$def['length']} caratteri", 1);
					$numWarnings++;
				}
				$value = substr($value,0,$def['length']);
			}
			addOrConcat($colLists[$table],$valLists[$table],$target,$value,$valArrays[$table],$multi,$colName);
		} else if ($def['type']=='number') {
			if (!interpretaNumero($value)) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna '$colName' a riga $row ($value)"
					." viene scartato  perch&eacute; perch&eacute; non &egrave; numerico", 1);
					$numWarnings++;
				}
				$value = null;
			} else { // numero valido
				if ($def['currency'] && $trasf['opzImportiInCentesimi']) { // import da dividere per 100?
					$value /= 100;
				}
			}
			$valArrays[$table][] = addInsClause($colLists[$table],$valLists[$table],$target,$value,"N");
		} else if ($def['type']=='date') {
			// Ammette date rappresentate da un numero (anno o AAAAMMGG) o in modo standard con separatori barra, trattino, punto
			// e anche con il numero di giorno progressivo dal 30/12/1899
			if (!($data=interpretaData($value))) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna '$colName' a riga $row ($value)"
					." viene scartato perch&eacute; non &egrave; una data valida", 1);
					$numWarnings++;
				} 
				$data = null;
			}
			$valArrays[$table][] = addInsClause($colLists[$table],$valLists[$table],$target,$data,"D");
		} else if ($def['lookup']>'') { // valore da trovare in una tabella di lookup
			$rr = getRow($sql="SELECT * FROM {$def['lookup']} WHERE ".quote_smart($value)." IN ({$def['lookupField']})",MYSQLI_NUM);
			if (getLastError()>'')
				erroreProcesso(getLastError()." SQL: $sql");
			if (!$rr) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna '$colName' a riga $row ($value)"
					." viene scartato perch&eacute; non &egrave; "
					." definito nella corrispondente tabella di riferimento ({$def['lookup']})", 1);
					$numWarnings++;
				}
				$value = null;
			} else {
				$value = $rr[0]; // valore della chiave
			}
			$valArrays[$table][] = addInsClause($colLists[$table],$valLists[$table],$target,$value,$def['type']=='string'?"S":"N");
		} else { // campo senza controlli
			addOrConcat($colLists[$table],$valLists[$table],$target,$value,$valArrays[$table],$multi,$colName);
		}
		//trace("($colLists[$table]) VALUES($valLists[$table]) ValArray=".print_r($valArrays[$table],true),false);
		
		// Ulteriori controlli personalizzati sull'input
		if (!Custom_Import_Check($table,$target,$value,$reason)) {
			erroreProcesso("Riga $row non valida: $reason");
		}
		
	}	// fine loop sulle colonne
	
	// Scrittura sulle tabelle temporanee del DB
	
	// Se e' chiesta la generazione automatica del numero pratica, genera il campo (anche se provvisorio perche' quello definitivo
	// viene messo nell'analisi piu' specifica che viene fatta nella fase di caricamento finale)
	// Questo serve a creare comunque la necessaria riga in "contratto" nel caso in cui l'unico campo scrivibile sia quello
	if ($trasf['opzCreaCodContratto']) {
		addInsClause($colLists['contratto'],$valLists['contratto'],"CodContratto",str_pad($row,10,'0',STR_PAD_LEFT),"S"); 
	}
	
	foreach ($colLists as $table=>$colList) {
		if ($colList>"") {
			$valList = $valLists[$table];
				
			addInsClause($colList,$valList,"IdLotto",$idLotto,"N"); // aggiunge chiave obbligatoria IdLotto
			addInsClause($colList,$valList,"IdModulo",$idModulo,"N"); // aggiunge chiave obbligatoria IdModulo
			
			// Altre aggiunte dipendenti dalla tabella
			switch ($table) {
				case "cliente":
					break;
				case "contratto":
					if (!$insertIds['cliente']) {
						erroreProcesso("Riga $row: tentativo di registrare campi relativi alla pratica senza identificazione del cliente");
					}
					addInsClause($colList,$valList,"IdImportCliente",$insertIds['cliente'],"N"); 
					break;
				case "recapito":
					if (!$insertIds['cliente']) {
						erroreProcesso("Riga $row: tentativo di registrare un recapito senza identificazione del cliente");
					}
					addInsClause($colList,$valList,"IdImportCliente",$insertIds['cliente'],"N"); 
					break;
				case "garante":
					if (!$insertIds['contratto']) {
						erroreProcesso("Riga $row: tentativo di registrare campi relativi ad un coobbligato senza identificazione della pratica");
					}
					addInsClause($colList,$valList,"IdImportContratto",$insertIds['contratto'],"N"); 
					break;
				case "posizione":
					if (!$insertIds['contratto']) {
						erroreProcesso("Riga $row: tentativo di registrare una posizione debitoria senza identificazione della pratica");
					}
					addInsClause($colList,$valList,"IdImportContratto",$insertIds['contratto'],"N"); 
					break;
				case "movimento":
					if (!$insertIds['contratto']) {
						erroreProcesso("Riga $row: tentativo di registrare un movimento contabile senza identificazione della pratica");
					}
					addInsClause($colList,$valList,"IdImportContratto",$insertIds['contratto'],"N"); 
					break;
				case "storiarecupero":
					if (!$insertIds['contratto']) {
						erroreProcesso("Riga $row: tentativo di registrare una riga di storia recupero (nota/evento) senza identificazione della pratica");
					}
					addInsClause($colList,$valList,"IdImportContratto",$insertIds['contratto'],"N"); 
					break;
				case "workarea":
					break;
			}
			
			// Usa replace perche' in "workarea" serve una sola riga per lotto
			if ($table=='workarea')
				$sql = "REPLACE INTO temp_import_$table ($colList) VALUES({$valList})";
			else // usa INSERT perché la replace senza primary key può generare 2 righe (??)
				$sql = "INSERT INTO temp_import_$table ($colList) VALUES({$valList})";
			if (!execute($sql)) {
				erroreProcesso(getLastError()." SQL: $sql");
			}
			$insertIds[$table] = getInsertId();
		}
	}
	// Aggiorna le colonne extra definite nella trasformazione
	aggiornaColonneExtra($row,$insertIds,$numWarnings);
}

/**
 * addOrConcat
 * Aggiunge un campo alla INSERT in corso di composizione, a meno che la stessa colonna non sia gia' stata inserita,
 * nel qual caso concatena il valore al precedente, con un salto riga nel mezzo e preceduto dal nome del campo
 * @param {String) $colList (byRef) lista dei nomi di colonne
 * @param {String)  $valArray (byRef) array dei valori di colonne
 * @param {String) $target nome della colonna
 * @param {String} $value valore del campo
 * @param {Array)  $valArray (byRef) array dei valori di colonne
 * @param {Boolean} $multi, se true significa che si devono concatenare pi� campi separandoli con newline e
 * antepondendo a ciascuno il nome del campo di input
 * @param {String} $colName nome della colonna nel file di input (cio� header di colonna)
 */
function addOrConcat(&$colList,&$valList,$target,$value,&$valArray,$multi,$colName) {
    if (!$multi) { // colonna non da concatenare 
		$valArray[] = addInsClause($colList,$valList,$target,$value,"S");  // inserimento normale
		return;
    }
    // La colonna � con concatenazione ma il nuovo valore e' vuoto
    if ($value=="NULL" or $value=='') 
    	return; // non fare nulla

    $cols = explode(',',$colList);
    $index = array_search($target,$cols);
    if ($index===false) { // e' la prima volta
    	$value = "$colName: $value";
    	$valArray[] = addInsClause($colList,$valList,$target,$value,"S");  // inserimento normale
    	return;
    }
    
    // La colonna e' gia' stata inserita nella INSERT, modifica il valore con una concatenazione
    $valueOld = preg_replace("/(^'|'$)/",'',$valArray[$index]); // valore gia' inserito (senza apici)
    
    // Dato che si deve concatenare 
    if (is_numeric($value) and strpos($value,".")!==false) {
    	$value = round($value,5); // evita decimali esagerati causati da arrotondamento Excel
    }
    $value = "$colName: $value";  
    if ($valueOld=="NULL" or $valueOld=='') { // era vuoto
    	$valArray[$index] = quote_smart($value); // sostituisce con il nuovo
    } else { // altrimenti concatena
    	$valArray[$index] = "'".$valueOld.'\n'.addslashes($value)."'"; // concatena con un salto riga + il nuovo
    }
	$valList = implode(",",$valArray);
}

/**
 * esaminaColonne
 * Esamina tutti i valori in una riga (di file o di foglio Excel) e verifica che siano compatibili con i rispettivi campi di destinazione
 * secondo le regole di formato impostate nella definizione wizard_config.json
 * @param {Number} $row numero della riga sotto esame (serve ai messaggi di errore
 * @param {Array} $fields array dei valori
 * @param {Number} $numWarnings (byRef) contatore del numero di avvisi emessi
 */
function esaminaColonne($row,$fields,&$numWarnings) {
	global $trasf,$processName,$wizardColumns;

	$colonneTrasf = $trasf['colonne'];

	foreach ($fields as $col=>$value) {
		$target = $colonneTrasf[$col]['colDB']; 	// colonna di destinazione nel DB
		if (!($target>' ')) continue; 				// colonna ignorata

		$value = preg_replace('/(^\s+|\s+$)/','',$value); 	// toglie spazi ecc.
		if ($value=='') continue; // valore vuoto: e' sempre valido, come formato (da fare controllo campi obbligatori)
		$def   = $wizardColumns[$target];  			// definizione della colonna nel wizard_config.json

		// Controllo con espressione regolare
		if ($def['check_ex']>'') {
			if (!preg_match($def['check_ex'],$value)) {
				writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
				." non &egrave; valido per il campo di destinazione '{$def['title']}'", 1);
				$numWarnings++;
			}
		} else if ($def['length']>0) { // lunghezza massima
			if (strlen($value)>$def['length']) {
				writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
				." &egrave; troppo lungo per il campo di destinazione '{$def['title']}' e sar&agrave; troncato a {$def['length']} caratteri", 1);
				$numWarnings++;
			}
		} else if ($def['type']=='number') {
			$v = $value;
			if (!interpretaNumero($v)) {
				writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
				." non pu&ograve; essere copiato nel campo di destinazione '{$def['title']}' perch&eacute; non &egrave; numerico", 1);
				$numWarnings++;
			}
		} else if ($def['type']=='date') {
			// Ammette date rappresentate da un numero (anno o AAAAMMGG) o in modo standard con separatori barra, trattino, punto
			if (!interpretaData($value)) {
				writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
				." non pu&ograve; essere copiato nel campo di destinazione '{$def['title']}' perch&eacute; non &egrave; una data valida", 1);
				$numWarnings++;
			}
		} else if ($def['lookup']>'') { // valore da trovare in una tabella di lookup
			if (!rowExistsInTable($def['lookup'],quote_smart($value)." IN ({$def['lookupField']})")) {
				writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
				." non pu&ograve; essere copiato nel campo di destinazione '{$def['title']}' perch&eacute; non &egrave; "
				." definito nella corrispondente tabella di riferimento ({$def['lookup']})", 1);
				$numWarnings++;
			}
		}
		// Ulteriori controlli personalizzati sull'input
		if (!Custom_Import_Check($def['table'],$target,$value,$reason)) {
			writeProcessLog($processName, "Il valore nella colonna '{$colonneTrasf[$col]['colFileInput']}' a riga $row ($value)"
			." non pu&ograve; essere copiato nel campo di destinazione '{$def['title']}' perch&eacute; $reason", 1);
			$numWarnings++;
		}
	}
}

/**
 * aggiornaColonneExtra
 * Crea una istruzione di update per aggiornare le colonne "calcolate" extra dopo che una riga di input ha prodotto
 * aggiornamenti nelle varie tabelle
 * @param {Number} $row numero della riga sotto esame (serve ai messaggi di errore
 * @param {Array} $insertIds array degli ID assegnati alle nuove righe inserite (al massimo 1 in ciascun tabella di transito)
 */
function aggiornaColonneExtra($row,$insertIds,&$numWarnings) {
	global $trasf,$processName,$wizardColumns,$idLotto,$idModulo;
		
	$colonneTrasf = $trasf['colonne'];
	
	 // liste colonne=valore per le varie UPDATE
	$setLists = array("workarea"=>"","cliente"=>"", "contratto"=>"", "recapito"=>"", "posizione"=>"", "movimento"=>"");
	foreach ($colonneTrasf as $col=>$colonna) {
		if ($colonna['colonnaInput']) continue; // colonna del file di input, gia' trattata	
		//trace("Calcolo colonna extra {$colonna['colFileInput']}",false);
		$target = $colonna['colDB']; 	// colonna di destinazione nel DB
		if (!($target>' ')) continue; 				// colonna ignorata (non deve capitare, perché nei campi extra il target è obbligatorio)

		$def   = $wizardColumns[$target];  			// definizione della colonna nel wizard_config.json
		$table =  $def['table'];
		
		// Calcola il valore, eseguendo la query necessaria
		//$where = "IdLotto=$idLotto AND IdModulo=$idModulo"; // 7/10/2016: tolta condizione su idModulo altrimenti non torna nulla
		                                                      // a causa di dati provenienti da diversi moduli (tolto anche dalle join nella view)
		$where = "IdLotto=$idLotto"; 
		if ($insertIds['cliente']>'')  
			$where .= " AND IdImportCliente={$insertIds['cliente']}"; 
		if ($insertIds['contratto']>'')
			$where .= " AND IdImportContratto={$insertIds['contratto']}";
		if ($insertIds['recapito']>'')
			$where .= " AND IdImportRecapito={$insertIds['recapito']}";
		if ($insertIds['posizione']>'')
			$where .= " AND IdImportPosizione={$insertIds['posizione']}";
		if ($insertIds['movimento']>'')
			$where .= " AND IdImportMovimento={$insertIds['movimento']}";
		
		$sql = "SELECT {$colonna['colFileInput']} FROM v_import_check_expr WHERE $where";
		//trace("Legge valore calcolato '{$colonna['colFileInput']}' con query '$sql'",false);
		$value = getScalar($sql,true);
		if (getLastError()>'') {
			erroreProcesso(getLastError()." SQL: $sql");
		}		
		//trace("Risultato = $value",false);
		
		// Controllo del valore con espressione regolare
		if ($def['check_ex']>'') {
			if ($value>'' and !preg_match($def['check_ex'],$value)) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna 'calcolata' n. {$colonna['IdColonna']} a riga $row ($value)"
					." viene scartato  perch&eacute; non &egrave; valido per il campo di destinazione '{$def['title']}'", 1);
					$numWarnings++;
				}
				$value = null;
			}
			addSetClause($setLists[$table],$target,$value,'S');
		} else if ($def['length']>0) { // lunghezza massima
			if ($value>'' and strlen($value)>$def['length']) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna 'calcolata' n. {$colonna['IdColonna']} a riga $row ($value)"
					." &egrave; troncato a {$def['length']} caratteri", 1);
					$numWarnings++;
				}
				$value = substr($value,0,$def['length']);
			}
			addSetClause($setLists[$table],$target,$value,'S');
		} else if ($def['type']=='number') {
			$v = preg_replace('/[\$\,\.]/','',$value); // toglie dollaro, virgola decimale, punto (per togliere euro fare qualcosa)
			if ($value>'' and !is_numeric($v)) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna 'calcolata' n. {$colonna['IdColonna']} a riga $row ($value)"
					." viene scartato  perch&eacute; perch&eacute; non &egrave; numerico", 1);
					$numWarnings++;
				}
				$value = null;
			} else { // numero valido
				if ($def['currency'] && $trasf['opzImportiInCentesimi']) { // import da dividere per 100?
					$value /= 100;
				}
			} 
			addSetClause($setLists[$table],$target,$value,'N');
		} else if ($def['type']=='date') {
			// Ammette date rappresentate da un numero (anno o AAAAMMGG) o in modo standard con separatori barra, trattino, punto
			if ($value>'' and !($data=interpretaData($value))) {
				if ($numWarnings<NUM_MAX_AVVISI) {
					writeProcessLog($processName, "Il valore nella colonna 'calcolata' n. {$colonna['IdColonna']} a riga $row ($value)"
					." viene scartato perch&eacute; non &egrave; una data valida", 1);
					$numWarnings++;
				}
				$data = null;
			}
			addSetClause($setLists[$table],$target,$data,'D');
		} else if ($def['lookup']>'') { // valore da trovare in una tabella di lookup
			if ($value>'') {
				$rr = getRow("SELECT * FROM {$def['lookup']} WHERE ". quote_smart($value)." IN ({$def['lookupField']})",MYSQLI_NUM);
				if (getLastError()>'')
					erroreProcesso(getLastError());
				if (!$rr) {
					if ($numWarnings<NUM_MAX_AVVISI) {
						writeProcessLog($processName, "Il valore nella colonna 'calcolata' n. {$colonna['IdColonna']} a riga $row ($value)"
						." viene scartato perch&eacute; non &egrave; "
						." definito nella corrispondente tabella di riferimento ({$def['lookup']})", 1);
						$numWarnings++;
					}
					$value = null;
				} else {
					$value = $rr[0]; // valore della chiave
				}
			} 
			addSetClause($setLists[$table],$target,$value,$def['type']=='string'?"S":"N");
		} else { // campo senza controlli
			addSetClause($setLists[$table],$target,$value,"S");
		}
	}	// fine loop sulle colonne
	
	// Esegue gli aggiornamenti sul DB
	foreach ($setLists as $table=>$setList) {
		if ($setList>"") {
			if (!($insertIds[$table]>'')) { // nella tabella in questione non e' stata scritta una riga, ma almeno un campo calcolato va su questa tabella
				     // bisogna quindi fare un INSERT. Per semplificare, fa prima insert poi update come nel caso precedente
				$sql = "INSERT INTO temp_import_$table (IdLotto,IdModulo) VALUES($idLotto,$idModulo)";
				if (!execute($sql))
					erroreProcesso(getLastError()." SQL: $sql");
				$insertIds[$table] = getInsertId(); // adesso può proseguire con l'update come negli altri casi
			}
			$where = "WHERE IdLotto=$idLotto AND IdModulo=$idModulo";
			if ($table!='workarea')
				$where .= " AND IdImport$table={$insertIds[$table]}";
			$sql = "UPDATE temp_import_$table $setList $where";
			if (!execute($sql)) 
				erroreProcesso(getLastError()." SQL: $sql");
			//trace("$sql",false);
		}
	}
}

/**
 * caricaDB Carica i dati dalle tabelle transitorie in quelle definitive
 */
function caricaDB() {
	global $trasf,$processName,$filePath,$idLotto,$idModulo,$tipoOperazione,$numFile;
	$nomeModulo = getScalar("SELECT DescrModulo FROM moduloimport WHERE IdModulo=$idModulo");
	if (!writeProcessLog($processName, "Inizio caricamento dati nelle tabelle definitive (modulo: '$nomeModulo')", 0))
		return; // se torna false significa che e' stata richiesta una interruzione

	// Elimina righe molto vecchie dalla tabella stornoLotto 
	execute("DELETE FROM stornolotto WHERE LastUpd<CURDATE()-INTERVAL 1 YEAR");
	
	if ($tipoOperazione=='l' && $numFile==0)  { // richiesto caricamento totale con storno del precedente, si fa sul primo file di input, non sugli altri
		writeProcessLog($processName, "Inizio storno dei dati caricati precedentemente per lo stesso lotto", 0);
		
		// Esegue lo storno delle istruzioni di caricamento lotto
		if (!stornoLotto($idLotto)) {
			erroreProcesso("Storno non riuscito: ".getLastError());
		} else {
			writeProcessLog($processName, "Effettuato storno dei dati caricati precedentemente per lo stesso lotto", 0);
		}
	}
	
	//---------------------------------------------------------------------------------------------------------
	// Caricamento della tabella Cliente (solo se la regola trasformazione prevede tale caricamento: altrimenti
	// resta con il contenuto che ha, presumibilmente caricato con altro modulo)
	//---------------------------------------------------------------------------------------------------------
	
	// Costruisce la condizione per restringere il riconoscimento del Cliente gia' esistente ai soli clienti specifici
	// di questo Mandante, a meno che non si sia specificata l'opzione "Non duplicare i debitori"
	$IdMandante = getScalar($sql="SELECT IdMandante FROM lotto WHERE IdLotto=$idLotto");
	if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
	
	if ($trasf['infoCliente']) {
		// Legge tutti i clienti (che possono comparire duplicati nella temp_import_cliente), e cerca se esistono gia'
		// oppure devono essere creati per la prima volta
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nUpd = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT * FROM temp_import_cliente WHERE IdLotto=$idLotto AND IdModulo=$idModulo ORDER BY IdImportCliente LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
					
				// determina se esiste un cliente con la stessa "identita'" 
				$IdCliente = trovaCliente($row,$IdMandante);
				if ($IdCliente===null) { // il cliente deve essere creato
					$IdCliente = creaCliente($row);
					$nIns++;
				} else if ($IdCliente==-1) { // il cliente e' ambiguo
					continue;
				} else { // il cliente esiste gia': aggiorna qualche campo
					if (aggiornaCliente($IdCliente,$row))
						$nUpd++;
				}
				
				// Crea o aggiorna la relazione tra debitore e mandante
				$sql = "REPLACE INTO clientecompagnia (IdCliente,IdCompagnia) VALUES($IdCliente,$IdMandante)";
				if (!execute($sql)) erroreProcesso(getLastError()." SQL=$sql");
				registraStorno("DELETE FROM clientecompagnia WHERE IdCliente=$IdCliente");
				
				// Registra il link al cliente nella riga attualmente processata di temp_import_cliente
				$sql = "UPDATE temp_import_cliente SET IdCliente=$IdCliente WHERE IdImportCliente={$row['IdImportCliente']}";
				if (!execute($sql)) erroreProcesso(getLastError()." SQL=$sql");
				registraStorno("UPDATE temp_import_cliente SET IdCliente=NULL WHERE IdImportCliente={$row['IdImportCliente']}");
				
				$numRows++;
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative ai debitori...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Create $nIns righe e aggiornate $nUpd righe nella tabella 'Cliente'", 0);
	}
		
	//------------------------------------------------------
	// Caricamento della tabella Contratto
	//------------------------------------------------------
	if ($trasf['infoContratto']) {
		// Legge tutti i contratti (che possono comparire duplicati nella temp_import_contratto), e cerca se esistono gia'
		// oppure devono essere creati per la prima volta
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nUpd = $numInsNote = $numUpdNote = $numDelNote = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdCliente,co.* FROM temp_import_contratto co JOIN temp_import_cliente cl ON co.IdImportCliente=cl.IdImportCliente"
			     . " WHERE co.IdLotto=$idLotto AND co.IdModulo=$idModulo  ORDER BY IdImportContratto LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				$numRows++;
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				$IdCliente = $row['IdCliente'];
					
				// determina se la pratica esiste gia'
				$IdContratto = trovaContratto($row,$IdMandante,$IdCliente);
				if ($IdContratto===null) { // il contratto deve essere creato
	//if ($tipoOperazione!='l') erroreProcesso("DEBUG: contratto non trovato, cliente=$IdCliente"); // TEMPORANEO
					$IdContratto = creaContratto($IdMandante,$IdCliente,$row,$numInsNote);
					$nIns++;
				} else if ($IdContratto==-1) { // il contratto e' ambiguo
					$Nome = getScalar("SELECT IFNULL(Nominativo,RagioneSociale) FROM cliente WHERE IdCliente=$IdCliente");
					writeProcessLog($processName, "Non e' possibile determinare in modo univoco qual e' la pratica da aggiornare per il cliente '$Nome'"
							." (riga n. $numRows)",1);
					continue;
				} else { // il contratto esiste gia': aggiornare i campi
					aggiornaContratto($IdContratto,$row,$numInsNote,$numUpdNote,$numDelNote);
					$nUpd++;
				}
				
				// Registra il link al contratto nella riga attualmente processata di temp_import_contratto
				$sql = "UPDATE temp_import_contratto SET IdContratto=$IdContratto WHERE IdImportContratto={$row['IdImportContratto']}";
				if (!execute($sql)) erroreProcesso(getLastError()." SQL=$sql");
				registraStorno("UPDATE temp_import_contratto SET IdContratto=NULL WHERE IdImportContratto={$row['IdImportContratto']}");
					
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative alle pratiche...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Create $nIns righe e aggiornate $nUpd righe nella tabella 'Contratto'", 0);
		writeProcessLog($processName, "Create $numInsNote righe, aggiornate $numUpdNote, cancellate $numDelNote righe nella tabella 'Nota'",0);	
	}
	//------------------------------------------------------------
	// Creazione di movimenti dalla tabella temp_import_posizione
	//------------------------------------------------------------
	if ($trasf['infoRata']) {
		// Legge tutti le righe di temp_import_posizione, che corrispondono ai movimenti base del partitario, ad es. l'addebito di capitale,
		// interessi, spese, e l'accredito di pagamenti.  
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nDel = 0;
		$lastContratto = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdContratto,po.* FROM temp_import_posizione po JOIN temp_import_contratto co ON co.IdImportContratto=po.IdImportContratto"
			. " WHERE po.IdLotto=$idLotto  AND po.IdModulo=$idModulo  ORDER BY po.IdImportContratto,IdImportPosizione LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				$numRows++;
				
				extract($row);
				// Alcuni contratti potrebbero non essere stati riconosciuti nel passo precedente
				if ($IdContratto==null) {
					writeProcessLog($processName,"La posizione a riga ".($numRows+1)." viene scartata perch&eacute; legata ad una pratica che non &egrave; stata riconosciuta",1);
					continue;
				}
				
				// Le posizioni che arrivano producono un refresh delle righe corrispondenti nella tabella movimento
				// (cioe' si presume che ogni volta contengano la situazione aggiornata, con eventuale aggiunte, poi, 
				// di movimenti vari attraverso la temp_import_movimento. Quindi al break, vengono ripulite le righe 
				// precedentemente caricate)
				if ($lastContratto!=$IdContratto) {
					// al break ripulisce i movimenti del contratto che si sta per trattare
					// per generare le istruzioni di storno e' costretto a fare una delete per volta
					$mrows = getRows("SELECT * FROM movimento WHERE IdContratto=$IdContratto");
					foreach ($mrows as $mrow) {
						$mrow['IdMovimento'] = null; // elimina l'Id automatico
						foreach ($mrow as $key=>$col) {
							if ($col===null) {
								$mrow[$key] = "null";
							} else if (!is_numeric($col)) {
								$mrow[$key] = quote_smart($col);
							}
						}
						registraStorno("INSERT INTO movimento VALUES (".implode(',',$mrow).")");
						if (!execute($sql="DELETE FROM movimento WHERE IdContratto=$IdContratto")) {
							erroreProcesso(getLastError()." SQL=$sql");
						} else {
							$nDel += getAffectedRows();
						}
					}
	
					$lastContratto = $IdContratto;
				}
	
				$n = creaPosizione($IdContratto,$row); // torna false se tutti gli importi erano a zero (nessun mov. creato)
				if ($n!==false)
					$nIns += $n;
				else
					writeProcessLog($processName, "ATTENZIONE: posizione con tutti gli importi a zero (riga n. ".($numRows+1).")",0);
				
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative alle posizioni...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Cancellate $nDel righe e inserite  $nIns righe nella tabella 'Movimento'", 0);
	} 
	//------------------------------------------------------------------------------------------------------------------------
	// Creazione di righe per i garanti/coobbligati, nella tabella Cliente, collegati al contratto nella tabella Controparte
	//------------------------------------------------------------------------------------------------------------------------
	if ($trasf['infoGarante']) {	
		// Legge tutti le righe di temp_import_garante, dati anagrafici e fiscali dei garanti e coobligati (altri soggetti connessi)  
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nDel = $nNew = 0;
		$lastContratto = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdContratto,ga.* FROM temp_import_garante ga JOIN temp_import_contratto co ON co.IdImportContratto=ga.IdImportContratto"
			. " WHERE ga.IdLotto=$idLotto  AND ga.IdModulo=$idModulo AND (ga.NominativoGarante>'' OR ga.CognomeGarante>'')"
			. " ORDER BY ga.IdImportContratto,IdImportGarante LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				extract($row);
				// Alcuni contratti potrebbero non essere stati riconosciuti nel passo precedente
				if ($IdContratto==null) {
					writeProcessLog($processName,"Il garante a riga ".($numRows+2)." viene scartato perch&eacute; legato ad una pratica che non &egrave; stata riconosciuta",1);
					continue;
				}
				
				// I garanti che arrivano producono un refresh delle righe corrispondenti nella tabella controparte
				// (mentre la tabella cliente non viene toccata)
				// (cioè si presume che ogni volta contengano l'elenco completo delle controparti
				// Quindi al break sulla pratica, vengono ripulite le righe precedentemente caricate
				if ($lastContratto!=$IdContratto) {
					// al break ripulisce le controparti del contratto che si sta per trattare
					// per generare le istruzioni di storno e' costretto a fare una delete per volta
					$mrows = getRows("SELECT * FROM controparte WHERE IdContratto=$IdContratto");
					foreach ($mrows as $mrow) {
						foreach ($mrow as $key=>$col) {
							if ($col===null) {
								$mrow[$key] = "null";
							} else if (!is_numeric($col)) {
								$mrow[$key] = quote_smart($col);
							}
						}
						registraStorno("INSERT INTO controparte VALUES (".implode(',',$mrow).")");
						if (!execute($sql="DELETE FROM controparte WHERE IdContratto=$IdContratto")) {
							erroreProcesso(getLastError()." SQL=$sql");
						} else {
							$nDel += getAffectedRows();
						}
					}
					$lastContratto = $IdContratto;
				}
	
				$n = creaGarante($IdContratto,$row,$nNew); // torna false se per qualche motivo nessuna riga � stata scritta
				if ($n!==false) {
					$nIns += $n;
				} else {
					writeProcessLog($processName, "Garante/coobbligato senza Partita IVA/Cod.Fiscale non inserito (riga n. ".($numRows+2).")",0);
				}
				
				$numRows++;
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative ai garanti/coobbligati...",0))
						return; // se torna false significa che e' stata richiesta un' interruzione dal chiamante
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Cancellate $nDel righe e inserite  $nIns righe nella tabella 'Controparte', create $nNew righe nella tabella 'Cliente'", 0);
	}
			
	//------------------------------------------------------------
	// Creazione di recapiti dalla tabella temp_import_recapito
	//------------------------------------------------------------
	if ($trasf['infoRecapito']) {
		// Legge tutte le righe di temp_import_recapito
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nUpd = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdCliente,re.* FROM temp_import_recapito re JOIN temp_import_cliente cl ON re.IdImportCliente=cl.IdImportCliente"
			     . " WHERE re.IdLotto=$idLotto AND re.IdModulo=$idModulo  ORDER BY IdImportRecapito LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				// Ogni riga di temp_import_recapito contiene 4 gruppi di campi identici, per esprimere fino a 4 recapiti distinti
				foreach (array("","2","3","4") as $i=>$suffix) {
					$IdCliente = $row['IdCliente'];
					// determina se il recapito esiste gia'
					$IdRecapito = trovaRecapito($row,$IdCliente,$suffix);
					if ($IdRecapito===null) { // il recapito deve essere creato
						$IdRecapito = creaRecapito($row,$IdCliente,$suffix); // crea se almeno un campo è valorizzato
						$nIns += ($IdRecapito>0);
					} else if ($IdRecapito==-1) { // il recapito e' ambiguo
						$Nome = getScalar("SELECT IFNULL(Nominativo,RagioneSociale) FROM cliente WHERE IdCliente=$IdCliente");
						writeProcessLog($processName, "Non e' possibile determinare in modo univoco qual e' il recapito da aggiornare"
								." per il cliente '$Nome' (riga n. ".($numRows+1).", gruppo indirizzo n. ".($i+1).")",1);
						continue;
					} else if ($IdRecapito==-2) { // il gruppo indirizzo è vuoto
						continue;
					} else { // il recapito esiste gia': aggiornare i campi
						$nUpd+=aggiornaRecapito($row,$IdRecapito,$suffix);
					}
				}
				$numRows++;
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative ai recapiti...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Create $nIns righe e aggiornate $nUpd righe nella tabella 'Recapito'", 0);
	}	
	//------------------------------------------------------------
	// Creazione di movimenti dalla tabella temp_import_movimento
	//------------------------------------------------------------
	if ($trasf['infoMovimento']) {	
		// Legge le righe di temp_import_movimento e le inserisce in "movimento", usando UPDATE solo nel caso in cui sia
	
		// una elaborazione di tipo "aggiornamento" e si tratti di un movimento con stessa data, causale e importo di uno 
		// già registrato
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nUpd = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdContratto,mo.* FROM temp_import_movimento mo JOIN temp_import_contratto co ON co.IdImportContratto=mo.IdImportContratto"
			. " WHERE mo.IdLotto=$idLotto AND mo.IdModulo=$idModulo  ORDER BY mo.IdImportContratto,IdImportMovimento LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				extract($row);
				// Alcuni contratti potrebbero non essere stati riconosciuti nel passo precedente
				if ($IdContratto==null) {
					writeProcessLog($processName,"Il movimento a riga ".($numRows+2)." viene scartato perch&eacute; legato ad una pratica che non &egrave; stata riconosciuta",1);
					continue;
				}
				
				if (!creaMovimento($IdContratto,$row,$nIns,$nUpd)) {
					writeProcessLog($processName, "ATTENZIONE: movimento con tutti gli importi a zero (riga n. ".($numRows+2).")",0);
				}
					
				$numRows++;
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative ai movimenti contabili...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Inserite $nIns righe e aggiornate $nUpd righe nella tabella 'Movimento'", 0);
	}
	//----------------------------------------------------------------------
	// Creazione di storia recupero dalla tabella temp_import_storiarecupero
	//----------------------------------------------------------------------
	if ($trasf['infoStoriaRec']) {
		// Legge le righe di temp_import_storiarecupero e le inserisce in "storiarecupero", usando UPDATE solo nel caso in cui sia
		// una elaborazione di tipo "aggiornamento" e si tratti di un evento con stesso identificativo
		$chunk = 100; // per evitare problemi di memoria, legge per porzioni
		$numRows = 0;
		$nIns = $nUpd = 0;
		for ($from = 0; ;$from += $chunk) {
			$sql = "SELECT IdContratto,sr.* FROM temp_import_storiarecupero sr JOIN temp_import_contratto co ON co.IdImportContratto=sr.IdImportContratto"
					. " WHERE sr.IdLotto=$idLotto AND sr.IdModulo=$idModulo  ORDER BY sr.IdImportContratto,IdImportStoriaRecupero LIMIT $from,$chunk";
			$rows = getRows($sql);
			if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
			// Elabora tutte le righe lette
			foreach ($rows as $row) {
				if (hasProcessLogInterrupt($processName)) { // il chiamante ha chiesto di interrompere
					return false;
				}
				extract($row);
				// Alcuni contratti potrebbero non essere stati riconosciuti nel passo precedente
				if ($IdContratto==null) {
					writeProcessLog($processName,"L'evento/nota a riga ".($numRows+2)." viene scartato perch&eacute; legato ad una pratica che non &egrave; stata riconosciuta",1);
					continue;
				}

				creaStoriaRecupero($IdContratto,$row,$nIns,$nUpd);
						
				$numRows++;
				if ($numRows%1000==0) {
					if (!writeProcessLog($processName, "Elaborate $numRows righe relative alle note / storia recupero...",0))
						return; // se torna false significa che e' stata richiesta una interruzione
				}
			}
			if ($numRows < $from+$chunk)  // records esauriti
				break;
		}
		writeProcessLog($processName, "Inserite $nIns righe e aggiornate $nUpd righe nella tabella 'StoriaRecupero'", 0);
	}
	
	
	// Fine importazione
	// Aggiorna data import del lotto
	if (!execute($sql="UPDATE lotto SET DataImport=NOW() WHERE IdLotto=$idLotto"))
		erroreProcesso(getLastError()." SQL=$sql");

	// Aggiorna saldi ecc. per tutti i contratti interessati
	if ($trasf['infoContratto'] or $trasf['infoRata'] or $trasf['infoMovimento']) {
		aggiornaSituazioneContabile($processName,$idLotto);
	}
	
	// Esegue assegnazioni a reparti e persone (dopo la creazione degli insoluti, come deve essere secondo la logica DCSys)
	if ($trasf['infoContratto']) {
		esegueAssegnazioni();
	}
	
	writeProcessLog($processName, "Fine caricamento dati nelle tabelle definitive", 0);
	writeLog("APP","Importazione lotto","Eseguita con successo","IMP_LOTTO");
	
}

/**
 * trovaCliente Analizza una riga di temp_import_cliente per determinare se il cliente gia' esiste in tabella cliente
 * @param {Array} $row riga letta da temp_import_cliente
 * @param {Number} $IdMandante Id del mandante (tabella compagnia)
 * @return {Number} IdCliente oppure null (non trovato) oppure -1 (ambiguo)
 */
function trovaCliente($row,$IdMandante) {
	global $trasf,$processName,$filePath,$idLotto,$idModulo,$tipoOperazione;
	
	extract($row);

	$condMandante = $trasf['opzUnisciClienti'] ? '' : " AND IdCliente IN (SELECT IdCliente FROM clientecompagnia WHERE IdCompagnia=$IdMandante)";
	
	if ($PartitaIVA>'') {
		$cond = "PartitaIVA LIKE '%".substr($PartitaIVA,-11)."'";
	} else if ($CodiceFiscale>'') {
		if (preg_match('/^([a-z]{2})?([0-9]{0,5})?([0-9]{11})$/i',$CodiceFiscale,$arr)) { // il campo CodiceFiscale contiene una partita IVA
			$cond = "PartitaIVA LIKE '%{$arr[3]}'";
		} else {
			$cond = "CodiceFiscale LIKE '$CodiceFiscale'";
		}
	} else if ($CodCliente>'') {
		$cond = "CodCliente LIKE ".quote_smart($CodCliente);
	} else if ($RagioneSociale>'') { 
		$cond = "(RagioneSociale LIKE ".quote_smart($RagioneSociale)." OR Nominativo LIKE ".quote_smart($RagioneSociale).")";
		if ($DataNascita>'')
			$cond .= " AND DataNascita='$DataNascita'";
	} else if ($Nominativo>'' and $DataNascita>'') {
		$cond = "(RagioneSociale LIKE ".quote_smart($Nominativo)." OR Nominativo LIKE ".quote_smart($Nominativo).") AND DataNascita='$DataNascita'";
	} else if ($Nominativo>'') {
		$cond = "(RagioneSociale LIKE ".quote_smart($Nominativo)." OR Nominativo LIKE ".quote_smart($Nominativo).")";
	} else if ($NomeDebitore>'' and $CognomeDebitore>'' and $DataNascita>'') {
		$Nominativo = trim($CognomeDebitore)." ".trim($NomeDebitore);		
		$cond = "Nominativo LIKE ".quote_smart($Nominativo)." AND DataNascita='$DataNascita'";
	}
	if ($cond=='') {
		writeProcessLog($processName, "Non e' possibile identificare il soggetto con Nominativo=$Nominativo,Rag.Sociale=$RagioneSociale. La riga e' stata scartata.",1);
		return -1;		
	}
	
	$ids = getColumn($sql="SELECT IdCliente FROM cliente WHERE $cond $condMandante");
	if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
	if (!$ids) {
		if ($tipoOperazione!='l') writeProcessLog($processName, "Trovato nuovo cliente: $Nominativo $RagioneSociale",0);
		return null;
	} else if (count($ids)==1) {
		return $ids[0];
	} else {
		$nome = trim("$CognomeDebitore $NomeDebitore $Nominativo $RagioneSociale");
		$piva = trim("$PartitaIVA $CodiceFiscale");
		writeProcessLog($processName, "Non e' possibile determinare in modo univoco qual e' il soggetto con i seguenti dati: "
				."Codice=$CodCliente,Nominativo:$Nome,P.IVA/Cod.Fisc.:$piva. La riga e' stata scartata. (SQL=$sql)",1);
		return -1;
	}
}

/**
 * creaCliente Crea una riga nella tabella cliente (e una in clientecompagnia) a partre da una riga di temp_import_cliente
 *  Inoltre puo' creare una riga nella tabella Area, se il cliente appartiene ad un Area non ancora censita
 * @param {Array} $row riga letta da temp_import_cliente
 * @return {Number} IdCliente della riga appena creata
 */
function creaCliente($row) {
	global $trasf;	

	extract($row);

	// Se il codice cliente non e' fornito, lo crea
	if (!($CodCliente>'')) {
		$sql = "SELECT IFNULL(CodProdotto,CodCompagnia) from lotto l "
			 . "JOIN prodotto p ON p.IdProdotto=l.IdProdotto "
             . "JOIN compagnia c ON l.Idmandante=c.IdCompagnia "
			 . "WHERE IdLotto=$IdLotto";
		$prefix = getScalar($sql);
		if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
		$prefix = substr($prefix,0,4); // usa al max 4 caratteri come prefisso
		// Determina l'ultimo numero assegnato con questo prefisso, ammesso che ce ne sia 	
		$sql = "SELECT MAX(CodCliente) FROM cliente WHERE CodCliente LIKE '$prefix%'";
		$max = getScalar($sql);
		if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
		if (preg_match('/^([0-9]+)$/',substr($max,strlen($prefix)),$arr)) { // e' numerico, puo' usarlo
			$CodCliente = $prefix.str_pad(1+$arr[1],6,'0',STR_PAD_LEFT);
		} else { // non numerico o assente, comincia la numerazione da adesso
			$CodCliente = $prefix."000001";
		}
	}
	$row['CodCliente'] = $CodCliente; // per evitare che si perda alla successiva extract 
	
	// Prepara l'istruzione di update
	preparaCampiCliente($row); // calcola alcuni campi
	extract($row);
	
	// Prepara l'istruzione INSERT
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'CodCliente',$CodCliente,'S');
	addInsClause($colList,$valList,'SiglaProvincia',$SiglaProvincia,'S');
	addInsClause($colList,$valList,'SiglaNazione',$SiglaNazione>''?$SiglaNazione:'IT','S'); // il campo e' NOT NULL per errore
	addInsClause($colList,$valList,'CodFormaGiuridica',$CodFormaGiuridica,'S');
	addInsClause($colList,$valList,'Nominativo',$Nominativo,'S');
	addInsClause($colList,$valList,'RagioneSociale',$RagioneSociale,'S');
	addInsClause($colList,$valList,'IdTipoCliente',$IdTipoCliente,'N');
	addInsClause($colList,$valList,'DataNascita',$DataNascita,'D');
	addInsClause($colList,$valList,'LocalitaNascita',$LocalitaNascita,'S');
	addInsClause($colList,$valList,'CodiceFiscale',$CodiceFiscale,'S');
	addInsClause($colList,$valList,'PartitaIVA',$PartitaIVA,'S');
	addInsClause($colList,$valList,'DataIni','CURDATE()','G');
	addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
	addInsClause($colList,$valList,'LastUser','import','S');
	addInsClause($colList,$valList,'IdArea',$IdArea,'N');
	addInsClause($colList,$valList,'Sesso',$Sesso,'S');
	addInsClause($colList,$valList,'NotaCliente',$NotaCliente,'S');
	
	$sql = "INSERT INTO cliente ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql"); 
	$IdCliente = getInsertId();
	registraStorno("DELETE FROM cliente WHERE IdCliente=$IdCliente");
	return $IdCliente;
}

/**
 * aggiornaCliente Aggiorna una riga della tabella cliente a partire da una riga di temp_import_cliente
 * @param {Number} $IdCliente ID della riga da aggiornare
 * @param {Array} $row riga letta da temp_import_crecapito
 * @return {Boolean} true se l'aggiornamento e' stato effettuato
 */
function aggiornaCliente($IdCliente,$row) {
    global $processName;
    
	// Legge la riga prima di aggiornarla
	$old = getRow($sql="SELECT * FROM cliente WHERE IdCliente=$IdCliente");
	if (getLastError()>'') erroreProcesso(getLastError()." SQL: $sql");
	if (!$old)  // la riga non c'e': per quanto improbabile, significa che un altro utente l'ha cancellata: non fare nulla
		return false;

	// Prepara l'istruzione di storno
	$setList = "";
	addSetClause($setList,'CodCliente',$old["CodCliente"],'S');
	addSetClause($setList,'SiglaProvincia',$old["SiglaProvincia"],'S');
	addSetClause($setList,'SiglaNazione',$old["SiglaNazione"],'S');
	addSetClause($setList,'CodFormaGiuridica',$old["CodFormaGiuridica"],'S');
	addSetClause($setList,'Nominativo',$old["Nominativo"],'S');
	addSetClause($setList,'RagioneSociale',$old["RagioneSociale"],'S');
	addSetClause($setList,'IdTipoCliente',$old["IdTipoCliente"],'N');
	addSetClause($setList,'DataNascita',$old["DataNascita"],'D');
	addSetClause($setList,'LocalitaNascita',$old["LocalitaNascita"],'S');
	addSetClause($setList,'CodiceFiscale',$old["CodiceFiscale"],'S');
	addSetClause($setList,'PartitaIVA',$old["PartitaIVA"],'S');
	addSetClause($setList,'IdArea',$old["IdArea"],'N');
	addSetClause($setList,'Sesso',$old["Sesso"],'S');
	addSetClause($setList,'NotaCliente',$old["NotaCliente"],'S');

	$sqlStorno = "UPDATE cliente $setList WHERE IdCliente=$IdCliente";

	// Prepara l'istruzione di update
	preparaCampiCliente($row); // calcola alcuni campi
	
	if ($CodCliente=='' and $old["CodCliente"]>'')
		$row['CodCliente'] = $old["CodCliente"]; 
	$row['IdCliente'] = $IdCliente; // $row contiene l'IdCliente di import_temp_cliente: evita che annulli il parametro passato
	extract($row);
	
	$setList = "";
	addSetClause($setList,'CodCliente',$CodCliente,'S');
	addSetClause($setList,'SiglaProvincia',$SiglaProvincia,'S');
	addSetClause($setList,'SiglaNazione',$SiglaNazione>''?$SiglaNazione:'IT','S');
	addSetClause($setList,'CodFormaGiuridica',$CodFormaGiuridica,'S');
	addSetClause($setList,'Nominativo',$Nominativo,'S');
	addSetClause($setList,'RagioneSociale',$RagioneSociale,'S');
	addSetClause($setList,'IdTipoCliente',$IdTipoCliente,'N');
	addSetClause($setList,'DataNascita',$DataNascita,'D');
	addSetClause($setList,'LocalitaNascita',$LocalitaNascita,'S');
	addSetClause($setList,'CodiceFiscale',$CodiceFiscale,'S');
	addSetClause($setList,'PartitaIVA',$PartitaIVA,'S');
	addSetClause($setList,'IdArea',$IdArea,'N');
	addSetClause($setList,'Sesso',$Sesso,'S');
	// Il campo nota cliente viene aggiornato concatenando il nuovo dato al vecchio, a meno che il vecchio dato non esista già 
	$n = "CONCAT(IFNULL(NotaCliente,''),if(notacliente like _latin1'%".addslashes($NotaCliente)."%',_latin1'',_latin1".quote_smart($NotaCliente)."))";
	addSetClause($setList,'NotaCliente',$n,'G');
	
	// Esegue l'update
	$sql = "UPDATE cliente $setList WHERE IdCliente=$IdCliente";
//	 writeProcessLog($processName,$sql,0);
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	if (getAffectedRows()>0) { // effettivamente ha aggiornato qualche riga
		registraStorno($sqlStorno);
		return true;
	} else { // non e' cambiato alcun campo
		return false;
	}
}

/**
 * preparaCampiCliente prepara alcuni campi derivati per insert/update inla tabella cliente
 * @param {Array} (by ref) riga di temp_import_cliente
 */
function preparaCampiCliente(&$row) {
	extract($row);
	// Determina, in maniera plausibile ma non certa, se il debitore e' persona fisica o giuridica
	// Prepara i campi PartitaIVA/Codice Fiscale
	if ($PartitaIVA>'') {
		$IdTipoCliente = 1; // persona giuridica
		$PartitaIVA = substr($PartitaIVA,-11); // partita IVA nuda e cruda
	} else if ($CodiceFiscale>'') {
		if (preg_match('/^([a-z]{2})?([0-9]{0,5})?([0-9]{11})$/i',$CodiceFiscale,$arr)) { // il campo CodiceFiscale contiene una partita IVA
			$PartitaIVA = $arr[3]; // partita IVA nuda e cruda
			$CodiceFiscale = null;
			$IdTipoCliente = 1; // persona giuridica
		} else {
			$IdTipoCliente = 2; // persona fisica
		}
	} else if ($RagioneSociale>'') {
		$IdTipoCliente = 1; // persona giuridica
	} else if ($Nominativo>'' or $NomeDebitore>'') {
		$IdTipoCliente = 2; // persona fisica
	}
	
	// Prepara il nome o ragione sociale
	if ($IdTipoCliente==1) {
		$RagioneSociale = $RagioneSociale>'' ? $RagioneSociale : ($Nominativo>'' ? $Nominativo : "$CognomeDebitore $NomeDebitore" );
		$Nominativo = null;
	} else {
		$Nominativo = $RagioneSociale>'' ? $RagioneSociale : ($Nominativo>'' ? $Nominativo : "$CognomeDebitore $NomeDebitore" );
		$RagioneSociale = null;
	}
	
	// Prepara l'IdArea
	if (trim($Area)>'') {
		$Area = quote_smart(trim($Area));
		$sql = "SELECT IdArea FROM area WHERE TitoloArea LIKE $Area";
		$IdArea = getScalar($sql);
		if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
		if (!$IdArea) { // crea l'area geografica
			$sql = "INSERT INTO area (CodArea,TitoloArea,TipoArea,DataIni,DataFin,LastUser)"
			     . " VALUES($Area,$Area,'R',CURDATE(),'9999-12-31','import')";
			if (!execute($sql)) erroreProcesso(getLastError()." SQL: $sql");
			$IdArea = getInsertId();
			registraStorno("DELETE FROM area WHERE IdArea=$IdArea");
		}
	}
	// Rimette in nuovi valori in $row
	$row['IdTipoCliente'] 	= $IdTipoCliente;
	$row['Nominativo'] 		= $Nominativo;
	$row['RagioneSociale'] 	= $RagioneSociale;
	$row['PartitaIVA'] 		= $PartitaIVA;
	$row['CodiceFiscale'] 	= $CodiceFiscale;
	$row['IdArea'] 			= $IdArea;
}

/**
 * trovaContratto Analizza una riga di temp_import_contratto per determinare se il contratto gia' esiste in tabella contratto
 * @param {Array} $row riga letta da temp_import_contratto
 * @param {Number} $IdMandante Id del mandante (tabella compagnia)
 * @param {Number} $IdCliente ID del cliente
 * @param {Boolean} (byref) $NuovoCliente lo imposta a true se è il primo 
 * @return {Number} IdContratto oppure null (non trovato) oppure -1 (ambiguo)
 */
function trovaContratto($row,$IdMandante,$IdCliente) {
	global $trasf,$processName,$filePath,$idLotto,$idModulo;

	extract($row);
	
	if ($trasf['opzCreaCodContratto'] or $CodContratto=='') {  // il numero pratica viene generato automaticamente
		//  Deve necessariamente trovare il contratto usando il numero documento (che viene usato in tal caso
		//  per creare una pratica per ciascun documento)
		$NumDocumento = getScalar("SELECT NumDocumento FROM temp_import_posizione WHERE IdTempContratto=0$IdTempContratto");
		if ($NumDocumento>'') {
			$where = "IdContratto IN (SELECT IdContratto FROM movimento WHERE NumDocumento=".quote_smart($NumDocumento).")";
		} else {
			return null; // se non è identificabile, ogni input è un nuovo contratto (soluzione da evitare perché non funziona
						 // per la modalità di aggiornamento del lotto)			
		}
	} else { // il codice contratto e' nei dati
		$where = "IdCliente=$IdCliente AND IdCompagnia=$IdMandante AND CodContratto=".quote_smart($row['CodContratto']);
	}
	
	$ids = getColumn($sql="SELECT IdContratto FROM contratto WHERE $where");
	if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
	if (!$ids) {
		return null;
	} else if (count($ids)==1) {
		return $ids[0];
	} else {
		return -1;
	}
}

/**
 * trovaRecapito Analizza una riga di temp_import_recapito per determinare se il recapito gia' esiste in tabella contratto
 * @param {Array} $row riga letta da temp_import_recapito
 * @param {Number} $IdCliente ID del cliente
 * @param {String} $suffix suffiso dei nomi dei campi da considerare in temp_import_recapito ("","2","3","4")
 * @return {Number} IdContratto oppure null (non trovato) oppure -1 (ambiguo), o -2 (gruppo indirizzi vuoto)
 */
function trovaRecapito($row,$IdCliente,$suffix) {
	global $trasf,$processName,$filePath,$idLotto,$idModulo;

	$Nome	   = $row["Nome$suffix"]; 
	$Indirizzo = $row["Indirizzo$suffix"]; 
	$Telefono  = $row["Telefono$suffix"]; 
	$Cellulare = $row["Telefono$suffix"];
	$Email	   = $row["Email$suffix"];
	$Localita = $row["Localita$suffix"];
	$CAP = $row["CAP$suffix"];
	$Fax = $row["Fax$suffix"];
	
	// Se i campi significativi di questo gruppo sono vuoti, considera il gruppo vuoto
	if ($Indirizzo=='' && $Localita=='' && $CAP=='' && $Telefono=='' && $Cellulare=='' && $Fax=='' && $Email=='' && $Nome=='')
		return -2;
	
	$where = array();
	
	if ($Nome>'') 
		$where[] = "Nome LIKE ".quote_smart($Nome);
	if ($Indirizzo>'')
		$where[] = "Indirizzo LIKE ".quote_smart($Indirizzo);
	if ($Telefono>'')
		$where[] = "Telefono LIKE ".quote_smart($Telefono);
	if ($Cellulare>'')
		$where[] = "Cellulare LIKE ".quote_smart($Cellulare);
	if ($Email>'')
		$where[] = "Email LIKE ".quote_smart($Email);
	if ($Nome>'')
		$where[] = "Nome LIKE ".quote_smart($Nome);
	
	if (count($where)>0) {
		$where = "IdCliente=$IdCliente AND ProgrRecapito<0"  // questo distingue i recapiti creati dall'import
		       . " AND (".implode(" OR ",$where).")";
	} else {
		$where = "IdCliente=$IdCliente AND ProgrRecapito<0";
	}
	
	$ids = getColumn($sql="SELECT IdRecapito FROM recapito WHERE $where");
	//  writeProcessLog($processName,"$sql => ".count($ids),0);
	if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
	if (!$ids) {
		return null;
	} else if (count($ids)==1) {
		return $ids[0];
	} else { // piu' di una riga: tenta di distinguerlo dal valore della posizione
		return -1;
	}
}

/**
 * creaContratto Crea una riga nella tabella contratto a partre da una riga di temp_import_contratto
 * @param {Array} $row riga letta da temp_import_contratto
 * @param {Number} $IdMandante Id del mandante (tabella compagnia)
 * @param {Number} $IdCliente ID del cliente
 * @param {Number} (by ref) $numInsNote contatore del numero di righe inserite in "nota"
 * @return {Number} IdContratto della riga appena creata
 */
function creaContratto($IdMandante,$IdCliente,$row) {
	global $trasf,$idLotto;

	extract($row);

	// Se il codice contratto non e' fornito, lo crea, concatenando un progressivo al codice cliente
	if ($trasf['opzCreaCodContratto'] or $CodContratto=='') {
		$CodCliente = getScalar($sql="SELECT CodCliente FROM cliente WHERE IdCliente=$IdCliente");
		if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
		$prefix = "$CodCliente-";
		// Determina l'ultimo numero assegnato con questo prefisso, ammesso che ce ne sia
		$sql = "SELECT MAX(CodContratto) FROM contratto WHERE CodContratto LIKE '$prefix%'";
		$max = getScalar($sql);
		if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
		if (preg_match('/^([0-9]+)$/',substr($max,strlen($prefix)),$arr)) { // e' numerico, puo' usarlo
			$CodContratto = $prefix.str_pad(1+$arr[1],3,'0',STR_PAD_LEFT);
		} else { // non numerico o assente, comincia la numerazione da adesso
			$CodContratto = $prefix."001";
		}
	}

	// Legge alcuni dati dal lotto (att.ne! dataini e datafin sono le date di affido, non quelle che vanno nei campi DataIni e DataFin del contratto)
	$r = getRow($sql="SELECT DataIni,DataFin,IdProdotto FROM lotto WHERE IdLotto=$idLotto");
	if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
	extract($r);
	
	// Prepara l'istruzione INSERT
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'CodContratto',$CodContratto,'S');
	addInsClause($colList,$valList,'IdCliente',$IdCliente,'N');
	addInsClause($colList,$valList,'IdCompagnia',$IdMandante,'N');
	addInsClause($colList,$valList,'Garanzie',$Garante,'S');
	addInsClause($colList,$valList,'IdProdotto',$IdProdotto,'N');
	addInsClause($colList,$valList,'IdClasse',$IdClasse>0?$IdClasse:1,'N');
	addInsClause($colList,$valList,'IdStatoContratto',$IdStatoContratto>0?$IdStatoContratto:1,'N');
	addInsClause($colList,$valList,'IdStatoRecupero',$IdStatoRecupero>0?$IdStatoRecupero:2,'N');
	addInsClause($colList,$valList,'ImpFinanziato',$ImpFinanziato,'I');
	addInsClause($colList,$valList,'ImpErogato',$ImpErogato,'I');
	addInsClause($colList,$valList,'NumRate',$NumRate,'N');
	addInsClause($colList,$valList,'ImpRata',$ImpRata,'I');
	addInsClause($colList,$valList,'NumEffettiScaduti',$NumEffettiScaduti,'S');
	addInsClause($colList,$valList,'DataPrimaScadenza',$DataPrimaScadenza,'D');
	addInsClause($colList,$valList,'DataUltimaScadenza',$DataUltimaScadenza,'D');
	addInsClause($colList,$valList,'CodBene',$CodBene,'S');
	addInsClause($colList,$valList,'DescrBene',$DescrBene,'S');
	addInsClause($colList,$valList,'ImpValoreBene',$ImpValoreBene,'I');
	addInsClause($colList,$valList,'DataIni','CURDATE()','G');
	addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
	addInsClause($colList,$valList,'LastUser','import','S');

	// Inserisce
	$sql = "INSERT INTO contratto ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	$IdContratto = getInsertId();

	// Registra una riga in storiarecupero
	$sql = "insert into storiarecupero (idcontratto,dataevento,descrevento,notaevento) "
	    .  "VALUES($IdContratto,now(),'Caricamento iniziale','')";
	if (!execute($sql)) erroreProcesso(getLastError()." SQL: $sql");	
	
	// Storni (verranno eseguiti in ordine inverso)
	registraStorno("DELETE FROM contratto WHERE IdContratto=$IdContratto");
	registraStorno("DELETE FROM insoluto WHERE IdContratto=$IdContratto");
	registraStorno("DELETE FROM _opt_insoluti WHERE IdContratto=$IdContratto");
	registraStorno("DELETE FROM storiarecupero WHERE IdContratto=$IdContratto");
	
	/* Registra le note, se presenti */
	if ($Nota1>'') creaNotaContratto($IdContratto,$Nota1,$numInsNote);
	if ($Nota2>'') creaNotaContratto($IdContratto,$Nota2,$numInsNote);
	if ($Nota3>'') creaNotaContratto($IdContratto,$Nota3,$numInsNote);
	if ($Nota4>'') creaNotaContratto($IdContratto,$Nota4,$numInsNote);
	
	return $IdContratto;
}

/**
 * aggiornaContratto Aggiorna una riga della tabella contratto a partire da una riga di temp_import_contratto
 * @param {Number} IdContratto ID della riga da aggiornare
 * @param {Array} $row riga letta da temp_import_contratto
 * @param {Number} (by ref) $numInsNote contatore del numero di righe scritte su "nota"
 * @param {Number} (by ref) $numUpdNote contatore del numero di righe aggiornate su "nota"
 * @param {Number} (by ref) $numDelNote contatore del numero di righe cancellate da "nota"
 */
function aggiornaContratto($IdContratto,$row,&$numInsNote,&$numUpdNote,&$numDelNote) {
	global $idLotto;
	// Legge la riga prima di aggiornarla
	$old = getRow($sql="SELECT * FROM contratto WHERE IdContratto=$IdContratto");
	if (getLastError()>'') erroreProcesso(getLastError()." SQL: $sql");
	if (!$old)  // la riga non c'è: per quanto improbabile, significa che un altro utente l'ha cancellata: non fare nulla
		return false;

	// Prepara l'istruzione di storno
	$setList = "";	
	addSetClause($setList,'CodContratto',$old['CodContratto'],'S');
	addSetClause($setList,'Garanzie',$old['Garante'],'S');
	addSetClause($setList,'IdClasse',$old['IdClasse'],'N');
	addSetClause($setList,'IdStatoContratto',$old['IdStatoContratto'],'N');
	addSetClause($setList,'IdStatoRecupero',$old['IdStatoRecupero'],'N');
	addSetClause($setList,'ImpFinanziato',$old['ImpFinanziato'],'I');
	addSetClause($setList,'ImpErogato',$old['ImpErogato'],'I');
	addSetClause($setList,'NumRate',$old['NumRate'],'N');
	addSetClause($setList,'ImpRata',$old['ImpRata'],'I');
	addSetClause($setList,'NumEffettiScaduti',$old['NumEffettiScaduti'],'S');
	addSetClause($setList,'DataPrimaScadenza',$old['DataPrimaScadenza'],'D');
	addSetClause($setList,'DataUltimaScadenza',$old['DataUltimaScadenza'],'D');
	addSetClause($setList,'CodBene',$old['CodBene'],'S');
	addSetClause($setList,'ImpValoreBene',$old['ImpValoreBene'],'I');
	addSetClause($setList,'DescrBene',$old['DescrBene'],'S');
	addSetClause($setList,'LastUser','import','S');
	
	$sqlStorno = "UPDATE contratto $setList WHERE IdContratto=$IdContratto";
	
	// Prepara l'istruzione di update
	$setList = "";
	addSetClause($setList,'CodContratto',$row['CodContratto'],'S');
	addSetClause($setList,'Garanzie',$row['Garante'],'S');
	addSetClause($setList,'IdClasse',$row['IdClasse'],'N');
	addSetClause($setList,'IdStatoContratto',$row['IdStatoContratto']>0?$row['IdStatoContratto']:1,'N');
	addSetClause($setList,'IdStatoRecupero',$row['IdStatoRecupero']>0?$row['IdStatoRecupero']:2,'N');
	addSetClause($setList,'ImpFinanziato',$row['ImpFinanziato'],'I');
	addSetClause($setList,'ImpErogato',$row['ImpErogato'],'I');
	addSetClause($setList,'NumRate',$row['NumRate'],'N');
	addSetClause($setList,'ImpRata',$row['ImpRata'],'I');
	addSetClause($setList,'NumEffettiScaduti',$row['NumEffettiScaduti'],'S');
	addSetClause($setList,'DataPrimaScadenza',$row['DataPrimaScadenza'],'D');
	addSetClause($setList,'DataUltimaScadenza',$row['DataUltimaScadenza'],'D');
	addSetClause($setList,'CodBene',$row['CodBene'],'S');
	addSetClause($setList,'ImpValoreBene',$row['ImpValoreBene'],'I');
	addSetClause($setList,'DescrBene',$row['DescrBene'],'S');
	addSetClause($setList,'LastUser','import','S');
			
	// Esegue l'update
	$sql = "UPDATE contratto $setList WHERE IdContratto=$IdContratto";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	if (getAffectedRows()>0) {  // effettivamente ha aggiornato qualche riga
		registraStorno($sqlStorno);
		aggiornaNoteContratto($IdContratto,$row,$numInsNote,$numUpdNote,$numDelNote);
		
		// Registra una riga in storiarecupero
		$sql = "insert into storiarecupero (idcontratto,dataevento,descrevento,notaevento) "
		     . " VALUES($IdContratto,now(),'Aggiornamento dei dati (import)','')";
		if (!execute($sql)) erroreProcesso(getLastError()." SQL: $sql");
		registraStorno("DELETE FROM storiarecupero WHERE IdContratto=$IdContratto");
		
		return true;
	} else { // non è cambiato alcun campo del contratto
		// Aggiorna le note del contratto
		aggiornaNoteContratto($IdContratto,$row,$numInsNote,$numUpdNote,$numDelNote);
		return false;
	}
}

/**
 * creaNotaContratto Inserisci una riga di tipo N nella tabella nota
 * @param {Number} $IdContratto ID del contratto
 * @param {String} $Testo testo della nota
 * @param {Number} (by ref) $numInsNote contatore del numero di righe scritte su "nota"
 */
function creaNotaContratto($IdContratto,$Testo,&$numInsNote) {
	$t = preg_replace('/(\n|<br>|<br\/>)/','',$Testo);
	if (trim($t)=='') { // vuota o consistente solo di salti riga
		return;
	}
	
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'IdContratto',$IdContratto,'N');
	addInsClause($colList,$valList,'TipoNota',"N",'S');
	addInsClause($colList,$valList,'TestoNota',$Testo,'S');
	addInsClause($colList,$valList,'DataCreazione','CURDATE()','G');
	addInsClause($colList,$valList,'DataIni','CURDATE()','G');
	addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
	addInsClause($colList,$valList,'LastUser','import','S');
	$sql = "INSERT INTO nota ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	
	// Registra le istruzione di storno (che vengono eseguite in ordine inverso)
	$IdNota = getInsertId();
	registraStorno("DELETE FROM nota WHERE IdNota = $IdNota");
	registraStorno("DELETE FROM notautente WHERE IdNota = $IdNota");
	$numInsNote++;
}

/**
 * aggiornaNoteContratto Aggiorna le note di import di un contratto
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row riga letta da temp_import_contratto
 * @param {Number} (by ref) $numInsNote contatore del numero di righe scritte su "nota"
 * @param {Number} (by ref) $numUpdNote contatore del numero di righe aggiornate su "nota"
 * @param {Number} (by ref) $numDelNote contatore del numero di righe cancellate da "nota"
 */
function aggiornaNoteContratto($IdContratto,$row,&$numInsNote,&$numUpdNote,&$numDelNote) {
	
	// Legge il contenuto attuale ed esegue update oppure insert, con relativo storno
	$note = getRows("SELECT * FROM nota WHERE IdContratto=$IdContratto AND TipoNota='N' AND LastUser='import' ORDER BY 1");
	foreach ($note as $i=>$old) {
		$TestoNota = $row['Nota'.($i+1)];
		if ($TestoNota>'') { // la nota (i+1)-esima ha un testo anche nel nuovo record
			// Prepara l'istruzione di storno
			$setList = "";
			addSetClause($setList,'TestoNota',$old['TestoNota'],'S');
			$sqlStorno = "UPDATE nota $setList WHERE IdNota={$old['IdNota']}";

			// Prepara l'istruzione di update
			$setList = "";
			addSetClause($setList,'TestoNota',$TestoNota,'S');
			$sql = "UPDATE nota $setList WHERE IdNota={$old['IdNota']}";
			if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
			$numUpdNote += getAffectedRows();
		} else { // la nota (i+1)-esima NON ha un testo nel nuovo record
			// Prepara l'istruzione di storno (in questo caso, un insert)
			$colList = "";
			$valList = "";
			addInsClause($colList,$valList,'IdContratto',$old['IdContratto'],'N');
			addInsClause($colList,$valList,'TipoNota',"N",'S');
			addInsClause($colList,$valList,'TestoNota',$old['Testo'],'S');
			addInsClause($colList,$valList,'DataCreazione',$old['DataCreazione'],'D');
			addInsClause($colList,$valList,'DataIni',$old['DataIni'],'D');
			addInsClause($colList,$valList,'DataFin',$old['DataFin'],'D');
			addInsClause($colList,$valList,'LastUser','import','S');
			$sqlStorno = "INSERT INTO nota ($colList) VALUES($valList)";
				
			// Prepara l'istruzione DELETE
			$sql = "DELETE FROM nota WHERE IdNota={$old['IdNota']}";
			if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
			$numDelNote++;
		}
		registraStorno($sqlStorno);
	}

	// Alla fine del loop, se le nuove note sono più delle vecchie, le inserisce
	for (;$i<4; $i++) {
		$colList = "";
		$valList = "";
		addInsClause($colList,$valList,'IdContratto',$IdContratto,'N');
		addInsClause($colList,$valList,'TipoNota',"N",'S');
		addInsClause($colList,$valList,'TestoNota',str_replace($row['Nota'+($i+1)],'\n','<br>'),'S');
		addInsClause($colList,$valList,'DataCreazione','CURDATE()','G');
		addInsClause($colList,$valList,'DataIni','CURDATE()','G');
		addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
		addInsClause($colList,$valList,'LastUser','import','S');
		$sql = "INSERT INTO nota ($colList) VALUES($valList)";
		if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
		$numInsNote++;
		
		// Registra l'istruzione di storno
		registraStorno("DELETE FROM nota WHERE IdNota = ".getInsertId());
	}
}

/**
 * creaRecapito Crea una riga nella tabella recapito a partire da una riga di temp_import_recapito
 * NOTA: la tabella temp_import_recapito contiene 4 gruppi di campi identici: l'argomento $suffix determina quale gruppo si considera
 * @param {Array} $row riga letta da temp_import_recapito
 * @param {Number} $IdCliente ID del cliente
 * @param {String} $suffix suffiso dei nomi dei campi da considerare in temp_import_recapito ("","2","3","4")
 * @return {Number} IdRecapito della riga appena creata
 */
function creaRecapito($row,$IdCliente,$suffix) {
	global $trasf,$idLotto;

	// Se i campi significativi di questo gruppo sono vuoti, non scrive nulla
	$Indirizzo = $row["Indirizzo$suffix"];
	$Localita = $row["Localita$suffix"];
	$CAP = $row["CAP$suffix"];
	$Telefono = $row["Telefono$suffix"];
	$Cellulare = $row["Cellulare$suffix"];
	$Fax = $row["Fax$suffix"];
	$Email = $row["Email$suffix"];
	$Nome = $row["Nome$suffix"];
	
	if ($Indirizzo=='' && $Localita=='' && $CAP=='' && $Telefono=='' && $Cellulare=='' && $Fax=='' && $Email=='' && $Nome=='')
		return null;

	if ($row["IdTipoRecapito$suffix"]>'')
		$tipo = $row["IdTipoRecapito$suffix"];
	else if ($suffix=='')
		$tipo = "1"; // recapito principale
	else
		$tipo = "99"; // altro recapito 
	
	// Prepara l'istruzione INSERT
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'IdCliente',$IdCliente,'N');
	addInsClause($colList,$valList,"ProgrRecapito","-UNIX_TIMESTAMP()","G"); // convenzione usata per ordinare per ultimi quelli creati dall'utente
	addInsClause($colList,$valList,'IdTipoRecapito',$tipo,'N');
	addInsClause($colList,$valList,'Indirizzo',$Indirizzo,'S');
	addInsClause($colList,$valList,'Localita',$Localita,'S');
	addInsClause($colList,$valList,'CAP',$CAP,'S');
	addInsClause($colList,$valList,'SiglaProvincia',$row["SiglaProvincia$suffix"],'S');
	addInsClause($colList,$valList,'SiglaNazione',$row["SiglaNazione$suffix"],'S');
	addInsClause($colList,$valList,'Telefono',$Telefono,'S');
	addInsClause($colList,$valList,'Cellulare',$Cellulare,'S');
	addInsClause($colList,$valList,'Fax',$Fax,'S');
	addInsClause($colList,$valList,'Email',$Email,'S');
	addInsClause($colList,$valList,'Nome',$Nome,'S');
	addInsClause($colList,$valList,'DataIni','CURDATE()','G');
	addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
	addInsClause($colList,$valList,'LastUser','import','S');

	// Inserisce
	$sql = "INSERT INTO recapito ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	$IdRecapito = getInsertId();
	registraStorno("DELETE FROM recapito WHERE IdRecapito=$IdRecapito");

	return $IdRecapito;
}

/**
 * aggiornaRecapito Aggiorna una riga della tabella recapito a partire da una riga di temp_import_recapito
 * @param {Array} $row riga letta da temp_import_crecapito
 * @param {Number} $IdRecapito ID della riga da aggiornare
 * @param {String} $suffix suffiso dei nomi dei campi da considerare in temp_import_recapito ("","2","3","4")
 * @return {Boolean} true se l'aggiornamento e' stato effettuato
 */
function aggiornaRecapito($row,$IdRecapito,$suffix) {

	// Legge la riga prima di aggiornarla
	$old = getRow($sql="SELECT * FROM recapito WHERE IdRecapito=$IdRecapito");
	if (getLastError()>'') erroreProcesso(getLastError()." SQL: $sql");
	if (!$old)  // la riga non c'è: per quanto improbabile, significa che un altro utente l'ha cancellata: non fare nulla
		return false;

	// Prepara l'istruzione di storno
	$setList = "";
	addSetClause($setList,'IdTipoRecapito',$old["IdTipoRecapito"],'N');
	addSetClause($setList,'Indirizzo',$old["Indirizzo"],'S');
	addSetClause($setList,'Localita',$old["Localita"],'S');
	addSetClause($setList,'CAP',$old["CAP"],'S');
	addSetClause($setList,'SiglaProvincia',$old["SiglaProvincia"],'S');
	addSetClause($setList,'SiglaNazione',$old["SiglaNazione"],'S');
	addSetClause($setList,'Telefono',$old["Telefono"],'S');
	addSetClause($setList,'Cellulare',$old["Cellulare"],'S');
	addSetClause($setList,'Fax',$old["Fax"],'S');
	addSetClause($setList,'Email',$old["Email"],'S');
	addSetClause($setList,'Nome',$old["Nome"],'S');
	
	$sqlStorno = "UPDATE recapito $setList WHERE IdRecapito=$IdRecapito";
	
	// Prepara l'istruzione di update
	if ($row["IdTipoRecapito$suffix"]>'')
		$tipo = $row["IdTipoRecapito$suffix"];
	else if ($suffix=='')
		$tipo = "1"; // recapito principale
	else
		$tipo = "99"; // altro recapito
	
	$setList = "";
	addSetClause($setList,'IdTipoRecapito',$tipo,'N');
	addSetClause($setList,'Indirizzo',$row["Indirizzo$suffix"],'S');
	addSetClause($setList,'Localita',$row["Localita$suffix"],'S');
	addSetClause($setList,'CAP',$row["CAP$suffix"],'S');
	addSetClause($setList,'SiglaProvincia',$row["SiglaProvincia$suffix"],'S');
	addSetClause($setList,'SiglaNazione',$row["SiglaNazione$suffix"],'S');
	addSetClause($setList,'Telefono',$row["Telefono$suffix"],'S');
	addSetClause($setList,'Cellulare',$row["Cellulare$suffix"],'S');
	addSetClause($setList,'Fax',$row["Fax$suffix"],'S');
	addSetClause($setList,'Email',$row["Email$suffix"],'S');
	addSetClause($setList,'Nome',$row["Nome$suffix"],'S');
	addSetClause($setList,'LastUser',"import",'S');
	
	// Esegue l'update
	$sql = "UPDATE recapito $setList WHERE IdRecapito=$IdRecapito";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	if (getAffectedRows()>0) { // effettivamente ha aggiornato qualche riga
		registraStorno($sqlStorno);
		return true;
	} else { // non e' cambiato alcun campo
		return false;
	}
}

/**
 * aggiornaMovimento Aggiorna una riga della tabella movimento a partire da una riga di temp_import_movimento
 * (modificata da creaMovimento)
 * @param {Array} $old contenuto della riga preesistente su "movimento"
 * @param {Array} $row contenuto della riga di temp_import_movimento
 * @return {Boolean} true se l'aggiornamento e' stato effettuato
 */
function aggiornaMovimento($old,$row) {

	extract($old);
	
	// Prepara l'istruzione di storno
	$setList = "";
	addSetClause($setList,'IdTipoMovimento',$IdTipoMovimento,'N');
	addSetClause($setList,'NumDocumento',$NumDocumento,'S');
	addSetClause($setList,'DataRegistrazione',$DataRegistrazione,'D');
	addSetClause($setList,'DataDocumento',$DataDocumento,'D');
	addSetClause($setList,'DataCompetenza',$DataCompetenza,'D');
	addSetClause($setList,'DataScadenza',$DataScadenza,'D');
	addSetClause($setList,'NumRata',$NumRata,'N');
	addSetClause($setList,'Importo',$Importo,'N');
	addSetClause($setList,'LastUser',$LastUser,'S');

	$sqlStorno = "UPDATE movimento $setList WHERE IdMovimento=$IdMovimento";

	// Prepara l'istruzione di update
	extract($row);
	
	$setList = "";
	addSetClause($setList,'IdTipoMovimento',$IdTipoMovimento,'N');
	addSetClause($setList,'NumDocumento',$NumDocumento>''?$NumDocumento:' ','S');
	addSetClause($setList,'DataRegistrazione',$DataRegistrazione,'D');
	addSetClause($setList,'DataDocumento',$DataDocumento,'D');
	addSetClause($setList,'DataCompetenza',$DataCompetenza,'D');
	addSetClause($setList,'DataScadenza',$DataScadenza,'D');
	addSetClause($setList,'NumRata',$NumRata?$NumRata:0,'N');
	addSetClause($setList,'Importo',$Importo,'N');
	addSetClause($setList,'LastUser',"import",'S');

	// Esegue l'update
	$sql = "UPDATE movimento $setList WHERE IdMovimento=$IdMovimento";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	if (getAffectedRows()>0) { // effettivamente ha aggiornato qualche riga
		registraStorno($sqlStorno);
		return true;
	} else { // non e' cambiato alcun campo
		return false;
	}
}
/**
 * aggiornaStoriaRecupero Aggiorna una riga della tabella storiarecupero a partire da temp_import_storiarecupero
 * @param {Array} $old contenuto della riga preesistente su "movimento"
 * @param {Array} $row contenuto della riga di temp_import_movimento
 * @return {Boolean} true se l'aggiornamento e' stato effettuato 
 */
function aggiornaStoriaRecupero($old,$row) {

	extract($old);

	// Prepara l'istruzione di storno
	$setList = "";
	addSetClause($setList,'DataEvento',$DataEvento,'D');
	addSetClause($setList,'DescrEvento',$DescrEvento,'S');
	addSetClause($setList,'NotaEvento',$NotaEvento,'S');

	$sqlStorno = "UPDATE storiarecupero $setList WHERE IdStoriaRecupero=$IdStoriaRecupero";

	// Prepara l'istruzione di update
	extract($row);

	$setList = "";
	addSetClause($setList,'DataEvento',$DataEvento,'D');
	addSetClause($setList,'DescrEvento',$DescrEvento,'S');
	addSetClause($setList,'NotaEvento',$NotaEvento,'S');
	
	// Esegue l'update
	$sql = "UPDATE storiarecupero $setList WHERE IdStoriaRecupero=$IdStoriaRecupero";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	if (getAffectedRows()>0) { // effettivamente ha aggiornato qualche riga
		registraStorno($sqlStorno);
		return true;
	} else { // non e' cambiato alcun campo
		return false;
	}
}

/**
 * inserisceMovimento Inserisce una riga della tabella movimento a partire da una riga di temp_import_movimento
 * (modificata da creaMovimento)
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row contenuto della riga di temp_import_movimento
 */
function inserisceMovimento($IdContratto,$row) {

	// Prepara l'istruzione di insert
	extract($row);

	// Crea la relazione tra contratto e garante
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'IdContratto',$IdContratto,'N');
	addInsClause($colList,$valList,'IdTipoMovimento',$IdTipoMovimento,'N');
	addInsClause($colList,$valList,'NumDocumento',$NumDocumento>''?$NumDocumento:' ','S');
	addInsClause($colList,$valList,'DataRegistrazione',$DataRegistrazione,'D');
	addInsClause($colList,$valList,'DataDocumento',$DataDocumento,'D');
	addInsClause($colList,$valList,'DataCompetenza',$DataCompetenza,'D');
	addInsClause($colList,$valList,'DataScadenza',$DataScadenza,'D');
	addInsClause($colList,$valList,'NumRata',$NumRata?$NumRata:0,'N');
	addInsClause($colList,$valList,'Importo',$Importo,'N');
	addInsClause($colList,$valList,'LastUser',"import",'S');
	
	$sql = "INSERT INTO movimento ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	
	// Registra l'istruzione di storno
	registraStorno("DELETE FROM movimento WHERE IdMovimento=".getInsertId());
}

/**
 * inserisceStoriaRecupero Inserisce una riga della tabella storiarecupero a partire da una riga di temp_import_storiarecuper
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row contenuto della riga di temp_import_storiarecupero
 */
function inserisceStoriaRecupero($IdContratto,$row) {

	// Prepara l'istruzione di insert
	extract($row);

	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'IdContratto',$IdContratto,'N');
	addInsClause($colList,$valList,'DataEvento',$DataEvento,'D');
	addInsClause($colList,$valList,'DescrEvento',$DescrEvento,'S');
	addInsClause($colList,$valList,'NotaEvento',$NotaEvento,'S');
	
	$sql = "INSERT INTO storiarecupero ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");

	// Registra l'istruzione di storno
	registraStorno("DELETE FROM storiarecupero WHERE IdStoriaRecupero=".getInsertId());
}

/**
 * creaGarante Crea una riga nella tabella controparte a partire da una riga di temp_import_garante. Se necessario, crea la
 *   riga necessaria nella tabella cliente. Se l'input contiene un recapito, inserisce anche quello
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row riga letta da temp_import_garante
 * @param {Number} $nNew (by ref) contatore delle righe inserite nella tabella cliente
 * @return {Number} numero di righe create (1) oppure false
 */
function creaGarante($IdContratto,$row,&$nNew) {
	global $trasf,$idLotto;

	// trasforma la riga in modo che possa essere utilizzata dalle funzioni usate per i "debitori"
	extract($row);
	$row['CodiceFiscale'] 	= $CodFiscaleGarante;
	$row['Nominativo'] 		= $NominativoGarante;
	$row['NomeDebitore'] 	= $NomeGarante;
	$row['CognomeDebitore'] = $CognomeGarante;
	$row['NotaCliente'] 	= $NotaGarante;
	
	// determina se esiste un cliente con la stessa "identita'"
	$IdMandante = getScalar($sql="SELECT IdMandante FROM lotto WHERE IdLotto=$idLotto");
	if (getLastError()>'') erroreProcesso(getLastError()." SQL=$sql");
	
	$IdCliente = trovaCliente($row,$IdMandante);
	if ($IdCliente===null) { // il cliente deve essere creato
		$IdCliente = creaCliente($row);
		$nNew++;
	} else if ($IdCliente==-1) { // il cliente e' ambiguo (il messaggio viene emesso dalla funzione trovaCliente)
		return false;
	} else { // il cliente esiste gia'
	}
	
	// Crea o aggiorna la relazione tra debitore e mandante
	$sql = "REPLACE INTO clientecompagnia (IdCliente,IdCompagnia) VALUES($IdCliente,$IdMandante)";
	if (!execute($sql)) erroreProcesso(getLastError()." SQL=$sql");
	registraStorno("DELETE FROM clientecompagnia WHERE IdCliente=$IdCliente");
	
	// Determina il tipo di controparte
	$IdTipoControparte = getScalar($sql="SELECT IdTipoControparte FROM tipocontroparte WHERE ".quote_smart($TipoGarante)." LIKE CONCAT('%',TitoloTipoControparte,'%')");
	if (getLastError()>'')	erroreProcesso(getLastError()." SQL: $sql");
	if (!$IdTipoControparte) $IdTipoControparte = 999; // soggetto collegato generico
	
	// Crea la relazione tra contratto e garante
	$colList = "";
	$valList = "";
	addInsClause($colList,$valList,'IdContratto',$IdContratto,'N');
	addInsClause($colList,$valList,'IdCliente',$IdCliente,'N');
	addInsClause($colList,$valList,'IdTipoControparte',$IdTipoControparte,'N');
	addInsClause($colList,$valList,'DataIni',"'2001-01-01'",'G');
	addInsClause($colList,$valList,'DataFin',"'9999-12-31'",'G');
	addInsClause($colList,$valList,'LastUser','import','S');
	$sql = "INSERT INTO controparte ($colList) VALUES($valList)";
	if (!execute($sql))	erroreProcesso(getLastError()." SQL: $sql");
	
	// Registra l'istruzione di storno
	registraStorno("DELETE FROM controparte WHERE IdContratto = $IdContratto AND IdCliente=$IdCliente");
	
	// Se ci sono info sul recapito, crea anche quello
	// determina se il recapito esiste gia'
	$row['IdTipoRecapitoGarante'] = 1;
	$IdRecapito = trovaRecapito($row,$IdCliente,"Garante");
	if ($IdRecapito===null) { // il recapito deve essere creato
		$IdRecapito = creaRecapito($row,$IdCliente,"Garante");   // crea un recapito per il garante/coobbligato
	} else if ($IdRecapito==-1) { // il recapito e' ambiguo
		$Nome = getScalar("SELECT IFNULL(Nominativo,RagioneSociale) FROM cliente WHERE IdCliente=$IdCliente");
		writeProcessLog($processName, "Non e' possibile determinare in modo univoco qual e' il recapito da aggiornare"
				." per il soggetto '$Nome' (riga n. ".($numRows+1),1);
	} else if ($IdRecapito>0) { // il recapito esiste gia': aggiorna i campi
		aggiornaRecapito($row,$IdRecapito,"Garante");
	}

	return 1;
}

/**
 * creaMovimento Crea una o piu' righe riga nella tabella movimento a partire da una riga di temp_import_posizione
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row riga letta da temp_import_movimento
 * @param {Number} $nIns (by ref) aggiorna il numero di righe inserite
 * @param {Number} $nUpd (by ref) aggiorna il numero di righe aggiornate
 * @return {Boolean} false se nessuna riga toccata
 */
function creaMovimento($IdContratto,$row,&$nIns,&$nUpd) {
	global $trasf,$idLotto,$tipoOperazione;
	
	extract($row);
	$capitale 	= (isset($ImpCapitaleCredito)?$ImpCapitaleCredito:0) - (isset($ImpCapitaleDebito)?$ImpCapitaleDebito:0);
	$interessi 	= (isset($ImpInteressiCredito)?$ImpInteressiCredito:0) - (isset($ImpInteressiDebito)?$ImpInteressiDebito:0);
	$spese 		= (isset($ImpSpeseCredito)?$ImpSpeseCredito:0) - (isset($ImpSpeseDebito)?$ImpSpeseDebito:0);
	if ($capitale==0 and $interessi==0 and $spese==0) {
		return false;
	}
	// Registrazione movimento su capitale
	if ($capitale!=0) {
		if (!$IdTipoMovimento) // se tipo causale non specificato, mette i default per i mov. di capitale
			$row['IdTipoMovimento'] = $IdTipoMovimento = $capitale>0 ? 2:13; // incasso e addebito capitale
		$row['Importo'] = -$capitale; // negativo = a credito, invece in input suppone che siano messi positivi nel giusto campo 
		// Determina se esiste già lo stesso movimento
		if ($tipoOperazione=='u') { // richiesto caricamento di tipo aggiornamento
			$old = getRow("SELECT * FROM movimento WHERE IdContratto=$IdContratto AND importo=-$capitale AND IdTipoMovimento=$IdTipoMovimento AND DataRegistrazione='$DataRegistrazione'");
			if ($old) {
				// UPDATE
				if (aggiornaMovimento($old,$row))
					$nUpd++;
			} else { // INSERT
				inserisceMovimento($IdContratto,$row);
				$nIns++;
			}
		} else {
			inserisceMovimento($IdContratto,$row);
			$nIns++;
		}
	}
	// Registrazione movimento su interessi
	if ($interessi!=0) {
		if (!$IdTipoMovimento) // se tipo causale non specificato, mette i default per i mov. interessi
			$row['IdTipoMovimento'] = $IdTipoMovimento = $interessi>0 ? 4:3; // incasso e addebito interessi
		$row['Importo'] = -$interessi; // negativo = a credito, invece in input suppone che siano messi positivi nel giusto campo
		// Determina se esiste già lo stesso movimento
		if ($tipoOperazione=='u') { // richiesto caricamento di tipo aggiornamento
			$old = getRow("SELECT * FROM movimento WHERE IdContratto=$IdContratto AND importo=-$interessi AND IdTipoMovimento=$IdTipoMovimento AND DataRegistrazione='$DataRegistrazione'");
			if ($old) {
				// UPDATE
				if (aggiornaMovimento($old,$row))
					$nUpd++;
			} else { // INSERT
				inserisceMovimento($IdContratto,$row);
				$nIns++;
			}
		} else {
			inserisceMovimento($IdContratto,$row);
			$nIns++;
		}
	}
	// Registrazione movimento su spese
	if ($spese!=0) {
		if (!$IdTipoMovimento) // se tipo causale non specificato, mette i default per i mov. spese
			$row['IdTipoMovimento'] = $IdTipoMovimento = $spese>0 ? 8:7; // incasso e addebito spese
		$row['Importo'] = -$interessi; // negativo = a credito, invece in input suppone che siano messi positivi nel giusto campo
		// Determina se esiste già lo stesso movimento
		if ($tipoOperazione=='u') { // richiesto caricamento di tipo aggiornamento
			$old = getRow("SELECT * FROM movimento WHERE IdContratto=$IdContratto AND importo=-$spese AND IdTipoMovimento=$IdTipoMovimento AND DataRegistrazione='$DataRegistrazione'");
			if ($old) {
				// UPDATE
				if (aggiornaMovimento($old,$row))
					$nUpd++;
			} else { // INSERT
				inserisceMovimento($IdContratto,$row);
				$nIns++;
			}
		} else {
			inserisceMovimento($IdContratto,$row);
			$nIns++;
		}
	}
	return true;
}

/**
 * creaPosizione Crea una o piu' righe riga nella tabella movimento a partire da una riga di temp_import_posizione
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row riga letta da temp_import_posizione
 * @return {Number} numero di righe create
 */
function creaPosizione($IdContratto,$row) {
	global $trasf,$idLotto;

	extract($row);

	// Usa la funzione gia' esistente in funzioniIncassi.php per creare i movimenti di addebito
	$error = creaMovimentoAddebito($IdContratto,$DataDocumentoPos,$NumDocumentoPos,
			$ImpCapitale,$ImpInteressi,$ImpSpeseRecupero,$ImpAltriAddebiti,$ImpSpeseLegali,
			$DataScadenzaPos,$DataRegistrazionePos,$NumRataPos,$insertedIds1,false, // chiede di tornare gli ID e di non aggiornare "insoluto"
			26 // fozra il tipo movimento 26: Debito iniziale
			);
	if ($error>'') erroreProcesso($error); 
	
	// Usa la funzione gia' esistente in funzioniIncassi.php per creare i movimenti di accredito
	if ($ImpPagato!=0) {
		$error = creaMovimentoIncasso($IdContratto,$DataDocumentoPos,$NumDocumentoPos,
				$ImpPagato,0,0,0,0,
				$DataScadenzaPos,$DataRegistrazionePos,$NumRataPos,$insertedIds2,false // chiede di tornare gli ID e di non aggiornare "insoluto"
		);
	}

	// registra l'istruzione per lo storno
	$nIns = 0;
	if ($insertedIds1>'' or $insertedIds2>'') {
		if ($insertedIds1>'') {
			$nIns += substr_count($insertedIds1,",")+1;
			registraStorno("DELETE FROM movimento WHERE IdContratto=$IdContratto AND IdMovimento IN ($insertedIds1)");
		}
		if ($insertedIds2>'') {
			$nIns += substr_count($insertedIds2,",")+1;
			registraStorno("DELETE FROM movimento WHERE IdContratto=$IdContratto AND IdMovimento IN ($insertedIds2)");
		}
		return $nIns;
	} else {
		return false;
	}
}

/**
 * creaStoriaRecupero Crea una riga in StoriaRecupero, a seguito della lettura di una riga da temp_import_storiarecupero
 * @param {Number} $IdContratto ID del contratto
 * @param {Array} $row riga letta da temp_import_storiarecupero
 * @param {Number} $nIns (by ref) aggiorna il numero di righe inserite
 * @param {Number} $nUpd (by ref) aggiorna il numero di righe aggiornate
 * @return {Boolean} false se nessuna riga toccata
 */
function creaStoriaRecupero($IdContratto,$row,&$nIns,&$nUpd) {
	global $trasf,$idLotto,$tipoOperazione;

	extract($row);

	// Determina se esiste già la stessa registrazione
	if ($tipoOperazione=='u') { // richiesto caricamento di tipo aggiornamento
		$old = getRow("SELECT * FROM storiarecupero WHERE IdContratto=$IdContratto AND DataEvento='$DataEvento' AND DescrEvento=".quote_smart($DescrEvento));
		if ($old) {
			// UPDATE
			if (aggiornaStoriaRecupero($old,$row))
				$nUpd++;
		} else { // INSERT
			inserisceStoriaRecupero($IdContratto,$row);
			$nIns++;
		}
	} else {
		inserisceStoriaRecupero($IdContratto,$row);
		$nIns++;
	}
	return true;
}

/**
 * registraStorno registra una istruzione SQL per lo storno
 */
function registraStorno($sql) {
	global $idLotto;
	
	if (!execute($sql="INSERT INTO stornolotto (IdLotto,SQLstmt) VALUES($idLotto,".quote_smart($sql).")")) {
		erroreProcesso(getLastError()." SQL: $sql");		
	}
}

/**
 * esegueAssegnazioni Crea le assegnazioni a reparti e utenti, in base alle regole associate al prodotto
 */
function esegueAssegnazioni() {
	global $idLotto,$processName;
	
	writeProcessLog($processName,"Esegue le assegnazioni automatiche a reparti e operatori (se previste)",0);
	// ottiene l'elenco degli IdContratto interessati in questo caricamento
	$ids = getColumn("SELECT DISTINCT IdContratto FROM temp_import_contratto WHERE IdLotto=$idLotto AND IdContratto IS NOT NULL");
	writeProcessLog($processName,"Individuate ".count($ids)." pratiche da assegnare",0);
	
	$cnt = 0;
	foreach ($ids as $IdContratto) { // loop per ogni contratto interessato nel lotto
		$con = getRow($sql="SELECT CodContratto,IdAgenzia,IdTeam,IdOperatore FROM contratto WHERE IdContratto=$IdContratto");
		if (getLastError()>'') erroreProcesso(getLastError()." SQL: $sql");
		extract($con);
		if ($IdAgenzia>0 or $IdTeam>0 or $IdOperatore>0) continue; // pratica gia' assegnata, non alterare
		
		$IdReparto = assegnaReparto($IdContratto,$idLotto);
		if ($IdReparto===false) {
			erroreProcesso("Fallita assegnazione della pratica $CodContratto ad un reparto");
			break;
		}

		$IdOperatore = assign($IdContratto); // in engineFunc.php (assegnazione ad operatore)
		if ($IdOperatore===false) {
			erroreProcesso("Fallita assegnazione della pratica $CodContratto ad un operatore");
			break;
		}
		
		$cnt += ($IdReparto>0 or $IdOperatore>0);
		
	}
	writeProcessLog($processName,"Assegnate $cnt pratiche",0);
}

/**
 * assegnaReparto funzione derivata da "delegate" (in engineFunc.php) semplificando le logiche in modo che siano
 *     applicabili all'assegnazione a reparto interno fatta nel processo di importazione dei lotti
 * @param {Number} $IdContratto Id del contratto da assegnare
 * @param {Number} $IdLotto Id del lotto (va a marcare le righe di "assegnazione"
 * @return {Number} false se qualcosa va male
 *                  0 se tutto ok ma non c'� alcuna regola di assegnazione applicabile
 *                  >0 se assegnato (IdReparto)
 */
function assegnaReparto($IdContratto,$IdLotto)
{
	try
	{
		$subtrace = FALSE; // mettere a true per traccia dettagliata
		trace("assegnaReparto Contratto=$IdContratto",FALSE);
		//----------------------------------------------------------
		// Se fuori recupero forzato, non affida
		//----------------------------------------------------------
		if (fuoriRecupero($IdContratto))
			return 0;
			
		//----------------------------------------------------------
		// Se in lavorazione interna o in workflow, non assegna
		//----------------------------------------------------------
		if (lavorazioneInterna($IdContratto))
			return 0;
			
		//----------------------------------------------------------
		// Trattamento standard, verifica le condizioni di ciascuna
		// assegnazione applicabile e mette in un'array gli Id
		// delle agenzie che soddisfano il criterio
		//----------------------------------------------------------
		$pratica = getRow("SELECT * FROM v_pratica_noopt WHERE IdContratto=$IdContratto");
		if (!is_array($pratica)) {
			Throw new Exception("Fallito affidamento della pratica n. $IdContratto");
		}

		// Seleziona tutte le regole di assegnazione che riguardano i reparti interni
		$arrayIds = Array();
		$forceCond = FALSE; // TRUE quando c'e' una condizione (cioe' non considera buona l'entry
							// con Condizione=NULL se ce n'e' una con Condizione non NULL)
		$preferred = FALSE; // TRUE quando una regola ha tipoDistribuzione=P (prioritaria)

		$regole = getFetchArray("SELECT * FROM regolaassegnazione WHERE TipoAssegnazione='2'"
								." AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY Ordine,Condizione DESC,TipoDistribuzione DESC");

		foreach ($regole as $regola) {
			if ($subtrace) trace("IdRegola=".$regola["IdRegolaAssegnazione"],FALSE);
			if ($regola["IdTipoCliente"]) // condizione sul tipo di cliente
				if ($regola["IdTipoCliente"]!=$pratica["IdTipoCliente"]) {
					if ($subtrace) trace("Scartata per check su IdTipoCliente",FALSE);
						continue;
				}
			if ($regola["IdFamiglia"]) // condizione sulla famiglia di prodotto
				if ($regola["IdFamiglia"]!=$pratica["IdFamiglia"]
				&&  $regola["IdFamiglia"]!=$pratica["IdFamigliaParent"]){
					if ($subtrace) trace("Scartata per check su IdFamiglia",FALSE);
						continue;
					}
			if ($regola["IdClasse"]) // condizione sulla classificazione
				if ($regola["IdClasse"]!=$pratica["IdClasse"]) {
					if ($subtrace) trace("Scartata per check su IdClasse",FALSE);
						continue;
				}
			if ($regola["IdArea"]) // condizione sull'area di recupero
				if ($regola["IdArea"]!=$pratica["IdAreaCliente"]) {
					if ($subtrace) trace("Scartata per check su IdAreaCliente",FALSE);
						continue;
				}
			if ($regola["ImportoDa"]>0) // condizione sull'importo minimo
				if ($regola["ImportoDa"]>$pratica["Importo"]){
					if ($subtrace) trace("Scartata per check su ImportoDa",FALSE);
						continue;
				}
			if ($regola["ImportoA"]>0) // condizione sull'importo massimo
				if ($regola["ImportoA"]<$pratica["Importo"]){
					if ($subtrace) trace("Scartata per check su ImportoA",FALSE);
						continue;
				}
				
			if ($regola["Condizione"]>'') { // condizione speciale
				$bool = getScalar("SELECT 1 FROM v_cond_affidamento c WHERE IdContratto=$IdContratto AND ".$regola["Condizione"]);
				if ($bool==1) {
					$forceCond = TRUE; // non accetta piu' le entry con Condizione NULL
					trace("Verificata condizione affido: ".$regola["Condizione"],FALSE);
				} else {
					if ($subtrace) trace("Scartata per check su Condizione",FALSE);
					continue;
				}
			} else  { // Condizione NULL
				if ($forceCond) {
					if ($subtrace) trace("Scartata perche' non soddisfa condizione su regola precedente",FALSE);
					continue;
				}
				$durata = $regola["DurataAssegnazione"];
			}

			$IdReparto =  $regola["IdReparto"]; // reparto interno selezionato
			if ($regola["IdArea"]>0) // soddisfatta condizione sull'area di recupero
				trace("Area id=".$regola["IdArea"]." assegnata al reparto $IdReparto",FALSE);
					
			// Gestisce le regole con tipoDistribuzione=P (assegnazione prevalente su quelle senza P)
			if ($regola["TipoDistribuzione"]=="P") {
				$preferred = TRUE;
				$arrayIds = Array(); // toglie le altre regole trovate
			} else if ($preferred==TRUE) // regola senza P, ma e' stata incontrata una P: ha la precedenza
				continue;
					
			// Mette in un array gli ID e tipoDistribuzione dei reparti individuati
			if (!array_key_exists($IdReparto,$arrayIds)) {
				$arrayIds[$IdReparto] = $regola["TipoDistribuzione"].";".$regola["IdRegolaProvvigione"];
			}
		} // fine loop sulle regole assegnazione

		//----------------------------------------------------------------------------------
		// Se una delle agenzie selezionate ha tipoDistribuzione=P, prevale sulle altre
		//----------------------------------------------------------------------------------
		foreach ($arrayIds as $key=>$value)
		{
			$values = split(";",$value); // separa tipo distribuzione da giorni fissi fine
			if ($values[0]=="P")
			{
				unset($arrayIds); // toglie tutto
				$arrayIds[$key] = $value; // rimette solo questo elemento
				break;
			}
		}

		//----------------------------------------------------------------------------------
		// Se arrayIds non e' vuoto, distribuisce la pratica al reparto che ne ha di meno
		// (in totale, se TipoDistribuzione='C', oppure nel lotto se TipoDistribuzione='I')
		//----------------------------------------------------------------------------------
		if (count($arrayIds)>0)
		{
			trace("Affidi possibili alle agenzie: ".join(", ",array_keys($arrayIds)),FALSE);
			// Individua l'agenzia+provvigione con meno pratiche assegnate
			$minimo = 9999999;
			$IdReparto = 0; // alla peggio sceglie il primo della lista
			foreach ($arrayIds as $key=>$value)
			{ 
				$value = split(";",$value); // separa tipo distribuzione da giorni fissi fine
				$tipo = $value[0];
				$IdRegolaProvvigioneTemp = $value[1]; // se la regola assegnazione determina una specifica regola provv.

				if (!($IdRegolaProvvigioneTemp>0))
				{
					// data riferimento (fine lotto standard)
					$data = mktime(0,0,0,date("n")+1,date("j")-1,date("Y"));
					$IdRegolaProvvigioneTemp = trovaProvvigioneApplicabile($IdContratto,$key,$CodProvv,$data,$durataProvv);
					if ($IdRegolaProvvigioneTemp>0) // regola trovata
					{
						// Calcola quanti contratti sono gia' affidati a questo cod. provvigione e lotto
						$numAssegnate = getScalar("SELECT COUNT(*) FROM contratto c"
							." WHERE IdRegolaProvvigione=0".$IdRegolaProvvigioneTemp
							. ($tipo=='I'?" AND DataFineAffido='".ISODate($data)."'":""));
						trace("Reparto $key NumAssegnate=$numAssegnate",FALSE);
						if ($numAssegnate<$minimo)
						{
							$IdReparto = $key;
							$minimo = $numAssegnate;
							$IdRegolaProvvigione = $IdRegolaProvvigioneTemp;
						}
					} else if ($IdReparto==0) { // se non ancora trovato, alla peggio sceglie il primo
						$IdReparto = $key;
					}
				}
			} // fine foreach per scelta tra reparti equivalenti
			
			// Adesso $IdReparto contiene il valore cercato, a meno che qualcosa manchi (da regolaProvvigione)
			if ($IdReparto>0)
			{
				// Utilizza la funzione affidaAgenzia (adattata) per assegnare al reparto come fosse un'agenzia
				$r = getRow($sql="SELECT l.DataIni,l.DataFin,IdRegolaProvvigione from lotto l join prodotto p on p.IdProdotto=l.IdProdotto WHERE IdLotto=$IdLotto");
				if (getLastError()) erroreProcesso(getLastError()." SQL: $sql");
				extract($r);
				
				if (!affidaAgenzia($IdContratto,$IdReparto,$DataFin,true,$DataIni,$IdRegolaProvvigione,$IdLotto)) {
					erroreProcesso("Fallita assegnazione al reparto");
				}
				return $IdReparto;
			}
		} // fine if almeno un reparto candidato
	
		// Non assegnabile ad alcuna agenzia: la revoca in automatico
		trace ("affido per contratto $IdContratto: nessuna regola di assegnazione applicabile",FALSE);
		return 0; // 0 indica OK ma non ho affidato
	}
	catch (Exception $e) {
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		return FALSE;
	}
}