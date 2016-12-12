<?php
// caricamento dei tipo partita da file sequenziale posto nella cartella /tmp
// Esempio di righe del file (tipo partita,codcontratto,numrata,numriga)
//
//'IM','LO188100',0,760
//'IM','LO188100',0,761
//'SR','LO188100',0,770
//
// NB: passare il filename con il parametro filename=  Es.:
//
//    https://cnctest.tfsi.it/server/batch/unaTantumTipoPartita.php?filename=TP_LOAN_LEASING.txt
//
require_once("commonbatch.php");

$file = dirname(__FILE__)."/../../tmp/".$_GET["filename"];
echo("<br>Inizio caricamento TipoPartita da file $file");
if (file_exists($file))
{
	$f = fopen($file,'r');
	$cnt = 0;
	while (!feof($f))
	{
		$riga = fgets($f);
		if (trim($riga)>"")
		{
			$campi = split(",",$riga);
			$tp  = $campi[0];
			$cod = $campi[1];
			$nra = $campi[2];
			$nri = $campi[3];
			$sql = "UPDATE movimento m JOIN contratto c ON m.idcontratto=c.idcontratto "
				  ." SET IdTipoPartita = (SELECT IdTipoPartita FROM tipopartita WHERE CodTipoPartitaLegacy=$tp)"
				  .",m.lastupd=m.lastupd"
				  ." WHERE CodContratto=$cod AND m.NumRata=$nra AND NumRiga=$nri";
			if (!execute($sql))
				die("<br>Errore $sql: ".getLastError());
			else
			{
				$cnt++;
				if ($cnt%10000==0)
					echo "<br>completati $cnt";
			}
		}
	}
}
else
	echo "<br>Il file $filename non esiste";
echo("<br>Fine caricamento TipoPartita da file");
?>	