<?php 
// formAzioneBase
// Genera la struttura del form di tipo "azione base"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla
require_once("userFunc.php");
require_once("workflowFunc.php");
//print_r ($codici);
//$row è definito nella GeneraFormAzione.php solo per l'invio x un singolo contratto
$IdCliente=$row["IdCliente"];

$strNumTel = getScalar("SELECT Cellulare FROM v_cellulare WHERE IdCliente=$IdCliente");
//$arrTel = split(",",$strNumTel);
//$numTel=trim($arrTel[0]);
//$numTel = preg_replace( '/[^0-9]/i', '', $numTel);
$arrayTel=explode(',', $strNumTel);

for ($i = 0; $i < count($arrayTel); $i++)
{
    $numTel = $arrayTel[$i];
   	$numTel = ctrlNumeroCellulare($numTel);
	//$numTel = preg_replace( '/[^0-9]/i', '', $numTel);
    $arrayTel[$i] = $numTel;
}
$numTel=implode(',', $arrayTel);
// funzione chiamata alla select sulla combobox
$listener  = <<<EOT
	function(combo, record, index) {
		Ext.Ajax.request({url: 'server/generaTestoSMS.php',method: 'POST',
                  			params :{IdModello:combo.getValue(), IdContratto:$ids, defaultSubst:""},
                  			success: function (result, request) {
                  				Ext.getCmp('nota').setValue(result.responseText);
               				},
                  			failure: function (result,request) {
								Ext.Msg.alert ("Invio SMS fallito",result.responseText);
               				}
       					});
	}
EOT;
$add = (userCanDo("READ_RISERVATO"))?"":" AND IFNULL(FlagRiservato,'N')='N'";
$comboModel = generaCombo("Scegli modello","IdModello","TitoloModello",
	"FROM modello WHERE TipoModello='S' $add AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY TitoloModello",$listener,"true");
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo

$contratti=json_decode($idcontratti);
$contrattiStr = join(",",$contratti );
$chkHidden = false;
if(rowExistsInTable("nota","IdContratto in (".$contrattiStr.") and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;

// inizio controllo numero sms inviati	
$sql = "select count(sr.IdContratto) from storiarecupero sr left join utente u on sr.IdUtente=u.IdUtente"
	 	." left join reparto re on u.IdReparto=re.IdReparto"	
		." left join azione az on az.IdAzione=sr.IdAzione"
		." where az.CodAzione='SMS'"
		." and re.IdReparto = (Select IdReparto from utente where IdUtente = ".$context['IdUtente'].")"
		." and IdContratto =$contrattiStr";
				
$sql1= "select MaxSmsContratto from reparto where IdReparto = (Select IdReparto from utente where IdUtente =".$context['IdUtente'].")";		

$NumSmsInviati = getscalar($sql);
$MaxSms = getscalar($sql1);
//trace("max:$MaxSms inviati:$NumSmsInviati");
$heightWin=300;
$hdnBtn=false;
$hdnSms=true;

if(!($MaxSms==""))
{
	if($NumSmsInviati >=$MaxSms)
	{
		
		$heightWin=310;
		$hdnSms=false;
		$hdnBtn = true;
	}
}
// fine controllo numero sms inviati

if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
{
	$dataLimite = getScalar("SELECT MIN(DataFineAffido) FROM contratto WHERE IdContratto in ($contrattiStr)");
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
    width: 420,height: <?php echo $heightWin?>,labelWidth:100,
         defaults: {
            width: 300, 
			height: 100
        },
        defaultType: 'textfield',
        items: [
        	<?php echo $comboModel?>
  		,{
         	fieldLabel: 'Cellulare',
         	height: 20,
         	value: "<?php echo $numTel?>",
        	allowBlank: false,
        	name: 'Cellulare',
        	vtype: 'cell_list'
        },{
			xtype:'textarea',
            fieldLabel: 'Testo',
            maxLength: 700,
            id: 'nota',
            name: 'nota',
            readOnly:(CONTEXT.InternoEsterno=='E')
        },{	
        	xtype: 'datefield',
			format: 'd/m/Y',
			width: 100,
			fieldLabel: 'Data verifica',
			value: '<?php echo $dataDefault?>',
			name: 'data',
			minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
			id: 'data'
		},{
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
		},{
         	xtype: 'label',
			html : '<span style="color:red;"><b align="justify">Impossibile inviare il messaggio, numero massimo di sms superato.</b></span>', 
			width: 300,
			height: 15,
			hidden:'<?php echo $hdnSms ?>',
			disabled:'<?php echo $hdnSms ?>'
		   }
	],
    buttons: [{
			text: 'Invio',
			disabled:'<?php echo $hdnBtn ?>',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}//,scope: this
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});