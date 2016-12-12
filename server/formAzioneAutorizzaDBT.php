<?php 

if(count($idsArray)>1){
	for($i=0; $i<count($idsArray); $i++)  
	{
		
		$dataForm=getRow("select * from v_pratiche WHERE IdContratto = '".$idsArray[$i]."'");
        $capitale= $dataForm['ImpDebitoResiduo']+$dataForm['Importo'];
        $subTotCapitale = $subTotCapitale + $capitale;
        $idContratti[]=$idsArray[$i];
	}
    $totCapitale=number_format($subTotCapitale, 2, ',', '.');
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
	include "formAzioneBaseMultiplaDBT.php";
	$confermaButton = <<<EOT
	   formPanel.addButton(
          {
			text: 'Conferma',
			id:'CnfButton',
			handler: function() {
			   if(formPanel.getForm().isValid()){
				 DCS.showMask();		
		         formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: $idstatoazione, idcontratti: '$idcontratti', autorizMultiplo: 'true'  },
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				 });
			   }		
			}
		  }	
    );  
EOT;
		 
} else {
	include "formAzioneBaseDBT.php";
	$confermaButton = <<<EOT

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
							params: {idstatoazione: $idstatoazione, idcontratti: '$idcontratti', idOldRegolaProvvigione: $IdRegolaProvvigione, txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue), autorizMultiplo: 'false'}, 
							success: function (frm,action) {saveSuccess(win,frm,action);},
							failure: saveFailure
						});
					}
				}//,scope: this
	      }
	   ); 
EOT;
}

?>

<?php echo $confermaButton; ?>
formPanel.addButton({text: 'Annulla',id:'anlButton',handler: function () {quitForm(formPanel,win);}});  