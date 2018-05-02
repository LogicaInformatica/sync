<?php
require_once('tcpdf.php');

//extend the main TCPDF class to create custom header and footer

/*class MYPDF extends TCPDF{
	
	//page header
	public function Header(){
		
		/*logo sx
		$img_sx = 'imgSX.png';
		$this->Image($img_sx, 10, 15, 15, '', 'PNG', '', false, 300, 'L', false, false, 0, false, false, false);
		
		//logo dx
		$img_dx = 'imgDX.png';
		$this->Image($img_dx, 10, 15, 15, '', 'PNG', '', false, 300, 'R', false, false, 0, false, false, false);
	}	
		
		
	public function Footer(){
			
		//logo footer
		//$img_footer = 'imgFooter.png';
		//$this->Image($img_footer);
		//position at 15mm from bottom
		$this->SetY(-15);
		$this->SetFont('helvetica', 'I', 6);
		// Page number
		$this->Cell(0, 0, 'Pagina '.$this->getGroupPageNo().'/'.$this->getPageGroupAlias(), 0, false, 'R', 0, '', 0, false, 'C', 'B');
			
	}
	
}*/

//create a new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

//set default monospaced font
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
		$fontname=$pdf->AddFont('ToyotaText', 'I',__DIR__."/fonts/toyotatext_it_4.php" );
		$pdf->SetFont("toyotatext", 'I', 10);
//$pdf->SetFont('helvetica', 'I', 12);
//$pdf->SetFont('', '', PDF_FONT_SIZE, '', 'false');

//add a page
$pdf->AddPage();

$txt = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="ISO-8859-1">
</head>
<body>
	<div style="background-color: #fff;margin:0 40px;">
	<div><img src="/images/imgHead.jpg" style="display:inline-block;"></div>
	<div align="right" style="margin-bottom: 90px;">
		Spettabile %RapprLegale%<br/>
		%Indirizzo%<br/>
		%Cap% %Localita% %SiglaProvincia%<br/>
	</div>
	<p>Roma, %Oggi%</p>
	<p></p>
	<p></p>
	<p style="font-weight:bold">OGGETTO: PREAVVISO COMUNICAZIONE DATI ALLA CENTRALE RISCHI</p>
	<p></p>
	<p>Gentile Cliente,</p><p></p>
	<p align="justify" style="margin-bottom:30px">
		In relazione al contratto di finanziamento n. %CodContrattoRidotto% da Lei stipulato con la Toyota Financial Services (UK) PLC, con la presente,
		 La informiamo che dopo aver effettuato una valutazione complessiva sulla Sua solvibilit&agrave; &egrave; stata stabilita la Sua oggettiva 
		 e non transitoria
		  difficolt&agrave; economico-finanziaria e che conseguentemente il nostro credito vantato nei Suoi confronti verr&agrave; messo in sofferenza. 
		  <br>
La invitiamo pertanto nuovamente a regolarizzare la Sua posizione debitoria entro e non oltre quindici giorni dalla ricezione della presente lettera 
di preavviso con una delle seguenti modalit&agrave;:
</p>
<p align="justify" style="margin-bottom:30px">
- Bonifico Bancario   presso la UNICREDIT BANCA (Cod.  IBAN IT77X0200805346000500045561) intestato alla Toyota Financial Services (UK) Plc con 
indicazione del numero di contratto (n. %CodContrattoRidotto%) nella causale;
</p>
<p align="justify" style="margin-bottom:30px">
- Pagamento a mezzo Carta di Credito tramite il portale della Toyota Financial Services https://www.tfsi.it/.  
		Dopo aver selezionato l"area "Accesso Clienti" -  dove non  &egrave;   necessario registrarsi - fare clic su "Paga rata scaduta con carta di credito" 
		e compilare i campi con i dati richiesti.	<
</p>
<p align="justify" style="margin-bottom:30px">
Ai sensi dell'articolo 4 del Codice Deontologico sulla Privacy La informiamo inoltre che, come previsto dal combinato disposto degli articoli 125 comma 3
 del Decreto Legislativo 1&deg; settembre 1993 e dalla Circolare di Banca di Italia n.139 del 11/2/1991 e s.m.i., in difetto di un Suo adempimento decorso
  invano il termine sopracitato, saremo tenuti a segnalare la Sua posizione alla Centrale Rischi, autorizzata al trattamento dei dati personali. 
</p>
<p align="justify" style="margin-bottom:30px">
Tale segnalazione potrebbe avere riflessi negativi in relazione al Suo accesso al credito. 
</p>
<p align="justify" style="margin-bottom:30px">
Qualora avesse gi&agrave; provveduto al relativo saldo e/o avesse attivato la procedura per l'estinzione anticipata, La preghiamo di fornircene comunicazione.
</p>
<p align="justify" style="margin-bottom:30px">
Per qualsiasi chiarimento potr&agrave; contattare l'agenzia %Agenzia% da noi incaricata all'indirizzo email %EmailAgenzia% oppure al numero %TelAgenzia% 
dal Luned&igrave; 
al Venerd&igrave; dalle ore 09:00  alle ore 18:00.	</p>
	
<p align="justify" style="margin-left:1cm;">Distinti Saluti,</p>
	
	<p align="right">Toyota Financial Services (UK) PLC</p>
	<div>
	</div>

</body>
</html>
EOT;

//print text
$pdf->writeHTML($txt,true, 0, true,0);

//close and output pdf document
ob_end_clean();
$pdf->Output('prova.pdf', 'I');

//============================================================+
// END OF FILE
//=

?>