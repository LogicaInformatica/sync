<?php 
// formAzioneStampaWO
// Genera documento word del Write Off Unico"
require_once("workflowFunc.php");

echo "window.open('server/generaStampaWO.php?TitoloModello=Stampa%20Write%20Off&IdContratto=$ids','_parent','');";
?>