<?php 
//
//  Compone il testo di un SMS (chiamata con Ajax da pagina js)
//
try
{
	require_once("workflowFunc.php");
	$strFileSMS = getScalar("SELECT FileName FROM modello WHERE IdModello=".$_REQUEST['IdModello']);
	$modelText = json_decode(file_get_contents(TEMPLATE_PATH.'/'.$strFileSMS));
	if ($_REQUEST['IdContratto']>0)
		$pratica = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=". $_REQUEST['IdContratto']);
	else
		$pratica = array();
	$subject = replaceVariables($modelText->testoSMS,$pratica,$_REQUEST['defaultSubst']);
	echo $subject;
}
catch (Exception $e)
{
	echo $e->getMessage();
}
?>