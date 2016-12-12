// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS');

var wind;

DCS.pnlAddP = Ext.extend(Ext.form.FormPanel,{
	
	initComponent : function() {
		Ext.apply(this,{
			autoHeight: true,
			frame: true,  
			title: 'Aggiungi profilo',
			layout: 'form',
		    items:[{
				xtype:'panel', layout:'form', labelWidth:100, columnWidth:.50, defaultType:'textfield',
				defaults: {anchor:'95%', readOnly:false},
				items: [
						{fieldLabel:'Codice del profilo',id: 'CodProfilo',allowBlank: false,	name:'CodProfilo',	style:'text-align:left'},
						{fieldLabel:'Titolo',id: 'TitoloProfilo',allowBlank: false,	name:'TitoloProfilo',	style:'text-align:left'}
						]
			}],
	        
	       buttons: [{
				text: 'Salva profilo',
				handler: function() {
		    	   if (this.getForm().isDirty()) {	// qualche campo modificato
						this.getForm().submit({
							url: 'server/utentiProfili.php',
							method: 'POST',
							params: {task: 'addP'},
							success: function(){
								Ext.Msg.alert('Messaggio','Profilo salvato con successo.');
								wind.close();
							},
							failure: function(frm, action){
								Ext.Msg.alert('Errore', action.result.error);
							},
							scope: this,
							waitMsg: 'Salvataggio in corso...'
						});
					} else{
					//console.log("Non si è inserito nulla");
					}
				},
				scope: this	
			}]  // fine array buttons
		});
		DCS.pnlAddP.superclass.initComponent.call(this);
	}

});

DCS.GridProfiliUtenti = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
		
	initComponent : function() {
	//---------------------------	
	//Gestione tasto Nuovo Profilo
	//----------------------------
		var newRecord = function(btn, pressed)
		{
	   		
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();

			var pnl = new DCS.pnlAddP();
			wind = new Ext.Window({
	    		width: 350, height:300, minWidth: 250, minHeight: 300,
	    		autoHeight:true, modal:true,
	    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
				title: 'Nuovo profilo',
	    		constrain: true,
	    	    items: [pnl]
	        });
	       	wind.show();
			myMask.hide();
	    };      
	//Fine Gestione Tasto Nuovo Profilo
	
	//**----------------------------------	
	// Gestione griglia Profili 
	//-----------------------------------
		
		var fields = [{name: 'IdProfilo', type: 'int', allowBlank:false},
		      		{name: 'CodProfilo', type: 'string' },
		    		{name: 'TitoloProfilo', type: 'string' },
		    		{name: 'AbbrProfilo', type: 'string' },
		    		{name: 'NumeroUtenti', type: 'int' },
		    		{name: 'Ordine', type: 'int'},
		    		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		    		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		    		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		    		{name: 'LastUser', type: 'string'}];
					
		var columns = [
	        	{dataIndex:'IdProfilo',width:60, header:'IdProfilo',hidden:true,filterable:true,groupable:true,sortable:true},
	        	{dataIndex:'CodProfilo',	width:60,	header:'Codice',hidden:true,filterable:true,groupable:true,sortable:true},
	        	{dataIndex:'TitoloProfilo',	width:40,	header:'Titolo',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'AbbrProfilo',	width:40,	header:'Abbreviazione',filterable:true,sortable:true,groupable:true},
	        	{dataIndex:'NumeroUtenti', width:50, header:'N. utenti',hideable:false,exportable:true,stateful:true},
	        	{dataIndex:'DataIni',width:30,xtype:'datecolumn', format:'d/m/Y',	header:'Valido dal',align:'left', filterable: true, groupable:true, sortable:true},
	        	{dataIndex:'DataFin',width:30,xtype:'datecolumn', format:'d/m/Y',	header:'al',align:'left', filterable: true, groupable:true, sortable:true}
	//{dataIndex:'titolofunzione',	width:60,	header:'Gruppi di funzioni',filterable:true,sortable:true,groupable:true}
		        ];
		
		switch (this.task)
		{
			case "gruppoCodice":
				groupOn = "CodProfilo";
				break;
			default: 
				groupOn = '';
		}
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/utentiProfili.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task},
			remoteSort: true,
			groupField: groupOn,
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
				groupTextTpl: 'Profilo:  {[values.rs[0].data["CodProfilo"]]}({[values.rs[0].data["TitoloProfilo"]]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			columns: columns,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					showPFDetail(rec.get('IdProfilo'),rec.get('TitoloProfilo'),this.gstore,rowIndex);
					//this.showListaFunzioni(rec.get('IdGruppo'),rec.get('titolofunzione'),rec.get('IdProfilo'));
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

		var tbarItems = [{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
		            '->',  {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNp',
							pressed: false,
							enableToggle:false,
							text: 'Nuovo profilo',
							handler: newRecord
							},
	                '-',	{xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDp',
							pressed: false,
							enableToggle:false,
							text: 'Cancella profilo',
							handler: this.showListaProfili,
							scope: this
							},
					'-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Profili"),' '
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

		DCS.GridProfiliUtenti.superclass.initComponent.call(this, arguments);
	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaFunzioni: function(gruppo,nomeG,idprofilo)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var win;
		var pnl = new DCS.pnlFuncList({gruppo: gruppo, nome_gruppo: nomeG, profilo: idprofilo});
		win = new Ext.Window({
    		width: 350, height:620,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Azioni assegnabili',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:win});
    	win.show();
		myMask.hide();
		pnl.activation.call(pnl);
    },
    //-----------------------------
    //Visualizza profili da cancellare
    //------------------------------
	showListaProfili: function()
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.pnlProfList();
		var win = new Ext.Window({
    		width: 450, height:620,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Profili',
    		constrain: true,
			items: [pnl]
        });
    	win.show();
		myMask.hide();
		pnl.activation.call(pnl);
    }
});
// Fine gestione griglia Macrofunzioni utente
  
//---------------	
//Tabs della griglia
//----------------
DCS.GridUtentiProfili = function(){

	return {
		create: function(){
			var gridGestProfili = new DCS.GridProfiliUtenti({
				titlePanel: 'Gestione dei profili utente'
					+'<span class="subtit">'
					+'<br>La definizione del profilo serve a indicare quali funzioni pu&ograve; eseguire l\'utente a cui &egrave; assegnato il dato profilo;'
					+'<br> ad ogni utente si pu&ograve;, in teoria, assegnare pi&ugrave; di un profilo, ma questo non &egrave;, in genere, consigliabile.</span>',
				title: 'Tipo di profilo',
				task: "readProfMain"
			});

			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [gridGestProfili]
			})
		}
	};
	
}();
