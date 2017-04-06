// Sintesi delle pratiche da convalidare viste da operatore interno ed esterno
Ext.namespace('DCS');

DCS.GridPraticheAzioniSpeciali = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	titlePanel: '',
	pagesize : PAGESIZE,
	btnMenuAzioni: null,
	task: 'read',
	stato: null,
	filters: null,
		
	initComponent : function() {
	
    	   var fields = [	
    	                 	{name: 'IdAzioneSpeciale', type:'int'},
    	                 	{name: 'IdAzione', type:'int'},
    	                 	{name: 'TitoloAzione'},
    	                 	{name: 'IdContratto'},
    	                 	{name: 'IdReparto'},
    	                 	{name: 'TitoloUfficio'},
    						{name: 'CodContratto'},
    						{name: 'NomeCliente'},
    						{name: 'IdUtente', type:'int'},
    						{name: 'IdApprovatore', type:'int'},
    						{name: 'NominativoUtente'},
    						{name: 'NominativoApprovatore'},
    						{name: 'DataEvento',  type: 'date', dateFormat:'Y-m-d H:i:s'},
    						{name: 'DataApprovazione', type: 'date', dateFormat:'Y-m-d H:i:s'},
    						{name: 'Nota'},
    						{name: 'Stato'},
    						{name: 'DescStato'},
							{name: 'UserSuper'},
							{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
							{name: 'IdAllegato'}
    					];
    	
								
			var columns = [
				           	{dataIndex:'IdAzioneSpeciale',width:45,	header:'IdAzioneSpeciale',align:'left', filterable: false, sortable:false,groupable:false, hidden:true},
				           	{dataIndex:'Nota',width:45,	header:'Nota',align:'left', filterable: false, sortable:false,groupable:false, hidden:true},
				           	{dataIndex:'CodContratto',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
				           	{dataIndex:'NomeCliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
				           	{dataIndex:'TitoloAzione',	width:90,	header:'TitoloAzione',filterable:false,sortable:true},
				           	{dataIndex:'NominativoUtente',	width:90,	header:'Autore',filterable:false,sortable:true},
							{dataIndex:'DataEvento', width:70, xtype:'datecolumn', format:'d/m/y H:i', header:'Data evento',sortable:true}
						  	];
				
		if (CONTEXT.InternoEsterno == 'I')
		{
			columns.push(
					{dataIndex:'TitoloUfficio',	width:90,	header:'Agenzia',filterable:false}
		    	);
		}
		if (this.stato == "A" || this.stato == "R")
		{
			columns.push(
					{dataIndex:'NominativoApprovatore',	width:90,	header:'Approvatore',filterable:false,sortable:true},
					{dataIndex:'DataApprovazione', width:70, xtype:'datecolumn', format:'d/m/y H:i', header:'Data approvazione',sortable:true}
		    	);
		}
		
		columns.push(
			{header:'Allegato', 
			 dataIndex:'IdAllegato', 
			 renderer: function(val){
			 	if(val != null) {
				  return "<img src='images/con_allegati.png'>";	
				} else {
				    return ;	
				}
			 }, 
			 width:35,
			 //align: 'center', 
			 filterable:false, 
			 sortable:false}
		);
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheAzioniSpeciali.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, stato:this.stato, idUtente:this.utenteId},
			remoteSort: true,
			//groupField: groupedOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			}),
			listeners:{
				load : function(Store, records, options)
				{
					//controllo di chi sta aprendo la griglia allegati
					var appoggio;
					for(var j=0; j<Store.getCount(); j++)
					{
						appoggio = Store.getAt(j);
						// se è una registrazione fatta impersonando, fa vedere i due userid all'amministratore
						// e solo quello dell'impersonatore all'utente ordinario
						if ( Store.getAt(j).get('lastSuper')!='' && Store.getAt(j).get('lastSuper') != null)
						{
							if (CONTEXT.IMPERSONA) // è un utente amministratore
								appoggio.set('LastUser',Store.getAt(j).get('lastSuper')+"/"+Store.getAt(j).get('LastUser'));
							else
								appoggio.set('LastUser',Store.getAt(j).get('lastSuper'));
						}
						appoggio.commit();
					}
				}
			}
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
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					//controllo se è un'azione speciale con allegato
					if(rec.get('IdAllegato')!=null) {
					  DCS.showAzioneSpecialeDetail.create(rec.get('IdAzioneSpeciale'),rec.get('IdContratto'),rec.get('NominativoUtente'),true);	
					} else {
						DCS.showAzioneSpecialeDetail.create(rec.get('IdAzioneSpeciale'),rec.get('IdContratto'),rec.get('NominativoUtente'),false);
					  }
				},
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
	                '-', helpButton("ListaConvalide"),' '
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

		DCS.GridPraticheAzioniSpeciali.superclass.initComponent.call(this, arguments);

	}

});

DCS.PraticheAzioniSpeciali = function() {
	
	    if(CONTEXT.InternoEsterno == 'E') {
		
		  var gridPraticheAzioniDaApprovare = new DCS.GridPraticheAzioniSpeciali({
			 stateId: 'PraticheAzioniSpecialiDaConvalidare',
			 stateful: true,
		 	 titlePanel: 'Sintesi pratiche che richiedono la convalida delle azioni',
			 title: 'Da convalidare',
			 id: 'GridPraticheAzioniSpecialiW',
			 task: "read",
			 stato: "W"
		  });
			
		  var gridPraticheAzioniApprovate = new DCS.GridPraticheAzioniSpeciali({
			 stateId: 'PraticheAzioniSpecialiConvalidate',
			 stateful: true,
			 titlePanel: 'Sintesi pratiche con azioni convalidate',
			 title: 'Convalidate',
			 id: 'GridPraticheAzioniSpecialiA',
			 task: "read",
			 stato: "A"
		  });
		  	
		  var gridPraticheAzioniRespinte = new DCS.GridPraticheAzioniSpeciali({
			 stateId: 'PraticheAzioniSpecialiRespinte',
			 stateful: true,
			 titlePanel: 'Sintesi pratiche con azioni respinte',
			 title: 'Respinte',
			 id: 'GridPraticheAzioniSpecialiR',
			 task: "read",
			 stato: "R"
		  });
			
		  return new Ext.TabPanel({
	   		   activeTab: 0,
			   enableTabScroll: true,
			   flex: 1,
			   items: [gridPraticheAzioniDaApprovare,gridPraticheAzioniApprovate,gridPraticheAzioniRespinte]
		  });
		} else {
			DCS.showMask();
			var tabPanelAsp = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabAspF',
				items: []
			});
			
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: "select distinct IdOperatore, NomeOperatore" +
					" from v_praticheazionispeciali vpas" +
					" order by NomeOperatore asc"
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var resp = ' + result.responseText);
					var arr = resp.results;
					var listP = new Array();
					for (i = 0; i < resp.total; i++) {
						var gridPraticheAzioniDaApprovare = new DCS.GridPraticheAzioniSpeciali({
							stateId: 'PraticheAzioniSpecialiDaConvalidare',
							stateful: true,
							titlePanel: 'Sintesi pratiche che richiedono la convalida delle azioni',
							title: 'Da convalidare',
							id: 'GridPraticheAzioniSpecialiW' + i,
							task: "read",
							stato: "W",
							utenteId: arr[i].IdOperatore
						});
						
						var gridPraticheAzioniApprovate = new DCS.GridPraticheAzioniSpeciali({
							stateId: 'PraticheAzioniSpecialiConvalidate',
							stateful: true,
							titlePanel: 'Sintesi pratiche con azioni convalidate',
							title: 'Convalidate',
							id: 'GridPraticheAzioniSpecialiA' + i,
							task: "read",
							stato: "A",
							utenteId: arr[i].IdOperatore
						});
						
						var gridPraticheAzioniRespinte = new DCS.GridPraticheAzioniSpeciali({
							stateId: 'PraticheAzioniSpecialiRespinte',
							stateful: true,
							titlePanel: 'Sintesi pratiche con azioni respinte',
							title: 'Respinte',
							id: 'GridPraticheAzioniSpecialiR' + i,
							task: "read",
							stato: "R",
							utenteId: arr[i].IdOperatore
						});

						var gridPraticheAzioniChiuse = new DCS.GridPraticheAzioniSpeciali({
							stateId: 'PraticheAzioniSpecialiChiuse',
							stateful: true,
							titlePanel: 'Sintesi pratiche con azioni chiuse/decadute',
							title: 'Chiuse',
							id: 'GridPraticheAzioniSpecialiC' + i,
							task: "read",
							stato: "C",
							utenteId: arr[i].IdOperatore
						});

						var indicesubtab = 'subTabAspF' + i;
						var subTabPanelAsp = new Ext.TabPanel({
							activeTab: 0,
							enableTabScroll: true,
							title: arr[i].NomeOperatore,
							flex: 1,
							id: indicesubtab,
							items: [gridPraticheAzioniDaApprovare, gridPraticheAzioniApprovate, gridPraticheAzioniRespinte,gridPraticheAzioniChiuse]
						});
						//end creation of subtabs iesimi
						//aggiunta subtabs al tab superiore
						listP.push(subTabPanelAsp);
					}
					
					Ext.getCmp('tabAspF').add(listP);
					DCS.hideMask();
					Ext.getCmp('tabAspF').setActiveTab(0);
				},
				failure: function(result, request){
					eval('var resp = ' + result.responseText);
					Ext.MessageBox.alert('Failed', resp.results);
				},
				scope: this
			});
			
			return tabPanelAsp;
		  }  
		
	};