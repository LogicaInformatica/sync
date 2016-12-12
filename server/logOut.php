<?php
require_once("common.php");

//scrittura nel log del logout dell'utente
$context = $_SESSION['userContext']; // riprende il contesto che aveva salvato
$task = ($_REQUEST['ret']) ? $_REQUEST['ret'] : 0;
if($task){
	$master = $context["master"];
	writeLog('APP','Logout',"Uscita da impersonificazione utente da parte di $master",'Logout');  
	$context["Userid"] = $master;
	$_SESSION['userContext'] = $context;
	header("Location: ../main.php");
}else{
	writeLog('APP','Logout','Logout utente','Logout');
	// pulitura dell'array di sessione (userContext)
	unset($_SESSION['userContext']);
	header("Location: ".PORTAL_URL);
}
?>
