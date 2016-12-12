<?php 
// formAzioneCambiaClasse
// Genera la struttura del form di tipo 
// "azione cambia classificazione solo per le classificazione con flagManuale = M"
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla

// Ottiene la lista delle classi specificate nei contratti selezionati
$IdsClassi = getColumn("SELECT DISTINCT IFNULL(IdClasse,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsClassi)==1) // se è assegnata una solo classe, nella list box la deve escludere
{
	$esclude = $IdsClassi[0];
	if ($esclude == 0)
		$classi = "nessuna";
	else
		$classi = getScalar("SELECT TitoloClasse FROM classificazione WHERE IdClasse=0$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsClassi)==0)
		$classi = "nessuna";
	else
		$classi = "varie";
}

// Genera la combobox per la scelta della classe a cui assegnare
$comboClasse = generaCombo("Classificazione","IdClasse","TitoloClasse",
		"FROM classificazione WHERE FlagManuale IN ('M','B') AND IdClasse!=$esclude"
		." AND NOW() BETWEEN DataIni AND DataFin ORDER BY TitoloClasse","",false); 
?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 420,height: 220,labelWidth:100,
         defaults: {
            width: 300
        },
        defaultType: 'textfield',
        items: [     
        {xtype:'displayfield', height: 25, fieldLabel: 'Classe attuale', value: '<?php echo addslashes($classi)?>'},
        <?php echo $comboClasse?>,
         {xtype:'textarea',height:100,fieldLabel: 'Nota',name: 'nota'}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				DCS.showMask();
				// qualche campo modificato
				formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
					success: function (frm,action) {saveSuccess(win,frm,action);},
					failure: saveFailure
				});
			}
		}, 	
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});