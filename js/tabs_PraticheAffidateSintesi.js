// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheAffidateSintesi = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	idAgenz:'',
	
	initComponent : function() {
    	var summary = new Ext.ux.grid.GroupSummary();

   	    var fields = [{name: 'AbbrStatoRecupero'},
		//			{name: 'AbbrClasse'},
					{name: 'Agenzia'},
    				{name: 'Trattati',type:'int'},
    				{name: 'DaTrattare',type:'int'},
					{name: 'CodAgenzia'},
					{name: 'TitoloFamiglia'},
					{name: 'NumInsoluti', type: 'int'},
					{name: 'ImpInsoluto', type: 'float'},
					{name: 'ImpPagato',type: 'float'},
    				{name: 'ImpCapitale',type: 'float'},
    				{name: 'PercTotale',type: 'float'},
    				{name: 'PercCapitale',type: 'float'},
    				{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'NumPratiche', type: 'int'}];
					
		var columns = [
	        	{dataIndex:'Agenzia',	width:40,	hidden: true,header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrStatoRecupero',width:60, header:'Stato',filterable:true,groupable:true,sortable:true},
	     //   	{dataIndex:'AbbrClasse',	width:60,	header:'Classific.',filterable:true,groupable:true,sortable:true},
	        	{dataIndex:'TitoloFamiglia',	width:60,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'NumInsoluti', width:60,	align:'right',header:'Num. pratiche',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
	        	{dataIndex:'Trattati', width:50,	align:'right',header:'Lavorate',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
	        	{dataIndex:'DaTrattare', width:50,	align:'right',header:'Da lavorare',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
	        	{dataIndex:'ImpInsoluto', width:60,	header:'Importo totale',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
	        		xtype:'numbercolumn',format:'0.000,00/i'},
	 	        {dataIndex:'ImpCapitale', width:60,	header:'Importo rate',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
    	        	xtype:'numbercolumn',format:'0.000,00/i'},
    	        {dataIndex:'ImpPagato',	  width:80,	header:'Importo recuperato',align:'right',sortable:true,summaryType:'sum',
	                xtype:'numbercolumn',format:'0.000,00/i'},
		        {dataIndex:'PercTotale', width:50,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
	    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
		        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
	    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'},
	        	{dataIndex:'NumPratiche',	width:30,	header:'N.prat.',align:'right',sortable:true,summaryType:'sum'}
		        ];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheAffidateSintesi.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, idA: this.idAgenz},
			remoteSort: true,
			//groupField: 'Agenzia',
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			}),
			listeners: {load: DCS.hideMask}
  		});

    	// filtri
		this.filters = new Ext.ux.grid.GridFilters({
        	// encode and local configuration options defined previously for easier reuse
        	encode: true, // json encode the filter query
        	local: true,   // defaults to false (remote filtering)
        	filters: [{
            	type: 'list',options:[],
            	dataIndex: 'AbbrStatoRecupero'
        	}, {
            	type: 'list',  options: [],
            	dataIndex: 'CodAgenzia'
        	}, {
            	type: 'list',  options: [],
            	dataIndex: 'TitoloFamiglia'
       		}]
    	});


		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			plugins: [this.filters,summary],
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showListaPratiche(rec.get('AbbrStatoRecupero'),rec.get('CodAgenzia'),rec.get('TitoloFamiglia'));
				},
				activate: function(pnl) {
					var lastOpt = this.store.lastOptions;
					if (!lastOpt) {
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
	                '->', {type:'button',text: 'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaAffidati"),' '
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

		DCS.GridPraticheAffidateSintesi.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaPratiche: function(stato,  agenzia, prodotto)
    {
   	var pnl = new DCS.pnlSearch({stato: stato, agenzia: agenzia, prodotto: prodotto, IdC: 'PSintesi'});
		var win = new Ext.Window({
    		width: 1100, height:600, minWidth: 700, minHeight: 500,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Pratiche di dettaglio',
    		constrain: true,
			items: [pnl]
        });
     	win.show();
		pnl.activation.call(pnl);
    }

});

DCS.PraticheAffidateSintesi = function(){

	return {
		create: function(){
			DCS.showMask();
				
			var TabPanelAgSint = new Ext.TabPanel({
				activeTab: 0,
				id: 'TabPanelAgSint',
				enableTabScroll: true,
				flex: 1,
				items: []
			}); 
			Ext.Ajax.request({
				url : 'server/praticheCorrenti.php' , 
				params : {task: 'AgenzieTabs'},
				method: 'POST',
				autoload:true,
				success: function ( result, request ) {
					eval('var resp = '+result.responseText);
					eval('var arr = '+resp.results);
					var grid = new Array();
					var nomeG='';
					var listG = new Array();
					for(i=0;i<resp.total;i++){
						//console.log("arr titolo "+arr[i] ['titoloufficio']+" | arr ida "+arr[i] ['idAgenzia']);
						nomeG = "gridN"+i; 
						//console.log("Nome: "+nomeG);
						grid[nomeG] = new DCS.GridPraticheAffidateSintesi({
							titlePanel: 'Sintesi pratiche affidate all\' agenzia '+arr[i]['titoloufficio'],
							title: arr[i] ['titoloufficio'],
							task: "sintesiAg",
							idAgenz: arr[i]['idAgenzia'],
							hideStato: true
						});
						//Ext.getCmp('TabPanelAg').add(grid[nomeG]);
						//console.log("G: "+grid[nomeG].titlePanel);
						listG.push(grid[nomeG]);
						//console.log("l "+listG[i].titlePanel);
					}
					Ext.getCmp('TabPanelAgSint').add(listG);
					DCS.hideMask();
					Ext.getCmp('TabPanelAgSint').setActiveTab(0);
				},
				failure: function ( result, request) { 
					DCS.hideMask();
					eval('var resp = '+result.responseText);
					Ext.MessageBox.alert('Failed', resp.results); 
				},
				scope:this
			});
			return TabPanelAgSint;
		}
	};
	
}();