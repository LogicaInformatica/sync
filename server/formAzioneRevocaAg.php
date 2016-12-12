<?php
// formAzioneRevocaAg
// Genera la struttura del form di tipo "azione revoca ad agenzia"
// Incluso dinamicamente in: generaFormAzione.php

// Ottiene il nome dell'agenzia di affidamento attuale
$dati = getRow("SELECT TitoloAgenzia,DataInizioAffido,DataFineAffido"
              ." FROM contratto c INNER JOIN v_agenzia a ON c.IdAgenzia=a.IdAgenzia WHERE IdContratto=$ids");
if (!is_array($dati)) // nessun affidamento corrente
{
?>
	var formPanel = new Ext.BoxComponent({autoEl:{html:"Revoca non possibile perch&eacute; questa pratica non &egrave; affidata"},width:500});
<?php
}
else
{
?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Agenzia attuale', value: '<?php echo addslashes($dati["TitoloAgenzia"])?>'}, 
        	{xtype:'displayfield', fieldLabel: 'Inizio affido', value: '<?php echo addslashes(italianDate($dati["DataInizioAffido"]))?>'}, 
        	{xtype:'displayfield', fieldLabel: 'Fine affido', value: '<?php echo addslashes(italianDate($dati["DataFineAffido"]))?> (sar&agrave; terminato oggi)'}, 
            {xtype:'textarea',height:100,fieldLabel: 'Nota',name: 'nota'}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				DCS.showMask();
				// qualche campo modificato
				formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>"},
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				});
			}
		}, 		
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});
<?php
}
?>
