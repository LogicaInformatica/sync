<?php
// formAzioneProrogaAg
// Genera la struttura del form di tipo "azione proroga ad agenzia"
// Incluso dinamicamente in: generaFormAzione.php

// Ottiene il nome dell'agenzia di affidamento attuale
$dati = getRow("SELECT TitoloAgenzia,DataInizioAffido,DataFineAffido"
              ." FROM contratto c INNER JOIN v_agenzia a ON c.IdAgenzia=a.IdAgenzia WHERE IdContratto IN ($ids)");
if (!is_array($dati)) // nessun affidamento corrente
{
?>
//	debugger;
	var formPanel = new Ext.BoxComponent({autoEl:{html:"Proroga non possibile perch&eacute; questa pratica non &egrave; affidata"},width:500});
<?php
}
else
{
?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 470,height: 220,labelWidth:100,
         defaults: {width: 330},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Agenzia attuale', value: '<?php echo addslashes($dati["TitoloAgenzia"])?>'}, 
        	{xtype:'displayfield', fieldLabel: 'Inizio affido', value: '<?php echo addslashes(italianDate($dati["DataInizioAffido"]))?>'
        	},
         	{xtype:'compositefield',
        	 items: [
        		{	xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Fine affido',
					value: '<?php echo $dati["DataFineAffido"]?>',
					name: 'data',
					minValue: '<?php echo italianDate($dati["DataInizioAffido"]) ?>',
					id: 'data'
				},{xtype:'displayfield', value:'(considerare i giorni fissi di fine lotto)'}
				]},        
            {xtype:'textarea',height:100,fieldLabel: 'Nota',name: 'nota'}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				// qualche campo modificato
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}	
			}
		}, 		
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});
<?php
}
?>