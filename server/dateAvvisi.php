<?php
//----------------------------------------------------------------
// Ritorna un array di date aventi degli avvisi
//----------------------------------------------------------------
require_once("userFunc.php");

if ($context['InternoEsterno']=='E') // utente esterno (di agenzia)
{  // non può vedere le scadenze di altri reparti
	$cond = " AND nota.IdUtente NOT IN (SELECT IdUtente FROM utente WHERE IdReparto!=".$context["IdReparto"].")";
}
else
	$cond = "";

$query = "SELECT DISTINCT DATE_FORMAT(datascadenza,'%d/%m/%y') as datascadenza FROM nota WHERE datascadenza IS NOT NULL and tiponota='S' and idutente=".$context["IdUtente"];
/*$query = "SELECT DATE_FORMAT(datascadenza,'%d/%m/%y') as datascadenza FROM nota" .
		" WHERE datascadenza IS NOT NULL AND " . userCondition() . $cond .
		" ORDER BY datascadenza";*/
$arr = fetchValuesArray($query);

echo json_encode_plus($arr);
