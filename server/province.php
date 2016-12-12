<?php
require_once("common.php");

$sql = 'SELECT SiglaProvincia FROM provincia ORDER BY 1';
echo "[['".implode("'],['", fetchValuesArray($sql))."']]";
?>
