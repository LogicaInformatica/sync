<?php 
// formAzioneSpeciale
// Genera la struttura del form di tipo "azione speciale con flag"
// Contenuto: Campo note, un flag dipendente dal tipo di azione e pulsanti Conferma / Annulla
$nonPrevedeConvalida = $azione["FlagSpeciale"]=='Y'?'false':'true';
switch ($azione["CodAzione"])
{
	case "REP":  // (ir)reperibilità
		$boxLabel = 'Intestatario irreperibile';
		$attuale  = getScalar("SELECT FlagIrreperibile FROM cliente WHERE IdCliente = (SELECT IdCliente FROM Contratto WHERE IdContratto IN ($ids))");
		break;
	case "IPO":  // ipoteca
		$boxLabel = 'Bene ipotecato';
		$attuale  = getScalar("SELECT FlagIpoteca FROM contratto WHERE IdContratto IN ($ids)");
		break;
}
?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 480,height: 220, labelWidth:100,
         defaults: {
            width: 340, 
			height: 150
        },
        defaultType: 'textfield',
        items: [{   xtype: 'checkbox',
				    height: 30,
				    boxLabel: '<?php echo $boxLabel?>',
					name: 'chkFlag',
					id: 'chkFlag',
					checked: <?php echo $attuale=='Y'?'true':'false' ?>
		},{
			xtype:'textarea',
            fieldLabel: 'Nota',
            name: 'nota'
        },{
            xtype: 'displayfield', 
	        value: 'NB: Azione soggetta a convalida da parte del mandatario',
	        width: 500,
	        hidden: <?php echo $nonPrevedeConvalida?>,
	        name: 'notaInforma', 
	        id: 'notaInforma'
	           
	    }],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				DCS.showMask();
				formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>"},
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				});
			}//,scope: this
		},
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});