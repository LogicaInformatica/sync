<?php 
// formAzioneBase
// Genera la struttura del form di tipo "azione base"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla
require_once("workflowFunc.php");
//print_r ($codici);
// (preg_match( '/^[(3{1})|(393{1})]+([0-9]){8,10}$/i', $numTel))//
$regexp ="/^[(3{1})|(393{1})]+([0-9]){8,10}$/";
if (count($codici)>1){
	for ($i=0; $i<count($idsArray); $i++)  
	{
		$numTel = trim(getScalar("SELECT Cellulare FROM v_cellulare e, contratto c WHERE e.idcliente=c.idcliente and c.IdContratto=$idsArray[$i]"));
		$arrayTel=explode(',', $numTel);
		$telefonoOK=FALSE;
		
		for ($y = 0; $y < count($arrayTel); $y++)
		{
    		$numTel = $arrayTel[$y];
			$numTel = preg_replace( '/[^0-9]/i', '', $numTel);
			if (preg_match($regexp,$numTel))
			{
				$telefonoOK=TRUE;
				break;
			}
		}
		
		// inizio controllo numero sms inviati	
		$context = $_SESSION['userContext'];
		$sql = "select count(sr.IdContratto) from storiarecupero sr left join utente u on sr.IdUtente=u.IdUtente"
			 	." left join reparto re on u.IdReparto=re.IdReparto"	
				." left join azione az on az.IdAzione=sr.IdAzione"
				." where az.CodAzione='SMS'"
				." and re.IdReparto = (Select IdReparto from utente where IdUtente = ".$context['IdUtente'].")"
				." and IdContratto =$idsArray[$i]";
				
		$sql1= "select MaxSmsContratto from reparto where IdReparto = (Select IdReparto from utente where IdUtente = ".$context['IdUtente'].")";		
		
		$NonSuperatoNumSms = true;
		$NumSmsInviati = getscalar($sql);
		$MaxSms = getscalar($sql1);
		
		if(!($MaxSms==""))
		{
			if($NumSmsInviati >=$MaxSms)
			{
			 $NonSuperatoNumSms = false;	
			}
		}
		
		if($telefonoOK && $NonSuperatoNumSms)
		{
			$idSMS[]=$idsArray[$i];
		}else{
			$idScartati[]=$idsArray[$i];
		}
		
/*		
		$numTel = preg_replace('/[^0-9]/', '', $numTel);
		if (!preg_match($regexp,$numTel))
		{
			$idScartati[]=$idsArray[$i];
		}else{
			$idSMS[]=$idsArray[$i];
		}	
*/
	}
	$idcontratti=json_encode_plus($idSMS);
	$codiciSMS  = array();
	if (count($idSMS)>0){
		$codiciSMS  = fetchValuesArray("SELECT CodContratto FROM contratto WHERE IdContratto IN (".join(",",$idSMS).")");
	}	
	if (count($codiciSMS) > 1){
		if (count($codiciSMS)<=8){ 
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$codiciSMS);
		}else{
			$output = array_slice($codiciSMS, 0, 6);   
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$output)." e altre ".(count($codiciSMS)-6);
		}
	}else{
		$titolo = "&nbsp;Pratica n. ".join(", ",$codiciSMS);
	}
//	$codiciScartati = array();
//	if (count($idScartati)>0){	
//		$codiciScartati = fetchValuesArray("SELECT CodContratto FROM contratto WHERE IdContratto IN (".join(",",$idScartati).")");
//	}	
//	if (count($codiciScartati) > 0){
//		$praticheEscluse = generaCombo("Pratiche Escluse","IdContratto","NomeCliente","FROM v_pratiche WHERE IdContratto IN (".join(",",$idScartati).")");
//	}
		
	if (count($codiciSMS) > 0)
	{
		//trace("c ".print_r($codiciSMS,true));
		include "formAzioneInvioSMSMultiplo.php"; 
	}else{
		$msg = "Numero Cellulare non disponible oppure superato numero massimo di sms inviabili per la selezione effettutata";
		include "formAzioneInvioMultiploNonSupportata.php";
	}
		
}else{
	include "formAzioneInvioSMSSingolo.php";
}

?>