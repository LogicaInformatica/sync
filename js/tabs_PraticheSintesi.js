// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS');

DCS.GridSintesiPratiche = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
		
	initComponent : function() {
    	var summary = new Ext.ux.grid.GroupSummary();
    	var fields, columns;
    	this.locFilters=null;
		switch (this.task)
		{
			case "sintesiPerStato":
				groupedOn = '';
				fields = [{name: 'StatoRecupero'},{name: 'IdStatoRecupero', type: 'int'},
								{name: 'NumInsoluti', type: 'int'},
								{name: 'ImpInsoluto', type: 'float'},
								{name: 'ImpPagato',type: 'float'},
			    				{name: 'ImpCapitale',type: 'float'},
			    				{name: 'PercTotale',type: 'float'},
			    				{name: 'PercCapitale',type: 'float'},
			    				{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
								{name: 'NumPratiche', type: 'int'}];
								
				columns = [
				        	{dataIndex:'StatoRecupero',width:80, header:'Stato',filterable:true,groupable:true,sortable:true},
				        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
				        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
				        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
				        		xtype:'numbercolumn',format:'0.000,00/i'},
				 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			    	        	xtype:'numbercolumn',format:'0.000,00/i'},
			    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',sortable:true,filterable:true,summaryType:'sum',
				                xtype:'numbercolumn',format:'0.000,00/i'},
					        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
					        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
					        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiPerAgenzia":
				groupedOn = '';
				fields = [{name: 'Agenzia'},{name: 'IdAgenzia', type: 'int'},
							{name: 'NumInsoluti', type: 'int'},
							{name: 'ImpInsoluto', type: 'float'},
							{name: 'ImpPagato',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercTotale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
							{name: 'NumPratiche', type: 'int'}];
							
				columns = [
			        	{dataIndex:'Agenzia',width:80, header:'Agenzia',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			        		xtype:'numbercolumn',format:'0.000,00/i'},
			 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
		    	        	xtype:'numbercolumn',format:'0.000,00/i'},
		    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',sortable:true,filterable:true,summaryType:'sum',
			                xtype:'numbercolumn',format:'0.000,00/i'},
				        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
				        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
				        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiPerClasse":
				groupedOn = '';
				fields = [{name: 'Classe'},{name: 'IdClasse', type: 'int'},
							{name: 'NumInsoluti', type: 'int'},
							{name: 'ImpInsoluto', type: 'float'},
							{name: 'ImpPagato',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercTotale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
							{name: 'NumPratiche', type: 'int'}];
							
				columns = [
			        	{dataIndex:'Classe',width:80, header:'Classificazione',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			        		xtype:'numbercolumn',format:'0.000,00/i'},
			 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
		    	        	xtype:'numbercolumn',format:'0.000,00/i'},
		    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',sortable:true,filterable:true,summaryType:'sum',
			                xtype:'numbercolumn',format:'0.000,00/i'},
				        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
				        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
				        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiPerProdotto":
				groupedOn = 'Famiglia';
				fields = [{name: 'Famiglia'},{name: 'Prodotto'},{name: 'IdFamiglia', type: 'int'},
							{name: 'NumInsoluti', type: 'int'},
							{name: 'ImpInsoluto', type: 'float'},
							{name: 'ImpPagato',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercTotale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
							{name: 'NumPratiche', type: 'int'}];
							
				columns = [
				        {dataIndex:'Famiglia',width:80, header:'Famiglia di prodotti',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'Prodotto',width:80, header:'Prodotto',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			        		xtype:'numbercolumn',format:'0.000,00/i'},
			 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
		    	        	xtype:'numbercolumn',format:'0.000,00/i'},
		    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',filterable:true,sortable:true,summaryType:'sum',
			                xtype:'numbercolumn',format:'0.000,00/i'},
				        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
				        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
				        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiPerLotto":
				groupedOn = 'Lotto';
				fields = [{name: 'Agenzia'},{name: 'IdAgenzia', type: 'int'},{name: 'Lotto'},{name: 'DataFineAffido'},
							{name: 'NumInsoluti', type: 'int'},
							{name: 'ImpInsoluto', type: 'float'},
							{name: 'ImpPagato',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercTotale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
							{name: 'NumPratiche', type: 'int'}];
							
				columns = [
			        	{dataIndex:'Lotto',width:80, header:'Lotto',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'Agenzia',width:80, header:'Agenzia',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			        		xtype:'numbercolumn',format:'0.000,00/i'},
			 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
		    	        	xtype:'numbercolumn',format:'0.000,00/i'},
		    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',filterable:true,sortable:true,summaryType:'sum',
			                xtype:'numbercolumn',format:'0.000,00/i'},
				        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
				        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
				        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiPerAgenzia2":
				groupedOn = 'Agenzia';
				fields = [{name: 'Agenzia'},{name: 'IdAgenzia', type: 'int'},{name: 'Lotto'},{name: 'DataFineAffido'},
							{name: 'NumInsoluti', type: 'int'},
							{name: 'ImpInsoluto', type: 'float'},
							{name: 'ImpPagato',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercTotale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
							{name: 'NumPratiche', type: 'int'}];
							
				columns = [
			        	{dataIndex:'Lotto',width:80, header:'Lotto',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'Agenzia',width:80, header:'Agenzia',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumPratiche',	width:40,	header:'Num. pratiche',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'NumInsoluti',	width:40,	header:'Num. insoluti',align:'right',sortable:true,filterable:true,summaryType:'sum'},
			        	{dataIndex:'ImpInsoluto', width:50,	header:'Importo totale',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
			        		xtype:'numbercolumn',format:'0.000,00/i'},
			 	        {dataIndex:'ImpCapitale', width:50,	header:'Importo rate',align:'right',filterable:true,sortable:true,groupable:false,summaryType:'sum',
		    	        	xtype:'numbercolumn',format:'0.000,00/i'},
		    	        {dataIndex:'ImpPagato',	  width:60,	header:'Importo recuperato',align:'right',filterable:true,sortable:true,summaryType:'sum',
			                xtype:'numbercolumn',format:'0.000,00/i'},
				        {dataIndex:'PercTotale', width:40,	align:'right',header:'% su debito',filterable:true,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
				        {dataIndex:'PercCapitale', width:40, align:'right',header:'% su rate',filterable:true,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentCapitale'}
				        ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'NumPratiche'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'NumInsoluti'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpInsoluto'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpCapitale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ImpPagato'
		       		}]
		    	});
				break;
			case "sintesiLavInterna":
				groupedOn = "";
				fields = [{name: 'mese'},{name: 'Lotto'},{name: 'DataFineAffido'},
									{name: 'numPraticheIn', type: 'int'},
									{name: 'debTotale', type: 'float'},
									{name: 'totRecuperato', type: 'float'},
									{name: 'ipr', type: 'float'},
									{name: 'numRatViaggio', type: 'int'},
									{name: 'debTotaleViaggio',type: 'float'},
									{name: 'totRecuperatoViaggio', type: 'float'},
									{name: 'iprViaggio', type: 'float'}];
				columns = [
				        	{dataIndex:'mese',			width:60, 	header:'Mese',align:'left',filterable: false, groupable:false, sortable:false},
				        	{dataIndex:'numPraticheIn',	width:80,	header:'Prat. in ingresso',	align:'right', 	filterable: true, sortable:true,groupable:false},
				        	{dataIndex:'debTotale',		width:80,	header:'Deb. totale',xtype:'numbercolumn',format:'0.000,00/i',align:'right',	filterable:true,sortable:true,groupable:false},
				        	{dataIndex:'totRecuperato',	width:80,	header:'Incassato',xtype:'numbercolumn',format:'0.000,00/i',align:'right',	filterable:true,sortable:true,groupable:false},
				        	{dataIndex:'ipr',			width:50,	header:'IPR',xtype:'numbercolumn',format:'000,00 %/i',		align:'right',	filterable:true,sortable:true,groupable:false},
				        	{dataIndex:'numRatViaggio',	width:80,	header:'Prat. viaggianti',	align:'right', 	filterable: true, sortable:true,groupable:false},
				        	{dataIndex:'debTotaleViaggio',		width:80,	header:'Tot. viaggianti',xtype:'numbercolumn',format:'0.000,00/i',align:'right',	filterable:true,sortable:true,groupable:false},
				        	{dataIndex:'totRecuperatoViaggio',	width:80,	header:'Inc. viaggianti',xtype:'numbercolumn',format:'0.000,00/i',align:'right',	filterable:true,sortable:true,groupable:false},
				        	{dataIndex:'iprViaggio',			width:50,	header:'IPR viaggianti',xtype:'numbercolumn',format:'000,00 %/i',align:'right',	filterable:true,sortable:true,groupable:false}
				          ];
				// filtri
				this.locFilters = new Ext.ux.grid.GridFilters({
		        	// encode and local configuration options defined previously for easier reuse
		        	encode: true, // json encode the filter query
		        	local: true,   // defaults to false (remote filtering)
		        	filters: [{
		            	type: 'numeric',
		            	dataIndex: 'numPraticheIn'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'debTotale'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'totRecuperato'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'ipr'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'numRatViaggio'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'debTotaleViaggio'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'totRecuperatoViaggio'
		       		}, {
		            	type: 'numeric',
		            	dataIndex: 'iprViaggio'
		       		}]
		    	});
				break;
			default: 
				groupedOn = '';
		}
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheSintesi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task},
			remoteSort: true,
			groupField: groupedOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
			,listeners: {load: DCS.hideMask} 
		});

		//Imposta la visibilit‡ delle colonne a seconda della configurazione effettuata sul submain
		columns = setColumnVisibility(columns);
		
		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			plugins: (this.locFilters==null?[summary]:[summary,this.locFilters]),
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showListaPratiche(rec.get('IdStatoRecupero'),rec.get('IdAgenzia'),rec.get('IdClasse'),rec.get('IdFamiglia'),rec.get('DataFineAffido'),
							rec.get('StatoRecupero'),rec.get('Agenzia'),rec.get('Classe'),rec.get('Prodotto'),rec.get('Lotto'));
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
	                '->', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("SintesiPratiche"),' '
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
		DCS.GridSintesiPratiche.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaPratiche: function(idstato, idagenzia, idclasse, idprodotto,idlotto, stato,agenzia,classe,prodotto,lotto)
    {
		DCS.showMask();
		// Compone il sottotitolo del pannello di dettaglio
		if (idstato)
			titolo = "Lista delle pratiche in stato '"+stato+"'";
		else if (idagenzia)
			if (idlotto)
				titolo = "Lotto pratiche affidate all'agenzia "+agenzia+" "+lotto.toLowerCase();
			else 	
				titolo = "Lista delle pratiche affidate all'agenzia "+agenzia;
		else if (idclasse>=0) 
			titolo = "Lista delle pratiche classificate come '"+classe+"'";
		else if (idprodotto) 
			titolo = "Lista delle pratiche relative al prodotto '"+prodotto+"'";
		else
			titolo = "Lista pratiche in lav. interna nel mese "+lotto;
		var pnl = new DCS.pnlSearch({stato: idstato, agenzia: idagenzia, classe: idclasse, prodotto: idprodotto, lotto:idlotto,
						titolo:titolo, IdC: 'PSintesi'});
		var win = new Ext.Window({
    		width: 1100, height:600, minWidth: 700, minHeight: 500,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Lista di dettaglio',
    		constrain: true,
			items: [pnl]
        });
    	win.show();
		pnl.activation.call(pnl);
    }

});

DCS.PraticheSintesi = function(tipo) {
	if (tipo==1) // sintesi normale (men√π Gestione pratiche)
	{
		var pnlSint =  new Ext.TabPanel({
	   			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: []
			});
		
		if(PraticheSintesiStato)
		{
			var gridSintesiPerStato = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiStato',
				stateful: true,
				titlePanel: 'Sintesi pratiche per stato (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per stato',
				task: "sintesiPerStato"
			});
			pnlSint.add(gridSintesiPerStato);
		}
		
		if(PraticheSintesiAgenzia)
		{
			var gridSintesiPerAgenzia = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiAgenzia',
				stateful: true,
				titlePanel: 'Sintesi pratiche per agenzia (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per agenzia',
				task: "sintesiPerAgenzia"
			});
			pnlSint.add(gridSintesiPerAgenzia);
		}		
		
		if(PraticheSintesiClassi)
		{
			var gridSintesiPerClasse = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiClassi',
				stateful: true,
				titlePanel: 'Sintesi pratiche per classificazione (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per classificazione',
				task: "sintesiPerClasse"
			});
			pnlSint.add(gridSintesiPerClasse);
		}			
		
		if(PraticheSintesiProd)
		{
			var gridSintesiPerProdotto = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiProd',
				stateful: true,
				titlePanel: 'Sintesi pratiche per famiglia di prodotto (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per prodotto',
				task: "sintesiPerProdotto"
			});
			pnlSint.add(gridSintesiPerProdotto);
		}				
		
		if(PraticheSintesiLavInt)
		{
			var gridSintesiLavInterna = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiLavInt',
				stateful: true,
				titlePanel: 'Sintesi pratiche in lavorazione interna (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'lavorazione interna',
				task: "sintesiLavInterna"
			});
			pnlSint.add(gridSintesiLavInterna);
		}					
		return pnlSint;
	}
	else // Sintesi degli affidamenti
	{
		var pnlAff = new Ext.TabPanel({
   			activeTab: 0,
			enableTabScroll: true,
			flex: 1,
			items: []
		});
		
		if(PraticheSintesiLotto)
		{
			var gridSintesiPerLotto = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiLotto',
				stateful: true,
				titlePanel: 'Sintesi pratiche per lotto di affidamento (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per lotto',
				task: "sintesiPerLotto"
			});
			pnlAff.add(gridSintesiPerLotto);
		}
		
		if(PraticheSintesiAffidoAg)
		{	
			var gridSintesiPerAgenzia2 = new DCS.GridSintesiPratiche({
				stateId: 'PraticheSintesiAffidoAg',
				stateful: true,
				titlePanel: 'Sintesi pratiche affidate per agenzia (doppio click su ciascuna riga per la lista di dettaglio)',
				title: 'per agenzia',
				task: "sintesiPerAgenzia2"
			});
			pnlAff.add(gridSintesiPerAgenzia2);
		}	
		return pnlAff;
	}
};
