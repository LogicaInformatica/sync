// Grid incassi
Ext.namespace('DCS');

DCS.GridListaIncassi = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	id:'',
	pagesize: 100,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	GroupFlag: '',
	hdn:'',
	groupOn:'',
	groupDir:'',
	
	initComponent : function() {

		var fields = [
						{name: 'CodContratto',type: 'string'},
						{name: 'NomeCliente', type: 'string'},
						{name: 'IdIncasso',   type: 'int'},
						{name: 'TitoloTipoIncasso',        type: 'string'},
						{name: 'Data'},
						{name: 'ImpPagato',   type: 'float'},
						{name: 'ImpInsoluto', type: 'float'},
						{name: 'UtenteInc',   type: 'string'},
						{name: 'RepartoInc',  type: 'string'},
						{name: 'UrlAllegato',  type: 'string'}];
					
		var columns = [
		               	{dataIndex:'UrlAllegato',width:80,   header:'UrlAllegato',   filterable:false, align:'left',   groupable:false,          sortable:true , hidden:true},
				        {dataIndex:'CodContratto',width:80,  header:'Contratto',     filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
				        {dataIndex:'NomeCliente', width:100, header:'Cliente',       filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
				        {dataIndex:'IdIncasso',   width:80,  header:'IdIncasso',     filterable:false, align:'left',   groupable:false,          sortable:false, hidden:true },
			        	{dataIndex:'TitoloTipoIncasso',	      width:60,  header:'Tipo inc.',     filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
			        	{dataIndex:'Data',	      width:80,  header:'Data',          filterable:true , align:'center', groupable:false,          sortable:true , hidden:false , xtype:'datecolumn', format:'d/m/y' },
			        	{dataIndex:'ImpPagato',	  width:80,  header:'Imp. Pagato',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpInsoluto', width:80,  header:'Imp. Insoluto', filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false,  xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'UtenteInc',   width:80,  header:'Utente',        filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
			        	{dataIndex:'RepartoInc',  width:80,  header:'Reparto',       filterable:true , align:'left',   groupable:this.GroupFlag, sortable:true , hidden:false},
			        	{
    		                xtype: 'actioncolumn',
    		                printable: false,
    		                header:'Azioni',
    		                sortable:false, 
    		                align:'center',
    		                resizable: false,
    		                filterable:false,
    		                width: 42,
    		                menuDisabled: true,
    		                items: [
	    		                      {
										   icon   : 'images/delete.gif', 
										   tooltip: 'Cancella',
										   handler : function(grid, rowIndex, colIndex) {
										       var rec = grid.gstore.getAt(rowIndex);
										       grid.deleteIncasso(rec);
										   }
	    		                      }
	    		                   ]
    		            }
			        ];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneIncassi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			groupDir: this.groupDir,
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
			view: new Ext.grid.GroupingView({
				//startCollapsed : true,
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			columns: columns,
			listeners: {
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
				
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showDettaglioIncasso(rec,this.id); 
					
				},
				scope: this
			}
	    });

		var tbarItems = [
					
		                {xtype:'tbtext', text:this.titlePanel, style:"color:#15428B;font:bold 11px tahoma,arial,verdana,sans-serif"},
		                '->',//chkElem,
						'-', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
		                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
		                '-', helpButton("ListaIncassi"),' '
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
					chkElem.reset();
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridListaIncassi.superclass.initComponent.call(this, arguments);
	},
	
	showDettaglioIncasso: function(rec,idcaller)
	{
		var risp = DCS.showIncassoDetail.create(idcaller,rec.get('IdIncasso'),rec.get('UrlAllegato'),rec.get('CodContratto'),rec.get('NomeCliente'));
	},
	
	deleteIncasso: function(rec)
	{
		Ext.Msg.confirm("Cancella incasso", "Si  vuole procedere con l'operazione?", 
				function(btn, text) {
										if (btn == 'yes')
										{	
											Ext.Ajax.request({
										        url: 'server/gestioneIncassi.php', method:'POST',
										        params :{task:"deleteIncasso",idIncasso:rec.get('IdIncasso')},
										        success: function (obj) {
													var grid = Ext.getCmp('ListaIncassi').getStore().reload(); 
													eval('var resp = '+obj.responseText);
										        	Ext.MessageBox.alert("Cancellazione incasso", resp.msg);
										        	},
										        failure: function (obj) {
													eval('var resp = '+obj.responseText);
													Ext.MessageBox.alert("Cancellazione incasso", resp.msg);
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
	}
});

DCS.Incassi = function(){

	return {
		create: function(){
			
			var user = CONTEXT.InternoEsterno;
		
			if (user=='I')
			{
				var ListaIncassi = new DCS.GridListaIncassi({
					id:'ListaIncassi',
					titlePanel: 'Incassi ',
					title: 'Recenti',
					task: "readU",
					GroupFlag:true,
					groupOn : 'RepartoInc',
					groupDir: 'DESC'
				});
			}
			
			if (user=='E')
			{
				var ListaIncassi = new DCS.GridListaIncassi({
					id:'ListaIncassi',
					titlePanel: 'Incassi agenzia',
					title: 'Recenti',
					task: "readA",
					GroupFlag:false,
					groupOn : '',
					groupDir: 'DESC'
				});
			}
				
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [ListaIncassi]
			})
		},
		
		showBonificiSospesi: function() {
			var wfeatures = 'menubar=yes,resizable=yes,scrollbars=yes,status=yes,location=yes';
			window.open('links/Bonifici sospesi.xls',"Bonifici sospesi",wfeatures);
							
			return null;
		},
		showBollettiniSmarriti: function() {
			var wfeatures = 'menubar=yes,resizable=yes,scrollbars=yes,status=yes,location=yes';
			window.open('links/Bollettini postali smarriti.xlsx',"Bollettini smarriti",wfeatures);
							
			return null;
		}	
		};
	
}();
