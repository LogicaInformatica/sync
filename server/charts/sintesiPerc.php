<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");
$type = $_REQUEST['type'];
$mese = $_REQUEST['mese'];
$caption = $_REQUEST['caption'];

$task = utf8_decode($_REQUEST['task']); // per trattare il simbolo °
$task = split(",",$task);
$where = "FasciaRecupero IN ('".join("','",$task)."')";

if ($mese=="") // chiamata da FusionChart senza parametri
	die();

$sql = "SELECT Agenzia, IdReparto, IPR, IPM " .
		"FROM v_graph_provvigione v where $where and Mese='$mese' order by Agenzia";
$arrData = getFetchArray($sql);
//trace($sql);
if ($type=='store') {
	die('{"results":' . json_encode_plus($arrData) . '}');
}

//Initialize <categories> element - necessary to generate a stacked chart
$strCategories = "<categories>";

//Initiate <dataset> elements
$strDataSetA = "<dataset seriesName='IPR (Recuperato/Affidato)' color='FF44CC'>";
$strDataSetB = "<dataset seriesName='IPM (Movimentate/Affidate)' color='88FF88'>";

foreach ($arrData as $row) {
	//Append <category name='...' /> to strCategories
	$strCategories .= "<category name='" . $row['Agenzia'] . "' />";
	//Add <set value='...' /> to both the datasets
/*
  	$strDataSetA .= "<set value='" . $row['IPR'] . "' link='newchart-xmlurl-server/charts/sintesiDettPerc.php?mese=$mese&ag=" . $row['IdAgenzia'] . "' />";
	$strDataSetB .= "<set value='" . $row['IPM'] . "' link='newchart-xmlurl-server/charts/sintesiDettPerc.php?mese=$mese&ag=" . $row['IdAgenzia'] . "' />";
*/
	$strDataSetA .= "<set value='" . $row['IPR'] . "' />";
	$strDataSetB .= "<set value='" . $row['IPM'] . "' />";
}
//Close <categories> element
$strCategories .= "</categories>";
//Close <dataset> elements
$strDataSetA .= "</dataset>";
$strDataSetB .= "</dataset>";
$strXML = "<chart caption='$caption' subCaption=\"(Valori calcolati sulla media ponderata dei lotti scaduti nel mese di riferimento)\"  yAxisMinValue='0' yAxisMaxValue='100' decimals='1' forceDecimals='1' decimalSeparator=',' numberSuffix='%' yAxisValueDecimals='0'>";
$strXML .= "<styles><definition><style name='trend' type='font' bold='1' /></definition>";
$strXML .= "<application><apply toObject='TrendValues' styles='trend' /></application></styles>";
$strXML .= $strCategories . $strDataSetA . $strDataSetB;

/* Lettura valore target */
$fy = substr($mese,0,4);
if ($mese%100 > getSysParm("LAST_FY_MONTH","3")) {
	$fy++;
}
// una o piu' righe di target (se e' un periodo con target misti dovuti ad un cambio in corso)
$v = fetchValuesArray("SELECT DISTINCT Valore FROM target t where $fy BETWEEN FY AND ENDFY "
     ." AND '$mese' BETWEEN DATE_FORMAT(dataini,'%Y%m') AND DATE_FORMAT(datafin,'%Y%m') and valore>0 and $where");
if (count($v)>0) 
{
	$strXML .= "<trendLines>";
	foreach ($v as $valore)
		$strXML .= "<line startValue='$valore' color='009933' thickness='3' toolText='Target $valore%' displayvalue='Target $valore%' valueOnRight='1'/>";
		
	$strXML .= 	"</trendLines>";
}

//header ( 'Content-type: text/xml' );
//echo pack ( "C3" , 0xef, 0xbb, 0xbf );
echo "$strXML</chart>";
?>
