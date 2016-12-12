//Gestione messaggi differiti 
Ext.namespace('DCS');

DCS.GridMessaggiDifferiti = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '', tipo: '',
	IdMessaggioDifferito:'',
	initComponent : function() {


	 var fields = [{name: 'CodContratto',type: 'string'},
    	              {name: 'NomeCliente',type: 'string'},
    	              {name: 'TitoloAllegato',type: 'string'},
    	              {name: 'UrlAllegato',type: 'string'},
    	              {name: 'Stato',type: 'string'},
    	              {name: 'Tipo',type: 'string'},
    	              {name: 'DataCreazione'},
    	              {name: 'DataEmissione'},
    	              {name: 'TestoEsito',type: 'string'},
    	              {name: 'TestoMessaggio',type: 'string'},
    	              {name: 'IdMessaggioDifferito',type: 'INT'},
    	              {name: 'IdModello',type: 'INT'},
    	              {name: 'IdContratto',type: 'INT'}
    	              //{name: 'Cellulare',type: 'string'},
    	              //{name: 'Email',type: 'string'}
    	          ];

    	var columns = [
    	               	{dataIndex:'IdMessaggioDifferito',width:80,hidden: true, header:'IdMessaggioDifferito'},
    	               	{dataIndex:'TestoMessaggio',width:80,hidden: true, header:'TestoMessaggio'},
    	               	//{dataIndex:'Cellulare',width:80,hidden: true, header:'Cellulare'},
    	               	//{dataIndex:'Email',width:80,hidden: true, header:'Email'},
    	               	{dataIndex:'CodContratto',width:50, header:'Cod Contratto',filterable:true,sortable:true,align:'center'},
    	               	{dataIndex:'NomeCliente',width:110, header:'Cliente',filterable:true,sortable:true,align:'left'},
    		        	{dataIndex:'Tipo',	width:40,	header:'Tipo',sortable:true,align:'left'},
    		        	{dataIndex:'Stato',	width:40,	header:'Stato',filterable:true,sortable:true,align:'left'},
    		        	{dataIndex:'TestoEsito',	width:140,	header:'Esito',sortable:true,align:'left'},
    		        	{dataIndex:'TitoloAllegato',	width:65,	header:'Titolo Allegato',filterable:true,sortable:true,align:'left'},
    		        	{dataIndex:'UrlAllegato',	width:120,hidden: true,	header:'Url Allegato',filterable:true,sortable:true,align:'center'},
    		        	{dataIndex:'DataCreazione',	width:80,	header:'Data Creazione',sortable:true,align:'center'},
    		        	{dataIndex:'DataEmissione',	width:80,	header:'Data Emissione',sortable:true,align:'center'},
    		        	{
    		                xtype: 'actioncolumn',
    		                printable: false,
    		                header:'Azioni',
    		                sortable:false, 
    		                align:'left',
    		                resizable: false,
    		                filterable:false,
    		                width: 42,
    		                menuDisabled: true,
    		                items: [
/*	    		                      {
										   icon   : 'images/delete.gif',               
										   tooltip: 'Cancella',
										   handler : function(grid, rowIndex, colIndex) {
										       var rec = grid.gstore.getAt(rowIndex);
										       grid.azione("Cancella",rec);
										   }
	    		                      },
*/ 
	        		                  {
			    		                    icon   : 'images/arrow_redo.png',               
					                        tooltip: 'Invia',
					                        handler: 
					                        	function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("Invio",rec);
					                        }
	        		                  },
	        		                  {
		        		                	getClass: function(v, meta, rec) {         
			        	                      
		        		                	  switch(rec.get('Stato'))
		        		                	  {
		        		                	  
		        		                		  case 'Sospeso':
		        		                			  this.items[1].tooltip = 'Riattiva';
		  	        	                              return 'attivazione';
		  	        	                          break;
		  	        	                          
		        		                		  case 'Creato':
		        		                				 this.items[1].tooltip = 'Sospendi';
			        	                        		 return 'sospensione';
		        		                		  default:
		        		                			  	this.items[1].tooltip = '';
		        		                		  		return 'empty_ico';
		     		                				 	break;
		        		                	  }// fine switch
		        		                     } 
			        	                    ,
			        	                    handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            if(rec.get('Stato')=='Sospeso')
					                            {
					                            	grid.azione("Attiva",rec);
					                            }
					                            if(rec.get('Stato')=='Creato')
					                            {
					                            	grid.azione("Sospendi",rec);
					                            }
					                        }
        		                      }
    		                       ]
    		            }
		        	  ];
		
		this.gstore = new Ext.data.Store({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/listaMessaggiDifferiti.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, tipo: this.tipo},
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});

		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			columns: columns,
			viewConfig: {
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
			    getRowClass : function(record, rowIndex, p, store){
		                if(record.get('Stato') =='Errore'){
		                    return 'grid-row-rosso';
		                }
		                if(record.get('Stato') =='Sospeso'){
		                    return 'grid-row-arancionechiaro';
		                }
		                if(record.get('Stato') =='Elaborato'){
		                    return 'grid-row-verdechiaro';
		                }
		        }
			},
			
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showDettaglio(rec);
				},
				scope: this
			}
	    });
		var tbarItems = [
							{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
			                '->', {type:'button',text: 'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
			                '-', {type: 'button', hidden:CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
			                '-', helpButton("MessaggiDifferiti"),' '
						];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		DCS.GridMessaggiDifferiti.superclass.initComponent.call(this, arguments);
		
		this.activation(); 
	},
	
	azione: function(task,rec)
	{
		todo=task;
	
		if(task=="Invio")
		{
			switch(rec.get('Tipo'))
			{
				case 'Lettera':
					
					Ext.Msg.alert("Attenzione....","Azione non disponibile su questo tipo di lettera.");
					return;
				
				case 'Sms':
						task="reinviaSms";
				break;	
				
				case 'Email':
						task="reinviaEmail";
					break;	
					
				default:
					Ext.Msg.alert("Attenzione....","Nessuna azione prevista");
				return;
		  }// fine switch
		}  // fine if	
		
		Ext.Msg.confirm(todo + '   '+ rec.get('Tipo'), "Si  vuole procedere con l'operazione?", 
				function(btn, text) {
										if (btn == 'yes')
										{	
											Ext.Ajax.request({
										        url: 'server/listaMessaggiDifferiti.php', method:'POST',
										        params :{task:task,IdModello:rec.get('IdModello'),IdMessaggioDifferito:rec.get('IdMessaggioDifferito'),testo:rec.get('TestoMessaggio'),CodContratto:rec.get('CodContratto'),tipo:rec.get('Tipo'),IdContratto:rec.get('IdContratto')},
										        success: function(obj) {
										        	eval('var resp = '+obj.responseText);
										        	Ext.MessageBox.alert(todo + '   '+ rec.get('Tipo'), resp.msg);
										        },
												failure: function (obj) {
													eval('var resp = '+obj.responseText);
													Ext.MessageBox.alert(todo + '   '+ rec.get('Tipo'), resp.msg);
					                    			
										        },
												scope: this
										     }); // fine request
											//this.store.load();
											this.store.load({
												params: { 
													start: 0, 
													limit: this.pagesize
												}
											});
										}	
		                    		 }, this); 
	  return;
	}// fine azione
	,	
	showDettaglio: function(rec)
	{
		var pnl = new Ext.Panel({
			title: 'Dest.  '+rec.get('NomeCliente'),
			preventBodyReset: true,
			html: '<p align="center"><strong>'+rec.get('TestoMessaggio')+'</strong><p>'
		});
		
		var win = new Ext.Window({
	    	modal: true,
	        width: 500,
	        height: 350,
	        layout: 'fit',
	        flex:1,
	        minHeight: 350,
	        minWidth: 500,
	        plain: true,
			constrainHeader: true,
	        title: "Dettaglio - " + rec.get('Tipo'),
	        items: [pnl]
	    });

		if(rec.get('Tipo')=='Sms')
		{	
			win.width= 500;
			win.height= 150;
		}
		
		if(rec.get('Tipo')=='Email')
		{	
			win.width= 550;
	        win.height= 500;
	    }
		
		if(rec.get('Tipo')=='Lettera')
		{
	    	if(rec.get('UrlAllegato')!="")
	    	{
	    		 pnl.html='<p align="justify"><strong><li><a href="'+rec.get('UrlAllegato')+'" target="_blank">Apri allegato lettera</a></li></strong><p>';
	    		 win.modal= true;
		    	 win.width= 300;
		    	 win.height= 100;
		    	 win.show();
	    	}
	    	else
	    	{
	    		Ext.Msg.alert("Attenzione","Allegato non generato.");
	    		return;
	    	}
		}
		else
		{
			if(rec.get('TestoMessaggio')=="")
		    {
				var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Operazione in corso...."});
				myMask.show();
				Ext.Ajax.request({
				        url: 'server/listaMessaggiDifferiti.php', method:'POST',
				        params :{task:rec.get('Tipo'),IdModello:rec.get('IdModello'),IdContratto:rec.get('IdContratto')},
				        callback: function (options, success, response) {
							if (success) 
							{ 
							  eval('var resp = '+response.responseText);
							  pnl.html= '<p align="center"><strong>' + resp.msg +'</strong><p>' ;
							  myMask.hide();
							  win.show();
							}
						    else 
						    {
								Ext.MessageBox.alert('Prego, provare di nuovo.',resp.msg);
							}
				       },
					   failure:function(response,options){
					   },                                      
				       success:function(response,options){
					   },      
					   scope: this
					  }); // fine request
		    
		    }
			else
			{
				win.show();	
			}
		}
	return;
	} // fine showDettaglio
	,	
	activation: function() {
		var lastOpt = this.store.lastOptions;
		if (!lastOpt || lastOpt.params==undefined) {
			if (this.pagesize>0) {
				this.store.load({
					params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
						start: 0, //pass start/limit parameters for paging
						limit: this.pagesize
					}
				}); 
			} else {
				this.store.load(); 
			}
		}
	}

}); // fine grid


DCS.MessaggiDifferiti = function(){
	return {
		create: function(){
			var grid1 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'SMS sollecito INS',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'SMS sollecito INS',
				task:'leggi', tipo:'SMS_INS'
			});

			var grid2 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'SMS sollecito ESA',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'SMS esattoriale',
				task:'leggi', tipo: 'SMS_ESA'
			});

			var grid3 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'Lettere sollecito INS',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'Lettere INS',
				task:'leggi', tipo: 'LET_INS'
			});

			var grid4 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'Lettere deontologiche',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'Lettere DEO',
				task:'leggi', tipo: 'LET_DEO'
			});

			var grid5 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'Lettere preavviso DBT',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'Lettere DBT',
				task:'leggi', tipo: 'LET_DBT'
			});

			var grid6 = new DCS.GridMessaggiDifferiti({
				titlePanel: 'SMS Precrimine',
				stateId: 'ListaMessaggiDifferiti',
				stateful: true,
				title: 'SMS Precrimine',
				task:'leggi', tipo: 'SMS_PRE'
			});
			
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [grid1, grid2, grid3, grid4, grid5, grid6]
			})
		}
	
	};
}();




