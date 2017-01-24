<?php
// formAzioneAffidaAg
// Genera la struttura del form di tipo "azione assegna ad agenzia"
// Incluso dinamicamente in: generaFormAzione.php

// Ottiene la lista delle agenzie specificate nei contratti selezionati
$IdsAgenzie = getFetchArray("SELECT DISTINCT IFNULL(IdAgenzia,0) AS IdAgenzia,CodRegolaProvvigione FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsAgenzie)==1) // se e' assegnato una sola agenzia, nella list box lo deve escludere
{
	$riga = $IdsAgenzie[0];
	$escludeAg = $riga["IdAgenzia"];
	$escludeProv = $riga["CodRegolaProvvigione"];
	if ($escludeAg == 0)
		$agenzie = "nessuna";
	else
		$agenzie = getScalar("SELECT TitoloUfficio FROM reparto WHERE IdReparto=$escludeAg")
				. " (Codice $escludeProv)";		
}
else // diverse agenzie/codici affidate per l'insieme di contratti selezionati, oppure nessuna
{
	$escludeAg = "0";
	$escludeProv = '';
	if (count($IdsAgenzie)==0)
		$agenzie = "nessuna";
	else
		$agenzie = "varie";
}
	
// Genera la combobox per la scelta dell'agenzia a cui assegnare
switch ($azione["CodAzione"])
{
	case "AFF":  // affido pre-DBT
		$tipoAgenzia = 'AGE';
		$dataDefault = getScalar("SELECT CURDATE()+INTERVAL 30 DAY");	
		$nota = "(considerare i giorni fissi di fine lotto)";		   
		break;
	case "STR":  // affido stragiudiziale
		$tipoAgenzia = 'STR';
		$dataDefault = getScalar("SELECT LAST_DAY(CURDATE()+INTERVAL 62 DAY)");			   
		$nota = "(assunti 3 mesi di affido: modificare se necessario)";		   
		break;
	case "ALE":  // affido legale
		$tipoAgenzia = 'LEG';
		$dataDefault = getScalar("SELECT CURDATE()+INTERVAL 45 DAY");	
		$nota = "(assunti 45 gg di affido: modificare se necessario)";		   
		// 21/10/2013: Se il contratto (è uno solo e) è di tipo Loan, 50gg, altrimenti 30 gg
		// se i contratti sono più di uno usa il tipo del primo
		//if (substr($codici[0],0,2)=='LO') { 
		//	$dataDefault = getScalar("SELECT CURDATE()+INTERVAL 50 DAY");			   
		//	$nota = "(assunti 50 giorni di affido: modificare se necessario)";		   
		//} else { // leasing
		//	$dataDefault = getScalar("SELECT CURDATE()+INTERVAL 30 DAY");			   
		//	$nota = "(assunti 30 giorni di affido: modificare se necessario)";		   
		//}
		break;
	case "AFR":  // affido rinegoziazione
		$tipoAgenzia = 'RIN';
		$dataDefault = getScalar("SELECT CURDATE()+INTERVAL 15 DAY");	;
		$nota = "(assunti 15 gg di affido: modificare se necessario)";		   
		break;
}
$comboAgenzie = generaCombo("Agenzia","IdAgenzia","TitoloAgenzia",
			 "FROM v_agenzia_provv WHERE (IdAgenzia != $escludeAg OR CodRegolaProvvigione != '$escludeProv')"
			." AND TipoAgenzia='$tipoAgenzia' AND (CURDATE() BETWEEN DataIni AND DataFin) ORDER BY TitoloAgenzia");

?>


var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 540,height: 220,labelWidth:100,
         defaults: {width: 420},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Agenzia attuale', value: '<?php echo addslashes($agenzie)?>'}, 
        	<?php echo $comboAgenzie?>,
        	{xtype:'compositefield',
        	 items: [
        		{	xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Fine affido',
					value: '<?php echo $dataDefault?>',
					name: 'data',
					minValue: new Date(),
					id: 'data'
				},{xtype:'displayfield', value:'<?php echo $nota?>'}
				]},
            {xtype:'textarea',height:100,fieldLabel: 'Nota',name: 'nota'}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				// qualche campo modificato
				if (formPanel.getForm().isValid()){	
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: { idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>" },
						success: function (frm,action) { saveSuccess(win,frm,action); },
						failure: saveFailure
					});
				}
			}
		}, 		
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});