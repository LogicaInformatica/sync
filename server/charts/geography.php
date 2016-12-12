<?php
require_once(dirname(__FILE__)."/../common.php");

doMain();

function doMain()
{
	$task = $_REQUEST['task']; // tipo tabella (GEO=esattoriale, GEO2=stragiudiziale)
	if ($task=="") // accade se chiamato da export
		return;
	if (isset($_REQUEST['mese'])) {
		$where = " where Mese='".$_REQUEST['mese']."'"; 
	} 
	else if (isset($_REQUEST['anno'])) {
		$anno = $_REQUEST['anno'];
		$lastFYMonth = $anno*100+getSysParm("LAST_FY_MONTH","3");
		$firstFYMonth = $lastFYMonth-99;
		$where = " where Mese between '$firstFYMonth' and '$lastFYMonth'"; 
	} 
	else
		$where = "";

	//-----------------------------------------
	// Lettura dati per la tabella dei target
	//-----------------------------------------
	if ($task=="GEO")
	{
		if (isset($_REQUEST['mese']))
			$arr = getFetchArray("SELECT * from v_geography_pivot WHERE Mese=".$_REQUEST['mese']);
		else // dati annuali
			$arr = getFetchArray("SELECT * from v_geography_pivot_fy WHERE Anno=$anno");
	}
	else
	{
		if (isset($_REQUEST['mese']))
			$arr = getFetchArray("SELECT * from v_geography_pivot_STR WHERE Mese=".$_REQUEST['mese']);
		else // dati annuali
			$arr = getFetchArray("SELECT * from v_geography_pivot_fy_STR WHERE Anno=$anno");
	}
		$data = json_encode_plus($arr);  //encode the data in json format
		$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
		echo $cb . '({"total":"' . count($arr) . '","results":' . $data . '})';
}
?>
