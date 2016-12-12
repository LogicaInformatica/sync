// Grid incassi
Ext.namespace('DCS');

DCS.GridListaDistinte = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	id:'',
	pagesize: 100,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	GroupFlag: '',
	GroupFlagLot: '',
	hdn:'',
	groupOn:'',
	groupDir:'',
	repId:'',
	
	initComponent : function() {
		
		var fields = [
						{name: 'IdDistinta',   	type: 'int'},
						{name: 'IdCompagnia',   type: 'int'},
						{name: 'DataPagamento', type: 'date'},
						{name: 'Importo',   	type: 'float'},
						{name: 'UrlRicevuta',	type: 'string'},
						{name: 'IBAN',    		type: 'string'},
						{name: 'LastUser',		type: 'string'},
						{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
						{name: 'CRO',     		type: 'string'}];
			
		
			var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
			var columns = [selM,
			               	{dataIndex:'IdDistinta',   	width:40,  header:'N. Distinta',		filterable:false, align:'left',   groupable:false,          sortable:true, 	hidden:false },
			               	{dataIndex:'IdCompagnia',   width:50,  header:'IdCompagnia',    filterable:false, align:'left',   groupable:false,          sortable:false, 	hidden:true },
			               	{dataIndex:'DataPagamento',	width:80,  header:'DataPagamento',  filterable:true , align:'center', groupable:false,          sortable:true , 	hidden:false , xtype:'datecolumn', format:'d/m/y' },
			               	{dataIndex:'Importo',	  	width:80,  header:'Importo',   		filterable:true , align:'right',  groupable:false,          sortable:true , 	hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
			               	{dataIndex:'IBAN',			width:100, header:'IBAN',     		filterable:true , align:'left',   groupable:false,          sortable:true , 	hidden:false},
			               	{dataIndex:'CRO', 			width:100, header:'CRO',       		filterable:true , align:'left',   groupable:false,          sortable:false , 	hidden:false},
			               	{dataIndex:'UrlRicevuta',	width:80,  header:'Ricevuta',   		filterable:false, align:'left',   groupable:false,          sortable:false , 	hidden:false},
			               	{dataIndex:'LastUser',	    width:60,  header:'Last User',     	filterable:true , align:'center', groupable:false,          sortable:true , 	hidden:false},
			               	{dataIndex:'LastUpd',	    width:80,  header:'LastUpd',        filterable:true , align:'center', groupable:false,          sortable:true , 	hidden:false , xtype:'datecolumn', format:'Y-m-d H:i:s' },
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
											       grid.deleteDist(rec,this.id);
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
			baseParams:{attiva:'N', task: this.task,repId: this.repId },
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			id: 'tabStoreDist',
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
			sm: selM,
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
					this.showDistintaDetail(rec,this.id); 
					
				},
				scope: this
			}
	    });
		
		var tbarItems = [
					
		                {xtype:'tbtext', text:this.titlePanel, style:"color:#15428B;font:bold 11px tahoma,arial,verdana,sans-serif"},
		                '->',//chkElem,
		                '-', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
		                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
		                '-', helpButton("ListaDistinte"),' '
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
		
		DCS.GridListaDistinte.superclass.initComponent.call(this, arguments);
	},
	
	showDistintaDetail: function(rec,idGrid)
	{
		var risp = DCS.showDistinctDetail.create(rec.get('IdDistinta'),"attachments/"+rec.get('UrlRicevuta'),idGrid);
	},
	
	deleteDist: function(rec,ID)
	{
		Ext.Msg.confirm("Cancellazione distinta", "Si  vuole procedere con l'operazione?", 
				function(btn, text) {
										if (btn == 'yes')
										{	
											Ext.Ajax.request({
										        url: 'server/gestioneIncassi.php', method:'POST',
										        params :{task:"deleteDistinta",idDistinta:rec.get('IdDistinta')},
										        success: function (obj) {
													var grid = Ext.getCmp(ID).getStore().reload(); 
													eval('var resp = '+obj.responseText);
										        	Ext.MessageBox.alert("Cancellazione distinta", resp.msg);
										        	},
										        failure: function (obj) {
													eval('var resp = '+obj.responseText);
													Ext.MessageBox.alert("Cancellazione distinta", resp.msg);
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

DCS.Distinte = function(){

	return {
		create: function(){
			
			var user = CONTEXT.InternoEsterno;
			var TabDistinte = new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabDist',
				items: []
			});
			
			if (user=='I')
			{
				Ext.Ajax.request({
					url : 'server/gestioneIncassi.php' , 
					params : {task: 'AgenzieDistinteTabs'},
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
							grid[nomeG] = new DCS.GridListaDistinte({
								id:'ListaDistintaG'+arr[i]['IdCompagnia'],
								titlePanel: 'Distinte dell\'agenzia '+arr[i]['RepartoInc'],
								title: arr[i]['RepartoInc'],
								task: "readDistLotMain",
								GroupFlag:false,
								GroupFlagLot:true,
								repId: arr[i]['IdCompagnia'],
								stateId: 'ListaDistinta',
								stateful: true,
								groupOn : '',
								groupDir: 'DESC'
							});
							listG.push(grid[nomeG]);
						}
						Ext.getCmp('tabDist').add(listG);
						Ext.getCmp('tabDist').setActiveTab(0);
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
				var ListaIncassi = new DCS.GridListaDistinte({
					id:'ListaDistintaG',
					titlePanel: 'Distinte agenzia',
					title: 'Recenti',
					task: "readDistLot",
					stateId: 'ListaDistinta',
					stateful: true,
					GroupFlag:true,
					GroupFlagLot:true,
					groupOn : '',
					groupDir: 'DESC'
				});
				
				Ext.getCmp('tabDist').add(ListaIncassi);
			}
			
			Ext.getCmp('tabDist').setActiveTab(0);
				
			return TabDistinte;
		}
	};
	
}();
