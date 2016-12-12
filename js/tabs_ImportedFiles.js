//grid lista dei file di import elaborati dai processi batch 
Ext.namespace('DCS');

DCS.Gridfile = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	initComponent : function() {
    
	if (this.task=='file') {
		var fields = [{name: 'IdImportLog',type: 'int'},
    	              {name: 'IdCompagnia',type: 'int'},
    	              {name: 'ImportTime'},
    	              {name: 'FileType',type: 'string'},
    	              {name: 'FileId',type: 'int'},
    	              {name: 'ImportResult',type: 'string'},
    	              {name: 'Status',type: 'string'},
    	              {name: 'lastupd'},
    	              {name: 'Message',type: 'string'}
    	            ];

    	var columns = [
    	               	{dataIndex:'IdImportLog',width:80,hidden: true, header:'IdImportLog',filterable:true,sortable:true,align:'center'},
    		        	{dataIndex:'FileId',width:60, header:'IdFile',filterable:true,sortable:true,align:'center'},
    		        	{dataIndex:'FileType',	width:80,	header:'Tipo file',filterable:true,sortable:true,align:'left'},
    		        	{dataIndex:'ImportTime',	width:120,	header:'Ora di arrivo',filterable:true,sortable:true,align:'center'},
    		        	{dataIndex:'ImportResult',	width:50,	header:'Esito',filterable:true,sortable:true,align:'center'},
    		        	{dataIndex:'Status',	width:80,	header:'Stato',sortable:true,align:'center'},
    		        	{dataIndex:'lastupd',	width:120,	header:'Ora di elab.',sortable:true,align:'center'},
    		        	{dataIndex:'Message',	width:350,	header:'Messaggio',align:'left',sortable:true}

    		          ];
	} else {
		var fields = [{name: 'Severity'},
    	              {name: 'LogMessage'},
    	              {name: 'lastupd'}
    	            ];

    	var columns = [
    		        	{dataIndex:'lastupd',	width:120,	header:'Ora di elab.'},
    		        	{dataIndex:'Severity',	width:120,	header:'Livello'},
    		        	{dataIndex:'LogMessage',width:350,	header:'Messaggio'}
    		          ];
	}
	this.gstore = new Ext.data.Store({
		autoDestroy: true,
		proxy: new Ext.data.HttpProxy({
			url: 'server/listaFileImport.php',
			method: 'POST'
		}),   
		baseParams:{task: this.task},
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
		columns: columns,
		viewConfig: {
			autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
			forceFit: false,
		    getRowClass : function(record, rowIndex, p, store){
	                if(record.get('ImportResult') =='Fallito' || record.get('Severity')=='Errore'){
	                    return 'grid-row-rosso';
	                }
	                if (record.get('ImportResult') =='Ok'){
	                    return 'grid-row-verdechiaro';
	                }
	         }
		},
			
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showListaMessaggi(rec.get('IdImportLog'),rec.get('FileId'),rec.get('FileType')); 
				},
				scope: this
			}
	    });
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
	                '->', {type:'button',text: 'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("FilesImportati"),' '
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
		DCS.Gridfile.superclass.initComponent.call(this, arguments);
		
		this.activation(); 
	},
	
	showListaMessaggi: function(IdLog,IdFile,FileType)
	{
		
		 var win = new Ext.Window({
		    	modal: true,
		        width: 600,
		        height: 350,
		        layout: 'fit',
		        minHeight: 350,
		        minWidth: 600,
		        plain: true,
				constrainHeader: true,
		        title: 'Lista messaggi di errore file '+ IdFile +" tipo '"+FileType+"'",
		        items: [DCS.visErrMsg.create(IdLog)]
		    });
		    win.show();
		return;
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


DCS.visFile = function(){
	return {
		create: function(){
			var gridMonitor = new DCS.Gridfile({
				titlePanel: 'Monitor del processo di acquisizione',
				stateId: 'MonitorAcquisizione',
				stateful: true,
				title: 'Monitor',
				task: "monitor"
			});
			var gridImportedFiles = new DCS.Gridfile({
				titlePanel: 'Lista file importati',
				stateId: 'ListaFilesImportati',
				stateful: true,
				title: 'Lista file importati',
				task: "file"
			});
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: [gridMonitor, gridImportedFiles]
			})
		}
	};
	
}();




