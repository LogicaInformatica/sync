<?php 
// formAzioneChiusura
// Genera la struttura del form di tipo "azione chiusura lavorazione"

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 480,height: 220,labelWidth:120,
         defaults: {width: 300, 
			height: 100
        },
        defaultType: 'textfield',
        items: [{
            xtype: 'radiogroup',
            height: 50,
            fieldLabel: 'Esito del recupero',
            items: [
                {boxLabel: 'Positivo', name: 'esito', inputValue: 'P', checked:true},
                {boxLabel: 'Negativo', name: 'esito', inputValue: 'N'}
            ]
           },{
			xtype:'textarea',
            fieldLabel: 'Nota',
            name: 'nota'
           }],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				DCS.showMask();
				formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>"},
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				});
			}//,scope: this
		},
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});