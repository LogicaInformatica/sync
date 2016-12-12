// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheEstinte = Ext.extend(DCS.GridPratiche, {
	anno:'',
	
	initComponent : function() {
	
		var locFields = [{name: 'IdContratto'},
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
							{name: 'ImpPagato', type: 'float'},
							{name: 'ImpCapitale', type: 'float'},
							{name: 'AbbrStatoContratto'},
							{name: 'agenzia'},
							{name: 'CodUtente'},
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
							{name: 'ListaGaranti'}, // solo in Export
							{name: 'UltimaAzione'}, // solo in Export
							{name: 'DataUltimaAzione'}, // solo in Export
							{name: 'UtenteUltimaAzione'}, // solo in Export
							{name: 'NotaEvento'}, // solo in Export
							{name: 'Garanzie'}, // solo in Export
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
							{name: 'MesiDilazione', type: 'int'}							
							];
	
			var columns;
								
			/*if(this.task=="workflow"){
				Ext.apply(this,{
					grpField: 'AbbrStatoRecupero',
					grpDir: 'desc'
		    	});
			}*/
			
			var columns = [
			        	{dataIndex:'DataChiusura',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Data estinzione',align:'left', filterable: true, groupable:true, sortable:true},
			        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
			        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
			        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
			        	{dataIndex:'AbbrStatoContratto', width:40,	header:'Stato Prat.',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
			        	{dataIndex:'TitoloAttributo'   , width:80, header:'Attributo', exportable:true,groupable:true},
			        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
			        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true},
			        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
			        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
			        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
			        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
			        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
			        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
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
			        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
			        	];
			
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
	        	}, {
	            	type: 'string',
	            	dataIndex: 'agenzia'
	        	}, {
	            	type: 'numeric',
	            	dataIndex: 'importo'
	       		}]
	    	});
	
		Ext.apply(this,{
			fields: locFields,
			filters: locFilters,
			innerColumns: columns,
			anno:this.anno
	    });

		DCS.GridPraticheEstinte.superclass.initComponent.call(this,arguments);
	}
});
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.PraticheEstinte = function() {
	//var idTabs;

	return {
				
		create: function(){
			DCS.showMask();
			var tabPanelEst = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabEst',
				items: []
			});
			// Crea un tab per ciascun anno di chiusura
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: "select year(datachiusura) as Anno,count(*) AS cnt from v_insoluti_estinti_count GROUP BY year(datachiusura)"
						 +" UNION ALL SELECT 0,COUNT(*) FROM v_insoluti_estinti_count ORDER BY 1" 
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var resp = ' + result.responseText);
					var arr = resp.results;
					var nomeG='';
					var listP = new Array();
					var grid = new Array();
					
					for (i = 0; i < resp.total; i++) {
						nomeG="EstinteTabs"+i;
						var subtitle = arr[i].Anno>0?arr[i].Anno:"Tutte";
						grid[nomeG] = new DCS.GridPraticheEstinte({
										anno:arr[i].Anno,
										task: "estinte",
										title: subtitle + " (" + arr[i].cnt + ")",
										titlePanel: 'Lista pratiche estinte con debito - '+subtitle,
										stateful: true,
										stateId: 'EstinteTabs'+arr[i].Anno
										});
						listP.push(grid[nomeG]);
					}
					Ext.getCmp('tabEst').add(listP);
					DCS.hideMask();
					Ext.getCmp('tabEst').setActiveTab(0);
				},
				failure: function ( result, request) { 
					DCS.hideMask();
					eval('var resp = '+result.responseText);
					Ext.MessageBox.alert('Failed', resp.results); 
				},
				scope: this
			});
			
			return tabPanelEst;
		}
	};
}();