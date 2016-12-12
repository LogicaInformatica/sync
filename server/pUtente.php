<?php 
require_once("userFunc.php"); 

// Prepara il numero di versione+numero di build
$numVersione = NUM_VERSIONE;
if ($SESSION['DCSYS_BUILD']) { 
	$numVersione .= ".".$SESSION['DCSYS_BUILD'];
} elseif (function_exists('calcolaNumeroBuild')) {
	$SESSION['DCSYS_BUILD'] = round( (calcolaNumeroBuild()-strtotime(constant('DATA_VERSIONE')>''?DATA_VERSIONE:'2016-06-01')) / 86400);
	$numVersione .= ".".$SESSION['DCSYS_BUILD'];
}

$com = $_SESSION['userContext'];
// verifica se attivo alert per i supervisori di agenzia
 $hiddenAlert = 'style="visibility:hidden"';
 $visAvvisoAge=false;
 if(userCanDo('READ_AVVISO')){
 	$fileAge = getScalar("SELECT FileName FROM modello where TipoModello='P' AND CURDATE() BETWEEN DataIni AND DataFin");
	if($fileAge!="")
	{
		$txt = file_get_contents(TEMPLATE_PATH."/".$fileAge);
		if ($txt>"")
		{	
			$visAvvisoAge=true;
			$hiddenAlert = 'style="visibility:visible"';
			$txt = addslashes($txt);
		}
	 } 
 } 
 $sqlAssegnati='select count(*) as numP from contratto where ';
 if ($com['InternoEsterno']=="I"){
 	$sqlAssegnati .=  'idoperatore=0'.$com['IdUtente'];
 }else{
 	$sqlAssegnati .=  "idagente=0".$com['IdUtente'];
 }
 $numPrAssegn=getScalar($sqlAssegnati);
 
 $sqlAssegnati="select count(*) as numP from nota where datascadenza='".date('Y-m-d',mktime(0,0,0,date("m"),date("d"),date("Y")))."' AND ".userCondition();
 //trace("SELECT per numero scadenze odierne: $sqlAssegnati",FALSE);
 $numPrOdierne=getScalar($sqlAssegnati);
 
// Conta le comunicazioni dirette all'utente e non ancora lette
$sql = "SELECT count(*) FROM nota n " . condNoteNonLette();
$numNuoveNote = getScalar($sql);
 
 // trace("SELECT SUM(IF($NumNote>0,$NumNote,0)) FROM v_note_utente_full WHERE IdUtente=".$com['IdUtente'],FALSE);
 // trace($numNuoveNote,FALSE);

 // Legge le condizioni sulle azioni di workflow che l'utente può gestire e che inducono
 // un cambio di stato, in modo da contare poi su quanti contratti in workflow l'utente
 // può agire (non conta le azioni di annullo, però)
 $sqlWrkFlowCondition="select CASE WHEN condizione IS NOT NULL THEN condizione
                 WHEN sa.IdStatoRecupero>0 THEN CONCAT('IdStatoRecupero=',sa.IdStatoRecupero)
                 ELSE 'IdContratto>0' END AS condizione ".
					"from statoazione sa ".
					"JOIN azione a ON sa.IdAzione=a.IdAzione ".
					"JOIN azioneprocedura ap ON ap.IdAzione=sa.idazione ".
					"JOIN profilofunzione pf ON pf.idfunzione=a.idfunzione ".
					"JOIN profiloutente pu ON pu.idprofilo=pf.idprofilo ".
					"where idstatorecuperosuccessivo is not null ".
					"and idutente=0".$com['IdUtente'].";";
 $arrCondition=getFetchArray($sqlWrkFlowCondition);
 
 if (count($arrCondition)>0)
 {
 	// Compone una
	$stringCond='';
 	foreach ($arrCondition as $condizione)
 	{
 		if($condizione['condizione'] != '')
 		{
 			$stringCond .= "c.".$condizione['condizione']." or ";
 		}
 	}
 	$stringCond=substr($stringCond,0,(strlen($stringCond)-4));
  	//conta i contratti che le soddisfano
 	$sqlWrkFlow="select count(*) ".
			"from v_contratto_workflow c ".
			"left join statorecupero sr ON sr.idstatorecupero=c.idstatorecupero ".
 			"left join utente u on u.IdUtente = c.IdOperatore ".
			"where sr.codstatorecupero like 'WRK%' ";
 	//filtro su operatore e reparto
		$IdUtente = $context["IdUtente"];
		$IdReparto = $context["IdReparto"];
		$clause = "IFNULL(c.IdOperatore,0)=0$IdUtente";
		if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto
			$clause .= " OR IFNULL(u.IdReparto,0)=0$IdReparto";
		if (userCanDo("READ_NONASSEGNATE")) // autorizzato a vedere le pratiche non assegnate
			$clause .= " OR u.IdReparto IS NULL";
		$sqlWrkFlow.= " AND ($clause)";
	//END filtro su operatore e reparto
 	$sqlWrkFlow.=" and (".$stringCond.");";
    //trace($sqlWrkFlow); 	
 	$numWrkFlow=getScalar($sqlWrkFlow);
 }
 else
	 $numWrkFlow=0;
?>
<table border="0" width="100%">
<?php 
if ($numWrkFlow>0) 
{
?>
	<tr>
		<td style="font-size:12; color:red;" align=right>Numero pratiche in workflow:</td>
		<td style="font-size:12; color:red;" align=right><a href="javascript:showPraticheWorkflow()"><?php echo $numWrkFlow;?></a></td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:10; color:blue;" align=right>
			<a <?php echo $hiddenAlert;?> onClick="Ext.namespace('DCS');CONTEXT.redisplayMsg=true;DCS.periodicNotify.delay(0);"><img src="images/alert.gif"  tooltip="Attenzione">
			</a>&nbsp;&nbsp;<?php echo "Versione $numVersione";?>
		</td>
	</tr>
<?php 
}elseif ($visAvvisoAge==true){
?>
	<tr>
		<td style="font-size:12; color:red;" align=right>&nbsp;</td>
		<td style="font-size:12; color:red;" align=right>&nbsp;</td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:10; color:blue;" align=right>
			<a <?php echo $hiddenAlert;?> onClick="Ext.namespace('DCS');CONTEXT.redisplayMsg=true;DCS.periodicNotify.delay(0);"><img src="images/alert.gif"  tooltip="Attenzione">
			</a>&nbsp;&nbsp;<?php echo "Versione $numVersione";?>
		</td>
	</tr>

<?php
} else {
?>
	<tr>
		<td style="font-size:12; color:red;" align=right>&nbsp;</td>
		<td style="font-size:12; color:red;" align=right>&nbsp;</td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:10; color:blue;" align="right">&nbsp;&nbsp;<?php echo "Versione $numVersione";?>
		</td>
	</tr>

<?php
}
if ($numNuoveNote>0)
{
?>
	<tr>
		<td style="font-size:12; color:red;" align=right>Numero messaggi non letti:</td>
		<td style="font-size:12; color:red;" align=right><a href="javascript:showMessaggiNonLetti();"><?php echo $numNuoveNote;?></a></td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:10; color:blue;" align=right>
		</td>
	</tr>
<?php 
}
?>
	<tr>
		<td style="font-size:12; color:blue;" align=right>Numero pratiche assegnate:</td>
		<td style="font-size:12; color:blue;" align=right><?php echo $numPrAssegn;?></td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:12; color:blue;" align=right><?php 
																if($com['master']!=''){
																	echo $com['NomeUtente']." [".$com['master']."]";						
																}else{echo $com['NomeUtente'];}
															?></td>
	</tr>
	<tr>
		<td style="font-size:12; color:blue;" align=right>Numero scadenze odierne:</td>
		<td style="font-size:12; color:blue;" align=right><?php echo $numPrOdierne;?></td>
		<td style="font-size:12; color:blue;" align=right>&nbsp;</td>
		<td style="font-size:10; color:blue;" align=right><?php 
			if($com['master']!='')
				echo "<a style='text-decoration:none' href='server/logOut.php?ret=1'>(Return) </a>";					
			/* link usato per operazioni di debug veloce */
			$specialLink = constant("SPECIAL_LINK");
			if ($specialLink>'' && $com['Userid']=='difalco')
			{
				echo "<a href=\"javascript:$specialLink\">$specialLink</a>";
			}
			?>
			<a style="text-decoration:none" href="server/logOut.php">(Log Out)</a></td>
	</tr>
</table>