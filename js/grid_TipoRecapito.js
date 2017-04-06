try
{
	var tableTRecapito = new DCS.Table();
	tableTRecapito.name = "tiporecapito";
	tableTRecapito.pk = "IdTipoRecapito";
	tableTRecapito.expandCol = "titolo";

	//------------------------- Record -------------------------
	tableTRecapito.record = Ext.data.Record.create([
		{name: 'IdTipoRecapito', type: 'int', allowBlank:false},
		{name: 'CodTipoRecapito', type: 'string' },
		{name: 'TitoloTipoRecapito', allowBlank:false},
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);

	//------------------------- ColumnModel -------------------------
	tableTRecapito.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdTipoRecapito',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'CodTipoRecapito',
				header: "Codice",
				//resizable: false,//disable column resizing (can also use fixed = true)
				width: 10,			
				//TextField editor - for an editable field add an editor
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			}, {
				id: 'titolo',
				dataIndex: 'TitoloTipoRecapito',
				header: 'Titolo',
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			},{
				dataIndex: 'Ordine',
				header: "Posizione",
				editor: new Ext.form.NumberField({
	                allowBlank: true,
	                allowNegative: false,
	                maxValue: 100000
	            }),
				align:'center',
				width: 20
			},{
				dataIndex: 'DataIni',
				header: "Inizio Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			}, {
				dataIndex: 'DataFin',
				header: "Fine Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			}, {
				dataIndex: 'LastUpd',
				header: "Ultima modifica",
				renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'),
				width: 20
			}, {
				dataIndex: 'LastUser',
				header: "Utente",
				width: 12
			}]
		}
	);
	
	tableTRecapito.newRecord = function() {
		return new tableTRecapito.record({	//specify default values
			IdTipoRecapito: 0,
			CodTipoRecapito: '', 
			TitoloTipoRecapito: '',
			Ordine:1,
			DataIni: (new Date()).clearTime(),
			DataFin: (new Date()).clearTime(),
			LastUpd: new Date(),
			LastUser: ''
		});
	};

	//--------------------------------
	//  Lista Azioni sulle pratiche
	//--------------------------------
	var fn_anagTRecapito= function() {
		var gridTRecapito = new EditGrid(tableTRecapito);
	
		return gridTRecapito.getGrid();
	};
}
catch (e)
{
	alert("grid_TipoRecapito.js: " + e);
}
