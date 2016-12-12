<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione inviata email"
$IdCliente=$row["IdCliente"];
$indirizzoEmail = trim(getScalar("SELECT Email FROM v_email WHERE IdCliente=$IdCliente")); 
//trim(getScalar("SELECT Email FROM recapito r,contratto c WHERE r.IdCliente=c.IdCliente AND c.IdContratto=$ids AND idTipoRecapito=1"));
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
    width: 420,height: 300,labelWidth:100,
    defaults: {
            width: 300, 
			height: 100
        },
        items: [{   
        			xtype: 'textfield',	
           			fieldLabel: 'Indirizzo e-mail',
           			name: 'email',
           			value: "<?php echo $indirizzoEmail?>",
           			vtype: 'email_list',
           			allowBlank: false,
           			height: 20
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
					minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'data',
					id: 'data'
				},{
		            xtype: 'fileuploadfield',
		            fieldLabel: 'Allega email',
		            name: 'docPath',
		            id: 'docPath',
		            height: 40,
		            buttonText: 'Cerca'
		        
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