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
$dataForm=getRow("select * from v_pratiche WHERE IdContratto in (".$idsArray[0].")");
$capitale= number_format($dataForm['ImpDebitoResiduo']+$dataForm['ImpCapitale'],2,',','.');
$residuo= number_format($dataForm['ImpDebitoResiduo']+$dataForm['Importo'],2,',','.');
$speseIncasso = number_format( $dataForm['ImpSpeseRecupero'], 2, ',', '.');
$interessiMora = number_format( $dataForm['ImpInteressiMora'], 2, ',', '.');
$impAltriAdd   = number_format($dataForm['ImpAltriAddebiti'],2,',','.');

?>

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
 
var gridAzioneSpecialeAllegato = new Ext.grid.GridPanel({
    	id: 'gridAllegato',
        width:530,
        height:150,
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
	        width: 510,
			renderer: renderTopic,
            align: 'left',
			sortable: true
        },
		{xtype: 'actioncolumn',
            width: 20,
            header:'Azioni',
            sortable:false,  filterable:false,
            items: [{icon:"images/space.png"},{icon:"images/space.png"},
                    {tooltip: 'Cancella',
						getClass: function(v,meta,rec) {
            				// è possibile eliminare solo l'ultima nota e solo da colui che l'ha emessa 
			 				//if ((rec.get('rowNum')=='1') && (rec.get('idUserCorrente')==rec.get('IdUtente'))) {
				 			if (CONTEXT['IdUtente']==rec.get('IdUtente')) {
			 					return 'del-row';
			 				} else {
			 					return '';
			 				}
			 			},                    	 
            			handler: function(grid, rowIndex, colIndex) {
			 				
	                        //var rec = grid.store.getAt(rowIndex);
			 				var rec = dsAllegato.getAt(rowIndex);
                        	Ext.Ajax.request({
                        		url : 'server/edit_allegati_speciale.php' , 
								params: {task: 'deleteSpeciale', IdAllegato: rec['id'],UrlAllegato: rec.get('UrlAllegato'), IdCompagnia: CONTEXT['idCompagnia'], TitoloTipoAllegato: rec.get('TitoloTipoAllegato')},
                        		method: 'POST',
                        		success: function ( result, request ) {
									eval("res = "+result.responseText);
									if (res.success) Ext.getCmp('gridAllegato').getStore().reload();
									else
	                        			Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 										
                        		},
                        		failure: function ( result, request) { 
                        			Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
                        		} 
                        	});
						}
                    }
            ]   // fine icone di azione su riga
        	}// fine colonna action
     ],        

        // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 10,
            id: 'bbAll',
            store: dsAllegato,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare",
            items:[]
        })
});

// Rendo invisibile il pulsante refresh sulla bbar
gridAzioneSpecialeAllegato.on("afterlayout", function() {
             Ext.getCmp('bbAll').refresh.hideParent = true;
             Ext.getCmp('bbAll').refresh.hide();
});

Ext.getCmp('gridAllegato').show();

/*var RiservatoHidden=true;
	var ValueRiservato=false;
	if (CONTEXT.InternoEsterno == 'I'){
		RiservatoHidden=false;
		ValueRiservato=true;
	}*/

var formPanel = new Ext.form.FormPanel({
    id: "formAzioneSpeciale",
	xtype: "form",
	frame: true, 
	title: "<?php echo $titolo?>",
    width: 545,
    autoHeight: true, 
    labelWidth: 90,
        // defaults: {
        //    width: 340, 
			//height: 100
        //},
        //defaultType: 'textfield',
        items: [{xtype:'container', id:'frmRicPR',
	        items: [
	        {
			   xtype:'container', layout:'column', 
			   items:[
				  {xtype: 'displayfield',height: 20,width: 90, value: "Deb. Residuo", style:'text-align:center;font-size:13px;'},
				  {xtype: 'displayfield',height: 20,width: 90, value: "Capitale", style:'text-align:center;font-size:13px;'},
				  {xtype: 'displayfield',height: 20,width: 100, value: "Interessi di mora", style:'text-align:center;font-size:13px;'},
				  {xtype: 'displayfield',height: 20,width: 110, value: "Spese di recupero", style:'text-align:center;font-size:13px;'},
				  {xtype: 'displayfield',height: 20,width: 120, value: "Imp. altri addebiti", style:'text-align:center;font-size:13px;'}]
			},
			{
				xtype:'container', layout:'column',
				items:[
				   {xtype: 'displayfield',height: 25,width: 90, value: "<b><?php echo $residuo?></b>", style: 'text-align:center;font-size:13px;',name: 'debResiduo'}, 
				   {xtype: 'displayfield',height: 25,width: 90, name:'capitale', value: "<b><?php echo $capitale;?></b>", style:'text-align:center;font-size:13px;'},
				   {xtype: 'displayfield',height: 25,width: 100, value: "<b><?php echo $interessiMora;?></b>", style:'text-align:center;font-size:13px;'},
				   {xtype: 'displayfield',height: 25,width: 110, name:'SpInc', value: "<b><?php echo $speseIncasso;?></b>", style:'text-align:center;font-size:13px;'},
				   {xtype: 'displayfield',height: 25,width: 120, name:'impAltriAdd', value: "<b><?php echo $impAltriAdd?></b>", style:'text-align:center;font-size:13px;'}]//end sub fieldset left column
			},
	        {
				xtype:'panel', layout:'form', labelWidth:90, defaultType:'textarea',
				defaults: {readOnly:false, anchor:'99%'},
				items: [{
				   xtype:'textarea',
	               fieldLabel: 'Nota',
	               width:355,
	               id: 'nota'
	            }]   
	        },
			{
			    xtype:'container', layout:'column',
				items:[
					{
					  xtype:'panel', layout:'form', labelWidth:90, columnWidth: .43, defaultType:'textfield',
					  defaults: {anchor:'96%'},
					  items: [{
							xtype:'numberfield',
		            		allowNegative: false,
		            		minValue :0.01,
		            		allowBlank: false,
		            		decimalPrecision: 2,
		            		decimalSeparator: ',',
 					        fieldLabel:'Primo importo',	
							name:'primoImporto',
						 	id:'primoImporto',	
							style:'text-align:right', 
							width:90,
						 listeners: {
							'change': function(){
							    if(Ext.getCmp('numeroRate').getValue()!='') {
									//var impInsoluto = Ext.getCmp('impInsoluto').getValue();
									var impInsoluto = '<?php echo($residuo);?>';
													
									impInsoluto = impInsoluto.replace('.','');
									impInsoluto = impInsoluto.replace(',','.');
									var primImporto = Ext.getCmp('primoImporto').getValue();
									if (primImporto==''){
									  Ext.Msg.alert("Attenzione","Il primo importo deve essere presente.");
									  Ext.getCmp('numeroRate').setValue('');
									  return;
									} 
									var impTotRate = parseFloat(impInsoluto) - parseFloat(primImporto);
									var numRate = Ext.getCmp('numeroRate').getValue();
									var impRata= (impTotRate/numRate);
									impoRata = Math.round(impRata*100)/100; // arrotondo ai due decimali
									
									var txtImpoRata = impoRata.toString().replace('.',',');
									Ext.getCmp('importoRata').setValue(txtImpoRata);
								}	
							}
						}									
					  }]
				    },
				    {        
						xtype:'panel', layout:'form', labelWidth:90, columnWidth:.17,defaultType:'textfield',
						defaults: {readOnly:true, anchor:'98%'},
						items: [{
							xtype: 'displayfield',
							width: 90
						}]
					},			        	
					{
					  xtype:'panel', layout:'form', labelWidth:110, columnWidth: .40, defaultType:'textfield',
					  defaults: {anchor:'98%'},
					  items: [{
					     xtype: 'datefield',
						 format: 'd/m/Y',
						 allowBlank: false,
						 width: 110,
						 fieldLabel: 'Data pagamento',
						 minValue: new Date(),
						 name: 'dataPagPrimoImporto',
						 id: 'dataPagPrimoImporto'						
					  }]
					}
				]
			},			
	        {
			    xtype:'container', layout:'column',
				items:[
					{
					  xtype:'panel', layout:'form', labelWidth:90, columnWidth: .27,defaultType:'textfield',
					  defaults: {anchor:'94%'},
					  items: [{
							xtype:'numberfield',
		            		allowNegative: false,
		            		minValue :1,
		            		allowBlank: false,
		            		decimalPrecision: 0,
 						 fieldLabel:'N. Rate',	
						 name:'numeroRate',
					 	 id:'numeroRate',	
						 style:'text-align:right', 
						 width:50,
						 listeners: {
							'change': function(){
							    //var impInsoluto = Ext.getCmp('impInsoluto').getValue();
								if(Ext.getCmp('numeroRate').getValue()=='') {
								  Ext.getCmp('decorrenzaRata').setValue('');
								  Ext.getCmp('importoRata').setValue('');
								  return;
								}
								var impInsoluto = '<?php echo($residuo);?>';
								impInsoluto = impInsoluto.replace('.','');
								impInsoluto = impInsoluto.replace(',','.');
								var primImporto = Ext.getCmp('primoImporto').getValue();
								if (primImporto==''){
								  Ext.Msg.alert("Attenzione","Il primo importo deve essere presente.");
								  Ext.getCmp('numeroRate').setValue('');
								  return;
								} 
								var impTotRate = parseFloat(impInsoluto) - parseFloat(primImporto);
								var numRate = Ext.getCmp('numeroRate').getValue();
								var impRata= (impTotRate/numRate);
								impoRata = Math.round(impRata*100)/100; // arrotondo ai due decimali
								
								var txtImpoRata = impoRata.toString().replace('.',',');
								Ext.getCmp('importoRata').setValue(txtImpoRata);
							}
						}						
					  }]
				    },
				    {
					  xtype:'panel', layout:'form', labelWidth:70, columnWidth: .33,defaultType:'textfield',
					  defaults: {anchor:'96%'},
					  items: [{
					     xtype: 'datefield',
						 format: 'd/m/Y',
						 allowBlank: false,
						 width: 110,
						 fieldLabel: 'Decorrenza',
						 minValue: new Date(),
						 name: 'decorrenzaRata',
						 id: 'decorrenzaRata'						
					  }]
				    },			        	
					{
					  xtype:'panel', layout:'form', labelWidth:80, columnWidth: .40,defaultType:'textfield',
					  defaults: {readOnly:true, anchor:'99%'},
					  items: [{
						 fieldLabel:'Importo rata',	
						 name:'importoRata',
					 	 id:'importoRata',	
						 style:'text-align:right', 
						 width:90						
					  }]
				    }
				]
			},			
			{
	        	xtype:'container', layout:'column',
				items:[ 
				    {xtype:'panel', layout:'form', labelWidth:90,defaultType:'textfield',
					defaults: {anchor:'98%'},
					items: [       
					<?php 
						if (!$prevedeConvalida)
						{
							if ($allegatoObbligatorio)
								echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenza', value: 'E\' obbligatorio inserire la documentazione prevista.'},";
						}
						else
						{
							if ($allegatoObbligatorio)
							{
								echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenze', value: 'E\' obbligatorio inserire la documentazione prevista.',width:350},";
								echo "{ xtype: 'displayfield', value: 'Azione soggetta a convalida da parte del mandatario.',width:350},";
							}
							else
								echo "{ xtype: 'displayfield', fieldLabel: 'Avvertenza', value: 'Azione soggetta a convalida da parte del mandatario.',width:350},";
						}
					?>
					]
				 }]
			},	 	
			{xtype: 'displayfield'},
		    gridAzioneSpecialeAllegato],
		}],    
        buttons: ['-',{
           ref: '../addBtn',
		   id: 'bAll',
		   text: 'Nuovo Allegato',
		   //hidden: true,
		   handler: function () {
		      if (Ext.getCmp('primoImporto').getValue()!='' && Ext.getCmp('dataPagPrimoImporto').getValue()!='' && Ext.getCmp('numeroRate').getValue()!='' && Ext.getCmp('decorrenzaRata').getValue()!='')
		      { 
		         eseguiAzioneAllegato();
		      } else {
		          Ext.MessageBox.alert('Attenzione','Devi inserire prima i campi sopra indicati');
			  	} 
		   },
		   tooltip: 'Crea un nuovo allegato',
		   iconCls:'grid-add'
        },{
			text: 'Conferma',
			handler: function() {
				if(Ext.getCmp('gridAllegato').getStore().totalLength >0) {
				  var vectValue = [];
				  obj = {nota : Ext.getCmp('nota').getValue()};
				  obj1 = {primoImporto : Ext.getCmp('primoImporto').getValue()};
				  if(Ext.getCmp('dataPagPrimoImporto').getValue()!='') {
					obj2 = {dataPagPrimoImporto : Ext.getCmp('dataPagPrimoImporto').getValue().format('d/m/Y')};
				  } else {
					  obj2 = {dataPagPrimoImporto : Ext.getCmp('dataPagPrimoImporto').getValue()};
					}
				  obj3 = {numeroRate : Ext.getCmp('numeroRate').getValue()};
				  if(Ext.getCmp('decorrenzaRata').getValue()!='') {
					obj4 = {decorrenzaRata : Ext.getCmp('decorrenzaRata').getValue().format('d/m/Y')};
				  } else {
					  obj4 = {decorrenzaRata : Ext.getCmp('decorrenzaRata').getValue()};
					}
				  obj5 = {importoRata : Ext.getCmp('importoRata').getValue()};
						    
				  vectValue.push(obj);
				  vectValue.push(obj1);
				  vectValue.push(obj2);
				  vectValue.push(obj3);
				  vectValue.push(obj4);
				  vectValue.push(obj5);
				  DCS.showMask();
 			      formPanel.getForm().submit({
					url: 'server/edit_azione.php', method: 'POST',
					params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>", idAzioneSpeciale: idAzione, importoPag: '<?php echo($residuo)?>', storiaRecuperoAllegati: storiaRecuperoAllegati, txtHTML: document.getElementById('frmRicPR').innerHTML	, valuesHtml: Ext.encode(vectValue) },
					success: function (frm,action) {
					  saveSuccess(win,frm,action);
					},
					failure: saveFailure
				  });
				} else {
				    Ext.MessageBox.alert('Operazione non eseguita','Documentazione obbligatoria');
				  }
			}//,scope: this
		},
		{
		   text: 'Annulla',
		   handler: function () {
		        DCS.showMask();
		        if(Ext.getCmp('gridAllegato').getStore().totalLength >0) {
				  var idAll;
				  var total = Ext.getCmp('gridAllegato').getStore().totalLength;
				  for(i=0; i<=(total-1) ; i++) {
				    var rec = Ext.getCmp('gridAllegato').getStore().getAt(i);
				    idAll = rec['id'];
				    urlAll = rec['UrlAllegato'];
				    Ext.Ajax.request({
		               url : 'server/edit_allegati_speciale.php' , 
					   params: {task: 'deleteSpeciale', IdAllegato: idAll, UrlAllegato: urlAll, IdCompagnia: CONTEXT['idCompagnia']},
		               method: 'POST',
		               success: function ( result, request ) {
						   eval("res = "+result.responseText);
						   if(res.success) ; 
						   else { 
						   		DCS.hideMask();
						   		Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 		
						   }								
		               },
		               failure: function ( result, request) { 
				   		   DCS.hideMask();
		                   Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
		               } 
		            });
				  }
				  if(storiaRecuperoAllegati != undefined) {
				    var ArrayStoriaRecuperoAllegati = storiaRecuperoAllegati.split(',');
				    for(i=0; i <= ((ArrayStoriaRecuperoAllegati.length)-1) ; i++) {
				      var idStoriaRecupero = ArrayStoriaRecuperoAllegati[i];
				      Ext.Ajax.request({
			             url : 'server/edit_allegati_speciale.php' , 
						 params: {task: 'annullaStoricoAllegatoSpeciale', IdStoriaRecupero : idStoriaRecupero},
			             method: 'POST',
			             success: function ( result, request ) {
						    eval("res = "+result.responseText);
							if(res.success) ; 
							else {
								DCS.hideMask();
								Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare lo storico dell\'allegato'); 										
							}								
			             },
			             failure: function ( result, request) { 
							DCS.hideMask();
			                Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
			             } 
			          });
				    }
				  }
				  Ext.Ajax.request({
		               url : 'server/edit_allegati_speciale.php' , 
					   params: {task: 'annullaSpeciale', IdAzioneSpeciale : idAzione},
		               method: 'POST',
		               success: function ( result, request ) {
						   eval("res = "+result.responseText);
						   if(res.success) ; 
						   else {
						    DCS.hideMask();
						   	Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 		
						   	}								
		               },
		               failure: function ( result, request) { 
					       DCS.hideMask();
		                   Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
		               } 
		          });
				} else {
				     if(storiaRecuperoAllegati != undefined) {
				       ArrayStoriaRecuperoAllegati = storiaRecuperoAllegati.split(',');
				       for(i=0; i <= ((ArrayStoriaRecuperoAllegati.length)-1) ; i++) {
				         var idStoriaRecupero = ArrayStoriaRecuperoAllegati[i];
				         Ext.Ajax.request({
			               url : 'server/edit_allegati_speciale.php' , 
						   params: {task: 'annullaStoricoAllegatoSpeciale', IdStoriaRecupero : idStoriaRecupero},
			               method: 'POST',
			               success: function ( result, request ) {
							   eval("res = "+result.responseText);
							   if(res.success) ; 
							   else {
							    DCS.hideMask(); 
							   	Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare lo storico dell\'allegato'); 										
							   	}
			               },
			               failure: function ( result, request) { 
							    DCS.hideMask(); 
			                   Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
			               } 
			             });
				       }
				     }
				     if(idAzione != undefined) {
				       Ext.Ajax.request({
			               url : 'server/edit_allegati_speciale.php' , 
						   params: {task: 'annullaSpeciale', IdAzioneSpeciale : idAzione},
			               method: 'POST',
			               success: function ( result, request ) {
							   eval("res = "+result.responseText);
							   if(res.success) ; 
							   else {
							    DCS.hideMask();
							   	Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 										
							   	}
			               },
			               failure: function ( result, request) { 
							    DCS.hideMask();
			                   Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
			               } 
			           });
				     }
				  }
				
				quitForm(formPanel,win);   
				this.enable(); // riabilita tasto Annulla, altrimenti alla risposta "No" al quit-dirty non si può più uscire
		   } 
        }]  // fine array buttons
 });
 
var idAzione;

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
				if (formPanelAllega.getForm().isValid()){
					DCS.showMask();
					//var idAzione;
					formPanelAllega.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: '37', idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {
						  Ext.Ajax.request({
							url: 'server/AjaxRequest.php', 
							params : {task: 'read', sql: "SELECT MAX(IdAllegato) as IdAllegato, UrlAllegato  FROM allegato WHERE lastupd >= (CURdate()) AND IdContratto = "+<?php echo $idcontratti ?>},
							method: 'POST',
							reader:  new Ext.data.JsonReader(
							   {
							      root: 'results',//name of the property that is container for an Array of row objects
							      id: 'idAllegato'//the property within each row object that provides an ID for the record (optional)
							   }),
							   success: function ( result, request ) {
							      eval('var resp = ('+result.responseText+').results[0]');
							      idAllega = resp.IdAllegato;
							      Ext.Ajax.request({
									  url: 'server/AjaxRequest.php', 
									  params : {task:'read', sql: "SELECT MAX(IdStoriaRecupero) AS IdStoriaRecupero FROM storiarecupero sr, allegato al WHERE sr.IdContratto = "+<?php echo $idcontratti ?>+" AND sr.IdUtente = al.IdUtente AND al.IdAllegato ="+idAllega},
									  method: 'POST',
									  reader:  new Ext.data.JsonReader(
									  {
										 root: 'results',//name of the property that is container for an Array of row objects
										 id: 'IdStoriaRecupero'//the property within each row object that provides an ID for the record (optional)
									  }),
									  success: function ( result, request ) {
										 eval('var resp = ('+result.responseText+').results[0]');
										 idStoriaRecupero = resp.IdStoriaRecupero;
										 if(storiaRecuperoAllegati == undefined) {
										   storiaRecuperoAllegati = idStoriaRecupero;
										 } else {
										    storiaRecuperoAllegati = storiaRecuperoAllegati + "," + idStoriaRecupero;
										 }
									  	 DCS.hideMask();
								      },
									  failure: function ( result, request) { 
									  	 DCS.hideMask();
										 Ext.MessageBox.alert('Errore', result.responseText); 
									  },
								  });
							      if(idAzione == undefined) {
							        Ext.getCmp('formAzioneSpeciale').getForm().submit({
										url: 'server/edit_azione.php', method: 'POST',
										params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti) ?>", idallegati: idAllega},
										success: function (frm,action) {
										Ext.Ajax.request({
											url: 'server/AjaxRequest.php', 
											params : {task:'read', sql: "SELECT MAX(IdAzioneSpeciale) AS idAzione FROM azionespeciale WHERE IdContratto = "+<?php echo $idcontratti ?>},
											method: 'POST',
											reader:  new Ext.data.JsonReader(
											   {
											      root: 'results',//name of the property that is container for an Array of row objects
											      id: 'idAzioneSpeciale'//the property within each row object that provides an ID for the record (optional)
											   }),
											   success: function ( result, request ) {
											      eval('var resp = ('+result.responseText+').results[0]');
											      idAzione = resp.idAzione;
											      var select =  "SELECT a.*, date(a.lastupd) as Data, CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato" 
											               +" FROM allegato a, allegatoazionespeciale aas" 
											               +" WHERE a.IdContratto = "+ <?php echo $idcontratti ?> +" AND a.IdAllegato = aas.IdAllegato AND aas.IdAzioneSpeciale='"+ idAzione +"'";
											      dsAllegato.load({params:{sql:select, start:0, limit:10}});
											      saveSuccess(winAllega,frm,action);              
											   },
											   failure: function ( result, request) { 
												  	DCS.hideMask();
											    	Ext.MessageBox.alert('Errore', result.responseText); 
											   },
										 });	   
										 //saveSuccess(win,frm,action);
										},
										failure: saveFailure
									  });
							      } else {
							         Ext.Ajax.request({
				                        	url : 'server/edit_allegati_speciale.php' , 
											params: {task: 'insertSpeciale', IdAllegato: idAllega, IdAzioneSpeciale: idAzione},
				                        	method: 'POST',
				                        	success: function ( result, request ) {
												//eval("res = "+result.responseText);
												var select =  "SELECT a.*, date(a.lastupd) as Data, CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato" 
											               +" FROM allegato a, allegatoazionespeciale aas" 
											               +" WHERE a.IdContratto = "+ <?php echo $idcontratti ?> +" AND a.IdAllegato = aas.IdAllegato AND aas.IdAzioneSpeciale='"+ idAzione +"'";
											    dsAllegato.load({params:{sql:select, start:0, limit:10}});
											    saveSuccess(winAllega,frm,action);   										
				                        	},
				                        	failure: function ( result, request) { 
				                        		DCS.hideMask();
				                        		Ext.MessageBox.alert('Inserimento non eseguito', result.responseText); 
				                        	} 
				                      });
							        }
							      
							   },
							   failure: function ( result, request) { 
							  	 	DCS.hideMask();
							    	Ext.MessageBox.alert('Errore', result.responseText); 
							   },
					      });
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