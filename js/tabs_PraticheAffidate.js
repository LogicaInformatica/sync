// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheAffidate = Ext.extend(DCS.GridPratiche, {
	agenz:'',
	idAgenz:'',
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
					{name: 'ImpCapitale', type: 'float'},
					{name: 'ImpInteressiMora', type: 'float'},
					{name: 'ImpSpeseRecupero', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'StatoLegale'},  
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'DataInizioAffido', type:'date'},
					{name: 'DataFineAffido', type:'date'},
					{name: 'barraFineAffido', type:'date'},
					{name: 'DataScadenzaAzione', type:'date', dateFormat:'Y-m-d H:i:s'},
					{name: 'DataScadenza', type:'date'},
					{name: 'NumNote', type: 'int'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'Telefono'},
					{name: 'CodiceFiscale'}, // solo in Export
					{name: 'Indirizzo'}, 	 // solo in Export
					{name: 'CAP'},           // solo in Export
					{name: 'Localita'},      // solo in Export
					{name: 'SiglaProvincia'},// solo in Export
					{name: 'TitoloRegione'}, // solo in Export
					{name: 'CodRegolaProvvigione'}, // solo in Export
					{name: 'ListaGaranti'}, // solo in Export
					{name: 'UltimaAzione'}, // solo in Export
					{name: 'DataUltimaAzione'}, // solo in Export
					{name: 'UtenteUltimaAzione'}, // solo in Export
					{name: 'NotaEvento'}, // solo in Export
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'CiSonoAzioniOggi'}];

		var columns = [
			    {dataIndex:'DataInizioAffido',width:62,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'center', resizable:true, groupable:true, sortable:true, hidden:false},
			    {dataIndex:'DataFineAffido',width:60,xtype:'datecolumn', format:'d/m/y',	 header:'Fine affido',align:'center', resizable:true, groupable:true, sortable:true, hidden:false},
			    {dataIndex:'barraFineAffido',width:60, hidden:false, exportable:false, renderer:DCS.render.dataSem,header:' ',align:'left', resizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
				{dataIndex:'CiSonoAzioniOggi',width:16, exportable:false, renderer:DCS.render.spunta, header:' ',align:'center', resizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
				{dataIndex:'DataScadenzaAzione',width:55, renderer:DCS.render.prossimaData, header:'Pross.azione',align:'left', groupable:true, sortable:true, hidden:false},			    
	        	{dataIndex:'numPratica',width:65,	header:'N.Pratica', align:'left', filterable: true,sortable:true, hidden:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true, hidden:false},
	        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false, hidden:false},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true, hidden:false},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true, hidden:false},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true, hidden:false},
	        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true, hidden:false},
	        	{dataIndex:'ImpCapitale', exportable:true, hidden:true, width:40,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'tipoPag',   width:25,	header:'Pag.', filterable: true, hidden:false},
	        	{dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true, hidden:false},
	        	{dataIndex:'AbbrStatoRecupero',		width:45,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true},
	        	{dataIndex:'AbbrClasse', width:40,	header:'Class.',filterable:true,sortable:true, hidden:false},
	        	{dataIndex:'agenzia',	width:50,	hidden: true,header:'Agenzia',filterable:true,sortable:true,groupable:true}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
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
			filters: locFilters,
			innerColumns: columns,
			agenzia: this.agenz,
			idAgenz: this.idAgenz
	    });

		DCS.GridPraticheAffidate.superclass.initComponent.call(this, arguments);
	}
});

DCS.PraticheAffidate = function(){

	return {
		myTipo: 1,
		create: function(tipo){
			DCS.showMask();
			var TabPanelAg = new Ext.TabPanel({
				activeTab: 0,
				id: 'TabPanelAg'+tipo,
				enableTabScroll: true,
				flex: 1,
				//items: [gridPhone, gridEsattoriale, gridStragiudiziale, gridLegale, gridAltre]
				items: []
			}); 
			Ext.Ajax.request({
				url : 'server/AjaxRequest.php' , 
				params : {
					task: 'read',
					sql: "select * FROM v_tabs_agenzie WHERE tipo="+tipo+" ORDER BY NomeAgenzia"},
				method: 'POST',
				reader:  new Ext.data.JsonReader(
    					{
    						root: 'results',//name of the property that is container for an Array of row objects
    						id: 'ChiaveAgenzia'//the property within each row object that provides an ID for the record (optional)
    					},
    					[{name: 'ChiaveAgenzia'},
    					{name: 'NomeAgenzia'}]
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
						grid[nomeG] = new DCS.GridPraticheAffidate({
							stateId: 'PraticheAffidate',
							stateful: true,
							titlePanel: 'Lista pratiche affidate all\' agenzia '+arr[i]['NomeAgenzia'],
							title: arr[i] ['NomeAgenzia'],
							task: "aff-Ag",
							agenz: arr[i]['NomeAgenzia'],
							idAgenz: arr[i]['ChiaveAgenzia'],
							hideStato: true
						});
						listG.push(grid[nomeG]);
					}
					TabPanelAg.add(listG);
					DCS.hideMask();
					TabPanelAg.setActiveTab(0);
				},
				failure: function ( result, request) { 
					DCS.hideMask();
					//eval('var resp = '+result.responseText);
					Ext.MessageBox.alert('Errore', result.statusText);  
				},
				scope:this
			});
			return TabPanelAg;
		}
	};
	
}();