/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.pnlProcList = Ext.extend(Ext.grid.GridPanel, {
	innerColumns: null,
	pagesize: 0,
	winL:'',
	
	initComponent : function() {
		var selM = new Ext.grid.CheckboxSelectionModel();
	
		var locFields = [{name: 'IdProcedura', type: 'int', allowBlank:false},
				      		{name: 'CodProcedura', type: 'string' },
				    		{name: 'TitoloProcedura', type: 'string' },
				    		{name: 'Ordine', type: 'int'},
				    		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
				    		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
				    		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
				    		{name: 'LastUser', type: 'string'},
				    		{name: 'Ordine', type: 'string'},
				    		{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
				    		{name: 'UrlDocProcedura', type: 'string'}];
		
		var columns = new Ext.grid.ColumnModel({
				columns: [selM,
				          {dataIndex:'IdProcedura',width:60, header:'IdProfilo',hidden:true,filterable:true,groupable:true,sortable:true},
				        	{dataIndex:'CodProcedura',	width:100,	header:'Codice',filterable:true,groupable:true,sortable:true},
				        	{dataIndex:'TitoloProcedura',	width:300,	header:'Titolo',hidden:false,filterable:true,sortable:true,groupable:true}]});
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readList'},

			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				idProperty: 'IdProcedura', //the property within each row object that provides an ID for the record (optional)
				fields: locFields
			})
  		});

		var titolo = 'Procedure';
		
		var cancella = new Ext.Button({
			sm:selM,
			store:this.store,
			text: 'Elimina procedure',
			handler: function(grid,rowIndex,colIndex) {
				var array = selM.getSelections();
				var i = selM.getCount();
				var vect = '';
				if (i>0){
					for (j=0;j<i;j++)
					{
						vect = vect + '|' + array[j].get('IdProcedura');
					}
				}else{
					console.log("no record");
				}
				Ext.Ajax.request({
			        url: 'server/gestioneProcedure.php',
			        method: 'POST',
			        params: {task: 'delete',vect: vect},
			        success: function(obj) {
			            var resp = obj.responseText;
			            //console.log("res "+resp);
			            if (resp == '' && vect!='') {
			                Ext.MessageBox.alert('Esito', 'Le procedure selezionate sono state cancellate');
			                this.store.load();
			            } else {
			            	if(resp!=''){
				                Ext.MessageBox.alert('Esito', resp);
				                this.store.load();			            		
			            	}
			            }
					},
					scope: this,
					waitMsg: 'Salvataggio in corso...'
			    });
			},
			scope: this
		});
		
		var annulla = new Ext.Button({
			text: 'Chiudi',
			handler: function(grid,rowIndex,colIndex) {
				this.winL.close();
			},
			scope: this
		});
		
		Ext.apply(this,{
			height: 442,
			store: gstore,
			titlePanel: titolo,
			fields: locFields,
			colModel: columns,
			sm: selM,
			buttons: [cancella,annulla]
		});
		
		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					//displayMsg: 'Righe {0} - {1} di {2}',
					//emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
		}
		
		DCS.pnlProcList.superclass.initComponent.call(this);
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
});
