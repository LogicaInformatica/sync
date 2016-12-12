// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS');


DCS.GridSintesiProroghe = Ext.extend(Ext.grid.GridPanel, {
	id:'',
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	groupOn:'',
	GroupFlag:'',
	GroupFlagLot:'',
	repId:'',
	btnMenuAzioni: null,
	
	initComponent : function() {

		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var actionColumn = {
				xtype: 'actioncolumn',
	            width: 90,
	            header:'Azioni',
	            printable:false, sortable:false,  filterable:false, resizable:false, fixed:true,
	            items: [{icon   : 'ext/examples/shared/icons/fam/table_refresh.png',
	                tooltip: 'Azioni',
	                handler: this.btnMenuAzioni.caricaMenu,
					scope: this.btnMenuAzioni
	            },'-',
	            {icon:"images/space.png"},
	            {icon: 'images/clock.png',
	                tooltip: 'Storia',
	                handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var numPratica = rec.get('numPratica');

					    var win = new Ext.Window({
					    	modal: true,
					        width: 800,
					        height: 650,
					        minWidth: 800,
					        minHeight: 650,
					        layout: 'fit',
					        plain: true,
							constrainHeader: true,
					        title: 'Storia Recupero - Pratica: '+numPratica,
					        items: DCS.StoriaRecupero(rec.get('IdContratto'), numPratica) 
					    });
					    win.show();
					},
					scope: this
	            },'-',{
					icon:"images/space.png"
				},{
	                getClass: function(v, meta, rec){ // Or return a class from a function
						if (rec.get('NumAllegati') > 0) {
							actionColumn.items[6].tooltip = 'Allegati';
							return 'con_allegati';
						}
						else {
							actionColumn.items[6].tooltip = 'Nessun allegato';
							return 'senza_allegati';
						}
					},
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var numPratica = rec.get('numPratica');

					    var win = new Ext.Window({
					    	modal: true,
					        width: 800,
					        height: 450,
					        minWidth: 800,
					        minHeight: 450,
					        layout: 'fit',
					        plain: true,
							constrainHeader: true,
					        title: 'Allegato - Pratica: '+numPratica,
					        items: DCS.Allegato(rec.get('IdContratto'), numPratica) 
					    });
					    win.show();
					},
					scope: this
	            },'-',
	            {
					getClass: function(v, meta, rec) {  // Or return a class from a function
	                	var nn =  rec.get('NumNote');
	                	if (nn==0)
	                	{
							actionColumn.items[8].tooltip = 'Nessuna nota';
	                    	return 'senza_note';
	                	}
	                	else if (nn<0) // solo note già lette
	                	{
							actionColumn.items[8].tooltip = (nn==-1)?'1 nota gi&agrave; letta':((-nn)+' note gi&agrave; lette');
	                    	return 'con_note';
	                	}
	                	else
	                	{
							actionColumn.items[8].tooltip = (nn==1)?'1 nuova nota':(nn+' nuove note');
	                    	return 'con_note_non_lette';
	                 	}
					},
	                handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
	                    DCS.FormNote.showDetailNote(rec.get('IdContratto'),rec.get('numPratica'),'N',0);
	                },
					scope: this
	            }]
		};
		
    	var fields, columns;
		fields = [{name: 'IdContratto'},
					{name: 'prodotto'},
					{name: 'numPratica'},
					{name: 'IdCliente', type: 'int'},
					{name: 'cliente'},
					{name: 'rata', type: 'int'},
					{name: 'insoluti',type: 'int'},
					{name: 'giorni', type: 'int'},
					{name: 'importo', type: 'float'},
					{name: 'ImpInteressiMora', type: 'float'},
					{name: 'ImpSpeseRecupero', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'DataInizioAffido', type:'date',dateFormat:'Y-m-d'},
					{name: 'DataFineAffido', type:'date',dateFormat:'Y-m-d'},
					{name: 'barraFineAffido', type:'date'},
					{name: 'DataScadenzaAzione', type:'date', dateFormat:'Y-m-d H:i:s'},
					{name: 'DataScadenza', type:'date'},
					{name: 'NumNote', type: 'int'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'Telefono'},
					{name: 'CodiceFiscale'}, // solo in Export
					{name: 'Indirizzo'}, 	 // solo in Export
					{name: 'CAP'},           // solo in Export
					{name: 'Localita'},      // solo in Export
					{name: 'SiglaProvincia'},// solo in Export
					{name: 'TitoloRegione'}, // solo in Export
					{name: 'CodRegolaProvvigione'}, // solo in Export
					{name: 'CiSonoAzioniOggi'},
					{name: 'Contratto'},
					{name: 'ColorState'},
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'Condizione'}];
					
		columns = [selM,
	        	{dataIndex:'DataInizioAffido',width:62,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'center', resizable:true, groupable:true, sortable:true, hidden: false},
			    {dataIndex:'DataFineAffido',width:60,xtype:'datecolumn', format:'d/m/y',	 header:'Fine affido',align:'center', resizable:true, groupable:true, sortable:true,hidden: false},
			    {dataIndex:'barraFineAffido',width:62,exportable:false, renderer:DCS.render.dataSem,header:' ',align:'left', resizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
				{dataIndex:'CiSonoAzioniOggi',width:16, exportable:false, renderer:DCS.render.spunta, header:' ',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
				{dataIndex:'ColorState',width:30, header:'Stato',align:'left', groupable:false, sortable:false},			    
	        	{dataIndex:'numPratica',width:65,	header:'N.Pratica', align:'left', filterable: true,sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
	        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:40,	header:'Importo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'tipoPag',   width:25,	header:'Pag.', filterable: true},
	        	{dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'AbbrStatoRecupero',		width:45,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true},
	        	{dataIndex:'AbbrClasse', width:40,	header:'Class.',filterable:true,sortable:true},
	        	{dataIndex:'agenzia',	width:45,	hidden: true,header:'Agenzia',filterable:true,sortable:true,groupable:true}
	        	,actionColumn
		        ];
		
		if (this.groupOn = 'lotto')
		{
			this.groupOn='DataFineAffido';
		}
		

		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheCorrenti.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task,repId: this.repId},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
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
			sm: selM,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true,
	            getRowClass : function(record, rowIndex, p, store){
	                if(record.get('ColorState')=='Accettata')
	                {
				        return 'grid-row-verdechiaro';
	                }else if(record.get('ColorState')=='Rifiutata')
	                {
	                	return 'grid-row-rosso';
	                }else if(record.get('ColorState')=='Scaduta')
	                {
	                	return 'grid-row-giallochiaro';
	                }
				}
			}),
			//plugins: [summary],
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					showPraticaDetail(rec.get('IdContratto'),rec.get('numPratica'),rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.gstore,rowIndex);
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
	                '->', this.btnMenuAzioni,
	                '-', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaProroghe"),' '
				];

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
//		debugger;
		DCS.GridSintesiProroghe.superclass.initComponent.call(this, arguments);
		
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);
	}
});


DCS.Proroghe = function(){

	return {
		create: function(){
			
			var user = CONTEXT.InternoEsterno;
			var TabProroghe = new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabPro',
				items: []
			});
			
			if (user=='I')
			{
				Ext.Ajax.request({
					url : 'server/praticheCorrenti.php' , 
					params : {task: 'AgenzieProrogheTabs'},
					method: 'POST',
					autoload:true,
					success: function ( result, request ) {
						eval('var resp = '+result.responseText);
						var arr = resp.results;
						var grid = new Array();
						var nomeG='';
						var listG = new Array();
						for(i=0;i<resp.total;i++){
							nomeG = "gridN"+i; 
							grid[nomeG] = new DCS.GridSintesiProroghe({
								id:'ListaProrogaG'+arr[i]['idAgenzia'],
								titlePanel: 'Proposte di proroga dell\'agenzia '+arr[i]['Agenzia'],
								title: arr[i]['Agenzia'],
								task: "readProLotMain",
								GroupFlag:false,
								GroupFlagLot:true,
								repId: arr[i]['idAgenzia'],
								stateId: 'ListaPro',
								stateful: true,
								groupOn:'lotto',
								groupDir: 'DESC'
							});
							listG.push(grid[nomeG]);
						}
						Ext.getCmp('tabPro').add(listG);
						Ext.getCmp('tabPro').setActiveTab(0);
					},
					failure: function ( result, request) { 
						eval('var resp = '+result.responseText);
						Ext.MessageBox.alert('Failed', resp.results); 
					},
					scope:this
				});
			}
			
			if (user=='E')
			{
				var ListaProroghe = new DCS.GridSintesiProroghe({
					id:'ListaProrogaG',
					titlePanel: 'Proposte di proroga',
					title: 'Recenti',
					task: "readProLot",
					stateId: 'ListaPro',
					stateful: true,
					GroupFlag:true,
					GroupFlagLot:true,
					groupOn:'lotto',
					groupDir: 'DESC'
				});
				
				Ext.getCmp('tabPro').add(ListaProroghe);
			}
			
			Ext.getCmp('tabPro').setActiveTab(0);
				
			return TabProroghe;
		}
	};
	
}();