<?php 
// formAzioneInvioEmail
// Genera la struttura del form di tipo "azione invio email"
// Contenuto: campo/listbox email destinatario, listbox modello di email, campo oggetto email, campo note (testo email) e pulsanti Conferma / Annulla
//if(!preg_match( '/^[\w\.\-]+@\w+[\w\.\-]*?\.\w{1,4}$/', $indirizzoEmail))
//{
//	$indirizzoEmail = "sbagliato";
//}
require_once("workflowFunc.php");
//print_r ($codici);
$regexp = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";
if (count($codici)>1){
	for ($i=0; $i<count($idsArray); $i++)  
	{
		//$indirizzoEmail = trim(getScalar("SELECT Email FROM recapito r,contratto c WHERE r.IdCliente=c.IdCliente AND c.IdContratto=$idsArray[$i] AND idTipoRecapito=1"));
		$indirizzoEmail = trim(getScalar("SELECT Email FROM v_email e, contratto c WHERE e.idcliente=c.idcliente and c.IdContratto=$idsArray[$i]"));
		$arrayEmail=explode(';', $indirizzoEmail);
		$emailOK=FALSE;
		for ($y = 0; $y < count($arrayEmail); $y++)
		{
    		$indirizzoEmail = $arrayEmail[$y];
			if(filter_var($indirizzoEmail, FILTER_VALIDATE_EMAIL))
			{ 
				$emailOK=TRUE;
				break;
			}
		}
		if($emailOK)
		{
			$idEmail[]=$idsArray[$i];
		}else{
			$idScartati[]=$idsArray[$i];
		}
/*		
		if(!filter_var($indirizzoEmail, FILTER_VALIDATE_EMAIL))
		{
			$idScartati[]=$idsArray[$i];
		}else{
			$idEmail[]=$idsArray[$i];
		}
*/		
	}
	$idcontratti=json_encode_plus($idEmail);
	$codiciEmail=array();
	if (count($idEmail) > 0){
		$codiciEmail  = fetchValuesArray("SELECT CodContratto FROM contratto WHERE IdContratto IN (".join(",",$idEmail).")");
	}
	if (count($codiciEmail) > 1){
		if (count($codiciEmail)<=8){ 
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$codiciEmail);
		}else{
			$output = array_slice($codiciEmail, 0, 6);   
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$output)." e altre ".(count($codiciEmail)-6);
		}
	}else{
		$titolo = "&nbsp;Pratica n. ".join(", ",$codiciEmail);
	}
	
//	$codiciScartati = array();
//	if (count($idScartati) > 0){
//		$codiciScartati = fetchValuesArray("SELECT CodContratto FROM contratto WHERE IdContratto IN (".join(",",$idScartati).")");
//	}
//	if (count($codiciScartati) > 0)
//	{
//		$praticheEscluse = generaCombo("Pratiche Escluse","IdContratto","NomeCliente","FROM v_pratiche WHERE IdContratto IN (".join(",",$idScartati).")");	
//	} 
	
	if (count($codiciEmail) > 0)
	{
		include "formAzioneInvioEmailMultiplo.php"; 
	}else{	
		$msg = "Indirizzo e-mail non disponible per la selezione effettutata";
		include "formAzioneInvioMultiploNonSupportata.php";
	}
	
}else{
	include "formAzioneInvioEmailSingolo.php";
}

?>

