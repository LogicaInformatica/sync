<?php
require_once(dirname(__FILE__)."/../common.php");
$ag = $_REQUEST['ag'];
$mese = $_REQUEST['mese'];
if ($mese=="") // chiamata da FusionChart senza parametri
	die();

$sql = "SELECT Agente, Agenzia, SUM(NumLavorate) trattati, SUM(NumDaLavorare) da_trattare, SUM(ImpCapitale) capitale, SUM(ImpPagato) pagato FROM v_sintesi_agenzia v WHERE IdAgenzia=$ag and DATE_FORMAT(DataFineAffido,'%Y%m')='$mese' group by IdAgente order by 1";
$arrData = getFetchArray($sql);

//Initialize <chart> element
$strXML = "<chart subcaption='". $arrData[0]['Agenzia']. "' formatNumberScale='2' sformatNumberScale='0' forceDecimals='1' decimalSeparator=',' thousandSeparator='.' numberPrefix='%E2%82%AC' yAxisValueDecimals='0'>";

//Initialize <categories> element - necessary to generate a stacked chart
$strCategories = "<categories>";

//Initiate <dataset> elements
$strDataSetA = "<dataset seriesName='Importo pagato' parentYAxis='P'>";
$strDataSetB = "<dataset seriesName='Importo capitale' parentYAxis='P'>";
//$strDataSetC = "<dataset seriesName='Trattati' parentYAxis='S'>";
//$strDataSetD = "<dataset seriesName='Da trattare' parentYAxis='S'>";

//Iterate through the data 
foreach ($arrData as $row) {
	//Append <category name='...' /> to strCategories
	$strCategories .= "<category name='" . $row['Agente'] . "' />";
	 //Add <set value='...' /> to both the datasets
	$strDataSetA .= "<set value='" . $row['pagato'] . "' />";
	$strDataSetB .= "<set value='" . $row['capitale'] . "' />";
//	$strDataSetC .= "<set value='" . $row['trattati'] . "' />";
//	$strDataSetD .= "<set value='" . $row['da_trattare'] . "' />";
}

//Close <categories> element
$strCategories .= "</categories>";
//Close <dataset> elements
$strDataSetA .= "</dataset>";
$strDataSetB .= "</dataset>";
//$strDataSetC .= "</dataset>";
//$strDataSetD .= "</dataset>";

//Assemble the entire XML now
$strXML = $strXML . $strCategories . $strDataSetA . $strDataSetB . "</chart>";

echo $strXML;
?>
