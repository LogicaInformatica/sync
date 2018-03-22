<?php
    require_once("../engineFunc.php");
    require_once("../userFunc.php");
	
//--------------------------------------------------------------------------------
// processStatisticheMaxirate
// legge tutte le pratiche in maxirata assegnandole al mese corrente 
// o in alcuni casi nel mese precedente eliminando le vecchie pratiche inserite
// caricandole nella tabella statistichemaxirate 
//--------------------------------------------------------------------------------
function processStatisticheMaxirate() {
		
	try {	
		$now = date();
		$dataAttuale = date('d');
			 
		if ($dataAttuale <=15) {
		  $numGiorni = cal_days_in_month(CAL_GREGORIAN, date('m')-1, date('Y'));	
		  $datamese = date("Y-m-d",mktime(0,0,0,date('m')-1,$numGiorni,date('Y')));
		  execute("DELETE FROM statistichemaxirate WHERE datamese='$datamese'");
		} 
		
		$rows = getRows("SELECT IdContratto, IdCategoriaMaxirata, ImpInsoluto FROM contratto WHERE IdCategoriaMaxirata IN (SELECT IdCategoriaMaxirata FROM categoriamaxirata)");
	    foreach ($rows as $row) {
	    	
	    	$colList = ""; // inizializza lista colonne
			$valList = ""; // inizializza lista valori
			addInsClause($colList,$valList,"IdContratto",$row['IdContratto'],"N");
			addInsClause($colList,$valList,"IdCategoriaMaxirata",$row['IdCategoriaMaxirata'],"N");
			addInsClause($colList,$valList,"ImpInsoluto",$row['ImpInsoluto'],"N");
			if ($dataAttuale <=15) {
			  addInsClause($colList,$valList,"Datamese",$datamese,"S");	
			} else {
				addInsClause($colList,$valList,"Datamese","LAST_DAY(CURDATE())","G");
			}
					
			if (!execute("INSERT INTO statistichemaxirate ($colList) VALUES ($valList)"))
			{
				writeResult($idImportLog,"K",getLastError());
				return 2;
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