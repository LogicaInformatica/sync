<?php 
// formAzioneCambioStato (stato recupero)
// Genera la struttura del form di tipo 
// "azione cambio stato recupero" 
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla


// Ottiene la lista degli stati specificati nei contratti selezionati
$IdsStati = fetchValuesArray("SELECT DISTINCT IFNULL(IdStatoRecupero,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsStati)==1) // se assegnato ad un sola stato, nella list box lo deve escludere
{
	$esclude = $IdsStati[0];
	if ($esclude == 0)
		$stati = "nessuno";
	else
		$stati = getScalar("SELECT CONCAT(TitoloStatoRecupero) FROM statorecupero WHERE IdStatoRecupero=$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsStati)==0)
		$stati = "nessuno";
	else
		$stati = "vari";
}

// Genera la combobox per la scelta dello stato da assegnare
$comboStati = generaCombo("Nuovo stato","IdStatoRecupero","TitoloStatoRecupero",
			"FROM statorecupero WHERE IdStatoRecupero != $esclude AND IFNULL(Manuale,'Y')='Y'"
		     ." AND CURDATE() BETWEEN DataIni AND DataFin AND IdStatoRecupero>0 ORDER BY Ordine,TitoloStatoRecupero","","true");
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