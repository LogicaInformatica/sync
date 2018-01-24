<?php 
// formAzioneCambioDataRiscScad
// Genera la struttura del form di tipo 
// "azione cambia data chiusura" 
// Contenuto: campo data chiusura e pulsanti Conferma / Annulla

// Ottiene la lista delle agenzie specificate nei contratti selezionati
$dataChiusura = getScalar("SELECT DataChiusura FROM contratto WHERE IdContratto IN ($ids)");

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 120,labelWidth:100,
         defaults: {width: 300},
        items: [
            {	
            	xtype: 'datefield',
				format: 'd/m/Y',
				width: 100,
				fieldLabel: 'Data chiusura',
				value: '<?php echo $dataChiusura?>',
				name: 'dataChiusura',
				id: 'dataChiusura'
			}
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