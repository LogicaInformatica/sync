<?php 
require_once("workflowFunc.php"); 
// formAzioneBase
// Genera la struttura del form di tipo "azione base"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla

$impDebito = getScalar("SELECT ImpInsoluto FROM contratto where IdContratto = ".$ids);

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 480,height: 200,labelWidth:100,
        defaultType: 'textfield',
        items: [{
					fieldLabel:'Importo debito',	
					name:'ImportoDebito',
					xtype:'numberfield', 	
					cls : 'red-title',
					id:'ImportoDebito',
					anchor:"95%",
					format: '0,00',
					style: 'text-align:right',
					value : <?php echo $impDebito?>,
		            width: 340, 
					readOnly: true
				},{
					xtype:'textarea',
					anchor:"95%",
            		fieldLabel: 'Nota',
            		name: 'nota',
		            width: 340, 
					height: 100
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