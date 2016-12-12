<?php
require_once("../engineFunc.php");

$ids = fetchValuesArray("
select idcontratto from contratto c
where c.datainizioaffido>'2013-11-25'
and idagenzia>0
and idclasse in (13,101,103,104) #lettera INS
and not exists (select 1 from messaggiodifferito m where m.idcontratto=c.idcontratto and datacreazione>c.datainizioaffido AND TIPO='L')
UNION
select idcontratto from contratto c
where c.datainizioaffido>'2013-11-25'
and idagenzia>0
and idclasse in (2,102) #lettera TEK
and not exists (select 1 from messaggiodifferito m where m.idcontratto=c.idcontratto and datacreazione>c.datainizioaffido AND TIPO='L')
order by 1");

echo "Rigenerazione lettere per ".count($ids)." contratti<br>";
trace("Rigenerazione lettere per ".count($ids)." contratti",FALSE);

$cnt=0;
foreach ($ids as $IdContratto)
{
	eseguiAutomatismiPerAzione('AFF',$IdContratto);
	// cancella l'SMS e mantiene la lettera
	execute("DELETE FROM messaggiodifferito WHERE IdContratto=$IdContratto AND Tipo='S' AND DataCreazione>CURDATE()");
}

//affidaTutti($listaClienti);
trace("Fine elaborazione rigeneraLettere.php",FALSE);
die("<br>Fine elaborazione rigeneraLettere.php");
?>	