// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPratichePianoRientro = Ext.extend(DCS.GridPratiche, {
	utenteId:'',
	
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
					{name: 'AbbrStatoRecupero'},
					{name: 'StatoLegale'},  
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'CodUtente'},
					{name: 'DataScadenza', type:'date'},
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
					{name: 'ListaGaranti'}, // solo in Export
					{name: 'UltimaAzione'}, // solo in Export
					{name: 'DataUltimaAzione'}, // solo in Export
					{name: 'UtenteUltimaAzione'}, // solo in Export
					{name: 'NotaEvento'}, // solo in Export
					{name: 'Garanzie'}, // solo in Export
					{name: 'NumNote', type: 'int'},
					{name: 'Categoria'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'StatoRinegoziazione'},
					{name: 'DataRataNonPagata', type:'date'},
					{name: 'NumeroRataNonPagata',type: 'int'},
					{name: 'DataRataDaPagare', type:'date'},
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'NumeroRataDaPagare',type: 'int'}];

		var columns = [
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'NumeroRataNonPagata',	width:50,	header:'N.RataPRNonPagata',align:'right',filterable:false,sortable:false,groupable:false,
	        	  hidden:(this.task=='pianorientroIS'|| this.task=='pianorientroIC' || this.task=='pianorientroPO')},
				{dataIndex:'DataRataNonPagata',     width:70,   xtype:'datecolumn', format:'d/m/y',	header:'DataRataPRNonPagata',align:'left', filterable:false,sortable:false,groupable:false,
	        	  hidden: (this.task=='pianorientroIS'|| this.task=='pianorientroIC' || this.task=='pianorientroPO')},
				{dataIndex:'NumeroRataDaPagare',	width:50,	header:'N.RataPRDaPagare',align:'right',filterable:false,sortable:false,groupable:false,
	        	  hidden: (this.task=='pianorientroSC'|| this.task=='pianorientroIC' || this.task=='pianorientroPO')},
				{dataIndex:'DataRataDaPagare',     width:70,   xtype:'datecolumn', format:'d/m/y',	header:'DataRataPRDaPagare',align:'left', filterable:false,sortable:false,groupable:false,
				  hidden: (this.task=='pianorientroSC'|| this.task=='pianorientroIC' || this.task=='pianorientroPO')},
				{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'DataScadenza',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Scad.',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Categoria'    ,   width:30, header:'Categoria', hidden:true,hideable:true,exportable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
	        		hidden:(this.task=='svaluta' || this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true}
		        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
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
            	type: 'list',  options: [DCS.Store.dsAbbrClasse],
            	dataIndex: 'AbbrClasse'
        	}, {
            	type: 'list',  options: [DCS.Store.dsAbbrStatoRecupero],
            	dataIndex: 'AbbrStatoRecupero'
        	}, {
            	type: 'list',  options: [DCS.Store.dsAgenzieAFF],
            	dataIndex: 'agenzia'
        	}, {
            	type: 'numeric',
            	dataIndex: 'importo'
       		}, {
            	type: 'numeric',
            	dataIndex: 'insoluti'
       		}, {
            	type: 'numeric',
            	dataIndex: 'giorni'
       		}]
    	});
		
		Ext.apply(this,{
			//grpField: 'agenzia',
			fields: locFields,
			//filters: locFilters,
			innerColumns: columns,
			//utente: this.utente,
			idUtente: this.utenteId
	    });
		
		DCS.GridPratichePianoRientro.superclass.initComponent.call(this, arguments);
		
				
	}
});

DCS.PratichePianoRientro = function(){

	return {
		create: function(){
			DCS.showMask();
			var TabPanelPr = new Ext.TabPanel({
					activeTab: 0,
					id: 'TabPanelPr',
					enableTabScroll: true,
					flex: 1,
					//items: [gridPhone, gridEsattoriale, gridStragiudiziale, gridLegale, gridAltre]
					items: []
			});
			Ext.Ajax.request({
					url: 'server/AjaxRequest.php',
					params: {
						task: 'read',
						sql: "select distinct IdOperatore, NomeOperatore"+
                             " from v_piano_rientro"+
                             " where IdStatoPiano ="+ tipo +" order by NomeOperatore asc"
					},
					method: 'POST',
					reader: new Ext.data.JsonReader({
						root: 'results',//name of the property that is container for an Array of row objects
						id: 'IdOperatore'//the property within each row object that provides an ID for the record (optional)
					}, [{
						name: 'IdOperatore'
					}, {
						name: 'NomeOperatore'
					}]),
					autoload: true,
					success: function(result, request){
						eval('var arr = (' + result.responseText + ').results');
						eval('var resp = (' + result.responseText + ').total');
						var listP = new Array();
						for (i = 0; i < resp; i++) {
							var gridPraticheInCorso = new DCS.GridPratichePianoRientro({
								stateId: 'PratichePianoRientroInCorso',
								stateful: true,
								titlePanel: 'Sintesi pratiche con piano di rientro in corso',
								title: 'In corso',
								id: 'GridPratichePianoRientroIC' + i,
								task: 'pianorientroIC',
								utenteId: arr[i]['IdOperatore']
							});
							
							var gridPraticheScaduti = new DCS.GridPratichePianoRientro({
								stateId: 'PratichePianoRientroScaduti',
								stateful: true,
								titlePanel: 'Sintesi pratiche con piano di rientro scadute',
								title: 'Scaduti',
								id: 'GridPratichePianoRientroSC' + i,
								task: "pianorientroSC",
								utenteId: arr[i]['IdOperatore']
							});
							
							var gridPraticheInScadenza = new DCS.GridPratichePianoRientro({
								stateId: 'PratichePianoRientroInScadenza',
								stateful: true,
								titlePanel: 'Sintesi pratiche con piano di rientro in scadenza',
								title: 'in Scadenza',
								id: 'GridPratichePianoRientroIS' + i,
								task: "pianorientroIS",
								utenteId: arr[i]['IdOperatore']
							});
							var gridPratichePositivi = new DCS.GridPratichePianoRientro({
								stateId: 'PratichePianoRientroPositivi',
								stateful: true,
								titlePanel: 'Sintesi pratiche con piano di rientro esito positivo',
								title: 'Positivi',
								id: 'GridPraticheAzioniSpecialiPOS' + i,
								task: "pianorientroPO",
								utenteId: arr[i]['IdOperatore']
							});
							var indicesubtab = 'subTabAspF' + i;
							var subTabPanelAsp = new Ext.TabPanel({
								activeTab: 0,
								enableTabScroll: true,
								title: arr[i]['NomeOperatore'],
								flex: 1,
								id: indicesubtab,
								items: [gridPraticheInCorso, gridPraticheScaduti, gridPraticheInScadenza, gridPratichePositivi]
							});
							//end creation of subtabs iesimi
							//aggiunta subtabs al tab superiore
							listP.push(subTabPanelAsp);
						}
						
						Ext.getCmp('TabPanelPr').add(listP);
						DCS.hideMask();
						Ext.getCmp('TabPanelPr').setActiveTab(0);
					},
					failure: function(result, request){
						DCS.hideMask();
						//eval('var resp = '+result.responseText);
						Ext.MessageBox.alert('Errore', resp.responseText);
					},
					scope: this
			});
			
			return TabPanelPr;
		}
	};
	
}();