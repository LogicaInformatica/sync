// Crea namespace DCS
Ext.namespace('DCS');
//
// Griglia delle pratiche stragiudiziali e legali
//
DCS.GridPraticheCessWO = Ext.extend(DCS.GridPratiche, {

	dsClassi:'',
	dsTipoPagamento:'',
	dsAgenzia:'',
	initComponent : function() {

				var locFields = [{name: 'IdContratto'},
					{name: 'prodotto'},
					{name: 'numPratica'},
					{name: 'IdCliente', type: 'int'},
					{name: 'cliente'},{name: 'CodCliente'},
					{name: 'rata', type: 'int'},
					{name: 'insoluti',type: 'int'},
					{name: 'giorni', type: 'int'},
					{name: 'importo', type: 'float'},
					{name: 'ImpCapitaleAffidato', type: 'float'},
					{name: 'ImpInteressiMora', type: 'float'},
					{name: 'ImpSpeseRecupero', type: 'float'},
					{name: 'ImpPagato', type: 'float'},
					{name: 'ImpCapitale', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'CodUtente'},
					{name: 'DataScadenza', type:'date'},
					{name: 'MeseCambioStato', type:'date'},
					{name: 'DataCambioStato', type:'date'},
					{name: 'DataCambioClasse', type:'date'},
					{name: 'DataScadenzaAzione', type:'date'},
					{name: 'Telefono'},
					{name: 'CodiceFiscale'}, // solo in Export
					{name: 'Indirizzo'}, 	 // solo in Export
					{name: 'CAP'},           // solo in Export
					{name: 'Localita'},      // solo in Export
					{name: 'SiglaProvincia'},// solo in Export
					{name: 'TitoloRegione'},// solo in Export
					{name: 'CodRegolaProvvigione'}, // solo in Export
					{name: 'NumNote', type: 'int'},
					{name: 'Categoria'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'ListaRate'},
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
					{name: 'MesiDilazione', type: 'int'},
					{name: 'ProssimaAgenzia'},
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'StatoInDBT'}];

		var columns;

		columns = [
				{dataIndex:'MeseCambioStato',header:'Mese', width:70, hidden: false, hideable: false, align:'left', groupable:true, sortable:true, renderer:DCS.render.meseAnno},
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true,summaryType:'sum'},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,summaryType:'sum'},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false,summaryType:'sum'},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,summaryType:'sum'},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true,summaryType:'sum'},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true,summaryType:'sum'},
	        	{dataIndex:'DataScadenza',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Scad.',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Categoria'    ,   width:30, header:'Categoria', hidden:true,hideable:true,exportable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
	        		hidden:(this.task=='ATSNULL' || this.task=='ATSSTR' || this.task=='ATSLEG')},
	        	{dataIndex:'ProssimaAgenzia',	width:50,	header:'Prossimo affido',filterable:true,sortable:true,groupable:true,
		        		hidden:(this.task!='ATSSTR' && this.task!='ATSLEG')},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'DataLiquidazione',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Data liquidazione',align:'left',hidden:true,exportable:true,hideable:false,stateful:false},
	        	{dataIndex:'ValoreBene', width:70, header:'Valore bene',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Finanziato', width:70, header:'Finanziato',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Anticipo', width:70, header:'Anticipo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Erogato', width:70, header:'Erogato',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Rata', width:70, header:'Rata',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'RataFinale', width:70, header:'Rata finale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Riscatto', width:70, header:'Riscatto',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Interessi', width:70, header:'Interessi',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SpeseIncasso', width:70, header:'Spese incasso',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Bollo', width:70, header:'Bollo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Tasso', width:70, header:'Tasso',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Taeg', width:70, header:'Taeg',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TassoReale', width:70, header:'Tasso reale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'NumeroRate', width:50, header:'N. rate',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'InteressiDilazione', width:70, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'MesiDilazione', width:50, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'StatoInDBT', width:100, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}];
		
		var locFilters = new Ext.ux.grid.GridFilters({
        	// encode and local configuration options defined previously for easier reuse
        	encode: true, // json encode the filter query
        	local: false,   // defaults to false (remote filtering)
        	filters: [{
            	type: 'date',
            	dataIndex: 'DataCambioStato'
        	}, {
            	type: 'list',options:['1','2','3'],
            	dataIndex: 'prodotto'
        	}, {
            	type: 'list',  options: [this.dsClassi],
            	dataIndex: 'AbbrClasse'
        	}, {
            	type: 'list',  options: [this.dsTipoPagamento],
            	dataIndex: 'tipoPag'
        	}, {
            	type: 'list',  options: [this.dsAgenzia],
            	dataIndex: 'agenzia'
        	}, {
            	type: 'numeric',
            	dataIndex: 'importo'
       		}, {
            	type: 'numeric',
            	dataIndex: 'giorni'
       		}]
       	});

		Ext.apply(this,{
			fields: locFields,
			filters: locFilters,
			innerColumns: columns,
			summary: new Ext.ux.grid.GroupSummary()
	    });
		
		DCS.GridPraticheCessWO.superclass.initComponent.call(this, arguments);
	}
});

DCS.PraticheCessWO = function(){

	return {
		create: function(){
			DCS.showMask();
			var tp=new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: []
			});			
			
			//definizione store degli elementi liste filtri
			var sqlClassCmb="SELECT IdClasse as id,AbbrClasse as text FROM classificazione";
			var dsClassi = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: sqlClassCmb
				},
				reader:  new Ext.data.JsonReader(
					{
						root: 'results',//name of the property that is container for an Array of row objects
						id: 'id'//the property within each row object that provides an ID for the record (optional)
					},
					[{name: 'id', type: 'int'},
					{name: 'text'}]
				)
			});
			
			var sqlTpagCmb="select IdTipoPagamento as id,CodTipoPagamento as text from tipopagamento";
			var dsTipoPagamento = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: sqlTpagCmb
				},
				reader:  new Ext.data.JsonReader(
					{
						root: 'results',//name of the property that is container for an Array of row objects
						id: 'id'//the property within each row object that provides an ID for the record (optional)
					},
					[{name: 'id', type: 'int'},
					{name: 'text'}]
				)
			});
			
			var sqlAgenziaCmb="select idregolaprovvigione as id,CONCAT(r.TitoloUfficio,' (',c.CodRegolaProvvigione,')') AS text"; 
			sqlAgenziaCmb+=" from regolaprovvigione c left join reparto r on(r.Idreparto=c.Idreparto)";
			var dsAgenzia = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: sqlAgenziaCmb
				},
				reader:  new Ext.data.JsonReader(
					{
						root: 'results',//name of the property that is container for an Array of row objects
						id: 'id'//the property within each row object that provides an ID for the record (optional)
					},
					[{name: 'id', type: 'int'},
					{name: 'text'}]
				)
			});
			//caricamento elementi liste filtri
			dsClassi.load({
				callback : function(r,options,success) 
				{
					dsTipoPagamento.load({
						callback : function(r,options,success) 
						{
							dsAgenzia.load({
								callback : function(r,options,success) 
								{
									var grid1 = new DCS.GridPraticheCessWO({
										stateId: 'PraCessWOAttesa',
										stateful: true,
										titlePanel: 'Lista pratiche in attesa di Cessione o Write-off',
										title: 'In attesa di Cessione o Write-off',
										task: "ATP",
										hideStato: true,
										dsClassi:dsClassi,
										dsTipoPagamento:dsTipoPagamento,
										dsAgenzia:dsAgenzia
									});
									var grid2 = new DCS.GridPraticheCessWO({
										stateId: 'PraCessWO',
										stateful: true,
										titlePanel: 'Lista pratiche in cessione',
										title: 'Lista cessioni',
										task: "CES",
										hideStato: true,
										dsClassi:dsClassi,
										dsTipoPagamento:dsTipoPagamento,
										dsAgenzia:dsAgenzia,
										grpField: 'MeseCambioStato'
									});
									var grid3 = new DCS.GridPraticheCessWO({
										stateId: 'PraCessWO',
										stateful: true,
										titlePanel: 'Lista pratiche in Write Off',
										title: 'Lista write-off',
										task: "WO",
										hideStato: true,
										dsClassi:dsClassi,
										dsTipoPagamento:dsTipoPagamento,
										dsAgenzia:dsAgenzia,
										grpField: 'MeseCambioStato'
									});
									tp.add(grid1,grid2,grid3);
									DCS.hideMask();
									tp.setActiveTab(0);
								},
								scope:this
							});	
						},
						scope:this
					});	
				},
				scope:this
			});		
			return tp;
		}
	};
	
}();
