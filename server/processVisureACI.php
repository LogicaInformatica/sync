<?php
/**
 * processVisureACI: esegue le varie fasi previste per il caricamento di un file delle visure ACI:
 * 1) caricamento dei files da form
 * 2) elaborazione del file xml importato e creazione del file pdf della visura richiesta tramite targa
 * 3) allegare il file pdf creato al contratto legato alla targa e cancellazione, se esistente, dell'allegato 
 *    precedentemente inserito
 * 4) restituzione del messaggio con il numero degli allegati inseriti
 */
require_once('tcpdf/tcpdf.php');
require_once("workflowFunc.php");
require_once("userFunc.php");

//require_once("tcpdf/examples/example_051.php");
/*
 * chiude la sessione per evitare di bloccare le richieste ajax concorrenti
 */
session_write_close();

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {
    public $footer;
     
    public function setData($footer){
    $this->footer = $footer;
    }
	 
    //Page header
    public function Header() {
        $this->SetAutoPageBreak(false, 0);
		$img_file = '../images/visuraAci.png';
        $this->Image($img_file, 0, 12, 210, 285, '', '', '', false, 300, '', false, false, 0);
        $this->SetAutoPageBreak(true, 12);
		$this->setPageMark();
    }
	
	 // Page footer
    public function Footer() {
    	// Position at 15 mm from bottom
        $this->SetY(-13);
        // set font		
		$this->AddFont('Helvetica', '',__DIR__."/tcpdf/fonts/helvetica.php" );
		$this->SetFont('Helvetica', '', 10);
        // Page number
        $this->Cell(0, 11, $this->footer, 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->Cell(17, 11, 'Pagina '.$this->getAliasNumPage().' di '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}		


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
				$datiLocazione = $datiRecuperati['DatiLocazione'];
				$datiVincoli = $datiRecuperati['DatiVincolo'];
				$datiPrd = $datiRecuperati['DatiPrd'];
				$datiUsufrutto = $datiRecuperati['DatiUsufrutto'];
				$datiIpoteche = $datiRecuperati['DatiIpoteca'];
				$datiAnnotazione = $datiRecuperati['Annotazioni'];
				
				//controllo se esiste il contratto con quella targa
				$pratica = getRows("SELECT IdContratto, CodContratto, IdCompagnia FROM contratto WHERE CodBene='".$datiRichiesta['Targa']."' GROUP BY CodContratto, IdCompagnia");
				if (count($pratica)>0) {
				    //Dati veicolo
					$parameter["targa"] = $datiRichiesta['Targa'];
					if ($datiRichiesta['DataRichiesta']!=="") {
					  $parameter["dataRichiesta"]= date("d/m/Y",strtotime($datiRichiesta['DataRichiesta']));	
					}
					$parameter["telaio"] = $datiTecnici['Telaio'];
					$parameter["fabbricaTipo"] = $datiTecnici['TipoOmologato'];
					$modelloCommerciale = $datiTecnici['ModelloCommerciale'];
					$parameter["modelloCommerciale"] = $modelloCommerciale['Fabbrica']." ".$modelloCommerciale['Tipo'];
					if ($datiTecnici['DataPrimaImmatricolazione']!=="") {
					   $parameter["dataImmatricolazione"] = date("d/m/Y",strtotime($datiTecnici['DataPrimaImmatricolazione']));	
					}
					$parameter["kw"] = $datiTecnici['Kilowatt'];
					$classeUso = $datiTecnici['Classe']['Descrizione']." / ".$datiTecnici['Uso'][Descrizione];
					$parameter["classeUso"] = $classeUso;
					$parameter["carrozzeria"] = $datiTecnici['Carrozzeria']['Descrizione'];
					$parameter["cilindrata"] = $datiTecnici['Cilindrata'];
					$parameter["alimentazione"] = $datiTecnici['Alimentazione']['Descrizione'];;
					$parameter["tara"] = $datiTecnici['Tara'];
					$parameter["portata"] = $datiTecnici['Portata'];
					$parameter["pesoComplessivo"] = $datiTecnici['PesoComplessivo'];
					$parameter["dispAntinquinamento"] = $datiTecnici['NormativaAntinquinamento'];
					$parameter["posti"] = $datiTecnici['Posti'];
					$parameter["assi"] = $datiTecnici['Assi'];
					$parameter["ultimaFormalita"] = $datiVeicolo['UltimaFormalita']['Descrizione'];
					if ($datiVeicolo['UltimaFormalita']['Data']!=="") {
					   $parameter["dataUltimaFormalita"] = date("d/m/Y",strtotime($datiVeicolo['UltimaFormalita']['Data']));
					}
					$datiRpSettore = $datiVeicolo['UltimaFormalita']['Rp']['Settore'];
					$datiRpProgressivo = $datiVeicolo['UltimaFormalita']['Rp']['Progressivo'];
					$datiRpControllo = $datiVeicolo['UltimaFormalita']['Rp']['Controllo'];
					$parameter["rp"] = "$datiRpSettore$datiRpProgressivo$datiRpControllo";
					
					//Dati intestazione
					$datiIntRpSettore = $datiIntestazione['Formalita']['Rp']['Settore'];
					$datiIntRpProgressivo = $datiIntestazione['Formalita']['Rp']['Progressivo'];
					$datiIntRpControllo = $datiIntestazione['Formalita']['Rp']['Controllo'];
					$parameter["datiIntRp"] = "$datiIntRpSettore$datiIntRpProgressivo$datiIntRpControllo";
					if ($datiIntestazione['Formalita']['Data']!=="") {
					   $parameter["del"] = date('d/m/Y',strtotime($datiIntestazione['Formalita']['Data']));	
					}
					$parameter["atto"] = $datiIntestazione['Formalita']['Atto']['Descrizione'];
					if ($datiIntestazione['Formalita']['Atto'][Data]!=="") {
					   $parameter["dataAtto"] = date('d/m/Y',strtotime($datiIntestazione['Formalita']['Atto'][Data]));
					}
					$parameter["prezzo"] = number_format($datiIntestazione['ImportoVeicolo'], 2, ',', '.');
					
					//Dati proprietario
					$numIntestatari = $datiIntestazione['NumeroIntestatari'];
					$datiIntestatario = $datiIntestazione['Intestatario'];
					$contentIntestatario = file_get_contents(TEMPLATE_PATH.'/intestatarioVisureACI.txt');
					$intestatario="";
					//controllo se esistono più proprietari
					if ($numIntestatari>1) {
					  //ciclo i propretari per visualizzarli	
					  for ($i=0;$i<$numIntestatari;$i++) {
				   	     $proprietario = $datiIntestatario[$i]['Denominazione']['Cognome']." ".$datiIntestatario[$i]['Denominazione']['Nome'];
						 $parameterInt["proprietario"] = $proprietario;
						 $parameterInt["sessoTS"] = $datiIntestatario[$i]['Denominazione']['Sesso'];
						 $parameterInt["CF"] = $datiIntestatario[$i]['Denominazione']['CodiceFiscale'];
						 if ($datiIntestatario[$i]['Nascita']['Data']!=="") {
							$parameterInt["dataNascita"] = date('d/m/Y',strtotime($datiIntestatario[$i]['Nascita']['Data']));
						 }
						 $parameterInt["comuneNasc"] = $datiIntestatario[$i]['Nascita']['Comune']." (".$datiIntestatario[$i]['Nascita']['Provincia'].")";
						 $parameterInt["codiceComuneNasc"] = $datiIntestatario[$i]['Nascita']['ComuneNascitaFinanze'];
						 $parameterInt["codiceComuneNascISTAT"] = $datiIntestatario[$i]['Nascita']['ComuneNascitaIstat'];
						 $parameterInt["comuneResidenza"] = $datiIntestatario[$i]['Residenza']['Comune']." (".$datiIntestatario[$i]['Residenza']['Provincia'].")";
						 $via = $datiIntestatario[$i]['Residenza']['Dug']." ".$datiIntestatario[$i]['Residenza']['Toponimo'];
						 $civico = $datiIntestatario[$i]['Residenza']['NumeroCivico'];
						 $cap = $datiIntestatario[$i]['Residenza']['Cap'];
						 $parameterInt["indirizzo"] = $via." ".$civico." - ".$cap;
						 $parameterInt["codiceComuneRes"] = $datiIntestatario[$i]['Residenza']['ComuneResidenzaFinanze'];
						 $parameterInt["codiceComuneResISTAT"] = $datiIntestatario[$i]['Residenza']['ComuneResidenzaIstat'];
						 $intestatario .= replaceVariables($contentIntestatario,$parameterInt);
				      }	 	
					} else {
						 $proprietario = $datiIntestatario['Denominazione']['Cognome']." ".$datiIntestatario['Denominazione']['Nome'];
						 $parameterInt["proprietario"] = $proprietario;
						 $parameterInt["sessoTS"] = $datiIntestatario['Denominazione']['Sesso'];
						 $parameterInt["CF"] = $datiIntestatario['Denominazione']['CodiceFiscale'];
						 if ($datiIntestatario['Nascita']['Data']!=="") {
							$parameterInt["dataNascita"] = date('d/m/Y',strtotime($datiIntestatario['Nascita']['Data']));
						 }
						 $parameterInt["comuneNasc"] = $datiIntestatario['Nascita']['Comune']." (".$datiIntestatario['Nascita']['Provincia'].")";
						 $parameterInt["codiceComuneNasc"] = $datiIntestatario['Nascita']['ComuneNascitaFinanze'];
						 $parameterInt["codiceComuneNascISTAT"] = $datiIntestatario['Nascita']['ComuneNascitaIstat'];
						 $parameterInt["comuneResidenza"] = $datiIntestatario['Residenza']['Comune']." (".$datiIntestatario['Residenza']['Provincia'].")";
						 $via = $datiIntestatario['Residenza']['Dug']." ".$datiIntestatario['Residenza']['Toponimo'];
						 $civico = $datiIntestatario['Residenza']['NumeroCivico'];
						 $cap = $datiIntestatario['Residenza']['Cap'];
						 $parameterInt["indirizzo"] = $via." ".$civico." - ".$cap;
						 $parameterInt["codiceComuneRes"] = $datiIntestatario['Residenza']['ComuneResidenzaFinanze'];
						 $parameterInt["codiceComuneResISTAT"] = $datiIntestatario['Residenza']['ComuneResidenzaIstat'];
						 $intestatario .= replaceVariables($contentIntestatario,$parameterInt);
					  }
					$parameter["intestatario"] = $intestatario;
					
					//Dati vincolo
					$numVincoli = $datiVincoli['QuantitaVincoli'];
					$datiVincolo = array();
					$datiVincolo = $datiVincoli['Vincolo'];
					$contentVincolo = file_get_contents(TEMPLATE_PATH.'/vincoloVisureACI.txt');
					$vincolo="";
					//controllo la presenza di un vincolo
					if (count($datiVincolo)>0) {
					  //controllo la presenza di più vincoli	
					  if ($numVincoli>1) {
						  //ciclo tutti i vincoli per visualizzarli	
						  for ($i=0;$i<$numVincoli;$i++) {
							 if ($datiVincolo[$i]["Formalita"]["Data"]!=="") {
								$parameterVincolo["del"]= date("d/m/Y",strtotime($datiVincolo[$i]["Formalita"]["Data"]));
							 }
						  	 $settore = $datiVincolo[$i]["Formalita"]["Rp"]["Settore"]; 
	                         $progressivo = $datiVincolo[$i]["Formalita"]["Rp"]["Progressivo"];
	                         $controllo = $datiVincolo[$i]["Formalita"]["Rp"]["Controllo"];
							 $parameterVincolo["datiIntRp"]= $settore.$progressivo.$controllo;
							 $parameterVincolo["atto"]= $datiVincolo[$i]["Formalita"]["Atto"]["Descrizione"];
							 if ($datiVincolo[$i]["Formalita"]["Atto"]["Data"]!=="") {
								$parameterVincolo["dataAtto"]= date("d/m/Y",strtotime($datiVincolo[$i]["Formalita"]["Atto"]["Data"]));
							 }
							 $parameterVincolo["causale"]= $datiVincolo[$i]["TipoProvvedimento"];
							 $parameterVincolo["importoConcorrenza"]= number_format($datiVincolo[$i]["ImportoConcorrenza"], 2, ',', '.'); 
							 $numAttori = $datiVincolo[$i][NumeroAttori];
							 $datiAttore = $datiVincolo[$i]['Attore'];
							 $attori = '';
							 //controllo la presenza di più attori del vincolo
							 if ($numAttori>1) {
							    //ciclo tutti gli attori per visualizzarli	
							    for ($j=0;$j<$numAttori;$j++) {
							      $attore = $datiAttore[$j]['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								  //$parameterVincolo["attore"] = $attore;
								  $sessoTS = $datiAttore[$j]['Denominazione']['TipoSocieta'];//['Sesso'];
								  $CF = $datiAttore[$j]['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								  $comuneResidenza = $datiAttore[$j]['Residenza']['Comune']." (".$datiAttore[$j]['Residenza']['Provincia'].")";
								  $via = $datiAttore[$j]['Residenza']['Dug']." ".$datiAttore[$j]['Residenza']['Toponimo'];
								  $civico = $datiAttore[$j]['Residenza']['NumeroCivico'];
								  $cap = $datiAttore[$j]['Residenza']['Cap'];
								  $indirizzo = $via." ".$civico." - ".$cap;
								  $codiceComuneRes = $datiAttore[$j]['Residenza']['ComuneResidenzaFinanze'];
								  $codiceComuneResISTAT = $datiAttore[$j]['Residenza']['ComuneResidenzaIstat'];
								  $attori .= '<tr><td width="295px"><font size="11"><b>Attore</b></font></td><td><font size="11"><b>'.$attore.'</b></font></td></tr>'.
								 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
								 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
								 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
								 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
											'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
											'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>';	
							    }
                             } else {
							 	 $attore = $datiAttore['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								 //$parameterVincolo["attore"] = $attore;
								 $sessoTS = $datiAttore['Denominazione']['TipoSocieta'];//['Sesso'];
								 $CF = $datiAttore['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								 $comuneResidenza = $datiAttore['Residenza']['Comune']." (".$datiAttore['Residenza']['Provincia'].")";
								 $via = $datiAttore['Residenza']['Dug']." ".$datiAttore['Residenza']['Toponimo'];
								 $civico = $datiAttore['Residenza']['NumeroCivico'];
								 $cap = $datiAttore['Residenza']['Cap'];
								 $indirizzo = $via." ".$civico." - ".$cap;
								 $codiceComuneRes = $datiAttore['Residenza']['ComuneResidenzaFinanze'];
								 $codiceComuneResISTAT = $datiAttore['Residenza']['ComuneResidenzaIstat'];
								 $attori .= '<tr><td width="295px"><font size="11"><b>Attore</b></font></td><td><font size="11"><b>'.$attore.'</b></font></td></tr>'.
								 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
								 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
								 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
								 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
											'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
											'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>';
							 }
							 $parameterVincolo["attori"] = $attori;
							 $parameterVincolo["importoConcorrenza"] = number_format($datiVincolo[$i]['ImportoConcorrenza'], 2, ',', '.');
							 $vincolo .= replaceVariables($contentVincolo,$parameterVincolo);
					      }	 	
					  } else {
							 if ($datiVincolo["Formalita"]["Data"]!=="") {
								$parameterVincolo["del"]= date("d/m/Y",strtotime($datiVincolo["Formalita"]["Data"]));
							 }
							 $settore = $datiVincolo["Formalita"]["Rp"]["Settore"]; 
	                         $progressivo = $datiVincolo["Formalita"]["Rp"]["Progressivo"];
	                         $controllo = $datiVincolo["Formalita"]["Rp"]["Controllo"];
							 $parameterVincolo["datiIntRp"]= $settore.$progressivo.$controllo;
							 $parameterVincolo["atto"]= $datiVincolo["Formalita"]["Atto"]["Descrizione"];
							 if ($datiVincolo["Formalita"]["Atto"]["Data"]!=="") {
								$parameterVincolo["dataAtto"]= date("d/m/Y",strtotime($datiVincolo["Formalita"]["Atto"]["Data"]));
							 }
							 $parameterVincolo["causale"]= $datiVincolo["TipoProvvedimento"];
							 $parameterVincolo["importoConcorrenza"]= number_format($datiVincolo["ImportoConcorrenza"], 2, ',', '.');
							 $numAttori = $datiVincolo[NumeroAttori];
							 $datiAttore = $datiVincolo['Attore'];
							 $attori = '';
							 //controllo la presenza di più attori del vincolo
							 if ($numAttori>1) {
							    //ciclo tutti gli attori del vincolo per visualizzarli	
							    for ($j=0;$j<$numVincoli;$j++) {
							      $attore = $datiAttore[$j]['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								  //$parameterVincolo["attore"] = $attore;
								  $sessoTS = $datiAttore[$j]['Denominazione']['TipoSocieta'];//['Sesso'];
								  $CF = $datiAttore[$j]['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								  $comuneResidenza = $datiAttore[$j]['Residenza']['Comune']." (".$datiAttore[$j]['Residenza']['Provincia'].")";
								  $via = $datiAttore[$j]['Residenza']['Dug']." ".$datiAttore[$j]['Residenza']['Toponimo'];
								  $civico = $datiAttore[$j]['Residenza']['NumeroCivico'];
								  $cap = $datiAttore[$j]['Residenza']['Cap'];
								  $indirizzo = $via." ".$civico." - ".$cap;
								  $codiceComuneRes = $datiAttore[$j]['Residenza']['ComuneResidenzaFinanze'];
								  $codiceComuneResISTAT = $datiAttore[$j]['Residenza']['ComuneResidenzaIstat'];
								  $attori .= '<tr><td width="295px"><font size="11"><b>Attore</b></font></td><td><font size="11"><b>'.$attore.'</b></font></td></tr>'.
								 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
								 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
								 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
								 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
											'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
											'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>';	
							    }
                             } else {
							 	 $attore = $datiAttore['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								 //$parameterVincolo["attore"] = $attore;
								 $sessoTS = $datiAttore['Denominazione']['TipoSocieta'];//['Sesso'];
								 $CF = $datiAttore['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								 $comuneResidenza = $datiAttore['Residenza']['Comune']." (".$datiAttore['Residenza']['Provincia'].")";
								 $via = $datiAttore['Residenza']['Dug']." ".$datiAttore['Residenza']['Toponimo'];
								 $civico = $datiAttore['Residenza']['NumeroCivico'];
								 $cap = $datiAttore['Residenza']['Cap'];
								 $indirizzo = $via." ".$civico." - ".$cap;
								 $codiceComuneRes = $datiAttore['Residenza']['ComuneResidenzaFinanze'];
								 $codiceComuneResISTAT = $datiAttore['Residenza']['ComuneResidenzaIstat'];
								 $attori .= '<tr><td width="295px"><font size="11"><b>Attore</b></font></td><td><font size="11"><b>'.$attore.'</b></font></td></tr>'.
								 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
								 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
								 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
								 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
											'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
											'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>';
							 }
							 $parameterVincolo["attori"] = $attori;
							 $parameterVincolo["importoConcorrenza"] = number_format($datiVincolo['ImportoConcorrenza'], 2, ',', '.');
							 $vincolo .= replaceVariables($contentVincolo,$parameterVincolo);
						  }
	                }
                    $parameter["vincolo"] = $vincolo;
									
					//Dati ipoteca
					$numIpoteche = $datiIpoteche['QuantitaIpoteche'];
					$datiIpoteca = array();
					$datiIpoteca = $datiIpoteche['Ipoteca'];
					$contentIpoteca = file_get_contents(TEMPLATE_PATH.'/ipotecaVisureACI.txt');
					$ipoteca="";
					//controllo se presenti ipoteche
					if (count($datiIpoteca)>0) {
						//controllo se ci sono più ipoteche	
						if ($numIpoteche>1) {
						  //ciclo tutte le ipoteche per visualizzarle	
						  for ($i=0;$i<$numIpoteche;$i++) {
							if ($datiIpoteca[$i]["Formalita"]["Data"]!=="") {
							   $parameterIpoteca["del"]= date("d/m/Y",strtotime($datiIpoteca[$i]["Formalita"]["Data"]));	
							}
						  	$settore = $datiIpoteca[$i]["Formalita"]["Rp"]["Settore"]; 
	                        $progressivo = $datiIpoteca[$i]["Formalita"]["Rp"]["Progressivo"];
	                        $controllo = $datiIpoteca[$i]["Formalita"]["Rp"]["Controllo"];
							$parameterIpoteca["datiIntRp"]= $settore.$progressivo.$controllo;
							$parameterIpoteca["atto"]= $datiIpoteca[$i]["Formalita"]["Atto"]["Descrizione"];
							if ($datiIpoteca[$i]["Formalita"]["Atto"]["Data"]!=="") {
							   $parameterIpoteca["dataAtto"]= date("d/m/Y",strtotime($datiIpoteca[$i]["Formalita"]["Atto"]["Data"]));	
							}
							if ($datiIpoteca[$i]["DataScadenzaCredito"]!=="") {
							   $parameterIpoteca["termineEsCred"]= date("d/m/Y",strtotime($datiIpoteca[$i]["DataScadenzaCredito"]));
							}
	                        $parameterIpoteca["tipoCredito"]= $datiIpoteca[$i]["TipoCredito"];
							if (isset($datiIpoteca[$i]["StatoIpotecaCumulativo"])) {
							   $statoIpoteca = '<tr><td width="295px">'.$datiIpoteca[$i]["StatoIpotecaCumulativo"].'</td></tr>';
							   $parameterIpoteca["statoIpotecaCumulativo"]= $statoIpoteca;		
							} else {
								$parameterIpoteca["statoIpotecaCumulativo"]='';
							}
							$parameterIpoteca["causaleCredito"]= $datiIpoteca[$i]["CausaleCredito"];
							$parameterIpoteca["importoCredito"] = number_format($datiIpoteca[$i]['ImportoCredito'], 2, ',', '.');
							$parameterIpoteca["importoCapitale"] = number_format($datiIpoteca[$i]['ImportoCapitale'], 2, ',', '.');
							$numCreditori = $datiIpoteca[$i]['NumeroCreditori'];
							$datiCreditore = $datiIpoteca[$i]['Creditore'];
							$creditori="";
							//controllo la presenza di più creditori
							if ($numCreditori>1) {
								//ciclo tutti i creditori per visualizzarli
								for ($j=0;$j<$numCreditori;$j++) {
									$creditore = $datiCreditore[$j]['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
									$sessoTS = $datiCreditore[$j]['Denominazione']['TipoSocieta'];//['Sesso'];
									$CF = $datiCreditore[$j]['Denominazione']['PartitaIva'];//['CodiceFiscale'];
									$comuneResidenza = $datiCreditore[$j]['Residenza']['Comune']." (".$datiCreditore[$j]['Residenza']['Provincia'].")";
									$via = $datiCreditore[$j]['Residenza']['Dug']." ".$datiCreditore[$j]['Residenza']['Toponimo'];
									$civico = $datiCreditore[$j]['Residenza']['NumeroCivico'];
									$cap = $datiCreditore[$j]['Residenza']['Cap'];
									$indirizzo = $via." ".$civico." - ".$cap;
									$codiceComuneRes = $datiCreditore[$j]['Residenza']['ComuneResidenzaFinanze'];
									$codiceComuneResISTAT = $datiCreditore[$j]['Residenza']['ComuneResidenzaIstat'];
									$creditori .= '<tr><td width="295px"><font size="11"><b>Creditore</b></font></td><td><font size="11"><b>'.$creditore.'</b></font></td></tr>'.
									 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
									 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
									 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
									 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
												'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
												'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>'.
												'<tr><td></td></tr>';
								}
							} else {
								//caso in cui c'è un solo creditore per l'ipoteca
								$creditore = $datiCreditore['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								$sessoTS = $datiCreditore['Denominazione']['TipoSocieta'];//['Sesso'];
								$CF = $datiCreditore['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								$comuneResidenza = $datiCreditore['Residenza']['Comune']." (".$datiCreditore['Residenza']['Provincia'].")";
								$via = $datiCreditore['Residenza']['Dug']." ".$datiCreditore['Residenza']['Toponimo'];
								$civico = $datiCreditore['Residenza']['NumeroCivico'];
								$cap = $datiCreditore['Residenza']['Cap'];
								$indirizzo = $via." ".$civico." - ".$cap;
								$codiceComuneRes = $datiCreditore['Residenza']['ComuneResidenzaFinanze'];
								$codiceComuneResISTAT = $datiCreditore['Residenza']['ComuneResidenzaIstat'];
								$creditori .= '<tr><td width="295px"><font size="11"><b>Creditore</b></font></td><td><font size="11"><b>'.$creditore.'</b></font></td></tr>'.
									 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
									 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
									 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
									 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
												'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
												'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>'.
												'<tr><td></td></tr>';
							  }
                            $parameterIpoteca["creditori"] = $creditori;
                            $ipoteca .= replaceVariables($contentIpoteca,$parameterIpoteca); 
                          }
                        } else {
							//il caso in cui c'è solo un'ipoteca	
							if ($datiIpoteca["Formalita"]["Data"]!=="") {
							   $parameterIpoteca["del"]= date("d/m/Y",strtotime($datiIpoteca["Formalita"]["Data"]));	
							}
							$settore = $datiIpoteca["Formalita"]["Rp"]["Settore"]; 
	                        $progressivo = $datiIpoteca["Formalita"]["Rp"]["Progressivo"];
	                        $controllo = $datiIpoteca["Formalita"]["Rp"]["Controllo"];
							$parameterIpoteca["datiIntRp"]= $settore.$progressivo.$controllo;
							$parameterIpoteca["atto"]= $datiIpoteca["Formalita"]["Atto"]["Descrizione"];
							if ($datiIpoteca["Formalita"]["Atto"]["Data"]!="") {
							   $parameterIpoteca["dataAtto"]= date("d/m/Y",strtotime($datiIpoteca["Formalita"]["Atto"]["Data"]));	
							}
							if ($datiIpoteca["DataScadenzaCredito"]!==""){
							   $parameterIpoteca["termineEsCred"]= date("d/m/Y",strtotime($datiIpoteca["DataScadenzaCredito"]));	
							}
							$parameterIpoteca["tipoCredito"]= $datiIpoteca["TipoCredito"];
							if (isset($datiIpoteca["StatoIpotecaCumulativo"])) {
							   $statoIpoteca = '<tr><td width="295px">'.$datiIpoteca["StatoIpotecaCumulativo"].'</td></tr>';
							   $parameterIpoteca["statoIpotecaCumulativo"]= $statoIpoteca;		
							} else {
								 $parameterIpoteca["statoIpotecaCumulativo"]='';
							}
							$parameterIpoteca["causaleCredito"]= $datiIpoteca["CausaleCredito"];
							$parameterIpoteca["importoCredito"] = number_format($datiIpoteca['ImportoCredito'], 2, ',', '.');
							$parameterIpoteca["importoCapitale"] = number_format($datiIpoteca['ImportoCapitale'], 2, ',', '.');
							//controllo quanti sono i creditori di questa ipoteca 
							$numCreditori = $datiIpoteca[NumeroCreditori];
							$datiCreditore = $datiIpoteca['Creditore'];
							$creditori = "";
							//controllo della presenza di più creditori
							if ($numCreditori>1) {
								//ciclo tutti i creditori per visualizzarli
								for ($j=0;$j<$numCreditori;$j++) {
									$creditore = $datiCreditore[$j]['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
									$sessoTS = $datiCreditore[$j]['Denominazione']['TipoSocieta'];//['Sesso'];
									$CF = $datiCreditore[$j]['Denominazione']['PartitaIva'];//['CodiceFiscale'];
									$comuneResidenza = $datiCreditore[$j]['Residenza']['Comune']." (".$datiCreditore[$j]['Residenza']['Provincia'].")";
									$via = $datiCreditore[$j]['Residenza']['Dug']." ".$datiCreditore[$j]['Residenza']['Toponimo'];
									$civico = $datiCreditore[$j]['Residenza']['NumeroCivico'];
									$cap = $datiCreditore[$j]['Residenza']['Cap'];
									$indirizzo = $via." ".$civico." - ".$cap;
									$codiceComuneRes = $datiCreditore[$j]['Residenza']['ComuneResidenzaFinanze'];
									$codiceComuneResISTAT = $datiCreditore[$j]['Residenza']['ComuneResidenzaIstat'];
									$creditori .= '<tr><td width="295px"><font size="11"><b>Creditore</b></font></td><td><font size="11"><b>'.$creditore.'</b></font></td></tr>'.
									 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
									 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
									 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
									 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
												'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
												'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>'.
												'<tr><td></td></tr>';
								}
							} else {
								//caso in cui c'è un solo creditore per l'ipoteca	
								$creditore = $datiCreditore['Denominazione']['Cognome']; //." ".$datiIntestatario['Denominazione']['Nome'];
								$sessoTS = $datiCreditore['Denominazione']['TipoSocieta'];//['Sesso'];
								$CF = $datiCreditore['Denominazione']['PartitaIva'];//['CodiceFiscale'];
								$comuneResidenza = $datiCreditore['Residenza']['Comune']." (".$datiCreditore['Residenza']['Provincia'].")";
								$via = $datiCreditore['Residenza']['Dug']." ".$datiCreditore['Residenza']['Toponimo'];
								$civico = $datiCreditore['Residenza']['NumeroCivico'];
								$cap = $datiCreditore['Residenza']['Cap'];
								$indirizzo = $via." ".$civico." - ".$cap;
								$codiceComuneRes = $datiCreditore['Residenza']['ComuneResidenzaFinanze'];
								$codiceComuneResISTAT = $datiCreditore['Residenza']['ComuneResidenzaIstat'];
								$creditori .= '<tr><td width="295px"><font size="11"><b>Creditore</b></font></td><td><font size="11"><b>'.$creditore.'</b></font></td></tr>'.
									 			'<tr><td width="295px">Sesso / Tipo Societa\'</td><td>'.$sessoTS.'</td></tr>'.
									 			'<tr><td width="295px">Codice Fiscale</td><td>'.$CF.'</td></tr>'.
									 			'<tr><td width="295px">Comune di residenza</td><td>'.$comuneResidenza.'</td></tr>'.
									 			'<tr><td width="295px">Codice Comune Residenza Ministero Finanze</td><td>'.$codiceComuneRes.'</td></tr>'.
												'<tr><td width="295px">Codice Comune Residenza ISTAT</td><td>'.$codiceComuneResISTAT.'</td></tr>'.
												'<tr><td width="295px">Indirizzo</td><td>'.$indirizzo.'</td></tr>'.
												'<tr><td></td></tr>';
							  }
                              $parameterIpoteca["creditori"] = $creditori;
                              $ipoteca .= replaceVariables($contentIpoteca,$parameterIpoteca);	 
						  }
                    }	
					$parameter["ipoteca"] = $ipoteca;
																		
					//annotazioni
					$annotazione= array();
					$annotazione = $datiAnnotazione['Annotazione'];
					$stringaAnnotazioni="";
					for($i=0;$i<count($annotazione);$i++) {
						$stringaAnnotazioni .= '<tr><td width="700px">'.$annotazione[$i].'</td></tr>';
					}
					$parameter["annotazioni"] = $stringaAnnotazioni;
					$parameter["dataSistema"] = $value['DatiRisposta']['DataDiSistema'];
					//trace("parametri: ".print_r($parameter,true));
					
					$footer = "Prodotto il ".$value['DatiRisposta']['DataDiSistema'];
					$fileVisureAci = 'visureACI.html';
					$content = file_get_contents(TEMPLATE_PATH.'/visureACI.html');
					
					if ($content == "") {
					  fail("Errore nella lettura del file $content");
					  Throw new Exception("Errore nella lettura del file $content");
					  //trace("Errore nella lettura del file $content",false);
					}
					
					$visura = replaceVariables($content,$parameter);
					//trace("file output: ".$visura);
					$fileName =	"VisuraACI".$datiRichiesta['Targa'].".pdf";
	                $newFile  = $localDir."/".$fileName;
					$result = creaPdfDaHtmlACI($visura,$footer,$newFile);
					
					if(!result){
						fail("Errore nella scrittura del file $newFile");
						Throw new Exception("Errore nella scrittura del file $newFile");
						//trace("Errore nella scrittura del file $newFile",false);
					}
					//ciclo per inserire in allegato il pdf creato
					for ($i=0;$i<count($pratica);$i++) {
					  $IdAllegato = getScalar("SELECT IdAllegato FROM allegato WHERE IdContratto=".$pratica[$i]['IdContratto']." AND IdTipoAllegato=13");
					  if(allegaDocumentoPDF($pratica[$i],"13","Sistema informativo A.C.I.","N",$fileName,$newFile,$idImportLog="NULL")){
					  	$numAllegati += 1; 
					  	//cancellazione dell'allegato precedentemente inserito
					  	if ($IdAllegato!=='' && $IdAllegato!==null) {
						  	$sqlDeleteAllegato = ("DELETE FROM allegato WHERE IdAllegato=$IdAllegato");
						  	execute($sqlDeleteAllegato);
						}
						//imposto il FlagVisuraAci della tabella contratto a Y
						$sqlUpdateFlagVisuraAci = "UPDATE contratto SET FlagVisuraAci = 'Y' WHERE IdContratto=".$pratica[$i]['IdContratto'];
						execute($sqlUpdateFlagVisuraAci);	
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
				fail("Impossibile creare la cartella $localDir");
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
function creaPdfDaHtmlACI($html,$footer,$filePath) {
	try {
				
		trace("Creazione PDF da HTML su $filePath",false);
		//create a new PDF document
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->setData($footer);
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		//$pdf->setPrintFooter(false);
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		// set margins
		$pdf->SetMargins(15,60); // millimetri
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
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
		$pdf->AddFont('Helvetica', '',__DIR__."/tcpdf/fonts/helvetica.php" );
		$pdf->SetFont('Helvetica', '', 10);
		
		$pdf->setCellHeightRatio(1.10);
		
		//add a page
		$pdf->AddPage();
		
		/*$pdf->SetAutoPageBreak(false, 0);
		$img_file = '../images/visuraAci.png';
        $pdf->Image($img_file, 0, 2, 210, 295, '', '', '', false, 300, '', false, false, 0);
        $pdf->SetAutoPageBreak(true, 0);
		$pdf->setPageMark();*/
		
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
?>