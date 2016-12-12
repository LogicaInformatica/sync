<?php 
// formAzioneBase
// Genera la struttura del form di tipo "azione base"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 480,height: 200,labelWidth:100,
         defaults: {
            width: 340, 
			height: 100
        },
        defaultType: 'textfield',
        items: [{
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