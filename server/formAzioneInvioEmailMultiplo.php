<?php 
// formAzioneInvioEmail
// Genera la struttura del form di tipo "azione invio email"
// Contenuto: campo/listbox email destinatario, listbox modello di email, campo oggetto email, campo note (testo email) e pulsanti Conferma / Annulla
//if(!preg_match( '/^[\w\.\-]+@\w+[\w\.\-]*?\.\w{1,4}$/', $indirizzoEmail))
//{
//	$indirizzoEmail = "sbagliato";
//}
require_once("userFunc.php");
require_once("workflowFunc.php");
//print_r ($codici);
// funzione chiamata alla select sulla combobox

$listener  = <<<EOT
	function(combo, record, index) {
		Ext.Ajax.request({url: 'server/generaTestoEmail.php',method: 'POST',
                  			params :{IdModello:combo.getValue(), IdContratto:null, defaultSubst:"***"},
                  			success: function (result, request) {
                  				var resp = JSON.parse(result.responseText);
                  				Ext.getCmp('oggetto').setValue(resp.subject);
                  				Ext.getCmp('nota').setValue(resp.body);
               				},
                  			failure: function (result,request) {
								Ext.Msg.alert ("Invio email fallito",result.responseText);
               				}
       					});
	}
EOT;
$add = (userCanDo("READ_RISERVATO"))?"":" AND IFNULL(FlagRiservato,'N')='N'";
$comboModel = generaCombo("Scegli modello","IdModello","TitoloModello",
	"FROM modello WHERE TipoModello='E' $add AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY TitoloModello",$listener,"true");
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
$height=400;
if (count($idScartati)>0){
$height=480;	
$contratti = join(",", $idScartati);

$dataStore = <<<EOT
	var select = "SELECT c.CodContratto, p.NomeCliente "
		+" FROM contratto c, v_pratiche p "
		+" WHERE c.IdContratto=p.IdContratto "
		+" AND c.IdContratto IN ($contratti)"
		+" ORDER BY p.NomeCliente ASC ";

	var dsPratiche = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
//where to retrieve data
		url: 'server/AjaxRequest.php',
		method: 'POST'
		}),   
		baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
	
		reader: new Ext.data.JsonReader(
			{
				root: 'results',//name of the property that is container for an Array of row objects
				id: 'IdCodContratto'//the property within each row object that provides an ID for the record (optional)
			},
			[
				{name: 'CodContratto'},
				{name: 'NomeCliente'}
			]
	    )//,
		//sortInfo:{field: 'NomeCliente', direction: "ASC"}
	});   

// trigger the data store load
	dsPratiche.load({params:{start:0, limit:10}});
EOT;
$gridScarti = <<<EOT
	{
		xtype: 'panel',
		width: 620,	
		autoHeight: true,
		title: 'Lista pratiche con indirizzo e-mail non attribuito o non valido',
		layout: 'fit',
		autoScroll: true,
		items: [{
			xtype: 'grid',
			store: dsPratiche,
			height: 80,
			width: 600,	
			columns: [{
				dataIndex: 'CodContratto',
				header: 'Pratica',
				groupable: false,
            	sortable: false,
            	menuDisabled: true,
				width: 100 
			},{
				dataIndex: 'NomeCliente',
				header: "Cliente",
				groupable: false,
            	sortable: false,
            	menuDisabled: true,
				width: 500 
	 	    }]
	 	}]   
	}
EOT;
}
?>

var readOnly = (CONTEXT.InternoEsterno=='E');

<?php echo $dataStore;

$chkHidden = false;
$contratti=json_decode($idcontratti);
$contrattiStr = join(",",$contratti );
if(rowExistsInTable("nota","IdContratto in (".$contrattiStr.") and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;

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
    width: 640,height: <?php echo $height?>,labelWidth:100,
        defaultType: 'textfield',
        items: [     
		<?php echo $comboModel?>,
		{
         	fieldLabel: 'Oggetto',
 			xtype:'textarea',
         	id: 'oggetto',
        	name: 'oggetto',
            readOnly: true,
            anchor: '97%',
            height: 36
        },{
			xtype:'htmleditor',
            fieldLabel: 'Nota/testo',
            id: 'nota',
            name: 'nota',
            enableAlignments : false, //!readOnly,
			enableColors : false, //!readOnly,
			enableFont : false, //!readOnly,
			enableFontSize : false, //!readOnly,
			enableFormat : false, //!readOnly,
			enableLinks : false, //!readOnly,
			enableLists : false, //!readOnly,
			enableSourceEdit : false, //!readOnly,
            readOnly: true,
            anchor: '97%',
            height: 200
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
        },
		{
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
		},        
		<?php echo $gridScarti?>
        ],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					// qualche campo modificato
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>", InvioMultiplo: 'TRUE'},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});