<?php 
// formAzioneBaseLeg
// Genera la struttura del form di tipo "azione base azioni legali"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla

$prevedeConvalida = $azione["FlagSpeciale"]=='Y'?'true':'false';
$dataLimite = '9999-12-31';
switch ($azione["CodAzione"])
{
	case 'PCC':
		$codcontratto = getScalar("select codcontratto from contratto where IDContratto = $ids");
		//Controllo se il contratto e leasing si applicano 120gg altrimenti 90gg
		if(substr($codcontratto,0,2)=="LE") {
		  $default = getScalar("SELECT CURDATE() + INTERVAL 120 DAY");	
		} else {
			$ggEvasione = getScalar("SELECT GiorniEvasione FROM azione where IdAzione=".$azione["IdAzione"]);
            $default = getScalar("SELECT CURDATE() + INTERVAL $ggEvasione DAY");
		  }
		break;
	default:
		$ggEvasione = getScalar("SELECT GiorniEvasione FROM azione where IdAzione=".$azione["IdAzione"]);
        $default = getScalar("SELECT CURDATE() + INTERVAL $ggEvasione DAY");
		break;	
}
$nota = "";
$messaggioAvviso = ""; 

// Se la stessa azione con convalida è già stata chiesta su questa pratica e non è stata convalidata, la ripresenta
// indicando all'utente che può modificare la data di scadenza richiesta
if ($azione["FlagSpeciale"]=='Y') {
	$oldaz = getRow("SELECT * FROM v_azioni_da_convalidare WHERE IdContratto = $ids AND IdAzione={$azione["IdAzione"]}");
	if (is_array($oldaz)) {
		$nota 	 = $oldaz["Nota"];
		$default = substr($oldaz["DataScadenza"],0,10);
		$utente  = $oldaz["NomeUtente"];
		$quando    = italianDate($oldaz["DataEvento"]);
		$messaggioAvviso = "<b>Esiste gi&agrave;</b> un'analoga richiesta su questa stessa pratica (fatta da <b>$utente</b> il <b>$quando</b>) in attesa di convalida; puoi modificarne il contenuto.";
	}
}
?>		

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 480,height: 230,labelWidth:100,
    defaults: {
            width: 340, 
			height: 100
        },
    defaultType: 'textfield',
    items: [
          {
			  xtype:'textarea',
              fieldLabel: 'Nota',
              name: 'nota',
              value: '<?php echo $nota?>'
          },{
		  	  xtype: 'datefield',
			  format: 'd/m/Y',
			  // utenti esterni possono modificare data se è prevista convalida
			  readOnly: (CONTEXT.InternoEsterno == 'E' && !<?php echo $prevedeConvalida?>),
			  width: 100,
			  fieldLabel: 'Data scadenza',
			  value: '<?php echo $default?>',
			  minValue: new Date(),
			  maxValue:'<?php echo italianDate($dataLimite) ?>',
			  name: 'dataScadenza',
			  id: 'dataScadenza'
		  },{
            xtype: 'displayfield', 
	        value: 'NB: Azione soggetta a convalida da parte del mandatario',
	        width: 500,
	        hidden: !<?php echo $prevedeConvalida?>,
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
	,messaggioAvviso: "<?php echo $messaggioAvviso; ?>"  // viene visualizzato dalla eseguiFunzioneBase in workflow.js
		
});