// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridScadenzarioSTR = Ext.extend(DCS.GridPratiche, {

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
					{name: 'ImpDebitoResiduo', type: 'float'},
					{name: 'ImpSaldoStralcio', type: 'float'},
					{name: 'ImpPagato', type: 'float'},
					{name: 'ImpCapitale', type: 'float'},
					{name: 'AbbrStatoRecupero'},
					{name: 'AbbrClasse'},
					{name: 'tipoPag'},
					{name: 'agenzia'},
					{name: 'CodUtente'},
					{name: 'DataSaldoStralcio', type:'date'},
					{name: 'DataScadenza', type:'date'}, // data di scadenza dell'azione (legale)
					{name: 'Azione'}, 
					{name: 'DataCambioStato', type:'date'},
					{name: 'DataCambioClasse', type:'date'},
					{name: 'DataFineAffido', type:'date'},
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
					{name: 'StatoInDBT'}];

		var columns;
		if (this.task.substr(0,14)=="scadenzaAffidi")  // colonne per affidamenti in scadenza
		{
			columns = [
			    {dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
				{xtype:'datecolumn', format:'d/m/Y', dataIndex:'DataFineAffido',	width:60,	header:'Fine affido', groupable:true, filterable:false,sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:true,stateful:false},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,hideable:false,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Filiale', width:110, header:'Filiale',hidden:true,hideable:true,exportable:true,stateful:false},
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
	        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}];
		}
		else if (this.task.substr(0,14)=="scadenzaAzioni") // colonne per azioni legali in scadenza
		{
			columns = [
			    {dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
				{dataIndex:'DataScadenza',xtype:'datecolumn', format:'d/m/Y', width:60,	header:'Scadenza', groupable:true, filterable:false,sortable:true},
				{dataIndex:'Azione', width:100,	header:'Azione', groupable:true, filterable:true,sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:true,stateful:false},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,hideable:false,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Filiale', width:110, header:'Filiale',hidden:true,hideable:true,exportable:true,stateful:false},
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
	        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}];
		}
		else if (this.task=="saldoStralcio") {
			locFields.push({name:'DataSaldoStralcio', type:'date'},{name:'ImpDebitoResiduo', type:'float'},{name:'ImpSaldoStralcio', type:'float'});
			columns = [
			    {dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
			    {xtype:'datecolumn', format:'d/m/Y', dataIndex:'DataSaldoStralcio',	width:60,	header:'Data saldo', groupable:true, filterable:false,sortable:true},
	        	{dataIndex:'ImpSaldoStralcio', width:70, header:'Imp. Saldo e stralcio', xtype:'numbercolumn',format:'0.000,00/i',align:'right',hidden:false,exportable:true,stateful:false},
	        	{dataIndex:'ImpDebitoResiduo', width:70, header:'Debito residuo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',hidden:false,exportable:true,stateful:false},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:true,stateful:false},
	        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,hideable:false,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Filiale', width:110, header:'Filiale',hidden:true,hideable:true,exportable:true,stateful:false},
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
	        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}];
		}		
		
		//Imposta la visibilit� delle colonne a seconda della configurazione effettuata sul submain
		columns = setColumnVisibility(columns);
		
		Ext.apply(this,{
			fields: locFields,
			innerColumns: columns
	    });
		
		DCS.GridScadenzarioSTR.superclass.initComponent.call(this, arguments);
	}
});

DCS.ScadenzarioSTR = function(){
	return {
		create: function(caller,sqlExtraCondition,panelTitle)
		{
			var pnlScadStr = new Ext.TabPanel({
				activeTab: 0,
				title :panelTitle,
				enableTabScroll: true,
				flex: 1,
				items: []
			});
			
			if(panelTitle !="")
				panelTitle = panelTitle +" - ";
			
			if(PraScadenzaSTRSoft && (caller=="STR" || caller=='STRLEG'))
			{
				var grid1a = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Pratiche stragiudiziali Soft in scadenza',
					title: 'Affidamenti DBT Soft in scadenza',
					task: "scadenzaAffidiSTR1",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid1a);
			}	
			
			if(PraScadenzaSTRHard && (caller=="STR" || caller=='STRLEG'))
			{
				var grid1b = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Pratiche stragiudiziali Hard in scadenza',
					title: 'Affidamenti DBT Hard in scadenza',
					task: "scadenzaAffidiSTR2",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid1b);
			}		
			
			if(PraScadenzaSTRStrong && (caller=="STR" || caller=='STRLEG'))
			{	
				var grid1c = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Pratiche stragiudiziali Strong in scadenza',
					title: 'Affidamenti DBT Strong in scadenza',
					task: "scadenzaAffidiSTR3",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid1c);
			}	
			
			if(PraScadenzaSTRREPO && (caller=="STR" || caller=='STRLEG'))
			{					
				var grid1d = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Pratiche stragiudiziali Repo in scadenza',
					title: 'Affidamenti STR REPO in scadenza',
					task: "scadenzaAffidiSTR4",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid1d);
			}		
				
			if(PraScadenzaSTRLEG && (caller=="LEG" || caller=='STRLEG'))
			{					
				var grid2a = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Pratiche legali in scadenza',
					title: 'Affidamenti LEG in scadenza',
					task: "scadenzaAffidiLEG",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid2a);
			}		

			if(PraScadenzaSTRLEGScad && (caller=="LEG" || caller=='STRLEG'))
			{					
				var grid2b = new DCS.GridScadenzarioSTR({
					stateId: 'PraScadenzaSTR',
					stateful: true,
					titlePanel: panelTitle +'Azioni legali in scadenza',
					title: 'Azioni LEG in scadenza',
					task: "scadenzaAzioniLEG",
					sqlExtraCondition : sqlExtraCondition,
					hideStato: true
				});
				pnlScadStr.add(grid2b);
			}		

			if(PraSaldoStralcio)
			{					
				var grid3 = new DCS.GridScadenzarioSTR({
					stateId: 'PraSaldoStralcio',
					stateful: true,
					titlePanel: panelTitle +'Scadenze pratiche in saldo e stralcio',
					title: 'Scadenze saldo e stralcio',
					sqlExtraCondition : sqlExtraCondition,
					task: "saldoStralcio",
					hideStato: true
				});		
				pnlScadStr.add(grid3);
			}		
			return pnlScadStr;
		}
	};
	
}();
