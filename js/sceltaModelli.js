Ext.namespace('DCS');

DCS.SceltaModelli = Ext.extend(Ext.Button, {
	disabled: false,

	initComponent: function() {
		
		var locFields = [{name: 'IdModello', type: 'int'},
		                 {name: 'tipo'},
		                 {name: 'tipomodello'}];
		
		var columns = new Ext.grid.ColumnModel({
				columns: [
			{dataIndex:'IdModello',width:45, hidden:true,header:'IdM',align:'left', filterable: false},
			{dataIndex:'tipo',	width:30, hidden:true,header:'tipo',filterable:false,sortable:false},
			{dataIndex:'tipomodello',	width:80, hidden:false,header:'Modello',filterable:false,sortable:false}
			]});
		
		this.gstore = new Ext.data.GroupingStore({
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
		
		Ext.apply(this, {
			text: 'Nuovo modello',
			//icon: 'ext/examples/shared/icons/fam/table_refresh.png',
			iconCls:'grid-add', 
			menu: {
				xtype: 'menu',
				id:'menu-scelta-mod',
				items: [{icon: 'ext/resources/images/access/grid/loading.gif', text:'Attendere caricamento...'}]/*,
				listeners:{
					click: function(menu,items,e){
						menu.disabled=true;
						console.log("disable");
					}
				}*/
			},
			handler: this.caricaMenu,
			scope: this
		});

		DCS.SceltaModelli.superclass.initComponent.call(this);

	},

	caricaMenu: function() {
		
		this.gstore.load({
			callback : function(r,option,success){
				Ext.getCmp('menu-scelta-mod').removeAll();
				for(var k=0;k<r.length;k++){
					switch (r[k].get('tipo'))
					{
						case 'E':
							//editor e-mail
							Ext.getCmp('menu-scelta-mod').add({text:r[k].get('tipomodello'),handler : DCS.FormMailModel.showDetailMailModel});
						break;
						case 'L':
							//editor lettera
							Ext.getCmp('menu-scelta-mod').add({text:r[k].get('tipomodello'),handler : DCS.FormLetteraModelText.showDetailLetteraModelText});
						break;
						case 'S':
							//editor sms
							Ext.getCmp('menu-scelta-mod').add({text:r[k].get('tipomodello'),handler : DCS.FormSMSModel.showDetailSMSModel});
						break;
						case 'X':
							//editor submodelli
							Ext.getCmp('menu-scelta-mod').add({text:r[k].get('tipomodello'),handler : DCS.FormSubModel.showDetailSubModel});
						break;
						case 'H':
							//editor lettera stampa online
							Ext.getCmp('menu-scelta-mod').add({text:r[k].get('tipomodello'),handler : DCS.FormLetteraModelWord.showDetailLetteraModelWord});
						break;
					}
				}
				Ext.getCmp('menu-scelta-mod').doLayout();
			}
		});
	}

});