<?php
require_once('commonbatch.php');
require_once('funzioniStorico.php');

set_time_limit(0); // aumenta il tempo max di cpu
svecchiamento($_GET['mesi']);
?>
