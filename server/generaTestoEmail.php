<?php 
//
//  Compone il testo di un Email (chiamata con Ajax da pagina js)
//
try
{
	require_once("workflowFunc.php");
	
	if ($_REQUEST['IdContratto']>0)
		$pratica = getRow("SELECT * FROM v_contratto_lettera WHERE IdContratto=". $_REQUEST['IdContratto']);
	else
		$pratica = array();
	
	$strFile = getScalar("SELECT FileName FROM modello WHERE IdModello=".$_REQUEST['IdModello']);
	$modelText = file_get_contents(TEMPLATE_PATH.'/'.$strFile);
	if (strpos($strFile,".json")!==FALSE) { // modello json
		$modelText = json_decode($modelText);
		$subject = $modelText->subject;
		$body    = $modelText->body;
	} else { // modello HTML
		$subject = substr($modelText,0,strpos($modelText,"\n"));
		$body = substr($modelText,1+strpos($modelText,"\n"));
	}
	$subject = replaceVariables($subject,$pratica,$_REQUEST['defaultSubst']);
	$body = replaceVariables($body,$pratica,$_REQUEST['defaultSubst']);
	trace("body=$body",false);
	echo json_encode(array("subject"=>$subject,"body"=>$body));
	//echo $subject."§".$body;
}
catch (Exception $e)
{
	echo $e->getMessage();
}
?>