<?php 
// formAzioneMandato
// Genera documento word con Mandato per l'Agenzia per i contratti dati"
require_once("workflowFunc.php");

//writeHistory($azione["IdAzione"],"Stampa mandato all'Agenzia",$ids,"");
		
//echo "window.open('server/generaTestoLettera.php?TitoloModello=Mandato%20Agenzia&IdContratto=$ids','Mandato Agenzia','');";
echo "window.open('server/generaStampaComunicazioniSS.php?TitoloModello=Comunicazione%20Sal/Str&IdContratto=$ids','_parent','');";
?>