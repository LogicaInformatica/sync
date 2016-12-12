// Crea namespace DCS
Ext.namespace('DCS');


//Visualizza dettaglio azione speciale
//--------------------------------------------------------
var win;
var formAzioneSpec;
var storiaRecAll;


DCS.showAzioneSpecialeDetail = function(){

	return {
		create: function(IdAzioneSpeciale,IdContratto,Utente,azSpecialeAllegato,isStorico){
			
			var color;
			var lastAllegato;
						
			storiaRecAll = '';
			var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
			if(azSpecialeAllegato) {
			  Ext.Ajax.request({
					  url: 'server/AjaxRequest.php',
					  params: {task: 'read', sql: "SELECT MAX(IdAllegato) as IdAllegato FROM "+schema+".allegatoazionespeciale WHERE IdAzioneSpeciale = " + IdAzioneSpeciale},
					  method: 'POST',
					  reader: new Ext.data.JsonReader({
						 root: 'results',//name of the property that is container for an Array of row objects
						 id: 'IdAllegato'//the property within each row object that provides an ID for the record (optional)
					  }, 
					  [{name: 'IdAllegato'}]),
					  success: function(result, request){
						 eval('var resp = (' + result.responseText + ').results[0]');
						 lastAllegato = resp.IdAllegato;
					  },
					  failure: function(result, request){
						 Ext.MessageBox.alert('Errore', result.responseText);
					  }
			  });			 	
			}
			
			var select = "SELECT * FROM "+schema+".allegato a, allegatoazionespeciale aas WHERE aas.IdAzioneSpeciale ="+ IdAzioneSpeciale + " AND aas.IdAllegato = a.IdAllegato AND FlagCancella='N'";
			
			var dsAllegato = new Ext.data.Store({
					proxy: new Ext.data.HttpProxy({
						//where to retrieve data
						url: 'server/AjaxRequest.php',
						method: 'POST'
					}),   
					baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
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
					sortInfo:{field: 'Data', direction: "ASC"}
			});
			
			 // pluggable renders
			var renderTopic = function (value, p, record){
			     return String.format(
			        '<b><a href="{1}" target="_blank">{0}</a></b>',
			        value, escape(record.data.UrlAllegato));
			};
			
			var gridAzioneSpecialeAllegato = new Ext.grid.GridPanel({
			    	id: 'gridAllegato',
			        width:470,
			        height:100,
			        title:'',
			        store: dsAllegato,
			        trackMouseOver:true,
			        disableSelection:true,
					hidden: azSpecialeAllegato?false:true,
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
				        width: 250,
						renderer: renderTopic,
			            align: 'left',
						sortable: true
			        },
					{xtype: 'actioncolumn',
			            width: 100,
			            header:'Azioni',
			            hidden: isStorico,
			            sortable:false,  filterable:false,
			            hidden: (CONTEXT.AZIONE_ALL!=true),
			            items: [{icon:"images/space.png"},{icon:"images/space.png"},
			                    {tooltip: 'Cancella',
									getClass: function(v,meta,rec) {
			            				// è possibile eliminare il primo allegato dall'utente che lo ha inserito
										if (CONTEXT['IdUtente']==rec.get('IdUtente')) {
						 					return 'del-row';
						 				} else {
						 					return '';
						 				}
						 			},                    	 
			            			handler: function(grid, rowIndex, colIndex) {
						 				
				                        //var rec = grid.store.getAt(rowIndex);
						 				var rec = dsAllegato.getAt(rowIndex);
										if(rec['id']<=lastAllegato) {
										  Ext.Ajax.request({
				                        	  url : 'server/edit_allegati_speciale.php' , 
											  params: {task: 'flagDeleteSpeciale', IdAllegato: rec['id']},
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
										} else {
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
			            items:['-',{ref: '../addBtn',
							  id: 'bAll',
							  text: 'Nuovo Allegato',
							  handler: function () 
							     {
								    eseguiAzioneAllegato(IdAzioneSpeciale,IdContratto);
								 },	
							  tooltip: 'Crea un nuovo allegato',
							  iconCls:'grid-add',
							  hidden: (CONTEXT.AZIONE_ALL!=true || isStorico)
		               }]
			        })
			});
			
			// Rendo invisibile il pulsante refresh sulla bbar
			gridAzioneSpecialeAllegato.on("afterlayout", function() {
			             Ext.getCmp('bbAll').refresh.hideParent = true;
			             Ext.getCmp('bbAll').refresh.hide();
			});
			
			dsAllegato.load();
			
			Ext.Ajax.request({
					url: 'server/AjaxRequest.php', 
					params : {	task: 'read',
								sql: "SELECT vasp.*, CASE WHEN vasp.DataScadenza='0000-00-00' then NULL else vasp.DataScadenza END as DataScadenza,"+ 
								     " (ifnull(vdi.Capitale,0) + ifnull(vp.ImpDebitoResiduo,0)) + ifnull(vp.ImpAltriAddebiti,0) as impTotInsoluto"+
								     " FROM "+schema+".v_praticheAzioniSpeciali vasp"+
									 " left join "+schema+".v_pratiche vp on vp.IdContratto=vasp.IdContratto"+
									 " left join "+schema+".v_dettaglio_insoluto vdi on vdi.IdContratto=vasp.IdContratto"+
									 " where IdAzioneSpeciale="+IdAzioneSpeciale
							},
					method: 'POST',
					success: function ( result, request ) 
					{
						eval('var resp = ('+result.responseText+').results[0]');
						
						switch(resp.Stato)
						{
							case 'W':
								color = "white";
							break
							case 'A':
								color = "#a6f0af";
							break
							case 'R':
								color = "#eda9a9";
							break
							default :
								colore = 'white';
						}
						
						if(resp.ImpSaldoStralcio != null) {
						  var impoSaldoStralcio = resp.ImpSaldoStralcio.replace('.',',');	
						  var impoAbbonato = parseFloat(resp.impTotInsoluto) - parseFloat(resp.ImpSaldoStralcio);
						  impoAbbonato = Math.round(impoAbbonato * 100) / 100;
						  var txtImpoAbbonato = impoAbbonato.toString().replace('.',',');
						  var percAbbuon = (impoAbbonato / parseFloat(resp.impTotInsoluto)) * 100;
						  percAbbuon = Math.round(percAbbuon * 100) / 100; // arrotondo ai due decimali
						  percAbbuon = percAbbuon + ' %';
						  var txtPercAbbuon = percAbbuon.toString().replace('.',',');
						}
						
						if(resp.PrimoImporto!=null) {
						  var primoImporto = resp.PrimoImporto.replace('.',',');
						  var importoRata = resp.ImportoRata.replace('.',',');	
						}
						
						formAzioneSpec = new Ext.form.FormPanel({
							xtype: 'form',
							frame: true, 
							fileUpload: true, 
							title: 'Pratica : ' + resp.CodContratto + ' - ' + resp.NomeCliente,
						    width: 450,
						    height: 425+100*azSpecialeAllegato,
						    labelWidth:120,
							buttonAlign :'center',
						    trackResetOnLoad: true,
							items: [ 
								    	{
											xtype: 'textfield', 
											anchor: '100%',
											hidden: false,
											fieldLabel: 'Stato azione',
											name:'StatoAzione',
											id: 'StatoAzione',
											disabled:false,
											readOnly: true,
											style: 'text-align:left;background:'+color+';',
											value:resp.DescStato
										}
										,
										{
											xtype: 'textfield', 
											anchor: '100%',
											hidden: false,
											name:'TitoloAzione',
											id: 'TitoloAzione',
											disabled:false,
											fieldLabel: 'Azione',
											readOnly: true,
											style: 'text-align:left',
											value:resp.TitoloAzione
										}
										,
										{
											xtype: 'textfield',
											anchor: '100%',
											hidden: false,
											fieldLabel: 'Utente autore',
											name:'UtenteAutore',
											id: 'UtenteAutore',
											disabled:false,
											readOnly: true,
											style: 'text-align:left',
											value: Utente//resp.NominativoUtente
										}
										,
										{
											xtype: 'textfield', 
											anchor: '100%',
											hidden: false,
											fieldLabel: 'Agenzia autore',
											name:'Agenzia',
											id: 'Agenzia',
											disabled:false,
											readOnly: true,
											style: 'text-align:left',
											value:resp.TitoloUfficio
										}
										,
										{	
											xtype: 'textfield',
											//format: 'd/m/Y H:i',
											anchor: '100%',
											autoHeight:true,
											readOnly: true,
											fieldLabel: 'Data evento',
											name: 'DataEvento',
											id: 'DataEvento',
											value:resp.DataEvento
										}
										,
										{	
											xtype: 'datefield',
						                    //format: 'd/m/Y',
											anchor: '48%',
											autoHeight:true,
											hidden: resp.DataScadenza||resp.TitoloAzione=='Richiesta di riaffido'?false:true,
											readOnly: (CONTEXT.InternoEsterno == 'E'),
											fieldLabel: '<b>Data scadenza</b>',
											name: 'DataScadenza',
											id: 'DataScadenza',
											value:resp.DataScadenza
										}
										,
										{
										    xtype:'container', layout:'column', hidden:resp.PrimoImporto?false:true,
											items:[
												{
												  xtype:'panel', layout:'form', labelWidth:120, columnWidth: .45, defaultType:'textfield',
												  defaults: {readOnly:true, anchor:'100%'},
												  items: [{
													 fieldLabel:'Primo importo',	
													 name:'primoImporto',
												 	 id:'primoImporto',	
													 style:'text-align:right', 
													 value: primoImporto
												  }]
											    },
											    {        
													xtype:'panel', layout:'form', labelWidth:90, columnWidth:.15,defaultType:'textfield',
													defaults: {readOnly:true, anchor:'98%'},
													items: [{
														xtype: 'displayfield'
													}]
												},			        	
												{
												  xtype:'panel', layout:'form', labelWidth:105, columnWidth: .40, defaultType:'textfield',
												  defaults: {readOnly:true, anchor:'100%'},
												  items: [{
												     xtype: 'textfield',
													 //format: 'd/m/Y H:i',
													 fieldLabel: 'Data pagamento',
													 name: 'dataPagPrimoImporto',
													 id: 'dataPagPrimoImporto',
													 value: resp.DataPagPrimoImporto						
												  }]
												}
											]
										},			
								        {
										    xtype:'container', layout:'column',hidden:resp.NumeroRate?false:true,
											items:[
												{
												  xtype:'panel', layout:'form', labelWidth:120, columnWidth: .37,defaultType:'textfield',
												  defaults: {readOnly:true, anchor:'96%'},
												  items: [{
													 fieldLabel:'N. Rate',	
													 name:'numeroRate',
												 	 id:'numeroRate',	
													 style:'text-align:right', 
													 value: resp.NumeroRate						
												  }]
											    },
											    {
												  xtype:'panel', layout:'form', labelWidth:65, columnWidth: .33,defaultType:'textfield',
												  defaults: {readOnly:true,anchor:'97%'},
												  items: [{
												     xtype: 'textfield',
													 //format: 'd/m/Y H:i',
													 fieldLabel: 'Decorrenza',
													 name: 'decorrenzaRata',
													 id: 'decorrenzaRata',
													 value: resp.DecorrenzaRate						
												  }]
											    },			        	
												{
												  xtype:'panel', layout:'form', labelWidth:50, columnWidth: .30,defaultType:'textfield',
												  defaults: {readOnly:true, anchor:'100%'},
												  items: [{
													 fieldLabel:'Importo',	
													 name:'importoRata',
												 	 id:'importoRata',	
													 style:'text-align:right', 
													 value: importoRata						
												  }]
											    }
											]
										},
										{
											xtype: 'textfield', 
											anchor: '100%',
											hidden: impoSaldoStralcio?false:true,
											fieldLabel: 'Imp. saldo e stralcio',
											name:'ImportoSS',
											id: 'ImportoSS',
											disabled:false,
											readOnly: true,
											style: 'text-align:left',
											value: impoSaldoStralcio
										},
										{
											xtype: 'container',	layout: 'column',
											items: [{
											  xtype:'panel', layout:'form', labelWidth:120, columnWidth: .55,defaultType:'textfield',
											  defaults: {readOnly:true, anchor:'96%'},
											  items: [{
												xtype: 'textfield',
												anchor: '96%',
												hidden: txtImpoAbbonato?false:true,
												fieldLabel: 'Importo cap. abb.',
												name: 'impAbbuono',
												id: 'impAbbuono',
												disabled: false,
												readOnly: true,
												style: 'text-align:left',
												value: txtImpoAbbonato
											  }]	
											 },{
											  xtype:'panel', layout:'form', labelWidth:90, columnWidth: .45, defaultType:'textfield',
											  defaults: {readOnly:true, anchor:'90%'},
											  items: [{
												xtype: 'textfield',
												anchor: '90%',
												hidden: txtPercAbbuon?false:true,
												fieldLabel: 'Capit. da abb.',
												name: 'percAbbuono',
												id: 'percAbbuono',
												disabled: false,
												readOnly: true,
												style: 'text-align:left',
												value: txtPercAbbuon
											  }]	
											}]
										},
										{	
											xtype: 'textfield',
											//format: 'd/m/Y H:i',
											anchor: '100%',
											hidden: resp.DataSaldoStralcio?false:true,
											autoHeight:true,
											readOnly: true,
											fieldLabel: 'Data saldo e stralcio',
											name: 'DataSS',
											id: 'DataSS',
											value:resp.DataSaldoStralcio
										}
										,
										{
											xtype: 'textfield', 
											anchor: '100%',
											hidden: false,
											fieldLabel: 'Approvatore',
											name:'UtenteApprovatore',
											id: 'UtenteApprovatore',
											disabled:false,
											readOnly: true,
											style: 'text-align:left',
											value:resp.NominativoApprovatore
										}
										,
										{	
											xtype: 'textfield',
											//format: 'd/m/Y H:i',
											anchor: '100%',
											autoHeight:true,
											readOnly: true,
											fieldLabel: 'Data approvazione',
											name: 'DataApprovazione',
											id: 'DataApprovazione',
											value:resp.DataApprovazione
										}
										,
										{
											xtype:'textarea',
						            		fieldLabel: 'Nota',
						            		name: 'Nota',
						            		height: 60,
						            		anchor: '100%',
						            		value:resp.Nota
						        		}, gridAzioneSpecialeAllegato
							        ],
						    buttons: 
						    	[{
									text: 'Vedi contratto',
									handler: function() {
										showPraticaDetail(IdContratto,'','','','',null,-1);
									},
									scope: this
								},'->',
								  {
									  text: 'Stampa lettera',
									  hidden:(resp.PrimoImporto && resp.Stato=='A' || isStorico)?false:true,
									  handler: function(){
									  	window.open('server/generaStampaComunicazioniSSDIL.php?TitoloModello=Comunicazione%20Piano%20di%20Rientro&IdContratto='+IdContratto+'','_parent','');
									  }	
								  },
								  {
								  text: 'Rate piano',
								  hidden:resp.PrimoImporto?false:true,
								  handler : function () {
								  	visualizzaRate(IdContratto);
								  }
								  },
								  {
								  text: 'Convalida',
								  hidden : (resp.Stato!='W' || CONTEXT.InternoEsterno == 'E' || !CONTEXT.AZIONI || isStorico),
								  handler : function () {submitAzioneSpeciale('A');}
								  }
								  ,
								  {
								   text: 'Respingi',
								   hidden : (resp.Stato!='W' || CONTEXT.InternoEsterno == 'E' || !CONTEXT.AZIONI || isStorico),
								   handler: function () {submitAzioneSpeciale('R');}
								  }
								  ,
								  /*{'-',
								   ref: '../addBtn',
								   id: 'bAll',
								   text: 'Nuovo Allegato',
								   hidden: (resp.TitoloAzione!='Fallimento'),
								   handler: function(){
										 eseguiAzioneAllegato(IdAzioneSpeciale,IdContratto);
								   },
								   tooltip: 'Crea un nuovo allegato',
							       iconCls:'grid-add'
								  } 
								  ,*/
						    	  {
								   text: 'Salva',
								   hidden : !CONTEXT.AZIONI || isStorico || resp.Stato=='C' ,
								   handler:  function () {
									   	 if(azSpecialeAllegato) {
										   if(Ext.getCmp('gridAllegato').getStore().totalLength == 0) {
										   	 Ext.MessageBox.alert('Operazione non eseguita','Documentazione obbligatoria');
										   } else {
										   	   Ext.Ajax.request({
										           url : 'server/AjaxRequest.php' , 
												   params: {task: 'read', sql:"SELECT aas.IdAllegato, a.UrlAllegato FROM allegatoazionespeciale aas, allegato a WHERE aas.IdAllegato = a.IdAllegato AND aas.FlagCancella='Y'"},
										           method: 'POST',
										           success: function ( result, request ) {
												   	   eval ("rec ="+result.responseText);
													   total = rec['total'];
													   for(i=0;i<=(total-1);i++) {
													   	 idAll = rec.results[i].IdAllegato;
														 urlAll = rec.results[i].UrlAllegato;
														 Ext.Ajax.request({
												              url : 'server/edit_allegati_speciale.php' , 
															  params: {task: 'deleteSpeciale', IdAllegato: idAll, UrlAllegato:urlAll},
												              method: 'POST',
												              success: function ( result, request ) {
																   eval("res = "+result.responseText);
																   if(res.success) ; 
																   else Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 										
												              },
												              failure: function ( result, request) { 
												                   Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
												              } 
												          });	 
													   }
												   },
										           failure: function ( result, request) { 
										               Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
										           } 
										       });		
										   	   submitAzioneSpeciale(resp.Stato);
										     }	
										 } else {
										 	 submitAzioneSpeciale(resp.Stato);
										   } 
								   	 }
								  }
								  , 
								  {
								   text: 'Annulla',
								   handler: function () {
									  quitForm(formAzioneSpec,win);
								   } 
								  }
							   ]  
						});
						
					    win = new Ext.Window({
							layout: 'fit',
							width: 500,
						    height: 420 + 100* azSpecialeAllegato,
							modal: true,
							title: 'Dettaglio azione',
							closable: azSpecialeAllegato?false:true,
									tools: [helpTool("DettaglioAzioneSpeciale")],
						    //constrain: true,
							flex: 1,
							items: [formAzioneSpec]
						});
						win.show();
						
					    function submitAzioneSpeciale(newState)
					    {
					    	if (formAzioneSpec.getForm().isValid())
					    	{
					    		formAzioneSpec.getForm().submit(
					    		{
					    		  url: 'server/praticheAzioniSpeciali.php', method: 'POST',
					    		  params: {task:"update",idAzioneSpeciale:resp.IdAzioneSpeciale, statoDopo : newState, statoPrima:resp.Stato},
					    		  success: function (frm,action) 
					    		  {
					    			if (resp.Stato != newState)
					    			{
					    			  var gridDopo= "GridPraticheAzioniSpeciali" +resp.Stato;
					    			  if (Ext.getCmp(gridDopo) != undefined)
					    				Ext.getCmp(gridDopo).getStore().reload();
					    			}
					    												
				    				var gridPrima = "GridPraticheAzioniSpeciali" + newState;
				    				if (Ext.getCmp(gridPrima) != undefined)
				    				  Ext.getCmp(gridPrima).getStore().reload();
					    			saveSuccess(win,frm,action);
					    		  },
					    		  failure: saveFailure
					    		});
					    	}	
					    };  
					},
					failure: function ( result, request) { 
						Ext.MessageBox.alert('Errore', result.responseText); 
					},
					autoLoad: true
				});
		  return true;
		}
	};
	
	function eseguiAzioneAllegato(idAzione,idContratto) { 
		
		/*var RiservatoHidden=true;
		var ValueRiservato=false;
		if (CONTEXT.InternoEsterno == 'I'){
			RiservatoHidden=false;
			ValueRiservato=true;
		}*/
		
		var formPanelAllega = new Ext.form.FormPanel({
			xtype: "form",
			id: "formAllega",
			closable: false,
			labelWidth: 105, 
			frame: true, 
			fileUpload: true, 
			title: '',
		    width: 430,
			height: 320,
	        defaults: {
	            width: 300
	        },
	        defaultType: 'textfield',
	        items: [
			{
			   xtype: 'combo',
			   fieldLabel: 'Tipo documento',
			   hiddenName: 'IdTipoAllegato',
			   anchor: '97%',
			   editable: false,
			   hidden: false,
			   typeAhead: false,triggerAction: 'all',
			   lazyRender: true,
			   allowBlank: false,
			   store: {xtype:'store',
					proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
					baseParams:{task: 'read', sql: "SELECT IdTipoAllegato, TitoloTipoAllegato FROM tipoallegato WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY TitoloTipoAllegato"},
					reader:  new Ext.data.JsonReader(
								{root: 'results', id: 'IdTipoAllegato'},
								[{name: 'IdTipoAllegato'},{name: 'TitoloTipoAllegato'}]
		            			),
								
					sortInfo:{field: 'TitoloTipoAllegato', direction: "ASC"}
			   },
			   displayField: 'TitoloTipoAllegato',
			   valueField: 'IdTipoAllegato'
			},   
	        {
	            xtype: 'fileuploadfield',
	            fieldLabel: 'Allega Documento',
	            name: 'docPath',
	            id: 'docPath',
	            allowBlank: false,
	            buttonText: 'Cerca',
	            listeners: {
		            'fileselected': function(){
		                var valueTitolo=Ext.getCmp('titolo').getValue();
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
	    	buttons: [
		    {
			 //console.log();
			 text: 'Allega',
			 handler: function(b,event) {
			  if(formPanelAllega.getForm().isValid()) {
				DCS.showMask();
                formPanelAllega.getForm().submit({
					url: 'server/edit_azione.php',
					method: 'POST',
					params: {idstatoazione: "37", idcontratti: "["+ idContratto +"]"},
					success: function(frm, action){
					   if(action.result.success) {
						 Ext.Ajax.request({
							url: 'server/AjaxRequest.php',
							params: {task: 'read', sql: "SELECT MAX(IdAllegato) as IdAllegato, UrlAllegato  FROM allegato WHERE lastupd >= (CURdate()) AND IdContratto = " + idContratto},
							method: 'POST',
							reader: new Ext.data.JsonReader({
								root: 'results',//name of the property that is container for an Array of row objects
								id: 'IdAllegato'//the property within each row object that provides an ID for the record (optional)
							}, 
							[{name: 'IdAllegato'},
							 {name: 'UrlAllegato'}
							]),
							success: function(result, request){
								eval('var resp = (' + result.responseText + ').results[0]');
								idAllega = resp.IdAllegato;
								Ext.Ajax.request({
								   url: 'server/AjaxRequest.php', 
								   params : {task:'read', sql: "SELECT MAX(IdStoriaRecupero) AS IdStoriaRecupero FROM storiarecupero sr, allegato al WHERE sr.IdContratto = "+idContratto+" AND sr.IdUtente = al.IdUtente AND al.IdAllegato ="+idAllega},
								   method: 'POST',
								   reader:  new Ext.data.JsonReader(
								   {
									   root: 'results',//name of the property that is container for an Array of row objects
										 id: 'IdStoriaRecupero'//the property within each row object that provides an ID for the record (optional)
								   }),
								   success: function(result, request){
									DCS.hideMask();
								   	eval('var resp = (' + result.responseText + ').results[0]');
								   	var idStoriaRecupero = resp.IdStoriaRecupero;
								   	if (storiaRecAll == '') {
								   		storiaRecAll = idStoriaRecupero;
								   	}
								   	else {
								   		storiaRecAll = storiaRecAll + "," + idStoriaRecupero;
								   	}
								   },
								   failure: function ( result, request) { 
									  DCS.hideMask();
									  Ext.MessageBox.alert('Errore', result.responseText); 
								   }
								});
								Ext.Ajax.request({
									url: 'server/edit_allegati_speciale.php',
									params: {task: 'insertSpeciale', IdAllegato: idAllega, IdAzioneSpeciale: idAzione},
									method: 'POST',
									success: function(result, request){
									    DCS.hideMask();
										Ext.getCmp('gridAllegato').getStore().reload();
										saveSuccess(winAllega, frm, action);
									},
									failure: function(result, request){
									    DCS.hideMask();
										Ext.MessageBox.alert('Errore', result.responseText);
									}
								});
							},
							failure: function(result, request){
							    DCS.hideMask();
								Ext.MessageBox.alert('Errore', result.responseText);
							}
						 });	
					   }	
					},
					failure: function(result, request){
					    DCS.hideMask();
						 Ext.MessageBox.alert('Errore', result.responseText);
					}
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
		    tools: [helpTool("Allega")],
		    constrain: true,
		    modal: true,
			
		    items: [formPanelAllega]
		});	
		winAllega.show();
		myMask.hide();
	}
	
	function visualizzaRate(idContratto) {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Qualche istante, prego..."});
		myMask.show();
	
		var winRate = new Ext.Window({
	        id: 'windowAllega',
		    width: 350, 
		    height: 200,
		    layout: 'fit', 
		    plain:true, 
		    bodyStyle:'padding:5px;',
		    title:  'Visualizzazione Rate del Piano',
		    tools: [helpTool("RatePiano")],
		    constrain: true,
		    modal: true,
			
		    items: [DCS.PianoRientro(idContratto)],
			buttons: [{text: 'Chiudi', handler: function () {winRate.close();}}] 
		});	
		winRate.show();
		myMask.hide();
	}

}();

