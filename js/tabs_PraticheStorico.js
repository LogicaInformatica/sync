// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheStorico = Ext.extend(DCS.GridPratiche, {
	isEmploye:false,
	
	initComponent : function() {
	
		var locFields,columns;
		
		if (this.isEmploye)  { // pratiche dei dipendenti
			var locFields = [	{name: 'IdCliente', type:'int'},
								{name: 'IdContratto', type:'int'},
								{name: 'CodAna'},
								{name: 'numPratica'},
								{name: 'Nominativo'},
								{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
								{name: 'NumNote', type: 'int'},
								{name: 'LastUpd', type:'date', dateFormat:'Y-m-d H:i:s'}
							];
					

				var columns = [ {dataIndex:'CodAna',width:45,header:'Codice',align:'left', filterable: true, sortable:true,groupable:false},
					        	{dataIndex:'numPratica',width:45,header:'Posiz.',align:'left', filterable: true, sortable:true,groupable:false},
					        	{dataIndex:'Nominativo',width:90,header:'Nome',filterable:false,sortable:true},
					        	{dataIndex:'LastUpd',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Ultima variaz.',align:'left', filterable: true, groupable:true, sortable:true}
 				        	  ];
				
		} else  { // pratiche normali
			locFields = [{name: 'IdContratto'},
							{name: 'prodotto'},
							{name: 'numPratica'},
							{name: 'IdCliente', type: 'int'},
							{name: 'cliente'},
							{name: 'AbbrStatoContratto'},
							{name: 'DataScadenza', type:'date'},
							{name: 'DataChiusura', type:'date'},
							{name: 'Telefono'},
							{name: 'CodiceFiscale'}, // solo in Export
							{name: 'Indirizzo'}, 	 // solo in Export
							{name: 'CAP'},           // solo in Export
							{name: 'Localita'},      // solo in Export
							{name: 'SiglaProvincia'},// solo in Export
							{name: 'TitoloRegione'},// solo in Export
							{name: 'CodRegolaProvvigione'}, // solo in Export
							{name: 'NumNote', type: 'int'},
							{name: 'TitoloAttributo'},
							{name: 'NumAllegati', type: 'int'},
							{name: 'Modello'},
							{name: 'Dealer'},
							{name: 'Filiale'},
							{name: 'DataLiquidazione', type:'date'},
							{name: 'ValoreBene', type: 'float'},
		
							{name: 'Finanziato', type: 'float'},
							{name: 'Anticipo', type: 'float'},
							{name: 'Erogato', type: 'float'},
							{name: 'Rata', type: 'float'},
							{name: 'RataFinale', type: 'float'},
							{name: 'Riscatto', type: 'float'},
							{name: 'Interessi', type: 'float'},
							{name: 'SpeseIncasso', type: 'float'},
							{name: 'Bollo', type: 'float'},
							{name: 'Tasso', type: 'float'},
							{name: 'Taeg', type: 'float'},
							{name: 'TassoReale', type: 'float'},
							{name: 'NumeroRate', type: 'int'},
							{name: 'InteressiDilazione', type: 'float'},
							{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
							{name: 'MesiDilazione', type: 'int'},
							{name: 'LastUpd', type:'date', dateFormat:'Y-m-d H:i:s'}
							];
			columns = [
			        	{dataIndex:'LastUpd',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Ultima variaz.',align:'left', filterable: true, groupable:true, sortable:true},
			        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
			        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
			        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
			        	{dataIndex:'AbbrStatoContratto', width:40,	header:'Stato Prat.',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
			        	{dataIndex:'TitoloAttributo'   , width:80, header:'Attributo', exportable:true,groupable:true},
			        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Filiale', width:110, header:'Filiale',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'DataLiquidazione',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Data liquidazione',align:'left',hidden:true,exportable:true,hideable:false},
			        	{dataIndex:'ValoreBene', width:70, header:'Valore bene',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Finanziato', width:70, header:'Finanziato',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Anticipo', width:70, header:'Anticipo',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Erogato', width:70, header:'Erogato',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Rata', width:70, header:'Rata',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'RataFinale', width:70, header:'Rata finale',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Riscatto', width:70, header:'Riscatto',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Interessi', width:70, header:'Interessi',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'SpeseIncasso', width:70, header:'Spese incasso',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Bollo', width:70, header:'Bollo',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Tasso', width:70, header:'Tasso',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Taeg', width:70, header:'Taeg',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'TassoReale', width:70, header:'Tasso reale',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'NumeroRate', width:50, header:'N. rate',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true}
			        	];
		}
			
			// filtri
			var locFilters = new Ext.ux.grid.GridFilters({
	        	// encode and local configuration options defined previously for easier reuse
	        	encode: true, // json encode the filter query
	        	local: true,   // defaults to false (remote filtering)
	        	filters: [{
	            	type: 'list',options:[],
	            	dataIndex: 'prodotto'
	        	}, {
	            	type: 'list',  options: [],
	            	dataIndex: 'stato'
	        	}]
	    	});
	
		Ext.apply(this,{
			fields: locFields,
			filters: locFilters,
			innerColumns: columns,
			isStorico: true,
			isEmploye: this.isEmploye
	    });
		
		this.on('render',function(){ 
			if (this.buttonAdded || this.isEmploye) return;
			this.buttonAdded = true;
			var dataDa  = {xtype: 'datefield',format: 'd/m/Y', allowBlank: true, id:'dataDa', width:80};
			var dataA   = {xtype: 'datefield',format: 'd/m/Y', allowBlank: true, id:'dataA', width: 80};

			var idObj=this.getId();
			var toolBar = Ext.getCmp(idObj).getTopToolbar();
			toolBar.insert(2,'Data ultima variazione dal &nbsp; ');
			toolBar.insert(3,dataDa);
			toolBar.insert(4,' &nbsp;al &nbsp; ');
			toolBar.insert(5,dataA);
			toolBar.insert(6,{type:'button', 
				              tooltip:'Ricarica la lista applicando l\'intervallo di date specificato', 
				              icon:'ext/resources/images/default/grid/refresh.gif', 
				              text: 'Ricarica',
				              handler: function(){ 
											var dataDa = Ext.getCmp("dataDa");
											dataDa = Ext.util.Format.date(dataDa.getValue(),'Y-m-d');
											var dataA = Ext.getCmp("dataA");
											dataA = Ext.util.Format.date(dataA.getValue(),'Y-m-d');
											var sqlExtraCondition = "DATE(LastUpd) BETWEEN '"+ dataDa + "' AND '" + dataA +"'";
											this.store.baseParams.sqlExtraCondition = sqlExtraCondition; // in modo che funzioni sul tasto di reload standard
											this.store.load({params:{task: 'storico', 
																	 sqlExtraCondition : sqlExtraCondition,
																	 start: 0,
																	 limit: this.pagesize
													   				}
															}); 
										},
				              scope: this});
			toolBar.insert(7,'-');
			toolBar.doLayout();
		});


		DCS.GridPraticheStorico.superclass.initComponent.call(this,arguments);
	}
});
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.PraticheStorico = function() {
	//var idTabs;

	return {
				
		create: function(){
			DCS.showMask();
			var tabPanelStorico = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabStorico',
				items: []
			});
			// Crea due tab (versione dal 9/1/2014)
			// primo tab: clienti, inizialmente vuoto
			var gridCli = new DCS.GridPraticheStorico({
				task: "storico",
				title: "Pratiche TFSI",
				titlePanel: 'Lista pratiche TFSI storicizzate (selezionare le date)',
				stateful: true,
				stateId: 'StoricoTabsCli'
				});
			
			// secondo tab: TKGI dipendenti, include tutti
			var gridDip = new DCS.GridPraticheStorico({
				task: "storicodip",
				isEmploye: true,
				title: "Pratiche TKGI dipendenti",
				titlePanel: 'Lista pratiche TKGI dipendenti storicizzate',
				stateful: true,
				stateId: 'StoricoTabsDip'
				});
			
			Ext.getCmp('tabStorico').add(new Array(gridCli,gridDip));
			Ext.getCmp('tabStorico').setActiveTab(0);
			
			return tabPanelStorico;
		}
	};
}();