<?php 
// formAzioneCambiaCategiria
// Genera la struttura del form di tipo 
// "azione cambia categoria" 
// Contenuto: listbox, campo note e pulsanti Conferma / Annulla


// Ottiene la lista delle categorie specificate nei contratti selezionati
$IdsCategorieMaxirata = fetchValuesArray("SELECT DISTINCT IFNULL(IdCategoriaMaxirata,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsCategorieMaxirata)==1) // se è assegnato una sola categoria, nella list box lo deve escludere
{
	$esclude = $IdsCategorieMaxirata[0];
	if ($esclude == 0)
		$categorieMaxirata = "nessuna";
	else
		$categorieMaxirata = getScalar("SELECT CONCAT(CodMaxirata,' - ',CategoriaMaxirata) FROM categoriamaxirata WHERE IdCategoriaMaxirata=$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsCategorieMaxirata)==0)
		$categorieMaxirata = "nessuna";
	else
		$categorieMaxirata = "varie";
}

// Genera la combobox per la scelta della cagegoria a cui assegnare
$comboCategorieMaxirata = generaCombo("Categoria","IdCategoriaMaxirata","CategoriaMaxirata",
			"FROM categoriamaxirata WHERE IdCategoriaMaxirata != $esclude ORDER BY CategoriaMaxirata","","true");

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Categoria attuale', value: '<?php echo addslashes($categorieMaxirata)?>'}, 
        	<?php echo $comboCategorieMaxirata?>,
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