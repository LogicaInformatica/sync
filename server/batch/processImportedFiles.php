<?php
define ("PROCESS_NAME", "OCS_IMPORT");

//require_once('commonbatch.php');
require_once('funzioniStorico.php');
require_once('../engineFunc.php');
require_once('../riempimentoOptInsoluti.php');
require_once('../updateSituazioneDebitoria.php');
global $idImportLog;
global $numErrors;
global $dataFile;
global $formeGiuridiche,$sigleProvince,$sigleNazioni;
global $listaClienti,$aree,$tipiRecapito;
global $nfiles;
global $precrimine;
global $flagBloccoAffido,$IdContratto;

global $processName;

$context["process_name"] = PROCESS_NAME; // per uso in altri sorgenti richiamati
//----------------------------------------------------------------------------------------------------------------------
// processImportedFiles
// Scopo: 		Processa, in ordine di arrivo, i files depositati dalla import.php
// NOTA:        viene chiamata da cron oppure manualmente
// Argomenti:	$postprocessing: se TRUE, vengono eseguite le operazioni finali
// Risposta:	messaggi su log e traccia
//----------------------------------------------------------------------------------------------------------------------
function processImportedFiles($postprocessing=TRUE)
{
	global $idImportLog;
	global $numErrors;  // loop sui file contenuti in tmp/import
	global $listaClienti;
	global $dataFile;
	global $precrimine;
	global $movimentiOK;

	global $context;
	
	$context["notify_mode"] = "deferred"; // i messaggi saranno accumulati per l'invio finale 
	
	writeProcessLog(PROCESS_NAME,"Inizio del processo di acquisizione files");
	set_time_limit(0); // aumenta il tempo max di cpu

	// Controllo semaforo
	if (!checkAndSetSemaphore())
	{
		writeProcessLog(PROCESS_NAME,"Job batch non eseguibile perche' il semaforo (riga 1 di ImportLog) segnala job ancora in esecuzione o invio files non completato",3);
		sendDeferMail(); // invia messaggi accumulati
		return;
	}

	writeProcessLog(PROCESS_NAME,"Analisi dei files per determinare quali contratti storicizzati devono essere riportati online");
	if (!recuperoStorico("","","","")) {
		sendDeferMail(); // invia messaggi accumulati
		return;
	}
	
	// 8/7/2014: legge data di riferimento comunicata dal servizio Windows (controllando che sia plausibile)
	$dataFile = getScalar("SELECT ImportTime FROM importlog WHERE IdImportLog=1 AND ImportResult='U' AND ImportTime>CURDATE()-INTERVAL 10 DAY");
	if ($dataFile>0) {
		writeProcessLog(PROCESS_NAME,"Data di riferimento dei dati: ".ISODate($dataFile));
	} else {
		writeProcessLog(PROCESS_NAME,"Data di riferimento dei dati mancante: il processo prosegue determinandola dai movimenti");
	}
	$dir = TMP_PATH."/import";
	$listaClienti = array();
	$nfiles = 0;
	$precrimine = FALSE;
	$movimentiOK=FALSE; // viene messo a TRUE se arrivano files di movimenti

	writeProcessLog(PROCESS_NAME,"Inizio acquisizione files di OCS");
	foreach (scandir($dir) as $item) // legge in ordine alfabetico
	{
		$filepath = "$dir/$item";
		if (is_file($filepath))
		{
			writeProcessLog(PROCESS_NAME,"Inizio elaborazione file $filepath");
			if (processFile($dir,$item))
			{
				rename($filepath,TMP_PATH."/okFiles/$item");
				//unlink($filepath);   // elaborazione OK: cancella file
				changeImportStatus($idImportLog,'P'); // indica come processato
				$nfiles++;
			}
			else // elaborazione KO: sposta nel folder dei file errati
			{
				rename($filepath,TMP_PATH."/errFiles/$item");
				if ($idImportLog>0) { // e' uno dei file attesi?
					changeImportStatus($idImportLog,'P'); // indica come processato
    				writeProcessLog(PROCESS_NAME,"Fine elaborazione forzata per errore su file $filepath",3);
            		sendDeferMail(); // invia messaggi accumulati
        			return;
                } // altrimenti prosegue col prossimo file
			}
			writeProcessLog(PROCESS_NAME,"Fine elaborazione file $filepath");
		}
		controllaStopForzato();
	}

	//----------------------------------------------------------------------
	// Sistema i link tra recapiti e clienti per i casi in cui il contratto
	// � stato spostato su altro codice cliente
	//----------------------------------------------------------------------
	execute("update recapito r join contratto c on c.idcontratto=r.idcontratto and c.idcliente != r.idcliente"
			." set r.idcliente=c.idcliente");
	
			
	//----------------------------------------------------------------------
	// Aggiorna la data di cambiostato nei contratti dei dipendenti (KG*)
	//----------------------------------------------------------------------
	execute("update contratto c 
	         join insolutodipendente i on c.idcontratto=i.idcontratto
			 set c.datacambiostato=date(i.lastupd)");
	
	//----------------------------------------------------------------------
	// Effettua le operazioni finali, che devono venire dopo l'elaborazione
	// di tutti i movimenti
	//-----------------------------------------------------------------------
	if ($postprocessing && $movimentiOK) // dal 12.1.2012 processa solo se arrivati i movimenti
	{
		$precrimine = FALSE; // reset variabile usata in ProcessAndClassify
		if (!($dataFile>0))
		{
			// 13/5/14: aggiunta sottrazione di 4 ore, per tollerare sforamento del batch di invio notturno
			$dataFile = getScalar("SELECT MAX(ImportTime)-interval 4 hour FROM importlog WHERE IdImportLog>1");
			trace("Nessun file elaborato: per il post-processing imposta la data di riferimento al piu' recente import: ".ISODate($dataFile),FALSE);
		}

		controllaStopForzato();
		
		writeProcessLog(PROCESS_NAME,"Elaborazione pratiche non ricevute ora ma che erano in negativo");
		rielaboraNegativi(); // aggiorna anche $listaClienti
		writeProcessLog(PROCESS_NAME,"Fine elaborazione pratiche non ricevute ora ma che erano in negativo");
		
		// i Rientri devono essere fatti prima dell'affido corrente, per far s� che vengano correttamente giudicati
		// i clienti per la gestione flotte
		writeProcessLog(PROCESS_NAME,"Elaborazione revoche automatiche di fine periodo affidamento");
		revocheAutomatiche($listaClienti);  // revoca senza riaffido
		writeProcessLog(PROCESS_NAME,"Fine elaborazione revoche automatiche di fine periodo affidamento");
		
		// Chiusure delle provvigioni mensili (parzializzazione delle assegnazioni)
		writeProcessLog(PROCESS_NAME,"Elaborazione chiusure mensili STR/LEG");
		chiusureMensili();  		
		writeProcessLog(PROCESS_NAME,"Fine elaborazione chiusure mensili STR/LEG");
		
		writeProcessLog(PROCESS_NAME,"Elaborazione aggiornamento tabella di comodo _opt_insoluti, prima degli affidi");
		updateOptInsoluti("lastUpd>CURDATE() OR idcontratto not in (select idcontratto from _opt_insoluti)"); // aggiorna l'ottimizzazione
		writeProcessLog(PROCESS_NAME,"Fine elaborazione aggiornamento tabella di comodo _opt_insoluti, prima degli affidi");
		
		// Finalmente effettua gli affidi (con gestione flotte)
		writeProcessLog(PROCESS_NAME,"Elaborazione delle pratiche in attesa di affido per eventuale avvio affidamento");
		if (!affidaTutti($listaClienti)) {
			sendDeferMail(); // invia messaggi accumulati
			return;
		}
		writeProcessLog(PROCESS_NAME,"Fine elaborazione delle pratiche in attesa di affido per eventuale avvio affidamento");
			
		//	trace("Elaborazione affidi rimasti in attesa",FALSE);
		//  obsoleto: incluso nella affidaTutti
		//	affidaInAttesa();      // affido di quelle rimaste in attesa
			
		$contratti = fetchValuesArray("SELECT IdContratto FROM contratto WHERE idoperatore is null and idclasse>1");
		$cnt = count($contratti);
		if ($cnt) {
			writeProcessLog(PROCESS_NAME,"Elaborazione regole di assegnazione operatori mancanti su $cnt contratti a recupero");
			foreach ($contratti as $IdContratto)
			{
				if (assign($IdContratto)===FALSE) // l'errore non � detto sia cos� grave
				{
					//writeResult($idImportLog,"K",getLastError());
					trace("assign fallita idContratto=$IdContratto");
				}
			}
			writeProcessLog(PROCESS_NAME,"Fine elaborazione regole di assegnazione operatori mancanti su $cnt contratti a recupero");
		}
		
		// Query corretta il 20/3/2014 per ridurre l'impegno. Adesso individua tutti i contratti in cui impInsoluto
		// non � (come dovrebbe) uguale alla somma di capitale, spese, interessi e altri addebiti
		$contratti = fetchValuesArray("SELECT IdContratto FROM contratto c"
		                             ." LEFT JOIN regolaripartizione rr ON c.IdRegolaProvvigione=rr.IdRegolaProvvigione"
									 ." WHERE impinsoluto!=impcapitale+impaltriaddebiti+impspeserecupero"
									 ."+IF(flagInteressimora='Y',impinteressimora+impinteressimoraaddebitati,0)");
//		$contratti = fetchValuesArray("SELECT IdContratto FROM contratto c WHERE impinsoluto!=impcapitale+impinteressimora+impaltriaddebiti+impspeserecupero+impinteressimoraaddebitati");
		writeProcessLog(PROCESS_NAME,"Elaborazione campi derivati in ".count($contratti)." contratti in cui non � stato ancora fatto");
		// NOTA BENE: il criterio di cui sopra non tiene conto del fatto che gli interessi di mora e le spese sono
		// addebitati (quindi contribuiscono a impinsoluto) solo se lo stato di affido lo prevede, quindi la query
		// produce molti falsi positivi, perdendo un po' di tempo inutile, ma non fa danni
		//$contratti = fetchValuesArray("SELECT IdContratto FROM contratto c WHERE LastUpd>CURDATE()-INTERVAL 4 HOUR "
		//." AND NOT EXISTS (SELECT 1 FROM insoluto i WHERE c.IdContratto=i.IdContratto AND LastUpd>CURDATE()-INTERVAL 4 HOUR)");
		
		foreach ($contratti as $IdContratto)
		{
			if (!aggiornaCampiDerivati($IdContratto))
			{
				writeResult($idImportLog,"K",getLastError());
				trace("aggiornaCampiDerivati fallita idContratto=$IdContratto");
			}
		}
		writeProcessLog(PROCESS_NAME,"Fine elaborazione campi derivati in ".count($contratti)." contratti in cui non � stato ancora fatto");

		// Effettua spostamenti di regolaprovvigione per le regole scadute (cosa che serve nei
		// periodi in cui si introducono nuove regole con codici identici alle vecchie)
		// (Aggiorna l'IdRegolaProvvigione sia in contratto che in assegnazione)
		// 8/1/15: rilevato che in questo modo si passano le assegnazioni alla nuova regola, anche per quelle con
		// chiusura mensile, mentre il mese ancora da chiudere dovrebbe restare con la vecchia regola
		// Non ho trovato per� una soluzione diretta. Quindi nel caso
		writeProcessLog(PROCESS_NAME,"Elaborazione spostamenti di regola-provvigione per le regole scadute");
		$sql = "update contratto c
join assegnazione a on c.idcontratto=a.idcontratto and a.datafin=c.datafineaffido
join regolaprovvigione r on c.idregolaprovvigione=r.idregolaprovvigione
join regolaprovvigione rNew on c.codregolaprovvigione=rNew.codregolaprovvigione AND c.datafineaffido between rNew.dataini and rNew.datafin
set c.idregolaprovvigione=rNEw.idregolaprovvigione,a.idregolaprovvigione=rNEw.idregolaprovvigione
where datafineaffido>=CURDATE() and c.datafineaffido not between r.dataini and r.datafin";
		execute($sql); // se va male procede lo stesso
		writeProcessLog(PROCESS_NAME,"Fine elaborazione spostamenti di regola-provvigione per le regole scadute");
		
		writeProcessLog(PROCESS_NAME,"Elaborazione provvigioni");
		aggiornaProvvigioni(true);
		writeProcessLog(PROCESS_NAME,"Fine elaborazione provvigioni");
		
		trace("Elaborazione aggiornamento tabella di comodo ListaGaranti",FALSE);
		execute("truncate table listagaranti");
		execute("INSERT INTO listagaranti SELECT * FROM v_lista_garanti");
		trace("Fine elaborazione aggiornamento tabella di comodo ListaGaranti",FALSE);

		trace("Elaborazione aggiornamento tabella di comodo _opt_insoluti",FALSE);
		updateOptInsoluti("lastUpd>CURDATE() OR idcontratto not in (select idcontratto from _opt_insoluti)"); // aggiorna l'ottimizzazione
		trace("Fine elaborazione aggiornamento tabella di comodo _opt_insoluti",FALSE);
		
		trace("Elaborazione tabella della situazione debitoria totale",FALSE);
		updateSituazioneDebitoria($dataFile);
		trace("Fine elaborazione tabella della situazione debitoria totale",FALSE);
		
		// Traccia con invio mail all'amministratore
		$nclassi = getScalar("SELECT COUNT(*) FROM contratto WHERE DataCambioClasse=CURDATE()");
		$naffidi = getScalar("SELECT COUNT(*) FROM contratto WHERE DataInizioAffido>=CURDATE()");
		$nmsg = getScalar("SELECT COUNT(*) FROM importmessage WHERE curdate()=date(lastupd)");
		writeProcessLog(PROCESS_NAME,"Fine acquisizione dati ($nmsg messaggi); processati $nfiles files; classificate $nclassi pratiche; affidate $naffidi pratiche.",4);
	}
	else
	{
		$nmsg = getScalar("SELECT COUNT(*) FROM importmessage WHERE curdate()=date(lastupd)");
		writeProcessLog(PROCESS_NAME,"Fine acquisizione dati ($nmsg messaggi); processati $nfiles files; post-elaborazione omessa.",4);
	}

	sendDeferMail(); // invia messaggi accumulati
	
	// Chiude qui il file corrente di traccia
	cleanTrace();

	// reset semaforo
	resetSemaphore();
}

//=======================================================================================
// processFile
// Scopo: processa uno dei file JSON ricevuti
// Argomenti:
//	1) $dir			path della cartella
//  2) $filename 	filename
// Ritorna:
//		FALSE		file rigettato (esito=K)
//=======================================================================================
function processFile($dir,$filename)
{
	global $idImportLog,$dataFile,$formeGiuridiche,$sigleProvince,$sigleNazioni,$aree,$tipiRecapito;
	global $precrimine,$flagBloccoAffido,$IdContratto;
	global $movimentiOK;

	try
	{
		$precrimine = FALSE;

		// Separa tipo e id del file da processare (il nome file e' Company_idfile_tipofile)
		$parti = explode("_",$filename);
        if (count($parts)<3) {
  			writeProcessLog(PROCESS_NAME,"File $filename non identificato",0);
			return FALSE;
        }
		$type  = $parti[2];
		$from  = $parti[0];
		$id    = $parti[1];

		// Traccia inizio processo
		trace("Inizio elaborazione file di import $type con id=$id",FALSE); // traccia per debug

		// Ottiene la chiave della riga corrispondente nell'importLog
		if ($dataFile>0)   // se gi� impostato (nuova gestione, da importlog con id=1
			$idImportLog = getImportLogId($from,$type,$id);
		else {
			$idImportLog = getImportLogId($from,$type,$id,$dataFile); // legge data di import
			// Corregge la data di riferimento
			$dataFile = dateFromString($dataFile);
			if (date('H',$dataFile)<getSysParm("ORA_FINE_GIORNO","20")) // deve considerare anche il giorno di import oppure
				// solo fino al giorno precedente?
				$dataFile = mktime(0,0,0,date('n',$dataFile),date('j',$dataFile)-1,date('Y',$dataFile));
			else
				$dataFile = mktime(0,0,0,date('n',$dataFile),date('j',$dataFile),date('Y',$dataFile));
			trace ("Usa come data di riferimento: ".date('Y-m-d',$dataFile),FALSE);
		}	
		if ($idImportLog==0)
		{
			writeProcessLog(PROCESS_NAME,"File $filename non identificato nella tabella ImportLog",2);
			return FALSE;
		}

		// Ottiene la chiave della Compagnia (= sistema mittente)
		$idCompany = getCompanyId($from);
		if ($idCompany==0)
		{
			writeProcessLog(PROCESS_NAME,"Sistema mittente '$from' non identificato nella tabella Compagnia",2);
			return FALSE;
		}

		// Marca il job
		changeImportStatus($idImportLog,'R'); // marca come running
		 
		// Inizializza contatore errori
		$numErrors = 0;

		// per ottimizzazione, carica una serie di array di codici
		$formeGiuridiche = fetchValuesArray("SELECT CodFormaGiuridica FROM formagiuridica");
		$sigleProvince = fetchValuesArray("SELECT SiglaProvincia FROM provincia");
		$sigleNazioni = fetchValuesArray("SELECT SiglaNazione FROM nazione");
		$tipiRecapito = fetchValuesArray("SELECT IdTipoRecapito FROM tiporecapito");

		$aree = getFetchKeyValue("SELECT CONCAT(IdArea,',',IdAreaParent) as Chiavi,SiglaProvincia from area WHERE"
		." TipoArea='R' AND CURDATE() BETWEEN DataIni AND DataFin AND SiglaProvincia>''","SiglaProvincia","Chiavi");

		// Se precrimine, svuota le tabelle relative
		if ($type=="precrimini" || $type=="precrimine" )
		{
			$precrimine = TRUE;
			if (!execute("TRUNCATE TABLE movimentoprecrimine"))
			{
				writeResult($idImportLog,"K","Elaborazione fallita per errore SQL sulla tabella movimentoprecrimine");
				return FALSE;
			}
			if (!execute("TRUNCATE TABLE insolutoprecrimine"))
			{
				writeResult($idImportLog,"K","Elaborazione fallita per errore SQL sulla tabella insolutoprecrimine");
				return FALSE;
			}
			// Cancella i messaggi differiti in attesa per il precrimine (att.ne: soluzione non generalizzata, si
			// basa sul fatto di sapere che i messaggi sono SMS con modello con id=2)
			if (!execute("DELETE FROM messaggiodifferito WHERE IdModello=2 AND Tipo='S' AND Stato='C'"))
			{
				writeResult($idImportLog,"K","Elaborazione fallita per errore SQL sulla tabella messaggiodifferito");
				return FALSE;
			}
		}
			
		// All'inizio del file dipendenti, marca la dataPagamento a tutte le righe in cui � null
		// per poi riconoscerle
		if ($type=="dipendenti")
		{
			trace("Marcaggio insoluti dipendenti pre-elaborazione",FALSE);
			if (!execute("UPDATE insolutodipendente SET DataChiusura='8888-08-08' WHERE DataChiusura IS NULL"))
			{
				writeResult($idImportLog,"K",getLastError());
				return FALSE;
			}
		}

		// All'inizio del file movimenti
		// si assicura la creazione delle righe in _opt_insoluti per i nuovi contratti
		if ($type=="movimenti")
		{
			trace("Elaborazione aggiornamento tabella di comodo _opt_insoluti",FALSE);
			updateOptInsoluti("lastUpd>CURDATE() OR idcontratto not in (select idcontratto from _opt_insoluti)"); // aggiorna l'ottimizzazione
			trace("Fine elaborazione aggiornamento tabella di comodo _opt_insoluti",FALSE);
		}		
		
		// Legge le righe e verifica che siano JSON ok
		$file = fopen("$dir/$filename",'r');
		if (!$file)
		{
			writeResult($idImportLog,"K","Elaborazione fallita per errore nella lettura del file temporaneo $filename");
			return FALSE;
		}

		// Processa tutte le righe del file
		$lastContratto = "";
		for ($nrows=0; ($buffer = fgets($file)) !== false; $nrows++)
		{
			controllaStopForzato();

			$json = json_decode($buffer);
			if (NULL == $json)
			{
				writeResult($idImportLog,"K","La riga n. " . ($nrows+1) . " del file ha un formato invalido");
				return FALSE;
			}
			else if (property_exists($json,"rows")) // si tratta dell'ultima riga di controllo (gi� controllata nella import.php)
				break;
			// Smista per il trattamento specifico
			switch ($type)
			{
				case "cliente":
				case "clienti":
					$ret = processCliente($json,$idCompany);
					break;
				case "banche":
				case "banca":
					$ret = processBanche($json);
					break;
				case "contratti":
				case "contratto":
					$ret = processContratto($json,$idCompany);
					// Adesso aggiorna il flag di blocco affido, con opportune conseguenze
					aggiornaFlagBloccoAffido($IdContratto,$flagBloccoAffido);
					break;
				case "controparti":
				case "controparte":
					$ret = processControparte($json,$idCompany);
					break;
				case "dipendenti":
					$ret = processDipendente($json,$idCompany);
					break;
				case "precrimini":
				case "precrimine":
				case "movimenti":
				case "movimento":
					$IdContratto = getIdContratto($json,$idCompany);
					if (!($IdContratto>0))
					{
						writeRecordError($idImportLog,"R","Codice contratto {$json->codContratto} non presente nella tabella Contratti; record ".($nrows+1)
						,$json->codContratto);
						if ($numErrors==MAX_BATCH_ERRORS) // se deve terminare
						{
							$ret = 1;
							break;
						}
						// Salta tutte le righe successive dello stesso codcontratto
						for ($cc=$json->codContratto; ($buffer = fgets($file)) !== false; $nrows++)
						{
							$json = json_decode($buffer);
							if ($json->codContratto!=$cc) break;
						}
						if ($buffer===FALSE) // finito
						{
							$ret = 1;
							break;
						}
						else // posizionato sul primo mov. del contratto successivo
						{
							$IdContratto = getIdContratto($json,$idCompany);
							if (!($IdContratto>0))
								continue;
						}
					}
					if	(!$precrimine) $movimentiOK = TRUE; // indica che � arrivato un file movimenti non vuoto
					
					$ret = processMovimento($json,$IdContratto,$lastContratto); // carica singolo record e gestisce il break
					break;
				default:
					writeResult($idImportLog,"K","Tipo di file non trattato ($filename)");
					return FALSE;
			}
			// Considera l'esito del record appena elaborato
			if ($ret==2) // errore grave
			{
				fclose($file);
				return FALSE;
			}
			else if ($ret==1) // errore normale
			{
				if ($numErrors==MAX_BATCH_ERRORS) // esauriti: deve terminare qui
				{
					writeResult($idImportLog,"K","Elaborazione interrotta perche' superato il numero massimo di errori ammessi (".MAX_BATCH_ERRORS.")");
					fclose($file);
					return FALSE;
				}
				else
					$numErrors++;
			}
		}
		
		// gestione ultimo contratto del file movimenti
		if ($lastContratto>"")
		{
			$ret =  processAndClassify($lastContratto);
			if ($ret==2) // errore grave
			{
				fclose($file);
				return FALSE;
			}
			aggiornaFlagBloccoAffido($lastContratto,$flagBloccoAffido);  // aggiorna il flag di blocco sul contratto
		}

		// Al termine del file dipendenti, mette la datapagamento a tutte le righe che non
		// sono arrivate (il che dovrebbe indicare partita chiusa)
		if ($type=="dipendenti")
		{
			trace("Marcaggio insoluti dipendenti post-elaborazione",FALSE);
			if (!execute("UPDATE insolutodipendente SET DataChiusura=CURDATE(),"
			." ImpPagato=ImpCapitale+ImpInteressi+ImpInteressiMora+ImpCommissioni WHERE DataChiusura = '8888-08-08'"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}

		// Fine
		fclose($file);
		
		// Traccia fine processo
		trace("Fine elaborazione file di import $type con id=$id",FALSE);
		$nmsg = getScalar("SELECT COUNT(*) FROM importmessage WHERE IdImportLog=$idImportLog");
		writeResult($idImportLog,"U","Elaborazione OK; numero elementi elaborati: $nrows; messaggi: $nmsg");
	}
	catch (Exception $e)
	{
		writeProcessLog( "Errore in elaborazione del file di import $type con id=$id: ".$e->getMessage(),3);
		fclose($file);
		return FALSE;
	}
	return TRUE;
}
//==============================================================================================
// processCliente
// Elabora la struttura JSON con i dati del cliente + recapiti
// Argomenti:
//   1) $json			struttura dati in input
//   2) $idCompany		idCompagnia da usare nella query e insert
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo (manca la chiave del record, oppure
//          superato il massimo numero di errori ammessi)
//==============================================================================================
function processCliente($json,$idCompany)
{
	global $idImportLog,$formeGiuridiche,$sigleProvince,$sigleNazioni,$aree;

	try
	{
		$codForma=$json->forma;

		$codForma = str_replace(" ","",str_replace(".","", $codForma));
		 
		//echo "cod:$codForma<br>";
		if ($codForma>"") // cod Forma Giuridica presente?
		{
			if (!in_array($codForma,$formeGiuridiche))
			//if (!rowExistsInTable("formagiuridica","CodFormaGiuridica='$codForma'")) // non esiste
			{
				// Crea la riga di associazione in FormaGiuridica
				$colList = ""; // inizializza lista colonne
				$valList = ""; // inizializza lista valori
				addInsClause($colList,$valList,"CodFormaGiuridica",$codForma,"S");
				addInsClause($colList,$valList,"TitoloFormaGiuridica", $codForma,"S");
				addInsClause($colList,$valList,"DataIni","2001-01-01","D");
				addInsClause($colList,$valList,"DataFin","9999-12-31","D");
				addInsClause($colList,$valList,"LastUser","import","S");
				addInsClause($colList,$valList,"LastUpd","NOW()","G");

				if (!execute("INSERT INTO formagiuridica ($colList)  VALUES ($valList)"))
				{
					writeResult($idImportLog,"K",getLastError());
					return 2;
				}
				$formeGiuridiche[] = $codForma; // aggiunge all'array
			}
		}

		$codcli = $json->codice;
		if ($codcli>"") // codice cliente presente?
			// cerca il cliente con codice dato, nella compagnia con id dato (deve essercene uno solo)
			$idCliente = getScalar("SELECT C.IdCliente FROM cliente C,clientecompagnia X "
								 . " WHERE X.IdCompagnia=$idCompany AND X.IdCliente=C.IdCliente AND C.CodCliente='$codcli'");
		else // codice cliente assente nella struttura JSON
		{
			writeResult($idImportLog,"K","Codice cliente non specificato in una riga del file Clienti");
			return 2;
		}

		// CONTROLLO SIGLA PROVINCIA E AREA COLLEGATA
		$sigla = $json->provnasc;
		if ($sigla>" ")//siglaProvincia presente?
		{
			if (!in_array($sigla,$sigleProvince))
			// 			if(!rowExistsInTable("provincia","SiglaProvincia='$sigla'"))
			{
				writeRecordError($idImportLog,"R","SiglaProvincia '$sigla' non presente nella tabella Provincia ",$codcli);
				$json->provnasc = "";
			}
		}

		// CONTROLLO SIGLA NAZIONE
		$sigla = $json->naznasc;
		if ($sigla>" ")
		{
			if (!in_array($sigla,$sigleNazioni))
			//if(!rowExistsInTable("nazione","SiglaNazione='$sigla'"))
			{
				writeRecordError($idImportLog,"R","Sigla Nazione '$sigla' non presente nella tabella Nazione ",$codcli);
				$json->naznasc = "IT";
			}
		}
		else
			$json->naznasc = "IT";

		// override tipocliente (per ditte individuali, altrimenti ricevute come persone fisiche)
		if ($json->ragsoc>'')
			$json->tipo = 1; // persona giuridica
		
		if ($idCliente!=NULL) // � necessario un UPDATE (la tab. ClienteCompagnia non si deve aggiornare)
		{

			$setClause = "";
			addSetClause($setClause,"SiglaProvincia",$json->provnasc,"S");
			addSetClause($setClause,"SiglaNazione",$json->naznasc,"S");
			addSetClause($setClause,"CodFormaGiuridica",$codForma,"S");
			addSetClause($setClause,"Nominativo",$json->nome,"S");
			addSetClause($setClause,"RagioneSociale",$json->ragsoc,"S");
			addSetClause($setClause,"IdTipoCliente",$json->tipo,"N");
			addSetClause($setClause,"DataNascita",$json->datanasc,"S");
			addSetClause($setClause,"LocalitaNascita",$json->locnasc,"S");
			addSetClause($setClause,"CodiceFiscale",$json->codfisc,"S");
			addSetClause($setClause,"PartitaIVA",$json->partiva,"S");
			addSetClause($setClause,"ABI",$json->abi,"S");
			addSetClause($setClause,"CAB",$json->cab,"S");
			addSetClause($setClause,"IBAN",$json->iban,"S");
			if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addSetClause($setClause,"DataIni",$json->dataini,"S");
			if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addSetClause($setClause,"DataFin",$json->datafin,"S");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			// NO: da recapito tipo 1. addSetClause($setClause,"IdArea",$idAreaCli,"N");
			addSetClause($setClause,"Sesso",$json->sesso,"S");

			if (!execute("UPDATE cliente $setClause WHERE IdCliente=$idCliente"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}

			// Aggiorna (eventualmente) le date di inizio e fine nell'associazione cliente compagnia
			$setClause = "";
			addSetClause($setClause,"DataIni",$json->dataini,"S");
			addSetClause($setClause,"DataFin",$json->datafin,"S");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			if (!execute("UPDATE clientecompagnia $setClause WHERE IdCliente=$idCliente AND IdCompagnia=$idCompany"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}


			//Aggiorna recapiti cliente
			if (insertRecapiti($json->recapito,"update",$idCliente,$json->dataini,$json->datafin,$codcli)==2)
				return 2;

			//trace("Update cliente $codcli OK");
		}
		else // � necessario un INSERT
		{
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"SiglaProvincia",$json->provnasc,"S");
			addInsClause($colList,$valList,"SiglaNazione",$json->naznasc,"S");
			addInsClause($colList,$valList,"CodFormaGiuridica",$codForma,"S");
			addInsClause($colList,$valList,"CodCliente",$codcli,"S");
			addInsClause($colList,$valList,"Nominativo",$json->nome,"S");
			addInsClause($colList,$valList,"RagioneSociale",$json->ragsoc,"S");
			addInsClause($colList,$valList,"IdTipoCliente",$json->tipo,"N");
			addInsClause($colList,$valList,"DataNascita",$json->datanasc,"S");
			addInsClause($colList,$valList,"LocalitaNascita",$json->locnasc,"S");
			addInsClause($colList,$valList,"CodiceFiscale",$json->codfisc,"S");
			addInsClause($colList,$valList,"PartitaIVA",$json->partiva,"S");
			addInsClause($colList,$valList,"ABI",$json->abi,"S");
			addInsClause($colList,$valList,"CAB",$json->cab,"S");
			addInsClause($colList,$valList,"IBAN",$json->iban,"S");
			if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addInsClause($colList,$valList,"DataIni",$json->dataini,"D");
			if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addInsClause($colList,$valList,"DataFin",$json->datafin,"D");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			// NO: da recapito tipo 1. addInsClause($colList,$valList,"IdArea",$idAreaCli,"N");
			addInsClause($colList,$valList,"Sesso",$json->sesso,"S");
			if (!execute("INSERT INTO cliente ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}

			$idCliente = getInsertId();

			// Crea la riga di associazione in ClienteCompagnia
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdCliente",$idCliente,"N");
			addInsClause($colList,$valList,"IdCompagnia",$idCompany,"N");
			addInsClause($colList,$valList,"DataIni",$json->dataini,"D");
			addInsClause($colList,$valList,"DataFin",$json->datafin,"D");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");

			if (!execute("INSERT INTO clientecompagnia ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}

			//Inserisce recapiti cliente
			if (insertRecapiti($json->recapito,"insert",$idCliente,$json->dataini,$json->datafin,$codcli)==2)
				return 2;

			//trace("Insert cliente $codcli OK");
		}
		// Aggiorna campo Telefono
		$telefono = getScalar("select telefoni from v_lista_telefoni where idCliente=$idCliente");
		if ($telefono>'')
		{
			$setClause = "";
			addSetClause($setClause,"Telefono",$telefono,"S");
			addSetClause($setClause,"LastUser","import","S");
			if (!execute("UPDATE cliente $setClause WHERE IdCliente=$idCliente"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}

		// Aggiorna campo IdArea (area geografica del recupero = regione di indirizzo principale)
		if (!aggiornaAreaCliente($idCliente))
		{
			writeResult($idImportLog,"K",getLastError());
			return 2;
		}

		return 0;
	}
	catch (Exception $e)
	{
   	    writeProcessLog(PROCESS_NAME,"Errore nell'elaborazione di un record clienti: ".$e->getMessage(),2);
		return 2;
	}
}

//==============================================================================================
// processBanche
// Elabora la struttura JSON con i dati della banca
// Argomenti:
//   1) $json			struttura dati in input
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function processBanche($json)
{
	global $idImportLog;

	try
	{
		if(rowExistsInTable('banca',"banca.ABI=$json->abi and banca.CAB=$json->cab") != false) // la banca esiste?
		{
			// � necessario un UPDATE
			$setClause = "";
			addSetClause($setClause,"ABI",$json->abi,"N");
			addSetClause($setClause,"CAB",$json->cab,"N");
			addSetClause($setClause,"TitoloBanca",$json->banca,"S");
			addSetClause($setClause,"TitoloAgenzia",$json->agenzia,"S");
			addSetClause($setClause,"IndirizzoAgenzia",$json->indirizzo,"S");
			addSetClause($setClause,"CAPAgenzia",formatCap($json->cap),"S");
			addSetClause($setClause,"LocalitaAgenzia",$json->localita,"S");
			addSetClause($setClause,"ProvAgenzia",$json->siglaprov,"S");
			if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addSetClause($setClause,"DataIni",$json->dataini,"D");
			if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addSetClause($setClause,"DataFin",$json->datafin,"D");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			addSetClause($setClause,"Telefono",$json->telefono,"S");

			if (!execute("UPDATE banca $setClause WHERE banca.ABI=$json->abi AND banca.CAB=$json->cab"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}

			//trace("Update banca ABI.$json->abi. - CAB.$json->cab.OK");

		}
		else
		{
			// � necessario un INSERT
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"ABI",$json->abi,"N");
			addInsClause($colList,$valList,"CAB",$json->cab,"N");
			addInsClause($colList,$valList,"TitoloBanca",$json->banca,"S");
			addInsClause($colList,$valList,"TitoloAgenzia",$json->agenzia,"S");
			addInsClause($colList,$valList,"CAPAgenzia",formatCap($json->cap),"S");
			addInsClause($colList,$valList,"LocalitaAgenzia",$json->localita,"S");
			addInsClause($colList,$valList,"ProvAgenzia",$json->siglaprov,"S");
			if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addInsClause($colList,$valList,"DataIni",$json->dataini,"D");
			if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addInsClause($colList,$valList,"DataFin",$json->datafin,"D");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"Telefono",$json->telefono,"S");

			//trace("INSERT INTO banca ($colList) VALUES ($valList)");

			if (!execute("INSERT INTO banca ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
			//trace("Insert banca ABI.$json->abi. - CAB.$json->cab.OK");
		}
		return 0;
	}
	catch (Exception $e)
	{
   	    writeProcessLog(PROCESS_NAME,"Errore nell'elaborazione di un record Banche: ".$e->getMessage(),2);
		return 2;
	}
}

//==============================================================================================
// processContratto
// Elabora la struttura JSON con i dati del contratto
// Argomenti:
//   1) $json			struttura dati in input
//   2) $idCompany		idCompagnia
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function processContratto($json,$idCompany)
{
	global $idImportLog,$flagBloccoAffido,$IdContratto;

	// Variabili di utility
	$QueryStr ="";         // query string
	$result="";            // var di app per i dati ricevuti dalle interrog DB
	$todo=""; 		       /* Operazione da eseguire con il contratto :  Insert=inserisco un nuovo contratto
	Update=eseguo l'update del contratto*/


	// Variabili contenenti i dati letti da db per la succ op di insert / update
	$IdContratto="";       // Id Contratto letto dalla tab contratto
	$IdCliente="";         // Id Cliente  letto dalla tab cliente
	$IdProdotto="";        // Id Prodotto letto dalla tab prodotto
	$IdStatoContratto="";  // Id Stato Contratto letto dalla tab statocontratto
	$IdTipoPagamento="";   // Id Tipo Pagamento letto dalla tab tipopagamento
	$Iban=""; 			   // Iban calcolato da abi cab e cc ricevuti
	$IdFiliale="";		   // Id Filiale letto dalla tab filiale
	$IdDealer=""; 		   // Id compagnia
	$IdTipoSpeciale="";    // Id tipo speciale letto dalla tab tipospeciale
	$Responsabile=""; 	   // Id filiale responsabile contratto letto da tb filiale
	$CodContratto =  $json->CodContratto;

	$flagBloccoAffido = 'N'; // inizializza indicatore di blocco affido
	
	// INIZIO CONTROLLO DATI RICEVUTI DA STRUTTURA JSON

	//Controllo codice contratto ricevuto da struttura Json e lttura IdContratto da DB
	if ($CodContratto!="")       // se � stato specificato  il codice contratto
	{
		// ricavo idCodContratto dalla tab Contratti
		$row = getRow("SELECT IdContratto,IdStatoRecupero FROM contratto WHERE CodContratto ='".$json->CodContratto."' AND IdCompagnia =".$idCompany);
		if ($row==NULL)
			$todo="Insert";  	    // 	dovr� fare l'insert del contratto
		else
		{
			$IdContratto = $row["IdContratto"];
			$oldStatoRecupero = $row["IdStatoRecupero"];
			$todo="Update";  	    // 	dovr� fare l'update del contratto
		}
	}
	else  						    //  non � stato specificato il codice contratto
	{
		//  invio errore per mancanza codice contratto
		writeRecordError($idImportLog,"E","Codice contratto non presente in un record del file contratti ",null);
		return 1;
	}


	//echo("todo=$todo");

	//Controllo Codice cliente ricevuto dalla struttura Json  e lettura IdCliente da DB
	if($json->CodCliente!="") 	    // se � stato specificato  il codice cliente
	{
		// ricavo IdCliente dalla tab Cliente in Join Con la tab ClienteCompagnia (con cod compagnia specificato)
		$QueryStr = " SELECT C.IdCliente"
		." FROM cliente C JOIN clientecompagnia X"
		." 				   ON C.IdCliente = X.IdCliente"
		." WHERE C.CodCliente='".$json->CodCliente."' AND X.IdCompagnia=".$idCompany;
			
		//echo("QueryStr:$QueryStr");
			
		$IdCliente = getScalar($QueryStr);

		//echo("\nIdCliente:".$IdCliente);

		if($IdCliente=="") 		    //  se non ha trovato l'IdClienti
		{
			//  invio errore per mancanza codice cliente sul db
			writeRecordError($idImportLog,"R","Codice cliente ".$json->CodCliente." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$flagBloccoAffido = 'C';
			return 1;
		}
	}
	else  						    //  non � stato specificato il codice cliente
	{
		//  invio errore per mancanza codice cliente
		writeRecordError($idImportLog,"E","Codice Cliente assente in un record del file contratti",$CodContratto);
		$flagBloccoAffido = 'C';
		return 1;
	}

	//Controllo CodProdotto ricevuto dalla struttura Json e lettura IdProdotto da db
	if($json->CodProdotto!="") 	    // se � stato specificato  il codice prodotto
	{
		// ricavo IdProdotto dalla tab Prodotto in Join Con la tab famigliaProdotto (con cod compagnia specificato)
		$QueryStr = " SELECT P.IdProdotto"
		." FROM prodotto P JOIN famigliaprodotto F"
		." 				   ON P.IdFamiglia = F.IdFamiglia"
		." WHERE P.CodProdotto='".$json->CodProdotto."' AND F.IdCompagnia=".$idCompany;
		$IdProdotto = getScalar($QueryStr);
			
		//echo("QueryStr:$QueryStr");
		//echo("IdProdotto:.$IdProdotto");
			
		if(!$IdProdotto) 	    //  se non ha trovato l'IdProdotto sul db
		{
			// Caso speciale, il prodotto OCS, composto da tre parti fam-sottofam-prod potrebbe avere una sottofamiglia
			// errata. In tal caso bada solo alla parte iniziale e finale
			$IdProdotto = getScalar("SELECT IdProdotto FROM prodotto p JOIN famigliaprodotto f ON p.IdFamiglia=f.IdFamiglia"
			. " WHERE CodProdotto LIKE '".substr($json->CodProdotto,0,2)."%".substr($json->CodProdotto,6)."'");
			//  invio errore per mancanza codice Prodotto sul db
			if(!$IdProdotto) 	    //  se non ha trovato l'IdProdotto sul db
			{
				writeRecordError($idImportLog,"R","Codice Prodotto {$json->CodProdotto} trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
				$flagBloccoAffido = 'C';
				return 1;
			}
		}
	}

	//Controllo CodStatoContratto ricevuto dalla struttura Json  e lettura IdStatoContratto da db
	if ($json->CodStatoContratto!="") 	// se � stato specificato  il codice statoContratto
	{
		// ricavo IdStatoContratto dalla tab statoContratto
		$QueryStr = " SELECT S.IdStatoContratto"
		." FROM statocontratto S"
		." WHERE S.CodStatoContratto='".$json->CodStatoContratto."'";
			
		$IdStatoContratto = getScalar($QueryStr);
			
		//echo("QueryStr:".$QueryStr);
		//echo("IdStatoContratto:".$IdStatoContratto);
			
		if(!($IdStatoContratto>0))	//  se non ha trovato l'IdStatoContratto sul db
		{
			//  invio errore per mancanza codice statoContratto sul db
			writeRecordError($idImportLog,"R","Codice stato contratto ".$json->CodStatoContratto." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$flagBloccoAffido = 'C';
			$IdStatoContratto = 999; // impostato a N/A
		}
	}
	else
	$IdStatoContratto = 999; // impostato a N/A

	//Controllo CodTipoPagamento ricevuto dalla struttura Json e lettura IdTipoPagamento
	if ($json->CodTipoPagamento!="") 	// se � stato specificato  il codice TipoPagamento
	{
		// ricavo IdTipoPagamento dalla tab tipopagamento
		$QueryStr = " SELECT T.IdTipoPagamento"
		." FROM tipopagamento T"
		." WHERE T.CodTipoPagamentoLegacy LIKE '%".$json->CodTipoPagamento."%'";
		$IdTipoPagamento = getScalar($QueryStr);

		//echo("QueryStr:".$QueryStr);
		//echo("IdTipoPagamento:".$IdTipoPagamento);
			
		if ($IdTipoPagamento=="") 	//  se non ha trovato l'IdTipoPagamento sul db
		{
			//  invio errore per mancanza codice Tipo Pagamento sul db
			writeRecordError($idImportLog,"R","Codice Tipo Pagamento ".$json->CodTipoPagamento." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$flagBloccoAffido = 'C';
			//$IdTipoPagamento="";
			return 1;
		}
	}
	else
		$IdTipoPagamento = 999; // default: N/A

	// Controllo ABI, CAB e CC e Calcolo IBAN
	if($json->CodABI!="" && $json->CodCAB!="" )  // se sono stati specificati ABI E CAB
	{
		// controllo se esiste una banca con abi e cab ricevuti
		if (!rowExistsInTable("banca", "banca.ABI=".$json->CodABI." AND banca.CAB=".$json->CodCAB))  // se esiste
		{
			writeRecordError($idImportLog,"E","Banca con codice ABI: $json->CodABI e codice CAB: $json->CodCAB trovata nel contratto $CodContratto ma non presente sul DB",$CodContratto);
		}

		if($json->CodCC!="")   // se � stato specificato il codice di conto corrente
		{
			$Iban = createIban($json->CodABI, $json->CodCAB, $json->CodCC);
			//echo("IBAN:".$Iban);
			//die();
			if($Iban=="")
			{
				writeRecordError($idImportLog,"E","Impossibile calcolare il codice IBAN con i seguenti dati ABI:'$json->CodABI - CAB:$json->CodCAB - CC:$json->CodCC '",$CodContratto);
				$Iban = "***CC="+$json->CodCC;
			}
		}
		else					// non � stato specificato il CC non posso calcolare Cod IBAN
		{
			writeRecordError($idImportLog,"E","Codice di conto corrente non presente, impossibile calcolare il codice IBAN",$CodContratto);
			$Iban = "***CC="+$json->CodCC;
		}
	}
	else 				 				// non � stata trovata una banca con abi e cab indicati e non � stato possibile calcolare l'iban
	{
		$Iban = "";
	}

	// Controllo CodFiliale e lettura IdFiliale da DB
	if($json->CodFiliale!="" && $json->CodFiliale!=0) 		// se � stato specificato  il codice filiale
	{
		// ricavo IdFiliale dalla tab filiale
		$QueryStr = " SELECT F.IdFiliale"
		." FROM filiale F"
		." WHERE F.CodFiliale='".$json->CodFiliale."'";
		$IdFiliale = getScalar($QueryStr);
			
		//echo("QueryStr:".$QueryStr);
		//echo("IdFiliale:".$IdFiliale);
		//die();
		if($IdFiliale=="") 			//  se non ha trovato l'IdFiliale sul db
		{
			//  invio errore per mancanza codice filiale sul db
			writeRecordError($idImportLog,"R","Codice filiale ".$json->CodFiliale." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$flagBloccoAffido = 'C';
			$IdFiliale="";
		}
	}

	// Controllo CodVenditore
	if($json->CodVenditore!="" && $json->CodVenditore!=0) 		// se � stato specificato  il codice venditore
	{
		// ricavo IdVenditore dall'anagrafe clienti
		$IdVenditore = getScalar("SELECT IdCliente FROM cliente WHERE CodCliente='".$json->CodVenditore."'");
		if(!($IdVenditore>0))
		{
			//  invio errore per mancanza codice venditore sul db
			writeRecordError($idImportLog,"R","Codice venditore ".$json->CodVenditore." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$IdVenditore = "";
		}
	}
	// Controllo CodPuntoVendita (per ora solo in anagrafe clienti)
	if($json->CodPuntoVendita!="" && $json->CodPuntoVendita!=0) 		// se � stato specificato  il codice punto vendita
	{
		// ricavo IdPuntoVendita dall'anagrafe clienti
		$IdPuntoVendita = getScalar("SELECT IdCliente FROM cliente WHERE CodCliente='".$json->CodPuntoVendita."'");
		if(!($IdPuntoVendita>0))
		{
			//  invio errore per mancanza codice venditore sul db
			writeRecordError($idImportLog,"R","Codice punto vendita ".$json->CodPuntoVendita." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$IdPuntoVendita = "";
		}
	}
	// Controllo Attributo
	if($json->Attributo!="") 		// se � stato specificato l'attributo
	{
		// ricavo IdPuntoVendita dall'anagrafe cl

		// il cod. attributo sul DB CNC ha la forma "LE CH" (ad es.) cio� famiglia+blank+attributo
		$attr = substr($json->CodProdotto,0,2)." ".$json->Attributo;
		$IdAttributo = getScalar("SELECT IdAttributo FROM attributo WHERE CodAttributoLegacy='$attr'");
		if(!($IdAttributo>0))
		{
			//  invio errore per mancanza codice venditore sul db
			writeRecordError($idImportLog,"R","Codice attributo ".$json->Attributo." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$flagBloccoAffido = 'C';
			$IdAttributo = "";
		}
	}
	else
	{
		$IdAttributo = "";
		$attr = "";
	}
	// Controllo IdCompagniaIdDealer e lettura id compagnia dealer dal db
	if($json->IdCompagniaIdDealer!="")// se � stato specificato  il codice compagnia dealer
	{
		// controllo se il codice compagnia dealer esiste e che la compagnia sia dealer (IdTipoCompagnia=3)
		$QueryStr = " SELECT C.IdCompagnia"
		." FROM compagnia C"
		." WHERE C.CodCompagnia='".$json->IdCompagniaIdDealer."' AND C.IdTipoCompagnia=3";
		$IdDealer = getScalar($QueryStr);
			
		//echo("QueryStr:".$QueryStr);
		//echo("IdDealer:".$IdDealer);
			
		if($IdDealer=="")
		{
			// Controlla se esiste il cliente con tale codice: se s� lo copia in Compagnia
			$cliente = getRow("SELECT * FROM cliente WHERE CodCliente='".$json->IdCompagniaIdDealer."'");
			if ($cliente>0)
			{
				execute("INSERT INTO compagnia (CodCompagnia,TitoloCompagnia,IdTipoCompagnia,SiglaProvincia,"
				."NomeTitolare,Indirizzo,CAP,Localita,Telefono,Fax,EmailTitolare,DataIni,DataFin,LastUser)"
				." SELECT CodCliente,IFNULL(RagioneSociale,Nominativo),3,r.SiglaProvincia,Nominativo,"
				."Indirizzo,CAP,Localita,r.Telefono,Fax,Email,c.DataIni,c.DataFin,'import'"
				." FROM cliente c LEFT JOIN recapito r ON c.IdCliente=r.IdCliente WHERE IdTipoRecapito=1"
				." AND CodCliente='".$json->IdCompagniaIdDealer."'");
				$IdDealer = getScalar($QueryStr); // rilegge l'ID
			}
		}
		 
		if($IdDealer=="")
		{
			writeRecordError($idImportLog,"R","Codice Dealer ".$json->IdCompagniaIdDealer." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$IdDealer="";
		}
	}

	// Controllo CodTipoSpeciale e lettura IdTipoSpeciale dal db
	if($json->CodTipoSpeciale!="") 	// se � stato specificato  il codice tipo speciale
	{
		// controllo se il codice compagnia dealer esiste e che la compagnia sia dealer (IdTipoCompagnia=3)
		$QueryStr = " SELECT C.IdTipoSpeciale"
		." FROM tipospeciale C"
		." WHERE C.CodTipoSpecialeLegacy like '%".$json->CodTipoSpeciale."%'";
			
		$IdTipoSpeciale = getScalar($QueryStr);
			
		//echo("QueryStr:".$QueryStr);
		//echo("IdTipoSpeciale:".$IdTipoSpeciale);
			
		if($IdTipoSpeciale=="")
		{
			//  invio errore per mancanza codice compagnia dealer sul db
			writeRecordError($idImportLog,"R","Codice tipo speciale (forzatura) ".$json->CodTipoSpeciale." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$IdTipoSpeciale="";
		}
	}

	// Controllo Responsabile e lettura IdFiliale (responsebile contratto) da DB
	if($json->Responsabile!="" && $json->Responsabile!="XXX" ) 		// se � stato specificato  il codice responsabile (filiale)
	{
		// ricavo IdFiliale (responsabile) dalla tab filiale
		$QueryStr = " SELECT F.IdFiliale"
		." FROM filiale F"
		." WHERE F.CodFiliale='".$json->Responsabile."'";
		$Responsabile = getScalar($QueryStr);
			
		//echo("QueryStr:".$QueryStr);
		//echo("Responsabile:".$Responsabile);
			
		if($Responsabile=="") 		//  se non ha trovato l'IdFiliale sul db
		{
			//  invio errore per mancanza codice filiale (responsabile) sul db
			writeRecordError($idImportLog,"R","Codice filiale (responsabile) ".$json->Responsabile." trovato nel contratto $CodContratto ma non presente sul DB",$CodContratto);
			$Responsabile = "";
		}
	}
	else  							//  non � stato specificato il codice responsabile (filiale)
	{
		$Responsabile = "";
	}
	// Determina lo stato recupero, nei casi in cui questo dipende dallo stato contratto
	// o da altre considerazioni (torna FALSE se non deve essere toccato). Pu� anche aggiustare il debitoResituo
	$IdStatoRecupero = calcolaStatoRecupero($IdContratto,$json->CodStatoContratto,$attr,$json->CapitRes,$flagRineg);
	if ($IdStatoRecupero==79 || $IdStatoRecupero==84) // se in write off o cessione
		$IdClasse = 19; // mette classe "fuori recupero"
	else
		$IdClasse = NULL; // non mettere nulla
	
	// Lista garanzie
	$garanzie = "";
	if ($json->Tgar1>"") $garanzie .= $json->Tgar1.",";
	if ($json->Tgar2>"") $garanzie .= $json->Tgar2.",";
	if ($json->Tgar3>"") $garanzie .= $json->Tgar3.",";
	if ($json->Tgar4>"") $garanzie .= $json->Tgar4.",";
	if ($json->Tgar5>"") $garanzie .= $json->Tgar5.",";
	if ($garanzie>"") $garanzie = substr($garanzie,0,strlen($garanzie)-1);

	// INIZIO SCRITTURA DATI SUL DB ( INSERT / UPDATE CONTRATTO)
	try
	{
		if($todo=="Insert")               // il contratto non esiste e va inserito
		{
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori

			addInsClause($colList,$valList,"IdContrattoDerivato","","N");
			addInsClause($colList,$valList,"IdCliente",$IdCliente,"N");
			addInsClause($colList,$valList,"CodContratto",$json->CodContratto,"S");
			addInsClause($colList,$valList,"IdProdotto",$IdProdotto,"N");
			addInsClause($colList,$valList,"IdStatoContratto",$IdStatoContratto,"N");
			addInsClause($colList,$valList,"IdTipoPagamento",$IdTipoPagamento,"N");
			addInsClause($colList,$valList,"ImpValoreBene",str_replace(',','.',$json->ImpValoreBene),"N");
			addInsClause($colList,$valList,"ImpFinanziato",str_replace(',','.',$json->ImpFinanziato),"N");
			addInsClause($colList,$valList,"ImpAnticipo",str_replace(',','.',$json->ImpAnticipo),"N");
			addInsClause($colList,$valList,"ImpErogato",str_replace(',','.',$json->ImpErogato),"N");
			addInsClause($colList,$valList,"ImpRata",str_replace(',','.',$json->ImpRata),"N");
			addInsClause($colList,$valList,"ImpRataFinale",str_replace(',','.',$json->ImpRataFinale),"N");
			addInsClause($colList,$valList,"ImpRiscatto",str_replace(',','.',$json->ImpRiscatto),"N");
			addInsClause($colList,$valList,"ImpInteressi",str_replace(',','.',$json->ImpInteressi),"N");
			addInsClause($colList,$valList,"ImpSpeseIncasso",str_replace(',','.',$json->ImpSpeseIncasso),"N");
			addInsClause($colList,$valList,"PercTasso",str_replace(',','.',$json->PercTasso),"N");
			addInsClause($colList,$valList,"PercTaeg",str_replace(',','.',$json->PercTaeg),"N");
			addInsClause($colList,$valList,"PercTassoReale",str_replace(',','.',$json->PercTassoReale),"N");
			addInsClause($colList,$valList,"ImpDebitoResiduo",str_replace(',','.',$json->CapitRes),"N");
			addInsClause($colList,$valList,"NumRate",$json->NumRate,"N");
			addInsClause($colList,$valList,"ImpInteressiDilazione",str_replace(',','.',$json->ImpInteressiDilazione),"N");
			addInsClause($colList,$valList,"NumMesiDilazione",$json->NumMesiDilazione,"N");
			addInsClause($colList,$valList,"DescrBene",$json->DescrBene,"S");
			addInsClause($colList,$valList,"CodBene",$json->CodBene,"S");
			addInsClause($colList,$valList,"CodTabFinanziaria",$json->CodTabFinanziaria,"S");
			addInsClause($colList,$valList,"DataContratto",$json->DataContratto,"D");
			addInsClause($colList,$valList,"DataDecorrenza",$json->DataDecorrenza,"D");
			addInsClause($colList,$valList,"DataPrimaScadenza",$json->DataPrimaScadenza,"D");
			addInsClause($colList,$valList,"DataUltimaScadenza",$json->DataUltimaScadenza,"D");
			addInsClause($colList,$valList,"DataChiusura",$json->DataChiusura,"D");
			addInsClause($colList,$valList,"ABI",$json->CodABI,"N");
			addInsClause($colList,$valList,"CAB",$json->CodCAB,"N");
			addInsClause($colList,$valList,"IBAN",$Iban,"S");
			addInsClause($colList,$valList,"IdFiliale",$IdFiliale,"N");
			addInsClause($colList,$valList,"IdDealer",$IdDealer,"N");
			addInsClause($colList,$valList,"IdTipoSpeciale",$IdTipoSpeciale,"N");
			//if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addInsClause($colList,$valList,"DataIni","2001-01-01","D");
			//if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addInsClause($colList,$valList,"DataFin","9999-12-31","D");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			addInsClause($colList,$valList,"IdAgenzia","","N");
			addInsClause($colList,$valList,"IdOperatore","","N");
			addInsClause($colList,$valList,"IdCompagnia",$idCompany,"N");
			addInsClause($colList,$valList,"DataCambioStato","","D");
			addInsClause($colList,$valList,"DataCambioClasse","","D");
			addInsClause($colList,$valList,"IdStatoRecupero",$IdStatoRecupero,"N");
			if ($IdClasse>0)	
				addInsClause($colList,$valList,"IdClasse",$IdClasse,"N");
			addInsClause($colList,$valList,"AutoreOverride",$json->AutoreOverride,"S");
			addInsClause($colList,$valList,"IdResponsabile",$Responsabile,"N");
			addInsClause($colList,$valList,"FlagRecupero","","S");
			addInsClause($colList,$valList,"IdVenditore",$IdVenditore,"N");
			addInsClause($colList,$valList,"IdPuntoVendita",$IdPuntoVendita,"N");
			addInsClause($colList,$valList,"IdAttributo",$IdAttributo,"N");
			addInsClause($colList,$valList,"ImpInteressiMora",$json->InteressiMora,"N");
			
			// nuovi campi dal 25/10/2012
			addInsClause($colList,$valList,"Garanzie",$garanzie,"S");
			if (substr($json->CodContratto,0,2)=='LO') // solo per i contratti loan (leasing prende qualcosa dal partitario)
			{
				if ($IdStatoContratto!=1) { // dataDBt e impDBT non contano se arrivano per una simulazione DBT, 
					                        // nel qual caso il contratto rimane in stato attivo
					addInsClause($colList,$valList,"DataDBT",$json->DataCalcolo,"D");
					addInsClause($colList,$valList,"ImpDBT",$json->ImpRecupero,"N");
				}
				addInsClause($colList,$valList,"ImpInteressiMaturati",$json->ImpMoraMatur,"N");
				addInsClause($colList,$valList,"DataInteressiMaturati",$json->DataValidita,"D");

			}
			else // leasing: la data DBT la prende da qui, ma la cerca pure nell'elaboraz. partitario
				if ($IdStatoContratto!=1) { // dataDBt e impDBT non contano se arrivano per una simulazione DBT 
					addInsClause($colList,$valList,"DataDBT",$json->DataDBT,"D");
				}
			//trace("INSERT INTO contratto ($colList) VALUES ($valList)");
			//echo("INSERT INTO contratto ($colList) VALUES ($valList)<br>");
			//die();

			if (!execute("INSERT INTO contratto ($colList) VALUES ($valList)"))
			{
				writeRecordError($idImportLog,"E","INSERT contratto $CodContratto fallita: ".getLastError(),$CodContratto);
				$flagBloccoAffido = 'C';
				return 1;
			}

			$IdContratto = getInsertId();

			//Inserisce accessorio
			if (insertServAcc($json->servizio,"insert",$IdContratto,$CodContratto)==2)
				return 2;

		}// end if Insert
		 

		if($todo=="Update")               // il contratto va aggiornato
		{
			$setClause ="";
			addSetClause($setClause,"IdCliente",$IdCliente,"N");
			addSetClause($setClause,"CodContratto",$json->CodContratto,"S");
			addSetClause($setClause,"IdProdotto",$IdProdotto,"N");
			addSetClause($setClause,"IdStatoContratto",$IdStatoContratto,"N");
			addSetClause($setClause,"IdTipoPagamento",$IdTipoPagamento,"N");
			addSetClause($setClause,"ImpValoreBene",str_replace(',','.',$json->ImpValoreBene),"N");
			addSetClause($setClause,"ImpFinanziato",str_replace(',','.',$json->ImpFinanziato),"N");
			addSetClause($setClause,"ImpAnticipo",str_replace(',','.',$json->ImpAnticipo),"N");
			addSetClause($setClause,"ImpErogato",str_replace(',','.',$json->ImpErogato),"N");
			addSetClause($setClause,"ImpRata",str_replace(',','.',$json->ImpRata),"G");
			addSetClause($setClause,"ImpRataFinale",str_replace(',','.',$json->ImpRataFinale),"N");
			addSetClause($setClause,"ImpRiscatto",str_replace(',','.',$json->ImpRiscatto),"N");
			addSetClause($setClause,"ImpInteressi",str_replace(',','.',$json->ImpInteressi),"N");
			addSetClause($setClause,"ImpSpeseIncasso",str_replace(',','.',$json->ImpSpeseIncasso),"N");
			addSetClause($setClause,"PercTasso",str_replace(',','.',$json->PercTasso),"N");
			addSetClause($setClause,"PercTaeg",str_replace(',','.',$json->PercTaeg),"N");
			addSetClause($setClause,"PercTassoReale",str_replace(',','.',$json->PercTassoReale),"N");
			addSetClause($setClause,"ImpDebitoResiduo",str_replace(',','.',$json->CapitRes),"N");
			addSetClause($setClause,"NumRate",$json->NumRate,"N");
			addSetClause($setClause,"ImpInteressiDilazione",str_replace(',','.',$json->ImpInteressiDilazione),"N");
			addSetClause($setClause,"NumMesiDilazione",$json->NumMesiDilazione,"N");
			addSetClause($setClause,"DescrBene",$json->DescrBene,"S");
			addSetClause($setClause,"CodBene",$json->CodBene,"S");
			addSetClause($setClause,"CodTabFinanziaria",$json->CodTabFinanziaria,"S");
			addSetClause($setClause,"DataContratto",$json->DataContratto,"D");
			addSetClause($setClause,"DataDecorrenza",$json->DataDecorrenza,"D");
			addSetClause($setClause,"DataPrimaScadenza",$json->DataPrimaScadenza,"D");
			addSetClause($setClause,"DataUltimaScadenza",$json->DataUltimaScadenza,"D");
			addSetClause($setClause,"DataDBT",$json->DataDBT,"D");
			addSetClause($setClause,"DataChiusura",$json->DataChiusura,"D");
			addSetClause($setClause,"IdStatoRinegoziazione",$flagRineg,"N");
			if ($IdStatoRecupero!=FALSE)
			{
				addSetClause($setClause,"IdStatoRecupero",$IdStatoRecupero,"N");
				if ($oldStatoRecupero!=$IdStatoRecupero)
					addSetClause($setClause,"DataCambioStato","CURDATE()","G");
			}
			if ($IdClasse>0)	
				addSetClause($setClause,"IdClasse",$IdClasse,"N");
			
			addSetClause($setClause,"ABI",$json->CodABI,"N");
			addSetClause($setClause,"CAB",$json->CodCAB,"N");
			addSetClause($setClause,"IBAN",$Iban,"S");
			addSetClause($setClause,"IdFiliale",$IdFiliale,"N");
			addSetClause($setClause,"IdDealer",$IdDealer,"N");
			addSetClause($setClause,"IdTipoSpeciale",$IdTipoSpeciale,"N");
			//if ($json->dataini==NULL) $json->dataini = "2001-01-01";
			addSetClause($setClause,"DataIni","2001-01-01","D");
			//if ($json->datafin==NULL) $json->datafin = "9999-12-31";
			addSetClause($setClause,"DataFin","9999-12-31","D");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			addSetClause($setClause,"IdCompagnia",$idCompany,"N");
			addSetClause($setClause,"AutoreOverride",$json->AutoreOverride,"S");
			addSetClause($setClause,"IdResponsabile",$Responsabile,"N");
			addSetClause($setClause,"IdVenditore",$IdVenditore,"N");
			addSetClause($setClause,"IdPuntoVendita",$IdPuntoVendita,"N");
			addSetClause($setClause,"IdAttributo",$IdAttributo,"N");
			addSetClause($setClause,"ImpInteressiMora",$json->InteressiMora,"N");

			// nuovi campi dal 25/10/2012
			addSetClause($setClause,"Garanzie",$garanzie,"S");
			if (substr($json->CodContratto,0,2)=='LO') // solo per i contratti loan (leasing prende qualcosa dal partitario)
			{
				if ($IdStatoContratto!=1) { // dataDBt e impDBT non contano se arrivano per una simulazione DBT, 
					                        // nel qual caso il contratto rimane in stato attivo
					addSetClause($setClause,"DataDBT",$json->DataCalcolo,"D");
					addSetClause($setClause,"ImpDBT",$json->ImpRecupero,"N");
				}
				addSetClause($setClause,"ImpInteressiMaturati",$json->ImpMoraMatur,"N");
				addSetClause($setClause,"DataInteressiMaturati",$json->DataValidita,"D");
			}
			else // leasing: la data DBT la prende da qui, ma la cerca pure nell'elaboraz. partitario
				if ($IdStatoContratto!=1) { // dataDBt e impDBT non contano se arrivano per una simulazione DBT, 
					                        // nel qual caso il contratto rimane in stato attivo
					addSetClause($setClause,"DataDBT",$json->DataDBT,"D");
				}
			//trace("UPDATE banca $setClause WHERE contratto.IdContratto=.$IdContratto");
			//echo("UPDATE contratto $setClause WHERE contratto.IdContratto=$IdContratto<br>");
			//die();
			if (!execute("UPDATE contratto $setClause WHERE contratto.IdContratto=$IdContratto"))
			{
				writeRecordError($idImportLog,"E","UPDATE contratto $CodContratto fallito: ".getLastError(),$CodContratto);
				$flagBloccoAffido = 'C';
				return 1;
			}

			//Inserisce accessorio
			if (insertServAcc($json->servizio,"update",$IdContratto,$CodContratto)==2)
				return 2;

		}// end if Update

		// Collegamento contratto estinto -> nuovo contratto
		if ($json->CodContrEstinto>'')
			collegaContrattoEstinto($IdContratto,$json->CodContrEstinto);
		
		// Se il contratto � gi� in DBT il campo ImpDebitoResiduo deve essere messo a zero, perch� il capitale
		// residuo su rate future � gi� addebitato su rata 0
		// Idem se il contratto � estinto (anche in questo caso il debito � dato dal saldo del partitario)
		$sql = "UPDATE contratto set impdebitoresiduo=0 where (idstatocontratto in (10,12,2,3,14)
		        OR idstatocontratto=23 and idattributo in (59,71,82,83,84)) AND IdContratto=$IdContratto";
		if (!execute($sql)) {
			$flagBloccoAffido = 'C';
			return 2;
		}
		// Se il contratto � in affido (o attesa di affido) STR/LEG, mette il coeff. di svalutazione a 0.85
		$sql = "UPDATE contratto SET PercSvalutazione=0.85 WHERE idstatorecupero in (5,6,25,26)
		        AND PercSvalutazione IS NULL AND IdContratto=$IdContratto";
		if (!execute($sql)) {
			$flagBloccoAffido = 'C';
			return 2;
		}
		return 0;
	}// end try
	catch (Exception $e)
	{
		writeResult($idImportLog,"K","Errore nell'elaborazione del contratto $CodContratto: ".$e->getMessage());
		$flagBloccoAffido = 'C';
		return 2;
	} // end catch

}// end function

//==============================================================================================
// calcolaStatoRecupero
// Calcola lo stato recupero, nei casi in cui � determinato dallo stato contratto
// Per i contratti DBT azzera l'importo debito residuo, perch� gi� nel partitario.
// Annulla anche il flag rinegoziazione, se il contratto � chiuso.
//==============================================================================================
function calcolaStatoRecupero($IdContratto,$CodStatoContratto,$CodAttributo,&$capitaleResiduo,&$flagRinegoziazione)
{
	//-----------------------------------------------------------------------------------------------
	// Controlla se la rinegoziazione � avvenuta e corregge il flag di rinegoziazione
	//-----------------------------------------------------------------------------------------------
	if ($IdContratto>0) // contratto esistente
	{
		if ($CodStatoContratto=="CHI" || $CodStatoContratto=="PAP" || $CodStatoContratto=='SOS'
		||  $CodStatoContratto=="EST" || $CodStatoContratto=="ANN" || $CodStatoContratto=='EA'
		||  $CodStatoContratto=="ESO" || $CodStatoContratto=="SOI" || $CodStatoContratto=='STO')
		{
			// Contratto chiuso: controlla se ne � stato aperto uno successivo di tipo
			// piano di rientro o rifinanziamento 
			$row = getRow("SELECT IdCliente,DataChiusura FROM contratto WHERE IdContratto=$IdContratto");
			$IdCliente = $row["IdCliente"];
			$DataChiusura = $row["DataChiusura"];
			if ($DataChiusura>'2012-12-31') // prende in considerazione solo quelli dal 2013
			{
				$row1 = getRow("SELECT IdContratto,CodContratto FROM contratto c WHERE IdProdotto IN (165,236) AND IdCliente=$IdCliente AND DataContratto>('$DataChiusura'-INTERVAL 1 MONTH)"
						." AND NOT EXISTS (SELECT 1 FROM contratto WHERE  IdCliente=$IdCliente AND DataChiusura>('$DataChiusura'-INTERVAL 1 MONTH) AND DataChiusura<=c.DataContratto)"
						." ORDER BY 1 LIMIT 1");
				$codNuovoContratto 	= $row1["CodContratto"];
				$idNuovoContratto 	= $row1["IdContratto"];
				if ($codNuovoContratto>"") 
				{
					$flagRinegoziazione = 7; // rinegoziazione avvenuta 
					execute("UPDATE contratto SET IdContrattoDerivato=$idNuovoContratto WHERE IdContratto=$IdContratto");
					if (getAffectedRows()==1) {
						writeHistory("NULL","Registrata avvenuta rinegoziazione con nuovo contratto $codNuovoContratto",$IdContratto,"");		
					}
				}
				else
					$flagRinegoziazione = getScalar("SELECT IdStatoRinegoziazione FROM contratto WHERE IdContratto=$IdContratto");
			}
			else
				$flagRinegoziazione = getScalar("SELECT IdStatoRinegoziazione FROM contratto WHERE IdContratto=$IdContratto");
		}
		else
			$flagRinegoziazione = getScalar("SELECT IdStatoRinegoziazione FROM contratto WHERE IdContratto=$IdContratto");
	}
	else
		$flagRinegoziazione = null; 
	
	//-----------------------------------------------------------------------------------------------
	// Gestione delle pratiche passate in DBT su OCS
	//-----------------------------------------------------------------------------------------------
	$IdStato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='ATS'"); // stato in attesa di affido LEG/STR
	if ($CodStatoContratto=='CIM' || $CodStatoContratto=='DBT' // DBT o CM (loan)
	|| ($CodStatoContratto=='SOS' && ($CodAttributo=='LE FU' || $CodAttributo=='LE RI' || $CodAttributo=='LE AI' || $CodAttributo=='LE RF' || $CodAttributo=='LE RE')))
	{
		$capitaleResiduo = 0;
		if (!$IdContratto) // contratto non ancora presente sul DB
		{
		//non ho idcontratto	writeHistory("NULL","Pratica messa in stato 'in attesa di affidamento STR/LEG'",$IdContratto,"");		
			return $IdStato;
		}
		else // controlla che sia in uno stato che pu� essere spostato su ATS
		     // se in affido, vedi funzione revocaAgenzia in workflowFunc.php
		{
			// dal 9/8/2012, quelle in stato INT non vengono cambiate di stato
			if (rowExistsInTable("statorecupero s JOIN contratto c"
				." ON c.IdStatoRecupero=s.IdStatoRecupero",
				"IdContratto=$IdContratto AND "
				."CodStatoRecupero IN ('NOR','ATT','OPE','WRKPROPDBT','WRKINVIOCMDBT','WRKINVIOCGMDBT','WRKAPPROVDBT')"))
			{
				writeHistory("NULL","Pratica messa in stato 'in attesa di affidamento STR/LEG'",$IdContratto,"");		
				return $IdStato;
			}
			else
				return FALSE;
		}
	}
	//-----------------------------------------------------------------------------------------------
	// Gestione delle pratiche passate a perdita su OCS o cedute
	// Per il loan, se lo stato � PAP (95) con attributo LO CE o nullo --> � una cessione
	//              se lo stato � PAP (95) con altro attributo         --> � un write off
	// Per il leasing, se lo stato � CHI (90) e attributo = PP pu� essere entrambe le cose,
	// lo mette in stato CES se era in corso una proposta di cessione del credito.
	//-----------------------------------------------------------------------------------------------
	$IdStatoCeduto = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='CES'"); 
	$IdStatoWriteoff = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='WOF'"); 
	$idNuovoStato = 0;
	if ($CodStatoContratto=='PAP')  // passato a perdita  (solo loan)
	{
		if ($CodAttributo=="LO CE")
			$idNuovoStato = $IdStatoCeduto;
		else
			$idNuovoStato = $IdStatoWriteoff;
	}
	else if ($CodStatoContratto=='CHI') // chiuso, sia Loan che Leasing
	{   
		if ($CodAttributo=='LE PP') // solo leasing
		{
			// Cerca l'ultima azione del workflow cessione per questo contratto
			$codAzione = getScalar("SELECT CodAzione FROM storiarecupero sr JOIN azione a ON a.idazione=sr.idazione 
				AND CodAzione LIKE 'WF%CES%' WHERE IdContratto=0$IdContratto ORDER BY 1 DESC LIMIT 1");
			if ($codAzione>"") // c'�
				if ($codAzione != "WF_ANNULLA_CES" && $codAzione != "WF_RIF_CGM_CES")	// richiesta non annullata	
					$idNuovoStato = $IdStatoCeduto;
				else
					$idNuovoStato = $IdStatoWriteoff;
			else
				$idNuovoStato = $IdStatoWriteoff;
		}
		// forse bisognerebbe portare in stato CLO anche tutti gli altri casi, ma non so se sono tutti fuori recupero effettivamente
	}
	$IdStatoCorrente = getScalar("SELECT IdStatoRecupero FROM contratto WHERE IdContratto=0$IdContratto");
	if ($idNuovoStato>0)
	{
		if (!$IdContratto) // contratto non ancora presente sul DB
		{
			return $idNuovoStato;
		}
		else 
		{
			if (getScalar("SELECT IdAgenzia FROM contratto WHERE IdContratto=$IdContratto")>"") // affidato?
				revocaAgenzia($IdContratto,true,"REV"); // revoca
			
			if ($IdStatoCorrente!=$idNuovoStato)
			{
				writeHistory("NULL","Pratica messa 'fuori recupero' causa passaggio a perdita o cessione",$IdContratto,"");		

				// azzera anche l'eventuale data di Saldo e Stralcio, in modo che non si veda pi� nello scadenzario specifico
				execute("UPDATE contratto SET DataSaldoStralcio=NULL WHERE IdContratto=$IdContratto");

				// chiusura forzata richieste in attesa di convalids
				chiudeConvalide($IdContratto);
			}
			return $idNuovoStato;
		}
	}
	
	if (!$IdContratto) // contratto non ancora presente sul DB
		return 1; // stato 1 = non a recupero
	else // contratto gi� esistente, lascia nello stato in cui �
	{
		return $IdStatoCorrente;
	} 
}
//==============================================================================================
// collegaContrattoEstinto
// Collega un contratto estinto al contratto derivato, eventualmente sistemando lo
// stato Rinegoziazione
//==============================================================================================
function collegaContrattoEstinto($IdContratto,$CodEstinto)
{
	$row = getRow("SELECT IdContratto,IdStatoRinegoziazione FROM contratto WHERE CodContratto='$CodEstinto'");
	if (!is_array($row)) {
		trace("Contratto estinto $CodEstinto non collegato al contratto id=$IdContratto perche' non presente nel DB",false);
		trace("SELECT IdContratto,IdStatoRinegoziazione FROM contratto WHERE CodContratto='$CodEstinto'",false);
		return;
	}
	$IdEstinto = $row["IdContratto"];
	$IdStatoRinegoziazione = $row["IdStatoRinegoziazione"];
	if ($IdStatoRinegoziazione>0 && $IdStatoRinegoziazione != 7) {
		$IdStatoRinegoziazione = 7; // segna rinegoziazione effettuata
		trace("Contratto $CodEstinto segnato come rinegoziazione effettuata",false);
	} else if ($IdStatoRinegoziazione==null)
		$IdStatoRinegoziazione = "NULL";
		
	if (execute("UPDATE contratto SET IdContrattoDerivato=$IdContratto,IdStatoRinegoziazione=$IdStatoRinegoziazione WHERE IdContratto=$IdEstinto"))
		trace("Contratto estinto $CodEstinto collegato al contratto id=$IdContratto",false);
}

//==============================================================================================
// controllaPianoRientro
// Se il contratto ha un piano di rientro, aggiorna gli importi pagati, attribuendo a scalare
// la diminuzione di debito rispetto al dovuto iniziale (che � dato dal totale rate da pagare)
//==============================================================================================
function controllaPianoRientro($IdContratto)
{
	$dati = getScalar("SELECT c.ImpCapitale,p.IdPianoRientro,IFNULL(SUM(r.Importo),0) AS Totale FROM contratto c
	                   LEFT JOIN pianorientro p ON p.IdContratto=c.IdContratto
	                   LEFT JOIN ratapiano r    ON p.IdPianoRientro=r.IdPianoRientro
	                   WHERE c.IdContratto=$IdContratto");
	$totalePiano 	= $dati["Totale"];		// totale da pagare nel piano di rientro
	$idPiano        = $dati["IdPianoRientro"];  
	$debitoCapitale = $dati["ImpCapitale"]; // debito odierno su capitale (dovrebbe essere minore o uguale al totale, 
	// perch� il passaggio in DBT dovrebbe aver portato a capitale tutto il dovuto, su rata 0)
	if ($totalePiano) return TRUE;
	trace("Elabora piano di rientro contratto $IdContratto",FALSE);
	
	/* loop sulle rate del piano, per impostare la parte pagata delle prime rate */
	$rows = getFetchArray("SELECT * FROM ratapiano WHERE IdPianoRientro=$idPiano ORDER BY NumRata");
	$pagato = $totalePiano-$debitoCapitale;
    foreach ($rows as $row)
    {
    	if ($pagato<=0)
    		$valore = 0; // parte pagata di questa rata
    	else
    		$valore = ($pagato>=$row["Importo"])?$row["Importo"]:$pagato;
    	
    	if (!execute("UPDATE ratapiano SET ImpPagato=$valore WHERE IdPianoRientro=$idPiano AND NumRata=".$row["NumRata"]))
    		return FALSE; 
    	$pagato -= $valore;
    }
    return TRUE;
}

//==============================================================================================
// processControparte
// Elabora la struttura JSON con i dati delle controparti
// Argomenti:
//   1) $json			struttura dati in input
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function processControparte($json,$idCompany)
{
	global $idImportLog;
	try
	{
		$key = $json->contratto; // key per i record di errore
		if (!$key)
		{
			writeRecordError($idImportLog,"E","Campo 'contratto' non specificato in un record del file Controparti ",null);
			return 1;
		}

		// Determina ID del contratto
		$idContratto = getScalar("SELECT IdContratto FROM contratto WHERE CodContratto='$key' AND IdCompagnia=$idCompany");
		if (!($idContratto>0))
		{
			writeRecordError($idImportLog,"R","Contratto $key trovato nel file Controparti ma non presente nella tabella Contratto ",$key);
			return 1;
		}

		beginTrans();
		// cancello tutti le righe nelle tabella controparte
		if (!execute("DELETE FROM controparte WHERE IdContratto =".$idContratto))
		{
			rollback();
			writeResult($idImportLog,"K",getLastError());
			return 2;
		}
		// Loop sull'array delle controparti
		for ($i=0; $i<count($json->controparti);$i++)
		{
			$contro = $json->controparti[$i];

			// Determina l'ID del tipo controparte
			if ($contro->tipo>'')
			{
				$idTipoControparte = getScalar("SELECT IdTipoControparte FROM tipocontroparte WHERE CodTipoControparte='".$contro->tipo."'");
				if (!$idTipoControparte)
				{
					writeRecordError($idImportLog,"R","Tipo controparte ".$contro->tipo." non presente nella tabella TipoControparte ",$key);
					$idTipoControparte = 999; // usa il tipo di default (soggetto collegato)
				}
			}
			else
			{
				writeRecordError($idImportLog,"E","Tipo controparte non specificato ",$key);
				$idTipoControparte = 999; // usa il tipo di default (soggetto collegato)
			}
			// Determina l'ID del cliente
			if ($contro->cliente>'')
			{
				$idCliente = getScalar("SELECT C.IdCliente"
				." FROM cliente C JOIN clientecompagnia X ON C.IdCliente = X.IdCliente"
				." WHERE C.CodCliente='".$contro->cliente."' AND X.IdCompagnia=".$idCompany);
				if (!$idCliente)
				{
					rollback();
					writeRecordError($idImportLog,"R","Codice cliente ".$contro->cliente." non presente nella tabella Cliente ",$key);
					return 1;
				}
			}
			else
			{
				rollback();
				writeRecordError($idImportLog,"E","Codice cliente non specificato ",$key);
				return 1;
			}
			// Scrittura

			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
			addInsClause($colList,$valList,"IdCliente",$idCliente,"N");
			addInsClause($colList,$valList,"IdTipoControparte",$idTipoControparte,"N");
			addInsClause($colList,$valList,"DataIni","2001-01-01","D");
			addInsClause($colList,$valList,"DataFin","9999-12-31","D");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");

			if (!execute("INSERT INTO controparte ($colList) VALUES ($valList)"))
			{
				rollback();
				writeRecordError($idImportLog,"E","INSERT Fallita: ".getLastError(),$CodContratto);
				return 1;
			}
			commit();
		}// fine for
		return 0;
	}
	catch (Exception $e)
	{
		writeResult($idImportLog,"K","Errore nell'elaborazione di un record Controparti: ".$e->getMessage());
		return 2;
	}
}

//==============================================================================================
// getIdContratto
// Inizia l'elaborazione di un gruppo di righe JSON con i dati dei movimenti,
// ricavando l'ID del contratto
// Argomenti:
//   1) $json			struttura dati in input
//   2) IdCompagnia
// Restituisce:
//      IdContratto oppure FALSE se c'� un errore
//==============================================================================================
function getIdContratto($json,$idCompany)
{
	global $idImportLog;
	try
	{
		$key = $json->codContratto;   // key per i record di errore
		if (!$key)
		{
			writeRecordError($idImportLog,"E","Campo 'codContratto' non specificato in un record del file Contratti",null);
			return FALSE;
		}

		// Determina ID del contratto
		$idContratto = getScalar("SELECT IdContratto FROM contratto WHERE CodContratto='$key' AND IdCompagnia=$idCompany");
		if (!($idContratto>0))
		{
			trace("Contratto $key non trovato",false);
			//writeRecordError($idImportLog,"R","Contratto $key non presente nella tabella Contratto ",$key);
			return FALSE;
		}
		return $idContratto;
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione di un record: ".$e->getMessage());
		return FALSE;
	}
}

//==============================================================================================
// processMovimento
// Carica una riga del file Movimenti e gestisce le operazioni da eseguire per ciascun contratto
// al break sul codice contratto
// Argomenti:
//   1) $json			struttura dati in input
//   2) IdContratto
//   3) (byref) lastContratto   ultimo IdContratto letto
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function processMovimento($json,$IdContratto,&$lastContratto)
{
	global $idImportLog,$precrimine,$flagBloccoAffido;
	$idTipoMovimento = "";
	$tabellaMovimenti = $precrimine?"movimentoprecrimine":"movimento";
	try
	{
		if ($IdContratto != $lastContratto) // break
		{
			if ($lastContratto>"" ) // non e' la prima riga
			{
				$ret = processAndClassify($lastContratto); // esegue le elaborazioni finali sul contratto
				if ($ret==2) {
					$flagBloccoAffido = 'M';
					return 2;
				}
				aggiornaFlagBloccoAffido($lastContratto,$flagBloccoAffido);  // aggiorna il flag di blocco sul contratto
			} // fine gestione dell'esame contratto al break

			//-----------------------------------------------------------------------------------------------
			// Adesso cancella i movimenti del nuovo contratto che comincia qui, prima di riscriverli
			//-----------------------------------------------------------------------------------------------
			if (!execute("delete from $tabellaMovimenti where IdContratto=$IdContratto"))
			{
				writeResult($idImportLog,"K","DELETE $tabellaMovimenti idContratto=$IdContratto".getLastError());
				fclose($file);
				$flagBloccoAffido = 'M';
				return FALSE;
			}
			$flagBloccoAffido = 'm'; // indica "nessuna anomalia rilevata sui movimenti di questo contratto"
			
			$lastContratto = $IdContratto; // ricorda valore
		} // fine gestione del break

		//-----------------------------------------------------------------------------------------------
		// Scrittura del record movimento
		//-----------------------------------------------------------------------------------------------

		// Controllo codTipoMovimento e lettura da DB IdTipoMovimento
		if ($json->codTipoMovimento!="" ) 	// se � stato specificato  il codTipoMovimento
		{
			// ricavo IdTipoMovimento  dalla tab tipoMovimento
			$QueryStr = " SELECT M.IdTipoMovimento"
			." FROM   tipomovimento M"
			." WHERE  M.CodTipoMovimentoLegacy='".$json->codTipoMovimento."'";
			$idTipoMovimento = getScalar($QueryStr);

			if(!($idTipoMovimento>0))	//  se non ha trovato l'idTipoMovimento sul db
			{
				//  invio errore per mancanza idTipoMovimento sul db
				writeRecordError($idImportLog,"R","Tipo Movimento '$json->codTipoMovimento' non presente nella tabella TipoMovimento",$json->codContratto);
				//trace("E","IdTipoMovimento non presente nella tabella tipomovimento per il contratto ".$IdContratto);
				//return 1;
				$idTipoMovimento=1; // usa un tipo generico di default, per non perdere il movimento
				$flagBloccoAffido = 'M';
			}
		}

		// Controllo codTipoPartita e lettura da DB IdTipoMovimento
		if ($json->codTP!="" ) 	// se � stato specificato  il tipo partita
		{
			$QueryStr = " SELECT IdTipoPartita FROM tipopartita"
				." WHERE CodTipoPartitaLegacy='".$json->codTP."'";
			$idTipoPartita = getScalar($QueryStr);

			if(!($idTipoPartita>0))	{ //  se non trovato
				writeRecordError($idImportLog,"R","Tipo Partita '$json->codTP' non presente nella tabella TipoPartita",$json->codContratto);
				$flagBloccoAffido = 'M';
			}
		}
		
		// Controllo codInsoluto e lettura da DB IdTipoInsoluto
		if($json->codInsoluto!="" ) 	// se � stato specificato  il codInsoluto
		{
			// ricavo IdTipoMovimento  dalla tab tipoMovimento
			$QueryStr = " SELECT IdTipoInsoluto"
			." FROM   tipoinsoluto"
			." WHERE  CodTipoInsoluto='".$json->codInsoluto."'";
			$idTipoInsoluto = getScalar($QueryStr);

			if (!($idTipoInsoluto>0)) 	//  se non ha trovato l'idTipoMovimento sul db
			{
				writeRecordError($idImportLog,"R","Tipo Insoluto '$json->codInsoluto' non presente nella tabella TipoInsoluto",$json->codContratto);
			}
		}

		// Insert Movimento
		$colList = ""; // inizializza lista colonne
		$valList = ""; // inizializza lista valori

		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"NumRata",$json->numRata,"N");
		addInsClause($colList,$valList,"NumRiga",$json->numRiga,"N");
		addInsClause($colList,$valList,"DataRegistrazione",$json->dataRegistrazione,"D");
		addInsClause($colList,$valList,"DataCompetenza",$json->dataCompetenza,"D");
		addInsClause($colList,$valList,"DataDocumento",$json->dataDocumento,"D");
		addInsClause($colList,$valList,"NumDocumento",$json->numDocumento,"N");
		addInsClause($colList,$valList,"DataScadenza",$json->dataScadenza,"D");
		addInsClause($colList,$valList,"DataValuta",$json->dataValuta,"D");
		addInsClause($colList,$valList,"IdTipoMovimento",$idTipoMovimento,"N");
		addInsClause($colList,$valList,"IdTipoPartita",$idTipoPartita,"N");
		addInsClause($colList,$valList,"IdTipoInsoluto",$idTipoInsoluto,"N");
		addInsClause($colList,$valList,"Importo",str_replace(',','.',$json->importo),"N");
		addInsClause($colList,$valList,"LastUser","import","S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");

		if (!execute("INSERT INTO $tabellaMovimenti ($colList) VALUES ($valList)"))
		{
			writeResult($idImportLog,"K",getLastError());
			$flagBloccoAffido = 'M';
			return 2;
		}
		
		//---------------------------------------------
		// Aggiunta campi DBT per leasing 25/10/2012
		//---------------------------------------------
		if ($idTipoMovimento==339) // � "Apertura sofferenza leasing" che stabilisce l'importo del DBT
		                           // per i contratti leasing
		{
			$setClause = "";
			addSetClause($setClause,"DataDBT",$json->dataRegistrazione,"D");
			addSetClause($setClause,"ImpDBT",str_replace(',','.',$json->importo),"N");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			if (!execute("UPDATE contratto $setClause WHERE IdContratto=$IdContratto"))
			{
				writeResult($idImportLog,"K",getLastError());
				$flagBloccoAffido = 'M';
				return 2;
			}
		}
		else if ($idTipoMovimento==121) // decadenza beneficio del termine per i Loan DBT
		{
			// se la data DBT � nulla (su alcuni contratti DBT accade per errori su OCS)
			// usa quella del movimento (non l'importo, che non contiene gli insoluti su rate passate)
			if (rowExistsInTable("contratto","DataDBT IS NULL AND IdContratto=$IdContratto"))
			{
				$setClause = "";
				addSetClause($setClause,"DataDBT",$json->dataRegistrazione,"D");
				addSetClause($setClause,"LastUser","import","S");
				addSetClause($setClause,"LastUpd","NOW()","G");
				if (!execute("UPDATE contratto $setClause WHERE IdContratto=$IdContratto"))
				{
					writeResult($idImportLog,"K",getLastError());
					$flagBloccoAffido = 'M';
					return 2;
				}
			}
		}
	}
	catch (Exception $e)
	{
		writeResult($idImportLog,"K","Errore nell'elaborazione di un record $tabellaMovimenti: ".$e->getMessage());
		$flagBloccoAffido = 'M';
		return 2;
	}

	return 0;
}

//==============================================================================================
// processAndClassify
// Esamina gli insoluti e classifica una pratica
// dopo aver caricato tutti i movimenti di un contratto
// Argomenti:
//   1) IdContratto
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//      3:  passato a Positivo nessuna operazione necessaria
//==============================================================================================
function processAndClassify($IdContratto)
{
	global $idImportLog;
	global $listaClienti,$precrimine;

	//-------------------------------------------------------------------------------------------
	// Gestione 'insoluti' precrimine
	//-------------------------------------------------------------------------------------------
	if ($precrimine)
	{
		trace("Esame insoluti precrimine contratto id=$IdContratto",false);
		$ret = processInsolutiPrecrimine($IdContratto); // elabora le informazioni sugli insoluti
		trace("Fine esame insoluti precrimine contratto id=$IdContratto, ret=$ret",false);
		return $ret;
	}

	//-------------------------------------------------------------------------------------------
	// Gestione 'insoluti' standard
	//-------------------------------------------------------------------------------------------
	trace("Esame insoluti contratto id=$IdContratto",FALSE);
	$ret = processInsoluti($IdContratto,$dataRif); // elabora le informazioni sugli insoluti

	trace("processInsoluti ha ritornato $ret",FALSE);
	if ($ret == 0) // ci sono insoluti
	{
		if (!segnaRecidivo($IdContratto)) // segna FlagRecupero=Y se pi� di 1 insoluto
		{
			if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
			return 2;
		}

		$IdClasse = classify($IdContratto,$changed); // classificazione contratto
		if ($IdClasse===FALSE) // Fallita
		{
			if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
			return 2;
		}
		if ($changed && $IdClasse>0)
		{
			eseguiAutomatismiPerAzione('CCL',$IdContratto); // esegue invio SMS differito o prep. lettere
		}

		// Segna il cliente, per poi processare gli affidi (con la gestione anche delle flotte)
		$IdCliente = getScalar("SELECT IdCliente FROM contratto WHERE IdContratto=$IdContratto");
		$listaClienti[$IdCliente] = $IdCliente;

		// 27/9/2011: mette in attesa di affido la pratica, se previsto dalla classificazione
		metteInAttesa($IdContratto);
		return 0;
	} // fine if processInsoluti ha ritornato 0
	else if ($ret == 3) // ret 3 corrisponde a contratto divenuto positivo
		return 3;
	else
		return 2; // andata male
}

//==============================================================================================
// processInsoluti
// Elabora l'insieme di movimenti di un contratto per creare/aggiornare le righe nella tabella
// insoluto, richiamando poi i motori di classificazione, assegnazione e affidamento
// Argomenti:
//   1) IdContratto
//   2) data di cutoff (oltre la quale non si considerano i movimenti)
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//      3:  passato a Positivo nessuna operazione necessaria? MS 20110406
//==============================================================================================
function processInsoluti($IdContratto,$dataRif=NULL)
{
	global $idImportLog,$dataFile;
	try
	{
		//------------------------------------------------------------------------------------------
		// Marca le righe degli insoluti, in modo da cancellare quelle residue dopo update
		//------------------------------------------------------------------------------------------
		if (!execute("UPDATE insoluto SET LastUser='delete' WHERE IdContratto=$IdContratto"))
		{
			if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
			return 2;
		}

		//------------------------------------------------------------------------------------------
		// Legge l'elenco dei numeri di rata che hanno data di scadenza non nel futuro oppure
		// che hanno movimenti d'incasso (le rate future LOAN sono create tutte ad inizio contratto
		// e non vanno considerate, a meno che non siano incassate, il che avviene per pagamenti
		// anticipati e nel caso di estinzione anticipata).
		// Dalla versione 0.9.10, anche gli incassi vengono contati solo se con datavaluta non futura
		// per non conteggiare i movimenti autogenerati per spostamento rate nel futuro
		// 5/10/2011: se esistono insoluti veri (categoria mov.=X) nel futuro, considera fino a
		//            quella data
		//------------------------------------------------------------------------------------------
		if ($dataRif==NULL)
			if ($dataFile==NULL)
				$dataRif = ISODate(time()-24*3600); // data di riferimento: ieri
			else
				$dataRif = ISODate($dataFile);
		else
			$dataRif = ISODate($dataRif);

		$dataUltimoInsoluto = getScalar("SELECT MAX(IFNULL(DataScadenza,DataValuta)) FROM movimento m JOIN tipomovimento t ON m.IdTipoMovimento=t.IdTipoMovimento"
			." WHERE m.IdContratto=$IdContratto AND CategoriaMovimento='X'");
		$dataUltimoInsoluto = ISODate($dataUltimoInsoluto);
		if ($dataUltimoInsoluto>$dataRif)
		{
			$dataLimite = $dataUltimoInsoluto;
			trace("Data limite spostata al $dataLimite perche' presente un insoluto preregistrato",FALSE);
		}
		else
			$dataLimite = $dataRif;

		$sql = "SELECT DISTINCT NumRata FROM movimento WHERE IdContratto=$IdContratto"
		." AND (DataScadenza <= '$dataLimite' OR Importo<0 AND DataValuta<='$dataLimite'"
		." OR idtipomovimento in (select idtipomovimento from tipomovimento where categoriamovimento='X')"
		.") ORDER BY 1";
		$rate = fetchValuesArray($sql);
		if (count($rate)==0) // non pu� verificarsi a meno di errore su DB, visto quando viene chiamata questa funzione
		{
			trace("Nessuna rata elaborabile individuata con la query precedente",FALSE);
			//if ($idImportLog>0) writeResult($idImportLog,"K","Nessuna rata elaborabile individuata per il contratto $IdContratto: ".getLastError());
			return 0;
		} else {
			trace("Individuate da elaborare le rate dalla n. {$rate[0]} alla n. {$rate[count($rate)-1]}",false);
		}

		//------------------------------------------------------------------------------------------
		// Ottimizzazione, legge tutte le righe di insoluto per questo contratto, per usarle poi
		// rata per rata
		//------------------------------------------------------------------------------------------
		unset($insArray);
		$rows = getFetchArray("SELECT * FROM insoluto WHERE IdContratto=$IdContratto");
        foreach ($rows as $ins)
        	$insArray[$ins["NumRata"]] = $ins;		
		unset($rows);
        	
		beginTrans();  // INIZIO TRANSAZIONE

		$dati = getRow("SELECT ImpRata,ImpRataFinale,ImpSpeseIncasso,IdAgenzia,DataInizioAffido,DataFineAffido,ImpSaldoStralcio,IdStatoRinegoziazione FROM contratto WHERE IdContratto=$IdContratto");
		$ImpRataNormale = $dati["ImpRata"]+$dati["ImpSpeseIncasso"];
		$ImpRataFinale  = $dati["ImpRataFinale"]+$dati["ImpSpeseIncasso"];
		$dataScadenzaRataPrecedente = 0;
		//------------------------------------------------------------------------------------------
		// Loop su ciascun numero di rata, cio� su ciascuna partita elementare
		// (la rata zero per� comprende movimenti di tipo vario)
		//------------------------------------------------------------------------------------------
		$saldo = 0; // saldo totale di tutte le rate
		$dataScadenzaRataPrecedente = "";
		foreach ($rate as $NumRata)
		{
			//--------------------------------------------------------------------------------------
			// Legge in ordine le righe della partita, per determinare la data di scadenza
			// (ultima data in cui si � passati da credito a debito)
			//--------------------------------------------------------------------------------------
			$sql = "SELECT m.*,CategoriaMovimento,m.IdTipoInsoluto,CategoriaPartita"
			." FROM movimento m LEFT JOIN tipomovimento tm ON m.IdTipoMovimento=tm.IdTipoMovimento"
			." LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto"
			." LEFT JOIN tipopartita tp ON m.IdTipoPartita=tp.IdTipoPartita"
			." WHERE IdContratto=$IdContratto AND NumRata=$NumRata"
			." ORDER BY IdMovimento";
			//." ORDER BY DataScadenza,DataRegistrazione,NumRiga";
			$movimenti = getFetchArray($sql);
			if (count($movimenti)==0) // non pu� verificarsi a meno di errore su DB, visto quando viene chiamata questa funzione
			{
				if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
				return 2;
			}
			$curr = 0; // somma corrente
			$impInt = 0; // importo interessi di mora
			$impSpese  = 0; // importo spese di recupero
			$impAltri  = 0; // importo altri addebiti
			$impCapitale = 0; // importo debito su rate
			$incasso = 0; // incassi da cliente
			$lastCausale = "";
			$dataInsoluto = "";
			$dataInsolutoVero = "";
			$eraDataInsoluto = "";
			$rataCerta = FALSE;
			$ultimoIncasso = 0;
			$primoMovInsoluto = true; // true finch� non si incontra un movimento di categoria X (insoluto RID)
			$veroDebito = false;
			$veroPagamento = false;
			//$eraCapitale = 0;
			foreach ($movimenti as $mov)            // Loop sui movimenti di questa singola rata
			{
				// Nuova gestione (con il tipo partita)
				$catP = $mov["CategoriaPartita"];
				$catMov  = $mov["CategoriaMovimento"];
				$importo = $mov["Importo"];         // importo a debito (se positivo) o credito (se negativo)
				//--------------------------------------------------------------------------------------------
				// Nuova gestione dal 26/1/2011: usa il tipo partita
				//--------------------------------------------------------------------------------------------
				if ($catP>"") // � presente il tipo partita
				{
					$nuovaGestione = true;
					switch ($catP)
					{
						case 'C': // movimento su capitale
							$veroDebito = true;
							if ($importo>0) // addebito su capitale
							{	
								if ($catMov=='S' // storno di un incasso
								|| $catMov=='X'  // insoluto: storna l'incasso e segna
								|| $catMov=='A') // annullamento (ad es. DBT, estinz. ecc.): non dovrebbe capitare con importo>0
								{
									$incasso -= $importo;
								}
								else // quota capitale vera e propria
								{
									$impCapitale += $importo;
									$dataInsoluto = $mov["DataScadenza"]?$mov["DataScadenza"]:$mov["DataCompetenza"];
									// dal 20/12/2012 prende per buona l'ultima data di scadenza capitale incontrata
									//                se il saldo fin l� � zero (quindi tutto il pregresso � stornato/annullato
									if ($dataInsolutoVero<$dataInsoluto || $curr == 0) // data spostata in avanti (oppure primo nuovo addebito) 
										$dataInsolutoVero = $dataInsoluto;
										
									//if ($eraCapitale==0)
									//	$eraCapitale = $capitale;
								}
							} // fine partita C, IF importo>0
							else // importo<0: accredito su capitale
							{
								if ($catMov=='S' // storno di un addebito (ad es. per cambio data)
								|| $mov["IdTipoMovimento"]==1 && $importo+$impCapitale<0.01) // Registrazione generica usata come storno
								{
									$impCapitale += $importo;
									if ($impCapitale<=0.001) // capitale azzerato, azzera anche la data scadenza, in attesa del prossimo addebito
										$dataInsolutoVero=0;
								}
								else if ($catMov=='X')	// insoluto a credito: non dovrebbe mai capitare, ma lo tratta come S
								{
									trace("Movimento di categoria X (insoluto) con importo<0: trattato come uno storno di addebito (contratto $IdContratto, numRata=$NumRata)",FALSE);
									$impCapitale += $importo;
								}	
								else // accredito su quota capitale vero e proprio, cio� incasso o scarico RID
								{
									$incasso -= $importo; // incasso tiene conto dei soli incassi su capitale
									$ultimoIncasso = -$importo;
									if ($catMov=='P') // � un "pagamento" su capitale vero e proprio, non uno scarico RID,
									                  // n� un annullamento per DBT, cessione ecc.
									{
										$veroPagamento = true;
									}
									else // � uno scarico RID o qualcosa di simile (anche una nota di credito)
									{
										if ($incasso>=$impCapitale) // azzera il capitale dovuto
											$veroDebito = false; // presume che sia una rata normalmente positiva, fino a qui
									}
								}
							}
							break;
						case 'I': // movimento su interessi di mora
							$impInt += $importo;
							break;	
						case 'R': // movimento su spese di recupero
							$impSpese += $importo;
							break;	
						case 'A': // movimento su altri addebiti
						default:
							if ($catMov=='R') // partite varie, ma un tipo movimento "spese di recupero"
								$impSpese += $importo;
							else
								$impAltri += $importo;
							break;	
					}
				}
				//--------------------------------------------------------------------------------------------
				// Vecchia gestione fino al 25/1/2011: usa il tipo movimento 
				//--------------------------------------------------------------------------------------------
				else
				{
					$nuovaGestione = false;
					if ($importo>0 && $catMov=='C') // addebiti CAPITALE
					{
						if (!$rataCerta) // ancora non incontrata un importo di rata certa
						{
							$impCapitale += $importo;
							//			trace("Aggiunto a capitale $importo",FALSE);
							if ($impCapitale == $ImpRataFinale || $impCapitale == $ImpRataNormale )
								$rataCerta = TRUE; // non serve pi� cercare quant'� la rata
						}
					}
					if ($curr<=0.001 && $curr + $importo>0.001) // questo movimento fa diventare debito
					{
						$dataInsoluto = $mov["DataScadenza"]?$mov["DataScadenza"]:$mov["DataCompetenza"];
						// dal 20/1/2012 $dataInsolutVero contiene la max data insoluto determinata dall'analisi delle righe
						// che producono un debito; in questo modo si considera sempre la data di emissione rata massima, anche
						// se l'ordine di registrazione � ingannevole come nella pratica con id=42857 rata n.3
						if ($dataInsolutoVero<$dataInsoluto)
							$dataInsolutoVero = $dataInsoluto;
					}
					if ($importo<0 && $catMov=='P') // tipo mov. INCASSO
					{
						//		trace("Aggiunto a incasso ".(-$importo),FALSE);
						$incasso -= $importo;
						$ultimoIncasso = -$importo;
					}
					// storni
					if (round($importo,2)==-round($impCapitale,2) && $catMov=='S' && !$rataCerta) // storno della rata
					{
						$impCapitale = 0;
					}
	
					// Se � uno storno che accredita, pu� servire a spostare il debito nel futuro,
					// allora non considera pi� la data insoluto impostata prima, in modo che si prenda la data
					// dell'eventuale mov. di spostamento in avanti
					// La terza condizione considera pure lo scarico RID vero e proprio
					// 29/9/2011: ricorretto per considerare come storno incasso anche i movimenti di categoria X,
					//            distinguendo per� l'insoluto RID puro e semplice
					// 1/12/2011: modificato ulteriormente per considerare i movimenti tipo X (insoluti)
					//            solo come eventuale storno dell'ultimo incasso
					if ($incasso>0 && round($importo,2)<=round($incasso,2) && $catMov=='S'   // � uno storno esplicito, anche parziale
					||  $incasso>0 && round($importo,2)<=round($ultimoIncasso,2) && $catMov=='X' && !$primoMovInsoluto) // oppure un mov. di tipo "insoluto", ma successivo al primo
					{                             // (evita di cancellare incasso registrato prima di insoluto rid originario)
						trace("rata $NumRata: storno incasso da $incasso a ".round($incasso-$importo,2),FALSE);
						$incasso = round($incasso-$importo,2);
					}
					if ($catMov=='X') // movimento di tipo "insoluto RID"
						$primoMovInsoluto = false; // spegne flag
				} // fine else vecchia gestione
				
				if ($mov["IdTipoInsoluto"]>0) // conserva ultima causale insoluto
					$lastCausale = $mov["IdTipoInsoluto"];
						
				$curr += $importo;

				//echo "numRata=$NumRata catMov=$catMov capitale=$impCapitale incasso=$incasso curr=$curr\n";
			} // fine loop sui movimenti della rata corrente
			
			// Se il capitale � zero a causa di storni
			
			// Arrotonda per evitare importi quasi 0 (nnnn E-14)
			$curr  = round($curr,2);
			//$impInt = round($impInt,2);
			$impCapitale = round($impCapitale,2);
			$incasso = round($incasso,2);
			$impInt = round($impInt,2);
			$impSpese = round($impSpese,2);
			$impAltri = round($impAltri,2);

			//--------------------------------------------------------------------------------------------
			// A causa della determinazione approssimativa di quali causali indicano una rata
			// e quali un pagamento, avviene abbastanza spesso che la rata venga contata pi� volte
			// e il pagamento meno volte del necessario. Nel caso (errato) pi� frequente,
			// 	capitale = multiplo di insoluto, e pagato=0. In questo caso, aggiusta l'importo capitale.
			//--------------------------------------------------------------------------------------------
//			if (!$nuovaGestione)
//			{
				if ($curr>0) // c'� un debito
				{
					if ($impCapitale>$curr*1.9 && $incasso==0) // capitale molto maggiore di ins. con pagam=0
					{
						if (round($impCapitale/$curr,2)==0 ) // capitale multiplo esatto dell'insoluto
							$impCapitale = $curr;  // imposta il capitale 'reale'
						// meno frequentemente, il capitale � moltiplicato ma c'� anche un residuo di spese
						// che fa s� che l'insoluto non sia un sottomultiplo esatto del capitale
						else
						{
							$newCap = $impCapitale/round($impCapitale/$curr,0); // sottomultiplo esatto
							if ($curr-$newCap>=0 && $curr-$newCap<10) // insoluto residuo � piccolo
								$impCapitale = $newCap; // imposta il capitale 'reale'
						}
					}
				}
	
				//----------------------------------------------------------------------------------
				// Applica ragionamenti simili al campo ImportoPagato
				//----------------------------------------------------------------------------------
				if ($impCapitale>0 && $incasso>$impCapitale*1.9)
				{
					if (round($incasso/$impCapitale,2)==0 && $curr>=0) // multiplo esatto e non a credito
					{
						$incasso = $impCapitale;
						trace("Forzata impostazione ImpPagato perche' multipla del capitale",FALSE);  
					}
				}
//			}

			//-------------------------------------------------------------------------------------
			// Se la partita � a debito, inserisce o aggiorna la riga in "Insoluto"
			// Se � a saldo 0 e la riga non � in Insoluto, la ignora; altrimenti la aggiorna.
			// Se � a saldo positivo la inserisce o aggiorna (serve a calcolare il bilancio totale)
			//-------------------------------------------------------------------------------------
			$dataInsoluto = ($dataInsolutoVero=="")?$dataInsoluto:$dataInsolutoVero;
			// Ottimizzazione 11/7/2012: legge tutte le righe di insoluto prima
			//$ins = getRow("SELECT * FROM insoluto WHERE IdContratto=$IdContratto AND NumRata=$NumRata");
			$ins = $insArray[$NumRata];
			if ($curr > 0 && ISODate($dataInsoluto)<=$dataLimite) // Insoluto in una data non futura
				$oper = ($ins==NULL)?"INS":"UPD";
			else if ($curr < 0) // residuo a credito: scrive perch� serve al totale
				$oper = ($ins==NULL)?"INS":"UPD";
			else if ($curr == 0) // saldo zero
				$oper = ($ins==NULL)?"":"UPD";
			else
				$oper = "";

			// Se � una rata positiva, ma contiene un vero pagamento, deve essere contata  come rata
			// viaggiante, nel caso in cui la pratica sia in affido, anche se � arrivata gi� positiva.
			// In questo caso, quindi deve essere scritta in modo opportuno su Insoluto (come se fosse
			// stata impagata e poi pagata)
			$special = false;
			if ($oper!="UPD" && $NumRata!=0 && $veroDebito && $veroPagamento && $curr<=0 && $dati["IdAgenzia"]>0
			&& ISODate($dataInsoluto)>=ISODate($dati["DataInizioAffido"]) )
			{
				// 17 mag 2012: controlla che la stessa rata non sia gi� stata archiviata come positiva in un lotto
				//              precedente (altrimenti ogni volta che esamina la stessa rata in un nuovo lotto
				//              la storicizza di nuovo). Questa soppressione pu� causare il mancato conteggio in casi
				//              particolari (incasso precedente stornato e riavvenuto pi� tardi, con rata positiva a inizio affido) 
				//              ma i pro sono pi� dei contro
				
				if (!rowExistsInTable("storiainsoluto",
					"IdContratto=$IdContratto AND NumRata=$NumRata AND CodAzione='POS' AND ImpPagato=".str_replace(',','.',$incasso). 
				     " AND ImpCapitale=".str_replace(',','.',$impCapitale)." AND DataFineAffido<'".ISODate($dati["DataInizioAffido"])."'"))
				{
					$oper = "INS";
					$special = true;
					trace("Rata n. $NumRata gia' positiva registrata comunque perche' da considerare viaggiante",FALSE);
				}
			}
				
			//			trace("Decide se scrivere la rata $NumRata su Insoluto: importo=$curr, dataInsoluto=$dataInsoluto,dataRif=$dataRif, oper=$oper",FALSE);
			if ($oper=="INS") // nuova riga in insoluto
			{
				$saldo += $curr;
				// Insert Insoluto
				$colList = ""; // inizializza lista colonne
				$valList = ""; // inizializza lista valori
				addInsClause($colList,$valList,"IdTipoInsoluto",$lastCausale,"N");
				addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
				addInsClause($colList,$valList,"NumRata",$NumRata,"N");
				addInsClause($colList,$valList,"DataInsoluto",$dataInsoluto,"D");
				// La data di arrivo � uguale alla data di oggi nelle elaborazione ordinarie, mentre � uguale alla dataRif passata se si tratta di
				// elaborazioni straordinarie eventualmente basate su dati pregressi
				// 26/10/2011: DataArrivo viene mantenuto solo per verifica, ma non deve pi� essere usato
				//             (viene usato IdAffidamento per gli stessi scopi)
				if ($dataRif>=ISODate(mktime(0,0,0,date("m"),date("d")-3,date("Y")))) // normale elaborazione giornaliera (considerando anche il gap del week end)
					addInsClause($colList,$valList,"DataArrivo","CURDATE()","G");
				else
					addInsClause($colList,$valList,"DataArrivo",$dataRif,"D"); //NB: � proprio dataRif (la data di arrivo del partitario vedi ProcessAndClassify)

				// L'IdAffidamento � nullo, in questo momento, a meno che non si tratti di un nuovo arrivo su una rata che �
				// gi� in affido ma � stata spostata nelle positivit� (storiainsoluto) e cancellata da Insoluto
				// In questo caso c'� un'altra conseguenza: se si tratta in realt� di un credito, verrebbe prodotta una nuova
				// riga positiva che, se non ci sono altri debiti maggiori, finisce subito dopo per essere ri-storicizzata 
				// in storiaRecupero, con conseguenze spesso non correttamente interpretabili. Perci� in questo caso, invece che
				// aggiungere una riga a insoluto, va a modificare la riga gi� storicizzata
			
				$row = getRow("SELECT * FROM storiainsoluto WHERE IdContratto=$IdContratto AND DataFineAffido>=CURDATE() AND NumRata=$NumRata AND CodAzione='POS'");
				$IdAffidamento = $row["IdAffidamento"];
				if ($IdAffidamento>0)
				{
					addInsClause($colList,$valList,"IdAffidamento",$IdAffidamento,"N");
					trace("IdAffidamento=$IdAffidamento ereditato da rata positiva n. $NumRata gi� storicizzata",FALSE);
				}

				if ($oper=="INS") // se non annullata poco sopra
				{
					addInsClause($colList,$valList,"LastUser","import","S");
					addInsClause($colList,$valList,"ImpCapitale",$impCapitale,"N");
					addInsClause($colList,$valList,"ImpPagato",$incasso,"N"); // importo GIA' pagato su capitale
					addInsClause($colList,$valList,"ImpInteressi",$impInt,"N"); 
					addInsClause($colList,$valList,"ImpSpeseRecupero",$impSpese,"N"); 
					addInsClause($colList,$valList,"ImpAltriAddebiti",$impAltri,"N"); 
					addInsClause($colList,$valList,"ImpInsoluto",$curr,"N"); // Att.ne: questo � il debito residuo a saldo dei pagamenti

					// se la stessa rata esiste gi� storicizzata come positiva, prende il debito iniziale e il capitale affidato da l�
					// altrimenti sono entrambi vuoti
					$storia = getRow("SELECT s.ImpInsoluto,s.ImpCapitaleDaPagare FROM storiainsoluto s JOIN contratto c ON c.IdContratto=s.IdContratto AND s.DataFineAffido=c.DataFineAffido"
					                ." WHERE s.IdContratto=$IdContratto AND s.NumRata=$NumRata AND CodAzione='POS'");
					if (is_array($storia))
					{
						$impDebitoIniziale = $storia["ImpInsoluto"];
						$impCapitaleAffidato = $storia["ImpCapitaleDaPagare"];
						if ($impDebitoIniziale!==NULL)	
						{
							addInsClause($colList,$valList,"ImpDebitoIniziale",$impDebitoIniziale,"N");  
							trace("Copiato debito iniziale $impDebitoIniziale da riga gi� storicizzata per rata n. $NumRata",FALSE);
						}
						else if ($special) // falsifica per considerare come rata viaggiante
							addInsClause($colList,$valList,"ImpDebitoIniziale",$impCapitale,"N");  
						else
							addInsClause($colList,$valList,"ImpDebitoIniziale",$curr,"N");  
						if ($impCapitaleAffidato!==NULL)	
						{   
							addInsClause($colList,$valList,"ImpCapitaleAffidato",$impCapitaleAffidato,"N");  
							trace("Copiato capitale affidato iniziale $impCapitaleAffidato da riga gi� storicizzata per rata n. $NumRata",FALSE);
						}
						else if ($special) // falsifica per considerare come rata viaggiante
							addInsClause($colList,$valList,"ImpCapitaleAffidato",$impCapitale,"N");  
						else
							addInsClause($colList,$valList,"ImpCapitaleAffidato",round($impCapitale-$incasso,2),"N");  
					}
					else // non registrata su storiaInsoluto
					{
						if ($special) // falsifica per considerare come rata viaggiante
						{
						 	addInsClause($colList,$valList,"ImpDebitoIniziale",$impCapitale,"N");
							addInsClause($colList,$valList,"ImpCapitaleAffidato",$impCapitale,"N");
						}
						else
						{
						 	addInsClause($colList,$valList,"ImpDebitoIniziale",$curr,"N");
							addInsClause($colList,$valList,"ImpCapitaleAffidato",
							($impCapitale-$incasso>0 && $curr>0)?round($impCapitale-$incasso,2):0,"N");
						}
					}
					//trace("INSERT INTO insoluto ($colList) VALUES ($valList)",FALSE);
					if (!execute("INSERT INTO insoluto ($colList) VALUES ($valList)"))
					{
						rollback();
						if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
						return 2;
					}
				}
			}
			// Insoluto gi� presente, esegue UPDATE
			else if ($oper=="UPD")
			{
				$saldo += $curr;
				//-------------------------------------------------------------------------------------
				// Rileva e segnala l'eventuale incasso
				//-------------------------------------------------------------------------------------
				if (round($ins["ImpInsoluto"]-$curr,2)>0 && $incasso>$ins["ImpPagato"]) // L'importo da pagare � diminuito e ho riconosciuto qualcosa come incasso
				{
					if ($curr<=0)
						$msg = "Incasso rata $NumRata a saldo";
					else if ($incasso>=$impCapitale) // il capitale � saldato
						$msg = "Incasso rata $NumRata a copertura del capitale";
					else
						$msg = "Incasso rata $NumRata parziale";
					writeHistory("NULL","$msg (".round($incasso-$ins["ImpPagato"],2)." euro)",$IdContratto,"");
				}
				//-------------------------------------------------------------------------------------
				// Aggiorna la riga di Insoluto
				//-------------------------------------------------------------------------------------
				$setClause ="";
				addSetClause($setClause,"ImpCapitale",$impCapitale,"N");
				addSetClause($setClause,"ImpPagato",$incasso,"N");
				addSetClause($setClause,"ImpInteressi",$impInt,"N");
				addSetClause($setClause,"ImpSpeseRecupero",$impSpese,"N");
				addSetClause($setClause,"ImpAltriAddebiti",$impAltri,"N");
				addSetClause($setClause,"ImpInsoluto",$curr,"N");
				addSetClause($setClause,"DataInsoluto",$dataInsoluto,"D");

				// NB: il campo "ImpDebitoIniziale" viene impostato solo dall'INSERT e aggiornato solo al riaffido (affidaAgenzia)
				// Se arriva un addebito che incrementa il debito, veniva corretto anche questo campo, ma dal 24/1/2012
				// questo � soppresso: ai fini delle provvigioni conta mantenere il debito iniziale cos� com'era
				// quando l'affido � avvenuto
//				if ($ins["ImpInsoluto"]<$curr) // l'insoluto � aumentato: aumenta anche quello su cui si calcola il recupero
//					addSetClause($setClause,"ImpDebitoIniziale",$ins["ImpDebitoIniziale"]+$curr-$ins["ImpInsoluto"],"N");

				addSetClause($setClause,"LastUser","import","S");
				//trace("UPDATE insoluto $setClause WHERE IdContratto=$IdContratto AND NumRata=$NumRata",FALSE);
				if (!execute("UPDATE insoluto $setClause WHERE IdContratto=$IdContratto AND NumRata=$NumRata"))
				{
					rollback();
					if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
					return 2;
				}
			}
			
			//--------------------------------------------------------------------------------
			// Cerca di capire se deve essere marcato come Completato accodamento
			// confr
			//--------------------------------------------------------------------------------
			$statoRine = $dati["IdStatoRinegoziazione"];
			if (($statoRine==3 || $statoRine==6) //  accodamento avviato e non concluso
			&& $oper>"" && $NumRata>0)  // insoluto e su rata diversa da zero
			{
				if ($dataInsoluto<$dataScadenzaRataPrecedente  // questa rata scade prima della precedente		
				&& ISODate($dataInsoluto) >= date("Y-m-d"))    // e sono entrambe nel futuro
				{ // significa che la rata precedente � stat aaccodata
					if (execute("UPDATE contratto SET IdStatoRinegoziazione=8 WHERE IdContratto=$IdContratto"))
					{
						$numRataSpostata = $NumRata-1;
						writeHistory("NULL","Registrato avvenuto accodamento (rilevato sposamento rata $numRataSpostata)",$IdContratto,"");		
					}
				}
			}
			$dataScadenzaRataPrecedente = $dataInsoluto;			
		}  // fine foreach su ciascun numero rata

		//------------------------------------------------------------------------------------------
		// Cancella le righe residue degli insoluti, marcate in precedenza
		// NB: dato che i movimenti dovrebbero essere TUTTI, se rimangono righe insoluti non trattate
		//     significa che erano errate, ad es. se i movimenti sono stati corretti alla fonte
		//------------------------------------------------------------------------------------------
		if (!execute("DELETE FROM insoluto WHERE LastUser='delete' AND IdContratto=$IdContratto"))
		{
			rollback();
			if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
			return 2;
		} else {
			trace("Eliminate ".getAffectedRows()." righe di insoluto con LastUser='delete'",false);
		}

		//-------------------------------------------------------------------------------------------
		// Se la somma totale copre l'intero debito, indipendentemente da come � costruito
		// (ad es. anche nel caso di saldo cumulativo), mette il contratto in positivit� e storicizza
		// tutti i suoi insoluti
		//------------------------------------------------------------------------------------------
		$saldo = round($saldo,2); // saldo di tutte le rate
		trace ("Saldo calcolato su tutte le rate prese in considerazione: $saldo",FALSE);
		// la positivit� sotto 26 euro non � corretta; si tratta semplicemente di non affidare sotto i 26 euro e a questo
		// provvede la soglia sulla tabella classificazione. Perci� va in positivit� solo se zero.
		if ($saldo<=0)
		{
			trace("Positivo per saldo<=0",FALSE);
			$totale = TRUE;
			if (rendePositivo($IdContratto,$totale)) // TRUE significa "totalmente positivo: storicizza tutto"
			{
				commit();
				return 3;        // tutto ok, segnala nessuna squadratura
			}
			else
			{
				rollback();
				if ($idImportLog>0) writeResult($idImportLog,"K","Fallito passaggio della pratica in positivo");
				return 2;
			}
		}

		//-------------------------------------------------------------------------------------
		// Loop sulle righe di insoluto appena inserite/aggiornate per determinare quali sono
		// risolte e quindi da storicizzare
		//-------------------------------------------------------------------------------------
		$insoluti = getFetchArray("SELECT * FROM insoluto WHERE IdContratto=$IdContratto ORDER BY NumRata");

		$capitale = 0;
		$pagato   = 0;
		foreach ($insoluti as $ins)
		{
			$numRata = $ins["NumRata"];
			if ($ins["ImpInsoluto"]<=0)
			{
				if ($ins["ImpDebitoIniziale"]>0)  // in credito o saldo 0 e c'era un debito: significa che questa rata � diventata positiva
				{
					if (!storicizzaInsoluto($IdContratto,$numRata,"POS"))
					{
						rollback();
						if ($idImportLog>0) writeResult($idImportLog,"K","Fallita storicizzazione della pratica $IdContratto rata $numRata");
						return 2;
					}
				}
			}
			// 6/12/2011: se � una riga a zero, la toglie
			if ($ins["ImpInsoluto"]==0 && $ins["ImpDebitoIniziale"]==0)
			{
				if (!execute("DELETE FROM insoluto WHERE IdContratto=$IdContratto AND NumRata=$numRata"))
				{
					rollback();
					if ($idImportLog>0) writeResult($idImportLog,"K","Fallita cancellazione insoluto $IdContratto rata $numRata");
					return 2;
				}
			}
			$pagato   += ($ins["ImpDebitoIniziale"]>$ins["ImpInsoluto"])?($ins["ImpDebitoIniziale"]-$ins["ImpInsoluto"]):0;

			// Parte del capitale da pagare (cio� non gi� pagato prima dell'affido)
			$capitaleDaPagare = ($ins["ImpCapitale"]<=0)?0:($ins["ImpCapitale"]-($ins["ImpPagato"]-$pagato));
			// Pu� darsi per� che il risultato sia troppo alto (se ci sono movimenti di storno, non riconosciuti come pagamenti, che per�
			// abbassano il saldo.

			// tolto il 8/11/2012: perch� pu� provocare difetti, se l'insoluto ha un debito iniziale piccolo, e poi
			//            l'insoluto sale perch� viene registrato un addebito: in questo caso il capitale da
			// 			  pagare veniva equiparato al debito iniziale, facendo s� che il saldo capitale totale
			//            calcolato per confrontarlo col pagato poteva risultare falsamente saldato
			//if ($capitaleDaPagare>$ins["ImpDebitoIniziale"] && $ins["ImpDebitoIniziale"]>0) 
			//	$capitaleDaPagare = ($ins["ImpDebitoIniziale"]<5)?0:$ins["ImpDebitoIniziale"];
				$capitale += $capitaleDaPagare;

		} // fine loop su righe di Insoluto

		//-------------------------------------------------------------------------------------
		// Se il capitale � comunque pagato (con residui su altre voci)
		// mette la pratica in positivit� (ma non storicizza ulteriori righe)
		//-------------------------------------------------------------------------------------
		//trace("Capitale=$capitale pagato=$pagato",FALSE);
		if ($capitale<=$pagato)
		{
			trace("Positivo per capitale ($capitale) <= pagato ($pagato)",FALSE);
			if (rendePositivo($IdContratto,FALSE))
			{
				commit();
				return 3;        // tutto ok, segnala nessuna squadratura
			}
			else
			{
				rollback();
				if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}

		//-----------------------------------------------
		// Aggiorna campi calcolati,nel contratto
		//-----------------------------------------------
		if (!aggiornaCampiDerivati($IdContratto))
		{
			writeResult($idImportLog,"K",getLastError());
			trace("aggiornaCampiDerivati fallita idContratto=$IdContratto");
			return 2;
		}

		//-------------------------------------------------------------------------------------
		// Se � stato pagato quanto concordato con un saldo e stralcio (eventualmente dilazionato)
		// cambia lo stato, passando il resto in proposta di write off
		//-------------------------------------------------------------------------------------
		$impSaldo = $dati["ImpSaldoStralcio"];
		if ($capitale-$pagato <= $impSaldo) // se capitale residuo non superiore al concordato, pu� procedere al writeoff
		{
 			writeHistory("NULL","Rilevato completamento del saldo e stralcio: la pratica passa nel workflow di Write Off ",$IdContratto,"");		
 			// Imposta il campo necessario alle uscite dal ciclo di workflow (forse da rivedere per questo caso)
 			// e azzera la data di saldo e stralcio
 			if (!execute("UPDATE Contratto SET IdStatoRecuperoPrecedente=IdStatoRecupero,DataSaldoStralcio=NULL WHERE IdContratto=$IdContratto"))
 			{
				writeResult($idImportLog,"K",getLastError());
				trace("Passaggio automatico da Saldo e Stralcio in WriteOff fallito idContratto=$IdContratto");
				return 2;
 			}
			else if (!impostaStato("WRKPROPWO",$IdContratto))
 			{
				writeResult($idImportLog,"K",getLastError());
				trace("Passaggio automatico da Saldo e Stralcio in WriteOff fallito idContratto=$IdContratto");
				return 2;
 			}	
 		}

 		//-------------------------------------------------------------------------------------
		// Segna quanto pagato nell'eventuale piano di rientro
		//-------------------------------------------------------------------------------------
 		if (!controllaPianoRientro($IdContratto))
		{
			writeResult($idImportLog,"K",getLastError());
			trace("Controllo piano di rientro fallito idContratto=$IdContratto");
			return 2;
		}
 		
		commit();
		return 0;
	}
	catch (Exception $e)
	{
			rollback();
			trace("Errore nell'elaborazione degli insoluti: ".$e->getMessage());
			writeResult($idImportLog,"K",$e->getMessage());
			return 2;
	}
}
	
//==============================================================================================
// processInsolutiPrecrimine
// Elabora l'insieme di movimenti di un contratto/rata per creare/aggiornare le righe nella tabella
// insolutoPrecrimine
// Argomenti:
//   1) IdContratto
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function processInsolutiPrecrimine($IdContratto)
{
	global $idImportLog;
	try
	{
		beginTrans();  // INIZIO TRANSAZIONE

		//------------------------------------------------------------------------------------------
		// Legge alcuni dati dal contratto
		//------------------------------------------------------------------------------------------
		$dati = getRow("SELECT ImpRata,ImpRataFinale,ImpSpeseIncasso FROM contratto WHERE IdContratto=$IdContratto");
		$ImpRataNormale = $dati["ImpRata"]+$dati["ImpSpeseIncasso"];
		$ImpRataFinale  = $dati["ImpRataFinale"]+$dati["ImpSpeseIncasso"];

		//------------------------------------------------------------------------------------------
		// Loop su ciascun numero di rata, cio� su ciascuna partita elementare
		// (la rata zero per� comprende movimenti di tipo vario)
		//------------------------------------------------------------------------------------------
		$rate = fetchValuesArray("SELECT NumRata FROM movimentoprecrimine WHERE IdContratto=$IdContratto And NumRata!=0 ORDER BY NumRata");
		$saldo = 0; // saldo totale di tutte le rate
		foreach ($rate as $NumRata)
		{
			//--------------------------------------------------------------------------------------
			// Legge in ordine le righe della partita, per determinare la data di scadenza
			// (ultima data in cui si � passati da credito a debito)
			//--------------------------------------------------------------------------------------
			$sql = "SELECT m.*,CategoriaMovimento,m.IdTipoInsoluto"
			." FROM movimentoprecrimine m LEFT JOIN tipomovimento tm ON m.IdTipoMovimento=tm.IdTipoMovimento"
			." LEFT JOIN tipoinsoluto ti ON m.IdTipoInsoluto=ti.IdTipoInsoluto"
			." WHERE IdContratto=$IdContratto AND NumRata=$NumRata"
			." ORDER BY IdMovimento";
			$movimenti = getFetchArray($sql);
			if (count($movimenti)==0) // non pu� verificarsi a meno di errore su DB, visto quando viene chiamata questa funzione
			{
				if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
				return 2;
			}
			$curr = 0; // somma corrente
			//$impInt = 0; // importo interessi: calcolato solo in aggiornProvvigioni
			$impCapitale = 0; // importo debito su rate
			$incasso = 0; // incassi da cliente
			$lastCausale = "";
			$dataInsoluto = "";
			$dataInsolutoVero = "";
			$rataCerta = FALSE;
			$ultimoIncasso = 0;
			foreach ($movimenti as $mov)            // Loop sui movimenti di questa singola rata
			{
				$catMov  = $mov["CategoriaMovimento"];
				$importo = $mov["Importo"];         // importo a debito (se positivo) o credito (se negativo)
				$saldo += $importo;
				//	if ($importo>0 && $catMov=='I') // addebiti INTERESSI
				//	{
				//		$impInt += $importo;
					//		trace("Addebito interessi $importo",FALSE);
					//	}
					if ($importo>0 && $catMov=='C') // addebiti CAPITALE
					{
						if (!$rataCerta) // ancora non incontrata un importo di rata certa
						{
							$impCapitale += $importo;
							//			trace("Aggiunto a capitale $importo",FALSE);
							if ($impCapitale == $ImpRataFinale || $impCapitale == $ImpRataNormale )
							$rataCerta = TRUE; // non serve pi� cercare quant'� la rata
						}
					}
					if ($curr<=0.001 && $curr + $importo>0.001) // questo movimento fa diventare debito
					{
						$dataInsoluto = $mov["DataScadenza"]?$mov["DataScadenza"]:$mov["DataCompetenza"];
						if ($importo>10 && ($dataInsolutoVero=="" || $catMov=='C')) // prob. un vero insoluto
						$dataInsolutoVero = $dataInsoluto;
					}
					if ($importo<0 && $catMov=='P') // tipo mov. INCASSO
					{
						//		trace("Aggiunto a incasso ".(-$importo),FALSE);
						$incasso -= $importo;
						$ultimoIncasso = -$importo;
					}
					// storni
					//trace(($importo==-$impCapitale)?"vero":"falso",FALSE);
					if (round($importo,2)==-round($impCapitale,2) && $catMov=='S' && !$rataCerta) // storno della rata
					{
						//		trace("Storno capitale",FALSE);
						$impCapitale = 0;
					}
					if (round($importo,2)==round($incasso,2) && $catMov=='S') // storno dell'incasso
					{
						//		trace("Storno incasso",FALSE);
						$incasso = 0;
					}
					else if (round($importo,2)==round($ultimoIncasso,2)
					&& $catMov=='S') // corretto il 26/7/2011 (azzerava incassi reg. prima di rid)
					//				&& ($catMov=='S' || $catMov=='X')) // storno dell'incasso
					{
						//		trace("Storno ultimo incasso",FALSE);
						$incasso -= $ultimoIncasso;
					}
					if ($mov["IdTipoInsoluto"]>0) // conserva ultima causale insoluto
					$lastCausale = $mov["IdTipoInsoluto"];
					$curr += $importo;
			}
			// Arrotonda per evitare importi quasi 0 (nnnn E-14)
			$curr  = round($curr,2);
			//$impInt = round($impInt,2);
			$impCapitale = round($impCapitale,2);
			$incasso = round($incasso,2);

			//----------------------------------------------------------------------------------
			// A causa della determinazione approssimativa di quali causali indicano una rata
			// e quali un pagamento, avviene abbastanza spesso che la rata venga contata pi� volte
			// e il pagamento meno volte del necessario. Nel caso (errato) pi� frequente,
			// 	capitale = multiplo di insoluto, e pagato=0. In questo caso, aggiusta l'importo capitale.
			//----------------------------------------------------------------------------------
			if ($impCapitale>$curr*1.9 && $incasso==0) // capitale molto maggiore di ins. con pagam=0
			{
				if (round($impCapitale/$curr,2)==0 ) // multiplo esatto
				$impCapitale = $curr;  // imposta il capitale 'reale'
				// meno frequentemente, il capitale � moltiplicato ma c'� anche un residuo di spese
				// che fa s� che l'insoluto non sia un sottomultiplo esatto del capitale
				else
				{
					$newCap = $impCapitale/round($impCapitale/$curr,0); // sottomultiplo esatto
					if ($curr-$newCap>=0 && $curr-$newCap<5) // insoluto residuo � piccolo
					$impCapitale = $newCap; // imposta il capitale 'reale'
				}
			}

			//----------------------------------------------------------------------------------
			// Applica ragionamenti simili al campo ImportoPagato
			//----------------------------------------------------------------------------------
			if ($impCapitale>0 && $incasso>$impCapitale*1.9)
			{
				if (round($incasso/$impCapitale,2)==0 ) // multiplo esatto
				$incasso = $impCapitale;
			}

			//-------------------------------------------------------------------------------------
			// Se la partita � a debito, inserisce o aggiorna la riga in "Insoluto"
			// Se � a saldo 0 e la riga non � in Insoluto, la ignora; altrimenti la aggiorna.
			// Se � a saldo positivo la inserisce o aggiorna (serve a calcolare il bilancio totale)
			// Se per� l'insoluto
			//-------------------------------------------------------------------------------------
			$dataInsoluto = ($dataInsolutoVero=="")?$dataInsoluto:$dataInsolutoVero;
			$ins = getRow("SELECT * FROM insolutoprecrimine WHERE IdContratto=$IdContratto AND NumRata=$NumRata");
		//	if ($impCapitale > 26) // Insoluto degno di segnalazione
			if ($curr >= 26) // Insoluto degno di segnalazione (correzione del 26/1/2015)
				$oper = ($ins==NULL)?"INS":"UPD";
			//			else if ($curr < 0) // residuo a credito: scrive perch� serve al totale
			//				$oper = ($ins==NULL)?"INS":"UPD";
			//			else if ($curr == 0) // saldo zero
			//				$oper = ($ins==NULL)?"":"UPD";
			else
				$oper = "";
			if ($oper=="INS") // nuova riga in insolutoPrecrimine
			{
				// Insert Insoluto
				$colList = ""; // inizializza lista colonne
				$valList = ""; // inizializza lista valori
				addInsClause($colList,$valList,"IdTipoInsoluto",$lastCausale,"N");
				addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
				addInsClause($colList,$valList,"NumRata",$NumRata,"N");
				addInsClause($colList,$valList,"DataInsoluto",$dataInsoluto,"D");
				addInsClause($colList,$valList,"DataArrivo","CURDATE()","G");
				addInsClause($colList,$valList,"LastUser","import","S");
				addInsClause($colList,$valList,"ImpCapitale",$impCapitale,"N");
				addInsClause($colList,$valList,"ImpPagato",$incasso,"N"); // importo GIA' pagato
				addInsClause($colList,$valList,"ImpInteressi",0,"N"); // calcolato solo in aggiornaProvvigioni
				addInsClause($colList,$valList,"ImpInsoluto",$curr,"N"); // Att.ne: questo � il debito residuo a saldo dei pagamenti
				addInsClause($colList,$valList,"ImpDebitoIniziale",$curr,"N");  // questo � il debito al momento dell'insert (resta invariato
				// oppure aumento finch� la riga esiste)

				if (!execute("INSERT INTO insolutoprecrimine ($colList) VALUES ($valList)"))
				{
					rollback();
					if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
					return 2;
				}
			}
			// Insoluto gi� presente, esegue UPDATE
			else if ($oper=="UPD")
			{
				//-------------------------------------------------------------------------------------
				// Aggiorna la riga di Insoluto
				//-------------------------------------------------------------------------------------
				$setClause ="";
				addSetClause($setClause,"ImpCapitale",$impCapitale,"N");
				addSetClause($setClause,"ImpPagato",$incasso,"N");
				addSetClause($setClause,"ImpInteressi",0,"N");
				addSetClause($setClause,"ImpInsoluto",$curr,"N");
				addSetClause($setClause,"DataInsoluto",$dataInsoluto,"D");

				// NB: il campo "ImpDebitoIniziale" viene impostato solo dall'INSERT e aggiornato solo al riaffido (affidaAgenzia)
				// oppure se arrivano ulteriori addebiti
				// Se il campo � NULL significa che la riga era stata creata prima dell'introduzione del campo
				if (!$ins["ImpDebitoIniziale"])
					addSetClause($setClause,"ImpDebitoIniziale",$curr+$incasso,"N");
				else if ($ins["ImpInsoluto"]<$curr) // l'insoluto � aumentato: aumenta anche quello su cui si calcola il recupero
					addSetClause($setClause,"ImpDebitoIniziale",$ins["ImpDebitoIniziale"]+$curr-$ins["ImpInsoluto"],"N");

				addSetClause($setClause,"LastUser","import","S");
				//trace("UPDATE insoluto $setClause WHERE IdContratto=$IdContratto AND NumRata=$NumRata",FALSE);
				if (!execute("UPDATE insolutoprecrimine $setClause WHERE IdContratto=$IdContratto AND NumRata=$NumRata"))
				{
					rollback();
					if ($idImportLog>0) writeResult($idImportLog,"K",getLastError());
					return 2;
				}
			}
		}  // fine foreach su ciascun numero rata

		commit();
		//---------------------------------------------------------------------------------------------
		//  Esegue l'azione automatica associata al precrimine (invio SMS)
		//---------------------------------------------------------------------------------------------
		if ($oper>"")
			if (!eseguiAutomatismiPerAzione("PRECRIMINE",$IdContratto,"v_contratto_precrimine"))
				return 2;
		return 0;
	}
	catch (Exception $e)
	{
		rollback();
		 
		("Errore nell'elaborazione degli insolutiPrecrimine: ".$e->getMessage());
		writeResult($idImportLog,"K",$e->getMessage());
		return 2;
	}
}
//==============================================================================================
// insertServAcc
// Elabora la l'array passato ed elbora il campo servizio del contratto
// Argomenti:
//   1) $json			struttura dati in input (parte servizio)
//   2) $todo           operazione da effettuare "update" (implica la cancellazione  e poi l'insert)
//						oppure "insert"
//   3)	$CodContratto			codice Contratto
//	 4) $IdContratto 			id contratto
// Restituisce:
//      0:	tutto OK
//      1:  errore su un record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function insertServAcc($json,$todo,$IdContratto,$CodContratto)
{
	global $idImportLog;

	try
	{
		beginTrans();

		if($todo == "update") // se � un update
		{
			if (!execute("DELETE FROM accessorio WHERE IdContratto =".$IdContratto))
			{
				rollback();
				writeRecordError($idImportLog,"E","Errore nell'elaborazione (cancellazione) di un record 'Accessorio' :".getLastError(),$CodContratto);
				trace("Errore nell'elaborazione (cancellazione) di un record 'Accessorio' del contratto $CodContratto ".getLastError()." contratto $CodContratto",FALSE);
				return 2;
			}

		}

		// insert (sia su update che insert)
		for($i=0; $i<count($json);$i++)
		{
			// procede all'insert
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
			addInsClause($colList,$valList,"Info",$json[$i]->Firmatario,"S");
			addInsClause($colList,$valList,"Importo",'',"N");
			addInsClause($colList,$valList,"DataIni",$json[$i]->DataInizio,"D");
			addInsClause($colList,$valList,"DataFin",$json[$i]->DataFine,"D");
			addInsClause($colList,$valList,"Prodotto",$json[$i]->Descrizione,"S");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");

			//trace("INSERT INTO accessorio ($colList) VALUES ($valList)",false);

			if (!execute("INSERT INTO accessorio ($colList) VALUES ($valList)"))
			{
				rollback();
				writeRecordError($idImportLog,"E","Errore nell'elaborazione (inserimento) di un record 'Accessorio'".getLastError(),$CodContratto);
				trace("Errore nell'elaborazione (inserimento) di un record 'Accessorio' del contratto $CodContratto ".getLastError()." contratto $CodContratto",FALSE);
				return 2;
			}
			//trace("Update recapiti cliente CodCliente= $codcli OK");
		}// fine for
		commit();
		return 0;
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione di un record 'Accessorio' del contratto $CodContratto : ".$e->getMessage(),FALSE);
		rollback();
		writeRecordError($idImportLog,"E","Errore nell'elaborazione di un record 'Accessorio' del contratto $CodContratto :".$e->getMessage(),$CodContratto);
		return 2;
	}
}


//==============================================================================================
// insertRecapiti
// Elabora la l'array passato e aggiorna i recapiti cliente
// Argomenti:
//   1) $json			struttura dati in input (parte recapiti)
//   2) $todo           operazione da effettuare "update" (implica la cancellazione dei recapiti e poi l'insert)
//						oppure "insert" dei recapiti
//   3)	$codcli			codice cliente
//	 4) $dataIni        data inizio cliente
//	 5) $dataFin        data fine cliente
// Restituisce:
//      0:	tutto OK
//      1:  errore su un record
//      2:  errore che implica la terminazione del processo
//==============================================================================================
function insertRecapiti($json,$todo,$idCliente,$dataIni,$dataFin,$codcli)
{
	global $idImportLog;

	try
	{
		beginTrans();

		// SE E' UN UPDATE VERIFICO I DATI RICEVUTI E LI CONFRONTO CON QUELLI GIA ESISTENTI
		if($todo == "update") // se � un update
		{
			$sql="SELECT IdRecapito,IdTipoRecapito,"
			." ProgrRecapito, Indirizzo,"
			." Localita,CAP, SiglaProvincia,"
			." SiglaNazione, Telefono,Cellulare,"
			." Fax,Email,LastUser,Nome,FlagAnnullato"
			." FROM recapito WHERE LastUser='import'"
			." and IdCliente=$idCliente";

			$oldRec = getFetchArray($sql);

			if(count($oldRec)>0)
			{
				for($i=0; $i<count($json);$i++)
				{
					$flag=0;
					for($j=0;$j<count($oldRec);$j++)
					{
						if($json[$i]->tipo == $oldRec[$j][IdTipoRecapito])
						{
							$esiste[]=$oldRec[$j][IdRecapito];
							$flag=1;
							if($oldRec[$j][FlagAnnullato]=='Y')
							{
								if(!confrontaRecapiti($json[$i],$oldRec[$j]))
								{
									if(!DelRecapito($oldRec[$j][IdRecapito],$codcli)==0)
									return 1;

									if(!InsRecapito($json[$i],$idCliente,$dataIni,$dataFin,$i,$codcli)==0)
									return 1;
								}
							}
							else
							{
								if(!DelRecapito($oldRec[$j][IdRecapito],$codcli)==0)
								return 1;

								if(!InsRecapito($json[$i],$idCliente,$dataIni,$dataFin,$i,$codcli)==0)
								return 1;
							}
						}
					}

					if($flag ==0)
					{
						if(!InsRecapito($json[$i],$idCliente,$dataIni,$dataFin,$i,$codcli)==0)
						return 1;
					}
				}

				for($j=0;$j<count($oldRec);$j++)
				{
					if(array_search($oldRec[$j][IdRecapito],$esiste)===FALSE)
					{
						if(!DelRecapito($oldRec[$j][IdRecapito],$codcli)==0)
						return 1;
					}
				}
			}
			else     // se � update e se nn esistono dati di sistema per il cliente allora inserisco i dati arrivati senza constrollare
			{
				$todo="insert";
			}
		}
		// SE E' UN INSERT NUOVO INSERISCO I RECAPITI
		if($todo == "insert")
		{
			// insert dei recapiti (sia su update che insert cliente)
			for($i=0; $i<count($json);$i++)
			{
				if (!InsRecapito($json[$i],$idCliente,$dataIni,$dataFin,$i,$codcli)==0)
				{
					return 1;
				}
			}
		} // fine if insert
		commit();

		return 0;
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione dei record recapiti: ".$e->getMessage());
		rollback();
		writeRecordError($idImportLog,"K","Errore nell'elaborazione dei record recapiti: ".$e->getMessage(),$codcli);
		return 2;
	}
}

// insert singolo recapito
function InsRecapito($json,$idCliente,$dataIni,$dataFin,$i,$codcli)
{
	global $idImportLog,$sigleProvince,$sigleNazioni,$aree,$tipiRecapito;

	if($json->tipo!=null)
	{
		if (!in_array($json->tipo,$tipiRecapito))
		//if (!rowExistsInTable("tiporecapito","IdTipoRecapito='".$json->tipo."'"))
		{
			rollback();
			writeRecordError($idImportLog,"R","IdTipoRecapito non presente nella tabella TipoRecapito ",$codcli);
			return 1;
		}
	}
	if($json->provincia!=null)
	{
		if (!in_array($json->provincia,$sigleProvince))
		//	if(!rowExistsInTable("provincia","SiglaProvincia='".$json->provincia."'"))
		{
			writeRecordError($idImportLog,"R","Sigla Provincia ".$json->provincia." non presente nella tabella Provincia",$codcli);
			$json->provincia = null;
		}
	}
	if($json->nazione!=null)
	{
		if (!in_array($json->nazione,$sigleNazioni))
		//if(!rowExistsInTable("nazione","SiglaNazione='".$json->nazione."'"))
		{
			writeRecordError($idImportLog,"R","Sigla Nazione ".$json->nazione." non presente nella tabella Nazione",$codcli);
			$json->nazione = "IT";
		}
	}
	// procede all'insert dei recapiti
	$colList = ""; // inizializza lista colonne
	$valList = ""; // inizializza lista valori
	addInsClause($colList,$valList,"IdCliente",$idCliente,"N");
	addInsClause($colList,$valList,"IdTipoRecapito",$json->tipo,"N");
	addInsClause($colList,$valList,"ProgrRecapito","-UNIX_TIMESTAMP()","G");
	addInsClause($colList,$valList,"Indirizzo",$json->indirizzo,"S");
	addInsClause($colList,$valList,"Localita",$json->localita,"S");
	addInsClause($colList,$valList,"CAP",formatCap($json->cap),"S");
	addInsClause($colList,$valList,"SiglaProvincia",$json->provincia,"S");
	addInsClause($colList,$valList,"SiglaNazione",$json->nazione,"S");
	addInsClause($colList,$valList,"Telefono",$json->telefono,"S");
	addInsClause($colList,$valList,"Cellulare",$json->cellulare,"S");
	addInsClause($colList,$valList,"Fax",$json->fax,"S");
	addInsClause($colList,$valList,"Email",$json->email,"S");
	if ($dataIni==NULL) $dataIni = "2001-01-01";
	addInsClause($colList,$valList,"DataIni",$dataIni,"D");
	if ($dataFin==NULL) $dataFin = "9999-12-31";
	addInsClause($colList,$valList,"DataFin",$dataFin,"D");
	addInsClause($colList,$valList,"LastUser","import","S");
	addInsClause($colList,$valList,"LastUpd","NOW()","G");
	addInsClause($colList,$valList,"Nome",$json->nome,"S");

	if (!execute("INSERT INTO recapito ($colList) VALUES ($valList)"))
	{
		writeRecordError($idImportLog,"K","Errore nell'inserimento di un record recapiti: ".$e->getMessage(),$codcli);
		return 2;
	}

	// Determina l'IdArea da mettere nel cliente
	// OBSOLETO: sostituito da UPDATE finale della funzione processCliente
	/********
	 if ($json->tipo==1)
	 {
	 if (!array_key_exists($json->provincia,$aree))
	 {
		writeRecordError($idImportLog,"E","Area non presente per la Provincia ".$json->provincia,$codcli);
		$idAreaCli = NULL;
		}
		else
		{
		$chiavi = split(",",$aree[$sigla]);
		if(count($chiavi)==2 && $chiavi[1]>0)
		$idAreaCli = $chiavi[1]; // IdAreaParent
		else
		$idAreaCli = $chiavi[0]; // IdArea
		}
		$setClause = "";
		addSetClause($setClause,"IdArea",$idAreaCli,"N");
		if (!execute("UPDATE cliente $setClause WHERE IdCliente=$idCliente"))
		{
		writeResult($idImportLog,"K",getLastError());
		return 2;
		}
		}******/

	return 0;
}

// elimina un singolo recapito
function delRecapito($IdRecapito,$codcli)
{
	global $idImportLog;

	$sql = "DELETE FROM recapito WHERE IdRecapito=$IdRecapito";
	if(!execute($sql))
	{
		writeRecordError($idImportLog,"K","Errore nella cancellazione di un record recapiti.",$codcli);
		return 2;
	}
	return 0;
}

// confronta due recapiti
function confrontaRecapiti ($json,$oldDb)
{
	if($oldDb["Indirizzo"]==$json->indirizzo)
	if($oldDb["Localita"]==$json->localita)
	if($oldDb["CAP"]==$json->cap)
	if($oldDb["SiglaProvincia"]==$json->provincia)
	if($oldDb["SiglaNazione"]==$json->nazione)
	if($oldDb["Telefono"]==$json->telefono)
	if($oldDb["Cellulare"]==$json->cellulare)
	if($oldDb["Fax"]==$json->fax)
	if($oldDb["Email"]==$json->email)
	if($oldDb["Nome"]==$json->nome)
	return true;
	return false;
}


//==============================================================================================
// processAllegato
// Elabora l'allegato ricevuto dalla import.php, copia il file nella cartella relativa al contratto
// e registra l'allegato nella tabella allegato.
// Restituisce:
//      true :	tutto OK
//      false:  errore nell'elaborazione
//==============================================================================================
function processAllegato($from,$type,$id,$codContratto,$titoloAllegato,$tipoAllegato,$idImportLog)
{
	try
	{
		// Ottiene la chiave della Compagnia
		$idCompagnia = getCompanyId($from);
		if ($idCompagnia==0)
		{
			trace("Sistema mittente '$from' non identificato nella tabella Compagnia",FALSE);
			echo "Sistema mittente '$from' non identificato nella tabella Compagnia<br>";
			return FALSE;
		}

		// ottiene l'id contratto
		$idContratto = getScalar("select IdContratto from contratto where CodContratto='".$codContratto."'");
		if ($idContratto==0)
		{
			trace("Id contratto non presente nella tabella contratto per il contratto $codContratto",FALSE);
			echo "Id contratto non presente nella tabella contratto per il contratto $codContratto <br>";
			return FALSE;
		}

		// controlla che tipo di allegato � : 'c' = l'allegato contiene il contratto
		// qualsiasi altro valore indica che l'allegato contiene un qualsiasi altro documento contrattuale
		if(($tipoAllegato=='c')||($tipoAllegato=='C'))
			$tipo = "CON";  // contiene contratto
		else
			$tipo = "DOC";  // contiene documenti contrattuali

		// ottiene l'id del tipo di allegato
		$idTipo = getScalar("SELECT IdTipoAllegato FROM tipoallegato where CodTipoAllegato='$tipo'");
		if ($idTipo==0)
		{
			trace("Id tipo allegato $idTipo non identificato nella tabella TipoAllegato",FALSE);
			echo "Id tipo allegato non identificato nella tabella TipoAllegato<br>";
			return FALSE;
		}

		// compone l'array che serve alla funzione allegaDocumento
		$dati = array('IdCompagnia'=>$idCompagnia,'CodContratto'=>$codContratto,'IdContratto'=>$idContratto);

		// prendo l'IdImportLog dalla tabella importlog
		$allegatoEsistente = getScalar("select IdImportLog from allegato where IdImportLog=$idImportLog AND IdContratto=$idContratto");
		if($allegatoEsistente>0)
		{
			trace("Cancellazione preliminare dell'allegato gia' registrato con IdImportLog=$idImportLog",FALSE);
			// se l'allegato era gi� registrato nella tabella importlog, cancello l'allegato dalla tab allegato
			execute("DELETE FROM allegato where IdImportLog=$idImportLog AND IdContratto=$idContratto");
		}
		 
		if(allegaDocumento($dati,$idTipo,$titoloAllegato,'N',"filename",$idImportLog) == FALSE)
		{
			trace("Errore durante l'elaborazione dell'allegato allegaDocumento",FALSE);
			echo "Errore durante l'elaborazione dell'allegato<br>";
			return FALSE;
		}
		return true;
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione dell'allegato. ".$e->getMessage());
		echo "Errore nell'elaborazione dell'allegato. ".$e->getMessage()."<br>";
		return false;
	}
}

//----------------------------------------------------------------------------------------------------
// rielaboraNegativi
// 22/20/2011: riesamina i partitari che non hanno ricevuto aggiornamenti oggi
//             ma contengono movimenti posteriori alla data del loro ultimo aggiornamento
//----------------------------------------------------------------------------------------------------
function rielaboraNegativi()
{
	// data ultimo aggiornamento movimenti
	$lastMovDate = getScalar("SELECT MAX(DATE(ImportTime)) FROM importlog WHERE FileType='movimenti' AND ImportResult='U'");
	if ($lastMovDate==NULL)
		$lastMovDate = ISODate(getScalar("SELECT MAX(DATE(LastUpd)) FROM movimento"),true);
	 // cerca le classificazioni che dipendono dal ritardo in giorni o dal numero di insoluti
	 // 11/7/2012: tolta condizione su numGiorniDa: infatti se un contratto � in classe con numGiorniDa>0 non 
	 //   cambia classe all'avanzare dei giorni, ma la cambia solo se numGiorniA � >0
	 //$classi = "SELECT IdClasse FROM classificazione WHERE NumInsolutiA IS NOT NULL OR NumGiorniA IS NOT NULL OR NumGiorniDa IS NOT NULL";
	 $classi = "SELECT IdClasse FROM classificazione WHERE NumInsolutiA IS NOT NULL OR NumGiorniA IS NOT NULL";
	/*
	 // cerca contratti delle classi suddette, che non abbiano movimenti registrati con l'ultima infornata
	 // (cio� i contratti non toccati). Prende sia quelli con pagamento = bollettino, sia quelli con i RID,
	 // perch� possono essere stati gi� registrati dei RID futuri insoluti, che vengono esaminati solo
	 // dalla data di scadenza della rata-
	 // 13/9/2011: aggiunta condizione per i contratti positivi con bilancio>0, perch� questi non vengono
	 //            mandati da Windows se non ci sono novit� nel partitario, e non verrebbero processati
	 //            perch� la classe 18 non � una di quelle considerate.
	 // 19/9/2011: modificata condizione di cui sopra includendo i contratti con idclasse qualsiasi, soprattutto
	 //            perch�, se non classificato, pu� essere scaduto un insoluto che lo rende classificabile
	 $sql = "SELECT IdContratto FROM contratto c
	 WHERE (IdClasse IN (SELECT IdClasse FROM classificazione WHERE NumInsolutiA IS NOT NULL OR NumGiorniA IS NOT NULL)
	 OR ImpInsoluto>0)
	 AND NOT EXISTS (SELECT 1 FROM movimento m WHERE m.IdContratto=c.IdContratto AND LastUpd>='$lastMovDate')
	 AND (IdTipoPagamento=1 OR
	 EXISTS (SELECT 1 FROM movimento m WHERE m.IdContratto=c.IdContratto AND LastUpd<'$lastMovDate'
	 AND ifnull(datascadenza,datavaluta)>=date(lastupd) and ifnull(dataScadenza,datavaluta)<=CURDATE())
	 ) ORDER BY 1";
	 */
	/* contratti senza movimenti nell'ultimo import ma con movimenti successivi al loro ultimi import e (entro prossimi giorni oppure a credito)*/
	/* In aggiunta: contratti con classe nulla ma con movimenti, forse inclassificati per errori pregressi */
	/* dal 19/3/2012: Semplificato mettendo tutti i contratti nelle classi dipendenti dal tempo non gi� classificati */ 
	// NOTA: la query � complosta di 3 query in UNION: la prima delle tre restituisce pi� dell'80% delle righe totali
	 
	// 5/1/2016: aggiunta condizione per escludere dalla prima query i contratti che hanno movimenti rilevanti ma solo
	// pi� vecchi di un mese fa e aggiunta condizione between (invece che <=) per individuare meglio quelli con movimento a debito
	// entro i prossimi giorni. Queste due aggiunte, in particolare la prima, eliminano circa 3000 contratti su 13000,
	// con un guadagno di circa 30 minuti sulle 3 ore totali [circa 90 contratti al minuto]
	$sql = "SELECT IdContratto FROM contratto c WHERE NOT EXISTS (SELECT 1 FROM movimento m WHERE m.IdContratto=c.IdContratto AND m.LastUpd>='$lastMovDate')"
	." AND EXISTS (SELECT 1 FROM movimento x WHERE x.IdContratto=c.IdContratto "
	." AND ifnull(datascadenza,datavaluta)>=date(lastupd) AND ifnull(datascadenza,datavaluta)>CURDATE()-INTERVAL 1 MONTH"
	." and (Importo<0 OR ifnull(datascadenza,datavaluta) BETWEEN CURDATE() AND CURDATE()+INTERVAL 4 DAY))"
	." UNION SELECT IdContratto FROM contratto c WHERE IdClasse IS NULL"
	." AND EXISTS (SELECT 1 FROM movimento m where m.idcontratto=c.idcontratto)"
	." UNION SELECT IdContratto FROM contratto WHERE IdClasse IN ($classi)"
	." AND DataCambioClasse<CURDATE() ORDER BY 1";

	$ids = fetchValuesArray($sql);
	writeProcessLog(PROCESS_NAME,"Individuati ".count($ids)." contratti da verificare");
	$cnt=0;
	foreach ($ids as $IdContratto) {
		controllaStopForzato();
		trace("Rielaboro contratto $IdContratto",FALSE);
		if (2==processAndClassify($IdContratto)) // se errore grave, interrompe
			break;
		$cnt++;
		if ($cnt%1000==0)
			writeProcessLog(PROCESS_NAME,"Elaborati $cnt contratti");
	}
}

//================================================================================
// cleanTrace()     (by Aldo)
// Rinomina i file di trace e cancella i file pi� vecchi di un tot di giorni
//================================================================================
function cleanTrace()
{
	global $context;
	try
	{
		$dataScad=date("Ymd",mktime(0,0,0,date("m"),date("d")-getSysParm("GIORNI_CANCELLAZIONE","90"),date("Y")));

		$dir = LOG_PATH.'/';
		foreach (scandir($dir) as $item)
		{
			if($item=="trace.txt")
			{
				$newname = "trace_".date('Y_m_j-H_i_s').".txt";
				if(!rename($dir.$item,$dir.$newname))
				trace("Errore durante la  rinomina del file di traccia".$dir.$item,false);
				else
				trace("File di traccia precedente archiviato col nome $newname",FALSE);
			}
			else
			{
		  if($item!='.' && $item!='..')
		  {
		  	$dataFile=substr($item,6,4).substr($item,11,2).substr($item,14,2);

		  	if($dataFile<$dataScad)
		  	{
		  		if(!unlink($dir.$item))
		  		trace("Errore durante la cancellazione del file di traccia".$dir.$item,false);
		  	}
		  }
			}
		}
	}
	catch (Exception $e)
	{
		trace("Errore durante la cancellazione del file di traccia :  $e->getMessage()");
		return false;
	}
}

//==============================================================================================
// processDipendente
// Elabora la struttura JSON con i dati di debito dei dipendenti, provenienti da TKGI
// Argomenti:
//   1) $json			struttura dati in input
//   2) $idCompany		idCompagnia da usare nella query e insert
// Restituisce:
//      0:	tutto OK
//      1:  errore sul record
//      2:  errore che implica la terminazione del processo (manca la chiave del record, oppure
//          superato il massimo numero di errori ammessi)
// Nota:
//   Questi record vanno a finire sulle tabelle:
//      Cliente   (con codCliente   = 'KG'+codana)
//      Contratto (con codContratto = 'KG'+pos)
//      InsolutoDipendente
//==============================================================================================
function processDipendente($json,$idCompany)
{
	global $idImportLog;

	try
	{
		//-------------------------------------------------------------------------
		// Inserimento nella tabella Cliente
		//-------------------------------------------------------------------------
		$codcli = 'KG'.$json->codana; // cod. cliente artificiale per distinguere da quelli normali TFSI
		if ($codcli>"") // codice cliente presente?
		{
			// cerca il cliente con codice dato, nella compagnia con id dato (deve essercene uno solo)
			$idCliente = getScalar("SELECT C.IdCliente FROM cliente C,clientecompagnia X "
			. " WHERE X.IdCompagnia=$idCompany AND X.IdCliente=C.IdCliente AND C.CodCliente='$codcli'");
			trace("IdCliente per il codice $codcli: $idCliente",FALSE);
		}
		else // codice cliente assente nella struttura JSON
		{
			writeResult($idImportLog,"K","Codice cliente non specificato");
			return 2;
		}

		if ($idCliente>0) // � necessario un UPDATE (la tab. ClienteCompagnia non si deve aggiornare)
		{
			$setClause = "";
			addSetClause($setClause,"SiglaNazione","IT","S");
			addSetClause($setClause,"Nominativo",$json->nome,"S");
			addSetClause($setClause,"IdTipoCliente",3,"N"); // 3 = dipendente
			addSetClause($setClause,"DataIni","2001-01-01","S");
			addSetClause($setClause,"DataFin","9999-12-31","S");
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");

			if (!execute("UPDATE cliente $setClause WHERE IdCliente=$idCliente"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}
		else // � necessario un INSERT
		{
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"SiglaNazione","IT","S");
			addInsClause($colList,$valList,"CodCliente",$codcli,"S");
			addInsClause($colList,$valList,"Nominativo",$json->nome,"S");
			addInsClause($colList,$valList,"IdTipoCliente",3,"N"); // 3 = dipendente
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			if (!execute("INSERT INTO cliente ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}

			$idCliente = getInsertId();
			trace("Creata riga Cliente con idCliente=$idCliente",FALSE);

			// Crea la riga di associazione in ClienteCompagnia
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdCliente",$idCliente,"N");
			addInsClause($colList,$valList,"IdCompagnia",$idCompany,"N");
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");

			if (!execute("INSERT INTO clientecompagnia ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}
		//-------------------------------------------------------------------------
		// Inserimento nella tabella Contratto
		//-------------------------------------------------------------------------
		$codcon = 'KG'.$json->posiz; // cod. contratto artificiale per distinguere da quelli normali TFSI
		if ($codcon=="")       // se non � stato specificato  il codice contratto
		{
			writeResult($idImportLog,"K","Codice contratto non presente");
			return 2;
		}

		// ricavo idCodContratto dalla tab Contratti
		$QueryStr = "SELECT IdContratto FROM contratto C WHERE IdCliente=$idCliente AND C.CodContratto ='$codcon' AND IdCompagnia =".$idCompany;
		$idContratto = getScalar($QueryStr);
		if (!$idContratto)
		{
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdCliente",$idCliente,"N");
			addInsClause($colList,$valList,"IdCompagnia",$idCompany,"N");
			addInsClause($colList,$valList,"CodContratto",$codcon,"S");
			addInsClause($colList,$valList,"IdProdotto",4,"N"); // fisso: prestito TKGI dipendenti
			addInsClause($colList,$valList,"IdStatoContratto",1,"N"); // fisso
			addInsClause($colList,$valList,"DataIni","2001-01-01","S");
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			if (!execute("INSERT INTO contratto ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
			$idContratto = getInsertId();
		}// end if Insert
		else               // il contratto va aggiornato
		{
			$setClause ="";
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			if (!execute("UPDATE contratto $setClause WHERE IdContratto=$idContratto"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}

		//-------------------------------------------------------------------------
		// Inserimento nella InsolutoDipendente
		//-------------------------------------------------------------------------
		$ins = getRow("SELECT * FROM insolutodipendente WHERE IdContratto=$idContratto"
		." AND DataScadenza='".$json->scadenzarata."'");
		if (is_array($ins)) // riga gi� esistente
		{
			$idInsoluto = $ins["IdInsoluto"];
			$setClause ="";
			addSetClause($setClause,"ImpCapitale",$json->quotacapitale,"N");
			addSetClause($setClause,"ImpInteressi",$json->quotainteressi,"N");
			addSetClause($setClause,"ImpInteressiMora",$json->interessimora,"N");
			addSetClause($setClause,"ImpCommissioni",$json->commissioni,"N");
				
			addSetClause($setClause,"ImpPagato",$ins["ImpCapitale"]-str_replace(',','.',$json->quotacapitale),"N");
			addSetClause($setClause,"DataChiusura","NULL","G"); // ripristina (toglie marcaggio fatto in processFile())
			addSetClause($setClause,"LastUser","import","S");
			addSetClause($setClause,"LastUpd","NOW()","G");
			if (!execute("UPDATE insolutodipendente $setClause WHERE IdInsoluto=$idInsoluto"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}
		else // inserimento nuova riga
		{
			$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$idContratto,"N");
			addInsClause($colList,$valList,"DataScadenza",$json->scadenzarata,"S");
			addInsClause($colList,$valList,"DataArrivo","CURDATE()","G");
			addInsClause($colList,$valList,"ImpCapitale",$json->quotacapitale,"N");
			addInsClause($colList,$valList,"ImpInteressi",$json->quotainteressi,"N");
			addInsClause($colList,$valList,"ImpInteressiMora",$json->interessimora,"N");
			addInsClause($colList,$valList,"ImpCommissioni",$json->commissioni,"N");
			addInsClause($colList,$valList,"ImpPagato",0,"N");
			addInsClause($colList,$valList,"LastUser","import","S");
			addInsClause($colList,$valList,"LastUpd","NOW()","G");
			if (!execute("INSERT INTO insolutodipendente ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
			}
		}

		return 0;
	}
	catch (Exception $e)
	{
		trace("Errore nell'elaborazione di un record clienti: ".$e);
		return 2;
	}
}

?>
