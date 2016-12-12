<?php
require_once(dirname(__FILE__)."/../common.php");
$ag = $_REQUEST['ag'];
$mese = $_REQUEST['mese'];
if ($mese=="") // chiamata da FusionChart senza parametri
	die();

$sql = "SELECT Agente, Agenzia, SUM(ImpPagato)/SUM(ImpCapitale)*100 IPR, SUM(Trattati)/SUM(NumInsoluti)*100 IPM FROM v_sintesi_agenzia v WHERE IdAgenzia=$ag and DATE_FORMAT(DataFineAffido,'%Y%m')='$mese' group by IdAgente order by 1";
$arrData = getFetchArray($sql);

//Initialize <chart> element
$strXML = "<chart subcaption='". $arrData[0]['Agenzia']. "' showValues='0' decimals='1' forceDecimals='1' decimalSeparator=',' numberSuffix='%' yAxisMinValue='0' yAxisMaxValue='100' numberSuffix='%' yAxisValueDecimals='0'>";

//Initialize <categories> element - necessary to generate a stacked chart
$strCategories = "<categories>";

//Initiate <dataset> elements
$strDataSetA = "<dataset seriesName='IPR (Recuperato/Affidato)' parentYAxis='P' color='FF44CC'>";
$strDataSetB = "<dataset seriesName='IPM (Movimentate/Affidate)' parentYAxis='P' color='88FF88'>";

//Iterate through the data 
foreach ($arrData as $row) {
	//Append <category name='...' /> to strCategories
	$strCategories .= "<category name='" . $row['Agente'] . "' />";
	 //Add <set value='...' /> to both the datasets
	$strDataSetA .= "<set value='" . $row['IPR'] . "' />";
	$strDataSetB .= "<set value='" . $row['IPM'] . "' />";
}

//Close <categories> element
$strCategories .= "</categories>";
//Close <dataset> elements
$strDataSetA .= "</dataset>";
$strDataSetB .= "</dataset>";

//Assemble the entire XML now
$strXML .= $strCategories . $strDataSetA . $strDataSetB . "</chart>";
/*
//$strXML .= "<trendLines><line startValue='80' endValue='85' color='009933' isTrendZone='1' displayvalue='Target' /></trendLines></chart>";
$strXML .= "<trendLines><line startValue='90' color='009933' dashed='1' dashLen='2' dashGap='2' toolText='Target IPM 90%' displayvalue='Target IPM' valueOnRight='1'/></trendLines>";
$strXML .= "<trendLines><line startValue='60' color='FF44CC' toolText='Target IPR 60%' displayvalue='Target IPR' valueOnRight='1'/></trendLines></chart>";
*/
echo $strXML;
?>
