<?php 
	require_once("workflowFunc.php");
	//
//  Compone il testo di una lettera (chiamata con Ajax da pagina js)
//
try {
	$arrayRate = json_decode(stripslashes($_REQUEST["arrayRate"]));
	$contratto = $_REQUEST["idContratto"];
	$pratica = htmlentities_deep(getRow("SELECT * FROM v_comunicazione_pianodrateazione WHERE IdContratto=".$contratto));
	
	$modelText = file_get_contents(TEMPLATE_PATH.'/modello_bollettino_RAV_td247.xml');
	$p1 = strpos($modelText, '<w:body>')+8;
	$p2 = strpos($modelText, '<w:sectPr'); // a volte ha attributi, percio' non cerca <w:sectPr> con  ">" di chiusura
//	$p2 = strpos($modelText, '<w:sectPr>');
	$p3 = strpos($modelText, '</w:body>');
	$header  = substr($modelText,0,$p1);
	$body 	 = substr($modelText,$p1,$p2-$p1);
	$section = substr($modelText,$p2,$p3-$p2);
	$footer  = substr($modelText,$p3);
	$textToPrint ='';
	
	
	//$textToPrint .= $header;
	$ind = count($arrayRate);
	foreach($arrayRate as $rata) {
		
		$pratica["DescrizioneRata"] = "Rata n. ".$rata[0]; 
		$pratica["Scadenza"]		= $rata[2];
		$pratica["Importo"]			= $rata[3];
		$pratica["CodiceRav"]	 	= "1234567890";
		//$textToPrint = $modelText;
		$textToPrint .= replaceVariables($modelText,$pratica,'');
		break;
//		$ind--;
//		if ($ind>0) {
//			$textToPrint .= "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
//			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
//			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
//			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".$section;
//		} else {
//			$textToPrint .= $section;
//		}
	}
	//$textToPrint.=$footer;
	
	$suff = 'stampa_bollettini_'.date("Ymd_Hi");
	header("Content-type: application/vnd.ms-word");
	header("Content-Disposition: attachment; filename=\""."$suff.doc\"");
	die($textToPrint);
}	
catch (Exception $e)
{
	echo $e->getMessage();
}
?>