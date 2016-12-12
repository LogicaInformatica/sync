<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione inviata email"
//$numTel = trim(getScalar("SELECT Cellulare FROM recapito r,contratto c WHERE r.IdCliente=c.IdCliente AND c.IdContratto=$ids AND idTipoRecapito=1"));
//$numTel = preg_replace( '/[^0-9]/i', '', $numTel); //str_replace(" ","",$numTel);
$IdCliente=$row["IdCliente"];
$strNumTel = getScalar("SELECT Cellulare FROM v_cellulare WHERE IdCliente=$IdCliente");
$arrayTel=explode(',', $strNumTel);
for ($i = 0; $i < count($arrayTel); $i++)
{
    $numTel = $arrayTel[$i];
	$numTel = preg_replace( '/[^0-9]/i', '', $numTel);
    $arrayTel[$i] = $numTel;
}
$numTel=implode(',', $arrayTel);
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
	labelWidth: 40, frame: true, title: "<?php echo $titolo?>",
    width: 420,height: 260,labelWidth:100,
    defaults: {
            width: 300, 
			height: 100
        },
        items: [{
        			xtype:'textfield',
         			fieldLabel: 'Cellulare',
        			name: 'cellulare',
        			value: "<?php echo $numTel?>",
        			height: 20,
        			allowBlank: false,
        			vtype: 'cell_list'
        		},{
					xtype:'textarea',
            		fieldLabel: 'Nota/testo',
            		name: 'nota'
        		},{	
        			xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Data verifica',
					value: '<?php echo $dataDefault?>',
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'data',
					minValue: new Date(),
					id: 'data'
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