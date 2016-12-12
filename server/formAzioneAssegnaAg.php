<?php 
// formAzioneAssegnaAg
// Genera la struttura del form di tipo "azione assegna ad operatore di agenzia"
// Incluso dinamicamente in: generaFormAzione.php

// Ottiene la lista delle agenzie a cui sono assegnate le pratiche selezionate: deve essere una ed una sola
$IdsAgenzie = fetchValuesArray("SELECT DISTINCT IFNULL(IdAgenzia,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsAgenzie)==1) 
	$IdAgenzia =  $IdsAgenzie[0];
else
{
	$msg = count($IdsAgenzie)==0?"Assegnazione non possibile: la pratica non &egrave; in affido"
	                            :"Assegnazione multipla non ammessa: le pratica non sono tutte affidate ad una stessa agenzia";
?>
	var formPanel = new Ext.BoxComponent({autoEl:{html:"$msg"},width:500});
<?php
	die();
}

// Ottiene la lista degli operatori specificati nei contratti selezionati
$IdsOperatori = fetchValuesArray("SELECT DISTINCT IFNULL(IdAgente,0) FROM contratto WHERE IdContratto IN ($ids)");
if (count($IdsOperatori)==1) // se è assegnato un solo operatore, nella list box lo deve escludere
{
	$esclude = $IdsOperatori[0];
	if ($esclude == 0)
		$operatori = "nessuno";
	else
		$operatori = getScalar("SELECT NomeUtente FROM utente WHERE IdUtente=0$esclude");
}
else
{
	$esclude = "0";
	if (count($IdsOperatori)==0)
		$operatori = "nessuno";
	else
		$operatori = "vari";
}
	
// Genera la combobox per la scelta dell'operatore a cui assegnare
$comboUtenti = generaCombo("Operatore","IdUtente","NomeUtente",
        			"FROM utente u WHERE u.IdReparto = $IdAgenzia"
				   ." AND u.IdUtente != $esclude"
				   ." AND NOW() BETWEEN u.DataIni AND u.DataFin "
				   ." ORDER BY u.NomeUtente");
?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
     width: 420,height: 220,labelWidth:100,
         defaults: {width: 300},
        items: [
        	{xtype:'displayfield', fieldLabel: 'Op. attuale', value: '<?php echo addslashes($operatori)?>'}, 
        	<?php echo $comboUtenti?>,
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