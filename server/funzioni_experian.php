<?php
/**
 * FUNZIONI PER ESTRAZIONE E COMUNICAZIONE CON Experian
 */

/**
 * inviaDatiExperian Determina se � il giorno giusto per inviare dati ad experian e li invia
 * NOTA: la selezione viene effettuata tramite la view v_experian_candidati, che deve essere cambiata ogni volta che si vogliono
 * cambiare i criteri di estrazione (la stessa view � usata nella pagina Experian->Candidati)
 * @param {Number} $numdays numero di giorni feriali prima dell'affido. Se =-1 il file viene prodotto e spedito indipendentemente
 * dal giorno di esecuzione (per i test)
 */
function inviaDatiExperian($numdays) {
	// Determina il prossimo giorno di affido
	$oggi = date('d');
	if ($oggi>24) {
		$giornoAffido = mktime(0,0,0,date('n')+1,5,date('Y'));
	} else if ($oggi<5) {
		$giornoAffido = mktime(0,0,0,date('n'),5,date('Y'));
	} else if ($oggi>4 && $oggi<15) {
		$giornoAffido = mktime(0,0,0,date('n'),15,date('Y'));
	} else {
		$giornoAffido = mktime(0,0,0,date('n'),25,date('Y'));
	}
	$intervallo = ($giornoAffido-mktime(0,0,0,date('n'),date('j'),date('Y'))) / (3600*24); // numero di giorni di intervallo;
	// Conta quanti giorni non di stop ci sono nel mezzo (sabato e domenica il batch non gira)
	$giorni = 0;
	for ($i=0;$i<$intervallo;$i++) {
		$giorno = date('w', mktime(0,0,0,date('n'),date('j')+$i,date('Y')));
		$giorni += ($giorno!=0 && $giorno!=6); // non � un sabato n� una domenica
	} 
	if ($giorni==$numdays or $numdays==-1) { // mancano esattamente i giorni richiesti
		//$where = "CodRegolaProvvigione IN ('".str_replace("," , "','" , $codici)."')"; 
		creaFileExperian('',$error);
	} else {
		trace("Invio file a Experian non eseguito perch� non � il giorno designato ($numdays giorni feriali prima dell'affido)",false);
	}
}

/**
 * riceveDatiExperian Controlla se ci sono dati Experian da leggere e li salva su database
 */
function riceveDatiExperian() {
	readAllExperianResponses($error);
}

/**
 * creaFileExperian Crea il file da spedire in SFTP a Experian
 * @param {String} condizione applicata a v_experian per determinare quali pratiche estrarre e inviare
 * Se la condizione manca (come nel caso di default), viene usata la vista v_experian_candidati 
 */
function creaFileExperian($where,&$error) {

	// Crea riga in experian
	// Legge i dati
	if ($where>'')
		$sql = "SELECT * FROM v_experian WHERE $where ORDER BY 1";
	else
		$sql = "SELECT * FROM v_experian where idcliente in (select idcliente from v_experian_candidati)";
	$rows = getFetchArray($sql);
	trace("Selezionate ".count($rows)." clienti da inviare con la query seguente: $sql",false);
	if (count($rows)==0) {
		trace("Avviso: la selezione di posizioni da inviare a Experian non ha estratto alcun record (con la query seguente: $sql)",false,true);
		return "";
	}
	// Prepara il file
	$fileName = "COLL_REQ_".date('Ymd');
	trace("Preparazione file estratto per Experian $fileName (".count($rows)." righe)",FALSE);
	
	// Crea riga in tabella experianfile
	beginTrans();
	$IdExperian = getScalar("SELECT IdExperian FROM experianfile WHERE Filename='$fileName'");
	if (!$IdExperian) { // � la prima volta che si produce questo file 	
		execute("INSERT INTO experianfile(Filename) VALUES('$fileName')");	
		$IdExperian = getInsertId();
		trace("Assegnato ID=$IdExperian alla richiesta da inviare ad Experian",FALSE);
	} else {
		trace("Riuso dell'ID=$IdExperian per invio di file ad Experian",FALSE);
	}
	
	// Per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
	// del processo corrente (siccome la creazione pu� essere lanciata da web o da batch)
	$processUser = posix_getpwuid(posix_geteuid());
	$localDir = TMP_PATH."/".$processUser['name'];
	$fileURL = TMP_REL_PATH."/".$processUser['name']."/$fileName";
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
		mkdir($localDir,0777,true); // true --> crea le directory ricorsivamente

	$filePath = "$localDir/$fileName";
	$file     = fopen($filePath,"w");
	
	// Produce le righe
	execute("DELETE FROM experianrichiesta WHERE IdExperian=$IdExperian"); // cancella le eventuali righe preesistenti
	
	$cnt = 0;
	foreach ($rows as $row) {
		extract($row);
		$v = array();
		$v[] = str_pad($CodCliente,20);
		$v[] = date('dmY');
		$v[] = $TipoCredito;
		$v[] = $MotivoFinanziamento;
		$v[] = $StatusPagamenti;
		$v[] = str_pad('',5); //$GiorniSconfino; non popolato perch� si applica solo ai non rateali? max 90
		$v[] = str_pad($Scaduto,7,'0',STR_PAD_LEFT);
		if ($Cognome=='') {
			$v[] = str_pad(substr($RagioneSociale,0,60),60);
			$v[] = str_pad($PartitaIva,16);
			$v[] = str_pad($PartitaIva,11);
			$v[] = "S"; // flag persona giuridica
			$v[] = str_pad('',40);
		} else {
			$v[] = str_pad(substr($Cognome,0,30),30);
			$v[] = str_pad(substr($Nome,0,30),30);
			$v[] = str_pad($CodiceFiscale,16);
			$v[] = str_pad($PartitaIva,11);
			$v[] = $Sesso;
			$v[] = substr($DataNascita,8,2).substr($DataNascita,5,2).substr($DataNascita,0,4);
			$v[] = str_pad(substr($LocalitaNascita,0,30),30);
			$v[] = str_pad('',2); // Prov Nascita non esiste
		}
		separaIndirizzo($Indirizzo,$via,$civico);
		$v[] = str_pad(substr($via,0,30),30);
		$v[] = str_pad(substr($civico,0,10),10);
		$v[] = str_pad(substr($Localita,0,30),30);
		$v[] = str_pad($SiglaProvincia,2);
		$v[] = str_pad($Cap,5);
		
		$dati = implode('',$v);
		fwrite($file,"$dati\n");
		
		// Scrive su DB
		$sql = "REPLACE INTO experianrichiesta (IdExperian,IdCliente,DatiInviati) VALUES($IdExperian,$IdCliente,".quote_smart($dati).")";
		execute($sql);
		$cnt += getAffectedRows();
	}
	fclose($file);

	trace("$cnt scritte sul file $filePath",FALSE);
	// Invia file
	if (sendToExperian($filePath,$error)) {
		trace("File inviato con successo",FALSE);
		$sql = "UPDATE experianfile SET DataInvio=NOW() WHERE IdExperian=$IdExperian";		
		execute($sql);
		commit();
		return true;
	} else {
		rollback();
		trace($error,true,true);
		return false;
	}
}

/**
 * separaIndirizzo
 * Tenta di separare la via dal numero civico in un indirizzo generico
 */
function separaIndirizzo($indirizzo,&$via,&$civico) {
	if (substr_count($indirizzo,",")==1) { // una sola virgola, presumo che separi la via dal civico
		$parti  = explode(',',$indirizzo);
		$via    = trim($parti[0]);
		$civico = trim($parti[1]);
	} else if (preg_match('/^(.+)\s(\d+)\s*$/',$indirizzo,$parti)   	// termina con un numero separato
		   or preg_match('/^(.+)\s(\d+\S+)\s*$/i',$indirizzo,$parti)    // o qualcosa che comincia con una cifra
		   or preg_match('/^(.+)\s(\d+.+)$/i',$indirizzo,$parti) ) {    
		$via    = trim($parti[1]);
		$civico = $parti[2];
	} else {
		$via = $indirizzo;
		$civico = "";
	}
}

/**
 * sendToExperian
 */
function sendToExperian($filePath,&$error) {
	$fileName = substr($filePath,strrpos($filePath,"/")+1);
	$sftp = connectToExperian($error);
	if (!$sftp)
		return false;
	
	try {
		$stream = fopen("ssh2.sftp://$sftp/to_xpn/$fileName", 'w');
		
		if (! $stream) {
			$error = "Fallita apertura del file di arrivo /to_xpn/$fileName";
			return false;
		}
		$data_to_send = file_get_contents($filePath);
		if ($data_to_send === false) {
			$error = "Fallita apertura del file di dati $filePath";
			return false;
		}
		if (fwrite($stream, $data_to_send) === false){
			$error = "Fallito invio dati al server Experian";
			return false;
		}
		fclose($stream);
	} catch(Exception $e) {
		disconnectFromExperian();
		$error = $e->getMessage();
		return false;
	}
	disconnectFromExperian();
	return true;
}

function my_ssh_disconnect($reason, $message, $language) {
	printf("Server disconnected with reason code [%d] and message: %s\n",$reason, $message);
}
function my_ssh_ignore($message) {
	trace(sprintf("Server ignore with message: %s\n",$message),false);
}

function my_ssh_debug($message, $language, $always_display) {
	trace(sprintf("Server debug with message: %s\n",$message),false);
}

function my_ssh_macerror($packet) {
	trace(sprintf("Server macerror with packet: %s\n",print_r($packet,true)),false);
}

/**
 * connectToExperian
 * Apre la connessione SFTP con experian
 * @return {Object} connessione aperta ($sftp)
 */
function connectToExperian(&$error) {
	global $ssh;
 
	if (!function_exists('ssh2_connect')) {
		$error = "Funzione ssh2_connect non disponibile";
		return false;
	}
	// SSH Host
	$ssh_host = 'st.uk.experian.com'; // '194.60.191.31' 
	// SSH Port
	$ssh_port = 22;
	// SSH Username
	$ssh_auth_user = 'cgtp5566toyoyatfs';
	// SSH Public Key File  (SI TROVANO NELLA CARTELLA ROOT DEL SITO (cnc/cnctest)
	$ssh_auth_pub = __DIR__.'/../id_rsa.pub';
	// SSH Private Key File
	$ssh_auth_priv = __DIR__.'/../id_rsa';
	// SSH Private Key Passphrase (null == no passphrase)
	$ssh_auth_pass = null;
	// SSH Connection
	$ssh = null;
	
	// Connect
	$ssh = ssh2_connect($ssh_host, $ssh_port, array('hostkey'=>'ssh-rsa,ssh-dss'),
			array('disconnect' => 'my_ssh_disconnect',
			  	  'ignore'     => 'my_ssh_ignore',
				  'debug'      => 'my_ssh_debug',
   				  'macerror'   => 'my_ssh_macerror')
			);
	if (!$ssh) {
		$error = "Connessione al server $ssh_host:$ssh_port non riuscita";
		return false;
	}
	
	// Passa le chiavi di sicurezza
	if (!ssh2_auth_pubkey_file($ssh, $ssh_auth_user, $ssh_auth_pub, $ssh_auth_priv, $ssh_auth_pass)) {
		$error = "Autenticazione con il server fallita (user=$ssh_auth_user, public key=$ssh_auth_pub, private_key=$ssh_auth_priv";
		return false;
	}
	
	// Collega in SFTP
	$sftp = ssh2_sftp($ssh);
	if (!$sftp) {
		$error = "Fallita apertura della comunicazione SFTP";
		disconnectFromExperian();
		return false;
	}
	return $sftp;
}

/**
 * disconnectFromExperian
 * Chiude la connessione ssh
 */
function disconnectFromExperian() {
	global $ssh;
	if ($ssh) {
		// pare che non si possano dare comandi: rinuncio per ora
//		ssh_exec('echo "EXITING" && exit;',$error);
//		ssh_exec('exit;',$error);

		$ssh = null;
	}	
}

/**
 * ssh_exec
 * Esegue un comando ssh
 */
function ssh_exec($cmd,&$error) {
	global $ssh;
	if (!($stream = ssh2_exec($ssh, $cmd))) {
		$error = "Comando ssh '$cmd' fallito";
		return false;
	}
	stream_set_blocking($stream, true);
	$data = "";
	while ($buf = fread($stream, 4096)) {
		$data .= $buf;
	}
	fclose($stream);
	return $data;
}

/**
 * Legge tutte le risposte disponibili per richieste Experian inviate
 */
function readAllExperianResponses(&$error) {
	$filenames = sftp_getFiles("from_xpn",$error);
	if (!$filenames) {
		trace("Nessun file nella cartella from_xpn (error=$error)",false);
		return false;
	}
	trace("Individuati i seguenti files nella cartella from_xpn: ".implode(', ',$filenames),false);
	$cnt = 0;
	foreach ($filenames as $filename) {
		if (!preg_match('/^COLL_RESP_([0-9]{8})/',$filename)) { // tollera altri caratteri dopo la data
			trace("File $filename scartato perche' con nome non standard",false);
			continue;
		}
		if (rowExistsInTable('experianfile',"FileNameRisposta='$filename'")) {
			trace("File $filename scartato perche' gia' elaborato",false);
			continue;
		}
		$content = getExperianFile($filename,$error);
		if (!$content) {
			$error = "Fallita lettura del file $filename";
			trace($error);
			return false;
		}
		// Individua la riga di richiesta corrispondente a questo file di risposta
		$reqfile = str_replace('_RESP_','_REQ_',$filename);
		// Trova l'entrata col nome file che probabilmente corrisponde a quanto cercato
		$IdExperian = getScalar("SELECT IdExperian FROM experianfile WHERE DataRisposta IS NULL AND FileName<='$reqfile' ORDER BY FileName DESC LIMIT 1");
		if (!$IdExperian) {
			trace("Non trovata alcuna richiesta Experian pendente per il file di risposta $filename",false);
			continue;
		}
		beginTrans();
		$sql = "UPDATE experianfile SET DataRisposta=NOW(),FileNameRisposta='$filename' WHERE IdExperian=$IdExperian";
		execute($sql);
		trace("File $filename attribuito come risposta alla richiesta con IdExperian=$IdExperian",false);
		
		if (!processExperianFile($IdExperian,$content,$error)) {
			rollback();
			trace($error,true,true);
			return false;
		} else {
			commit();
			$cnt++;
		}
	}
	trace("Elaborati $cnt files",false);
	return true;
}

/**
 * getExperianFile
 * Legge un dato file dalla cartella from_xpn del SFTP Experian
 */
function getExperianFile($fileName,&$error) {
	$sftp = connectToExperian($error);
	if (!$sftp)
		return "";
	
	try {
		$stream = fopen("ssh2.sftp://$sftp/from_xpn/$fileName", 'r');
	
		if (! $stream) {
			$error = "Fallita apertura del file /from_xpn/$fileName";
			return false;
		}
		$content = "";
		while (!feof($stream)) {
			$content .= fread($stream, 8192);
		}
		fclose($stream);
		disconnectFromExperian();
		return $content;
	} catch(Exception $e) {
		$error = $e->getMessage();
		disconnectFromExperian();
		return false;
	}
}

/**
 * processExperianFile
 * Elabora il contenuto del file di risposta Experian
 */
function processExperianFile($IdExperian,$content,&$error) {
	
	trace("Elaborazione file proveniente da Experian, lunghezza = ".strlen($content),false);
	
	// Mette i dati strutturati nell'apposita tabella experian
	$cnt = 0;
	$items = array_filter(preg_split('/\n+/',$content));
	foreach ($items as $item) {
		$v = array();
		$CodCliente = 1*trim(substr($item,6,20)); // Experian ficca uno zero in testa (senza motivo)
		if (!$CodCliente>'') continue;
		$IdCliente = getScalar("SELECT IdCliente FROM cliente WHERE CodCliente='$CodCliente'");
		if (!$IdCliente) {
			trace("Codice Cliente $CodCliente non trovato nella base dati: informazione experian scartata",false);
			continue;
		}
		$v[] = $IdExperian;
		$v[] = $IdCliente;
		$v[] = quote_smart(trim($CodCliente));
		$v[] = quote_smart(substr($item,5,1)); // own data
		$v[] = quote_smart(substr($item,26,4).'-'.substr($item,30,2).'-'.substr($item,32,2)); // data analisi
		$v[] = quote_smart(trim(substr($item,34,40))); // nominativo
		$v[] = quote_smart(trim(substr($item,74,16))); // codice fiscale
		$v[] = numero(substr($item,101,4)); // D4C score
		$v[] = numero(substr($item,105,2)); // D4C score index
		$v[] = numero(substr($item,120,2)); // tipo finanziamento
		$v[] = numero(substr($item,122,2)); // motivo finanziamento
		$v[] = numero(substr($item,124,1)); // stato pagamenti
		$v[] = numero(substr($item,125,7)); // scaduto non pagato
		$v[] = numero(substr($item,132,3)); // mesi dal pi� recente protesto
		$v[] = numero(substr($item,135,3)); // # protesti
		$v[] = numero(substr($item,138,9)); // importo totale protesti
		$v[] = numero(substr($item,147,3)); // mesi dato pubblico pi� recente
		$v[] = numero(substr($item,150,3)); // mesi dato pregiudizievole pi� recente
		$v[] = numero(substr($item,153,3)); // num dati pregiudizievoli
		$v[] = numero(substr($item,156,9)); // totale dati pregiudizievoli
		$v[] = numero(substr($item,165,3)); // richieste credito ultimi 6 mesi
		$v[] = numero(substr($item,168,9)); // importo richiesto ultimi 6 mesi
		$v[] = numero(substr($item,177,3)); // richieste credito ultimi 3 mesi
		$v[] = numero(substr($item,180,3)); // richieste credito accettate ultimi 6 mesi
		$v[] = numero(substr($item,183,9)); // importo accettato ultimi 6 mesi
		$v[] = numero(substr($item,192,3)); // richieste credito accettate ultimi 3 mesi
		$v[] = numero(substr($item,195,9)); // importo finanziato ultima richiesta accettata
		$v[] = quote_smart(substr($item,204,1)); // peggior status speciale
		$v[] = numero(substr($item,205,3)); // num contratti aperti 12 mesi
		$v[] = numero(substr($item,208,3)); // num contratti attivi
		$v[] = numero(substr($item,211,3)); // num contratti status = 0
		$v[] = numero(substr($item,214,3)); // num contratti status = 1-6
		$v[] = numero(substr($item,217,3)); // num contratti status = 1-3
		$v[] = numero(substr($item,220,3)); // num contratti status = 4-5
		$v[] = numero(substr($item,223,3)); // num contratti status = 6
		$v[] = numero(substr($item,226,3)); // num contratti peggiore status 12 mesi = 0-2
		$v[] = numero(substr($item,229,3)); // num contratti peggiore status 12 mesi = 1-2
		$v[] = numero(substr($item,232,3)); // num contratti peggiore status 12 mesi = 3-5
		$v[] = numero(substr($item,235,3)); // num contratti peggiore status 12 mesi = 6
		$v[] = numero(substr($item,238,3)); // num contratti in default 12 mesi  
		$v[] = quote_smart(substr($item,241,1)); // peggior status ultimi 1-12 mesi
		$v[] = quote_smart(substr($item,242,1)); // peggior status ultimi 6 mesi
		$v[] = quote_smart(substr($item,243,1)); // peggior status ultimi 7-12 mesi
		$v[] = quote_smart(substr($item,244,1)); // peggior status corrente
		$v[] = numero(substr($item,245,3)); // num contratti estinti
		$v[] = numero(substr($item,248,3)); // num contratti estinti ultimi 6 mesi
		$v[] = numero(substr($item,251,3)); // num contratti in default
		$v[] = numero(substr($item,254,3)); // num contratti passati in perdita / cessione
		$v[] = numero(substr($item,257,3)); // num contratti passati in perdita / cessione ultimi 12 mesi
		$v[] = numero(substr($item,260,3)); // mesi contratto pi� recente con status 0 ultimi 12 mesi
		$v[] = numero(substr($item,263,3)); // mesi contratto pi� recente con status 3-6 ultimi 12 mesi
		$v[] = numero(substr($item,266,3)); // mesi contratto pi� recente in default
		$v[] = numero(substr($item,269,9)); // totale scaduto non pagato
		$v[] = numero(substr($item,278,9)); // totale scaduto non pagato con status 1-2
		$v[] = numero(substr($item,287,9)); // totale scaduto non pagato con status 3-5
		$v[] = numero(substr($item,296,9)); // totale scaduto non pagato con status 6-8
		$v[] = numero(substr($item,305,9)); // totale saldo in essere
		$v[] = numero(substr($item,314,9)); // totale impegno mensile
		$v[] = numero(substr($item,323,3)); // num conti revolving con saldo <75% del limite
		$v[] = numero(substr($item,326,3)); // rapporto saldo dovuto / limite di credito
		$v[] = numero(substr($item,329,3)); // rapporto saldo dovuto auto / saldo totale
		$v[] = numero(substr($item,332,3)); // rapporto max scaduto non pagato / saldo dovuto
		$v[] = numero(substr($item,335,3)); // num prestiti finalizzati
		$v[] = numero(substr($item,338,3)); // num prestiti personali
		$v[] = numero(substr($item,341,3)); // num conti revolving
	
		$sql =
		"REPLACE INTO experian (IdExperian,IdCliente,CodCliente,OwnData,DataAnalisi,Nominativo,CodiceFiscale,D4CScore,D4CScoreIndex,
		TipoFinanziamento,MotivoFinanziamento,StatoPagamenti,ScadutoNonPagato,MesiDaUltimoProtesto,NumProtesti,ImportoTotaleProtesti,
		MesiDaUltimoDatoPubblico,MesiDaUltimoDataPregiudizievole,NumDatiPregiudizievoli,ImportoTotaleDatiPregiudizievoli,
		NumRichiesteCredito6mesi,ImpRichiesteCredito6mesi,NumRichiesteCredito3mesi,NumRichiesteAccettate6mesi,ImpRichiesteAccettate6mesi,
		NumRichiesteAccettate3mesi,ImpUltimaRichiestaFinanziata,PeggiorStatusSpeciale,NumContratti12mesi,NumContrattiAttivi,
		NumContrattiStatus0,NumContrattiStatus1_6,NumContrattiStatus1_3,NumContrattiStatus4_5,NumContrattiStatus6,NumContrattiPeggiorStatus0_2_12mesi,
		NumContrattiPeggiorStatus1_2_12mesi,NumContrattiPeggiorStatus3_5_12mesi,NumContrattiPeggiorStatus6_12mesi,NumContrattiDefault_12mesi,
		PeggiorStatus_1_12mesi,PeggiorStatus_6mesi,PeggiorStatus_7_12mesi,PeggiorStatusCorrente,NumContrattiEstinti,NumContrattiEstinti_6mesi,
		NumContrattiDefault,NumContrattiPerditaCessione,NumContrattiPerditaCessione_12mesi,MesiDaUltimoContrattoStatus0_12mesi,
		MesiDaUltimoContrattoStatus3__6_12mesi,MesiDaUltimoContrattoDefault,TotaleImpScadutoNonPagato,TotaleImpScadutoNonPagato_Status1_2,
		TotaleImpScadutoNonPagato_Status3_5,TotaleImpScadutoNonPagato_Status6_8,TotaleSaldoInEssere,TotaleImpegnoMensile,
		NumContiRevolvingSaldoMinore75percento,RapportoMaxSaldoLimiteCredito,RapportoSaldoAutoSaldoTotale,RapportoMaxScadutoSaldo,
		NumPrestitiFinalizzati,NumPrestitiPersonali,NumContiRevolving) VALUES (".implode(',',$v).")";
		if (execute($sql))
			$cnt++;
		else 
			return false;
	}
	trace("Inserite/aggiornate $cnt righe nella tabella 'experian'",false);
	return true;
}

/**
 * numero
 * Imposta un valore numerico o NULL per la scrittura di un campo sul DB
 */
function numero($v) {
	return trim($v)>''?trim($v):'NULL';	
}

/**
 * sftp_getFiles
 * Restituisce una lista di files di una data cartella sftp
 */
function sftp_getFiles($dir,&$error) {
	$sftp = connectToExperian($error);
	if (!$sftp)
		return false;
	
	try {
		$dir = "ssh2.sftp://$sftp/$dir";
	    $arr = array();
    	$handle = opendir($dir);
	    while (false !== ($file = readdir($handle))) {
    		if (substr("$file", 0, 1) != "." and !is_dir($file)) {
            	  $arr[]=$file;
        	}
    	}
    	disconnectFromExperian();
    	return $arr;
   	} catch (Exception $e) {
		$error = $e->getMessage();
    	disconnectFromExperian();
		return false;
	}
}
?>
