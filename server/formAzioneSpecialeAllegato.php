<?php 
// formAzioneSpecialeAllegato
// Genera la struttura del form di tipo "azione speciale allegato"
// Contenuto: Solo campo note e pulsanti Allega/ Conferma / Annulla
//trace("Sono entrato in formAzioneSpecialeAllegato",TRUE);
$prevedeConvalida = $azione["FlagSpeciale"]=='Y';
//$allegatoObbligatorio = ($azione["CodAzione"]!='RVC');
$allegatoObbligatorio = ($azione["FlagAllegato"]=='Y');
$comboModel = generaCombo("Tipo Documento","IdTipoAllegato","TitoloTipoAllegato",
			"FROM tipoallegato WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY TitoloTipoAllegato");
$idstatorecupero= getScalar("SELECT IdStatoRecupero FROM contratto WHERE IdContratto='$ids'");

$ggEvasione = getScalar("SELECT GiorniEvasione FROM azione where IdAzione=".$azione["IdAzione"]);
if ($ggEvasione>0) // è previsto un prolungamento di affido per questa azione
  	$default = getScalar("SELECT CURDATE() + INTERVAL $ggEvasione DAY");
else
    $default = null;

$nota = "";
$messaggioAvviso = ""; 

// Se la stessa azione con convalida è già stata chiesta su questa pratica e non è stata convalidata, la ripresenta
// indicando all'utente che può modificare la data di scadenza richiesta
$sqlallegati = "";
$IdAzioneSpeciale = "0";
if ($azione["FlagSpeciale"]=='Y') {
	$oldaz = getRow("SELECT * FROM v_azioni_da_convalidare WHERE IdContratto = $ids AND IdAzione={$azione["IdAzione"]}");
	if (is_array($oldaz)) {
		$IdAzioneSpeciale = $oldaz["IdAzioneSpeciale"];
		$nota 	 = $oldaz["Nota"];
		$default = substr($oldaz["DataScadenza"],0,10);
		$utente  = $oldaz["NomeUtente"];
		$quando    = italianDate($oldaz["DataEvento"]);
		$messaggioAvviso = "<b>Esiste gi&agrave;</b> un'analoga richiesta su questa stessa pratica (fatta da <b>$utente</b> il <b>$quando</b>) in attesa di convalida; puoi modificarne il contenuto.";
		$sqlallegati = "SELECT a.*, date(a.lastupd) as Data, CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato" 
					  ." FROM allegato a JOIN allegatoazionespeciale aas ON a.IdAllegato = aas.IdAllegato"
					  ." WHERE aas.IdAzioneSpeciale=$IdAzioneSpeciale";
	}
}
?>

var IdAzioneSpeciale = <?php echo $IdAzioneSpeciale?>; // IdAzioneSpeciale nel caso di riproposta azione gia' pendente
var storiaRecuperoAllegati;

// create the Data Store

var dsAllegato = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read'},//this parameter is passed for any HTTP request
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdAllegato'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'TitoloTipoAllegato'},
					{name: 'IdUtente'},
					{name: 'TitoloAllegato'},
					{name: 'UrlAllegato'},
					{name: 'Riservato'},
					{name: 'LastUser'},
					{name: 'lastSuper'},
					{name: 'Data'}
				]
        ),
		sortInfo:{field: 'Data', direction: "ASC"},
});		

 // pluggable renders
var renderTopic = function (value, p, record){
     return String.format(
        '<b><a href="{1}" target="_blank">{0}</a></b>',
        value, escape(record.data.UrlAllegato));
};

//-----------------------------------------------------------------------
// Elenco degli allegati (in basso nel form)
//-----------------------------------------------------------------------
var gridAzioneSpecialeAllegato = new Ext.grid.GridPanel({
    	id: 'gridAllegato',
        width:450,
        height:100,
        title:'',
        store: dsAllegato,
        trackMouseOver:true,
        disableSelection:true,
        loadMask: true,
        viewConfig: {
			autoFill: true,
			forceFit: false
		},

        // grid columns
        columns:[{
 			id: 'idAllegato',
			header: "",
            dataIndex: 'IdAllegato',
            sortable: false,
	        hidden: true
        },{
 			id: 'topic',
			header: "Titolo allegato",
            dataIndex: 'TitoloAllegato',
	        width: 350,
			renderer: renderTopic,
            align: 'left',
			sortable: true
        },
		{xtype: 'actioncolumn',
            width: 70,
            header:'Azioni',
            sortable:false,  filterable:false,
            items: [{icon:"images/space.png"},{icon:"images/space.png"},
                    {tooltip: 'Cancella',
						getClass: function(v,meta,rec) {
							// rende visibile l'azione di cancellazione
            				// è possibile eliminare solo gli allegati che lo stesso utente ha inserito
				 			if (CONTEXT['IdUtente']==rec.get('IdUtente')) {
			 					return 'del-row';
			 				} else {
			 					return '';
			 				}
			 			},                    	 
            			handler: function(grid, rowIndex, colIndex) 
            				{
			 					var rec = dsAllegato.getAt(rowIndex);
            					allegatiCancellati.push(rec['id']); // conserva l'ID per il save
            					dsAllegato.removeAt(rowIndex);
                        	} 
					}
            ]   // fine icone di azione su riga
        }// fine colonna action
     ]
});


<?php 
if ($sqlallegati>'') {
	echo "dsAllegato.load({params:{sql:\"$sqlallegati\"}}); // legge allegati gia' registrati";
}
?>

Ext.getCmp('gridAllegato').show();

// Fino alla conferma o annullamento, conserva in un vettore gli ID degli allegati modificati (cioè nuovi o cancellati)
var allegatiCancellati = [];
var allegatiInseriti = [];

var formPanel = new Ext.form.FormPanel({
    id: "formAzioneSpeciale",
	xtype: "form",
	frame: true, 
	title: "<?php echo $titolo?>",
    width: 480,
    autoHeight: true, 
    labelWidth:100,
        // defaults: {
        //    width: 340, 
			//height: 100
        //},
        //defaultType: 'textfield',
        items: [{
			xtype:'textarea',
            fieldLabel: 'Nota',
            width:340,
            name: 'nota',
            value: '<?php echo $nota?>'
        },{
		  	  xtype: 'datefield',
			  format: 'd/m/Y',
			  readOnly: (CONTEXT.InternoEsterno == 'E' && <?php echo $prevedeConvalida?"false":"true"?>),
			  width: 100,
			  fieldLabel: 'Data scadenza',
			  value: '<?php echo $default?>',
			  name: 'dataScadenza',
			  id: 'dataScadenza'
		  },
<?php 
	if (!$prevedeConvalida)
	{
		if ($allegatoObbligatorio)
			echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenza', value: 'E\' obbligatorio inserire la documentazione prevista.',width:280},";
	}
	else
	{
		if ($allegatoObbligatorio)
		{
			echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenze', value: 'E\' obbligatorio inserire la documentazione prevista.',width:280},";
			echo "{ xtype: 'displayfield', value: 'Azione soggetta a convalida da parte del mandatario.',width:500},";
		}
		else
			echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenza', value: 'Azione soggetta a convalida da parte del mandatario.',width:280},";
	}
?>
		{xtype: 'displayfield'},
	    gridAzioneSpecialeAllegato],
        buttons: ['-',{
           ref: '../addBtn',
		   id: 'bAll',
		   text: 'Nuovo Allegato',
		   //hidden: true,
		   handler: function () {
		      eseguiAzioneAllegato();
		   },
		   tooltip: 'Crea un nuovo allegato',
		   iconCls:'grid-add'
        },{ //-------------------------------------------------------------
        	// Gesttione tasto Conferma (=save)
        	//-------------------------------------------------------------
			text: 'Conferma',
			handler: function() {
				if(Ext.getCmp('gridAllegato').getStore().totalLength>0) {
				  DCS.showMask();
				  formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>",
							allegatiCancellati: Ext.util.JSON.encode(allegatiCancellati),
							allegatiInseriti:   Ext.util.JSON.encode(allegatiInseriti), 
							datascadenza: '<?php echo $default?>'},
					success: function (frm,action) {
					  saveSuccess(win,frm,action);
					},
					failure: saveFailure
				  });
				} else {
				    Ext.MessageBox.alert('Operazione non eseguita','Documentazione obbligatoria');
				    this.enable();
				  }
			}//,scope: this
		},
		{
		   text: 'Annulla',
		   handler: function () {
		          DCS.showMask();
		          // Al tasto annulla si deve chiamare il server solo se sono stati allegati nuovi documenti
		          // perché in tal caso bisogna cancellare i file, le righe di "allegato" e la storia corrispondente
		          if (allegatiInseriti.length>0) {
		          	  DCS.showMask();
					  Ext.Ajax.request({
			               url : 'server/edit_allegati_speciale.php' , 
						   params: {task: 'annullaSpeciale', allegatiInseriti: Ext.util.JSON.encode(allegatiInseriti)
						   			,IdContratto: <?php echo $ids?>},
		    	           method: 'POST',
		        	       success: function ( result, request ) {
										   eval("res = "+result.responseText);
										   if(res.success) ; 
									  	else {
									  		Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'azione'); 	
									  		}									
					               },
			               failure: function ( result, request) { 
			    		               Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
			            			} 
			          });
				   }				
				   quitForm(formPanel,win);   
		   } 
        }]  // fine array buttons
	,messaggioAvviso: "<?php echo $messaggioAvviso; ?>"  // viene visualizzato dalla eseguiFunzioneBase in workflow.js
 });
 
// Gestione dell'operazione Nuovo allegato
function eseguiAzioneAllegato() 
{
	var formPanelAllega = new Ext.form.FormPanel({
	xtype: "form",
	id: "formAllega",
	closable: false,
	labelWidth: 105, 
	frame: true, 
	fileUpload: true, 
	title: "<?php echo $titolo?>",
    width: 430,height: 320,
         defaults: {
            width: 300
        },
        defaultType: 'textfield',
        items: [   
        <?php echo $comboModel?>  
        ,{
            xtype: 'fileuploadfield',
            fieldLabel: 'Allega Documento',
            name: 'docPath',
            id: 'docPath',
            allowBlank: false,
            buttonText: 'Cerca',
            listeners: {
	            'fileselected': function(){
	                var valueTitolo=Ext.getCmp('titolo').getValue();
//	                if (valueTitolo=="")
//	                {
	                	valueTitolo=Ext.getCmp('docPath').getValue();
	                	// Ri-trasforma i caratteri URLEncoded in caratteri normali
	                	valueTitolo=unescape(String(valueTitolo).replace("/\+/g", " ")); 
	                	
	                	// Toglie l'estensione del nome file
	                	if (valueTitolo.lastIndexOf(".")>0)
		    				valueTitolo=valueTitolo.substring(0,(valueTitolo.lastIndexOf(".")));
		    			// Toglie il path
	                	if (valueTitolo.lastIndexOf("\\")>0) 
		    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("\\"));
	                	if (valueTitolo.lastIndexOf("/")>0) 
		    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("/"));
	                	Ext.getCmp('titolo').setValue(valueTitolo);
//	                }
	            }
   	        }
        },{
           	fieldLabel: 'Titolo Documento',
           	allowBlank: false,
           	id: 'titolo',
           	name: 'titolo'
        },{
			xtype: 'textarea',
           	fieldLabel: 'Nota',
           	height: 100,
           	name: 'nota'
        },{
			xtype: 'checkbox',
			boxLabel: '<span style="color:red;"><b>Riservato</b></span>',
			name: 'FlagRiservato',
			hidden: true,
			checked: false
		}],
    	buttons: [{
    	    //console.log();
			text: 'Allega',
			handler: function() {
				// Caricamento di un nuovo allegato
				if (formPanelAllega.getForm().isValid()){
					DCS.showMask();
					formPanelAllega.getForm().submit({
						url: 'server/edit_allegati_speciale.php', method: 'POST',
						params: {task: 'allega', IdContratto: <?php echo $ids?>},
						success: function (frm,action) {
							DCS.hideMask();
							allegatiInseriti.push(action.result.idAllegato); // ricorda l'id per il save
							winAllega.close();
							var sql = " SELECT a.*, date(a.lastupd) as Data FROM allegato a"
							+ " WHERE (IdAllegato IN (" + allegatiInseriti.join(",")+ ")"
							+ " OR    IdAllegato IN (SELECT IdAllegato FROM allegatoazionespeciale WHERE IdAzioneSpeciale=" + IdAzioneSpeciale +")"
							+ ") AND  IdAllegato NOT IN (0" + allegatiCancellati.join(",")+ ")";
							dsAllegato.load(({params:{sql:sql}}));
					    },
						failure: saveFailure
					});
				}
			}
		}, 
		  {text: 'Annulla', handler: function () {quitForm(formPanelAllega,winAllega);} 

		}]  // fine array buttons
     });
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Qualche istante, prego..."});
	myMask.show();

	var winAllega = new Ext.Window({
        id: 'windowAllega',
	    width: formPanelAllega.width+30, height:formPanelAllega.height+30, 
	    minWidth: formPanelAllega.width+30, minHeight: formPanelAllega.height+30,
	    layout: 'fit', 
	    plain:true, 
	    bodyStyle:'padding:5px;',
	    title:  'Allega nuovo documento',
	    constrain: true,
	    modal: true,
		
	    items: [formPanelAllega]
	});	
	winAllega.show();
	myMask.hide();
}