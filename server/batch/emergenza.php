<?php
require_once("processImportedFiles.php");

$ids = getColumn("SELECT IdContratto FROM contratto WHERE idcontratto in (﻿5476,
5850,25878,25944,37375,44924,50169,79346,80501,104761,106686,136001,141231,152776,183221,192121,192851,193756,201807,
208847,264052,266597,295242,386902,399832)");
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