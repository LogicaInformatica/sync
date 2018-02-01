<?php
/**
 * processVisureACI: esegue le varie fasi previste per il caricamento di un file delle visure ACI:
 * 1) caricamento dei files da form
 * 2) elaborazione del file xml importato e creazione del file pdf della visura richiesta tramite targa
 * 3) allegare il file pdf creato al contratto legato alla targa e cancellazione, se esistente, dell'allegato 
 *    precedentemente inserito
 * 4) restituzione del messaggio con il numero degli allegati inseriti
 */
require_once("workflowFunc.php");
require_once("userFunc.php");
/*
 * chiude la sessione per evitare di bloccare le richieste ajax concorrenti
 */
session_write_close();
doMain();

function doMain()
{
	global $context;

	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch($task)
	{
	case "importFile": importFile();
		break;
	default:
		echo "{failure:true, task: '$task', messaggio:'$task sconosciuto'}";
	}
}

/*
 * importFile : prende il file scelto nel form Importazione file, lo carica nella cartella import
 */

function importFile(){
	
	extract($_REQUEST);
	//$infoModuloImport = array($IdModulo1, $IdModulo2, $IdModulo3, $IdModulo4); //array delle chiavi dei modulo di import
	//$info = array(); //array che conterr� le informazioni dei vari moduli di import
	//trace(print_r($_FILES, TRUE), false);
	foreach($_FILES as $key=>$file){
	
		$tmpName  = $file['tmp_name'];
		
		if($tmpName == '') continue;
		$fileName = $file['name'];
		$fileSize = $file['size'];
		$fileType = $file['type'];
	
		$fileName=urldecode($fileName);
		if(!get_magic_quotes_gpc())
			$fileName = addslashes($fileName);
		
		$localDir = TMP_PATH."/wizard";
		if (!file_exists($localDir)) {
			if (!mkdir($localDir,0777,true)) { // true --> crea le directory ricorsivamente
				fail("Impossibile creare la cartella $localDir");
			}
		}
		$filePath = "$localDir/$fileName";
				
		if (!move_uploaded_file ($tmpName, $filePath))	{
			fail("Impossibile copiare il file nella cartella $localDir");
		}
		// autorizza alla scrittura ?
		//if (!chown($filePath,0)) {
		//	trace("Fallita chown su $filePath",0);
		//}
		chmod($filePath,0777);
		//trasformazione in json del file xml
		$fileContents= file_get_contents($filePath);
        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
        $fileContents = trim(str_replace('"', "'", $fileContents));
        $simpleXml = simplexml_load_string($fileContents);
        $json = json_encode($simpleXml);
		$array = json_decode($json,true);
		
		foreach($array as $arr) {
			foreach($arr as $key=>$value) {
				//gestione dei dati contenuti nel file xml
				$datiRichiesta = $value['DatiRichiesta'];
				$datiRecuperati = $value['DatiRisposta']['DatiRecuperati'];
				
				$datiVeicolo = $datiRecuperati['DatiVeicolo'];
				$datiDettaglio = $datiRecuperati['DatiDtt'];
				$datiTecnici = $datiRecuperati['DatiTecnici'];
				$datiIntestazione = $datiRecuperati['DatiIntestazione'];
				$datiAnnotazione = $datiRecuperati['Annotazioni'];
				
				//controllo se esiste il contratto con quella targa
				$pratica = getRows("SELECT IdContratto, CodContratto, IdCompagnia FROM contratto WHERE CodBene='".$datiRichiesta['Targa']."' GROUP BY CodContratto, IdCompagnia");
				if (count($pratica)>0) {
				    //Dati veicolo
					$parameter["targa"] = $datiRichiesta['Targa'];
					$parameter["dataRichiesta"]= date("d/m/Y",strtotime($datiRichiesta['DataRichiesta']));
					$parameter["telaio"] = $datiTecnici['Telaio'];
					$parameter["fabbricaTipo"] = $datiTecnici['TipoOmologato'];
					$modelloCommerciale = $datiTecnici['ModelloCommerciale'];
					$parameter["modelloCommerciale"] = $modelloCommerciale['Fabbrica']." ".$modelloCommerciale['Tipo'];
					$parameter["dataImmatricolazione"] = date("d/m/Y",strtotime($datiTecnici['DataPrimaImmatricolazione']));
					$parameter["kw"] = $datiTecnici['Kilowatt'];
					$classeUso = $datiTecnici['Classe']['Descrizione']." / ".$datiTecnici['Uso'][Descrizione];
					$parameter["classeUso"] = $classeUso;
					$parameter["carrozzeria"] = $datiTecnici['Carrozzeria']['Descrizione'];
					$parameter["cilindrata"] = $datiTecnici['Cilindrata'];
					$parameter["alimentazione"] = $datiTecnici['Alimentazione']['Descrizione'];;
					$parameter["tara"] = $datiTecnici['Tara'];
					$parameter["portata"] = $datiTecnici['Portata'];
					$parameter["peso"] = $datiTecnici['PesoComplessivo'];
					$parameter["dispAntinquinamento"] = $datiTecnici['NormativaAntinquinamento'];
					$parameter["posti"] = $datiTecnici['Posti'];
					$parameter["assi"] = $datiTecnici['Assi'];
					$parameter["ultimaFormalita"] = $datiVeicolo['UltimaFormalita']['Descrizione'];
					$parameter["dataUltimaFormalita"] = date("d/m/Y",strtotime($datiVeicolo['UltimaFormalita']['Data']));
					$datiIntRpSettore = $datiIntestazione['Formalita']['Rp']['Settore'];
					$datiIntRpProgressivo = $datiIntestazione['Formalita']['Rp']['Progressivo'];
					$datiIntRpControllo = $datiIntestazione['Formalita']['Rp']['Controllo'];
					$parameter["rp"] = "$datiIntRpSettore$datiIntRpProgressivo$datiIntRpControllo";
					
					//Dati intestazione
					$parameter["datiIntRp"] = "$datiIntRpSettore$datiIntRpProgressivo$datiIntRpControllo";
					$parameter["del"] = date('d/m/Y',strtotime($datiIntestazione['Formalita']['Data']));
					$parameter["atto"] = $datiIntestazione['Formalita']['Atto']['Descrizione'];
					$parameter["dataAtto"] = date('d/m/Y',strtotime($datiIntestazione['Formalita']['Atto'][Data]));
					$parameter["prezzo"] = number_format($datiIntestazione['ImportoVeicolo'], 2, ',', '.');
					
					//Dati proprietario
					$datiIntestatario = $datiIntestazione['Intestatario'];
					$proprietario = $datiIntestatario['Denominazione']['Cognome']." ".$datiIntestatario['Denominazione']['Nome'];
					$parameter["proprietario"] = $proprietario;
					$parameter["sessoTS"] = $datiIntestatario['Denominazione']['Sesso'];
					$parameter["CF"] = $datiIntestatario['Denominazione']['CodiceFiscale'];
					$parameter["dataNascita"] = date('d/m/Y',strtotime($datiIntestatario['Nascita']['Data']));
					$parameter["comuneNasc"] = $datiIntestatario['Nascita']['Comune']." (".$datiIntestatario['Nascita']['Provincia'].")";
					$parameter["codiceComuneNasc"] = $datiIntestatario['Nascita']['ComuneNascitaFinanze'];
					$parameter["codiceComuneNascISTAT"] = $datiIntestatario['Nascita']['ComuneNascitaIstat'];
					$parameter["comuneResidenza"] = $datiIntestatario['Residenza']['Comune']." (".$datiIntestatario['Residenza']['Provincia'].")";
					$via = $datiIntestatario['Residenza']['Dug']." ".$datiIntestatario['Residenza']['Toponimo'];
					$civico = $datiIntestatario['Residenza']['NumeroCivico'];
					$cap = $datiIntestatario['Residenza']['Cap'];
					$parameter["indirizzo"] = $via." ".$civico." - ".$cap;
					$parameter["codiceComuneRes"] = $datiIntestatario['Residenza']['ComuneResidenzaFinanze'];
					$parameter["codiceComuneResISTAT"] = $datiIntestatario['Residenza']['ComuneResidenzaIstat'];
					
					//annotazioni
					$parameter["annotazioni"] = $datiAnnotazione['Annotazione']['0'];
					$parameter["annotazioni2"] = $datiAnnotazione['Annotazione']['1'];
					$parameter["annotazioni3"] = $datiAnnotazione['Annotazione']['2'];
					$parameter["annotazioni4"] = $datiAnnotazione['Annotazione']['3'];
					$parameter["dataSistema"] = $value['DatiRisposta']['DataDiSistema'];
					//trace("parametri: ".print_r($parameter,true));
					
					$fileVisureAci = 'visureACI.html';
					$content = file_get_contents(TEMPLATE_PATH.'/'.$fileVisureAci);
					$visura = replaceVariables($content,$parameter);
					//trace("file output: ".$visura);
					$fileName =	"VisuraACI".$datiRichiesta['Targa'].".pdf";
	                $newFile  = $localDir."/".$fileName;
					$result = creaPdfDaHtmlACI($visura,$newFile);
					
					if(!result){
						Throw new Exception("Errore nella scrittura del file $newFile");
						trace("Errore nella scrittura del file $newFile",false);
						return  false;
					}
					//ciclo per inserire in allegato il pdf creato
					for ($i=0;$i<count($pratica);$i++) {
					  $IdAllegato = getScalar("SELECT IdAllegato FROM allegato WHERE IdContratto=".$pratica[$i]['IdContratto']." AND IdTipoAllegato=13");
					  if(allegaDocumentoPDF($pratica[$i],"13","Sistema informativo A.C.I.","N",$fileName,$newFile,$idImportLog="NULL")){
					  	$numAllegati += 1; 
					  	//cancellazione dell'allegato precedentemente inserito
					  	if ($IdAllegato!=='' && $IdAllegato!==null) {
						  	$sqlDelete = ("DELETE FROM allegato WHERE IdAllegato=$IdAllegato");
						  	execute($sqlDelete);
						}	
					  }	
					}
					unlink($newFile);	
				}
			}
		}
		//$info[]= array("filePath"=>$filePath);
		$info="Allegati numero $numAllegati file di visure ACI";
	}
	success($info);
}

//----------------------------------------------------------------------------------------------
// allegaDocumentoPDF
// Allega un documento 
// Argomenti: 1) riga di v_pratiche oppure IdContratto
//            2) tipo allegato
//            3) titolo allegato
//            4) flag riservato
//            5) nome del file
//            6) path del pdf generato
// Ritorna:   IdAllegato della nuova riga oppure false
//----------------------------------------------------------------------------------------------
function allegaDocumentoPDF($pratica,$idtipo,$titolo,$riservato,$fileName,$pathFile,$idImportLog="NULL")
{
	
	try
	{
		global $context;
		
		if (!is_array($pratica))
			$pratica = getRow("SELECT * from v_pratiche WHERE IdContratto=$pratica");
				
		$fileName=urldecode($fileName);
						
		if(!get_magic_quotes_gpc())
			$fileName = addslashes($fileName);
		
		// 14/8/2011: per evitare problemi di permission, genera il file in un subfolder che si chiama come lo userid
		// del processo corrente
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
		//trace("allegaDocumento 3",false);
		
		if (copy($pathFile, $localDir."/".$fileName))
		{
			chmod($localDir."/".$fileName,0777);
			$titolo = quote_smart($titolo);
			$IdContratto = $pratica['IdContratto'];
			$url = quote_smart(str_replace(ATT_PATH,REL_PATH,$localDir)."/".$fileName);			
			$userid = getUserName($IdUtente);
			$userid = quote_smart($userid);
			$riservato = quote_smart($riservato);
			
			$master=$context["master"];
			//trace("master ".$master);
			$varNameS='';
			$Master='';
			if($master!=''){
				$varNameS=',lastSuper';
				$Master=",'$master'";
			}
			
			$sql = "INSERT INTO allegato (IdContratto, TitoloAllegato, UrlAllegato,"
								." IdUtente,LastUser, IdTipoAllegato, FlagRiservato,IdImportLog$varNameS) "
								."VALUES($IdContratto,$titolo,$url,$IdUtente,$userid,$idtipo,$riservato,$idImportLog$Master)"; 
//			trace("sql $sql");
			$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
			writeHistory($idAzione,"Allegato documento",$pratica['IdContratto'],"Documento: $titolo Contratto:".$pratica['CodContratto']);				
			if (execute($sql)) {
				return getInsertId();
			} else {
				return false;
			}
				
		}
		else
		{
			setLastError("Impossibile copiare il file nel repository");
			trace("Impossibile copiare il file nel repository");
			return FALSE;
		}
		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastError($e->getMessage());
		return FALSE;
	}	
}

/**
 * creaPdfDaHtmlACI
 * Crea un file PDF a partire da un testo HTML, utilizzando la libreria tcpdf
 * @param {String} $html testo HTML
 * @param {String} $filePath path del file di output (completo di nome file)
 */
function creaPdfDaHtmlACI($html,$filePath) {
	try {
		trace("Creazione PDF da HTML su $filePath",false);
		//create a new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		$pdf->SetMargins(15,51); // millimetri
		
		// remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		// ---------------------------------------------------------
		
		// set font		
		$pdf->AddFont('PdfaHelvetica', '',__DIR__."/tcpdf/fonts/pdfahelvetica.php" );
		$pdf->SetFont('PdfaHelvetica', '', 10);
		
		//add a page
		$pdf->AddPage();
		
		$pdf->SetAutoPageBreak(false, 0);
		$img_file = '../images/visuraACI2.png';
        $pdf->Image($img_file, 0, 2, 210, 295, '', '', '', false, 300, '', false, false, 0);
        $pdf->SetAutoPageBreak(true, 0);
		$pdf->setPageMark();
		
		//print text
		$pdf->writeHTML($html,true, 0, true,0);
				
		//close and output pdf document
		ob_end_clean();
		$pdf->Output($filePath, 'F');
		trace("Registrato file $filePath",false);
		unset($pdf);
		return true;
	} catch (Exception $e) {
		trace($e->getMessage(),true);
		return false;
	}		
}