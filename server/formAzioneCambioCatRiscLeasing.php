<?php 
// formAzioneCambiaCategiria
// Genera la struttura del form di tipo 
// "azione cambia categoria" 
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla


// Ottiene la lista delle categorie specificate nei contratti selezionati
$IdsCategorieRiscattoLeasing = fetchValuesArray("SELECT DISTINCT IFNULL(IdCategoriaRiscattoLeasing,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsCategorieRiscattoLeasing)==1) // se è assegnato una sola categoria, nella list box lo deve escludere
{
	$esclude = $IdsCategorieRiscattoLeasing[0];
	if ($esclude == 0)
		$categorieRiscattoLeasing = "nessuna";
	else
		$categorieRiscattoLeasing = getScalar("SELECT CONCAT(CodRiscattoLeasing,' - ',CategoriaRiscattoLeasing) FROM categoriariscattoleasing WHERE IdCategoriaRiscattoLeasing=$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsCategorieRiscattoLeasing)==0)
		$categorieRiscattoLeasing = "nessuna";
	else
		$categorieRiscattoLeasing = "varie";
}

// Genera la combobox per la scelta della cagegoria a cui assegnare
$comboCategorieRiscattoLeasing = generaCombo("Categoria","IdCategoriaRiscattoLeasing","CategoriaRiscattoLeasing",
			"FROM categoriariscattoleasing WHERE IdCategoriaRiscattoLeasing != $esclude ORDER BY CategoriaRiscattoLeasing","","true");

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Categoria attuale', value: '<?php echo addslashes($categorieRiscattoLeasing)?>'}, 
        	<?php echo $comboCategorieRiscattoLeasing?>,
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
						params: { idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>" },
						success: function (frm,action) { saveSuccess(win,frm,action); },
						failure: saveFailure
					});
				}
			}
		}, 		
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});