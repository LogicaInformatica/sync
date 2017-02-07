<?php
require_once("server/constant.php");
require_once("ext/func.php");

session_start();

//------------------------------------------------------------------------------
//Pagina dove si andra' se si e' tutto ok
$PageOk     =  KPAGEOK;
//Pagina dove si andra' se non e' tutto OK
$PageError  =  KPAGEKO;
//------------------------------------------------------------------------------
if (!isset($_POST["FData"]) && ($sito == 'LOCAL'))
{
    $_SESSION= array();
    header("Location: $PageOk");
    $_SESSION["sitolocale"] = 1;
    exit;
}
else
    $_SESSION["sitolocale"] = 0;

//fine le seguenti righe devono essere cancellate a regime
//echo '['.print_r($_SESSION["SUserData"]).']sute=['.$_SESSION["SUserData"]["Sute_vccodana"].'] user=['.$_SESSION["FUser"].']';

$WEstra     = (empty($_SESSION["GSesError"])) ? '':  $_SESSION["GSesError"];
$WEstraMsg  = (empty($_SESSION["GSesErrorMsg"])) ? '':  $_SESSION["GSesErrorMsg"];

//Prende i Data Post venuti dall'esterno
$WData= base64_decode( $_POST["FData"]);
//Li Normalizza
$WUserData= unserialize($WData);
 
$_SESSION= array();
$_SESSION["SUserData"]= array();
//Poi metti i dati un Sessione
$_SESSION["SUserData"]= $WUserData;
 
//------------------------------------------------------------------------------
//Controlla che URl di fin-portal sia valida
if( geu_isTrustURL($_SESSION['SUserData']['SUrlPortal']) ){
    //Ora ricontatta ANA gentrale, per controllare che sia tutto o.k.
    $WResult= gen_checkRemoreSession(1);
}else{
    $WResult["Esito"]=0;
    $WResult["RetCode"]      =  222;
    $WResult["MsgReturn"]   =  'Fin-Portal non valido';

}
//Se è tutto corretto, sessione valida
if(($WResult["Esito"]==1) ){
    //Pulisce eventuali errori passati
    $_SESSION["GSesError"]="";

    //E Rendirizza verso la vera pagina del sito
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: https://$host$uri/$PageOk");
    echo "Reindirizzamento fallito alla pagina: https://$host$uri/$PageOk";
}else{
    //Qualcosa è andato storto, mette i dati dell'errore in sessione
    $_SESSION["GSesError"]      =  $WResult["RetCode"];
    $_SESSION["GSesErrorMsg"]   =  $WResult["MsgReturn"];
    $_SESSION["SUserData"]      =  array();

    //E reindirzza verso la pagina di errore
    header("Location: $PageError");
}
?>

