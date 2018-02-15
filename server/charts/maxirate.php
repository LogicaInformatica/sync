<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");

try {
	doMain();
}
catch (Exception $e)
{
	trace($e->getMessage());
}

function doMain()
{
	if (!isset($_REQUEST['task']))
      return;
		
	$type = $_REQUEST['type'];
	$mese = $_REQUEST['mese'];
	$caption = $_REQUEST['caption'];
	$categoria = $_REQUEST['data'];
	$task = $_REQUEST['task'];
    	
	if ($categoria!=="" && $categoria!==null) {
	  $where = " CategoriaMaxirata = '$categoria' AND ";	
	} else {
		$where = "";
	  }
	
	if ($mese=="") // chiamata da FusionChart senza parametri
	  die();			
		
	//--------------------------------------------------------------
	// Lettura dati per il grafico		
	//--------------------------------------------------------------
	if ($type=="stack")
	{
		$sql = "SELECT IdCategoriaMaxirata, CategoriaMaxirata, TotaleImportoInsoluto, NumCategoriaMaxirata, Mese " .
				"FROM v_graph_maxirata v where $where Mese='$mese' order by IdCategoriaMaxirata";
		$arrData = getFetchArray($sql);
		echo('{"results":' . json_encode_plus($arrData) . '}');
	} else {
		$sql = "SELECT IdCategoriaMaxirata, CategoriaMaxirata, TotaleImportoInsoluto, NumCategoriaMaxirata, Mese " .
				"FROM v_graph_maxirata v where $where Mese='$mese' order by IdCategoriaMaxirata";
		$arrData = getFetchArray($sql);
		$data = json_encode_plus($arrData);  //encode the data in json format	
		$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
		echo $cb . '({"total":"' . count($arrData) . '","results":' . $data . '})';
	}
}	
?>
