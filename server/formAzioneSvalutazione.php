<?php 
// formAzioneSvalutazione
// Genera la struttura del form di tipo 
// "azione Svalutazione" (simile a cambia categoria) 
// Contenuto: textbox numerica (.00), campo note e pulsanti Conferma / Annulla


// Ottiene la percentuale di svalutazione per quei contratti selezionati
$IdsPercSv = fetchValuesArray("SELECT IFNULL(PercSvalutazione,0.00) FROM contratto where IdContratto IN ($ids)");
//$perc = $IdsPercSv[0];
if (count($IdsPercSv)==1) 
{
	$perc = $IdsPercSv[0];
}
else
{
	$perc =0;
}

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'numberfield', fieldLabel: 'Perc. svalutazione', allowBlank:false, allowNegative:false, decimalPrecision:0, name:'percentualeS', maxValue:100, minValue:0, value: '<?php echo $perc?>',width: 50},
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