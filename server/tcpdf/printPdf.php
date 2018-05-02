<?php
require_once('tcpdf.php');
require_once('dbConnection.php');

global $context;
if($context["IdAgenzia"]<=0)
	die("Operazione non autorizzata");
else
	$age = getFetchArray("select Agenzia, Email,IF((PathImgHeaderSx is null || PathImgHeaderSx ='') ,'', concat('../',PathImgHeaderSx)) as PathImgHeaderSx,IF((PathImgHeaderDx is null || PathImgHeaderDx =''),'', concat('../',PathImgHeaderDx)) as PathImgHeaderDx, IF((PathImgFooter is null || PathImgFooter ='') ,'', concat('../',PathImgFooter)) as PathImgFooter, PathImgFirma From agenzia where IdAgenzia = ".$context["IdAgenzia"]);	
	
// GET PARAMS
$idTestoComunicazione = $_GET["IdTC"]; 		// se  viene chiamato dal dettaglio delle comunicazini batch gli passo idtestocomunicazione (in quanto la comunicazione deve essere ancora inviata)
$idComunicazione 	  = $_GET["IdC"];	    // se  viene chiamato dal dettaglio delle comunicazini clienti / polizze gli passo IdComunicazione (in quanto la comunicazione � stata gi� inviata)
$task 	  			  = $_GET["sr"];        // se  faccio una sta stampa comulativa dal pulsante "STAMPA PDF" della griglia comunicazioni batch, gli passso anche il task della griglia in quanto viene effettuato
											// solo la stampa delle comunicazioni di quella griglia e non tutte (es. arretrati)
$idsTC	  			  = $_GET["idsTC"];     // se  faccio una sta stampa comulativa dal pulsante "STAMPA PDF" e ho selezionato delle comunicazioni stampa solo quelle selezioante
$done	  			  = $_GET["done"];      // se  impostato a true indica che bisogna impostare le comunicazioni come gi� inviate										   
$saved 				  = $_GET["saved"];     // se  impostato a true indica che � la comunicazione � stata salvata ed eseguita dal pulsante "Stampa PDF" del dettaglio comun.batch e pertanto la com non si trova pi� in scadenza ma si trova in comunicazioni in quanto eseguita
$sql 				  = "";

// se devo fare una stampa comulativa 
if($task!="")
{
	$cond ="";
	
	//se l'utente ha selezionato delle comunicazioni prendo solo quelle  altrimenti prendo tutte le comunicazioni di tipo
	if($idsTC!="")
		$cond = " and IdTestoComunicazione in($idsTC)";
	else
		$cond = "  and Confermata ='S'";	
	
	switch($task)
	{
		case 'comPolInScad' :
					$cond .=" and IdTipoRegola=1";	
						
			break;
		case 'comPolScad' :
					$cond .=" and IdTipoRegola=2 ";	
			break;
		case 'comPolManuali' :
					$cond .=" and IdTipoRegola is null ";	
			break;	
		default;
					die("Operazione non disponibile");
			break;	
	}
	
	$sql = "select TestoCorpo,NominativoDestinatario,IdTestoComunicazione from v_scadenze_per_stampaPDF where  IdAgenzia = ".$context["IdAgenzia"].$cond. "  order by NominativoDestinatario ASC";
}
else
{
	     // sto eseguendo la stampa di una singola riga dal dettaglio comunicazioni polizza / cliente
		if($idComunicazione>0 && $idComunicazione != "" && $idComunicazione !=null)
			$sql = "select TestoCorpo,NominativoDestinatario,IdTestoComunicazione from v_comunicazioni_per_stampaPDF  where IdComunicazione=$idComunicazione and IdAgenzia = ".$context["IdAgenzia"]." order by NominativoDestinatario ASC";
		else
		{
			// sto eseguendo la stampa da una singola riga della grid comunicazioni batch
			if($idTestoComunicazione>0 && $idTestoComunicazione != "" && $idTestoComunicazione !=null)
				$sql = "select TestoCorpo,NominativoDestinatario,IdTestoComunicazione from v_scadenze_per_stampaPDF where  IdAgenzia = ".$context["IdAgenzia"]." and IdTestoComunicazione =$idTestoComunicazione ";
			else
				die("Operazione non disponibile");
		}	

}

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {
	
	//Page header
	public function Header() {
		// Logo sx
		if($this->header_left_logo !="")
		{
			$image_file = dirname(__FILE__)."/".$this->header_left_logo;
			$arrImg = getInfoImg($image_file,MAX_WIDTH_HEADER_IMG_SX_PDF,MAX_HEIGHT_HEADER_IMG_SX_PDF);
			$this->Image($image_file, 2,2, $arrImg["WIDTH"], $arrImg["HEIGHT"], $arrImg["TYPE"], '', '', false, 300, 'L', false, false, 0, false, false, false);
		}
		// Logo dx
		if($this->header_right_logo !="")
		{
			$image_file = dirname(__FILE__)."/".$this->header_right_logo;
			$arrImg = getInfoImg($image_file,MAX_WIDTH_HEADER_IMG_DX_PDF,MAX_HEIGHT_HEADER_IMG_DX_PDF);
//trace(print_r($arrImg,true));
			$this->Image($image_file, 2, 2, $arrImg["WIDTH"], $arrImg["HEIGHT"], $arrImg["TYPE"], '', '', false, 300, 'R', false, false, 0, false, false, false);
		}
	}

	// Page footer
	public function Footer() {
		$this->SetY(-15);
		if($this->footer_credits_image !="")
		{
			$image_file = dirname(__FILE__)."/".$this->footer_credits_image;
			$arrImg = getInfoImg($image_file,MAX_WIDTH_FOOTER_IMG_PDF,MAX_HEIGHT_FOOTER_IMG_PDF);
        	$this->Image($image_file, 5, 280,  $arrImg["WIDTH"], $arrImg["HEIGHT"], $arrImg["TYPE"], '', 'T', false, 300, '', false, false, 0, false, false, false);
		}
        
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 6);
        // Page number
        $this->Cell(0, 0, 'Pagina '.$this->getGroupPageNo().'/'.$this->getPageGroupAlias(), 0, false, 'R', 0, '', 0, false, 'C', 'B');
	}
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator($age[0]['Agenzia']);
$pdf->SetAuthor($age[0]['Agenzia']);
$pdf->SetTitle('Comunicazioni');
$pdf->SetSubject('Stampa comunicazioni');
$pdf->SetKeywords('Stampa comunicazioni');

//trace(dirname(__FILE__)."/".$age[0]["PathImgHeaderSx"],false);
// set default header data
//A $pdf->SetHeaderData('tcpdf_logo.jpg', 30, 'PDF_HEADER_TITLE', 'PDF_HEADER_STRING');

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->setFooterImgs($age[0]["PathImgFooter"]);
$pdf->setHeaderImgs($age[0]["PathImgHeaderSx"],$age[0]["PathImgHeaderDx"]);

 // set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);


// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
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
$pdf->SetFont('times', 'BI', 12);
//$pdf->SetFont('', '', PDF_FONT_SIZE, '', 'false'); //PDF_FONT_SIZE variabile globale


$arrayTxtToPrint = getFetchArray($sql);
$idTcSent = "";
for($i=0;$i<count($arrayTxtToPrint);$i++)
{
	$com =  $arrayTxtToPrint[$i];
	if($idTcSent!="")
		$idTcSent.=",".$com["IdTestoComunicazione"];
	else
		$idTcSent.=$com["IdTestoComunicazione"];	
		
	$pdf->startPageGroup();
	$pdf->AddPage();
	$pdf->Bookmark($com["NominativoDestinatario"], 0, 0, '', 'B', array(0,64,128));
	$txt = $com["TestoCorpo"];
	
	// faccio replace del percorso delle img http://apa.logicainformatica.it/sinistriTest lo trasformo in path 
	$txt = str_replace(PORTAL_URL,dirname(__FILE__)."/../", $txt);
//trace($txt,false);
	$pdf->writeHTML($txt, true, 0, true, 0);

//	$pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('stampa.pdf', 'I');
// contrassegno la  comunicazione come eseguita su scelta dell'operatore
$err ="";
if(($idTcSent!="") && ($done=="true")){
	sendComunicScad($idTcSent,"",$err);
}	

//============================================================+
// END OF FILE                                                
//============================================================+