// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheStatiStragiudiziali = Ext.extend(DCS.GridPratiche, {
	IdCategoria:'',
	
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
							{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
							{name: 'StatoLegale'},  
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
			        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
			        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
			        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
			        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
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
			        		hidden:(this.task=='inAttesa' || this.task=='interne'  || this.task=='workflow')},
			        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
			        	{dataIndex:'ListaRate', width:30, header:'Lista Rate',hidden:true,hideable:true,exportable:true},
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
	            	type: 'list',options:[],
	            	dataIndex: 'prodotto'
	        	}, {
	            	type: 'list',  options: [],
	            	dataIndex: 'classif'
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
			IdCategoria:this.IdCategoria
	    });

	       this.on('render',function(){
				var idObj=this.getId();
				var toolBar = Ext.getCmp(idObj).getTopToolbar();
				// aggiungo il pulsante per l'export dei dati contenuti nelle griglie in un unico file excel
				toolBar.insert(8,{
					   id:'export_all',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   icon: 'images/export.png',
					   text: 'Esporta tutto',
					   tooltip: 'Esporta su excel i dati relativi a tutti gli stati stragiudiziali',
					   handler: function(){Ext.ux.Printer.exportXLS(this,1,"Pratiche in Affido Legale");},
					   scope: this,
					   sm: this.SelmTPratiche, // aggiunge propriet� custom per passare la colonna di selezione 
					   gstore: this.store // aggiunge propriet� custom per passare lo store
					});
				toolBar.insert(9,'-');
				
				toolBar.doLayout();
			});
				
		DCS.GridPraticheStatiStragiudiziali.superclass.initComponent.call(this,arguments);
	}
});
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.PraticheStatiStragiudiziali = function() {
	//var idTabs;

	return {
				
		create: function(){
			DCS.showMask();
			var tp = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabsstrg',
				items: []
			});
			
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: "SELECT IdStatoStragiudiziale,CodStatoStragiudiziale,TitoloStatoStragiudiziale,1 AS ordine FROM statostragiudiziale"
						 +" UNION ALL SELECT 0,'NUL','Non specificato',0 as ordine order by ordine,TitoloStatoStragiudiziale"
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var arr = ('+result.responseText+').results');
					eval('var resp = ('+result.responseText+').total');
					for(i=0;i<resp;i++){
						nomeG = "gridSle"+i; 
						tp.add(new DCS.GridPraticheStatiStragiudiziali({
							stateId: 'PraSle',
							stateful: true,
							titlePanel: 'Lista pratiche in stato: '+arr[i]['TitoloStatoStragiudiziale'],
							title: arr[i]['TitoloStatoStragiudiziale'],
							task: 'statostragiudiziale',
							sqlExtraCondition : arr[i]['IdStatoStragiudiziale']==0 ? "IdStatoStragiudiziale IS NULL": ("IdStatoStragiudiziale="+arr[i]['IdStatoStragiudiziale'])
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
				scope: this
			});
			return tp;
		}
	};
}();