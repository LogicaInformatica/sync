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
	$lotto = $_REQUEST['lotto'];
	$task = $_REQUEST['task'];
	$where="";
    	
	if ($categoria!="" && $categoria!=null) {
	  $where .= " CategoriaRiscattoLeasing = '$categoria' AND ";	
	}
	
	if ($lotto!="" && $lotto!=null) {
	  $where .= " Lotto = '$lotto' AND ";	
	}
	
	if ($mese=="") // chiamata da FusionChart senza parametri
	  die();			
		
	//--------------------------------------------------------------
	// Lettura dati per il grafico		
	//--------------------------------------------------------------
	if ($type=="stack")
	{
		$sql = "SELECT IdCategoriaRiscattoLeasing, CategoriaRiscattoLeasing, TotaleImportoInsoluto, NumCategoriaRiscattoLeasing, Lotto, Mese " .
				"FROM v_graph_riscattoleasing v where $where Mese='$mese' order by IdCategoriaRiscattoLeasing";
		//trace($sql,FALSE);
		$arrData = getFetchArray($sql);
		echo('{"results":' . json_encode_plus($arrData) . '}');
	} else {
		$sql = "SELECT IdCategoriaRiscattoLeasing, CategoriaRiscattoLeasing, TotaleImportoInsoluto, NumCategoriaRiscattoLeasing, Lotto, ".
			   "CASE WHEN Lotto = '30' THEN 'Riscatti scaduti 0-30' ".
			   "WHEN Lotto = '60' THEN 'Riscatti scaduti 30-60' ".
			   "WHEN Lotto = '90' THEN 'Riscatti scaduti 60-90' ".
			   "WHEN Lotto = '90+' THEN 'Riscatti scaduti oltre 90' ".
			   "END as descrizioneLotto, ". 
			   "Mese FROM v_graph_riscattoleasing v where $where Mese='$mese' order by IdCategoriaRiscattoLeasing";
		//trace($sql,FALSE);
		$arrData = getFetchArray($sql);
		$data = json_encode_plus($arrData);  //encode the data in json format	
		$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
		echo $cb . '({"total":"' . count($arrData) . '","results":' . $data . '})';
	}
}	
?>
