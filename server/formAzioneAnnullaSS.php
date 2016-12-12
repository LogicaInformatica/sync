<?php 
if(count($idsArray)>1){
	for($i=0; $i<count($idsArray); $i++)  
	{
		
		$dataForm=getRow("select * from v_pratiche WHERE IdContratto = '".$idsArray[$i]."'");
        $capitale= $dataForm['ImpDebitoResiduo']+$dataForm['Importo'];
        $impSaldoStralcio  = $dataForm["ImpSaldoStralcio"];
        $subTotCapitale = $subTotCapitale + $capitale;
        $subTotImportoProposto = $subTotImportoProposto + $impSaldoStralcio;
        $idContratti[]=$idsArray[$i];
	}
    $totCapitale = number_format($subTotCapitale, 2, ',', '.');
    $totImportoProposto = number_format($subTotImportoProposto, 2, ',', '.');
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
	include "formAzioneBaseMultiplaSS.php";
	$confermaButton = <<<EOT
	
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
					 params: {idstatoazione: $idstatoazione, idcontratti: '$idcontratti', annulloMultiplo: 'true'  },
					 success: function (frm,action) {saveSuccess(win,frm,action);},
					 failure: saveFailure
				 });
			   }		
			}
		  }	
    );  
EOT;
		 
} else {
	include "formAzioneBaseSS.php";
	$confermaButton = <<<EOT
	
	   Ext.getCmp('importoProposto').setReadOnly(true);
       Ext.getCmp('dataVerifica').setVisible(false);
       Ext.getCmp('dataPagamento').setReadOnly(true);
       
	   formPanel.addButton(
	     {
				text: 'Conferma',
				id:'CnfButton',
				handler: function() {
			   	var vectValue = saveSSFormDataToVect();
				
				   if (formPanel.getForm().isValid()){
				 		DCS.showMask();
				   		formPanel.getForm().submit({
							url: 'server/edit_azione.php', method: 'POST',
							params: {idstatoazione: $idstatoazione, idcontratti: '$idcontratti', txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue), annulloMultiplo: 'false'},  
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

