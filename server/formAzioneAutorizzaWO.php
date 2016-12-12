<?php 

if(count($idsArray)>1){
	$totCapitale = getScalar("SELECT SUM(ImpPap) FROM writeoff WHERE IdContratto IN (".join(",",$idContratti).")");
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
	include "formAzioneBaseMultiplaWO.php";
?>

formPanel.addButton(
          {
			text: 'Conferma',
			id:'CnfButton',
			handler: function() {
			   if(formPanel.getForm().isValid()){
				 DCS.showMask();		
		         formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: '<?php echo $idcontratti?>', autorizMultiplo: 'true'  },
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				 });
			   }		
			}
		  }	
    );  

<?php
   } else { 
	   include "formAzioneBaseWO.php";
	   
?>

formPanel.addButton(
	     {
				text: 'Conferma',
				id:'CnfButton',
				handler: function() {
				    // dati per salvataggio immagine richiesta         	
				    var vectValue = saveWOFormDataToVect();
					
			    	if (formPanel.getForm().isValid()){
						DCS.showMask();
						formPanel.getForm().submit({
							url: 'server/edit_azione.php', method: 'POST',
							params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>", txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue), autorizMultiplo: 'false'}, 
							success: function (frm,action) {saveSuccess(win,frm,action);},
							failure: saveFailure
						});
					}
				}//,scope: this
	      }
);

<?php }?>
	   
formPanel.addButton({text: 'Annulla',id:'anlButton',handler: function () {quitForm(formPanel,win);}}); 