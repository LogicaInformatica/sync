<?php
//----------------------------------------------------------------------------------------------------------------------
// pagina di comodo per richiamare a mano le funzioni batch
//----------------------------------------------------------------------------------------------------------------------
try 
{
	ob_implicit_flush(TRUE);
	if ($argc) // chiamato da riga comandi
		$postprocessing = ($argv[1]=="Y");
	else
		$postprocessing = ($_GET["postproc"]=="Y");
	require_once('processImportedFiles.php');
	processImportedFiles($postprocessing);
	die("processImportedFiles ended");
	
	/*require_once('mailsOverride.php');
	doOverride(1);
	die('processOverride ended ');*/
}
catch (Exception $e)
{
	die ($e->getMessage());
}
?>
