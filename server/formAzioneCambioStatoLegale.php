<?php 
// formAzioneCambioStatoLegale
// Genera la struttura del form di tipo 
// "azione cambio stato legale" 
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla


// Ottiene la lista degli stati legali specificati nei contratti selezionati
$IdsStati = fetchValuesArray("SELECT DISTINCT IFNULL(IdStatoLegale,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsStati)==1) // se assegnato ad un sola stato, nella list box lo deve escludere
{
	$esclude = $IdsStati[0];
	if ($esclude == 0)
		$stati = "nessuno";
	else
		$stati = getScalar("SELECT CONCAT(TitoloStatoLegale) FROM statolegale WHERE IdStatoLegale=$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsStati)==0)
		$stati = "nessuno";
	else
		$stati = "vari";
}

// Genera la combobox per la scelta della cagegoria a cui assegnare
$comboStati = generaCombo("Nuovo stato","IdStatoLegale","TitoloStatoLegale",
			"FROM statolegale WHERE IdStatoLegale != $esclude ORDER BY TitoloStatoLegale","","true");

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Stato attuale', value: '<?php echo addslashes($stati)?>'}, 
        	<?php echo $comboStati?>,
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