// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPraticheWrkf = Ext.extend(DCS.GridPratiche, {
	IdStatRec:'',
	codProcedura: '',
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
				{name: 'StatoInDBT'}];

	var columns = [
    	{dataIndex:'DataCambioStato',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Data stato',align:'left', filterable: true, groupable:true, sortable:true},
    	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
    	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
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
			IdStatoRecupero:this.IdStatRec,
			codProcedura: this.codProcedura
	    });

		// Nel workflow di cessione aggiungo i bottoni per esportare i files excel speciali 
		if (this.IdStatRec>=80 && this.IdStatRec<=84) { // stati del workflow di cessione
			this.on('render',function(){
				if (this.buttonAdded) return;
				this.buttonAdded = true;
				var idObj=this.getId();
				var toolBar = Ext.getCmp(idObj).getTopToolbar();
				toolBar.insert(8,{
					   id:'export_ces_clienti',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   hidden:!CONTEXT.EXPORT,
					   icon: 'images/export.png',
					   text: 'File Excel clienti',
					   tooltip: 'Estratto Excel Cessioni - Clienti',
					   scope: this,
					   handler: function() {this.exportXLS(this,'v_cessioni_con_cliente','File_cessioni_clienti');}
					});
				toolBar.insert(9,{
					   id:'export_ces_garanti',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   hidden:!CONTEXT.EXPORT,
					   icon: 'images/export.png',
					   text: 'File Excel garanti',
					   tooltip: 'Estratto Excel Cessioni - Garanti',
					   scope: this,
					   handler: function() {this.exportXLS(this,'v_cessioni_con_garante','File_cessioni_garanti');}
					});
				toolBar.doLayout();
			});
		} else if (this.IdStatRec>=14 && this.IdStatRec<=17) { // stati del workflow di proposta DBT
			this.on('render',function(){ 
				if (this.buttonAdded) return;
				this.buttonAdded = true;
				var idObj=this.getId();
				var toolBar = Ext.getCmp(idObj).getTopToolbar();
				toolBar.insert(8,{
					   id:'export_dbt',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   hidden:!CONTEXT.EXPORT,
					   icon: 'images/export.png',
					   text: 'Estratto dati DBT',
					   tooltip: 'Estratto Excel dati proposta DBT',
					   scope: this,
					   handler: function() {this.exportXLS(this,'v_pratiche_dbt','Estratto_DBT');}
					});
				toolBar.doLayout();
			});
		}
		DCS.GridPraticheWrkf.superclass.initComponent.call(this,arguments);
	}
	// Alla pressione di uno dei due tasti Export speciali, richiama la funzione di export
	,exportXLS: function(grid,viewname,titolo) {
		if (viewname=='v_pratiche_dbt') {
			var columns =  [
    	    	{dataIndex:'numPratica',	width:35, 	header:'N.Pratica', xtype: 'string'},
    	    	{dataIndex:'Cliente',		width:90,	header:'Cliente'},  
    	    	{dataIndex:'Prodotto',		width:90,	header:'Prodotto'},
    	    	{dataIndex:'Dealer',		width:90,	header:'Dealer'},
    	    	{dataIndex:'Regione',		width:90,	header:'Regione'},
    	    	{dataIndex:'Stato',			width:90,	header:'Stato'},
    	    	{dataIndex:'ImpInsoluto',	width:70,	header:'Importo',xtype:'numbercolumn',format:'0.000,00/i',align:'right'},
    	    	{dataIndex:'AgenziaProx',	width:70,	header:'Agenzia prox affidamento'},
    	    	{dataIndex:'DataVendita',	width:70,	header:'Vettura venduta'},
    	    	{dataIndex:'Nota',			width:70,	header:'Note'},
    	    	{dataIndex:'DataStato',		width:30,	xtype:'datecolumn', format:'d/m/y',	header:'Data stato'},
    	    	{dataIndex:'Garanzie',		width:90,	header:'Garanzie'}
    	    	];
		} else {
			var columns =  [
	        	{dataIndex:'Modulo',		width:25, 	header:'Modulo'},
	        	{dataIndex:'Pratica',		width:45,	header:'Pratica', xtype: 'string'}, // tipo inventato per export.php 
	        	{dataIndex:'NumRate',		width:30,	header:'Numero Rate',	align:'right'},
	        	{dataIndex:'NumRatePagate',	width:30,	header:'Rate Pagate',	align:'right'},
	        	{dataIndex:'DataDBT',		width:30,	header:'DataPassaggio Sofferenza',xtype:'datecolumn',align:'left',format:'d/m/y'},
	        	{dataIndex:'ImpDBT',		width:70,	header:'Importo Passaggio Sofferenza',xtype:'numbercolumn',format:'0.000,00/i',align:'right'},
	        	{dataIndex:'ImpCapitale',	width:70,	header:'Capitale Impagato', xtype:'numbercolumn',format:'0.000,00/i',align:'right'},
	        	{dataIndex:'Cliente',		width:90,	header:'Cliente'},
	        	{dataIndex:'DataNascita',	width:30,   xtype:'datecolumn', format:'d/m/y',	header:'Data Nascita'},
	        	{dataIndex:'CodiceFiscale', width:70, 	header:'Codice Fiscale', xtype: 'string'},
	        	{dataIndex:'Indirizzo', 	width:70, 	header:'Indirizzo'},
	        	{dataIndex:'Localita',  	width:70, 	header:'Localit&agrave;'},
	        	{dataIndex:'Cap',		   	width:30, 	header:'CAP', xtype: 'string'},
	        	{dataIndex:'Telefono',  	width:70, 	header:'Telefono', xtype: 'string'},
	        	{dataIndex:'Telefono2',  	width:70, 	header:'Telefono 2', xtype: 'string'},
	        	{dataIndex:'Cellulare', 	width:70, 	header:'Telefono Cell.', xtype: 'string'},
	        	{dataIndex:'TelefonoSede',  width:70, 	header:'Telefono Sede', xtype: 'string'},
	        	{dataIndex:'CodConvenzionato',  width:70, header:'Cod. Convenzionato', xtype: 'string'},
	        	{dataIndex:'Convenzionato',  width:70, header:'Convenzionato'},
	        	{dataIndex:'DataLiquidazione',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Data liquidazione'},
	        	{dataIndex:'Finanziato', width:70, header:'Finanziato',xtype:'numbercolumn',format:'0.000,00/i',align:'right'}
	        	];
		}		
		var escapeForm=new Ext.form.FormPanel({
		    standardSubmit: true,
		    renderTo: Ext.getBody(),
		    hidden: true, 
		    floating: true,
		    defaults: {xtype: 'hidden'},
		    items: [
		        {name: 'titolo',		value: titolo},
		        {name: 'filename',		value: titolo},
		        {name: 'url',			value: grid.store.proxy.url},
				{name: 'baseParams',	value: Ext.encode(grid.store.baseParams)},
				{name: 'columns',		value: Ext.encode(columns)},
				{name: 'expAll',		value: viewname}
		    ],
		    url: 'server/export.php'
		});
		var frm = escapeForm.getForm();
		frm.getEl().dom.target='_blank';
		frm.submit();
		escapeForm.destroy();		
	}
});
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.PraticheWorkflow = function() {
	//var idTabs;

	return {
				
		create: function(){
			DCS.showMask();
			var tabPanelWkf = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabWrkF',
				items: []
			});
			//tabs
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: "select p.IdProcedura,p.CodProcedura,p.TitoloProcedura from" 
						+ " procedura p"
						+ " where CURDATE() BETWEEN p.DataIni AND p.DataFin Order by TitoloProcedura"
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var resp = ' + result.responseText);
					var arr = resp.results;
					var listP = new Array();
					var corrispondenze = new Array();
					for (i = 0; i < resp.total; i++) {
						var indicesubtab = 'subTabWrkF'+i;
						var subTabPanelWkf = new Ext.TabPanel({
							activeTab: 0,
							enableTabScroll: true,
							title: arr[i].TitoloProcedura,
							flex: 1,
							id: indicesubtab,
							items: []
						});
						//subTabs
						var link=[i,arr[i].IdProcedura];
						corrispondenze.push(link);
						Ext.Ajax.request({
							url: 'server/AjaxRequest.php',
							params: {
								task: 'read',
								sql: "SELECT distinct IdStatoRecuperoSuccessivo,CodStatoRecupero,TitoloStatoRecupero,ap.IdProcedura,CodProcedura" 
									+ " FROM statoazione sa"
									+ " left join statorecupero sr on(sa.idstatorecuperosuccessivo=sr.idstatorecupero)"
									+ " left join azioneprocedura ap on(sa.idazione=ap.idazione)"
									+ " left join procedura p ON p.IdProcedura=ap.IdProcedura"
									+ " where sa.idstatorecuperosuccessivo is not null and sa.idstatorecuperosuccessivo!=0 and ap.idprocedura="+arr[i].IdProcedura+" order by sr.ordine"
							},
							method: 'POST',
							autoload: true,
							success: function(result, request){
								eval('var resp = ' + result.responseText);
								var arrSub = resp.results;
								var nomeG='';
								var listPSub = new Array();
								var grid = new Array();
								for (j = 0; j < resp.total; j++) {
									nomeG="Wtabs"+j;
									grid[nomeG] = new DCS.GridPraticheWrkf({
													IdStatRec:arrSub[j].IdStatoRecuperoSuccessivo,
													task: "workflow",
													title:arrSub[j].TitoloStatoRecupero,
													titlePanel: 'Lista pratiche in '+arrSub[j].TitoloStatoRecupero,
													stateful: true,
													stateId:arrSub[j].CodStatoRecupero,
													codProcedura: arrSub[j].CodProcedura
													});
									//idTabs.push(arr[i].IdProcedura);
									listPSub.push(grid[nomeG]);
									
								}
								
								for(var h =0;h<corrispondenze.length;h++)
								{
									var proc=corrispondenze[h][1];
									if(proc==arrSub[0].IdProcedura)
									{
										var obj='subTabWrkF'+corrispondenze[h][0];
										Ext.getCmp(obj).add(listPSub);
										DCS.hideMask();
										Ext.getCmp(obj).setActiveTab(0);
									}
								}
								
							},
							failure: function ( result, request) { 
								eval('var resp = '+result.responseText);
								Ext.MessageBox.alert('Failed', resp.results); 
							},
							scope: this
						});
						//end creation of subtabs iesimi
						//aggiunta subtabs al tab superiore
						listP.push(subTabPanelWkf);
					}
					Ext.getCmp('tabWrkF').add(listP);
					Ext.getCmp('tabWrkF').setActiveTab(0);
				},
				failure: function ( result, request) { 
					eval('var resp = '+result.responseText);
					Ext.MessageBox.alert('Failed', resp.results); 
				},
				scope: this
			});
			return tabPanelWkf;
		}
	};
}();