<?php 
// formAzioneStampaLettera
// Genera la struttura del form di tipo "azione stampa lettera"

require_once("userFunc.php");
require_once("workflowFunc.php");

$contratti=json_decode($idcontratti);
$numContratti= count($contratti);
$contrattiStr = join(",",$contratti );
$count = count($contratti);

$url = "server/generaTestoLettera.php?IdModello=%MODELLO%&IdContratto=$contrattiStr";
/*
$ids = "";
foreach ($contratti as $contratto) {
	$ids .= ",$contratto";
}
$url .= substr($ids,1);
*/
$listener  = <<<EOT
	function(combo, record, index) {
		url = '$url'.replace(/%MODELLO%/g,combo.getValue());
		Ext.getCmp('lettera').setValue(record.get('TitoloModello'));
		Ext.getCmp('pnllinks').body.update('<a target="_blank" href="'+url+'">'+record.get('TitoloModello')+'.doc</a>');
	}
EOT;

$add = (userCanDo("READ_RISERVATO"))?"":" AND IFNULL(FlagRiservato,'N')='N'";

//debug
$modToExtract=Array();
$modToEvaluate = getFetchArray("select IdModello,condizione from modello WHERE TipoModello='H' AND CURDATE() BETWEEN DataIni AND DataFin");
for($j=0;$j<count($modToEvaluate);$j++)
{
	if($modToEvaluate[$j]['condizione']!='' && $modToEvaluate[$j]['condizione']!=NULL)
	{
		//query di verifica su condizione
		trace("select count(*) from v_pratiche where idcontratto in($contrattiStr) and ".$modToEvaluate[$j]['condizione'].";",false);
		$testNum = getScalar("select count(*) from v_pratiche where idcontratto in($contrattiStr) and ".$modToEvaluate[$j]['condizione'].";");
		if($testNum==$numContratti)
			$modToExtract[]=$modToEvaluate[$j]['IdModello'];
	}else{
		$modToExtract[]=$modToEvaluate[$j]['IdModello'];
	}
}
$modToExtractStr=join(",",$modToExtract);

//$comboModel = generaCombo("Scegli modello","IdModello","TitoloModello",
//    "FROM modello WHERE TipoModello='H' AND (IdReparto IS NULL OR IdReparto=".$context['IdReparto'].") $add AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY TitoloModello",$listener);
$comboModel = generaCombo("Scegli modello","IdModello","TitoloModello", "from modello where idmodello in($modToExtractStr)",
		$listener);
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo

$chkHidden = false;
if(rowExistsInTable("nota","IdContratto in ($contrattiStr) and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;
	

if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
{
	$dataLimite = getScalar("SELECT MIN(DataFineAffido) FROM contratto WHERE IdContratto in ($contrattiStr)");
	if ($dataLimite==NULL)
		$dataLimite = '2999-12-31';
	else
		$dataLimite = ISODate($dataLimite);
}	
else
	$dataLimite = '2999-12-31';
	?>

var url = "";

var sql = "";

var readOnly = (CONTEXT.InternoEsterno=='E');

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	frame: true, title: "<?php echo $titolo?>",
    width: 450, height:200, labelWidth:100,
     defaults: {xtype:'textfield',width: 300},
        items: [     
        <?php echo $comboModel?>,
		{
			xtype:'panel',
            fieldLabel: 'links',
            id: 'pnllinks',
            autoScroll: true,
            hidden: true,
            frame: true,
            height: 50
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
        	xtype: 'textfield',
			name: 'lettera',
            hidden: true,
			id: 'lettera'
	 	},{
		    xtype: 'checkbox',
		    height: 18,
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
					   msg: '<span style="color:red; text-aling:justify;"><b>Selezionando questa voce saranno sostituite tutte le scadenze gi&agrave; inserite per questa pratica.</b></span>',
					   buttons: Ext.Msg.OK,
					   icon: Ext.MessageBox.WARNING
					});	
						
				  }
	 		    }
	 		}    
		}
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				if (formPanel.getForm().isValid()) {
					DCS.showMask();
					window.open(url,"Lettere",'');

					// qualche campo modificato
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', 
						method: 'POST',
						params: {
							idstatoazione: <?php echo $idstatoazione?>,
							idcontratti: "<?php echo addslashes($idcontratti)?>"
						},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}		
			}
		}, 
		{text: 'Annulla',handler: function () {win.close();} 

		}]  // fine array buttons
});