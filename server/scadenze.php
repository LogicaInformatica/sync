<?php
//----------------------------------------------------------------
// Restituisce la lista di scadenze da mostrare nella lista
// sotto al calendario
//----------------------------------------------------------------
require_once("userFunc.php");
//trace(var_dump($_REQUEST));
	if (isset($_REQUEST['data']))
		$data = $_REQUEST['data'];
	else
		$data = date('Y-m-d');
	
	$context = $_SESSION['userContext'];
	$idUtente = $context['IdUtente'];
	
	$dSelez 	= ISODate($data);
	$data       = italianDate($dSelez);
	echo "<span style=\"color:red; font-weight:bold;\">$data</span>";
	
	if ($context['InternoEsterno']=='E') // utente esterno (di agenzia)
	{  // non può vedere le scadenze di altri reparti
		$cond = " AND nota.IdUtente NOT IN (SELECT IdUtente FROM utente WHERE IdReparto!=0".$context["IdReparto"].")";
	}
	else
		$cond = "";
	
	$sql = "SELECT CASE WHEN DATE_FORMAT(nota.DataScadenza,'%H%i')='0000' THEN 1 ELSE 0 END AS priorita," .
	        " nota.*, cliente.idcliente as idcliente,cliente.nominativo as nominativo, contratto.codcontratto as numpratica " .
			"FROM nota left join contratto on nota.idcontratto=contratto.idcontratto left join cliente on contratto.idcliente=cliente.idcliente " .
			"WHERE cast(datascadenza as date)='$dSelez' AND ".userCondition().$cond.
	        " ORDER BY Priorita,DataScadenza";
		
	//trace($sql);
	$arr = getFetchArray($sql);
	if (count($arr)==0) {
   		echo ("<li style=\"list-style-type:none\"><font color=\"gray\"><i>Nessuna scadenza</i></font></li>");
	} else {
   		foreach($arr as $element) {
			$idContratto=$element['IdContratto'];
   			$numpratica=$element['numpratica'];
   			$tipoNota=$element['TipoNota'];
   			$idNota=$element['IdNota'];
   			$idcliente=$element['idcliente'];
   			$nominativo=$element['nominativo'];
			
   			if (($idContratto==null)) 
   				$idContratto=0;
   			if (($idNota==null)) 
   				$idNota=0;
   			if (($idcliente==null)) 
   				$idcliente=0;
   			// Prepara il prefisso indicante l'ora, se specificata
   			$ora = date('H:i',dateFromString($element["DataScadenza"]));
   			if ($ora!="00:00")
   				$prefix = "<b>(ore $ora)</b> ";
   			else
   				$prefix = "";
//   			trace($idContratto.",'".$numpratica."','".$tipoNota."',".$idNota.",".$idcliente.",'".$nominativo."'");
			echo "<li><a  style=\"text-decoration:none\" href=\"javascript:DCS.FormNota.showDetailNote(".$idContratto.",'".$numpratica."','".$tipoNota."',".$idNota.",".$idcliente.",'".str_replace("'","\'",$nominativo)."');\">"
				.$prefix.$element['TestoNota'].'</a></li>';
		}
 	} 	
?>