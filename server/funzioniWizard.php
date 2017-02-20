<?php
require_once('processInsoluti.php');
require_once("workflowFunc.php");
require_once("userFunc.php");
require_once('riempimentoOptInsoluti.php');
require_once("PHPExcel.php");

set_time_limit(1000); // aumenta il tempo max di cpu
ini_set('max_execution_time','600');

define("NUM_MIN_COLONNE_HEADER",4); 	// numero di colonne iniziali piene per riconoscere una riga come testata di tabella Excel 
define("NUM_MIN_COLONNE_RIGA_VUOTA",5); // numero di colonne iniziali vuote che determinano la fine della tabella Excel
define("NUM_RIGHE_RICERCA_HEADER",10);	// numero di righe iniziali in cui si cerca la testata di tabella Excel
define("MAX_CSV_LINE_LENGTH",10000);    // lunghezza massima prevista per le righe dei file  CSV


doWizard();

function doWizard(){
	
	global $context;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch($task)
	{
		case "analizzaFile":
			analizzaFile();
			break;
		case "saveTrasformazione":
			saveTrasformazione();
			break;
		case "interrompiProcesso":
			interrompiProcesso();
			break;
		case "saveNewRecord":
			saveNewRecord();
			break;
		case "stornoLotto";
			if(!stornoLotto($idLotto)){
				fail("Impossibile eseguire lo storno",false);
			}else{
				success("Lotto stornato");
			}	
			break;
		default:
		//	echo "{failure:true, task: '$task', messaggio:'task $task sconosciuto'}";
	}
}
/**
 * analizzaFile Analizza il file caricato nel form Regole di Trasformazione e ritorna le informazioni sulla sua struttura
 */
function analizzaFile() {
	if (!$_FILES or !$_FILES['docPath'] or !$_FILES['docPath']['name']>"") {
		fail("Nessun file caricato",false);
	}

	$tmpName  = $_FILES['docPath']['tmp_name'];
	$fileName = $_FILES['docPath']['name'];
	$fileSize = $_FILES['docPath']['size'];
	$fileType = $_FILES['docPath']['type'];
	
	$fileName=urldecode($fileName);
	if(!get_magic_quotes_gpc())
		$fileName = addslashes($fileName);
	
	$localDir = TMP_PATH."/import";
	if (!file_exists($localDir)) {
		if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
			fail("Impossibile creare la cartella $localDir");
		}
	}

	$filePath = "$localDir/$fileName";
	if (!move_uploaded_file ($tmpName, $filePath))	{
		fail("Impossibile copiare il file nella cartella $localDir");
	} else {
		$ext = pathinfo($filePath,PATHINFO_EXTENSION);
		if ($ext=='xls' or $ext=='xlsx') {
			$info = analizzaFileExcel($filePath,$error);
			if (!$info) fail($error);
			success(array("type" => "Excel",  "info"  =>  $info));
		} else if ($ext=='csv' or $ext=='txt') {
			$info = analizzaFileCsv($filePath,$error);
			if (!$info) fail($error);
			success(array("type" => "CSV",  "info"  =>  $info));
		} else {
			fail("Tipo di file non gestito (ammesse solo le estensioni .csv, .txt, .xls, .xlsx)");
		}			
	}
}

/*
 * analizzaFileCsv � una funzione che analizza il file csv in upload:
 * conta il numero di righe e delle colonne del file, memorizza i nomi dei campi della testata
 * ed il separatore dei campi
 */
function analizzaFileCsv($filePath, &$error){	
	trace("Inizio analisi file di import CSV $filePath",false);
	if(!file_exists($filePath) || !is_readable($filePath)){
		$error = "Il file non esiste oppure non puo' essere letto";
		trace($error,false);
		return false;
	}
	$handle = fopen($filePath, 'r');
	if (!$handle){
		$error = "Il file non puo' essere aperto";
		trace($error,false);
		return false;
	}
	
	// Nota: i file prodotti con separatore di riga = \r (Mac), non vengono letti correttamente nemmeno con la auto_detect_line_endings
	$chunk = fread($handle,MAX_CSV_LINE_LENGTH*3);  
	if (strlen($chunk)<20) {
		$error = "Il file sembra vuoto o quasi vuoto";
		trace($error,false);
		return false;
	} 
	
	// Determina qual � il separatore di riga
	$cnt1 = substr_count($chunk,"\r\n");
	$cnt2 = substr_count($chunk,"\n");
	$cnt3 = substr_count($chunk,"\r");
	trace("cnt $cnt1 $cnt2 $cnt3",false);
	if ($cnt1>=1) {
		$lineSeparator = "\r\n";
		trace('Individuato separatore di linea \\r\\n',false);
	} else if ($cnt2>=1 && $cnt2>=$cnt3) {
		$lineSeparator = "\n";
		trace('Individuato separatore di linea \\n',false);
	} else {
		$lineSeparator = "\r";
		trace('Individuato separatore di linea \\r',false);
	}
	
	$header = explode($lineSeparator,$chunk);
	$header = $header[0];

	// Individua il separatore di colonne
	$separatoreCampi = array(",",";","\t","|");
	$max = 0;
	foreach($separatoreCampi as $sepC){
		$num = substr_count($header, $sepC);
		if($num > $max){
			$max = $num;
			$fieldSeparator = $sepC;
		}
	}
	trace('Individuato separatore di campo '.addslashes($fieldSeparator),false);
	// Ottiene l'array con i nomi di colonna e da' un nome a quelle eventualmente senza header
	$fieldNames = explode($fieldSeparator, $header);
	foreach ($fieldNames as $i=>$colName) {
		if (trim($colName)=='') {
			$fieldNames[$i] = "Colonna ".($i+1);
		}		
	}
	$numberOfColumns = count($fieldNames);
	trace("La riga di testata contiene $numberOfColumns colonne",false);
	fclose($handle);
	
	// Riapre il file per leggerlo tutto, contando le righe ed estraendo un valore di esempio per ogni colonna
	$handle = fopen($filePath, 'r');
	$chunk  = "";
	$numberOfRows = 0;
	$sampleRow = array();
	$residue = $numberOfColumns;
	while (!!($line = fread($handle,MAX_CSV_LINE_LENGTH))) {
		trace("Letti ".strlen($line)." bytes dal file",false);
		$line = $chunk . $line; // concatena al pezzo di riga rimasto dalla fread precedente
		$chunk = '';
		while (strlen($line)>0) { // loop per spezzare le righe contenute nel buffer letto
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
			if ($numberOfRows>0) { // ho superato l'header
				$fields = explode($fieldSeparator,$riga);
				if (count($fields)<=$numberOfColumns) { // riga buona (non contiene separatori di campo nei valori dei campi)
					for ($col = 0; $col<count($fields) && $residue>0; $col++) {
						if (!($sampleRow[$col]>'')) { // valore per questa colonna non ancora trovato
							$text = $fields[$col];
							if ($text>''){
								$sampleRow[$col] = $text;
								$residue--;
							}
						}
					}
				}
			}
			$numberOfRows++;
		} // fine esame di un buffer letto
	}
	
	fclose($handle);
	$numberOfRows--; // detrae l'header dal conteggio
	
	$data = array("filePath" => $filePath,
				  "fileName" => pathinfo($filePath,PATHINFO_FILENAME),
				  "fileType" => "CSV",
				  "lineSeparator" => $lineSeparator,
				  "fieldSeparator" => $fieldSeparator,
                  "numrows" => $numberOfRows,
				  "columns"=> $fieldNames,
				  "sampleRow" => $sampleRow);
	return $data;
}

/* analizzaFileExcel � una funzione che analizza il file excel in upload:
 * conta il numero di fogli presenti nel file
 * il numero di righe e delle colonne del file, memorizza i nomi dei campi della testata
 */

function analizzaFileExcel($filePath,&$error){
	if (!file_exists($filePath)) {
		$error = "Il file $filePath non esiste o non &egrave; accessibile";
		return false;
	}
	if (!is_readable($filePath)) {
		$error = "Il file $filePath non &egrave; leggibile";
		return false;
	}

	$fileType = PHPExcel_IOFactory::identify($filePath);
	if (stripos($fileType,'excel')===FALSE) {
		$error = "Il file $filePath non e' un file Excel elaborabile";
		return false;
	}
	$objReader = PHPExcel_IOFactory::createReader($fileType);
	$objReader->setReadDataOnly(true);
	$objPHPExcel = $objReader->load($filePath);
	$sheets = array(); //array dei fogli excel contenuti nel file
	foreach ($objPHPExcel->getAllSheets() as $sheet) {
		$highestRow = $sheet->getHighestRow();
		$highestColumn = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());// numero massimo di colonne: conversione della stringa posizione in posizione numerica
		$sheetName = $sheet->getTitle();
		$realNumberOfColumns = 0;
		for($row = 1; $row<NUM_RIGHE_RICERCA_HEADER; $row++){
			$headerRow = array();
			for($col = 0; $col<$highestColumn; $col++){
				//memorizziamo il valore della cella
				$colRow = $sheet->getCellByColumnAndRow($col,$row)->getValue();
				if ($colRow>'') { // la cella ha un contenuto
					if(!preg_match('/^[0-9 .,]+$/i',$colRow)){//controllo sulle stringhe dei campi del foglio excel: si assume che i campi siano alfanumerici e non vuoti
						$headerRow[] = trim($colRow);
						$realNumberOfColumns++;
					}else{ // cella con contenuto esclusivamente numerico, non � considerato un header
						break; // passa alla riga successiva
					}
				} else if (count($headerRow)>=NUM_MIN_COLONNE_HEADER) { // La cella � vuota ma l'header � gi� stato accertato
					$headerRow[] = "Colonna ".($col+1); // assegna un nome di default alla colonna e continua
				} else { // cella vuota e header ancora non accertato: passa alla riga successiva
					break;
				}
			}
			if($realNumberOfColumns>=NUM_MIN_COLONNE_HEADER){ // presunto header
				break;
			}
		}

		if($row >= NUM_RIGHE_RICERCA_HEADER) // nessun candidato header trovato, ignora questo Foglio
			break;
		
		array_splice($headerRow,$realNumberOfColumns); // rimuove le colonne finali senza nome
		
		// Conta le righe di dati
		for($rw = $row+1; $rw<=$highestRow;$rw++){
			for($col = 0; $col<NUM_MIN_COLONNE_RIGA_VUOTA; $col++){
				//controllo sulle celle non vuote
				if ($sheet->getCellByColumnAndRow($col,$rw)->getValue()>''){
					break;
				}
			}
			//controllo sulla colonna limite
			if($col == NUM_MIN_COLONNE_RIGA_VUOTA) break; // riga con le prime celle vuote: presumo che la tabella di dati sia finita
		}
		// numero di righe effettivo che popolano la tabella nel file excel
		$numRows = $rw - $row;
		$highestRow = $rw-1;
		
		// in $sampleRow costruisce una riga di esempio, prendendo un valore non nullo per ogni colonna
		$sampleRow = array_fill(0,$realNumberOfColumns,'');
		$residue = $realNumberOfColumns;
		for($rw = $row+1; $rw<=$highestRow && $residue>0;$rw++){
			for($col = 0; $col<$realNumberOfColumns && $residue>0; $col++){
				if (!($sampleRow[$col]>'')) { // valore per questa colonna non ancora trovato
					$text = $sheet->getCellByColumnAndRow($col,$rw)->getValue();
					if ($text>''){
						$sampleRow[$col] = $text;
						$residue--;
					}
				}
			}
		}
		// Trasforma $sampleRow in un vero array con indice numerico (essendo stato riempito con indici disordinati, fino a qui � un
		// array con chiavi numeriche ma disordinate
		ksort($sampleRow);
		$sampleRow = array_values($sampleRow);
		
		$sheets[] = array (
				"filePath" => $filePath,
				"fileName" => pathinfo($filePath,PATHINFO_FILENAME),
				"fileType" => "Excel",
				"headerRow"=>$row,
				"sheetName"=>$sheetName,
				"numrows"  =>$numRows,
				"columns"  =>$headerRow,
				"sampleRow"=>$sampleRow
		);
	}
	if (count($sheets)==0) { 
		$error = "Il file $filePath sembra contenere alcun foglio contenente dati riconoscibili";
		return false;
	}
	return $sheets;
}

/*
 * saveTrasformazione 
 * Prepara la struttura trasformazione, che descrive l'intera regola di trasformazione e la salva come stringa JSON
 * nel campo Trasformazione del "moduloimport" di appertenenza
 * Le colonne della trasformazione vengono composte a partire dal risultato dell'analisi del file, a meno che
 * non si tratti di un update di una trasformazione gi� creata (tasto Save su trasformazione gi� salvata o su pagina
 * tab_ColonneTrasformazione
 */

function saveTrasformazione(){
	extract($_REQUEST);
	
	// Legge le definizioni proprie del wizard
	$config = file_get_contents('../js/wizard_config.json'); 
	if (!$config) {
		fail("Impossibile leggere il file di configurazione necessario (file js/wizard_columns.json)",false);	
	}
	$config = json_decode($config,true);
	
	// Legge le info di analisi del file e costruisce lo store "colonne" che serve alla pagina tab_ColonneTrasformazione
	$infoFile = json_decode(utf8_encode($risultatoAnalisi),true);
	
	if (!$colonne or $colonne=='null') { // non sono state passate le colonne della trasformazione in input (prima volta dopo analisi file)
		$colonne = array();
		// Loop sulle colonne individuate nell'header del file di esempio 
		$usate = array();
		foreach ($infoFile["columns"] as $i=>$columnName) {
			$colDB = '';
			// Cerca la prima colonna target plausibile per il dato nome di colonna
			foreach ($config['columns'] as $coldef) {
				if (preg_match($coldef['match_re'],$columnName)   // il nome combacia con il pattern
				and (($coldef['table']=='cliente' and $infoCliente=='on') // non generare se la tabella non � inclusa (questo esculde anche i codice cli/contratto)
				or   ($coldef['table']=='garante' and $infoGarante=='on') 
				or   ($coldef['table']=='recapito' and $infoRecapito=='on')
				or   ($coldef['table']=='contratto' and $infoContratto=='on')
				or   ($coldef['table']=='posizione' and $infoRata=='on')
				or   ($coldef['table']=='movimento' and $infoMovimento=='on')
				or   ($coldef['table']=='storiarecupero' and $infoStoriaRec=='on')
				)
				and !in_array($coldef['name'],$usate)) {          // e non l'ho gia' usato
					$colDB 		= $coldef['name']; 				  // indica questa colonna come candidata target
					$usate[] 	= $colDB;
					break;
				}
			}
			
			$colonne[] = array(
				"IdColonna" 	=> $i+1,
				"colFileInput" 	=> $columnName,
				"esempioValoreCol" => $infoFile["sampleRow"][$i],
				"colDB" 		=> $colDB,
				"colonnaInput"  => true // indica che e' una colonna del file di input, non una colonna aggiunta
			);	
		}
	} else { // il chiamante ha passato le colonne
		trace("Colonne= $colonne",false);
		$colonne = json_decode(utf8_encode($colonne),true);
		//trace("decoded=".print_r($colonne,true),false);
	}
		
	// Prepara la struttura finale
	$t = array(
			"infoCliente" 	=> ($infoCliente=='on'),
			"infoGarante" 	=> ($infoGarante=='on'),
			"infoRecapito" 	=> ($infoRecapito=='on'),
			"infoContratto" => ($infoContratto=='on'),
			"infoRata" 		=> ($infoRata=='on'),
	    	"infoMovimento" => ($infoMovimento=='on'),
			"infoStoriaRec" => ($infoStoriaRec=='on'),
			"opzUnisciClienti" => ($opzUnisciClienti=='on'),
			"opzCreaCodCliente" => ($opzCreaCodCliente=='on'),
			"opzCreaCodGarante" => ($opzCreaCodGarante=='on'),
			"opzCreaCodContratto" => ($opzCreaCodContratto=='on'),
			"opzImportiInCentesimi" => ($opzImportiInCentesimi=='on'),
			"infoFile"		=> $infoFile,
			"colonne"       => $colonne,
			"combo"         => $config['columns']
	);
	
	// Salva su DB
	$sql = "UPDATE moduloimport SET trasformazione=".quote_smart(json_encode_plus($t))." WHERE IdModulo=$idmodulo";
	if (!execute($sql)) {
		fail(getLastError());
	} else {
		success($t);
	}
}

/**
 * interrompiProcesso
 * Interrompe un processo di acquisizione, scrivendo una riga con LogLevel -2 sulla tabella processlog
 * @param {String} $processName nome del processo 
 */
function interrompiProcesso() {
	writeProcessLog($_REQUEST['processName'],'Richiesta interruzione del processo',-2);
	success("");
}

/*
 * saveNewRecord
 * Processo di salvataggio di un nuovo record da parte dell'utente
 * Scrive una riga nella griglia Trasformazione Dati durante il processo
 * di importazione dei dati sul database
 * @param {String} $colFileInput  nome dell'espressione di input
 */

function saveNewRecord(){
	global $context;
	
	extract($_REQUEST);
	getScalar("SELECT $colFileInput FROM v_import_check_expr LIMIT 1", true);
	if(getLastError()>''){
		die('{success:false, messaggio:"Espressione non valida ('.getLastError().')"}');
	}
	success("");
}

/*
 * stornoLotto
 * Elimina un lotto eseguendo all'indietro tutte le istruzioni di storno registrate per il lotto dato nella tabella stornolotto
 * Al termine elimina le righe utilizzate di stornolotto
 * @param {Number} $idLotto id del lotto da stornare
 * @return {Boolean} false se si e' verificato un errore
 */

function stornoLotto($idLotto){

	if (!$idLotto) $idLotto = $_REQUEST['idLotto'];
	beginTrans();
	// Cancella provvigioni e assegnazioni, create da programmi che non effettuano la creazione delle righe di storno
	if (!execute($sql="DELETE FROM notautente WHERE IdNota IN (SELECT IdNota FROM nota WHERE IdContratto IN (SELECT IdContratto FROM temp_import_contratto WHERE IdLotto=$idLotto))")) {
		if (function_exists('erroreProcesso'))
			erroreProcesso(getLastError()." SQL: $sql");
		else
			return false;
	}
	
	if (!execute($sql="DELETE FROM modificaprovvigione WHERE IdProvvigione IN (SELECT IdProvvigione FROM provvigione WHERE IdLotto=$idLotto)")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	if (!execute($sql="DELETE FROM dettaglioprovvigione WHERE IdProvvigione IN (SELECT IdProvvigione FROM provvigione WHERE IdLotto=$idLotto)")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	if (!execute($sql="DELETE FROM storiainsoluto WHERE IdAffidamento IN (SELECT IdAssegnazione FROM assegnazione WHERE IdLotto=$idLotto "
			         ." OR IdProvvigione IN (SELECT IdProvvigione FROM provvigione WHERE IdLotto=$idLotto))")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	if (!execute($sql="DELETE FROM insoluto WHERE IdAffidamento IN (SELECT IdAssegnazione FROM assegnazione WHERE IdLotto=$idLotto "
			         ." OR IdProvvigione IN (SELECT IdProvvigione FROM provvigione WHERE IdLotto=$idLotto))")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	if (!execute($sql="DELETE FROM assegnazione WHERE IdLotto=$idLotto OR IdProvvigione IN (SELECT IdProvvigione FROM provvigione WHERE IdLotto=$idLotto)")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;	
	}
	if (!execute($sql="DELETE FROM provvigione WHERE IdLotto=$idLotto")) {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	
	// Conserva gli ID dei contratti del lotto, prima di stornare tutti i riferimenti
	$ids = getColumn($sql="SELECT DISTINCT IdContratto FROM temp_import_contratto WHERE IdLotto=$idLotto AND IdContratto IS NOT NULL");
	if (getLastError()>'') {
		if (function_exists('erroreProcesso')) 
			erroreProcesso(getLastError()." SQL: $sql");
		else 
			return false;
	}
	
	$count = getScalar("SELECT count(*) FROM stornolotto WHERE IdLotto=$idLotto");
	for($from = 0; $from<$count; $from+=100){
		$rows = getColumn("SELECT SQLstmt FROM stornolotto WHERE IdLotto=$idLotto ORDER BY IdStornoLotto DESC LIMIT $from, 100");
		foreach($rows as $row){
			if (!execute($row)) return false;
		}
	}
	commit();
	// Riaggiorna la situazione contabile di tutti i contratti del lotto
	aggiornaSituazioneContabile(null,$idLotto,$ids);
	
	// Storno completato: elimina tutte le righe utilizzate
	if (!execute("DELETE FROM stornolotto WHERE IdLotto=$idLotto"))
		return false;

	return true;
}

/**
 * aggiornaSituazioneContabile aggiorna i campi derivati contabili per un dato lotto
 * @param {Number} $idLotto ID del lotto
 */
function aggiornaSituazioneContabile($processName,$IdLotto,$ids=null) {
	
	if (!$ids) {
		$ids = getColumn($sql="SELECT IdContratto FROM temp_import_contratto WHERE IdLotto=$IdLotto AND IdContratto IS NOT NULL");
		if (getLastError()>'') erroreProcesso(getLastError()." SQL: $sql");
	}
	if ($processName) writeProcessLog($processName, "Inizio aggiornamento situazioni contabili per ".count($ids)." pratiche", 0);
	foreach ($ids as $index=>$id) {
		if ($index && $index%1000==0) {
			if ($processName)
				if (!writeProcessLog($processName, "Aggiornate $index pratiche",0))
					return; // se torna false significa che e' stata richiesta una interruzione
		}
		if (is_numeric($id) && $id>0) {
			if (processInsolutiSimple($id)) { // chiama anche aggiornaCampiDerivati
				updateOptInsoluti("IdContratto=$id");
				//aggiornaCampiDerivati($id);
			}
		} else {
			erroreProcesso("Tentativo di elaborare un contratto senza Id (nella funzione aggiornaSituazioneContabile; ids=".implode(',',$ids).")");
		}
	}
	if ($processName) writeProcessLog($processName, "Aggiornate ".count($ids)." pratiche",0);	
}

