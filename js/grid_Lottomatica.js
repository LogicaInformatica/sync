// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridLottomatica = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: '',
	idObj:'',
	
	initComponent : function() { 
		
		this.idObj=this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:true});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAut',
	            width: 60,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: [{
	            	icon   : 'images/delete.gif',               
                    tooltip: 'Elimina file',
	                handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var IdLottomatica = rec.get('IdLottomatica');
							//si sta cancellando la selezione: ok
							Ext.Ajax.request({
						        url: 'server/processLottomatica.php',
						        method: 'POST',
						        params: {task: 'delete',id: IdLottomatica},
						        success: function(obj) {
						        	eval('var resp = '+obj.responseText);
						        	Ext.MessageBox.alert('Esito', resp.error);
						        	grid.getStore().reload();
								},
								failure: function (obj) {
									eval('var resp = '+obj.responseText);
	                    			Ext.MessageBox.alert('Errore', resp.error); 
	                    		},
								scope: this,
								waitMsg: 'Eliminazione in corso...'
						    });
					},
					scope: this
	            }]
			};

		var fields = [{name: 'IdLottomatica', type: 'int'},
							{name: 'NomeFile'},
							{name: 'DataCreazione', type:'date', dateFormat: 'Y-m-d H:i:s'},
							{name: 'lastupd', type:'date', dateFormat: 'Y-m-d H:i:s'},
							{name: 'LastUser'}];

    	var columns = [selM,
    	               	{dataIndex:'IdLottomatica',width:10,hidden: true, hideable: false, header:'IdA',filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'NomeFile',	width:140,	header:'Nome', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'DataCreazione',	width:100,xtype:'datecolumn', format:'Y-m-d H:i:s', header:'Data caricamento',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'lastupd',	width:100,xtype:'datecolumn',header:'Last update',filterable:true,sortable:true,groupable:false,hidden:true},
    		        	{dataIndex:'LastUser',	width:70,header:'Nome utente',filterable:true,sortable:true,groupable:false}
    		        	/*,
    		        	actionColumn cancellazione file tolta il 25/1 */
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/processLottomatica.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});
		
		Ext.apply(this,{
			store: gstore,
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
					this.showListaPraticheFile(rec.get('IdLottomatica'),rec.get('NomeFile'));
				},
				scope: this
			}
	    });
		
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							pressed: false,
							enableToggle:false,
							text: 'Importa file',
							handler: function(){
								DCS.showImportLottForm.create(this.idObj);
							},
							scope:this
					},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaLottomatica"),' '
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

		DCS.GridLottomatica.superclass.initComponent.call(this, arguments);
		this.activation();
		//this.store.load();
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);

	},
	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaPraticheFile: function(IdLottomatica,nomeF)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		// Compone il sottotitolo del pannello di dettaglio
		titolo = "Lista delle pratiche relative file lottomatica '"+nomeF+"'";
		
		var pnl = new DCS.PnlLott({IdLottomatica:IdLottomatica,titolo:titolo, IdC: 'dettaglioLott'});
		var win = new Ext.Window({
    		width: 700, height:600, minWidth: 700, minHeight: 500,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Lista di dettaglio',
    		constrain: true,
			items: [pnl]
        });
    	win.show();
		myMask.hide();
		pnl.activation.call(pnl);
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
	}
});

DCS.GridGestLottomatica = function(){

	return {
		create: function(){
			var gridGestLot = new DCS.GridLottomatica({
				titlePanel: 'Gestione dei files lottomatica',
				//title: 'Utenti presenti',
				groupOn: "",
				flex: 1,
				task: "read"
			});

			return gridGestLot;
		}
	};
	
}();