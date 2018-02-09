<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");

$type = $_REQUEST['type'];
$anno = $_REQUEST['anno'];
$idGrafico = $_REQUEST['id'];
$categoria = $_REQUEST['data'];

if (isset($_REQUEST['task']))
{
	$task = utf8_decode($_REQUEST['task']); // per trattare il simbolo °
	$task = split(",",$task);
} 

if ($anno=="") // chiamata da FusionChart senza parametri
die();

if ($categoria!=="") {
  $where = " CategoriaMaxirata = '$categoria' AND ";	
} else {
	$where = "";
  }	

//$lastFYMonth = $anno*100+getSysParm("LAST_FY_MONTH","3"); // ad es. 201203
//$firstFYMonth = $lastFYMonth-99;		// ad es. 201104

$lastFYMonth = $anno.'12'; // ad es. 201203
$firstFYMonth = $anno.'01';		// ad es. 201104

$sql = "SELECT IdCategoriaMaxirata, CategoriaMaxirata, TotaleImportoInsoluto, NumCategoriaMaxirata, Mese " .
		"FROM v_graph_maxirata v where $where Mese between '$firstFYMonth' and '$lastFYMonth' order by 3,1";
//trace($sql,FALSE);
$arrData = getFetchArray($sql);

if ($type=='store') {
	die('{"results":' . json_encode_plus($arrData) . '}');
}

echo('{"results":' . json_encode_plus($arrData) . '}');
?>
