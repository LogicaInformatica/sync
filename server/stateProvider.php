<?php
/* Legge/Scrive lo stato dell'interfaccia utente */
require_once("common.php");

$cmd = ($_REQUEST['cmd']) ? ($_REQUEST['cmd']) : null;
switch($cmd){
	case 'readState':
		$sql = "SELECT StateId as name, IFNULL(Value,'') as value FROM uistate WHERE IdUtente=".$_REQUEST['user']
			.  " AND StateId NOT LIKE '*%'"; // quelli col nome che comincia con asterisco sono da non leggere
			                                 // save di dati controllati da programma
		$arr = getFetchArray($sql);
		echo '{success:true, data:' . json_encode_plus($arr) . '}';
		break;

	case 'readOneState':
		$sql = "SELECT value FROM uistate WHERE IdUtente=".$_REQUEST['user']." AND StateId='".$_REQUEST["stateId"]."'";
		trace("readOneState $sql",FALSE);
		$value = getScalar($sql);
		if (getLastError()>"")
			echo '{success:false, msg: getLastError(), data:""}';
		else
			echo '{success:true, data:' . json_encode_plus($value) . '}';
		break;
	
	case 'saveState':
		$arr = json_decode($_REQUEST['data']);
		if (!is_array($arr))
			die("{success:false,msg:'Il parametro data non è un\'array'}");
			
		$id = "(".$_REQUEST['user'].",";
		$valori = "";
		foreach ($arr as $obj) {
			$valori .= ($valori==''?$id:",$id")."'$obj->name','$obj->value')";
		}
		if ($valori>'')
		{
			if (execute("REPLACE INTO `uistate` (`IdUtente`,`StateId`,`Value`) VALUES $valori")) 
				echo "{success:true}";
			else
				echo "{success:false,msg:".quote_smart(getlastError())."}";
		}
		break;

	default:
		echo "{success:false, msg:'Parametro cmd=".quote_smart($cmd)." non riconosciuto'}";
		break;
}
?>