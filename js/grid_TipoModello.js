// Crea namespace DCS
Ext.namespace('DCS');

var winS;

DCS.GridModello = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	hideStato: false,
	titlePanel: '',
	idMod:'',
	initComponent : function() {
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false});
		
		var btnMenuAzioni = new DCS.SceltaModelli();
		
		var locFields = [{name: 'IdModello', type: 'int', allowBlank:false},
		         		{name: 'TitoloModello', allowBlank:false},		// Codice abbreviato dello stato
		        		{name: 'TitoloTipoAllegato', allowBlank:false},
		        		{name: 'TipoModello', type: 'string'},
		        		{name: 'descrTMod', type: 'string'},
		        		{name: 'FileName', type: 'string'},
		        		{name: 'IniVal', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		        		{name: 'FinVal', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		        		{name: 'lastMod', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		        		{name: 'lastU', type: 'string'}];
		
		var columns = [selM,
		    	    {dataIndex:'IdModello',	width:10,	header:'ID',hidden: true,hideable: false,filterable:false,sortable:false,groupable:false},
		        	{dataIndex:'TitoloModello',width: 50,header: "Modello",align:'left', filterable: true, groupable:false, sortable:false},
		        	{dataIndex:'TitoloTipoAllegato',width:60,header: 'Allegato',align:'left', filterable: true, sortable:true,groupable:false},
		        	{dataIndex:'TipoModello',width:10,hidden: true,header: "Tipo",hideable: false,filterable:false,sortable:false,groupable:true},
		        	{dataIndex:'descrTMod',width: 50,header: "Modello",align:'left', filterable: true, groupable:false, sortable:false},
		        	{dataIndex:'IniVal',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Inizio Validit&agrave;.',align:'left', filterable: true, groupable:false, sortable:false},
		        	{dataIndex:'FinVal',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Fine Validit&agrave',align:'left', filterable: true, groupable:false, sortable:false},
		        	{dataIndex:'lastMod',width:30,xtype:'datecolumn', format:'d/m/y',	header:'Ultima modifica',align:'left', hidden:true,filterable: true, groupable:false, sortable:false},
		        	{dataIndex:'lastU',	width:20,	header:'Utente',hidden:true,filterable:false,sortable:false,groupable:false}];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/ana_modelli.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: 'read'},
			remoteSort: true,
			groupField: 'descrTMod',
			groupDir: 'ASC',
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: locFields
			})
  		});

		Ext.apply(this,{
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true,
	            getRowClass : function(record, rowIndex, p, store){
                if(rowIndex%2)
                {
			        return 'grid-row-azzurrochiaro';
                }
		        return 'grid-row-azzurroscuro';
			}
           }),
			columns: columns,
			sm: selM,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					var modello = rec.get('TipoModello');
					switch (modello) {
					case 'E':
						DCS.FormMailModel.showDetailMailModel("","",rec.get('IdModello'));
						break;
					case 'W':
						DCS.FormMailModel.showDetailMailModel("","",rec.get('IdModello'));
						break;
					case 'S':
						DCS.FormSMSModel.showDetailSMSModel("","",rec.get('IdModello'));
						break;
					case 'L':
						DCS.FormLetteraModelText.showDetailLetteraModelText("","",rec.get('IdModello'));
						break;
					case 'X':
						DCS.FormSubModel.showDetailSubModel("","",rec.get('IdModello'));
						break;
					case 'H':
						DCS.FormLetteraModelWord.showDetailLetteraModelWord("","",rec.get('IdModello'));
						break;
					}
				},
				activate: this.activation,
				scope: this
			}
	    });

		Ext.applyIf(this, {
			store: gstore
		});
		
		var tbarItems = [{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
		        '->',btnMenuAzioni,
 				'-', { //add a separator
 					ref: '../removeBtn',
 					text: 'Cancella modello',
 					id:'remove',
 					tooltip: 'Elimina le righe selezionate',
 					iconCls:'grid-remove', 
 					handler: function(){
		 					var array = selM.getSelections();
		 					var i = selM.getCount();
		 					var vectIdM = '';
		 					var vectTmod = '';
		 					var vectFname = '';
		 					if (i>0){
		 						for (j=0;j<i;j++)
		 						{
		 							vectIdM = vectIdM + '|' + array[j].get('IdModello');
		 							vectTmod = vectTmod + '|' + array[j].get('TipoModello');
		 							vectFname = vectFname + '|' + array[j].get('FileName');
		 						}
		 					}
		 					Ext.Ajax.request({
				        		url : 'server/ana_modelli.php' , 
				        		params : {task: 'delete',model:vectTmod,idM:vectIdM,nomeF:vectFname},
				        		method: 'POST',
				        		success: function ( result, request ) {
				        			var jsonData = Ext.util.JSON.decode(result.responseText);
				        			Ext.MessageBox.alert('Esito', jsonData.error);
				        		},
				        		failure: function ( result, request) { 
				        			Ext.MessageBox.alert('Errore', result.responseText);
				        		},
				        		scope: this 
				        	});
							gstore.reload();
 					},
 					scope: this,
 					disabled: true 
 				},
                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
                '-', helpButton("Modelli"),' '
 			];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridModello.superclass.initComponent.call(this, arguments);
		this.activation();
		
		selM.on('selectionchange', function(selm) {
			this.getTopToolbar().getComponent(4).setDisabled(selm.getCount() < 1);
		}, this);
	},

	activation: function() {
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
	
	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaModelli: function()
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();

		var pnl = new DCS.pnlModList(/*{IdModOriginale:this.idMod, originalStore:this.store}*/);
		winS = new Ext.Window({
    		width: 130, height:200, minWidth: 130, minHeight: 200,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Modelli disponibili',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winS});
		winS.show();
		myMask.hide();
		pnl.activation.call(pnl);
    }
});

DCS.Modelli = function(){

	return {
		create: function(){
			var subtitle = '';
			return new DCS.GridModello({hideStato: true,flex: 1, titlePanel: 'Lista dei modelli'+subtitle});
		}
	};
}();


