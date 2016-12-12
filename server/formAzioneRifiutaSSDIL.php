<?php 

    include "formAzioneBaseSSDIL.php";
	
?>

Ext.getCmp('importoProposto').setReadOnly(true);
Ext.getCmp('dataVerifica').setReadOnly(true);
Ext.getCmp('primoImporto').setReadOnly(true);
Ext.getCmp('dataPagPrimoImporto').setReadOnly(true);
Ext.getCmp('numeroRate').setReadOnly(true);
Ext.getCmp('decorrenzaRata').setReadOnly(true);
Ext.getCmp('chkHidden').setVisible(false);

formPanel.addButton(
	     {
				text: 'Conferma',
				id:'CnfButton',
				handler: function() {
				   var vectValue = saveSSDFormDataToVect();
				
				   if (formPanel.getForm().isValid()){
						DCS.showMask();
						formPanel.getForm().submit({
							url: 'server/edit_azione.php', method: 'POST',
							params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>", txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue)},  
							success: function (frm,action) {saveSuccess(win,frm,action);},
							failure: saveFailure
						});
					}
				}//,scope: this
	      }
);

formPanel.addButton({text: 'Annulla',id:'anlButton',handler: function () {quitForm(formPanel,win);}}); 

