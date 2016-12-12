<?php
//
// Selezione del form di gestione di una particolare azione
// (richiamato dalla eseguiAzione in workflow.js)
//
	require_once("userFunc.php"); 

	try 
	{
		//trace("in genfazione ".print_r($_REQUEST,true));
		// Interpreta i parametri ricevuti
		$idstatoazione    = $_REQUEST['idstatoazione'];
		if (!is_numeric($idstatoazione)) {
			$idstatoazione = getScalar("SELECT IdStatoAzione FROM statoazione "
					. "WHERE IdAzione IN (SELECT IdAzione FROM azione WHERE CodAzione='$idstatoazione')");
		}
		$isStorico   = $_REQUEST['isStorico']=='Y';
		$idcontratti = $_REQUEST['idcontratti']; // array id contratti in formato JSON con slashes (utile per ripassarlo)
		// Compone la lista dei codici contratto
		$idGrid = $_REQUEST['idGrid'];
		$idsArray    = json_decode(unquote_smart($idcontratti));
		$ids         = join(",",$idsArray); // lista per la clausola IN
		$schema = MYSQL_SCHEMA.($isStorico?'_storico':'');
		$codici      = fetchValuesArray("SELECT CodContratto FROM $schema.contratto WHERE IdContratto IN ($ids)");
		
		// aggiunto da ALDO un array contenente dati extra da aggiungere alle richieste 
		//trace("Dati Extra ricevuti da generaFormAzione: {$_REQUEST['ArrayDati']}",false);
		
		$arrDatiExtra = json_decode(unquote_smart($_REQUEST['ArrayDati']));
		
		// Compone il sottotitolo
		if (count($codici)>1){
			if (count($codici)<=8){ 
				$titolo = "&nbsp;Pratiche nn. ".join(", ",$codici);
			}else{
				$output = array_slice($codici, 0, 6);   
				$titolo = "&nbsp;Pratiche nn. ".join(", ",$output)." e altre ".(count($codici)-6);
			}
		}else{
			$row = getRow("SELECT CodContratto,NomeCliente,IdCliente FROM $schema.v_pratiche WHERE IdContratto=$ids");
			$titolo = "&nbsp;Pratica n. ".$row["CodContratto"]." - ".str_replace('"','&quot;',$row["NomeCliente"]);
		}
		// Legge il tipo di form da utilizzare per l'azione data
		$azione    = getRow("SELECT a.*,IdStatoAzione FROM azione a,statoazione sa WHERE IdStatoAzione=$idstatoazione AND a.IdAzione=sa.IdAzione");

		if (!$azione)  // niente form specificato
		{
			include "formAzioneNonSupportata.php"; 
		}
		else // include (e quindi esegue) il generatore del form specifico
		{
			//trace("Richiamo form 'formAzione".$azione["TipoFormAzione"].".php'",FALSE);
			include 'formAzione'.$azione["TipoFormAzione"].'.php';
		}
	}
	catch (Exception $e)
	{
		echo "{xtype:'label',text:'".$e->message."'}"; // restituisce una stringa per l'eval
		trace($e->message);
	}
?>