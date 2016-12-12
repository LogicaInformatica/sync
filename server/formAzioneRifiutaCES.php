<?php 

if(count($idsArray)>1){
	for($i=0; $i<count($idsArray); $i++)  
	{
		
		$dataForm=getRow("select * from v_pratiche WHERE IdContratto = '".$idsArray[$i]."'");
        $capitale= $dataForm['ImpDebitoResiduo']+$dataForm['Importo'];
        $subTotCapitale = $subTotCapitale + $capitale;
        $idContratti[]=$idsArray[$i];
	}
    $totCapitale = number_format($subTotCapitale, 2, ',', '.');
    $codiciContratto=array();
	if (count($idContratti) > 0){
		$codiciContratto  = fetchValuesArray("SELECT CodContratto FROM contratto WHERE IdContratto IN (".join(",",$idContratti).")");
	}
    if (count($codiciContratto) > 1){
		if (count($codiciContratto)<=8){ 
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$codiciContratto);
		}else{
			$output = array_slice($codiciContratto, 0, 6);   
			$titolo = "&nbsp;Pratiche nn. ".join(", ",$output)." e altre ".(count($codiciContratto)-6);
		}
	}else{
		$titolo = "&nbsp;Pratica n. ".join(", ",$codiciContratto);
	}
	include "formAzioneBaseMultiplaCES.php";
?>
     
     Ext.getCmp('dataVerifica').setReadOnly(true);
	   
	 formPanel.addButton(
          {
			text: 'Conferma',
			id:'CnfButton',
			handler: function() {
			   if(formPanel.getForm().isValid()){
				 DCS.showMask();
				 formPanel.getForm().submit({
					 url: 'server/edit_azione.php', method: 'POST',
					 params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: '<?php echo $idcontratti?>', rifiutoMultiplo: 'true'},
					 success: function (frm,action) {saveSuccess(win,frm,action);},
					 failure: saveFailure
				 });
			   }		
			}
		  }	
    ); 	

<?php
   } else { 
	include "formAzioneBaseCES.php";
?>

Ext.getCmp('dataVerifica').setReadOnly(true);
Ext.getCmp('chkHidden').setVisible(false);

formPanel.addButton(
	     {
				text: 'Conferma',
				id:'CnfButton',
				handler: function() {
				   var vectValue = [];
				    obj = {nota : Ext.getCmp('nota').getValue()};
					if(Ext.getCmp('dataVerifica').getValue()!='') {
					  obj1 = {dataVerifica : Ext.getCmp('dataVerifica').getValue().format('d/m/Y')};
			  		} else {
			  			obj1 = {dataVerifica : Ext.getCmp('dataVerifica').getValue()};
			  		  }  
					vectValue.push(obj);
					vectValue.push(obj1);
					
				    if (formPanel.getForm().isValid()){
						DCS.showMask();
						formPanel.getForm().submit({
							url: 'server/edit_azione.php', method: 'POST',
							params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>", txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue), rifiutoMultiplo: 'false'}, 
							success: function (frm,action) {saveSuccess(win,frm,action);},
							failure: saveFailure
						});
					}
				}//,scope: this
	      }
);

<?php } ?>
	   
formPanel.addButton({text: 'Annulla',id:'anlButton',handler: function () {quitForm(formPanel,win);}}); 