<?php
require_once("processImportedFiles.php");

$ids = getColumn("SELECT IdContratto FROM contratto WHERE DataCambioClasse='2016-09-19'");
echo "Ricalcolo emergenza per ".count($ids)." contratti<br>";
	flush();
trace("Ricalcolo emergenza per ".count($ids)." contratti",FALSE);

global $idImportLog;
global $listaClienti,$precrimine;

$cnt=0;
foreach ($ids as $IdContratto)
{
	if (2==processAndClassify($IdContratto)) // se errore grave, interrompe
		break;
	echo "<br>Elaborato contratto $IdContratto";
	flush();
}

trace("Fine elaborazione ricalcolo emergenza.php",FALSE);
die("<br>Fine elaborazione ricalcolo emergenza.php");
?>	