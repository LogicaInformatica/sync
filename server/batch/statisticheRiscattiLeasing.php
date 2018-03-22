<?php
    require_once("../engineFunc.php");
    require_once("../userFunc.php");
	
//--------------------------------------------------------------------------------
// processStatisticheRiscattoLeasing
// legge tutte le pratiche in riscatto leasing assegnandole al mese corrente 
// o in alcuni casi nel mese precedente eliminando le vecchie pratiche inserite
// caricandole nella tabella statisticheriscattoleasing 
//--------------------------------------------------------------------------------
function processStatisticheRiscattiLeasing() {
		
	try {	
		$now = date();
		$dataAttuale = date('d');
			 
		if ($dataAttuale <=15) {
		  $numGiorni = cal_days_in_month(CAL_GREGORIAN, date('m')-1, date('Y'));	
		  $datamese = date("Y-m-d",mktime(0,0,0,date('m')-1,$numGiorni,date('Y')));
		  execute("DELETE FROM statisticheriscattileasing WHERE datamese='$datamese'");
		} 
		
		$sql = "SELECT IdContratto, IdCategoriaRiscattoLeasing, ".
			   " CASE ".
			   "    WHEN CURDATE() < (DataChiusura + INTERVAL 30 DAY) THEN '30' ".
			   "    WHEN CURDATE() BETWEEN (DataChiusura + INTERVAL 30 DAY) AND (DataChiusura + INTERVAL 60 DAY) THEN '60' ".
			   "    WHEN CURDATE() BETWEEN (DataChiusura + INTERVAL 60 DAY) AND (DataChiusura + INTERVAL 90 DAY) THEN '90' ".
			   "    WHEN CURDATE() > (DataChiusura + INTERVAL 90 DAY) THEN '90+' ".
			   " END as Lotto, ImpInsoluto FROM contratto WHERE IdCategoriaRiscattoLeasing IN (SELECT IdCategoriaRiscattoLeasing FROM categoriariscattoleasing)";
		$rows = getRows($sql);
		
	    foreach ($rows as $row) {
	    	
	    	$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$row['IdContratto'],"N");
			addInsClause($colList,$valList,"IdCategoriaRiscattoLeasing",$row['IdCategoriaRiscattoLeasing'],"N");
			addInsClause($colList,$valList,"Lotto",$row['Lotto'],"S");
			addInsClause($colList,$valList,"ImpInsoluto",$row['ImpInsoluto'],"N");
			if ($dataAttuale <=15) {
			  addInsClause($colList,$valList,"Datamese",$datamese,"S");	
			} else {
				addInsClause($colList,$valList,"Datamese","LAST_DAY(CURDATE())","G");
			}
			
			$sqlInsert = "INSERT INTO statisticheriscattileasing ($colList) VALUES ($valList)";		
			if (!execute($sqlInsert))
			{
				echo"Errore nell'insert";
			}
	    	
	    }
	}
    catch (Exception $e)
	{
		trace($e->getMessage());
		return FALSE;
	}  		
}	
?>