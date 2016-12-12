<?php 
include "formAzioneBaseDBT.php";
?>

formPanel.addButton(
     {
			text: 'Conferma',
			id:'CnfButton',
			handler: function() {
			   var vectValue = [];
			   obj = {nota : Ext.getCmp('nota').getValue()}; 
			   obj1 = {comboAffido : Ext.getCmp('comboAffido').getRawValue()};
			   if(Ext.getCmp('dataVendita').getValue()!='') {
			     obj2 = {dataVendita : Ext.getCmp('dataVendita').getValue().format('d/m/Y')};
  			   } else {
  			       obj2 = {dataVendita : Ext.getCmp('dataVendita').getValue()};
  			     }
  			   if(Ext.getCmp('dataVerifica').getValue()!='') {
			     obj3 = {dataVerifica : Ext.getCmp('dataVerifica').getValue().format('d/m/Y')};
  			   } else {
  			       obj3 = {dataVerifica : Ext.getCmp('dataVerifica').getValue()};
  			     }  
			   vectValue.push(obj);
			   vectValue.push(obj1);
			   vectValue.push(obj2);
			   vectValue.push(obj3); 
			
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
