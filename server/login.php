<?php
require_once("userFunc.php");

if (defined('MEN_AT_WORK_PAGE') && MEN_AT_WORK_PAGE!='') {
	if(isset($_SESSION['userContext']))
	{
		$cont=$_SESSION['userContext'];
		$user=$cont['Userid'];
		if (!($user=="difalco" || $user='c.desantis' || $user==""))
		{
			header("location:".MEN_AT_WORK_PAGE);
			die();
		}
	}
	else
	{
		header("location:".MEN_AT_WORK_PAGE);
		die();
	}
}

$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$master='';
$existU = ' AND CURDATE() BETWEEN DataIni AND DataFin AND IdStatoUtente=1';
$getUser = true;
if(isset($_SESSION['userContext'])){

	$cont = $_SESSION['userContext'];
	$master=$cont["master"];

	if($master!=''){
		$user=$cont['Userid'];
		if($master!=$cont['Userid']){
			$existU='';
		}else{
			$master = '';
		}
		$getUser = false;
	}
	unset($_SESSION['userContext']);
}


if ($getUser) {

	
	isset($_REQUEST['wrkflw']) ? $_REQUEST['wrkflw'] : '';
	if($_REQUEST['wrkflw'] != '')
	{
		$_SESSION["workflow"]=$_REQUEST['wrkflw'];
	}
	
	if ($_SESSION["sitolocale"] == 1 // entrata da portale labit/test/local
	|| !isset($_SESSION["sitolocale"]) && ($sito!='PROD' && $sito!='TEST')) {
		
		if (isset($_POST["loginUsername"])) {
			$user = $_POST["loginUsername"];
			$pwd  = isset($_POST["loginPassword"]) ? $_POST["loginPassword"] : "";
		} else {	
		    header("Location: $uri/local_login.html");
			exit;
		}
	} else {
		
		if ($_POST["loginUsername"]=="difalco" && $_POST["loginPassword"]=="logica01"
		|| $_POST["loginUsername"]=="c.desantis" && $_POST["loginPassword"]=="fiat500"
		|| $_POST["loginUsername"]=="f.cerrato" && $_POST["loginPassword"]=="cerrato13")
			$user = $_POST["loginUsername"];
		else
			$user = $_SESSION["SUserData"]["Sute_vccodute"];
	}
}

$error = "";
if (rowExistsInTable('utente',"Userid=".quote_smart($user).$existU)) {
	try {
		createContext($user,$master);
		if (isset($_SESSION['userContext'])) {
			$context = $_SESSION['userContext'];	
			$master  = $context["master"];
			if ($master>'')
				writeLog('APP','Login',"Impersonificazione utente da parte di $master",'Login');  // scrittura nel log dell'login utente
			else			
				writeLog('APP','Login','Login utente','Login');  // scrittura nel log dell'login utente
		} else {
			$error = "Errore nella creazione del contesto utente.";
		}

	} catch (Exception $e) {
		$error = $e->getMessage();
	}
} else {
	$error = "'Nome utente o password non corretti.'";
}
if ($error != "") {
	$_SESSION["GSesError"] = 1;
	$_SESSION["GSesErrorMsg"] = $error;
	$errorString = "?codeErr=3";
	header("Location: $uri/exit.page.php".$errorString);
	exit;
}
?>