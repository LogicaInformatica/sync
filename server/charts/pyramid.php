<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");
$type = $_REQUEST['type'];
$gruppo= $_REQUEST['gruppo'];
if (isset($_REQUEST['mese'])) {
	$mese  = $_REQUEST['mese'];
	$anno  = substr($mese,0,4);
	$lastFYMonth = $anno*100+getSysParm("LAST_FY_MONTH","3");
	if ($mese>$lastFYMonth)
		$anno++;
	$where = " where Mese='$mese' and gruppo='$gruppo'"; 
	$whereTarget = " WHERE '$mese' BETWEEN DATE_FORMAT(dataini,'%Y%m') AND DATE_FORMAT(datafin,'%Y%m') AND $anno BETWEEN FY AND ENDFY  and gruppo='$gruppo'";
} else
	if (isset($_REQUEST['anno'])) {
		$anno = $_REQUEST['anno'];
		$lastFYMonth = $anno*100+getSysParm("LAST_FY_MONTH","3");
		$firstFYMonth = $lastFYMonth-99;
		$where = " where Mese between '$firstFYMonth' and '$lastFYMonth' and gruppo='$gruppo'"; 
		$whereTarget = " WHERE $anno BETWEEN FY AND ENDFY and gruppo='$gruppo'";
	} else
		$where = "";

//--------------------------------------------------------------
// Lettura dati per il grafico		
//--------------------------------------------------------------
if ($type=="stack")
{		
	
	$sql = "SELECT replace(FasciaRecupero,'&deg;','°') AS chartFasciaRecupero, replace(FasciaRecupero,'°','&deg;') AS FasciaRecupero, SUM(NumAffidati) Affidati 
			FROM v_graph_provvigione v $where group by FasciaRecupero";
	$arrData = getFetchArray($sql);
//trace($sql);	
	if ($type=='store') {
		die('{"results":' . json_encode_plus($arrData) . '}');
	}
	$cat = fetchValuesArray("SELECT DISTINCT replace(CONVERT(FasciaRecupero USING utf8),'°','&deg;') AS FasciaRecupero FROM target $whereTarget ORDER BY Ordine");
	$category = getFetchArray("SELECT DISTINCT replace(CONVERT(FasciaRecupero USING utf8),'&deg;','°') AS FasciaRecupero FROM target $whereTarget ORDER BY Ordine");
//	$cat = array('INS','PHONE LOAN','PHONE LEASING','PHONE REC','I ESA','II ESA','LEASING','III ESA','IV ESA','FLOTTE');
	$colori = array('aa88ff','3588aa','489999','66aa88','02b955','55ca00','a2ca00','ff4400','ffca00','cc0088','aa2266','bbaa99');
//	$col = array('INS'=>'aa88ff','PHONE'=>'3588aa','PHONE REC'=>'489999','I ESA'=>'66aa88','II ESA'=>'02b955',
//		'LEASING'=>'55ca00','III ESA'=>'a2ca00','IV ESA'=>'ff4400','FLOTTE'=>'ffca00');
	$ncat = count($cat);
	
	//Initialize <categories> element - necessary to generate a stacked chart
	$strCategories = "<categories>";
	for ($i=0; $i<$ncat; $i++) {
		$catname = str_replace('&deg;','°',$cat[$i]);
		$strCategories .= "<category name='$catname'/>";
		$dataset[$cat[$i]] = "<set />"; // inizializza il corrispondente dataset
		//trace("indice cat[$i]=".$cat[$i],FALSE);
	}
	$strCategories .= "</categories>";
	
	$totale = 0;
	$i = 0;
	foreach ($arrData as $row) {
		//trace("indice ".$row['FasciaRecupero'],FALSE);
		$dataset[$row['FasciaRecupero']] = "<set value='" . $row['Affidati'] . "' color='".$colori[($i++)%12]."' />";
		$totale += $row['Affidati'];
	}
	//trace(print_r($dataset,true),false);
	//Initiate <dataset> elements
	$strDataSetA = "<dataset seriesName=''>" . join('',$dataset) . "</dataset>";
	
	$strXML = "<chart caption='$caption' formatNumberScale='0' forceDecimals='0' decimalSeparator=',' thousandSeparator='.' yAxisValueDecimals='0'>";
	$strXML = $strXML . $strCategories . $strDataSetA  . "</chart>";
	
	
	//header ( 'Content-type: text/xml' );
	//echo pack ( "C3" , 0xef, 0xbb, 0xbf );
	//echo $totale . "\n" . $strXML; // restituisce anche il totale, su una riga davanti all'XML
	echo('{"categorie":'.json_encode_plus($category).',"results":' . json_encode_plus($arrData) . '}'); 
}
//-----------------------------------------
// Lettura dati per la tabella dei target
//-----------------------------------------
else
{	
	if (isset($_REQUEST['mese']))
	{
		$arr = getFetchArray("SELECT * from v_graph_target WHERE Mese=".$_REQUEST['mese']." and gruppo='$gruppo'");
//		trace("SELECT * from v_graph_target WHERE Mese=".$_REQUEST['mese'],FALSE);
	}
	else // dati annuali
		$arr = getFetchArray("SELECT * from v_graph_target_fy WHERE FY=$anno AND gruppo='$gruppo'");

	$data = json_encode_plus($arr);  //encode the data in json format
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	echo $cb . '({"total":"' . count($arr) . '","results":' . $data . '})';
}
?>