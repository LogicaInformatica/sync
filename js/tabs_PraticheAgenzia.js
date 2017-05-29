// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheAgenzia = Ext.extend(DCS.GridPratiche, {

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
					{name: 'ImpCapitaleAffidato', type: 'float'}, // solo per export
					{name: 'ImpInteressiMora', type: 'float'},
					{name: 'ImpSpeseRecupero', type: 'float'},
					{name: 'ImpPagato', type: 'float'},
					{name: 'ImpCapitale', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'StatoLegale'},
					{name: 'StatoStragiudiziale'},
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'operatore'},
					{name: 'CodAgente'},
					{name: 'DataScadenza', type:'date', dateFormat:'Y-m-d'},
					{name: 'DataInizioAffido', type:'date', dateFormat:'Y-m-d'},
					{name: 'DataFineAffido', type:'date', dateFormat:'Y-m-d'},
					{name: 'barraFineAffido', type:'date', dateFormat:'Y-m-d'},
					{name: 'DataCambioClasse', type:'date', dateFormat:'Y-m-d'},
					{name: 'DataScadenzaAzione', type:'date', dateFormat:'Y-m-d H:i:s'},
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
					{name: 'CiSonoAzioniOggi'},
					{name: 'NumNote', type: 'int'},
					{name: 'NumAllegati', type: 'int'},
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'ListaRate'}];

		var columns;
		if (this.task=="inScadenzaAg") {
			locFields.push({name:'scadenza', type:'date', dateFormat:'Y-m-d'},{name:'nota'});

			columns = [
					{xtype:'datecolumn', format:'d/m/Y', dataIndex:'scadenza',	width:60,	header:'Scadenza', groupable:true, filterable:false,sortable:true},
	    	    	{dataIndex:'nota',	width:140,	header:'Nota',filterable:false},
				    {dataIndex:'DataInizioAffido',width:62,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
				    {dataIndex:'DataFineAffido',width:60, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
				    {dataIndex:'barraFineAffido',width:60, exportable:false, renderer:DCS.render.dataSem, header:' ',align:'left', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
					{dataIndex:'DataScadenzaAzione',width:55, renderer:DCS.render.prossimaData, header:'Pross.azione',align:'left', groupable:true, sortable:true},			    
		        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
		        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
		        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false},
		        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
		        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'CodAgente',	width:45,	header:'Operatore',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
		        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
		        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
		        	];

			Ext.apply(this,{
				grpField: 'scadenza'
	    	});
		} 
		else if (this.task=="positiveAg" || this.task=="incassiParzialiAg") 
		{
			columns = [
			    {dataIndex:'DataInizioAffido',width:55,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'DataFineAffido',width:55, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'barraFineAffido',width:60, exportable:false, renderer:DCS.render.dataSem, header:' ',align:'left', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false},
	        	{dataIndex:'CodiceFiscale' ,width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	{dataIndex:'importo',	width:45,	header:'Tot. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpPagato',	width:40,	header:'Imp. Pagato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodAgente',	width:45,	header:'Operatore',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	];
		} else {
			columns = [
			    {dataIndex:'DataInizioAffido',width:55,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'DataFineAffido',width:55, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'barraFineAffido',width:60, exportable:false, renderer:DCS.render.dataSem, header:' ',align:'left', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
				{dataIndex:'CiSonoAzioniOggi',width:16, exportable:false, renderer:DCS.render.spunta, header:' ',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
				{dataIndex:'DataScadenzaAzione',width:60, renderer:DCS.render.prossimaData, header:'Pross.azione',align:'left', groupable:true, sortable:true},			    
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:45,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:false,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,stateful:false},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodAgente',	width:45,	header:'Operatore',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
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
            	dataIndex: 'classif'
        	}, {
            	type: 'list',  options: [],
            	dataIndex: 'operatore'
        	}, {
            	type: 'numeric',
            	dataIndex: 'importo'
       		}]
    	});

		Ext.apply(this,{
			fields: locFields,
			filters: locFilters,
			innerColumns: columns
	    });

		DCS.GridPraticheAgenzia.superclass.initComponent.call(this, arguments);
	}
});

DCS.PraticheAgenzia = function(){

	return {
		create: function(){
			DCS.showMask();
			var grid1 = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaLav',
				stateful: true,
				titlePanel: 'Lista pratiche in gestione',
				title: 'In lavorazione',
				task: "inLavorazioneAg"
			});

			var gridRin = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaRin',
				stateful: true,
				titlePanel: 'Lista pratiche in rinegoziazione',
				title: 'Rinegoziazione',
				task: "rinegoziaAg"
			});

			var grid2 = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaScad',
				stateful: true,
				titlePanel: 'Lista pratiche in scadenza',
				title: 'In scadenza',
				task: "inScadenzaAg"
			});

			var grid3 = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaPos',
				stateful: true,
				titlePanel: 'Lista pratiche risolte',
				title: 'Positivit&agrave;',
				task: "positiveAg"
			});
			
			var grid4 = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaInc',
				stateful: true,
				titlePanel: 'Lista pratiche con incassi parziali',
				title: 'Incassi parziali',
				task: "incassiParzialiAg"
			});
/*
			var grid4 = new DCS.GridPraticheAgenzia({
				stateId: 'PraticheAgenziaRev',
				stateful: true,
				titlePanel: 'Lista pratiche revocate',
				title: 'Revocate',
				task: "revocateAg"
			});
*/			

			if (CONTEXT.PRATICHE_RINE)
				return new Ext.TabPanel({
					activeTab: 0, enableTabScroll: true, flex: 1,
					items: [grid1, gridRin, grid2, grid3, grid4]});
			else
				return new Ext.TabPanel({
					activeTab: 0, enableTabScroll: true, flex: 1,
					items: [grid1, grid2, grid3, grid4]});
		}
	};
	
}();

