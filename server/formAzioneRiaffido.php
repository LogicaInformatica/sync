<?php
// formAzioneRiaffido
// Genera la struttura del form di tipo "Forza prossimo affido"
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
	case "FPA":  // affido pre-DBT
		$tipoAgenzia = "TipoAgenzia IN ('','AGE')";
		break;
	case "FPS":  // affido stragiudiziale
		$tipoAgenzia = "TipoAgenzia IN ('','STR')";
		break;
	case "FPL":  // affido legale
		$tipoAgenzia = "TipoAgenzia IN ('','LEG','GENLEG')";
		break;
}
$comboAgenzie = generaCombo("Forza prossimo affido","IdRegolaProvvigione","TitoloAgenzia",
			"FROM v_agenzia_provv_plus WHERE $tipoAgenzia");			   
?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 470,height: 220,labelWidth:100,
         defaults: {width: 330},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Agenzia attuale', value: '<?php echo addslashes($agenzie)?>'}, 
        	<?php echo $comboAgenzie?>,
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