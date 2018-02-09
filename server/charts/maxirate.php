<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");
$type = $_REQUEST['type'];
$mese = $_REQUEST['mese'];
$caption = $_REQUEST['caption'];
$categoria = $_REQUEST['data'];

$task = utf8_decode($_REQUEST['task']); // per trattare il simbolo °
$task = split(",",$task);
//$where = "FasciaRecupero IN ('".join("','",$task)."')";

if ($mese=="") // chiamata da FusionChart senza parametri
	die();

if ($categoria!=="" && $categoria!==null) {
  $where = " CategoriaMaxirata = '$categoria' AND ";	
} else {
	$where = "";
  }	

$sql = "SELECT IdCategoriaMaxirata, CategoriaMaxirata, TotaleImportoInsoluto, NumCategoriaMaxirata, Mese " .
		"FROM v_graph_maxirata v where $where Mese='$mese' order by IdCategoriaMaxirata";
$arrData = getFetchArray($sql);
//trace($sql);
if ($task=='store') {
	$data = json_encode_plus($arrData);  //encode the data in json format	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	echo $cb . '({"total":"' . count($arr) . '","results":' . $data . '})';
}

echo('{"results":' . json_encode_plus($arrData) . '}');
?>
