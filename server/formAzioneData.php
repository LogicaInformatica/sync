<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione con data"
// Contenuto: campo data (da inserire in scadenzario), note e pulsanti Conferma / Annulla

$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
$idArr=json_decode($idcontratti);
$chkHidden = false;
if(rowExistsInTable("nota","IdContratto in (".$idArr[0].") and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;

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
    width: 420,height: 220,labelWidth:100,
    defaults: {
            width: 300, 
			height: 100
        },
        defaultType: 'textfield',
        items: [{
        			xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Prossima data',
					value: '<?php echo $dataDefault?>',
					minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'data',
					id: 'data'
				},
				{
				    xtype: 'checkbox',
				    height: 30,
				    boxLabel: '<span style="color:blue;"><b>Elimina scadenze gi&agrave; in calendario</b></span>',
					name: 'chkHidden',
					id: 'chkHidden',
					hidden: '<?php echo $chkHidden?>',
					disabled:'<?php echo $chkHidden?>',
					checked: false,

					listeners:{
			 			check: function(r,v)
			 			{
		 	 			  if(v==true)
			 			  {
							Ext.Msg.show({
							   title:'Attenzione...',
							   msg: '<span style="color:red;"><b align="justify">Selezionando questa voce saranno sostituite tutte le scadenze gi&agrave; inserite per questa pratica.</b></span>',
							   buttons: Ext.Msg.OK,
							   icon: Ext.MessageBox.WARNING
							});	
								
						  }
			 		    }
			 		}    
				},				
				{
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
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}//,scope: this
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});