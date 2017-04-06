// Crea namespace DCS
Ext.namespace('DCS');

// PRIMO TAB: Situazione debitoria corrente
// TABS SUCCESSIVI: situazione negli anni precedenti (i dati hanno campo Consolidata='Y')
DCS.GridPraticheSituazione = Ext.extend(DCS.GridPratiche, {
	initComponent : function() {

		var locFields;
		var columns;

		Ext.apply(this,{
			grpField: '',
			grpDir: 'asc'
    	});

		locFields = [{name: 'IdContratto'},
					{name: 'prodotto'},
					{name: 'numPratica'},
					{name: 'IdCliente', type: 'int'},
					{name: 'cliente'},{name: 'CodCliente'},
					{name: 'ImpInsoluto', type: 'float'},
					{name: 'CapitaleResiduo', type: 'float'},  
					{name: 'ImpCapitale', type: 'float'},
					{name: 'ImpInteressiMora', type: 'float'},
					{name: 'ImpSpeseRecupero', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'AbbrClasse'},
					{name: 'agenzia'},
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
					{name: 'Categoria'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'PercSvalutazione', type: 'float'},
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'Svalutazione', type: 'float'}];

		columns = [
        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
        	{dataIndex:'ImpInsoluto',	width:40,	header:'Tot.Insoluto', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
        	{dataIndex:'PercSvalutazione',	hidden:true, width:25,	header:'% Sval.', xtype:'numbercolumn', format:'000,00 %/i',align:'right',filterable:true,sortable:true},
        	{dataIndex:'Svalutazione',	hidden:true, width:50,	header:'Svalutazione',xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
        	{dataIndex:'CapitaleResiduo',	width:70,	header:'Capitale Residuo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true},
        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale ins.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true
        		,hidden:true},
        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
        	{dataIndex:'Categoria'    ,   width:30, header:'Categoria', hidden:true,hideable:true,exportable:true,groupable:true},
        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true}
        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
   		];
		
		Ext.apply(this,{
			fields: locFields,
			innerColumns: columns
	    });

		DCS.GridPraticheSituazione.superclass.initComponent.call(this, arguments);
	}
});

//------------------------------------------------------------------------------
// Creazione pannello multi-tab
//------------------------------------------------------------------------------
DCS.PraticheSituazione = function(){

	return {
		create: function(caller,sqlExtraCondition,panelTitle){
			DCS.showMask();
			var tp=new Ext.TabPanel({
				activeTab: 0,
				title : panelTitle,
				enableTabScroll: true,
				flex: 1,
				items: []
			});
			
			if(panelTitle !="")
				panelTitle = panelTitle +" - ";
			
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
					dsAgenzia.load({
						callback : function(r,options,success) 
						{
							var grid0 = new DCS.GridPraticheSituazione({
										stateId: 'PraSitCurrent',
										stateful: true,
										titlePanel: 'Situazione debitoria corrente',
										title: 'Situazione corrente',
										task: "situazione",
										dsClassi:dsClassi,
										sqlExtraCondition : "Consolidata='N'",
										dsAgenzia:dsAgenzia
							});
							tp.add(grid0);
							
							
							Ext.Ajax.request({
								url : 'server/AjaxRequest.php' , 
								params : {
									task: 'read',
									sql: "select distinct DataRiferimento,DATE_FORMAT(datariferimento,'%d/%m/%Y') AS data from situazione where consolidata='Y' ORDER BY datariferimento"},
								method: 'POST',
								reader:  new Ext.data.JsonReader(
				    					{
				    						root: 'results',//name of the property that is container for an Array of row objects
				    						id: 'DataRiferimento'//the property within each row object that provides an ID for the record (optional)
				    					},
				    					[{name: 'DataRiferimento'}, {name: 'data'}]
				    			),
								autoload:true,
								success: function ( result, request ) {
									eval('var arr = ('+result.responseText+').results');
									eval('var resp = ('+result.responseText+').total');
									for(i=0;i<resp;i++){
										nomeG = "gridSit"+i; 
										tp.add(new DCS.GridPraticheSituazione({
											stateId: 'PraSit',
											stateful: true,
											titlePanel: 'Situazione debitoria complessiva al '+arr[i]['data'],
											title: 'Situazione al '+arr[i]['data'],
											task: 'situazione',
											dsClassi:dsClassi,
											sqlExtraCondition : "Consolidata='Y' AND DataRiferimento='"+arr[i]['DataRiferimento']+"'",
											dsAgenzia:dsAgenzia
										 })
										);
									}
									DCS.hideMask();
									tp.setActiveTab(0);
								},
								failure: function ( result, request) { 
									DCS.hideMask();
									Ext.MessageBox.alert('Errore', result.statusText);  
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

