<?php
require_once("common.php");
try {
	$modello = getRow("SELECT * FROM modello WHERE IdModello={$_REQUEST['IdModello']}");
	extract($modello);
	if ($modello) {
		header("Content-type: application/vnd.ms-word");
		header("Content-Disposition: attachment; filename=\"$FileName\"");
		readfile(TEMPLATE_PATH."/$FileName");
	} else {
		echo "File $FileName non trovato";
	}
} catch (Exception $e) {
	echo $e->getMessage();
}
?>