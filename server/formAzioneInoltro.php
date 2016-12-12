<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione inviata email"
$comboRichiesta = generaCombo("Tipo Richiesta","TitoloTipoRichiesta","TitoloTipoRichiesta","FROM tiporichiesta WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY 2");
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo

$idArr=json_decode($idcontratti);
if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
{
	$dataLimite = getScalar("SELECT MIN(DataFineAffido) FROM contratto WHERE IdContratto in (".$idArr[0].")");
	if ($dataLimite==NULL)
		$dataLimite = '9999-12-31';
	else
		$dataLimite = ISODate($dataLimite);
}	
else
	$dataLimite = '9999-12-31';

?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	labelWidth: 40, frame: true, fileUpload: true, title: "<?php echo $titolo?>",
    width: 420,height: 270,labelWidth:100,
    defaults: {
            width: 300, 
			height: 100
        },
        items: [
        		<?php echo $comboRichiesta ?>,
        		{	
        			xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Data verifica',
					value: '<?php echo $dataDefault?>',
					minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'data',
					id: 'data'
				},{
					xtype:'textarea',
            		fieldLabel: 'Nota',
            		name: 'nota'
        		}],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}	
			}//,scope: this
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});