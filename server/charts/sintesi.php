<?php
require_once(dirname(__FILE__)."/../common.php");

$type = $_REQUEST['type'];
$mese = $_REQUEST['mese'];
$task = utf8_decode($_REQUEST['task']); // per trattare il simbolo �
$task = split(",",$task);
$where = "FasciaRecupero IN ('".join("','",$task)."')";

if ($mese=="") // chiamata da FusionChart senza parametri
	die();

$sql = "SELECT * " .
		"FROM v_graph_provvigione v where $where and Mese='$mese' order by Agenzia";
$arrData = getFetchArray($sql);

if ($type=='store') {
	die('{"results":' . json_encode_plus($arrData) . '}');
}

//Initialize <categories> element - necessary to generate a stacked chart
$strCategories = "<categories>";

//Initiate <dataset> elements
$strDataSetA = "<dataset seriesName='Capitale recuperato'>";
$strDataSetB = "<dataset seriesName='Capitale affidato'>";

foreach ($arrData as $row) {
	//Append <category name='...' /> to strCategories
	$strCategories .= "<category name='" . $row['Agenzia'] . " (".$row['NumIncassati']."/".$row['NumAffidati'].")' />";
	//Add <set value='...' /> to both the datasets
/*
	$strDataSetA .= "<set value='" . $row['ImpCapitaleIncassato'] . "' link='newchart-xmlurl-server/charts/sintesiDett.php?mese=$mese&ag=" . $row['IdReparto'] . "' />";
	$strDataSetB .= "<set value='" . $row['ImpCapitaleAffidato'] . "' link='newchart-xmlurl-server/charts/sintesiDett.php?mese=$mese&ag=" . $row['IdReparto'] . "' />";
*/
	$strDataSetA .= "<set displayValue='".$row['LabelIncassato']."' value='" . $row['ImpCapitaleIncassato']."'/>";
	$strDataSetB .= "<set displayValue='".$row['LabelAffidato']."' value='" . $row['ImpCapitaleAffidato'] .  "' />";
}
//Close <categories> element
$strCategories .= "</categories>";
//Close <dataset> elements
$strDataSetA .= "</dataset>";
$strDataSetB .= "</dataset>";
$strXML = "<chart caption='$caption' subCaption=' ' formatNumberScale='2' decimalPrecision='2' forceDecimals='1' decimalSeparator=',' thousandSeparator='.' numberPrefix='%E2%82%AC' yAxisValueDecimals='0'>";
$strXML = $strXML . $strCategories . $strDataSetA . $strDataSetB . "</chart>";


//header ( 'Content-type: text/xml' );
//echo pack ( "C3" , 0xef, 0xbb, 0xbf );
//echo $strXML;
echo('{"results":' . json_encode_plus($arrData) . '}');
?>
