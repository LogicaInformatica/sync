// Crea namespace DCS
Ext.namespace('DCS');

// Calcola il fiscal year da archiviare (fino ad almeno la fine di Aprile, propone l'archiviazione dell'anno prima)
var oggi = new Date();
var fiscalYear = oggi.getMonth()>4 ? (oggi.getFullYear()+1) : oggi.getFullYear();

// PRIMO TAB: griglia nel formato della tabs_Pratiche.js
// TABS SUCCESSIVI: griglia nel formato specifico delle svalutazioni storicizzate (non discende da tabs_Pratiche.js)
DCS.GridPraticheSvalutate = Ext.extend(DCS.GridPratiche, {
	initComponent : function() {

		var locFields;
		var columns;

		if (this.task=="svaluta") {
							
			Ext.apply(this,{
				grpField: '',
				grpDir: 'asc'
	    	});

			locFields = [{name: 'IdContratto'},
							{name: 'prodotto'},
							{name: 'numPratica'},
							{name: 'IdCliente', type: 'int'},
							{name: 'cliente'},{name: 'CodCliente'},
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
							{name: 'PercSvalutazione', type: 'float'},
							{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
							{name: 'Svalutazione', type: 'float'}];

			columns = [
	        	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'importo',	width:40,	header:'Deb. Tot', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
	        	{dataIndex:'PercSvalutazione',	width:25,	header:'% Sval.', xtype:'numbercolumn', format:'000,00 %/i',align:'right',filterable:true,sortable:true,hidden:false},
	        	{dataIndex:'Svalutazione',	width:50,	header:'Svalutazione',xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,hidden:false},
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
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UltimaAzione', width:100, header:'Ultima azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'DataUltimaAzione', width:100, header:'Data ult. azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'UtenteUltimaAzione', width:100, header:'Utente Ult.Azione',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'NotaEvento', width:100, header:'Nota',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'Garanzie', width:100, header:'Garanzie',hidden:true,hideable:true,exportable:true,stateful:false}
       		];
		}
		
		Ext.apply(this,{
			fields: locFields,
			innerColumns: columns
	    });

		DCS.GridPraticheSvalutate.superclass.initComponent.call(this, arguments);
		
		var printing=false;
		var loadedFirst=false;
		var idObj=this.getId();
		Ext.getCmp(idObj).getTopToolbar().getComponent(4).setHandler(function(){},this);
		
		this.getStore().addListener('load',function(Store, records, options ){
			var limit=Store.getCount();
			var Sum=0;
			for(var h=0;h<limit;h++){
				Sum=Sum+Store.getAt(h).get('Svalutazione');
			}
			Sum=Ext.util.Format.number(Sum, '0.000,00/i');
			if(!loadedFirst)
			{
				var btnArchivia = {type: 'button', text: '&nbsp;Archivia FY '+fiscalYear,  icon: 'images/floppy_disk.png', handler: DCS.archiviaSvalutazione, scope:this};
				Ext.getCmp(idObj).getBottomToolbar().insert(12,'-');
				Ext.getCmp(idObj).getBottomToolbar().insert(13,btnArchivia);
				Ext.getCmp(idObj).getBottomToolbar().insert(14,'-');
				Ext.getCmp(idObj).getBottomToolbar().insert(15,{xtype:'tbtext', text:"Totale svalutazione: "+Sum, cls:'panel-title'});
				Ext.getCmp(idObj).getBottomToolbar().insert(16,'-');
				Ext.getCmp(idObj).getBottomToolbar().doLayout();
				loadedFirst=true;
			}else{
				Ext.getCmp(idObj).getBottomToolbar().getComponent(15).setText("Totale svalutazione: "+Sum);
			}
			//aggiunta del listener della stampa
			Ext.getCmp(idObj).getTopToolbar().getComponent(4).addListener('click',function (Button,e){
				//console.log(">>printing "+printing);
				//console.log(">>loadedFirst "+loadedFirst);
				if(!printing)
				{
					printing=true;
					var array = Ext.getCmp(idObj).SelmTPratiche.getSelections();
		    		//console.log("store "+Ext.getCmp(idObj).getStore().getCount());
		    		//console.log("selm "+array.length);
		    		var j = Ext.getCmp(idObj).getStore().getCount();//elementi nello store della griglia
		    		//console.log("elementi iniziali "+j);
		    		if(array.length>0){
			    		var deleteArr=new Array();
			    		var ind=0;
			    		//scarnifica lo store della griglia di ogni elemento non selezionato
			    		for(var h=0;h<j;h++)//per ogni elemento nello store
			    		{
			    			//console.log("elemento h-esimo "+this.gstore.getAt(h).get('CodContratto'));
			    			if(!Ext.getCmp(idObj).SelmTPratiche.isSelected(Ext.getCmp(idObj).getStore().getAt(h)))
			    			{
			    				//tiene conto degli indici da eliminare
			    				deleteArr[ind]=h;
			    				ind++;
			    			}
			    		}
		
			    		for(var h=0;h<ind;h++)
			    		{
			    			//rimozione
			    			//console.log("Rimuove indice: "+deleteArr[h]);
			    			Ext.getCmp(idObj).getStore().remove(Ext.getCmp(idObj).getStore().getAt(deleteArr[h]-h));
							//se nn � nella selezione viene rimosso con 
							//riallineamento dell'indice interno a deleteArr
			    		}
			    		//ora lo store della griglia corrisponde a quello da stampare
		    		}
			    	var l = Ext.getCmp(idObj).getStore().getCount();
			    	//console.log("elementi finali "+l);
			    	if(l!=0){
			    		var totalImp=0;
			    		for(var h=0;h<l;h++)
			    		{
			    			totalImp=totalImp+Ext.getCmp(idObj).getStore().getAt(h).get('Svalutazione');
			    		}
			    		totalImp=Ext.util.Format.number(totalImp, '0.000,00/i');
			    		//inserisci la riga della somma
			    		var defaultData = {
			    				IdContratto:'',
			    				IdCliente:'',
			    				prodotto:'',
			    				numPratica:'',
			    				cliente:'TOTALE:',
			    				rata:'',
			    				insoluti:'',
			    				giorni:'',
			    				importo:'',
			    				ImpInteressiMora:'',
			    				ImpSpeseRecupero:'',
			    				ImpPagato:'',
			    				ImpCapitale:'',
			    				AbbrStatoRecupero:'',
			    				AbbrClasse:'',
			    				tipoPag:'',
			    				agenzia:'',
			    				CodUtente:'',
			    				DataScadenza:'',
			    				DataCambioStato:'',
			    				DataCambioClasse:'',
			    				DataScadenzaAzione:'',
			    				Telefono:'',
			    				CodiceFiscale:'',
			    				Indirizzo:'',
			    				CAP:'',
			    				Localita:'',
			    				SiglaProvincia:'',
			    				TitoloRegione:'',
			    				CodRegolaProvvigione:'',
			    				NumNote:'',
			    				Categoria:'',
			    				NumAllegati:'',
			    				PercSvalutazione:'',
			    				Svalutazione:totalImp
			                };
			            var recId = (l); // provide unique id
			            //console.log("id "+recId);
			            var store = Ext.getCmp(idObj).getStore();
			            var p = new store.recordType(defaultData, recId); // create new record
			            Ext.getCmp(idObj).getStore().insert(l, p); // insert a new record into the store (also see add)
			            //stampa
			        	Ext.ux.Printer.print(Ext.getCmp(idObj),true);
			    	}
			    	//console.log("elementi dopo aggiunta "+this.gstore.getCount());
			    	var lo=Ext.getCmp(idObj).getStore().lastOptions;
		    		//Ext.getCmp(idObj).getStore().reload();
			    	Ext.getCmp(idObj).getStore().load({
			    		options :lo,
			    		callback : function(rows,options,success) {
			    			//console.log("doporeload");
			    			printing=false;
			    		}
			    	});
				}
			});
		});
		
		
	}
});

//------------------------------------------------------------------------------
// Griglia dei tabs successivi al primo
//------------------------------------------------------------------------------
DCS.GridSvalStor = Ext.extend(Ext.grid.GridPanel, {
	pagesize: 25,
	year: 0,

	gstore: null,
	titlePanel: '',
		
	initComponent : function() {
    	var fields, columns;
		fields = [{name: 'IdContratto'},
		     	  {name: 'Prodotto'},
		    	  {name: 'CodContratto'},
                  {name: 'IdCliente', type: 'int'},
		    	  {name: 'cliente'},{name: 'CodCliente'},
		    	  {name: 'ImpDebito',  type: 'float'},
                  {name: 'PercSvalutazione', type: 'float'},
		    	  {name: 'Svalutazione', type: 'float'}
                  ];

				columns = [
				        	{dataIndex:'CodContratto',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
				        	{dataIndex:'Cliente',	width:120,	header:'Cliente',filterable:false,sortable:true},
				        	{dataIndex:'Prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
				        	{dataIndex:'ImpDebito',	width:100,	header:'Debito', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true},
				        	{dataIndex:'PercSvalutazione',	width:50,	header:'% Sval.', xtype:'numbercolumn', format:'000,00 %/i',align:'right',filterable:true,sortable:true},
				        	{dataIndex:'Svalutazione',	width:50,	header:'Svalutazione',xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true}
 			    	      ];
								
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneSvalutazione.php',
				method: 'GET'
			}),   
			baseParams:{task: 'read', fy: this.year},
			remoteSort: true,
			groupField: "",
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});

		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
	            hideGroupedColumn: true
           }),
    		columns: columns,
			listeners: {
				activate: function(pnl) {
					this.store.setBaseParam('attiva','Y'); 
					var lastOpt = this.store.lastOptions;
					if (!lastOpt || lastOpt.params==undefined) {
						if (this.pagesize>0) {
							this.store.load({
								params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
									start: 0, //pass start/limit parameters for paging
									limit: this.pagesize
								}
							}); 
						} else {
							this.store.load(); 
						}
					}
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
	                '->', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaSvalutazioni"),' '
				];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.gstore,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		
		var loadedFirst=false;
		var idObj=this.getId();
		this.getStore().addListener('load',
				function(store, records, options )
				{
					var Sum=0;
					for (var h=0; h<store.getCount(); h++)
						Sum = Sum + store.getAt(h).get('Svalutazione');
					
					Sum = Ext.util.Format.number(Sum, '0.000,00/i');
					if (!loadedFirst)
					{
						Ext.getCmp(idObj).getBottomToolbar().insert(12,'-');
						Ext.getCmp(idObj).getBottomToolbar().insert(13,{xtype:'tbtext', text:"Totale svalutazione: "+Sum, cls:'panel-title'});
						Ext.getCmp(idObj).getBottomToolbar().insert(14,'-');
						Ext.getCmp(idObj).getBottomToolbar().doLayout();
						loadedFirst = true;
					}
					else
					{
						Ext.getCmp(idObj).getBottomToolbar().getComponent(13).setText("Totale svalutazione: "+Sum);
					}
				}
		);
		DCS.GridSvalStor.superclass.initComponent.call(this, arguments);
	}
});

//------------------------------------------------------------------------------
//  Archiviazione fiscal year
//------------------------------------------------------------------------------
DCS.archiviaSvalutazione = function()
{
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Archiviazione in corso..."
	});											
	myMask.show();
	Ext.Ajax.request({
		url: 'server/gestioneSvalutazione.php', method:'GET',
		params: {task:'archive',fy:fiscalYear},
		success: function ( result, request ) 
				 {
					var jsonData = Ext.util.JSON.decode(result.responseText);
					if (jsonData.success)
					{	// Se � andato bene, pu� darsi che serva un nuovo tab per il fiscal year in corso
						Ext.MessageBox.alert('', jsonData.error);
						if (jsonData.isnew) // indica che � stata una nuova archivazione
						{
							var grid = new DCS.GridSvalStor({
								stateId: 'PraSvalutateYear',
								stateful: true,
								titlePanel: 'Archivio svalutazioni FY '+ fiscalYear,
								title: 'Fiscal Year '+ fiscalYear,
								year: fiscalYear,
								hideStato: true
							});
							Ext.getCmp('TabPanelSval').add(grid);
						}
					}
					else
						Ext.MessageBox.alert('Errore', jsonData.error);
				 },
		failure: function (obj)
				 {
					Ext.MessageBox.alert('Errore', 'Archiviazione non riuscita');
				 },
		scope: this
	});
 	myMask.hide();
}

//------------------------------------------------------------------------------
// Creazione pannello multi-tab
//------------------------------------------------------------------------------
DCS.PraticheSvalutate = function(){

	return {
		create: function(){
			var grid1 = new DCS.GridPraticheSvalutate({
				stateId: 'PraSvalutate',
				stateful: true,
				titlePanel: 'Lista svalutazioni correnti',
				title: 'Svalutazioni correnti',
				task: "svaluta",
				hideStato: true
			});
					
			var panel = new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'TabPanelSval',
				items: [grid1]
			});
			
			// Genera gli altri tabs corrispondenti a ciascun anno archiviato
			Ext.Ajax.request({
				url: 'server/gestioneSvalutazione.php', method:'GET',
				params: {task:'list'},
				failure: function (obj)
				{
					Ext.MessageBox.alert('Errore', 'Errore in lettura dei dati archiviati');
				},

				success: function ( result, request ) 
				{
					var jsonData = Ext.util.JSON.decode(result.responseText);
					if (!jsonData.success)
						Ext.MessageBox.alert('Errore', jsonData.error);

					var tabs = new Array();
					for (var i=0; i<jsonData.years.length; i++)
					{
						var year = jsonData.years[i];
						var grid = new DCS.GridSvalStor({
							stateId: 'PraSvalutateYear',
							stateful: true,
							titlePanel: 'Archivio svalutazioni FY '+year,
							title: 'Fiscal Year '+ year,
							year: year,
							hideStato: true
						});
						tabs.push(grid);
					}
					Ext.getCmp('TabPanelSval').add(tabs);
					Ext.getCmp('TabPanelSval').setActiveTab(jsonData.years.length); // rende corrente l'ultimo fiscal year
				},
				scope:this
			});
			return panel;
		}
	};
	
}();
