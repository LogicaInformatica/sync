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
	$p3 = strpos($modelText, '</w:body>');
	$header  = substr($modelText,0,$p1);
	$body 	 = substr($modelText,$p1,$p3-$p1);
	$footer  = substr($modelText,$p3);
	
	$contrattiStr =  $_REQUEST['IdContratto'];
	$arrayContratti = explode(",",$contrattiStr );
	
	$textToPrint .= $header;
	
	$ind = count($arrayContratti);
	foreach($arrayContratti as $contratto) {
		$pratica = htmlentities_deep(getRow("SELECT * FROM v_dati_generali_writeoff WHERE IdContratto=".$contratto));
		$pratica["proponente"] = getScalar("SELECT Proponente FROM v_proponente_writeoff WHERE IdContratto=$contratto");
		// aggiunge il numero rate pagate (a parte perché ci impiega troppo per metterlo nella view)
		$pratica["ratePag"] = calcolaNumRatePagate($contratto);
		
		$textToPrint .= replaceVariables($body,$pratica,' ');
		$ind--;
		$textToPrint .= $section;
	}
	//trace(print_r($pratica,true),false);
	$textToPrint.=$footer;
	
	//$suff = count($arrayContratti)==1?$pratica['dataPagamento']:date("Ymd_Hi");
	if (count($arrayContratti)>1)
		$suff = "_".date("Ymd_Hi");
	else
		$suff = $pratica["CodContratto"];
	header("Content-type: application/vnd.ms-word");
	header("Content-Disposition: attachment; filename=\"".$row['TitoloModello']."_$suff.doc\"");
	die($textToPrint);
}	
catch (Exception $e)
{
	echo $e->getMessage();
}
?>