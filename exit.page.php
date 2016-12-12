<?php
require_once("server/constant.php");
session_start();
//------------------------------------------------------------------------------
//Array con il titolo e la descrizione dell'errore
$TitleError=array();

$TitleError=array();
$TitleError[1]="Errore di comunicazione";
$TitleError[2]="Session non esistente o non valida";
$TitleError[3]="Utenza non valida";
$TitleError[4]="Concessionario non valido";
$TitleError[5]="Password non valida";
$TitleError[6]="";
$TitleError[7]="Attenzione! Sessione scaduta";
$TitleError[8]="Sessione scaduta";
$TitleError[9]="Sessione chiusa o utilizzata per un altro accesso";
$TitleError[10]="";
$TitleError[11]="Portale non attivo";
$TitleError[12]="Session non valida";
$TitleError[13]="Session non valida";
$TitleError[92]="Convenzione invalida";
$TitleError[101]="Session non valida";
$TitleError[222]="Errore grave di comunicazione";

$DescrError=array();
$DescrError[1]="Impossibile accedere al database.";
$DescrError[2]="Session non esistente o non valida";
$DescrError[3]="Utente non esistente, o non abilitato";
$DescrError[4]="Concessionario non valido";
$DescrError[5]="Password non valida";
$DescrError[7]="Attenzione! Sessione scaduta";
$DescrError[6]="";
$DescrError[8]="Sessione scaduta per inattivit&agrave;, rifare il Login";
$DescrError[9]="Sessione chiusa o utilizzata per un altro accesso";
$DescrError[10]="";
$DescrError[11]="Sessione non valida";
$DescrError[12]="Session non valida";
$DescrError[13]="Session non valida";
$DescrError[92]="Nessuna convenzione attiva per l'utente";
$DescrError[101]="Parametri non validi";
$DescrError[222]="Indirizzo di fin-portal non valido, contattare lo staff tecnico.";
//------------------------------------------------------------------------------
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
  <title></title>
    <!-- LINK href="Styles.css" type="text/css" rel="stylesheet" -->
</head>

<body>
<?php
//messaggio visualizzato per l'utente.
$message="I Tuoi Dati sono in corso di elaborazione, ";
$mailmessage ="Anomalia riscontrata sul sito '".$_SERVER['SERVER_NAME']."'\n";
$utente = $_SESSION["SUserData"]["Sute_vccog"]." ".$_SESSION["SUserData"]["Sute_vcnom"];
  if (isset($_REQUEST["codeErr"]))
    {
      $codeErr=$_REQUEST["codeErr"];
      switch (intval($codeErr))
      {
       case 1:
        {
            $mailmessage = "L'utente corrente non e' autorizzato.";
            $mailSubject="Utente non autorizzato";
            break;
        }
      case 2:
      {
        $mailmessage.="Occorre portare a termine la registrazione dell'utente " .$utente.".";
        $mailSubject ='Registrazione nuovo utente';
        break;
      }
      case 3:
        {
            $mailmessage.=$_SESSION["GSesErrorMsg"];
            $mailSubject = "Errore nell'applicazione";
            break;
        }
      }
    }
  else
    if(!($_SESSION["GSesError"]==0))
       // $message="Errore di sistema: è possibile che la sessione utente sia scaduta:<br>";
       {
         $errormsg = $_SESSION["GSesErrorMsg"];
         $mailmessage.=$errormsg;
         $mailSubject = "Errore di sistema";
       }

/*
  if (!($mailmessage==''))
    {
        mail(MAIL_ERROR_CONTACT,$mailSubject,$mailmessage,"From:" .getSysParm("MAIL_SENDER","noreply@dcsys.it"));
    }
*/
  if (!($message==''))
  {?>
 <form id="formRichiesta" target="_top" method="post" action="<?php echo PORTAL_URL?>">
	<table width="650px" border="0" id="Table3" align="center">
        <tr><td height="30"></td></tr>
		<tr>
			<td colspan="3" class="textintroduction" id="msgErrore" align="center">
			<?php echo $mailSubject. ' - '.$mailmessage?>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="textintroduction" id="msgErrore" align="center">
            Rieffettua il login utilizzando il bottone sottostante</td>
		</tr>
		<tr>
			<td colspan="3" height="20"></td>
		</tr>
        <tr>
			<td colspan="3" align="center"><input class="PulsanteSito" id="Button1" style="COLOR: #ff0000" type="submit" value="Accedi"/></td>
		</tr>
    </table>
 </form>
 <?php }?>
<br>
<br>
<!--font size="1">Informazioni per il supporto tecnico<br>
ID Errore: <?php echo $_SESSION["GSesError"]; ?> <br>
Descrizione sintetica Errore: <?php echo $_SESSION["GSesErrorMsg"]; ?> <br>
Descrizione Errore: <?php echo $DescrError[$_SESSION["GSesError"]]; ?> <br>
</font-->
</body>

</html>