/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.pnlAAList = Ext.extend(Ext.grid.GridPanel, {
	IdAut: '',
	nome_Aut:'',
	profilo:'',
	innerColumns: null,
	pagesize: 0,
	winList:'',

	initComponent : function() {
			
		var locFields = [{name: 'IdAzione', type: 'int'},
						{name: 'IdAutomatismo', type: 'int'},
						{name: 'CodAzione'},
						{name: 'TitoloAzione'}];
		
		var columns = new Ext.grid.ColumnModel({
				columns: [
			{dataIndex:'IdAzione',width:45, hidden:true,header:'IdAz',align:'left', filterable: false},
			{dataIndex:'IdAutomatismo',	width:45, hidden:true,header:'IdAut',filterable:false,sortable:false},
			{dataIndex:'CodAzione',	width:120, hidden:false,header:'Codice',filterable:false,sortable:false,groupable:false},
			{dataIndex:'TitoloAzione',	width:260,	header:'Azione',align:'left',filterable:false,sortable:false}
			]});
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAutomatismi.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readAzAut', Aut: this.IdAut},

			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				idProperty: 'IdAzione', //the property within each row object that provides an ID for the record (optional)
				fields: locFields
			})
  		});

		var titolo = 'Azioni dell\' automatismo: "'+this.nome_Aut+'"';
		
		Ext.apply(this,{
			height: 442,
			store: gstore,
			titlePanel: titolo,
			fields: locFields,
			colModel: columns
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
		
		DCS.pnlAAList.superclass.initComponent.call(this, arguments);
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
