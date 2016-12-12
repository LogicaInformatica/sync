<?php 
	require_once("workflowFunc.php");
	//
//  Compone il testo di una lettera (chiamata con Ajax da pagina js)
//
try {
	$where = " WHERE ".(isset($_REQUEST['IdModello'])?"IdModello=".$_REQUEST['IdModello']:"TitoloModello='".$_REQUEST['TitoloModello']."'");
	$row = getRow("SELECT FileName, TitoloModello FROM modello $where");
	$modelText = file_get_contents(TEMPLATE_PATH.'/'.$row['FileName']);
	$p1 = strpos($modelText, '<w:body>')+8;
	$p2 = strpos($modelText, '<w:sectPr'); // a volte ha attributi, percio' non cerca <w:sectPr> con  ">" di chiusura
//	$p2 = strpos($modelText, '<w:sectPr>');
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
		$pratica = htmlentities_deep(getRow("SELECT * FROM v_comunicazione_saldostralcio WHERE IdContratto=".$contratto));
		/*$text = replaceModel($body,"RataLettera","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
		$text = replaceModel($text,"RataMandato","SELECT * FROM v_rate_insolute WHERE IdContratto=".$contratto);
		$text = replaceModel($text,"RecapitiMandato","SELECT * FROM v_recapiti_mandato WHERE IdContratto=".$contratto);*/
		//$text = replaceModel($body,"SubComSS","SELECT * FROM v_contratto_com_saldostralcio WHERE IdContratto=".$contratto);
		$textToPrint .= replaceVariables($body,$pratica,'_________');
		$ind--;
		if ($ind>0) {
			$textToPrint .= "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".
			                "<w:p><w:pPr></w:pPr></w:p><w:p><w:pPr></w:pPr></w:p>".$section;
		} else {
			$textToPrint .= $section;
		}
	}
	
	$textToPrint.=$footer;

	if (count($arrayContratti)>1)
		$suff = "_".date("Ymd_Hi");
	else
		$suff = $pratica["CodContratto"];
	
	//$suff = count($arrayContratti)==1?$pratica['dataPagamento']:date("Ymd_Hi");
	header("Content-type: application/vnd.ms-word");
	header("Content-Disposition: attachment; filename=\"".$row['TitoloModello']."_$suff.doc\"");
	die($textToPrint);
}	
catch (Exception $e)
{
	echo $e->getMessage();
}
?>