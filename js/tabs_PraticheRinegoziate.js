// Crea namespace DCS
Ext.namespace('DCS');
DCS.GridPraticheRinegoziate = Ext.extend(DCS.GridPratiche, {
	initComponent : function() {
		var IdStatoRinegoziazione;
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
					{name: 'DataFineAffido', type:'date'},
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
					{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
					{name: 'StatoRinegoziazione'}];

		var columns = [
		        {dataIndex:'DataFineAffido',width:60,xtype:'datecolumn', format:'d/m/y', header:'Fine affido',align:'center', resizable:true, groupable:true, sortable:true, hidden:false},
	        	{dataIndex:'numPratica',width:45,	header:'N.Pratica',align:'left', filterable: true, sortable:true,groupable:false},
	        	{dataIndex:'cliente',	width:90,	header:'Cliente',filterable:false,sortable:true},
{dataIndex:'CodCliente',width:70,	header:'Cod.Cliente',hidden:true,hideable:true},
	        	{dataIndex:'prodotto',	width:120,	header:'Prodotto',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'StatoRinegoziazione', width:120, header:'Stato Rinegoziazione', filterable:true,sortable:true,groupable:true,hidden:false},
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
	        	{dataIndex:'agenzia',	width:50,	header:'Agenzia',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'CodUtente',	width:30,	header:'Oper.',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'ListaGaranti', width:100, header:'Garanti',hidden:true,hideable:true,exportable:true,stateful:false}
	        	,{dataIndex:'StatoLegale', width:100, header:'Stato Legale',hideable:true,exportable:true,stateful:false,hidden:true}
	        	,{dataIndex:'StatoStragiudiziale', width:100, header:'Stato<br>Stragiudiziale',hideable:true,sortable:true,exportable:true,stateful:false,hidden:true}
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
			fields: locFields,
			innerColumns: columns
		});
		
		// Aggiungo il bottone della ricerca pratiche nel tbar
        this.on('render',function(){
			var idObj=this.getId();
			var toolBar = Ext.getCmp(idObj).getTopToolbar();
			toolBar.insert(4,{
					   id:'search_more',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   icon: 'images/lente.png',
					   text: 'Seleziona pratiche',
					   tooltip: 'Ricerca pratiche',
					   handler: avviaRicercaPratiche
					   
					});
			toolBar.insert(5,'-');

			// Aggiungo il bottone pre la rimozione dalla lista pratiche (solo per lo stato 1)
			if (this.IdStatoRinegoziazione==1)
			{
				toolBar.insert(4,{
					   id:'remove_selected',
					   xtype: 'button',
					   style: 'width:15; height:15',
					   icon: 'ext/examples/shared/icons/fam/delete.gif',
					   text: 'Rimuovi selezionate',
					   tooltip: 'Rimuove dalla lista le pratiche selezionate',
					   handler: rimuovePratiche,
					   sm: this.SelmTPratiche, // aggiunge propriet� custom per passare la colonna di selezione 
					   gstore: this.store // aggiunge propriet� custom per passare lo store
					});
				toolBar.insert(5,'-');
			}

			// aggiungo il pulsante per l'export dei dati contenuti nelle griglie in un unico file excel
			toolBar.insert(4,{
				   xtype: 'button',
				   style: 'width:15; height:15',
				   icon: 'images/export.png',
				   text: 'Esporta tutto',
				   tooltip: 'Esporta su excel i dati contenuti in tutte le griglie',
				   handler: function(){Ext.ux.Printer.exportXLS(this,1);},
				   scope: this,
				   sm: this.SelmTPratiche, // aggiunge propriet� custom per passare la colonna di selezione 
				   gstore: this.store // aggiunge propriet� custom per passare lo store
				});
			toolBar.insert(5,'-');
			
			toolBar.doLayout();
		});
				
        DCS.GridPraticheRinegoziate.superclass.initComponent.call(this, arguments);
		
				
	}
});

// dalla versione 0.9.34 (10/1/2013) la lista rinegoziazioni cambia e vale solo per gli operatori interni
DCS.PraticheRinegoziate = function(){

	return {
		create: function(){
			DCS.showMask();
			
			// TabPanel che contiene le varie liste
			var tabPanel = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: []
			});			

			// Lettura degli stati di rinegoziazione, per creare una lista per ciascuno stato
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: "select * FROM v_statorinegoziazione ORDER BY KeyOrd"
				},
				method: 'GET',
				reader: new Ext.data.JsonReader(
						{root: 'results',
						 id: 'IdStatoRinegoziazione'}, 
					     [{name: 'IdStatoRinegoziazione', type: 'int'}
					     , {name: 'TitoloStatoRinegoziazione'}
					     , {name: 'AbbrStatoRinegoziazione'}
					     ]),
				autoload: false,
				success: function(result, request){
						eval('var arr = (' + result.responseText + ').results');
						eval('var resp = (' + result.responseText + ').total');
						
						var grid = new Array();
						var listG = new Array();
						i1=0;
						for (i = 0; i < resp; i++) {
							if (i==1){
								grid[i1] = new DCS.GridPraticheRinegoziate({
									stateId: 'PraticheRinegoziate',
									stateful: true,
									titlePanel: 'Pratiche affidate per rinegoziazione',
									title: 'Affidate',
									task: "rinegozia_affidate",
									IdStatoRinegoziazione: 0,
									hideStato: true
								});
								listG.push(grid[i1]);
								i1++;							
							}
							grid[i1] = new DCS.GridPraticheRinegoziate({
								stateId: 'PraticheRinegoziate',
								stateful: true,
								titlePanel: 'Pratiche in stato: '+ arr[i]['TitoloStatoRinegoziazione'],
								title: arr[i]['AbbrStatoRinegoziazione'],
								task: "rinegozia_"+arr[i]['IdStatoRinegoziazione'],
								IdStatoRinegoziazione: arr[i]['IdStatoRinegoziazione'],
								hideStato: true
							});
							listG.push(grid[i1]);
							i1++;
						//console.log("l "+listG[i].titlePanel);
						}
						tabPanel.add(listG);

						DCS.hideMask(); // toglie la mask per caricamento tabs, prima che entri l'altra
						tabPanel.setActiveTab(0);
						//tabPanel.items.itemAt(0).store.load();
					},
				failure: function(result, request){
						DCS.hideMask();
						//eval('var resp = '+result.responseText);
						Ext.MessageBox.alert('Errore', resp.responseText);
				},
				scope: this
			});
			return tabPanel;
		}
	};
}();

//----------------------------------------------------------------
// rimuovePratiche
// Rimuove dalla lista dei candidati le pratiche selezionate
//----------------------------------------------------------------
function rimuovePratiche(btn,pressed) 
{
	// costruice array degli idcontratto selezionati
	var sel = btn.sm.getSelections();
	var ids = new Array();
	for (i=0; i<sel.length; i++) 
		ids.push(sel[i].get('IdContratto'));

	if (ids.length==0)
		Ext.Msg.alert("","Nessuna pratica selezionata");
	else // modifica stato rinegoziazione per i contratti dati
	{
		var myMask = new Ext.LoadMask(Ext.getBody(), {
			msg: "Rimozione in corso..."
		});
		myMask.show();
		Ext.Ajax.request({
			url: 'server/AjaxRequest.php', method:'POST',
			params: {task: 'exec', sql: 'UPDATE contratto SET IdStatoRinegoziazione=NULL WHERE IdContratto IN (0' + ids.join(',')+ ')'},
			scope: this,
			failure: function() 
					{	myMask.hide();
						Ext.Msg.alert("Operazione non riuscita", "Errore di comunicazione");
					}, 
			success: function(result, request)
					{
						myMask.hide();
						eval('var result = ' + result.responseText);
						if (result.error)
							Ext.Msg.alert("Operazione non riuscita", result.error);
						else
						{
							btn.gstore.reload();
						}
					}
		});
	}
}

//----------------------------------------------------------------
// avviaRicercaAvanzata
// Apre il form per la ricerca avanzata
//----------------------------------------------------------------
function avviaRicercaPratiche() 
{
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Qualche istante, prego..."});
	myMask.show();
	Ext.Ajax.request({
     url: 'server/formRicercaAvanzata.php', method:'POST',
		params: {idRic: 'RicRin'},
		failure: function() {Ext.Msg.alert("Impossibile aprire la pagina per la ricerca delle pratice da selezionare", "Errore Ajax");},
        success: function(req)
        {
			var formPanel;
            eval(req.responseText);
			if (formPanel!=undefined) {	// se costruito un form
	            var win = new Ext.Window({
	                width: formPanel.width+30, height:formPanel.height+30, 
	                minWidth: formPanel.width+30, minHeight: formPanel.height+30,
	                layout: 'fit', plain:true, bodyStyle:'padding:5px;',modal: true,
	                title:  "Seleziona pratiche per rinegoziazione e accodamento",
					constrain: true,
					modal: true,
					closable: true,
	                items: formPanel,
	                tools: [helpTool("Funzionediricercadiretta")]
	                });
	            win.show();
			}
			myMask.hide();
       } // fine corpo funzione Ajax.success
	} // fine corpo richiesta Ajax
  ); // fine parametri Ajax.request
}