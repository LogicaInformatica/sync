<?php
require_once("userFunc.php");
require_once("workflowFunc.php");
$task = ($_POST['task']) ? ($_POST['task']) : null;

switch($task){
	case "annullaSpeciale":		
		annullaAzioneSpeciale();
		break;
	case "annullaStoricoAllegatoSpeciale":
		annullaStoricoAllegatoSpeciale();
		break;	
	case "insertSpeciale":
		insertAllegatoSpeciale();
		break;
	case "flagDeleteSpeciale":
		flagDeleteSpeciale();
		break;
	case "annullaFlagCancella":
	    annullaFlagCancella();
	    break;			
	case "deleteSpeciale":
	    deleteAllegato($_POST['IdAllegato']);
	    break;	
	case "allega": 
		allega();
		break;
	default:
		echo "{failure:true}";
		break;
}

//-----------------------------------------------------------------------
// insertAllegatoSpeciale
// Inserisce un determinato allegato, la chiave viene ricevuta in post
//-----------------------------------------------------------------------
function insertAllegatoSpeciale()
{
	try
	{
		$idAll = $_POST['IdAllegato'];
		$idAziSpec = $_POST['IdAzioneSpeciale'];
		$IdAllegato = 'IdAllegato';
		$IdAzioneSpeciale = 'IdAzioneSpeciale';
		$sql =  "insert into allegatoazionespeciale ($IdAllegato,$IdAzioneSpeciale) VALUES($idAll,$idAziSpec)";
		beginTrans();
		if (execute($sql)) 
		{
			commit();
			echo "{'success':true}";
		} 
		else { 
			echo "{success:false, error:\"".getLastError()."\"}";
			rollback();
		}
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// deleteAllegato
// Elimina un determinato allegato, la chiave viene ricevuta in post
//-----------------------------------------------------------------------
function flagDeleteSpeciale()
{
	try
	{
		$sql =  "update allegatoazionespeciale set FlagCancella='Y' where IdAllegato =".$_POST['IdAllegato'];
		beginTrans();
		if(execute($sql)) 
		{
		  commit();
		  echo "{'success':true}";
		} 
		else { 
		  echo "{success:false, error:\"".getLastError()."\"}";
		  rollback();
		}	
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// deleteAllegato
// Elimina un determinato allegato, la chiave viene ricevuta in post
//-----------------------------------------------------------------------
function annullaFlagCancella()
{
	try
	{
		$sql =  "update allegatoazionespeciale set FlagCancella='N' where IdAzioneSpeciale=".$_POST['IdAzioneSpeciale']." AND FlagCancella ='Y'";
		beginTrans();
		if(execute($sql)) 
		{
		  commit();
		  echo "{'success':true}";
		} 
		else { 
		  echo "{success:false, error:\"".getLastError()."\"}";
		  rollback();
		}	
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// annullaAzioneSpeciale
// A causa della rinuncia a registrare un'azione speciale, elimina gli
// allegati che erano stati creati
//-----------------------------------------------------------------------
function annullaAzioneSpeciale()
{
	try
	{
		$IdContratto = $_POST["IdContratto"];
		$ids = json_decode($_POST["allegatiInseriti"],true); // id degli allegati inseriti in questa sessione
		
		beginTrans();
		foreach ($ids as $idallegato) {
			if (!deleteAllegato($idallegato)) {
				rollback();
				echo '{success:false, error:"'.getLastError().'"}';
				return false;
			}
		}
		commit();
		echo '{"success":true}';
		return true;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		echo '{success:false, error:"'.getLastError().'"}';
		return false;
	}
}

//-----------------------------------------------------------------------
// deleteStoricoAllegatoSpeciale
// Elimina dallo storico recupero un determinato allegato, la chiave viene ricevuta in post
//-----------------------------------------------------------------------
function annullaStoricoAllegatoSpeciale()
{
	try
	{
		$sqlstoriarecuperoallegato =  "delete from storiarecupero where IdStoriaRecupero =".$_POST['IdStoriaRecupero'];
		$codMex='ANN_ALLEG_STOR';
		beginTrans();
		if(execute($sqlstoriarecuperoallegato)) {
			writeLog("APP","Gestione allegato storico","(Storico Id:".$_POST['IdStoriaRecupero'].")Annullamento effettuato con successo.",$codMex);
		  commit();
		  echo "{'success':true}";
		} else {
			writeLog("APP","Gestione allegato storico","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
			rollback();
		}
		
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		setLastSerror($e->getMessage());
		writeLog("APP","Gestione allegato storico","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// allega
// Allega un documento (non crea ancora il link con l'azione speciale
//-----------------------------------------------------------------------
function allega() {
	$IdAllegato =  allegaDocumento($_REQUEST["IdContratto"],$_REQUEST["IdTipoAllegato"],$_REQUEST["titolo"],'N');
	if ($IdAllegato) {
		echo '{"success":true, "idAllegato":'.$IdAllegato.'}';
	} else {
		echo '{"success":false, "msg":"'.getLastError().'"}';
	}
}
?>


