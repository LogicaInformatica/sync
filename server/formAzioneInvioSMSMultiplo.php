<?php 
// formAzioneBase
// Genera la struttura del form di tipo "azione base"
// Contenuto: Solo campo note e pulsanti Conferma / Annulla
require_once("userFunc.php");
require_once("workflowFunc.php");

// funzione chiamata alla select sulla combobox
$listener  = <<<EOT
	function(combo, record, index) {
		Ext.Ajax.request({url: 'server/generaTestoSMS.php',method: 'POST',
                  			params :{IdModello:combo.getValue(), IdContratto:null, defaultSubst:"***"},
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
$height=270;
if (count($idScartati)>0){
$height=400;
$contrattiKO=join(",",$idScartati);
$dataStore = <<<EOT
	var select = "SELECT c.CodContratto, p.NomeCliente," 
	 	+" case when cl.cellulare is not null then 'Superato numero sms'"
       	+" else 'Cellulare mancante'"
       	+" end as Risultato "
		+" FROM contratto c left join  v_pratiche p on c.IdContratto = p.IdContratto "
		+" left join  v_cellulare cl on c.IdCliente = cl.IdCliente "
		+" where  c.IdContratto IN ($contrattiKO)"
		+" ORDER BY p.NomeCliente ASC";

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
				{name: 'NomeCliente'},
				{name: 'Risultato'}
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
		width: 400,	
		autoHeight: true,
		title: 'Lista pratiche alle quali non verr&agrave inviato il messaggio',
		layout: 'fit',
		autoScroll: true,
		items: [{
			xtype: 'grid',
			store: dsPratiche,
			ddText: '{0} Pratiche {1}',
			height: 80,
			width: 380,	
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
				width: 150 
	 	    },{
				dataIndex: 'Risultato',
				header: "Errore",
				groupable: false,
            	sortable: false,
            	menuDisabled: true,
				width: 150 
	 	    }]
	 	}]
	 }	
EOT;
	
}

?>

<?php echo $dataStore;
$contratti=json_decode($idcontratti);
$contrattiStr = join(",",$contratti );
$chkHidden = false;
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
	frame: true, title: "<?php echo $titolo?>",
    width: 420,height: <?php echo $height?>,labelWidth:100,
         defaults: {
            width: 300
        },
        defaultType: 'textfield',
        items: [
        <?php echo $comboModel ?>
  		,{
			xtype:'textarea',
            fieldLabel: 'Testo',
            maxLength: 700,
			height: 100,
            id: 'nota',
            name: 'nota',
            readOnly: true
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
		}
		
		
		
		
		
		
		
		
		,<?php echo $gridScarti?>   
	 	],
    buttons: [{
			text: 'Invio',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>", InvioMultiplo: 'TRUE'},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}//,scope: this
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});