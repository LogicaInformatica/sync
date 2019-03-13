// Sintesi delle provvigioni

DCS.GridProvvigioni = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	agenz:'',
	idAgenz:'',
	tipoProvv:'',
		
	initComponent : function() {
    	var summary = new Ext.ux.grid.GroupSummary();
    	var fields, columns;
		switch (this.task)
		{
			case "sintesiPerLotto":
			case "sintesiPerLotto2":
				groupedOn = 'Lotto';
				fields = [{name: 'IdProvvigione'},
				          {name: 'Agenzia'},
				          {name: 'Lotto'},{name: 'DataFineAffido'},
						  {name: 'NumAffidati', type: 'int'},
						  {name: 'NumIncassati', type: 'int'},
						  {name: 'ImpCapitaleAffidato', type: 'float'},
						  {name: 'ImpCapitaleIncassato', type: 'float'},
						  {name: 'ImpCapitaleRealeIncassato', type: 'float'},
						  {name: 'ImpInteressiDiMora', type: 'float'},
						  {name: 'ImpSpeseRecupero', type: 'float'},
						  {name: 'ImpAltroAffidato', type: 'float'},
						  {name: 'ImpProvvigione', type: 'float'},
						  {name: 'ImpBonus', type: 'float'},
						  {name: 'IPM', type: 'float'},
						  {name: 'IPR', type: 'float'},
						  {name: 'IPF', type: 'float'},
						  {name: 'CodRegolaProvvigione'},
						  {name: 'TipoProvvigione', type:'int'},
						  {name: 'Stato'},
						  {name: 'UltimaElaborazione'},
						  {name: 'DescrFormula'}
						 ];
							
				columns = [
			        	{dataIndex:'Lotto',width:80, header:'Lotto',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'Agenzia',width:67, header:'Agenzia',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'CodRegolaProvvigione',width:40, header:'Cod.',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'NumAffidati',	width:57,	header:'Affidi',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'NumIncassati',	width:66,	header:'Incassi',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'ImpCapitaleAffidato', width:81,	header:'Cap. aff.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
			        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpAltroAffidato', width:71,	header:'Altri deb.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
				        		xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpCapitaleIncassato', width:91,	header:'Cap. inc.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
				        		xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpCapitaleRealeIncassato', width:88,	header:'Cap.reale inc.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
					        	xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpInteressiDiMora', width:78,	header:'Int. mora',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
					        	xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Interessi di mora incassati'},
			        	{dataIndex:'ImpSpeseRecupero', width:76,	header:'Spese rec.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
					        	xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Spese di recupero incassate'},
						{dataIndex:'IPM', width:43,	align:'right',header:'IPM',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPM'},
				        {dataIndex:'IPR', width:43, align:'right',header:'IPR',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPR'},
				        {dataIndex:'IPF', width:43, align:'right',header:'IPF',filterable:false,sortable:true,groupable:false,
			    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPF'},
			        	{dataIndex:'ImpProvvigione', width:78,	header:'Provvigione',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
						        	xtype:'numbercolumn',format:'0.000,00/i'},
						{dataIndex:'ImpBonus', width:78,	header:'Bonus',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
							        xtype:'numbercolumn',format:'0.000,00/i'},
			    	    {dataIndex:'DescrFormula',width:65, header:'Formula',filterable:true,groupable:true,sortable:true},
			    	    {dataIndex:'Stato',width:63, header:(this.tipoProvv==1?'Stato lotto':'Mese'),filterable:true,groupable:true,sortable:true},
			    	    {dataIndex:'UltimaElaborazione',width:75, header:'Ultima elab.',filterable:true,groupable:true,sortable:true},
						{
							xtype: 'actioncolumn',
							id: 'azioneColumn',
							printable: false,
							header: 'Azioni',
							sortable: false,
							align: 'left',
							resizable: true,
							filterable: false,
							width: 70,
							menuDisabled: true,
							hidden: CONTEXT.InternoEsterno=='E' || !CONTEXT.RICALCPROVV,
							items: [{
			    		               icon   : 'images/arrow_redo.png',               
					                   tooltip: 'Ricalcola',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("ricalcolaProvvigione",rec);
					                   }
	        		                 },
									 {
			    		               icon   : 'images/refresh.gif',               
					                   tooltip: 'Cambia stato',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("cambiaStato",rec);
					                   }
	        		                 },
									 {
				    		               icon   : 'images/export.png',               
						                   tooltip: 'Produce file CERVED',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileCerved",rec);
						                   }
		        		             },
									 {
				    		               icon   : 'images/exportAci.png',               
						                   tooltip: 'Produce file ACI',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileAci",rec);
						                   }
		        		             }]
						}
			    	    ];
				break;

			case "sintesiMeseRine":
			case "sintesiMeseRineAgenzia":
			case "sintesiUnAgenziaRine":
				groupedOn = 'Lotto';
				fields = [{name: 'IdProvvigione'},
				          {name: 'Agenzia'},
				          {name: 'Lotto'},{name: 'DataFineAffido'},
						  {name: 'NumAffidati', type: 'int'},
						  {name: 'NumIncassati', type: 'int'},
						  {name: 'NumRiconosciuti', type: 'int'},
						  {name: 'ImpCapitaleAffidato', type: 'float'},
						  {name: 'ImpCapitaleIncassato', type: 'float'},
						  {name: 'ImpCapitaleRealeIncassato', type: 'float'},
						  {name: 'ImpInteressiDiMora', type: 'float'},
						  {name: 'ImpSpeseRecupero', type: 'float'},
						  {name: 'ImpAltroAffidato', type: 'float'},
						  {name: 'ImpProvvigione', type: 'float'},
						  {name: 'ImpBonus', type: 'float'},
						  {name: 'IPM', type: 'float'},
						  {name: 'IPR', type: 'float'},
						  {name: 'IPF', type: 'float'},
						  {name: 'CodRegolaProvvigione'},
						  {name: 'TipoProvvigione', type:'int'},
						  {name: 'Stato'},
						  {name: 'UltimaElaborazione'},
						  {name: 'DescrFormula'}
						 ];
							
				columns = [
			        	{dataIndex:'Lotto',width:80, header:'Lotto',filterable:true,groupable:true,sortable:true},
			        	{dataIndex:'Agenzia',width:67, header:'Agenzia',filterable:true,groupable:true,sortable:true
			        			,hidden:(this.task=='sintesiMeseRineAgenzia')},
			        	{dataIndex:'CodRegolaProvvigione',width:40, header:'Cod.',filterable:true,groupable:true,sortable:true
			        				,hidden:(this.task=='sintesiMeseRineAgenzia')},
			        	{dataIndex:'NumAffidati',	width:57,	header:'Affidi',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'NumRiconosciuti',	width:66,	header:'Rinegoziati',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'ImpCapitaleAffidato', width:81,	header:'Cap. aff.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
			        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpAltroAffidato', width:71,	header:'Altri deb.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
				        		xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpProvvigione', width:78,	header:'Provvigione',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
						        	xtype:'numbercolumn',format:'0.000,00/i'},
			    	    {dataIndex:'DescrFormula',width:65, header:'Formula',filterable:true,groupable:true,sortable:true},
			    	    {dataIndex:'Stato',width:63, header:(this.tipoProvv==1?'Stato lotto':'Mese'),filterable:true,groupable:true,sortable:true},
			    	    {dataIndex:'UltimaElaborazione',width:75, header:'Ultima elab.',filterable:true,groupable:true,sortable:true},
						{
							xtype: 'actioncolumn',
							id: 'azioneColumn',
							printable: false,
							header: 'Azioni',
							sortable: false,
							align: 'left',
							resizable: true,
							filterable: false,
							width: 70,
							menuDisabled: true,
							hidden: CONTEXT.InternoEsterno=='E' || !CONTEXT.RICALCPROVV,
							items: [{
			    		               icon   : 'images/arrow_redo.png',               
					                   tooltip: 'Ricalcola',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("ricalcolaProvvigione",rec);
					                   }
	        		                 },
									 {
			    		               icon   : 'images/refresh.gif',               
					                   tooltip: 'Cambia stato',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("cambiaStato",rec);
					                   }
	        		                 },
									 {
				    		               icon   : 'images/export.png',               
						                   tooltip: 'Produce file CERVED',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileCerved",rec);
						                   }
		        		             },
									 {
				    		               icon   : 'images/exportAci.png',               
						                   tooltip: 'Produce file ACI',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileAci",rec);
						                   }
		        		             }]
						}
			    	    ];
				break;

			case "sintesiPerLAgenzia":
			case "sintesiUnAgenzia":
				groupedOn = 'Stato';
				fields = [{name: 'IdProvvigione'},{name: 'Agenzia'},
				          {name: 'Lotto'},{name: 'DataFineAffido'},
						  {name: 'NumAffidati', type: 'int'},
						  {name: 'NumIncassati', type: 'int'},
						  {name: 'ImpCapitaleAffidato', type: 'float'},
						  {name: 'ImpCapitaleIncassato', type: 'float'},
						  {name: 'ImpCapitaleRealeIncassato', type: 'float'},
						  {name: 'ImpInteressiDiMora', type: 'float'},
						  {name: 'ImpSpeseRecupero', type: 'float'},
						  {name: 'ImpAltroAffidato', type: 'float'},
						  {name: 'IPM', type: 'float'},
						  {name: 'IPR', type: 'float'},
						  {name: 'IPF', type: 'float'},
						  {name: 'ImpProvvigione', type: 'float'},
						  {name: 'ImpBonus', type: 'float'},
						  {name: 'CodRegolaProvvigione'},
						  {name: 'TipoProvvigione', type:'int'},
						  {name: 'Stato'},
						  {name: 'UltimaElaborazione'},
						  {name: 'DescrFormula'}
						 ];
							
				columns = [
				        	{dataIndex:'Lotto',width:80, header:'Lotto',filterable:true,groupable:true,sortable:true},
				        	{dataIndex:'CodRegolaProvvigione',width:39, header:'Codice',filterable:true,groupable:true,sortable:true},
				        	{dataIndex:'NumAffidati',	width:48,	header:'Affidi',align:'right',sortable:true,summaryType:'sum'},
				        	{dataIndex:'NumIncassati',	width:52,	header:'Incassi',align:'right',sortable:true,summaryType:'sum'},
				        	{dataIndex:'ImpCapitaleAffidato', width:65,	header:'Cap. affidato',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
				        			xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'ImpAltroAffidato', width:55,	header:'Altri debiti',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
					        		xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'ImpCapitaleIncassato', width:75,	header:'Cap. incassato',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
					        		xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'ImpCapitaleRealeIncassato', width:75,	header:'Cap. reale inc.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
						        	xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'ImpInteressiDiMora', width:60,	header:'Int. mora',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
						        	xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Interessi di mora incassati'},
				        	{dataIndex:'ImpSpeseRecupero', width:56,	header:'Spese rec.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
						        	xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Spese di recupero incassate'},
						    {dataIndex:'IPM', width:40,	align:'right',header:'IPM',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPM'},
					        {dataIndex:'IPR', width:40, align:'right',header:'IPR',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPR'},
					        {dataIndex:'IPF', width:40, align:'right',header:'IPF',filterable:false,sortable:true,groupable:false,
				    	        	xtype:'numbercolumn',format:'000,00 %/i',summaryType:'percentIPF'},
				        	{dataIndex:'ImpProvvigione', width:62,	header:'Provvigione',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
							        	xtype:'numbercolumn',format:'0.000,00/i'},
							{dataIndex:'ImpBonus', width:78,	header:'Bonus',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
									        xtype:'numbercolumn',format:'0.000,00/i'},
				    	    {dataIndex:'DescrFormula',width:60, header:'Formula',filterable:true,groupable:true,sortable:true},
				    	    {dataIndex:'Stato',width:50, header:(this.tipoProvv==1?'Stato lotto':'Mese'),filterable:true,groupable:true,sortable:true},
				    	    {dataIndex:'UltimaElaborazione',width:75, header:'Ultima elab.',filterable:true,groupable:true,sortable:true},
							
							{
    		                  xtype: 'actioncolumn',
							  id: 'azioneColumn',
    		                  printable: false,
    		                  header:'Azioni',
    		                  sortable:false, 
    		                  align:'left',
    		                  resizable: true,
    		                  filterable:false,
    		                  width: 70,
    		                  menuDisabled: true,
							  hidden: CONTEXT.InternoEsterno=='E' || !CONTEXT.RICALCPROVV,
							  items:[{
			    		               icon   : 'images/arrow_redo.png',               
					                   tooltip: 'Ricalcola',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
					                            grid.azione("ricalcolaProvvigione",rec);
					                   }
	        		                 },
									 {
			    		               icon   : 'images/refresh.gif',               
					                   tooltip: 'Cambia stato',
					                   handler: function(grid, rowIndex, colIndex) {
					                            var rec = grid.gstore.getAt(rowIndex);
												grid.azione("cambiaStato",rec);
					                   }
	        		                 },
									 {
				    		               icon   : 'images/export.png',               
						                   tooltip: 'Produce file CERVED',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileCerved",rec);
						                   }
		        		             },
									 {
				    		               icon   : 'images/exportAci.png',               
						                   tooltip: 'Produce file ACI',
						                   handler: function(grid, rowIndex, colIndex) {
						                            var rec = grid.gstore.getAt(rowIndex);
						                            grid.azione("fileAci",rec);
						                   }
		        		             }]
							}
			    	    ];
				break;

			default: 
				groupedOn = '';
		}
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/provvigioni.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, IdAgenzia: this.idAgenz, tipo: this.tipoProvv},
			remoteSort: true,
			groupField: groupedOn,
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
			agenzia: this.agenz,
			idAgenz: this.idAgenz,
			thisProvv: this.thisProvv,
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
					this.showDettaglioProvvigione(rec.json.TipoProvvigione,rec.json.IdProvvigione,rec.json.Agenzia,rec.json.CodRegolaProvvigione
							,rec.json.Lotto);
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
	                '-', helpButton("ListaProvvigioni"),' '
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
		DCS.GridProvvigioni.superclass.initComponent.call(this, arguments);

	},
	
	azione: function(task,rec)
	{
		if (!CONTEXT.RICALCPROVV)
			Ext.MessageBox.alert('Operazione non consentita', 'Non sei autorizzato ad eseguire questa azione');
		else
		{
   	   		var idProvvigione = rec.get("IdProvvigione");
   	   		if(task=='cambiaStato'){
   	   			Ext.Ajax.request({
   	   				url : 'server/AjaxRequest.php' , 
   	   				params : {task: 'read',sql: "SELECT StatoProvvigione as stato FROM provvigione WHERE IdProvvigione='"+idProvvigione+"'"},
   	   				method: 'POST',
   	   				autoload:true,
   	   				success: function ( result, request ) {
   	   					var jsonData = Ext.util.JSON.decode(result.responseText);
   	   					var stato=jsonData.results[0] ['stato'];
   	   					if(stato!=0){
   	   						showAnswFormProvvigione(task,"cambio di stato", idProvvigione, this.gstore);
   	   					} else{
   	   						Ext.MessageBox.alert('Non consentito', "Il cambio stato non pu� essere effettuato su un periodo in corso");
   	   					}
   	   				},
   	   				failure: function ( result, request) { 
   	   					Ext.MessageBox.alert('Errore', result.responseText); 
   	   				},
   	   				scope:this
   	   			}); 		 									
   	   		} else {
   	   			showAnswFormProvvigione(task, task=='fileCerved'?'creazione file CERVED':(task=='fileAci'?'creazione file ACI':'ricalcolo') ,idProvvigione, this.gstore);
   	   		}
		}
	},	
	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showDettaglioProvvigione: function(tipoProvv,idprovvigione, agenzia, codregola,lotto)
    {
		// Compone il sottotitolo del pannello di dettaglio
		titolo = "Dettaglio provvigioni - Agenzia "+agenzia+' ('+codregola+') lotto: '+lotto;
		var pnl = new DCS.pnlSearch({stato: idprovvigione, titolo:titolo, IdC: tipoProvv==4?'ProvvigioniSingole':'Provvigioni'});
		pnl.lotto = lotto;
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

});

/*DCS.Provvigioni = 
	{
		create: function(tipo) 
			{
				var grid1 = new DCS.GridProvvigioni({
					titlePanel: 'Sintesi provvigioni',
					title: 'Tutte',
					task: "sintesiPerLotto"
				});
				return new Ext.TabPanel({
					activeTab: 0,
					enableTabScroll: true,
					flex: 1,
					items: [grid1]
					});
			}
	};*/
DCS.Provvigioni = function(){

	return {
		myTipo: 1,
		create: function(tipo){
			this.myTipo = tipo;
			var TabPanelProv =  new Ext.TabPanel({
				activeTab: 0,
				id: 'TabPanelProv'+tipo,
				enableTabScroll: true,
				flex: 1,
				items: []});
			var grid1,grid2;
			
			if (CONTEXT.InternoEsterno=='E')
			{
				if (tipo==4) // provvigioni per rinegoziazione
					grid1 = new DCS.GridProvvigioni({
						stateId: 'Provvigioni'+tipo,
						stateful: true,
						titlePanel: 'Sintesi provvigioni di rinegoziazione (doppio click per il dettaglio di ogni elemento)',
						title: 'Sintesi per mese',
						task: "sintesiMeseRineAgenzia",
						tipoProvv: tipo
					});
				else
					grid1 = new DCS.GridProvvigioni({
						stateId: 'Provvigioni',
						stateful: true,
						titlePanel: '(Doppio click per visualizzare il dettaglio di ogni elemento)',
						title: 'Sintesi per stato di completamento e data del lotto',
						task: "sintesiPerLAgenzia",
						tipoProvv: tipo
					});
				TabPanelProv.add(grid1);
			}
			else
			{
				switch (tipo)
				{
					case 1: // provvigioni pre-DBT (esattoriali per lotto)
						grid1 = new DCS.GridProvvigioni({
							stateId: 'Provvigioni'+tipo,
							stateful: true,
							titlePanel: 'Sintesi provvigioni pre-DBT (doppio click per il dettaglio di ogni elemento)',
							title: 'Tutte',
							task: "sintesiPerLotto",
							tipoProvv: tipo
						});
						TabPanelProv.add(grid1);
						break;
					case 2: // provvigioni STR due tipi di sintesi
						grid1 = new DCS.GridProvvigioni({
							stateId: 'Provvigioni1'+tipo,
							stateful: true,
							titlePanel: 'Sintesi per mese (doppio click per il dettaglio di ogni elemento)',
							title: 'Sintesi per mese',
							task: "sintesiPerLotto",
							tipoProvv: tipo
						});
						// seconda lista, usata solo per le provvigioni STR/LEG organizzate per lotto vero
						grid2 = new DCS.GridProvvigioni({
							stateId: 'Provvigioni2'+tipo,
							stateful: true,
							titlePanel: 'Sintesi per lotto (doppio click per il dettaglio di ogni elemento)',
							title: 'Sintesi per lotto',
							task: "sintesiPerLotto2",
							tipoProvv: tipo
						});
						TabPanelProv.add(grid1);
						TabPanelProv.add(grid2);
						break;
					case 3: // tipo=3 legali 
						grid1 = new DCS.GridProvvigioni({
							stateId: 'Provvigioni1'+tipo,
							stateful: true,
							titlePanel: 'Sintesi per mese (doppio click per il dettaglio di ogni elemento)',
							title: 'Sintesi per mese',
							task: "sintesiPerLotto",
							tipoProvv: tipo
						});
						TabPanelProv.add(grid1);
						break;
					case 4: // provvigioni per rinegoziazione
						grid1 = new DCS.GridProvvigioni({
							stateId: 'Provvigioni'+tipo,
							stateful: true,
							titlePanel: 'Sintesi provvigioni di rinegoziazione (doppio click per il dettaglio di ogni elemento)',
							title: 'Tutte',
							task: "sintesiMeseRine",
							tipoProvv: tipo
						});
						TabPanelProv.add(grid1);
						break;
				}
				DCS.showMask();
				Ext.Ajax.request({
					url : 'server/AjaxRequest.php' , 
					params : {
						task: 'read',
						sql: "SELECT IdReparto,Agenzia,Ordine FROM v_tabs_provvigioni WHERE tipo="+tipo+" ORDER BY 3,2"},
					method: 'POST',
					reader:  new Ext.data.JsonReader(
		    				{
		    					root: 'results',//name of the property that is container for an Array of row objects
		    					id: 'IdReparto'//the property within each row object that provides an ID for the record (optional)
		    				},
		    				[{name: 'IdReparto'},
		    				{name: 'Agenzia'}]
		    		),
					autoload:true,
					success: function ( result, request ) {
						eval('var arr = ('+result.responseText+').results');
						eval('var resp = ('+result.responseText+').total');
						var grid = new Array();
						var nomeG='';
						var listG = new Array();
						for(i=0;i<resp;i++){
							//console.log("arr titolo "+arr[i] ['titoloufficio']+" | arr ida "+arr[i] ['idAgenzia']);
							nomeG = "gridN"+i; 
							//console.log("Nome: "+nomeG);
							grid[nomeG] = new DCS.GridProvvigioni({
								stateId: 'ProvvigioniUnAgenzia'+tipo,
								stateful: true,
								titlePanel: 'Sintesi provvigioni dell\' agenzia '+arr[i]['Agenzia']+' (doppio click per il dettaglio di ogni elemento)',
								title: arr[i] ['Agenzia'],
								task: tipo==4?"sintesiUnAgenziaRine":"sintesiUnAgenzia",
								agenz: arr[i]['Agenzia'],
								idAgenz: arr[i]['IdReparto'],
								hideStato: true,
								tipoProvv: tipo
							});
							//Ext.getCmp('TabPanelAg').add(grid[nomeG]);
							//console.log("G: "+grid[nomeG].titlePanel);
							listG.push(grid[nomeG]);
							//console.log("l "+listG[i].titlePanel);
						}
						Ext.getCmp('TabPanelProv'+tipo).add(listG);
						DCS.hideMask();
						Ext.getCmp('TabPanelProv'+tipo).setActiveTab(0);
					},
					failure: function ( result, request) { 
						DCS.hideMask();
						Ext.MessageBox.alert('Errore', result.statusText);  
					},
					scope:this
				});
			}				
			return TabPanelProv;
		}
	};
	
}();