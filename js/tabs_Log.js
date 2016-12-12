// Grid lista log 
Ext.namespace('DCS');

DCS.GridListaLog = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 100,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	DtGrupFlag: '',
	GrupFlag: '',
	hdn:'',
	groupOn:'',
	groupDir:'',
	vista:'',
	
	initComponent : function() {
    	
		var fields = [{name: ''},
					{name: 'DataOra'},
					{name: 'Data'},
					{name: 'Evento', type: 'string'},
					{name: 'Descrizione', type: 'string'},
					//{name: 'CodEvento', type: 'string'},
					{name: 'Utente', type: 'string'}];
					
		var columns = [
		        {dataIndex:'Data',width:80, header:'Data',filterable:true,align:'left',groupable:this.DtGrupFlag,sortable:true,hidden:true},
	        	{dataIndex:'DataOra',width:80, header:'Data - Ora',filterable:true,align:'left',groupable:false,sortable:true},
	        	{dataIndex:'Evento',	width:150,	header:'Evento',align:'left',filterable:true,groupable:this.GrupFlag,sortable:true},
	        	{dataIndex:'Descrizione',	width:250,	header:'Descrizione',align:'left',filterable:true,sortable:true,groupable:false},
	        	//{dataIndex:'CodEvento',	width:60,	header:'CodEvento',align:'left',filterable:true,sortable:true,groupable:false},
	        	{dataIndex:'Utente',	width:120,	header:'Utente',align:'left',sortable:true,groupable:this.GrupFlag}
	        	];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/listaLog.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task,vista:this.vista},
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
				scope: this
			}
	    });

/*
		var chkElem = new Ext.form.Checkbox ({
			labelStyle: 'width:300;',
			//xtype: 'checkbox',
			boxLabel: '<span style="color:blue;"><b>Includi storia recupero</b></span>',
			name: 'StoriaRecupero',
			hidden: false,
			checked: false,
			listeners:{
		 		check: function(r,v)
		 		{
		 			if(v==true)
		 			{
		 				this.vista='storiarecupero';
		 				//this.store.load({params:{vista:this.vista}});
		 			}
		 			if(v==false)
		 			{
		 				this.vista='log';
		 				//this.store.load({params:{vista:this.vista}});
		 			}	
		 		},
		 		scope : this
	 		},	
			scope: this
	 	});
*/		
		var tbarItems = [
					
		                {xtype:'tbtext', text:this.titlePanel, style:"color:#15428B;font:bold 11px tahoma,arial,verdana,sans-serif"},
		                '->',//chkElem,
						'-', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
		                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
		                '-', helpButton("Log"),' '
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

		DCS.GridListaLog.superclass.initComponent.call(this, arguments);
	}
});

DCS.LogList = function(){

	return {
		create: function(){
			var gridLogPerData = new DCS.GridListaLog({
				titlePanel: 'Dettaglio eventi per data',
				title: 'Eventi per data',
				task: "data",
				GrupFlag:false,
				DtGrupFlag: true,
				groupOn : "Data",
				groupDir: 'DESC',
				hdn: false,
				vista: 'log'
				
			});
			var gridLogPerUtente = new DCS.GridListaLog({
				titlePanel: 'Dettaglio eventi per utente',
				title: 'Eventi per utente',
				task: "utente",
				GrupFlag:true,
				DtGrupFlag: false,
				groupOn : "Utente",
				groupDir: 'ASC',
				hdn: true,
				vista: 'log'
			});
			
			var gridLogPerDataTutto = new DCS.GridListaLog({
				titlePanel: 'Dettaglio eventi ed azioni utenti',
				title: 'Eventi ed azioni per data',
				task: "data",
				GrupFlag:false,
				DtGrupFlag: true,
				groupOn : "Data",
				groupDir: 'DESC',
				hdn: false,
				vista: 'storiarecupero'
				
			});
			var gridLogPerUtenteTutto = new DCS.GridListaLog({
				titlePanel: 'Dettaglio eventi ed azioni utenti',
				title: 'Eventi ed azioni per utente',
				task: "utente",
				GrupFlag:true,
				DtGrupFlag: false,
				groupOn : "Utente",
				groupDir: 'ASC',
				hdn: false,
				vista: 'storiarecupero'
			});
			
			
			
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [gridLogPerData,gridLogPerUtente,gridLogPerDataTutto,gridLogPerUtenteTutto]
			})
		}
	};
	
}();
