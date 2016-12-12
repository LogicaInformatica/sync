<?php
require_once("workflowFunc.php");

$task = ($_POST['task']) ? ($_POST['task']) : null;

//**********usati per il log**********************************************************************
$par[] = getscalar("SELECT NomeCliente from v_pratiche where IdCliente=".$_POST['IdCliente']);
$par[]= 'Mod. dettaglio pratiche';
//************************************************************************************************

switch($task){
	case "save":
		if (isset($_POST['IdRecapito']) && $_POST['IdRecapito']!=0)
		{
			update();
		}else{
			insert();
		}	
		break;
	case "delete":
		deleteRec();
		break;
	default:
		echo "{failure:true}";
		break;
}
// Aggiorna campo Telefono nel cliente
$telefono = getScalar("select telefoni from v_lista_telefoni where idCliente=".$_POST['IdCliente']);
execute("update cliente set Telefono=".quote_smart($telefono).",LastUser=".quote_smart($context['Userid'])." where idCliente=".$_POST['IdCliente']);

//-----------------------------------------------------------------------
// insert
// Inserimento nuovo record recapito
//-----------------------------------------------------------------------
function insert() {

	$codMex="ADD_RECAP";
	if (!subIns())
	{
		writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
	else
	{
		// ricalcola il valore di IdArea 
		if (aggiornaAreaCliente($_POST['IdCliente'])){	
			writeLog("APP","Gestione recapito","Aggiornamento area cliente riuscita per il cliente n.".$_POST['IdCliente'],$codMex);
			echo "{success:true}";
		}else{
			writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
		}
	}
}
//-----------------------------------------------------------------------
// update
// Salvataggio record Recapito
//-----------------------------------------------------------------------
function update() {
	global $context;
	$setClause = "";
	$codMex="MOD_RECAP";
	if ($_POST['modificabile']=='S') {
		//caso locale, update semplice
		addSetClause($setClause,"IdCliente",$_POST['IdCliente'],"N");
		addSetClause($setClause,"IdContratto",$_POST['IdContratto'],"N");
		addSetClause($setClause,"IdTipoRecapito",$_POST['IdTipoRecapito'],"N");
		addSetClause($setClause,"ProgrRecapito","-UNIX_TIMESTAMP()","G");
		addSetClause($setClause,"Indirizzo",$_POST['Indirizzo'],"S");
		addSetClause($setClause,"Localita",$_POST['Localita'],"S");
		addSetClause($setClause,"CAP",$_POST['CAP'],"S");
		addSetClause($setClause,"SiglaProvincia",$_POST['SiglaProvincia'],"S");
		addSetClause($setClause,"SiglaNazione",$_POST['SiglaNazione'],"S");
		addSetClause($setClause,"Telefono",$_POST['Telefono'],"S");
		addSetClause($setClause,"Cellulare",$_POST['Cellulare'],"S");
		addSetClause($setClause,"Fax",$_POST['Fax'],"S");
		addSetClause($setClause,"Email",$_POST['Email'],"S");
		addSetClause($setClause,"Nome",$_POST['Nome'],"S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		
		$sql =  "UPDATE recapito $setClause WHERE IdRecapito=".$_POST['IdRecapito'];
		
		// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
		// nel caso in cui non ci fosse nessuna modifica di valore) 
		if (execute($sql)) {
			global $par;
			writeLog('APP',$par[1],'Modifica recapito per il cliente '.$par[0],$codMex);  // scrittura nel log
		} else {
			writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
			return;
		}
	}else{
		//caso ocs, ANNULLAMENTO->copia locale
		
		if (subIns()) 
		{
			if (!delOcs())
			{
				writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
				echo "{success:false, error:\"".getLastError()."\"}";
				return;
			}
		} 
		else 
		{
			writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
			return;
		}
	}
	// ricalcola il valore di IdArea 
	if (aggiornaAreaCliente($_POST['IdCliente'])){
		writeLog("APP","Gestione recapito","Aggiornamento area cliente riuscita per il cliente n.".$_POST['IdCliente'],$codMex);	
		echo "{success:true}";
	}else{
		writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}
//-----------------------------------------------------------------------
// deleteRec
// Elimina un recapito
//-----------------------------------------------------------------------
function deleteRec()
{
	//controlla se si tratta di un recapito di ocs o meno (S->locale, N->ocs)
	$codMex="DEL_RECAP";
	if ($_POST['modificabile']=='S') {
		//caso locale cancellazione fisica (eliminazione)
		$sql =  "delete from recapito where IdRecapito =".$_POST['IdRecapito'];
		if (execute($sql)) {
			global $par;
			writeLog('APP',$par[1],'Cancellazione recapito per il cliente '.$par[0],'MOD_CON');  // scrittura nel log
		} else {
			echo "{success:false, error:\"".getLastError()."\"}";
			return;
		}
	}else{
		//caso ocs cancellazione logica (annullamento)
		if (!delOcs()) 
		{
			writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";
			return;
		}
	}	
	// ricalcola il valore di IdArea 
	if (aggiornaAreaCliente($_POST['IdCliente'])){
		writeLog("APP","Gestione recapito","Aggiornamento area cliente riuscita per il cliente n.".$_POST['IdCliente'],$codMex);	
		echo "{success:true}";
	}else{
		writeLog("APP","Gestione recapito","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

function delOcs()
{
	$sql =  "UPDATE recapito r SET FlagAnnullato='S' where IdRecapito =".$_POST['IdRecapito'];
	global $par;
	writeLog('APP',$par[1],'Annullamento recapito per il cliente '.$par[0],'MOD_RECAP');  // scrittura nel log
	return execute($sql);
}

function subIns()
{
	global $context;
	$valList = "";
	$colList = "";
	addInsClause($colList,$valList,"IdCliente",$_POST['IdCliente'],"N");
	addInsClause($colList,$valList,"IdContratto",$_POST['IdContratto'],"N");
	addInsClause($colList,$valList,"IdTipoRecapito",$_POST['IdTipoRecapito'],"N");
	addInsClause($colList,$valList,"ProgrRecapito","-UNIX_TIMESTAMP()","G");
	addInsClause($colList,$valList,"Indirizzo",$_POST['Indirizzo'],"S");
	addInsClause($colList,$valList,"Localita",$_POST['Localita'],"S");
	addInsClause($colList,$valList,"CAP",$_POST['CAP'],"S");
	addInsClause($colList,$valList,"SiglaProvincia",$_POST['SiglaProvincia'],"S");
	addInsClause($colList,$valList,"SiglaNazione",$_POST['SiglaNazione'],"S");
	addInsClause($colList,$valList,"Telefono",$_POST['Telefono'],"S");
	addInsClause($colList,$valList,"Cellulare",$_POST['Cellulare'],"S");
	addInsClause($colList,$valList,"Fax",$_POST['Fax'],"S");
	addInsClause($colList,$valList,"Email",$_POST['Email'],"S");
	addInsClause($colList,$valList,"Nome",$_POST['Nome'],"S");
	addInsClause($colList,$valList,"DataIni","1970-01-01","S");
	addInsClause($colList,$valList,"DataFin","2999-12-31","S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	
	$sql =  "INSERT INTO recapito ($colList)  VALUES($valList)";

	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	global $par;		
	writeLog('APP',$par[1],'Inserimento nuovo recapito per il cliente '.$par[0],'MOD_RECAP');  // scrittura nel log
	return execute($sql);
}
?>
