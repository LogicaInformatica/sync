<?php
require_once("../server/batch/processImportedFiles.php");
set_time_limit(9000); // aumenta il tempo max di cpu

if ($_GET['cond']>'' or $argv[1]>'')
    $cond = $_GET['cond']>'' ? $_GET['cond'] : $argv[1];
else if ($_GET['id']>'')
    $cond =  "idContratto={$_GET['id']}";
else if ($_GET['code']>'')
	$cond =  "CodContratto='{$_GET['code']}'";
else {
    die("Specificare un IdContratto con il parametro 'id' oppure un CodContratto col parametro 'code' oppure una condizione qualsiasi col parametro 'cond'");
}    
$ids = getColumn("select idcontratto from contratto where $cond");
echo "<br>",count($ids)," contratti..."."<br>";
flush();
trace("Inizio calcolo updateOptInsoluti e processInsoluti per ".count($ids)." contratti...",false);
foreach ($ids as $id) {
    if (2==processInsoluti($id)) break;
	if (!updateOptInsoluti("IdContratto=$id")) break;
	//aggiornaCampiDerivati($id);
}
echo "Done.";

?>	