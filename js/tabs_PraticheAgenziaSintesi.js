// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS');

DCS.GridSintesiPraticheAgenzia = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
		
	initComponent : function() {
    	var summary = new Ext.ux.grid.GroupSummary();
   	    var fields, columns;
		
 		switch (this.task)
		{
			case "sintesiPerAgente":
				groupOn = "Lotto";
		    	fields = [  {name: 'Lotto'},{name: 'IdAgente',type:'int'},{name: 'IdAgenzia',type:'int'},
		    	            {name: 'Agente'},
		    				{name: 'NumInsoluti',type:'int'},
		    				{name: 'Trattati',type:'int'},
		    				{name: 'DaTrattare',type:'int'},
		    				{name: 'ImpTotale', type: 'float'},
		    				{name: 'ImpPagatoTotale',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
		    				{name: 'NumAzioni',type: 'int'},
		    				{name: 'DataUltimaAzione', type: 'date', dateFormat:'Y-m-d H:i:s'},
		    				{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
		    				{name: 'DataFineAffido'}];
		    					
		    	columns = [ {dataIndex:'Lotto', width:70, header:'Lotto'},
		    	            {dataIndex:'Agente', width:75,	header:'Agente',filterable:true,groupable:true,sortable:true},
		    	        	{dataIndex:'NumInsoluti', width:60,	align:'right',header:'Num. pratiche',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'Trattati', width:50,	align:'right',header:'Lavorate',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'DaTrattare', width:50,	align:'right',header:'Da lavorare',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	 	        {dataIndex:'ImpCapitale', width:60,	header:'Capitale',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
			    	        	xtype:'numbercolumn',format:'0.000,00/i'},
			    	        {dataIndex:'ImpPagatoTotale',	  width:80,	header:'Recuperato',align:'right',sortable:true,summaryType:'sum',
		    	                xtype:'numbercolumn',format:'0.000,00/i'},
		    		        {dataIndex:'PercCapitale', width:50,	align:'right',header:'% recuperato',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
		   		    	    {dataIndex:'NumAzioni',	width:50, header:'Num. azioni',align:'right',sortable:true,summaryType:'sum'},
		    	        	{dataIndex:'DataUltimaAzione', width:70, xtype:'datecolumn', format:'d/m H:i', header:'Ultima azione'}
		    		      ];
				break;
			case "sintesiStoricaPerAgente":
				groupOn = "Anno";
		    	fields = [  {name: 'Anno'},{name: 'IdAgente',type:'int'},{name: 'IdAgenzia',type:'int'},
		    	            {name: 'Agente'},
		    				{name: 'NumInsoluti',type:'int'},
		    				{name: 'Trattati',type:'int'},
		    				{name: 'DaTrattare',type:'int'},
		    				{name: 'ImpTotale', type: 'float'},
		    				{name: 'ImpPagatoTotale',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
		    				{name: 'NumAzioni',type: 'int'}];
		    					
		    	columns = [ {dataIndex:'Anno', width:50, header:'Anno'},
		    	            {dataIndex:'Agente', width:75,	header:'Agente',filterable:true,groupable:true,sortable:true},
		    	        	{dataIndex:'NumInsoluti', width:60,	align:'right',header:'Num. pratiche',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'Trattati', width:50,	align:'right',header:'Lavorate',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'DaTrattare', width:50,	align:'right',header:'Da lavorare',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	 	        {dataIndex:'ImpCapitale', width:60,	header:'Capitale',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
			    	        	xtype:'numbercolumn',format:'0.000,00/i'},
			    	        {dataIndex:'ImpPagatoTotale',	  width:80,	header:'Recuperato',align:'right',sortable:true,summaryType:'sum',
		    	                xtype:'numbercolumn',format:'0.000,00/i', hidden:true},
		    		        {dataIndex:'PercCapitale', width:50,	align:'right',header:'% recuperato',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
		   		    	    {dataIndex:'NumAzioni',	width:50, header:'Num. azioni',align:'right',sortable:true,summaryType:'sum'}
		    		      ];
				break;
			case "sintesiVistaDaAgente":
				groupOn = "Anno";
		    	fields = [  {name: 'Anno'},{name: 'Lotto'},{name: 'IdAgente',type:'int'},{name: 'IdAgenzia',type:'int'},
		    				{name: 'NumInsoluti',type:'int'},
		    				{name: 'Trattati',type:'int'},
		    				{name: 'DaTrattare',type:'int'},
		    				{name: 'ImpTotale', type: 'float'},
		    				{name: 'ImpPagatoTotale',type: 'float'},
		    				{name: 'ImpCapitale',type: 'float'},
		    				{name: 'PercCapitale',type: 'float'},
		    				{name: 'NumAzioni',type: 'int'}];
		    					
		    	columns = [ {dataIndex:'Anno', width:50, header:'Anno'},
		    	            {dataIndex:'Lotto', width:70, header:'Lotto'},
		    	        	{dataIndex:'NumInsoluti', width:60,	align:'right',header:'Num. pratiche',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'Trattati', width:50,	align:'right',header:'Lavorate',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	        	{dataIndex:'DaTrattare', width:50,	align:'right',header:'Da lavorare',filterable:false,sortable:true,groupable:false,summaryType:'sum'},
		    	 	        {dataIndex:'ImpCapitale', width:60,	header:'Capitale',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
			    	        	xtype:'numbercolumn',format:'0.000,00/i'},
			    	        {dataIndex:'ImpPagatoTotale',	  width:80,	header:'Recuperato',align:'right',sortable:true,summaryType:'sum',
		    	                xtype:'numbercolumn',format:'0.000,00/i'},
		    		        {dataIndex:'PercCapitale', width:50,	align:'right',header:'% recuperato',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentTotale'},
		   		    	    {dataIndex:'NumAzioni',	width:50, header:'Num. azioni',align:'right',sortable:true,summaryType:'sum'},
		   		    	    {dataIndex:'DataUltimaAzione', width:70, xtype:'datecolumn', format:'d/m H:i', header:'Ultima azione'}
		   		    	    ];
				break;
				default: 
				groupOn = '';
		}
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheSintesi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task},
			remoteSort: true,
			groupField: groupOn,
			groupOnSort: false,
			remoteGroup: (groupOn>""),
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
			,listeners: {load: DCS.hideMask}
			});

		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
 			plugins: [summary],
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					if (this.task=='sintesiPerAgente')
						this.showListaPraticheAgente(rec.get('Agente'),rec.get('IdAgente'),rec.get('IdAgenzia'),rec.get('DataFineAffido'),rec.get('Lotto'));
					else if (this.task=='sintesiStoricaPerAgente')
						this.showListaPraticheAgente(rec.get('Agente'),rec.get('IdAgente'),rec.get('IdAgenzia'),null,'');
					else
						this.showListaPraticheStatoLav(rec.get('IdAgenzia'),rec.get('CodStato'),rec.get('TitoloStato'),rec.get('DataFineAffido'),rec.get('Lotto'));
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
	                '->', {type:'button',text: 'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("SintesiAgenzia"),' '
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
    // Visualizza dettaglio (lista per agente)
    //--------------------------------------------------------
	showListaPraticheAgente: function(Agente,IdAgente,IdAgenzia,DataFineAffido,Lotto)
    {
		var oggi = new Date();
		if (DataFineAffido<oggi.dateFormat('Y-m-d')) {	// Lotto non pi� affidato
			Ext.Msg.alert('Informazione', "Non &egrave; possibile visualizzare la lista perch&eacute; il periodo di affido del lotto &egrave; terminato.");
		} else if (DataFineAffido==null) {
			Ext.Msg.alert('Informazione', "Non &egrave; possibile visualizzare la lista di dettaglio sulla sintesi storica.");
		} else {
			var pnl = new DCS.pnlSearch({agente: IdAgente, agenzia:IdAgenzia, lotto: DataFineAffido, 
				titolo:'Lista pratiche assegnate all\'operatore '+ Agente + ' '+Lotto.toLowerCase(), IdC: 'PSintesiAgenzia'});
			var win = new Ext.Window({
	    		width: 1100, height:700, 
	    		autoHeight:true,modal: true,
	    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    	    title: 'Lista di dettaglio',
	    		constrain: true,
				items: [pnl]
	        });
	    	win.show();
			pnl.activation.call(pnl);
		}
    },

	//--------------------------------------------------------
    // Visualizza dettaglio (lista per stato)
    //--------------------------------------------------------
	showListaPraticheStatoLav: function(IdAgenzia,CodStato,TitoloStato,DataFineAffido,Lotto)
    {
		var oggi = new Date();
		if (DataFineAffido < oggi.dateFormat('Y-m-d')) { // Lotto non pi� affidato
			Ext.Msg.alert('Informazione', "Non &egrave; possibile visualizzare la lista perch&eacute; il periodo di affido del lotto &egrave; terminato.");
		}
		else {
			var pnl = new DCS.pnlSearch({
				agenzia: IdAgenzia,
				stato: CodStato,
				lotto: DataFineAffido,
				titolo: 'Lista pratiche ' + TitoloStato + ' nel lotto di affidamento ' + Lotto.toLowerCase(),
				IdC: 'PSintesiStato'
			});
			var win = new Ext.Window({
				width: 1100,
				height: 700,
				autoHeight: true,
				modal: true,
				layout: 'fit',
				plain: true,
				bodyStyle: 'padding:5px;',
				title: 'Lista di dettaglio',
				constrain: true,
				items: [pnl]
			});
			win.show();
			pnl.activation.call(pnl);
		}
    }

});

DCS.PraticheAgenziaSintesi = function(){

	return {
		create: function(){
			if (CONTEXT.READ_REPARTO) // pu� vedere il lavoro di tutti gli agenti
			{
				var grid1 = new DCS.GridSintesiPraticheAgenzia({
					stateId: 'PraticheSintesiAgAge',
					stateful: true,
					titlePanel: 'Sintesi affidi per agente',
					title: 'Sintesi affidi per agente',
					task: "sintesiPerAgente"
				});
				var grid2 = new DCS.GridSintesiPraticheAgenzia({
					stateId: 'PraticheSintesiAgStoria',
					stateful: true,
					titlePanel: 'Sintesi storica per agente',
					title: 'Sintesi storica per agente',
					task: "sintesiStoricaPerAgente"
				});
				return new Ext.TabPanel({
					activeTab: 0,
					enableTabScroll: true,
					flex: 1,
					items: [grid1,grid2]
				});
			}
			else // operatore di agenzia
			{
				var grid1 = new DCS.GridSintesiPraticheAgenzia({
					stateId: 'PraticheSintesiAgA',
					stateful: true,
					titlePanel: 'Sintesi pratiche per lotto',
					title: 'Sintesi per lotto di affidamento',
					task: "sintesiVistaDaAgente"
				});

				return new Ext.TabPanel({
					activeTab: 0,
					enableTabScroll: true,
					flex: 1,
					items: [grid1]
				});
			}
		}
	};
	
}();
