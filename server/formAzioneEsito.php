<?php 
require_once("workflowFunc.php");
// formAzioneAssegnaOp
// Genera la struttura del form di tipo "esito"
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla


// Generalizzare così:
//
//  generaCombo(keyFieldName,displayFieldName,sqlPortion,fieldLabel,hiddenName);
//
$comboEsito = generaCombo("Esito","IdTipoEsito","TitoloTipoEsito","FROM tipoesito WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY ordine,2");
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
$oraDefault  = "11:00";
//

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
	frame: true, title: "<?php echo $titolo?>",
    width: 440,height: 270,labelWidth:120,
        defaults: {width: 300},
        items: [     
        	<?php echo $comboEsito ?>,
			{xtype:'textarea',height:100,fieldLabel: 'Nota',name: 'nota'},
			{xtype: 'compositefield',items: [
				{xtype: 'datefield',
						format: 'd/m/Y',
						width: 100,
						fieldLabel: 'Prossima data',
						value: '<?php echo $dataDefault?>',
						minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
						name: 'data',
						id: 'data'
				},			
				{xtype: 'timefield',
						format: 'H:i',
						width: 70,
						fieldLabel: 'ora',
						value: '<?php echo $oraDefault?>',
						name: 'ora',
						id: 'ora'
				}]},
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
				}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				// qualche campo modificato
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}
		},
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);}
		}]  // fine array buttons
});