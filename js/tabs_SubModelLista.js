/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.pnlModList = Ext.extend(Ext.grid.GridPanel, {
	pagesize: 0,
	//IdModOriginale: '',
	winList:'',
	//originalStore:'',
	initComponent : function() {
		var selM = new Ext.grid.CheckboxSelectionModel({singleSelect:true});
	
		var locFields = [{name: 'IdModello', type: 'int'},
		                 {name: 'tipo'},
		                 {name: 'tipomodello'}];
		
		var columns = new Ext.grid.ColumnModel({
				columns: [selM,
			{dataIndex:'IdModello',width:45, hidden:true,header:'IdM',align:'left', filterable: false},
			{dataIndex:'tipo',	width:30, hidden:true,header:'tipo',filterable:false,sortable:false},
			{dataIndex:'tipomodello',	width:80, hidden:false,header:'Modello',filterable:false,sortable:false}
			]});
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/ana_modelli.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readModels'},

			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				idProperty: 'IdModello', //the property within each row object that provides an ID for the record (optional)
				fields: locFields
			})
  		});

		var titolo = 'Selezionare un modello: ';
		
		var continua = new Ext.Button({
			sm:selM,
			store:this.store,
			text: 'continua',
			handler: function(grid,rowIndex,colIndex) {
				if (selM.hasSelection()) 
				{
					var rec = selM.getSelected();
					var mod = rec.get('tipo');
					
					switch (mod)
					{
						case 'E':
							//editor e-mail
							DCS.FormMailModel.showDetailMailModel();
							this.winList.close();
						break;
						case 'L':
							//editor lettera
							DCS.FormLetteraModel.showDetailLetteraModel();
							this.winList.close();
						break;
						case 'S':
							//editor sms
							DCS.FormSMSModel.showDetailSMSModel();
							this.winList.close();
						break;
					}
				}
			},
			scope: this
		});
		
		Ext.apply(this,{
			store: gstore,
			titlePanel: titolo,
			fields: locFields,
			colModel: columns,
			sm: selM,
			buttons: [continua]
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
		
		DCS.pnlModList.superclass.initComponent.call(this, arguments);
	},
	activation: function() {
		var lastOpt = this.store.lastOptions;
		if (!lastOpt) {
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
