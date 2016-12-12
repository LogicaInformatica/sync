<?php 
// formAzioneComSaldoStralcioDil
// Genera documento word della lettera di accettazione piano di rientro 
//e piano di rientro Saldo Stralciodilazionato
require_once("workflowFunc.php");

//writeHistory($azione["IdAzione"],"Stampa mandato all'Agenzia",$ids,"");
		
//echo "window.open('server/generaTestoLettera.php?TitoloModello=Mandato%20Agenzia&IdContratto=$ids','Mandato Agenzia','');";
echo "window.open('server/generaStampaComunicazioniSSDIL.php?TitoloModello=Comunicazione%20Piano%20di%20Rientro&IdContratto=$ids','_parent','');";
?>