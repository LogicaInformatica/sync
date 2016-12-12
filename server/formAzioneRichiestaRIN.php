<?php 
// formAzioneRichiestaRIN
// Genera la struttura del form di tipo "richiesta rinegoziazione"

if ($context["InternoEsterno"] == 'I') // operatore interno
	$cond = "";
else if (userCanDo("PRATICHE_RINE")) // esterno di agenzia di rinegoziazione
	$cond = " AND TipoVisibilita IN ('A','R')"; 
else // agenzia non di rinegoziazione
	$cond = " AND TipoVisibilita='A'"; // vede solo gli stati disponibili a tutti

$comboStato = generaCombo("Stato richiesta","IdStatoRinegoziazione","TitoloStatoRinegoziazione",
			"FROM statorinegoziazione WHERE IdStatoRinegoziazione BETWEEN 2 AND 6 $cond");

?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 500,height: 250,labelWidth:130,
         defaults: {
            width: 340, 
			height: 100
        },
        defaultType: 'textfield',
        items: [<?php echo $comboStato ?>,
        {
			     xtype:'textarea',
                 fieldLabel: 'Nota',
                 name: 'nota'
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