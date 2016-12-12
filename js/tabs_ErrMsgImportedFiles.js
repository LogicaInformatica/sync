//Grid  lista errori dell'elaborazione nei processi batch dei file di import
Ext.namespace('DCS');

DCS.GridErrMessage = Ext.extend(Ext.grid.GridPanel, {
	pagesize: 10,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	idLog:'',

	initComponent : function() {
        	var fields = [
    	              {name: 'IdLog',type: 'int'},
    	              {name: 'Campo',type: 'string'},
    	              {name: 'Messaggio',type: 'string'}
    	            ];

    	var columns = [
    		        	{dataIndex:'Campo',	width:80,	header:'Chiave',sortable:true,align:'left'},
    		        	{dataIndex:'Messaggio',	width:500,	header:'Messaggio',align:'left'}
    		          ];

		this.gstore = new Ext.data.Store({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/listaFileImport.php',
				method: 'POST'
			}),   
			baseParams:{idLog: this.idLog,task: this.task},
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
			columns: columns
			});

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
	                '->', {type:'button',text: 'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaMessaggiImport"),' '
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
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		DCS.GridErrMessage.superclass.initComponent.call(this, arguments);
		
		this.activation();
	},
	
	activation: function() {
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

}); // fine grid


DCS.visErrMsg = function(){

	return {
		create: function(MyidLog){
			var gridErrMsg = new DCS.GridErrMessage({
				titlePanel: 'Lista messaggi di import',
				title: '',
				task: "msg",
				flex: 1,
				idLog: MyidLog
			});
			return gridErrMsg;
		}
	};
	
}();

