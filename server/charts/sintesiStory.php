<?php
// ATTENZIONE: FILE DA SALVARE IN UTF8 SENZA BOM
require_once(dirname(__FILE__)."/../common.php");

$type = $_REQUEST['type'];
$anno = $_REQUEST['anno'];
$idGrafico = $_REQUEST['id'];
$dataPerc = $_REQUEST['data'];

if (isset($_REQUEST['task']))
{
	$task = utf8_decode($_REQUEST['task']); // per trattare il simbolo °
	$task = split(",",$task);
	$where = "FasciaRecupero IN ('" . join("','",$task) ."') and ";
	$subGraph = 'sintesiPerc';
} else {
	$where = "";
	$subGraph = 'pyramid';
}
if ($anno=="") // chiamata da FusionChart senza parametri
die();

$lastFYMonth = $anno*100+getSysParm("LAST_FY_MONTH","3"); // ad es. 201203
$firstFYMonth = $lastFYMonth-99;		// ad es. 201104

$sql = "SELECT Agenzia, IdReparto, Mese, IPR, IPM " .
		"FROM v_graph_provvigione v where $where Mese between '$firstFYMonth' and '$lastFYMonth' order by 3,1";
trace($sql,FALSE);
$arrData = getFetchArray($sql);

if ($type=='store') {
	die('{"results":' . json_encode_plus($arrData) . '}');
}

//Initialize <categories> element - necessary to generate a stacked chart
$strCategories = "<categories>";
$annoLab = $firstFYMonth / 100;
$meseLab = $firstFYMonth % 100;
for ($i=0; $i<12; $i++) {
	$strCategories .= sprintf("<category name='%02d/%4d' />",$meseLab,$annoLab);
	if ($meseLab==12) {
		$meseLab = 1;
		$annoLab++;
	} else {
		$meseLab++;
	}
}
$strCategories .= "</categories>";

$strDataSet = array();
$cat = array();
//Initiate <dataset> elements
//$strDataSetA = "<dataset seriesName='IPR (Recuperato/Affidato)' color='FF44CC'>";
//$strDataSetB = "<dataset seriesName='IPM (Movimentato/Affidate)' color='88FF88'>";

foreach ($arrData as $row) {
	$agenzia = $row['Agenzia'];
	if (!array_key_exists($agenzia, $strDataSet)) {
		$strDataSet[$agenzia] = "<dataset seriesName='$agenzia'>";
		for ($i=0; $i<12; $i++)
		$sets[$agenzia][$i] = '<set />';
	}
	//Add <set value='...' /> to both the datasets
	$ind = $row['Mese']-$firstFYMonth;
	if ($ind>11) {
		$ind -= 88;  // ad es. l'indice di 201201 rispetto a 201104 e' (201201-201104)-88
		// perche' 89 e' la differenza tra il dic. di un anno e il genn dell'anno successivo
	}
	$mese = $row['Mese'];
	//	$sets[$agenzia][$ind] = "<set value='" . $row[$dataPerc] . "' link='newchart-xmlurl-server/charts/$subGraph.php?type=stack&mese=$mese&caption=".strftime("%B %Y", mktime(0,0,0,$mese%100,1,$mese/100))."&task=".$_REQUEST['task']."' />";
	$sets[$agenzia][$ind] = "<set value='" . $row[$dataPerc] . "' link='javascript:DCS.Charts.presentaMese(\"$idGrafico\",$mese);' />";
}
//Close <categories> element

$strXML = "<chart caption='$caption' yAxisMinValue='0' yAxisMaxValue='100' decimals='1' forceDecimals='1' decimalSeparator=',' numberSuffix='%' yAxisValueDecimals='0'>";
$strXML .= "<styles><definition><style name='trend' type='font' bold='1' /></definition>";
$strXML .= "<application><apply toObject='TrendValues' styles='trend' /></application></styles>";
$strXML .= $strCategories;

foreach ($strDataSet as $i => $value) {
	$strXML .= $value;
	for ($k=0; $k<12; $k++) {
		$strXML .= $sets[$i][$k];
	}
	$strXML .= '</dataset>';
}

// una o piu' righe di target (se e' un periodo con target misti dovuti ad un cambio in corso)
if (isset($_REQUEST['task']))
{	
	$v = fetchValuesArray("SELECT DISTINCT Valore FROM target t where ".str_replace("&deg;","°",$where)." $anno BETWEEN FY AND ENDFY ");
	if (count($v)>0) 
	{
		$strXML .= "<trendLines>";
		foreach ($v as $valore)
			$strXML .= "<line startValue='$valore' color='009933' thickness='3' toolText='Target $valore%' displayvalue='Target $valore%' valueOnRight='1'/>";
			
		$strXML .= 	"</trendLines>";
	}
}
//header ( 'Content-type: text/xml' );
//echo pack ( "C3" , 0xef, 0xbb, 0xbf );
echo "$strXML</chart>";
?>
