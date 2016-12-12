<?php
require_once("common.php");

//==============================================================
//  FUNZIONE PER L'AGGIORNAMENTO DELLA TABELLA situazione
//==============================================================

//-------------------------------------------------------------------------------------------------
// updateSituazioneDebitoria
// Determina se la data di riferimento passata (data di riferimento dell'acquisizione dati batch)
// è nel mese successivo alla data di consolidamento più recente. Se sì, consolida con data fine
// mese scorso i dati correnti (che hanno Consolidata='N', portando lo stato a 'Y' e la data al fine mese).
// Poi, in ogni caso, aggiorna le righe della situazione corrente
//-------------------------------------------------------------------------------------------------
function updateSituazioneDebitoria($dataRif)
{
	trace("Consolidamento situazione debitoria (scrittura su tabelle 'situazione')",false);

	// Determina l'ultimo consolidamento avvenuto
	$dataRif = ISODate($dataRif);
	$ultimo = getScalar("SELECT MAX(DataRiferimento) FROM situazione");
	if ($ultimo) {
		$meseOggi = 12*(int)substr($dataRif,0,4) + (int)substr($dataRif,5,2); // anno*12+mese
		$meseUltimo = 12*(int)substr($ultimo,0,4) + (int)substr($ultimo,5,2); 	
	
		beginTrans();
		if ($meseOggi>$meseUltimo) { // c'è stato un cambio di mese
			$dataRif = getScalar("SELECT LAST_DAY('$dataRif')+INTERVAL 1 DAY-INTERVAL 1 MONTH-INTERVAL 1 DAY");
			trace("Cambio di mese, consolida l'ultima situazione calcolata con data di riferimento = $dataRif",false);
			// Attenzione: non può essere eseguito due volte con la stessa data
			execute("UPDATE situazione SET DataRiferimento='$dataRif',Consolidata='Y' WHERE Consolidata='N'");
			trace("Consolidate ".getAffectedRows()." righe",false);
		}
	}	
	// Inserisce le righe con la situazione corrente
	execute("DELETE FROM situazione WHERE Consolidata='N'");
	execute("INSERT INTO situazione SELECT CURDATE(),'N',v.* FROM v_situazione_corrente v");
	trace("Inserite/aggiornate ".getAffectedRows()." righe. Fine consolidamento.",false);
	
	commit();
	return TRUE;
}
?>