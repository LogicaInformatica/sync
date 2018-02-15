// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheCorrenti = Ext.extend(DCS.GridPratiche, {

	dsClassi:'',
	dsTipoPagamento:'',
	dsAgenzia:'',
	sqlExtraCondition : '',
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
					{name: 'StatoLegale'},
					{name: 'StatoStragiudiziale'},
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
					{name: 'DataNascitaCliente', type:'date'}, //solo in Export
					{name: 'Venditore', type:'string'}, //solo in Export
					{name: 'CodOverride', type:'string'}, //solo in Export
					{name: 'DescOverride', type:'string'}, //solo in Export
					{name: 'DescProdotto', type:'string'}, //solo in Export
					{name: 'Pratica', type:'string'}, //solo in Export
					{name: 'ServiziAssicurativi', type:'string'}, //solo in Export
					{name: 'TipoAnagrafica', type:'string'}, //solo in Export
					{name: 'CodFiscGarante', type:'string'}, //solo in Export
					{name: 'ProvinciaDealer', type:'string'}, //solo in Export
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
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'StatoInDBT'},
					{name: 'CategoriaMaxirata'},
					{name: 'FlagVisuraAci'}];

		var columns;
		if (this.task=="inScadenza") {
			locFields.push({name:'scadenza', type:'date'},{name:'nota'},{name:'IdNota'});

			columns = [
				{xtype:'datecolumn', format:'d/m/Y', dataIndex:'scadenza',	width:60,	header:'Scadenza', groupable:true, filterable:false,sortable:true},
	    	    {dataIndex:'nota',	width:140,	header:'Nota',filterable:false},
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',renderer:DCS.render.flagVisuraAci,filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
//	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
//	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
//	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
//	        	{dataIndex:'importo',	width:40,	header:'Importo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:true,stateful:false},
	        	{dataIndex:'DataScadenza',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Scad.',align:'left', filterable: true, groupable:true, sortable:true},
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
	        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
  	];

			Ext.apply(this,{
				grpField: 'scadenza'
	    	});
		} 
		else if (this.task=="positive" || this.task=="incassiParziali") 
		{
			columns = [
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',renderer:DCS.render.flagVisuraAci,filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	{dataIndex:'importo',	width:45,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,stateful:false},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true,stateful:false},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true,stateful:false},
	        	{dataIndex:'ImpPagato',	width:40,	header:'Imp. Pagato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
	        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
		        {dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
		        {dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
		        {dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true,stateful:false},
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
	        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
	        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
	        	];
				
		} 
		else if(this.task=="override")
		{
			locFields.push({name: 'IdFiliale', type: 'int'},{name: 'IdTipoSpeciale', type: 'int'},{name:'TitoloTipoSpeciale'},{name:'TitoloFiliale'},{name:'Responsabile'});

			columns = [
				{dataIndex:'DataCambioStato',width:90,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
		        	{dataIndex:'numPratica',width:110,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
		        	{dataIndex:'cliente',	width:240,	header:'Cliente',renderer:DCS.render.flagVisuraAci,filterable:false,sortable:true},
		        	{dataIndex:'prodotto',	width:240,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
		        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',hidden:true,align:'right',filterable:false,sortable:true,groupable:true},
		        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
		        	{dataIndex:'importo',	width:130,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
		        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
		        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,stateful:false},
		        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
		        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
		        	{dataIndex:'DataScadenza',width:50,xtype:'datecolumn', format:'d/m/y',	header:'Scad.',align:'left', filterable: true, groupable:true, sortable:true,hidden:true},
		        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true,hidden:true},
		        	{dataIndex:'AbbrStatoRecupero',		width:90,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'AbbrClasse',	width:70,	header:'Class.',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'Categoria'    ,   width:30, header:'Categoria', hidden:true,hideable:true,exportable:true,groupable:true},
		        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
		        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow' || this.task=='override')},
		        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true,hidden:true},
		        	{dataIndex:'Responsabile',	width:240,	header:'Responsabile',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'TitoloTipoSpeciale',	width:330,	header:'Motivo override',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'TitoloFiliale',	width:100,	header:'Filiale',filterable:true,sortable:true,groupable:true},
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
		        	{dataIndex:'InteressiDilazione', width:90, header:'Interessi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'MesiDilazione', width:90, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'StatoInDBT', width:110, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
		        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
		        	];
				
				Ext.apply(this,{
					grpField: 'TitoloFiliale'
		    	});
		
		}else if(this.task=="nonstarted"){
			locFields.push({name: 'NumRatePagate', type: 'int'},{name: 'ImpRateInsoluto', type: 'float'},{name:'DataDBT', type:'date'});
			columns = [
			        {dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false,exportable:false},
			        {dataIndex:'Pratica',width:45,	header:'N.Pratica',align:'left', hidden:true,hideable:true,exportable:true,stateful:false},
			        {dataIndex:'cliente',	width:90,	header:'Cliente',renderer:DCS.render.flagVisuraAci,filterable:false,sortable:true},
			        {dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
		        	{dataIndex:'DataLiquidazione',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Data liquidazione',align:'left',hidden:true,exportable:true,hideable:false,stateful:false},
		        	{dataIndex:'AbbrStatoRecupero',		width:40,	header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},		        	
		        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
		        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true,exportable:false},
		        	{dataIndex:'DescProdotto',	width:120,	header:'Prodotto',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Modello', width:110, header:'Modello',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Dealer', width:110, header:'Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Venditore', width:110, header:'Venditore',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'DescOverride', width:70, header:'Descrizione Override',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'ValoreBene', width:70, header:'Valore bene',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Finanziato', width:70, header:'Finanziato',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Anticipo', width:70, header:'Anticipo',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Erogato', width:70, header:'Erogato',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Rata', width:70, header:'Rata',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'RataFinale', width:70, header:'Rata finale',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Riscatto', width:70, header:'Riscatto',hidden:true,hideable:true,exportable:true},
		        	{dataIndex:'ServiziAssicurativi', width:70, header:'Serv.Assicurativi',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Interessi', width:70, header:'Interessi',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'SpeseIncasso', width:70, header:'Spese incasso',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Bollo', width:70, header:'Bollo',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Tasso', width:70, header:'Tasso',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'TipoAnagrafica', width:100, header:'Tipo anagrafica',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'DataNascitaCliente',width:30,xtype:'datecolumn', format:'d/m/y',header:'Data nascita cliente',align:'left', hidden:true, exportable:true, stateful:false, hideable:true},
		        	{dataIndex:'CodFiscGarante', width:70, header:'Cod.Fisc. Garante',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'ProvinciaDealer', width:30, header:'Prov. Dealer',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
		        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
		        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
		        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
		        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
		        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale Insoluto', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,stateful:false},
		        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
		        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
		        	{dataIndex:'DataScadenza',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Primo scaduto insoluto',align:'left', filterable: true, groupable:true, sortable:true},
		        	{dataIndex:'tipoPag',   width:20,	header:'Pag.', filterable: true},
		        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true,
		        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
		        	{dataIndex:'Categoria'    ,   width:30, header:'Categoria', hidden:true,hideable:true,exportable:true,groupable:true},
		        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
		        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true,stateful:false},
		        	{dataIndex:'Taeg', width:70, header:'Taeg',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'TassoReale', width:70, header:'Tasso reale',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'NumeroRate', width:50, header:'N. rate',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'NumRatePagate', width:50, header:'N. rate Pagate',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'ImpRateInsoluto', width:100, header:'Insoluto',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'InteressiDilazione', width:70, header:'Interessi dilazione',hidden:true,hideable:false,exportable:false,stateful:false},
		        	{dataIndex:'MesiDilazione', width:50, header:'N. mesi dilazione',hidden:true,hideable:true,exportable:false,stateful:false},
		        	{dataIndex:'StatoInDBT', width:100, header:'Stato in DBT',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:false,stateful:false,hidden:true}
		        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:false,stateful:false,hidden:true}
		        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:false,stateful:false}
		        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:false,stateful:false}
		        	];
		}else{
					
			if(this.task=="workflow"){
				Ext.apply(this,{
					grpField: 'AbbrStatoRecupero',
					grpDir: 'desc'
		    	});
			}
			
			/*if(this.task=="interne"){
				Ext.apply(this,{
					grpField: 'Categoria',
					grpDir: 'desc'
		    	});
			}*/
			
			columns = [
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',renderer:DCS.render.flagVisuraAci,filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
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
	        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
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
	        	{dataIndex:'StatoInDBT', width:100, header:'Stato in DBT',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
	        	];
		}
		var locFilters = new Ext.ux.grid.GridFilters({
        	// encode and local configuration options defined previously for easier reuse
        	encode: true, // json encode the filter query
        	local: false,   
        	filters: [{
            	type: 'date',
            	dataIndex: 'DataCambioStato'
        	}, {
            	type: 'list',  options: [this.dsClassi],
            	dataIndex: 'AbbrClasse'
        	}, {
            	type: 'list',  options: [this.dsAgenzia],
            	dataIndex: 'agenzia'
        	}, {
            	type: 'numeric',
            	dataIndex: 'importo'
       		},{
       			type: 'list',  options: [this.dsGestore],
            	dataIndex: 'CodUtente'
       		},{
       			type: 'list',  options: [this.dsAgente],
            	dataIndex: 'CodAgente'
       		},{
            	type: 'string',
            	dataIndex: 'Team'
       		},{
            	type: 'numeric',
            	dataIndex: 'giorni'
       		},{
            	type : 'string',
            	dataIndex: 'numPratica'
        	},{
        		type : 'string',
            	dataIndex: 'cliente'
        	},{
        		type : 'string',
        		dataIndex: 'CodFormaGiuridica'
        	},{
        		type:'numeric',
        		dataIndex:'ImpDebitoIniziale'
        	},{
        		type:'numeric',
        		dataIndex:'ImpPagato'
        	},{
        		type:'numeric',
        		dataIndex:'ImpCapitale'
        	},{
        		type:'numeric',
        		dataIndex:'ImpPagatoSBF'
        	},{
        		type:'list', options: [this.dsTeam],
        		dataIndex:'Team'
       		}]
       	});
		/*for(var k=0;k<locFilters.getFilter('AbbrClasse').options.length;k++)
		{
			console.log("inSIN "+locFilters.getFilter('AbbrClasse').options[k]);
		}*/
		
		//Imposta la visibilit� delle colonne a seconda della configurazione effettuata sul submain
		columns = setColumnVisibility(columns);
		
		Ext.apply(this,{
			fields: locFields,
			filters: locFilters,
			innerColumns: columns
	    });
		DCS.GridPraticheCorrenti.superclass.initComponent.call(this, arguments);
	}
});

DCS.PraticheCorrenti = function(){

	return {
		create: function(sqlExtraCondition,panelTitle){
			DCS.showMask();
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
			
			var tp=new Ext.TabPanel({
				activeTab: 0,
				title : panelTitle,
				enableTabScroll: true,
				flex: 1,
				items: []
			});	
			
			if(panelTitle !="")
				panelTitle = panelTitle +" - ";
			
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
									if(PraCorrentiAttesa)
									{	
										//definizione ed aggiunta dei tabs delle pratiche con i filtri aggiornati
										var grid1 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiAttesa',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche in attesa di affido',
											title: 'In attesa',
											task: "inAttesa",
											hideStato: true,
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid1);
									}
									
									if(PraPreRecupero)
									{	
										var grid2 = new DCS.GridPraticheCorrenti({
											stateId: 'PraPreRecupero',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche in prerecupero',
											title: 'Prerecupero',
											task: "preRecupero",
											hideStato: true,
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid2);
									}	
									
									if(PraCorrentiAttive)
									{	
										var grid3 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiAttive',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche attive (in affido pre-DBT)',
											title: 'Attive',
											task: "attive",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid3);
									}	
									/*
									var grid4 = new DCS.GridPraticheCorrenti({
										stateId: 'PraCorrentiStrag',
										stateful: true,
										titlePanel: 'Lista pratiche affidate a recupero stragiudiziale',
										title: 'Stragiudiziale',
										task: "stragiudiziale",
										dsClassi:dsClassi,
										dsTipoPagamento:dsTipoPagamento,
										dsAgenzia:dsAgenzia
									});
									var grid5 = new DCS.GridPraticheCorrenti({
										stateId: 'PraCorrentiLegale',
										stateful: true,
										titlePanel: 'Lista pratiche affidate a recupero legale',
										title: 'Legale',
										task: "legale",
										dsClassi:dsClassi,
										dsTipoPagamento:dsTipoPagamento,
										dsAgenzia:dsAgenzia
									});
									*/
									/*var grid6 = new DCS.GridPraticheCorrenti({
										stateId: 'PraCorrentiWorkflow',
										stateful: true,
										titlePanel: 'Lista pratiche in workflow',
										title: 'Workflow',
										task: "workflow"
									});*/
									if(PraCorrentiScadenza)
									{
										var grid7 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiScadenza',
											stateful: true,
											titlePanel: panelTitle+'Lista azioni o pratiche in scadenza',
											title: 'In scadenza',
											task: "inScadenza",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid7);
									}	
									if(PraCorrentiPositive)
									{
										var grid8 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiPositive',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche positive (incasso totale)',
											title: 'Positivit&agrave;',
											task: "positive",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid8);
									}	
									
									if(PraCorrentiIncParz)
									{
										var grid9 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiIncParz',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche con incasso parziale',
											title: 'Incassi parziali',
											task: "incassiParziali",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid9);
									}	
									
									if(PraCorrentiNoStart)
									{
										// pagina "custom": in futuro bisogna calcolare questa parte dinamicamente (vedi anche praticheCorrenti.php)
										var grid10 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiNoStart',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche non-started',
											title: 'Non-started',
											task: "nonstarted",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid10);
									}	
									
									if(PraCorrentiOverride)
									{
										var grid11 = new DCS.GridPraticheCorrenti({
											stateId: 'PraCorrentiOverride',
											stateful: true,
											titlePanel: panelTitle+'Lista pratiche in override',
											title: 'Override',
											task: "override",
											dsClassi:dsClassi,
											sqlExtraCondition : sqlExtraCondition,
											dsTipoPagamento:dsTipoPagamento,
											dsAgenzia:dsAgenzia
										});
										tp.add(grid11);
									}	
									
									/*return new Ext.TabPanel({
						    			activeTab: 0,
										enableTabScroll: true,
										flex: 1,
										items: [grid1,grid3,grid4,grid5,grid7,grid8,grid9,grid10,grid11]
									})*/	
									//tp.add(grid1,grid2,grid3,grid4,grid5,grid7,grid8,grid9,grid10,grid11);
									//tp.add(grid1,grid2,grid3,grid7,grid8,grid9,grid10,grid11);
									tp.setActiveTab(0);
									//myMask.hide();
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
