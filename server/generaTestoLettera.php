<?php 
	require_once("workflowFunc.php");
	
//
//  Compone il testo di una lettera (chiamata con Ajax da pagina js)
// Nota : 2016-05-26 - l'azione STA ha FlagMultiplo = N anche se questo programma gestisce più contratti in una volta,
// perchè la separazione del modello word in parti è difettosa nei casi in cui il modello contiene più sezioni
	global $context;
try {
	$where = " WHERE ".(isset($_REQUEST['IdModello'])?"IdModello=".$_REQUEST['IdModello']:"TitoloModello='".$_REQUEST['TitoloModello']."'");
	$sql = "SELECT * FROM modello $where";
	$modello = getRow($sql);
	trace("Lettura modello lettera '{$modello['FileName']}' con sql: $sql");
	$filepath = TEMPLATE_PATH.'/'.$modello['FileName'];
	if (!file_exists($filepath)) {
		trace("Il file modello $filepath non esiste",false);
		die("Il file modello $filepath non esiste");
	} else if (!is_readable($filepath)) {
		trace("Il file modello $filepath non può essere letto",false);
		die("Il file modello $filepath non può essere letto");
	}
	
	if(preg_match('/html?$/i',$filepath)){ //genera documenti pdf a partire da modelli html
		
		$pdf = ".pdf"; //estensione del file da generare
		
		$strText = file_get_contents($filepath);
		if (strlen($strText)==0) {
			trace("Il file modello $filepath è vuoto",false);
			die("Il file modello $filepath è vuoto");
		}
		
		htmlToPdf($_REQUEST['IdContratto'],$modello,$strText,$pdf);

	}else{ //segue la generazione dei documenti in formato xml
		$modelText = file_get_contents($filepath);
		if (strlen($modelText)==0) {
			trace("Il file modello $filepath è vuoto",false);
			die("Il file modello $filepath è vuoto");
		}
		$p1 = strpos($modelText, '<w:body>')+8;
		// Cerca l'ultima sezione (è la fine del body?)
		$p2 = strrpos($modelText, '<w:sectPr'); // a volte ha attributi, percio' non cerca <w:sectPr> con  ">" di chiusura
		$p3 = strpos($modelText, '</w:body>');
		$header  = substr($modelText,0,$p1);
		$body 	 = substr($modelText,$p1,$p2-$p1);
		$section = substr($modelText,$p2,$p3-$p2);
		$footer  = substr($modelText,$p3);
		
		$contrattiStr =  $_REQUEST['IdContratto'];
		$arrayContratti = explode(",",$contrattiStr );
		
		
		$textToPrint .= $header;
		
		$ind = count($arrayContratti);
		foreach($arrayContratti as $contratto) {
			$pratica = htmlentities_deep(getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=".$contratto));
			$text = replaceModel($body,"RataLettera","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
			$text = replaceModel($text,"RataMandato","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
			$text = replaceModel($text,"RecapitiMandato","SELECT * FROM v_recapiti_mandato WHERE IdContratto=".$contratto);
			$text = replaceModel($text,"DatoreMandato","SELECT * FROM v_datore_di_lavoro WHERE IdContratto=".$contratto);
			$text = replaceModel($text,"SubLetteraFattura","SELECT * FROM v_fatture WHERE IdContratto=".$contratto);
		
			// 20/1/2016: aggiunto nome e numero utente corrente
		
			getUserName($IdUtente); // ottiene IdUtente
			$sql = "SELECT NomeUtente as Utente,IFNULL(u.Telefono,r.TelefonoPerClienti) AS TelUtente"
					. " FROM utente u JOIN reparto r on r.IdReparto=u.IdReparto WHERE IdUtente=$IdUtente";
		
					$pratica = array_merge($pratica,htmlentities_deep(getRow($sql)));
					$textToPrint .= replaceVariables($text,$pratica,'_________');
					$ind--;
					if ($ind>0) { // se devo mettere piu' pratiche nello stesso file word, inserisco un break di pagina
						$textToPrint .= "<w:p><w:pPr>".str_replace("</wx:sect>","</w:pPr></w:p></wx:sect>",$section);
					} else {
						$textToPrint .= $section;
					}
		}
		
		$textToPrint.=$footer;
		
		
		// Allega il risultato, se il modello ha l'IdTipoAllegato
		// PER DEBUG
		
		
		$suff = count($arrayContratti)==1?$pratica['CodContratto']:date("Ymd_Hi");
		trace('Lunghezza del file XML prodotto: '.strlen($textToPrint));
		
		
		/*
		 * LOOP PER GENERARE GLI ALLEGATI (26/05/2016)
		 *
		 */
		if($modello['IdTipoAllegato'] > ''){
			foreach($arrayContratti as $contratto){
					
				$testoDoc = $header;
				$pratica = htmlentities_deep(getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=".$contratto));
				$text = replaceModel($body,"RataLettera","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
				$text = replaceModel($text,"RataMandato","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
				$text = replaceModel($text,"RecapitiMandato","SELECT * FROM v_recapiti_mandato WHERE IdContratto=".$contratto);
				$text = replaceModel($text,"DatoreMandato","SELECT * FROM v_datore_di_lavoro WHERE IdContratto=".$contratto);
				$text = replaceModel($text,"SubLetteraFattura","SELECT * FROM v_fatture WHERE IdContratto=".$contratto);
					
				getUserName($IdUtente); // ottiene IdUtente
				$sql = "SELECT NomeUtente as Utente,IFNULL(u.Telefono,r.TelefonoPerClienti) AS TelUtente"
						. " FROM utente u JOIN reparto r on r.IdReparto=u.IdReparto WHERE IdUtente=$IdUtente";
						$pratica = array_merge($pratica,htmlentities_deep(getRow($sql)));
						$testoDoc .= replaceVariables($text,$pratica,'_________');
						$testoDoc .= $section;
						$testoDoc.=$footer;
						if(!salvaLetteraComeAllegato($pratica,$modello,$testoDoc))
							die(getLastError());
								
			}
		}else{
			trace("Lettera non salvata come allegato perchè il modello non lo prevede");
		}
		header("Content-type: application/vnd.ms-word");
		header("Content-Disposition: attachment; filename=\"".$modello['TitoloModello']."_$suff.doc\"");
		echo $textToPrint;
	}
}	
catch (Exception $e)
{
	echo $e->getMessage();
}



/**
 * salvaLetteraComeAllegato
 * @param {Object} $pratica : dati del contratto dalla vista v_contratto_lettera
 * @param {Object} $modello : dati del modello
 * @param {Object} $textToPrint : testo dell'allegato
 * @return boolean
 */


function salvaLetteraComeAllegato($pratica, $modello, $textToPrint){

	if (function_exists('posix_getpwuid')) {
		$processUser = posix_getpwuid(posix_geteuid());
		$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$processUser['name']."/".$pratica['CodContratto'];
	} else {
		$localDir = ATT_PATH."/".$pratica['IdCompagnia']."/".$pratica['CodContratto'];
	}
	if (!file_exists($localDir)) { // se necessario crea il folder che ha per nome il path della cartella allegati + id Compagnia + codice Contratto
		if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
			setLastError("Impossibile creare la cartella $localDir");
			trace("Impossibile creare la cartella $localDir");
			return FALSE;
		}
	}
	
	$fileName = "{$modello['TitoloModello']}_{$pratica['CodContratto']}.doc";
	if (file_put_contents($localDir."/".$fileName,$textToPrint))
	{
		chmod($localDir."/".$fileName,0777);
		$titolo = quote_smart($modello['TitoloModello']);
		$IdContratto = $pratica['IdContratto'];
		$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fileName);
		$userid = getUserName($IdUtente);
		$userid = quote_smart($userid);
		$riservato = quote_smart($modello['FlagRiservato']);
		$idtipo = $modello['IdTipoAllegato'];
			
		$master=$context["master"];
		if($master!=''){
			$master=quote_smart($master);
		}else{
			$master = "null";
		}
			
		$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,"
				." IdUtente,LastUser, IdTipoAllegato, FlagRiservato,lastSuper) "
				."VALUES($IdContratto,$titolo,$url,$IdUtente,$userid,$idtipo,$riservato,$master)";
				//			trace("sql $sql");
				$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
				writeHistory($idAzione,"Allegato documento",$pratica['IdContratto'],"Documento: $titolo Contratto:".$pratica['CodContratto']);
				return execute($sql);
	}
	else
	{
		setLastError("Impossibile copiare il file nel repository");
		trace("Impossibile copiare il file $fileName nel repository $localDir");
		return FALSE;
	}
}


/**
 * htmlToPdf
 * Funzione che genera a partire da un testo html un documento in formato pdf
 * Alla creazione del pdf segue l'inserimento del file tra gli allegati
 * @param $idcontratto : idcontratto che serve per leggere le variabili da v_contratto_lettera
 * @param $ext : estensione del file (.pdf)
 */

function htmlToPdf($IdContratto,$modello,$strTxt,$ext){
	
	global $context;
	$sql = "SELECT * FROM v_contratto_lettera WHERE IdContratto=$IdContratto";
	$row = getRow($sql);
	
	$CodContratto = $row["CodContratto"];
	$file = $modello['FileName'];
	//2020-05-8 GENERAZIONE DEL QR CODE VALIDA SOLO PER IL MODELLO Lettera DEO.txt (fmazzarani)
	$qrcode = false;
	//TEST REGEXP PER NOMI FILE LETTERA DEO, LETTERA DEO MAXIRATE E PREAVVISO CENTRALE RISCHI
	// E PER I MODELLI RELATIVI AI GARANTI
	//sono le uniche lettere che hanno il QRCode inglobato nel testo
	if(	preg_match("/.*?\s(DEO)\.html/ism",$file,$match) || preg_match("/.*?(DEO garante)\.html/ism",$file,$match)){
		if($match){
			$imgBase64Code = generaQRCode($row,$errConvertion);
			$qrcode = true;
			if(!$imgBase64Code){
				$qrcode = false;
				$msg = $errConvertion > '' ? $errConvertion : '';
				Throw new Exception("\Genereazione QR Code per il contratto $CodContratto non riuscita a causa del seguente errore: $msg");
				return false;
			}
		}
	}
	// sceglie la cartella di destinazione in base allo userid
	$processUser = posix_getpwuid(posix_geteuid());
	$folder = $processUser['name'].'_new/'.substr($CodContratto,0,4);
	
	$localDir=ATT_PATH."/".$row["IdCompagnia"]."/$folder/".$CodContratto;
	//echo "Scrittura allegato nel folder $localDir";
	trace("Scrittura allegato nel folder $localDir",FALSE);
	if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
		if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
		Throw new Exception("\nOperazione non riuscita a causa del seguente errore: Impossibile creare la cartella dei documenti $localDir");
	
	$fileName =	substr($file,0,strrpos($file,'.'))."_{$CodContratto}_Rata_{$row["Rata"]}$ext";
	$newFile  = $localDir."/".$fileName;
	
	// sostituisco i dati tra % % con i dati ricevuti dalla vista
	//debug
	//echo "TESTO HTML: ".$strTxt."\n";
	
	preg_match_all('/(%[a-z_0-9\.]+%)/i',$strTxt,$arr);
	for ($i=count($arr[1])-1;$i>=0; $i--) {
		$var  = $arr[1][$i];
		$keySearch = substr($var,1,strlen($var)-2);
		if (strtolower(substr($keySearch,0,8))=='modello.') {
			$keymod = substr($keySearch,8);
			$fileModel = getScalar("SELECT filename FROM modello where TitoloModello = '$keymod'");
			if (!$fileModel)
				Throw new Exception("Non trovata definizione del sottomodello di stampa '$keymod'");
				$newVal='';
					
				$sqlRate = "SELECT * FROM v_rate_insolute WHERE IdContratto=".$IdContratto;
				$resultRate = getFetchArray($sqlRate);
				foreach ($resultRate as $rowRate) {
					//apre il modello e lo sostituisce
					$content = file_get_contents(TEMPLATE_PATH.'/'.$fileModel);
					if ($content=="")
						Throw new Exception("Non trovato il file '$fileModel' per il sottomodello di stampa '$keymod'");
	
						$newVal .= replaceVariables($content,$rowRate);
						$newVal .= TEXT_NEWLINE;
				}
		}else{
			if (array_key_exists($keySearch,$row)){
				$newVal= $row[$keySearch];
			}else{
				$newVal = '';
				trace("Non trovato valore da sostituire alla variabile $var",FALSE);
			}
		}
		//2020-05-08 gestione della sostituzione della variabile relativa al QRcode nel modello Lettera DEO.txt
		if($var == '%QRCode%' && $qrcode){
			$strTxt = str_replace($var,$imgBase64Code,$strTxt);
		}else{
			$strTxt = str_replace($var,$newVal,$strTxt);
		}
	}
	
	if($ext =='.pdf'){
		$result = creaPdfDaHtml($strTxt,$newFile);
	}
	if(!result){
		Throw new Exception("Errore nella scrittura del file $newFile");
		trace("Errore nella scrittura del file $newFile",false);
		return  false;
	}
	
	$titolo = substr($file,0,strrpos($file,"."));
	$url = REL_PATH."/".$row["IdCompagnia"]."/$folder/".$CodContratto."/".$fileName;
	$IdUtente = $context["IdUtente"];
	$idtipo = $modello['IdTipoAllegato'];
	
	if($idtipo>''){// effettuo l'insert su tab allegato
		$colList = "";
		$valList  = "";
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
		addInsClause($colList,$valList,"TitoloAllegato", $titolo,"S");
		addInsClause($colList,$valList,"UrlAllegato",$url,"S");
		addInsClause($colList,$valList,"IdUtente",$IdUtente,"N");
		addInsClause($colList,$valList,"LastUser","system","S");
		addInsClause($colList,$valList,"IdTipoAllegato",$idtipo,"N");
			
		$master=$context["master"];
		//trace("master ".$master);
		if($master!=''){
			addInsClause($colList,$valList,"lastSuper",$master ,"S");
		}
			
		if (!execute("INSERT INTO allegato ($colList) VALUES ($valList)"))
		{
			$msg = "Errore durante l'inserimento dell'allegato relativo alla pratica $CodContratto nella tabella allegato";
			Throw new Exception("Errore nell'elaborazione ".$modello["TitoloModello"],$msg);
			trace($msg,false);
			return false;
		}
		$IdAllegato=getInsertId();  // prendo l'id dell'ultimo allegato inserito sul db
		trace("creato allegato $IdAllegato per il contratto ".$row["IdContratto"],FALSE);
		writeHistory("NULL","Creata lettera '".$modello["TitoloModello"]."'",$IdContratto,"");
	}
	
	header("Content-type: application/pdf");
	header("Content-Disposition: attachment; filename=\"$fileName\"");
	readfile($newFile);
	
}

?>