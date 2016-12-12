// Lista delle Comunicazioni
Ext.namespace('DCS');

// Oggetto per espandere/collassare a riga

DCS.GridComunicazioni = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: PAGESIZE,
	titlePanel: '',
	tipoNota: '',
	testoNewNota: '',
	task: '',
	sqlExtraCondition : '',
	filters: null,
	initComponent : function() {
 
	var textExpander = new Ext.ux.grid.RowExpander({
    	tpl : new Ext.Template('<p><b>Testo:</b> {TestoNota}</p><br>')
	});		

	var fields = [{name: 'IdNota',type:'int'},
				{name: 'DataCreazione', type:'date', dateFormat:'Y-m-d H:i:s'},
				{name: 'mittente'},
				{name: 'destinatario'},
				{name: 'riservato'},
				{name: 'TestoNota'},
				{name: 'CodContratto'},
				{name: 'NomeCliente'},
				{name: 'IdCliente'},
				{name: 'IdUtente'},
				{name: 'IdContratto'},
				{name: 'DataScadenza', type:'date', dateFormat:'Y-m-d'},
				{name: 'OraScadenza', type:'date', dateFormat:'H:i'},
				{name: 'DataIni'},
				{name: 'DataFin'}];
	
	
	if (this.task.match(/^scadenz/)) {
		var fldData = {dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y', 
				header:'Data scadenza',align:'left', filterable: true, groupable:true, sortable:true};
		var fldOra  = {dataIndex:'OraScadenza',width:50, header:'Ora scadenza',sortable:true, xtype:'datecolumn', format:'H:i'};
		var sort = {field: 'DataScadenza', direction: "ASC"};
		var grpField = 'DataScadenza';
	} else {
		var fldData = {dataIndex:'DataCreazione', width:82, header:'Data',sortable:true, xtype:'datecolumn', format:'d/m/y', 
				filterable: true, groupable:true};
		var fldOra  = {dataIndex:'DataCreazione',width:50, header:'Ora',sortable:true, xtype:'datecolumn', format:'H:i'};
		var sort = {field: 'DataCreazione', direction: "ASC"};
		var grpField = 'DataCreazione';
	}
	
	var RiservatoHidden=true;
	if (CONTEXT.READ_RISERVATO){
		RiservatoHidden=false;
	}
	var columns = [textExpander,fldData,fldOra,
	               {dataIndex:'mittente',width:100, header:'Mittente',groupable:true,sortable:true},
	               {dataIndex:'destinatario',width:100, header:'Destinatario',groupable:true,sortable:true},
	               {dataIndex:'TestoNota',	width:150,	header:'Testo'},
	               {dataIndex:'riservato',	width:40,	header:'Riservato', hidden:RiservatoHidden},
	               {dataIndex:'CodContratto',	width:40,	header:'Num. prat.',sortable:true},
	               {dataIndex:'NomeCliente',	width:80,	header:'Cliente',sortable:true},
	               {dataIndex:'DataIni',	width:42,	header:'Visibile dal',hidden:true},
	               {dataIndex:'DataFin',	width:42,	header:'al',hidden:true},
	               {xtype: 'actioncolumn', width: 40,  header:'Azioni',
	            	   fixed:true,
	            	   items: [{tooltip: 'Cancella comunicazione',
	            		   getClass: function(v,meta,rec) {
	            		   // è possibile eliminare solo l'ultima nota e solo da colui che l'ha emessa 
	            		   //if ((rec.get('rowNum')=='1') && (rec.get('idUserCorrente')==rec.get('IdUtente'))) {
	            		   if (CONTEXT.IdUtente==rec.get('IdUtente')) {
	            			   return 'del-row';
	            		   } else {
	            			   return '';
	            		   }
	            	   },                    	 
	            	   handler: function(grid, rowIndex, colIndex) {
	            		   var rec = grid.gstore.getAt(rowIndex);
							
                        	Ext.Ajax.request({
                        		url : 'server/edit_note.php' , 
                        		params : {task: 'delete',idNotaDel: rec.get('IdNota')},
                        		method: 'POST',
                        		success: function ( result, request ) {
        	            		   	grid.gstore.reload();
                        		},
                        		failure: function ( result, request) { 
                        			Ext.MessageBox.alert('Errore', result.responseText); 
                        		} 
                        	});
	            	   }
	            	   }
	            	   ]}
	               ];
	
		//Imposta la visibilità delle colonne a seconda della configurazione effettuata sul submain
		columns = setColumnVisibility(columns);

		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/comunicazioni.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, sqlExtraCondition : this.sqlExtraCondition, ggNota: this.ggNota},
			remoteSort: true,
			groupField: grpField,
			groupOnSort: false,
			remoteGroup: true,
			sortInfo: sort,
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
			view: new Ext.grid.GroupingView({
				forceFit: true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
	            hideGroupedColumn: true
           }),
           viewConfig: {
               forceFit:true,
               enableRowBody:true,
               showPreview:true,
               getRowClass : function(record, rowIndex, p, store){
                   if(this.showPreview){
                       p.body = '<p style="color:darkblue">&nbsp&nbsp&nbsp;'+record.TestoNota+'</p>';
                       return 'x-grid3-row-expanded';
                   }
                   return 'x-grid3-row-collapsed';
               }
           },
		   plugins: [textExpander],
		   autoExpandColumn: 'TestoNota',
		   columns: columns,
		   listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					if (this.task=='scadenzepraticheGG' || this.task=='scadenzegeneraliGG' 
						|| this.task=='scadenzepratiche'  || this.task=='scadenzegenerali') {
						DCS.FormNota.showDetailNote(rec.get('IdContratto'),rec.get('CodContratto'),'S',rec.get('IdNota'),rec.get('IdCliente'),rec.get('NomeCliente'));
					} else {
						if (rec.get('IdContratto')>0)
							DCS.FormVistaNote.showDetailVistaNote(rec.get('IdContratto'),rec.get('CodContratto'),'N',0,this.store);
						else
							DCS.FormNota.showDetailNote(rec.get('IdContratto'),rec.get('CodContratto'),'C',rec.get('IdNota'),rec.get('IdCliente'),rec.get('NomeCliente'));
					}
				},
				activate: function(pnl) {
					this.store.setBaseParam('attiva','Y'); 
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
				},
				scope: this
			}
	    });
 		
 		
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{ref: '../addBtn',
						  text: 'Nuova '+this.testoNewNota,
						  hidden: (CONTEXT.BOTT_NEW_COMM != true),
						  handler: function() {
								DCS.FormNota.showDetailNote(0,0,this.tipoNota,0,0,'');
		                    },
		    				scope: this,
						  tooltip: 'Crea una nuova '+this.testoNewNota,
						  iconCls:'grid-add'},
					'-'];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.gstore,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridComunicazioni.superclass.initComponent.call(this, arguments);
		
	}

});

DCS.Comunicazioni = function(){
	return {
		createComm: function(){


			if (CONTEXT.READ_ALL || CONTEXT.READ_RISERVATO) // può vedere le com. riservate
			{
				var pnlCom = new Ext.TabPanel({
	    			activeTab: 0,
					enableTabScroll: true,
					flex: 1,
					items: []});
				
				if(ComNormaliNonRis)
				{
					var grid1 = new DCS.GridComunicazioni({
						titlePanel: 'Note non riservate',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Note Normali',
						task: "nonriservate"
					});
					pnlCom.add(grid1);
				}	
				
				if(ComRiservate)
				{	
					var grid2 = new DCS.GridComunicazioni({
						titlePanel: 'Note riservate',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Note Riservate',
						task: "riservate"
					});
					pnlCom.add(grid2);
				}	

				if(MsgNonLetti)
				{
					var grid3 = new DCS.GridComunicazioni({
						titlePanel: 'Messaggi non letti',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Messaggi non letti',
						task: "nonlette"
					});
					pnlCom.add(grid3);
				}	

				if(MsgLetti)
				{
					var grid4 = new DCS.GridComunicazioni({
						titlePanel: 'Messaggi letti di recente',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Messaggi letti di recente',
						task: "lettirecenti"
					});
					pnlCom.add(grid4);
				}	

				return pnlCom;
			}
			else
			{
				var pnlCom = new Ext.TabPanel({
	    			activeTab: 0,
					enableTabScroll: true,
					flex: 1,
					items: []});
				
				if(ComNormaliNonRis)
				{
					var grid1 = new DCS.GridComunicazioni({
						titlePanel: 'Comunicazioni',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Normali',
						task: "nonriservate"
					});
					pnlCom.add(grid1);
				}	
				
				if(MsgNonLetti)
				{
					var grid3 = new DCS.GridComunicazioni({
						titlePanel: 'Messaggi non letti',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Comunicazioni non lette',
						task: "nonlette"
					});
					pnlCom.add(grid3);
				}
				
				if(MsgLetti)
				{
					var grid4 = new DCS.GridComunicazioni({
						titlePanel: 'Messaggi letti di recente',
						tipoNota: 'C',
						testoNewNota: 'Comunicazione',
						title: 'Messaggi letti di recente',
						task: "lettirecenti"
					});
					pnlCom.add(grid4);
				}
					
				return pnlCom;
			}
		},
		
		createScadenzario: function(sqlExtraCondition,panelTitle) {
		
			var pnlScad = new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				title : panelTitle,
				flex: 1,
				items: []});
			
			if(panelTitle>"")
				panelTitle = panelTitle +" - ";
			else
				panelTitle=''; // per evitare "undefined"
			
			if(ScadScadenzePratiche)
			{	
				var grid1 = new DCS.GridComunicazioni({
					titlePanel: panelTitle+'Scadenze su pratiche',
					tipoNota: 'S',
					testoNewNota: 'Scadenza',
					title:  'Su pratiche',
					sqlExtraCondition : sqlExtraCondition,
					task: "scadenzepratiche"
				});
				pnlScad.add(grid1);
			}	
			
			if(ScadScadenzeGenerali)
			{
				var grid2 = new DCS.GridComunicazioni({
					titlePanel:  panelTitle+'Altre scadenze',
					tipoNota: 'S',
					testoNewNota: 'Scadenza',
					title: 'Generali',
					sqlExtraCondition : sqlExtraCondition,
					task: "scadenzegenerali"
				});
				pnlScad.add(grid2);
			}	
				
			return pnlScad;
		},
		createScadenzarioMax: function(gg) {
			var grid1 = new DCS.GridComunicazioni({
				titlePanel: 'Scadenze su pratiche',
				tipoNota: 'S',
				ggNota: gg,
				testoNewNota: 'Scadenza',
				title: 'Su pratiche',
				task: "scadenzepraticheGG"
			});
			
			var grid2 = new DCS.GridComunicazioni({
				titlePanel: 'Altre scadenze',
				tipoNota: 'S',
				ggNota: gg,
				testoNewNota: 'Scadenza',
				title: 'Generali',
				task: "scadenzegeneraliGG"
			});
			
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [grid1, grid2]});
		}

	};
	
}();
