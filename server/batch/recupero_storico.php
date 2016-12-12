<?php
require_once('funzioniStorico.php');

set_time_limit(0); // aumenta il tempo max di cpu

recuperoStorico($_GET['cliente'],$_GET["contratti"],$_GET['file'],$_GET['dir']);

?>
