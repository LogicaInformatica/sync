<?php
require_once("engineFunc.php");

$ret = inAttesaDaPrima("2011-05-13","5,15,25",$dataInizio);

echo ($ret?"true":"false")." - $dataInizio";

?>	