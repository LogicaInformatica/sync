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
	
	$fileName = "{$modello['TitoloModello']}_{$suff}.doc";
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
?>