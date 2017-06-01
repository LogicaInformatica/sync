/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.tabs_Ricerca = Ext.extend(Ext.form.TriggerField, {
	
	initComponent : function(){
		
		DCS.tabs_Ricerca.superclass.initComponent.call(this);

		this.on('specialkey', function(f, e){
			if (e.getKey() == e.ENTER){
            	this.onTriggerClick.call(this);
            }
        }, this);
    },

	validationEvent:false,
	validateOnBlur:false,
	triggerClass:'x-form-search-trigger',
	//width:300,
	hasSearch : false,
	paramName : 'query',
	
    onTriggerClick: function(){
		var v = this.getRawValue().trim();
		if( v.length > 0 && v!=this.emptyText) {
			var pnl = new DCS.pnlSearch({IdC: v});
			var win = new Ext.Window({
	    		width: 1100, height:700, 
	    		autoHeight:true, modal:true,
	    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
				title: 'Ricerca',
	    		constrain: true,
	    	    items: [pnl]
	        });
	       	win.show();
			pnl.activation.call(pnl);
        }
    }

});

DCS.pnlSearch = Ext.extend(DCS.GridPratiche, {
	IdC: '',
	stato: '', 
	classe: '', 
	agenzia: '', 
	prodotto: '',
	agente: '',
	lotto:'',
	titolo: '',
	IdLottomatica:'',
	searchFields:'', 
	
	initComponent : function() {
		var locFields;
		var columns;
		// determina se sta sulla pagina Storico
		var mainp = Ext.getCmp('mainPanel'); 
		if (mainp.findById('tabStorico') && !mainp.findById('tabStorico').hidden)
			this.isStorico = true;

		if (this.IdC=='Provvigioni')
		{
			locFields = [{name: 'IdContratto'},
   						 {name: 'CodContratto'},
						 {name: 'IdCliente', type: 'int'},
						 {name: 'cliente'},{name: 'CodCliente'},
						 {name: 'Agenzia'},
						 {name: 'ImpCapitaleAffidato',type: 'float'},
						 {name: 'NumRate',type: 'int'},
						 {name: 'GiorniRitardo',type: 'int'},
						 {name: 'RateViaggiantiIncassate',type: 'int'},
						 {name: 'ImpRiconosciuto',type: 'float'},
						 {name: 'ImpPagatoTotale',type: 'float'},
						 {name: 'ImpInteressi',type: 'float'},
						 {name: 'ImpSpese',type: 'float'},
                                                 {name: 'ImpProvvigione',type: 'float'},
						 {name: 'PercCapitale', type: 'float'},
						 {name: 'PercCapitaleReale', type: 'float'},
    					 {name: 'DataUltimoPagamento', type:'date'},
    					 {name: 'DataInizioAffidoContratto', type:'date'}, // prese da Contratto non da Assegnazione (per STR/LEG)
    					 {name: 'DataFineAffidoContratto', type:'date'},
  					     {name: 'Operatore'},
  					     {name: 'UltimaAzione'}, // solo Export
  					     {name: 'DataUltimaAzione', type:'date'}, // solo Export
  					     {name: 'UtenteUltimaAzione'},  // solo Export
 					     {name: 'NotaEvento'},  // solo Export
 						 {name: 'Garanzie'}, // solo in Export
						 {name: 'NumNote', type: 'int'},
 						 {name: 'IdAgenzia', type: 'int'},
 						 {name: 'IdAgenziaCorrente', type: 'int'},
					     {name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
 					     {name: 'IdProvvigione'}
						];		
			columns = [
			        	{dataIndex:'CodContratto',width:55,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
			        	{dataIndex:'cliente',	width:95,	header:'Cliente',filterable:false,sortable:true},
			        	{dataIndex:'Operatore',	width:45,	header:'Operatore',filterable:true,sortable:true, hidden:false, hideable:true},
					    {dataIndex:'DataInizioAffidoContratto',width:50,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
					    {dataIndex:'DataFineAffidoContratto',width:50, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			        	{dataIndex:'ImpCapitaleAffidato', width:53,	header:'Cap. affidato',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'},
	//		        	{dataIndex:'ImpTotaleAffidato', width:58,	header:'Debito tot.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
	//	        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'NumRate',	width:33,	header:'Rate',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'GiorniRitardo',	width:33,	header:'Giorni',align:'right',sortable:true,summaryType:'avg'},
			        	{dataIndex:'RateViaggiantiIncassate',	width:43,	header:'Viagg. inc.',align:'right',sortable:true,summaryType:'sum'},
			        	{dataIndex:'ImpRiconosciuto', width:40,	header:'IPR',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpPagatoTotale', width:60,	header:'Incasso tot.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'ImpInteressi', width:63,	header:'Int. mora inc.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Interessi di mora incassati'},
			        	{dataIndex:'ImpSpese', width:58,	header:'Spese rec.',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i',tooltip:'Spese di recupero incassate'},
					{dataIndex:'ImpProvvigione', width:40,	header:'Provvigione',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'},	
				        {dataIndex:'Provenienza',	width:120,	header:'Provenienza',filterable:true,sortable:true, hidden:(CONTEXT.InternoEsterno == 'E'), hideable:(CONTEXT.InternoEsterno != 'E'), groupable: true},
				       {dataIndex:'PercCapitale', width:55,	align:'right',header:'% capitale',filterable:false,sortable:true,groupable:false,
			    	       	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentIPM'},
				        {dataIndex:'PercCapitaleReale', width:60,	align:'right',header:'% cap. reale',filterable:false,sortable:true,groupable:false,
			    	       	xtype:'numbercolumn',format:'000 %/i',summaryType:'percentIPM'},
					    {dataIndex:'DataUltimoPagamento',width:55, xtype:'datecolumn', format:'d/m/y',	header:'Ultimo pag.',align:'left', sizable:false, groupable:true, sortable:true}
				       ,{dataIndex:'UltimaAzione', width:70, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'DataUltimaAzione', width:70, header:'Eseguita il',xtype:'datecolumn', format:'d/m/y',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'UtenteUltimaAzione', width:70, header:'Eseguita da',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'NotaEvento', width:120, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
			        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
			        ];
		}
		else if (this.IdC=='ProvvigioniSingole')
		{
			locFields = [{name: 'IdContratto'},
   						 {name: 'CodContratto'},
						 {name: 'IdCliente', type: 'int'},
						 {name: 'cliente'},{name: 'CodCliente'},
						 {name: 'ImpCapitaleAffidato',type: 'float'},
						 {name: 'ImpProvvigione',type: 'float'},
    					 {name: 'DataInizioAffidoContratto', type:'date'}, // prese da Contratto non da Assegnazione (per STR/LEG)
    					 {name: 'DataFineAffidoContratto', type:'date'},
  					     {name: 'StatoRinegoziazione'},
  					     {name: 'Provenienza'},
  					     {name: 'Operatore'},
  					     {name: 'UltimaAzione'}, // solo Export
  					     {name: 'DataUltimaAzione', type:'date'}, // solo Export
  					     {name: 'UtenteUltimaAzione'},  // solo Export
 					     {name: 'NotaEvento'},  // solo Export
 						 {name: 'Garanzie'}, // solo in Export
						 {name: 'NumNote', type: 'int'},
					     {name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
 						 {name: 'IdAgenzia', type: 'int'},
 						 {name: 'IdAgenziaCorrente', type: 'int'},
					     {name: 'IdProvvigione'}
						];		
			columns = [
			        	{dataIndex:'CodContratto',width:55,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
			        	{dataIndex:'cliente',	width:95,	header:'Cliente',filterable:false,sortable:true},
			        	{dataIndex:'Operatore',	width:45,	header:'Operatore',filterable:true,sortable:true, hidden:false, hideable:true},
					    {dataIndex:'DataInizioAffidoContratto',width:50,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
					    {dataIndex:'DataFineAffidoContratto',width:50, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			        	{dataIndex:'ImpCapitaleAffidato', width:53,	header:'Cap. affidato',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'},
			        	{dataIndex:'StatoRinegoziazione',	width:120,	header:'Stato',filterable:true,sortable:true, hidden:false, hideable:true, groupable: true},
			        	{dataIndex:'Provenienza',	width:120,	header:'Provenienza',filterable:true,sortable:true, hidden:(CONTEXT.InternoEsterno == 'E'), hideable:(CONTEXT.InternoEsterno != 'E'), groupable: true},
						{dataIndex:'ImpProvvigione', width:40,	header:'Provvigione',align:'right',filterable:false,sortable:true,groupable:false,summaryType:'sum',
		        			xtype:'numbercolumn',format:'0.000,00/i'}	
				       ,{dataIndex:'UltimaAzione', width:70, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'DataUltimaAzione', width:70, header:'Eseguita il',xtype:'datecolumn', format:'d/m/y',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'UtenteUltimaAzione', width:70, header:'Eseguita da',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'NotaEvento', width:120, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
				       ,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
			        ];
		} else if (this.IdC=='DettaglioExperianInvio') { // dettaglio di un invio experian (lista clienti)
			locFields = DCS.ExperianModel.fields.slice(0); // clona per evitare che l'aggiunta delle selectbox sia permanente
			columns   = DCS.ExperianModel.columns.slice(0);
		} else if (this.IdC=='DettaglioExperianCliente') { // dettaglio di tutti gli invii experian per cliente
			locFields = DCS.ExperianModel.fields.slice(0);
			columns   = DCS.ExperianModel.columns.slice(0);
		} else if (CONTEXT.MENU_GPA_CORR || CONTEXT.MENU_GPA_SINT) {
			locFields = [{name: 'IdContratto'},
				{name: 'prodotto'},
				{name: 'numPratica'},
				{name: 'IdCliente', type: 'int'},
				{name: 'cliente'},{name: 'CodCliente'},
				{name: 'rata', type: 'int'},
				{name: 'insoluti',type: 'int'},
				{name: 'giorni', type: 'int'},
				{name: 'importo', type: 'float'},
				{name: 'ImpCapitaleAffidato',type: 'float'},
				{name: 'ImpInteressiMora', type: 'float'},
				{name: 'ImpSpeseRecupero', type: 'float'},
				{name: 'ImpPagato', type: 'float'},
				{name: 'AbbrStatoRecupero'},
				{name: 'StatoLegale'},  
				{name: 'AbbrClasse'},
				{name: 'tipoPag'},
				{name: 'DataUltimoPagamento', type:'date'},
				{name: 'agenzia'},
				{name: 'operatore'},
				{name: 'CodAgente'},
				{name: 'DataScadenza', type:'date'},
				{name: 'DataInizioAffido', type:'date'},
				{name: 'DataFineAffido', type:'date'},
				{name: 'barraFineAffido', type:'date'},
				{name: 'DataCambioClasse', type:'date'},
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
				{name: 'Garanzie'}, // solo in Export
				{name: 'CiSonoAzioniOggi'},
				{name: 'NumNote', type: 'int'},
				{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
				{name: 'NumAllegati', type: 'int'}
			];
			
			columns = [
			    {dataIndex:'DataInizioAffido',width:55,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'DataFineAffido',width:55, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'barraFineAffido',width:60, exportable:false, renderer:DCS.render.dataSem, header:' ',align:'left', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false},
				{dataIndex:'CiSonoAzioniOggi',width:16, exportable:false, renderer:DCS.render.spunta, header:' ',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false},
				{dataIndex:'DataScadenzaAzione',width:60, renderer:DCS.render.prossimaData, header:'Pross.azione',align:'left', groupable:true, sortable:true},			    
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'Telefono',	width:60,	header:'Telefono',filterable:false,sortable:false},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'rata',		width:30,	header:'N.rata',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'insoluti',	width:30,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
	        	//{dataIndex:'giorni',	width:30,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
	        	{dataIndex:'importo',	width:40,	header:'Importo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'ImpInteressiMora',	width:40,	header:'Int.mora', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	{dataIndex:'ImpSpeseRecupero',	width:40,	header:'Spese rec.', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:true},
	        	//{dataIndex:'DataScadenza',width:60,xtype:'datecolumn', format:'d/m/y',	header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'DataUltimoPagamento',   width:45,	header:'Ultimo pag.', xtype:'datecolumn', format:'d/m/y'},
	        	{dataIndex:'AbbrClasse',	width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodAgente',	width:45,	header:'Operatore',filterable:true,sortable:true,groupable:true}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
	        ];
		/*} else if (this.IdC=='PLottomatica'){
			locFields = [
				{name: 'idTransazione'},
				{name: 'CcBeneficiario'},
				{name: 'dataTransazione', type:'date'},
				{name: 'importo', type: 'int'},
				{name: 'ufficioSportello'},
				{name: 'dataContbAccredito', type:'date'},
				{name: 'codiceContratto'}
			];
		
			columns = [
			    {dataIndex:'idTransazione',	width:20,	header:'IdT', hidden:true, filterable:false,sortable:true},
			    {dataIndex:'CcBeneficiario',	width:120,	header:'CcBeneficiario',filterable:true,sortable:true,groupable:true},
			    {dataIndex:'dataTransazione',width:55,xtype:'Data transizione', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'importo',	width:45,	header:'Importo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
			    {dataIndex:'ufficioSportello',width:45,	header:'Sportello',filterable:true,sortable:true,groupable:true},
			    {dataIndex:'dataContbAccredito',width:55, xtype:'DataContbAccredito', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'codiceContratto',width:45,	header:'Cod.Pratica',align:'left', filterable: false, sortable:true}
			];*/
		} else if (this.isStorico) { // ricerca sullo storico
			locFields =  [{name: 'IdContratto'},
							{name: 'prodotto'},
							{name: 'numPratica'},
							{name: 'IdCliente', type: 'int'},
							{name: 'cliente'},{name: 'CodCliente'},
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
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
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
		}else{		
			locFields = [
				{name: 'IdContratto'},
				{name: 'prodotto'},
				{name: 'numPratica'},
				{name: 'IdCliente', type: 'int'},
				{name: 'cliente'},{name: 'CodCliente'},
				{name: 'rata', type: 'int'},
				{name: 'insoluti',type: 'int'},
				{name: 'giorni', type: 'int'},
				{name: 'importo', type: 'float'},
				 {name: 'ImpCapitaleAffidato',type: 'float'},
				{name: 'DataUltimoPagamento', type:'date'},
				{name: 'AbbrStatoRecupero'},
				{name: 'StatoLegale'},  
				{name: 'AbbrClasse'},
				{name: 'tipoPag'},
				{name: 'ruolo'}, // usato solo nella lista di tipo PraticheSoggetto
				{name: 'agenzia'},
				{name: 'NumNote', type: 'int'},
				{name: 'NumAllegati', type: 'int'},
				{name: 'DataInizioAffido', type:'date'},
				{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
				{name: 'DataFineAffido', type:'date'}
			];
		
			columns = [
				{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: false, sortable:true},
				{dataIndex:'ruolo',width:65,		header:'Ruolo',align:'left', sortable:true, hidden:(this.IdC!='PraticheSoggetto')},
				{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
				{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
				{dataIndex:'rata',		width:35,	header:'N.rata',align:'right',filterable:false,sortable:true},
				{dataIndex:'insoluti',	width:35,	header:'N.ins.',align:'right',filterable:false,sortable:true,groupable:true},
				{dataIndex:'giorni',	width:35,	header:'Gg rit.',align:'right',filterable:false,sortable:true},
				{dataIndex:'importo',	width:45,	header:'Importo', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'ImpCapitaleAffidato',	width:70,	header:'Cap. affidato', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true,hidden:true,hideable:true,stateful:false},
	        	{dataIndex:'DataUltimoPagamento',   width:60,	header:'Ultimo pag.', xtype:'datecolumn', format:'d/m/y'},
				{dataIndex:'AbbrStatoRecupero',width:45,header:'Stato',hidden:this.hideStato,filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true},
				{dataIndex:'AbbrClasse',width:45,	header:'Class.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodiceFiscale', width:70, header:'Codice Fiscale',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Indirizzo', width:70, header:'Indirizzo',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CAP'    ,   width:30, header:'CAP',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'Localita',  width:70, header:'Localit&agrave;',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'SiglaProvincia', width:30, header:'Prov.',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'TitoloRegione', width:30, header:'Regione',hidden:true,hideable:true,exportable:true},
	        	{dataIndex:'CodRegolaProvvigione', width:30, header:'Codice',hidden:true,hideable:true,exportable:true},
				{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
				{dataIndex:'DataInizioAffido',width:55,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affido',align:'left', sizable:false, groupable:true, sortable:true},
			    {dataIndex:'DataFineAffido',width:55, xtype:'datecolumn', format:'d/m/y',	header:'Fine affido',align:'left', sizable:false, groupable:true, sortable:true}
			];
		}

		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/ricercaCorrenti.php',
				method: 'POST'
			}),   
			baseParams:{stato: this.stato, classe: this.classe, agenzia: this.agenzia, agente: this.agente, 
						prodotto: this.prodotto, lotto: this.lotto, lottomatica: this.IdLottomatica, task: this.IdC,
						searchFields:Ext.encode(this.searchFields),
						storico: this.isStorico},
			remoteSort: true,

			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				idProperty: 'IdContratto', //the property within each row object that provides an ID for the record (optional)
				fields: locFields
			}),
			listeners: { 
					
					// a fine load, toglie l'eventuale maschera di attesa messa da altri
					load: DCS.hideMask 
				}
  		});

		switch (this.IdC) {
			case "DettaglioExperianInvio":
			case "DettaglioExperianCliente":
			case "Provvigioni":
			case "ProvvigioniSingole":
			case "PSintesi":
			case "PSintesiAgenzia":
			case "PSintesiStato":
			case "NoteNonLette":
			case "Wrkflow":
			case "PraticheSoggetto":
				break;
			case "ComplexSearch":
				this.titolo = "Lista delle pratiche che soddisfano i criteri specificati";
				break;
			case "ComplexSearchRin":
				this.pagesize = 0;
				this.titolo = "Lista delle pratiche che soddisfano i criteri specificati";
				break;	
			default:
				this.titolo = 'Risultati per cliente/numero pratica contenenti: "'+this.IdC+'"';
		}

		Ext.apply(this,{
			height: 442,
			store: gstore,
			titlePanel: this.titolo,
			fields: locFields,
			innerColumns: columns,
			//actionCol: (this.IdC!='Provvigioni'),
			//selectCol: (this.IdC!='Provvigioni'),
			isDettaglioProvvigioni: (this.IdC=='Provvigioni' || this.IdC=='ProvvigioniSingole'),
			isStorico: this.isStorico,
			actionCol: !this.IdC.match(/^DettaglioExperian/)
		});
		
		// Aggiungo il bottone della ricerca pratiche nel tbar
        this.on('render',function(){
			if (this.selectCol && this.IdC == 'ComplexSearchRin') {
				var idObj = this.getId();
				var tbar = Ext.getCmp(idObj).getTopToolbar();
				tbar.insert(4,{
				    text: 'Seleziona per Rin./Acc.',
				    handler: function(b, event){
					   	var numPratica = [];
					   	var array = Ext.getCmp(idObj).SelmTPratiche.getSelections();
					   	if (array.length > 0) {
					   		for (i = 0; i < array.length; i++) {
					   			numPratica.push(array[i].get('numPratica'));
					   		}
					   		Ext.Ajax.request({
					   			url: 'server/gestioneRinegoziazione.php',
					   			params: {
					   				task: 'insertPratiche',
					   				pratiche: Ext.encode(numPratica)
					   			},
					   			method: 'POST',
					   			success: function(result, request){
					   				eval("res = " + result.responseText);
					   				if (res.success) 
					   					Ext.MessageBox.alert('Operazione eseguita', 'Le pratiche selezionate sono state aggiunte correttamente');
					   				else 
					   					Ext.MessageBox.alert('Operazione non eseguita', result.responseText);
					   			},
					   			failure: function(result, request){
					   				Ext.MessageBox.alert('Operazione non eseguita', result.responseText);
					   			}
					   		}); // fine parametri Ajax.request
						 } else {
							 Ext.MessageBox.alert('Attenzione', 'Non sono state selezionate pratiche');
						 }
				    }	
				});
			    tbar.insert(5,'-');
			    tbar.doLayout();
				
				/*var bbar = Ext.getCmp(idObj).getBottomToolbar();
				bbar.insert(11,'-');
				bbar.insert(13, {
				   text: 'Aggiungi Rinegoziazione',
				   handler: function(b,event) {
				   	  var numPratica = [];
					  var array = Ext.getCmp(idObj).SelmTPratiche.getSelections();
			    	  if (array.length > 0) {
					  	for(i = 0; i < array.length; i++) {
					  		numPratica.push(array[i].get('numPratica'));
						}
						Ext.Ajax.request({
						   url : 'server/gestioneRinegoziazione.php' , 
						   params: { task:'insertPratiche', pratiche: Ext.encode(numPratica)},
						   method: 'POST',
						   success: function ( result, request ) {
							  eval("res = "+result.responseText);
							  if(res.success) Ext.MessageBox.alert('Operazione eseguita', 'Le pratiche selezionate sono state aggiunte correttamente'); 
							  else Ext.MessageBox.alert('Operazione non eseguita', result.responseText); 										
						   },
						   failure: function ( result, request) { 
							  Ext.MessageBox.alert('Operazione non eseguita', result.responseText); 
						   } 	
						}); // fine parametri Ajax.request
					  } else {
					  	  Ext.MessageBox.alert('Attenzione', 'Non sono state selezionate pratiche');
					    }
				   }
			    });
				bbar.insert(14, '-');
				bbar.doLayout();*/
			}	
		});
				
		DCS.pnlSearch.superclass.initComponent.call(this, arguments);
	}
});
//--------------------------------------------------------------------------
// showDettaglioRateProvvigione
// Mostra la lista delle rate di un dettaglio provvigione
//--------------------------------------------------------------------------
function showDettaglioRateProvvigione(grid, rowIndex, colIndex)
{
    var rec = grid.store.getAt(rowIndex);

    DCS.showMask('',true);
	var pnl = new DCS.pnlDettaglioRateProvvigione(rec.json.IdProvvigione,rec.json.IdContratto);
	pnl.title = 'Contratto '+rec.json.CodContratto+ ' - Lotto '+grid.lotto;
	var win = new Ext.Window({
		width: 800, height:270, minWidth: 800, minHeight: 270,
		autoHeight:true,modal: true,
	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    title: 'Lista rate considerate nel calcolo provvigione per il lotto in esame',
		constrain: true,
		items: [pnl]
	});
	win.show();
}
